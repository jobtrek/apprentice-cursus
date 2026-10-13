# Stack: Automatic reading in Laravel

Written 2026-10-13, updated the same day from `~/dev/ocr_evaluation/RECOMMENDATION.md` (all 27 saved OCR runs scored on the same 67 PDFs). Decision record: `docs/adr/ADR.md` → "Automatic reading runs inside Laravel, not as a Python service". Terms follow the root [`CONTEXT.md`](../../CONTEXT.md) (Manual entry, Automatic reading, Reading, Pre-fill, Flagged, Confirmation, Processing consent).

Flow diagram: [`diagrams/laravel.d2`](diagrams/laravel.d2).

## Scope

**Ported 1:1 from notar v2** (89.6% both fields right on 67 PDFs, $0.0014 per PDF). Every parameter stays as measured, or the number no longer applies:
1. Render page 1 as PNG, 200 dpi, full page; pages 2–3 only when page 1 has no grade
2. Call 1, Gemini `gemini-3.5-flash-lite`, temperature 0, `maxOutputTokens` 8192, notar's `CALL1_PROMPT` unchanged, image before prompt → `{text, subject, grade, grade_box}`
3. Loop guard: `finishReason != STOP` → page is truncated, try the next page
4. Module rule: regex on `text` → Test type = the printed code
5. Otherwise Jev `jev-1.13.0` Choice on `text` (via `laravel/ai` Classification) → Test type = Jev's pick (Gemini's `subject` is not used)
6. Grade ∈ {1.0 … 6.0, step 0.5}, else null
7. Flag rule (see below)
8. Same-PDF cache: `sha256(pdf)` + config hash

**Later, after the bench passes** (rollout step 3): top-half retry on truncated pages (fixes `2_3_Eco&Droit_5`), the Module codes in `subjects.json`, a grade re-read on the `grade_box` crop when the grade is missing (3 of the 5 grade misses).

**Dropped:** the FastAPI service, the Python benchmark, the stronger-model tie-break, the embeddings vote, the opt-in consent, the vector column, GLM-OCR and Chandra (both lose to notar v2 with McNemar p < 0.0001).

## Flag rule

Flagged = **grade missing OR Jev confidence < 0.5 OR no readable page.**

| Rule (same 67 PDFs) | Flags | Errors caught | Silent errors | Precision | Recall |
|---|---|---|---|---|---|
| notar v2 (Gemini≠Jev, `OTHER`, invalid grade) | 10 | 1 / 7 | 6 | 0.10 | 0.14 |
| **grade missing OR Jev < 0.5** | **8** | **5 / 7** | **2** | **0.62** | **0.71** |

The 0.5 threshold was picked on the same PDFs it was scored on. Re-check it once ~30 Confirmations exist. One wrong Jev pick had confidence 0.99, so Confirmation stays mandatory on every reading.

## Flow

1. The apprentice picks Manual entry or Automatic reading.
2. Automatic reading needs **Processing consent**: a checkbox "I accept that my scan is sent to Google and TypeSafe". Unticked → Manual entry only. Not stored.
3. Upload (`StoreTestUploadRequest`: PDF only, 10 MB) → the PDF is stored, a `test_readings` row is created (`pending`), the `ReadTest` job is queued.
4. The page polls the reading every 2 s (Inertia `usePoll`).
5. Job outcome:
   - Not Flagged → confirm form, pre-filled. Confirmation is required.
   - Flagged → the same confirm form, pre-filled, with a warning and every Reading shown. A missing grade is left empty for the apprentice to type.
   - Job failed after retries → Manual entry, scan kept.
6. Confirmation saves the grade through the same endpoint as Manual entry, and records `confirmed_type`, `confirmed_grade`, `edited` on the reading.

## Layers

| Concern | Choice | Notes |
|---|---|---|
| Framework | Laravel 13 (PHP 8.3+), Inertia + Vue 3, Tailwind 4, shadcn-vue | Existing app stack |
| Queue | Laravel queue, `database` driver | `ReadTest` job, `tries` + backoff, retries 5xx/429 only; `failed()` marks the reading failed |
| Progress | Inertia `usePoll` (2 s) on the reading status | No websockets |
| State until Confirmation | `test_readings` table | `pdf_sha256`, `config_hash`, `result` JSON, `confirmed_type`, `confirmed_grade`, `edited`. The edit rate is the production accuracy signal |
| Same-PDF cache | Lookup on `test_readings` (`pdf_sha256`, `config_hash`) | Config hash covers models, prompt, threshold, `subjects.json`. A hit makes no model calls |
| Gemini | `Http` facade → `…/models/gemini-3.5-flash-lite:generateContent` | Not `laravel/ai`: the loop guard needs `finishReason`, cost needs `usageMetadata` (thinking tokens included), and the call needs the exact JSON schema |
| Jev | `laravel/ai` v1.0 Classification, `typesafe` provider | `Classification::of($text)->questions(['subject' => new Choice($instructions, $criteria)])->classify(provider: 'typesafe')`. Criteria = Test types from `subjects.json`, keyed by name. Answer gives `choice`, `probabilities`, `confidence` (nullable), plus `$result->usage`. Experimental API: pin the package version |
| PDF → PNG | Imagick + Ghostscript | `setResolution(200, 200)`, `readImage("$path[$n]")`, flatten, PNG |
| Config | `config/ocr.php` + `resources/ocr/subjects.json` + `config/ai.php` + `.env` | `config/ocr.php`: pinned Gemini model, dpi, pages, token cap, prices, threshold 0.5. `config/ai.php`: the TypeSafe provider. `GEMINI_API_KEY`, `TYPESAFE_API_KEY` |
| Tests | Pest; `Http::fake()` for Gemini, `Classification::fake()` with `ChoiceAnswer` for Jev | Offline only |

