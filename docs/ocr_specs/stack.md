# Stack: Automatic reading in Laravel

Written 2026-10-13, updated the same day from [`RECOMMENDATION.md`](RECOMMENDATION.md) (all 27 saved OCR runs scored on the same 67 PDFs). Decision record: `docs/adr/ADR.md` → "Automatic reading runs inside Laravel, not as a Python service". Terms follow the root [`CONTEXT.md`](../../CONTEXT.md) (Manual entry, Automatic reading, Reading, Pre-fill, Flagged, Confirmation, Processing consent).

Flow diagram: [`diagrams/laravel.d2`](diagrams/laravel.d2). Docs for every library and API below: [`documentation.md`](documentation.md).

## Reference files

Copied from notar (private repo `ThomasLucking/Notar`) so the port needs nothing outside this repo:

| File                                                                                   | What it is                                                       |
| -------------------------------------------------------------------------------------- | ---------------------------------------------------------------- |
| [`resources/prompts/ocr.md`](../../resources/prompts/ocr.md)                           | notar's `CALL1_PROMPT`, verbatim. Do not reword it               |
| [`config/subjects.json`](config/subjects.json)                                         | notar's `subjects.json` (Test types; `modules` still empty)      |
| [`config/jev.json`](config/jev.json)                                                   | The Jev `Choice` request built from `subjects.json`              |
| [`data/ground_truth.json`](data/ground_truth.json)                                     | Answer key for the 67 benchmark PDFs (notar's `real_table.json`) |
| [`final_bench_mark.md`](final_bench_mark.md), [`RECOMMENDATION.md`](RECOMMENDATION.md) | Where the 89.6% and the flag-rule numbers come from              |

**Benchmark PDFs are not committed** (real student tests with names and grades; `docs/ocr_specs/pdf_data` is gitignored). Ask Thomas Lucking for the archive and unpack it to `docs/ocr_specs/pdf_data/`, so `pdf_data/pdfs/<file>.pdf` matches each `pdf_path` in `ground_truth.json`.

## Scope

**Ported 1:1 from notar v2** (89.6% both fields right on 67 PDFs, $0.0014 per PDF). Every parameter stays as measured, or the number no longer applies:

1. Render page 1 as PNG, 200 dpi, full page; pages 2–3 only when page 1 has no grade
2. Call 1, Gemini `gemini-3.5-flash-lite`, temperature 0, `maxOutputTokens` 8192, notar's `CALL1_PROMPT` unchanged → `{text, subject, grade, grade_box}`. notar sent the image before the prompt; `laravel/ai` sends the prompt first (see Layers → Gemini)
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

| Rule (same 67 PDFs)                           | Flags | Errors caught | Silent errors | Precision | Recall   |
| --------------------------------------------- | ----- | ------------- | ------------- | --------- | -------- |
| notar v2 (Gemini≠Jev, `OTHER`, invalid grade) | 10    | 1 / 7         | 6             | 0.10      | 0.14     |
| **grade missing OR Jev < 0.5**                | **8** | **5 / 7**     | **2**         | **0.62**  | **0.71** |

The 0.5 threshold was picked on the same PDFs it was scored on. Re-check it once ~30 Confirmations exist. One wrong Jev pick had confidence 0.99, so Confirmation stays mandatory on every reading.

## Flow

As built (synchronous; the queue, polling and same-PDF cache are "later"):

1. The apprentice picks Manual entry or Automatic reading (`AddGradeDialog.vue`; the switch is hidden when `OCR_ENABLED=false`).
2. Automatic reading needs **Processing consent**: a checkbox "I accept that my scan is sent to Google and TypeSafe", validated as `accepted` by `ReadScanRequest`. Not stored.
3. `POST /read-scan` (`ReadScanRequest`: PDF only, `GradeRequest::MAX_FILE_KB`; `can:create` grade, `throttle:ocr`) → `GradeController::readScan` reads the PDF in the request, creates an `ocr_scans` row and returns `{scan_id, subject, confidence, grade, flagged, duration_ms}`.
4. Outcome:
    - Not Flagged → the form is pre-filled. Confirmation is required.
    - Flagged → the same pre-filled form, with a warning. A missing grade is left empty for the apprentice to type.
5. Confirmation saves the grade through the same `grades.store` as Manual entry, with `ocr_scan_id` and `ocr_node_id`; the scan gets `grade_id` and `suggested_node_id`. Comparing the scan with its grade gives the edit rate (`OcrScanController`, local admin only).

## Layers

| Concern                  | Choice                                                                                         | Notes                                                                                                                                                                                                                                                                                                                                          |
| ------------------------ | ---------------------------------------------------------------------------------------------- | ---------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| Framework                | Laravel 13 (PHP 8.3+), Inertia + Vue 3, Tailwind 4, shadcn-vue                                 | Existing app stack                                                                                                                                                                                                                                                                                                                             |
| Execution                | Synchronous, in `GradeController::readScan`                                                    | Later: a queued job with `tries` + backoff and polling, if requests get too slow                                                                                                                                                                                                                                                               |
| Scan record              | `ocr_scans` table                                                                              | Uploader, subject, grade, Jev confidence, flag, pages read, Gemini and Jev cost, duration; `grade_id` + `suggested_node_id` once confirmed. Never stores the text. The edit rate is the production accuracy signal                                                                                                                             |
| Same-PDF cache           | Not built (later)                                                                              | Planned: lookup on `sha256(pdf)` + a config hash, so a hit makes no model calls                                                                                                                                                                                                                                                                |
| Gemini                   | `laravel/ai` agent (`GeminiService`), `gemini-3.5-flash-lite`, Gemini `interactions` endpoint  | Loop guard reads the last step's `finishReason`; cost from `usage` (thinking tokens included). Prompt is sent before the image, unlike notar: `ocr:bench` decides if that matters, else fall back to `Http::post` on `generateContent`                                                                                                         |
| Jev                      | `laravel/ai` v1.0 Classification, `typesafe` provider                                          | `Classification::of($text)->questions(['subject' => new Choice($instructions, $criteria)])->classify(provider: 'typesafe', model: 'jev-1.13.0')`. Criteria = Test types from `subjects.json`, keyed by name. Answer gives `choice`, `probabilities`, `confidence` (nullable), plus `$result->usage`. Experimental API: pin the package version |
| PDF → PNG                | Imagick + Ghostscript                                                                          | `setResolution(200, 200)`, `readImage("$path[$n]")`, flatten, PNG                                                                                                                                                                                                                                                                              |
| Config                   | `config/ocr.php` + `OcrSubject` enum + `config/ai.php` + `.env`                                | `config/ocr.php`: `enabled`, token prices. Model, temperature 0 and the 8192 token cap are attributes on `GeminiService`; the 0.5 threshold is `OcrScan::FLAG_BELOW`; Test types are `App\Enums\OcrSubject`. `config/ai.php`: the TypeSafe provider. `GEMINI_API_KEY`, `TYPESAFE_API_KEY`                                                    |
| Tests                    | Pest, `tests/Feature/ReadScanTest.php`; Gemini and Jev faked                                   | Offline only                                                                                                                                                                                                                                                                                                                                   |

## Packages and infra

- `laravel/ai` is pinned to `1.2.1` in `composer.json` (exact version: Classification is experimental), `config/ai.php` is published, and `GEMINI_API_KEY` / `TYPESAFE_API_KEY` are in `.env.example`.
- Imagick + Ghostscript are in the Sail 8.5 runtime (`php8.5-imagick`) and the production `Dockerfile`.
- `vendor:publish` also publishes the SDK's conversation-table migration. This feature does not use it, so it was deleted: do not re-publish it.
- No change to the Postgres image (no vector column).

## Code layout

```
app/
  Http/Requests/ReadScanRequest.php          # consent + PDF only, size limit
  Http/Controllers/GradeController.php       # readScan(): page loop (Imagick, 200 dpi), Jev call, grade check, flag, cost
  Http/Controllers/OcrScanController.php     # local-admin dashboard: flagged share, cost, unflagged accuracy
  Services/Ocr/
    GeminiService.php                        # laravel/ai agent: text, subject, grade, grade_box, truncated, usage
    JevService.php                           # laravel/ai Classification: OcrSubject + confidence + usage
  Enums/OcrSubject.php                       # Test types
  Models/OcrScan.php                         # FLAG_BELOW = 0.5
config/ocr.php
resources/prompts/ocr.md
database/migrations/2026_10_15_080000_create_ocr_scans_table.php
tests/Feature/ReadScanTest.php
```

Not built yet: the Module rule (the Test type is always Jev's pick today), the same-PDF cache, the queue, and `php artisan ocr:bench`.

## Checking the port

Rollout, in order:

1. Port with notar v2's **current** flag rule and run `php artisan ocr:bench` on notar's 67 PDFs (answer key: [`data/ground_truth.json`](data/ground_truth.json), a copy of notar's `real_table.json`). Pass = both fields right on ≥ 56/67 (89.6% minus the ~4-file run-to-run noise). About $0.10 per run. A gain under ~4 PDFs needs 3 runs to trust.
2. Switch to the new flag rule. Pure PHP, $0.
3. The "later" items in Scope.

Pest, faked Readings: module rule, page fallback, truncated page, invalid grade, each flag condition, cache hit makes no calls, failed job → Manual entry.

## Open questions

Before coding the pipeline:

1. ~~Same `confidence` as notar's Python SDK?~~ **Yes (checked 2026-10-13, `laravel/ai` 1.2.1).** `TypeSafeGateway` posts to the same `/systemone` endpoint and passes the API's `confidence` through unchanged. On 3 notar PDFs, same text, 2 calls each per SDK: same choice in all 12 calls; confidence Python 0.41–0.61, PHP 0.39–0.68. Jev is not deterministic: identical calls differ by up to 0.14, so a reading near 0.5 can flip between Flagged and not. Flag on `confidence`, not `probabilityOf($choice)` (that one is 0.03–0.06 higher).
2. ~~How to pin `jev-1.13.0`?~~ **`classify(provider: 'typesafe', model: 'jev-1.13.0')`**, or set `providers.typesafe.models.classification.default` in `config/ai.php` (the SDK's default is `jev-latest`). The answer's `meta->model` came back `jev-1.13.0`.
3. Whether Imagick's PNG matches pymupdf's at 200 dpi. Compare 1 page by eye.
4. School rules on sending student work to cloud services (pipeline-spec open question 4): the checkbox may need to become recorded consent.

Where the OCR side meets the grade tree (as built):

5. **Test type → grade node.** `AddGradeDialog.vue` maps only `Anglais` and `Maths` to a branch of the apprentice's plan (`SUBJECT_BRANCHES`). Any other Test type, or a branch missing from the plan, makes the apprentice pick the branch by hand. The mapping is recorded as `ocr_scans.suggested_node_id` to compare with the saved grade.
6. **Ungraded tests.** A null grade is valid on the OCR side, but `grades.value` is NOT NULL with CHECK 1.0–6.0. Approving needs a grade, so the apprentice types it or cancels.
7. **Grade store.** Confirmation posts to `grades.store`, the same route as Manual entry.
8. **Retention.** `readScan` does not store the PDF. The confirmed upload becomes the grade's proof file (`grades.file_path`, private disk); an unconfirmed one is never stored, so no prune job is needed.
9. **Date and semester.** The pipeline reads neither. The form keeps the date field (pre-filled with today); the semester is derived from `test_date` (`App\Support\Gradebook\Semester`).
10. **Module codes.** Not built. RECOMMENDATION.md says 40 codes; `resources/js/data/modules.json` has 41. Build the Module rule from `modules.json`.
