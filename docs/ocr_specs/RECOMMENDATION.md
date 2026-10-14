# OCR evaluation: every saved run, scored with scikit-learn, and the Laravel plan

Written 2026-10-13. Numbers come from `report.txt`, made by `uv run evaluate.py > report.txt` (no API calls, reads the saved results of `../OCR_recognition`, `../Notar`, `../glm_ocr`).

Every run is scored the same way: the 67 PDFs in `Notar/real_data/real_table.json` (the same file in all 3 repos), `CG` = `ECG`, `OTHER`/null = "none", a null in the answer key expects null. That is why a few numbers differ by 1 file from each repo's own report (for example GLM: 50.7% here, 49.3% in `glm_ocr/docs/BENCHMARK.md`).

## 1. Results (27 runs, 9 shown)

| Run | Subject acc | Subject κ | Macro-F1 | Grade acc | Grade MAE* | **Both** | 95% CI (Wilson) | McNemar p vs notar_v2 | $/PDF |
|---|---|---|---|---|---|---|---|---|---|
| **notar_v2** (Gemini + Jev, page fallback) | **97.0%** | **0.964** | **94.3%** | **91.0%** | 0.049 | **89.6%** | 80.0–94.8% | — | 0.0014 |
| OCR `best` gemini_jev, full page | 94.0% | 0.928 | 91.7% | 86.6% | 0.047 | 82.1% | 71.3–89.4% | **0.27 (not significant)** | 0.0012 |
| notar_v2, Gemini's subject only | 85.1% | 0.822 | 80.3% | 91.0% | 0.049 | 77.6% | 66.3–85.9% | 0.008 | 0.0014 |
| notar_v1 | 82.1% | 0.788 | 76.3% | 89.6% | 0.079 | 71.6% | 59.9–81.0% | 0.004 | 0.0012 |
| OCR `direct_subject` (×3 mean) | 78.6% | ~0.75 | ~79% | 85.6% | ~0.06 | 70.1% | — | ≤0.012 | 0.0004 |
| OCR baseline gemini_jev | 80.6% | 0.771 | 72.3% | 85.1% | 0.026 | 67.2% | 55.3–77.2% | 0.002 | 0.0008 |
| OCR baseline Gemini alone | 74.6% | 0.703 | 69.4% | 86.6% | 0.059 | 61.2% | 49.2–72.0% | 0.0001 | 0.0004 |
| **glm_ocr** test 3 (glm-ocr + qwen3:4b, local) | 68.7% | 0.626 | 51.7% | 65.7% | **0.595** | 50.7% | 39.1–62.3% | <0.0001 | 0 |
| OCR local chandra-ocr-2 | 26.9% | 0.224 | 30.6% | 59.7% | **3.060** | 14.9% | 8.3–25.3% | <0.0001 | 0 |

\* MAE on the PDFs where both the answer and the truth have a grade.

Not scored: `glm_ocr` test 1/2 (`results/test1.jsonl` is empty; the log stops at 13 of 67 PDFs, all with no grade). `OCR_recognition/results/best_v2` and `holdout` are empty.

All 27 runs, every confusion matrix (subject and grade), per-class precision/recall, run-to-run stability: `report.txt`.

## 2. What the maths says

1. **The local models lose, and it is not noise.** GLM: 31 PDFs only notar_v2 gets right, 5 only GLM gets right (McNemar p < 0.0001). Its grade MAE is 12× worse (0.595 vs 0.049): it writes `1.0` when unsure (6 of the 1.0 answers are true 4.5–5.5). It sends English tests to Eco&Droit (recall Anglais 0.41, precision Eco&Droit 0.40). Chandra is worse on every metric.
2. **notar_v2 vs OCR `best` is not significant.** 9 PDFs only notar_v2 right, 4 only `best` right, p = 0.27. Both are the same idea (Gemini transcription → Jev picks the subject). notar_v2 wins on design (page fallback, module rule, flags, one `process()`), not on a proven accuracy gap. Port notar_v2, but don't sell it as "+7.5 points".
3. **In notar_v2 the final subject is always Jev's pick.** Jev alone = final, file for file (97.0%). Gemini's own subject is 85.1% and is wrong in all 8 disagreements (McNemar b=8, c=0). Gemini's subject only feeds the flag.
4. **notar_v2's flags don't work.** Flag = "result is wrong" test: TP 1, FP 9, 6 silent errors. Precision 0.10, recall 0.14. The flags fire on Gemini≠Jev, and Gemini is the one that's wrong.
5. **A free flag rule works much better** (section 9 of `report.txt`, same data, no extra API call):

   | Flag rule | Flags | Errors caught | Silent errors | Precision | Recall |
   |---|---|---|---|---|---|
   | current (Gemini≠Jev, OTHER, invalid grade) | 10 | 1 / 7 | 6 | 0.10 | 0.14 |
   | **grade missing OR Jev confidence < 0.5** | **8** | **5 / 7** | **2** | **0.62** | **0.71** |

   Caveat: picked on the same 67 PDFs, so it's optimistic. Re-check the 0.5 threshold once ~30 Confirmations exist.
6. **Jev confidence ranks right vs wrong reasonably** (ROC AUC 0.76–0.80), but notar_v2 had one wrong pick at 0.99. A threshold catches most errors, not all.
7. **Run-to-run noise is real.** For the ×3 variants, 1–6 subjects and 1–4 grades flip between identical runs (subject κ between runs 0.92–0.99). A gain of less than ~4 PDFs needs 3 runs.
8. **Upper bound of running 2 pipelines** (notar_v2 + `best`, take whichever is right) = 95.5%. Using their disagreement as a flag catches 4 of 7 errors for 2× the cost, so it's worse than rule 5, which is free.