## Packages and infra

Not in the app, the Sail image or `compose.yaml` today:

```bash
./vendor/bin/sail composer require laravel/ai:1.x.y   # exact version: Classification is experimental
./vendor/bin/sail artisan vendor:publish --provider="Laravel\Ai\AiServiceProvider"
./vendor/bin/sail artisan sail:publish   # then add php8.5-imagick + ghostscript to the runtime Dockerfile
./vendor/bin/sail build --no-cache
```

- Add a queue worker (`php artisan queue:work`): `compose.yaml` runs no worker yet.
- Add `GEMINI_API_KEY` and `TYPESAFE_API_KEY` to `.env.example` (`config/ai.php` reads both).
- `vendor:publish` also publishes the SDK's conversation-table migrations. This feature doesn't use them.
- Check before coding: `php -m | grep imagick`, `gs --version`.
- No change to the Postgres image (no vector column).

## Code layout

```
app/
  Http/Requests/StoreTestUploadRequest.php   # PDF only, size limit
  Jobs/ReadTest.php                          # runs TestReader, writes the test_readings row
  Services/Ocr/
    TestReader.php                           # process() port: page loop, module rule, Jev Classification call, decisions, flags, cost
    Gemini.php                               # one Http::post; text, subject, grade, grade_box, truncated, cost
    PdfPage.php                              # Imagick: page N → PNG at 200 dpi
  Console/Commands/OcrBench.php              # php artisan ocr:bench
config/ocr.php
resources/ocr/subjects.json                  # Test types + Module codes
database/migrations/…_create_test_readings_table.php
tests/Feature/TestReaderTest.php
```

## Checking the port

Rollout, in order:
1. Port with notar v2's **current** flag rule and run `php artisan ocr:bench` on notar's 67 PDFs (`real_table.json`). Pass = both fields right on ≥ 56/67 (89.6% minus the ~4-file run-to-run noise). About $0.10 per run. A gain under ~4 PDFs needs 3 runs to trust.
2. Switch to the new flag rule. Pure PHP, $0.
3. The "later" items in Scope.

Pest, faked Readings: module rule, page fallback, truncated page, invalid grade, each flag condition, cache hit makes no calls, failed job → Manual entry.

## Open questions

Before coding the pipeline:
1. Whether `laravel/ai`'s `Choice` `confidence` is the same number notar's Python SDK returned (the 0.5 threshold was set on it). Classify 2–3 of notar's PDFs' text and compare. If not, flag on `probabilityOf($choice)`.
2. How to pin `jev-1.13.0`. The SDK docs show no `model:` argument on `classify()`, only `provider:`. Check `config/ai.php` after publishing for a TypeSafe default model.
3. Whether Imagick's PNG matches pymupdf's at 200 dpi. Compare 1 page by eye.
4. School rules on sending student work to cloud services (pipeline-spec open question 4): the checkbox may need to become recorded consent.

Where the OCR side and this app disagree (to decide with the grade-store work):
5. **Test type → evaluation node.** No 1:1 map today. IT has three Culture générale nodes (`EvaluationTreeSeeder.php:150-152`), so `ECG` is ambiguous. IT has no MP variant, so the MP subjects of `mp.json` (Français, Allemand, Histoire, Eco&Droit) have no node. EC sends every school Subject to one node (`:205`) and has no Modules. Sciences and TIB have no node. Module leaves are named `"293 — …"` with no code column. The uncommitted `docs/db/schemas/mcd_current.d2` moves grades to `domain_id`.
6. **Ungraded tests.** A null grade is valid on the OCR side, but `grades.value` is NOT NULL with CHECK 1.0–6.0. Current plan: the apprentice types the grade or cancels.
7. **Module tests and scans.** The form hides the PDF field for Module tests (`GradeForm.vue:291`), so a Module test can't be read today.
8. **No grade store exists yet** (`GradeForm.vue` only emits `saved`, no `grades.store` route). Manual entry's store comes first; Confirmation posts to it.
9. **Retention.** The OCR spec deletes the scan after Confirmation; the user stories keep it as the grade's proof file, readable by supervisors. The app wins: the confirmed scan becomes `grades.file_path` on the private `local` disk. Unconfirmed uploads are deleted after 7 days (no prune job yet).
10. **Date and semester.** The pipeline reads neither. The confirm form keeps the date field (pre-filled with today); the semester is derived on the server.
11. **Module codes.** RECOMMENDATION.md says 40 codes; `resources/js/data/modules.json` has 41. Build `subjects.json`'s modules from `modules.json`.