## 3. Recommendation: notar_v2, ported 1:1 to Laravel, with the new flag rule

The existing `Notar/docs/specs/service-spec.md` picked a Python service because "Jev has no PHP SDK". **That's no longer a blocker:** `laravel/ai` v1.0 (2026-09-23) added Jev classification (`typesafe` provider, `Classification::of($text)`). Gemini is one REST call through Laravel's `Http` facade. The only new Composer package is `laravel/ai`, pinned to an exact version (Classification is experimental). See `docs/adr/ADR.md` and `stack.md`.

> An earlier draft of this section proposed a hand-written Jev HTTP client (`POST /v1/systemone`) and no new package. Superseded by the `laravel/ai` decision in `docs/adr/ADR.md`.
>
> **Superseded (2026-10-13):** Gemini also goes through `laravel/ai` (an agent, `GeminiService`), not the `Http` facade. The `Http::post` call on `generateContent` below is only the fallback if `ocr:bench` fails. See `docs/adr/ADR.md` and `stack.md`.

Keep the parameters identical, so the 89.6% still applies: model `gemini-3.5-flash-lite`, temperature 0, `maxOutputTokens` 8192, the same prompt (`CALL1_PROMPT` in `Notar/src/notar/providers.py`), PNG at 200 dpi, full page, image before prompt, `jev-1.13.0`, the same `subjects.json`.

### Flow

```
upload (FormRequest: mimes:pdf, max size)
  → store PDF, dispatch ReadTest job (queue)
      → cache hit? (sha256(pdf) + config hash) → done
      → for page 1..3:
           PdfPage::png(path, page, 200 dpi)         Imagick + Ghostscript
           Gemini::read(png)                          Http → generateContent
             finishReason != STOP → truncated, next page
           stop at the first page with a grade
      → module regex on the text  → Test type = code
        else Classification (Jev)  → Test type = Jev pick
      → grade ∈ {1.0 … 6.0 step 0.5} else null
      → flagged = grade null OR jev_conf < 0.5 OR no readable page
  → test_readings row (result JSON) → confirm screen pre-filled, warning if flagged
```

### Files (Laravel 13, matches `RAG-Laravel`'s stack)

| File | Job |
|---|---|
| `app/Http/Requests/StoreTestUploadRequest.php` | PDF only, size limit |
| `app/Jobs/ReadTest.php` | runs the pipeline, `tries` + backoff; retries 5xx/429 only |
| `app/Services/Ocr/TestReader.php` | the `process()` port: page loop, module rule, Jev `Classification` call (`laravel/ai`, `typesafe` provider), decisions, flags, cost |
| `app/Services/Ocr/Gemini.php` | one `Http::post` to `…/models/gemini-3.5-flash-lite:generateContent`; returns text, subject, grade, grade_box, truncated, cost |
| `app/Services/Ocr/PdfPage.php` | `Imagick::setResolution(200, 200)`, `readImage("$path[$n]")`, flatten, PNG |
| `config/ocr.php` + `resources/ocr/subjects.json` | pinned models, dpi, pages, token cap, prices, threshold 0.5 |
| migration `test_readings` | `pdf_sha256`, `config_hash`, `result` JSON, `confirmed_type`, `confirmed_grade`, `edited` |
| `app/Console/Commands/OcrBench.php` | runs `TestReader` on `real_table.json` and prints subject/grade/both, so the Laravel number can be compared with 89.6% |
| `tests/Feature/TestReaderTest.php` | `Http::fake()` for Gemini, `Classification::fake()` for Jev: module rule, page fallback, truncated page, invalid grade, flag rule |

Why the `Http` facade and not `laravel/ai` for Gemini: the pipeline needs `finishReason` (loop guard), `usageMetadata` including thinking tokens (cost), and an exact JSON schema. The `laravel/ai` docs show usage and structured output, but no finish reason. Raw HTTP keeps parity with the Python call for about the same number of lines.

### Rollout, in order

1. Port as above, with the **current** flag rule, and run `php artisan ocr:bench`. Pass = both correct ≥ 56/67 (89.6% minus the ~4-file noise). About $0.10 per run.
2. Switch to the new flag rule (grade missing OR Jev conf < 0.5). Pure PHP, $0.
3. Then, from `Notar/docs/benchmark-results.md`: top-half retry on truncated pages (fixes `2_3_Eco&Droit_5`), the 40 module codes in `subjects.json`, a grade re-read on the `grade_box` crop when the grade is missing (3 of the 5 grade misses).

### Don't

- Don't use GLM-OCR or Chandra (section 2.1).
- Don't drop Jev for Gemini's own subject (−8 subjects, p = 0.008).
- Don't send the PDF straight to Gemini instead of a PNG. The input would change, so the benchmark would no longer apply.
- Don't change the prompt while porting. Rewordings lowered grades (`refined`: 85.1% vs 88.6%).

### Unverified before coding

- Whether `laravel/ai`'s `Choice` `confidence` is the same number notar's Python SDK returned (the 0.5 threshold was set on it), and how to pin `jev-1.13.0`. See `stack.md`.
- Imagick and Ghostscript are installed on the server (`php -m | grep imagick`, `gs --version`). The PNG must match pymupdf's at 200 dpi: compare 1 page by eye.
