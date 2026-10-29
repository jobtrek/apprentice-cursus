# OCR backend tasks

Backend work for Automatic reading, in order. Details: [`stack.md`](stack.md), decision: `docs/adr/ADR.md` ("Automatic reading runs inside Laravel"). v1 runs synchronously in `GradeController::readScan`; the queue and cache items are deferred.

## Setup

- [x] **1. Config.** `laravel/ai` 1.2.1 installed, `config/ai.php` published, `GEMINI_API_KEY` and `TYPESAFE_API_KEY` in `.env.example`. `config/ocr.php` holds `enabled` and token prices; the model, temperature and 8192 token cap are attributes on `GeminiService`, the 0.5 threshold is `OcrScan::FLAG_BELOW`.
- [x] **2. Imagick + Ghostscript.** In the Sail 8.5 runtime (`php8.5-imagick`) and the production `Dockerfile`.
- [ ] **3. Queue worker.** Only needed once reading moves to a job. Production already runs `queue:work` (`compose.prod.yaml`); `compose.yaml` does not.

## Services (`app/Services/Ocr/`)

- [x] **4. PDF → PNG.** Inline in `readScan`: Imagick at 200 dpi, flattened on white. Still to do: compare one page by eye against notar's pymupdf output.
- [x] **5. `GeminiService`.** `laravel/ai` agent on `gemini-3.5-flash-lite`, temperature 0, prompt from `resources/prompts/ocr.md`, returns `{text, subject, grade, grade_box}`, `truncated` when the last `finishReason` is not `Stop`, and `usage`.
- [x] **6. `JevService`.** Classifies the first page's text into an `OcrSubject` with its `confidence` and `usage`.
- [ ] **7. Pipeline.** Done in `readScan`: page 1, pages 2–3 only if no grade or truncated; grade kept only if 1.0–6.0 in 0.5 steps; Flagged when the grade is missing or Jev's confidence is below 0.5 (null counts as 0). Left: the Module code rule (the Test type is always Jev's pick today).

## Storage and flow

- [x] **8. `ocr_scans` migration + model.** One row per read PDF: uploader, subject, grade, confidence, flag, pages read, costs, duration, `grade_id`, `suggested_node_id`.
- [x] **9. `ReadScanRequest` + `POST /read-scan`.** Consent required, PDF only, max 10 MB, `throttle:ocr`. The PDF is read in the request and not stored by this route.
- [ ] **10. Same-PDF cache.** Reuse a scan when `sha256(pdf)` + config hash already exists, with no model calls.
- [ ] **11. Queued job + polling.** Move `readScan` into a job with `tries` + backoff (retry 5xx/429 only), a status endpoint the page polls, and `failed()` falling back to Manual entry.
- [x] **12. Confirmation.** `grades.store` takes `ocr_scan_id` + `ocr_node_id` and links the scan to the saved grade.

## Checking

- [ ] **13. Pest tests.** `tests/Feature/ReadScanTest.php` covers a read, rate limit, OCR disabled, missing consent, linking to a grade, another apprentice's scan, and the dashboard. Left: page fallback, truncated page, invalid grade, each flag condition.
- [ ] **14. `php artisan ocr:bench`.** Run the pipeline on the 67 PDFs in `pdf_data/` and score it against `data/ground_truth.json`. Pass = both fields right on ≥ 56/67; if it fails, switch `GeminiService` to `Http::post` on `generateContent` (image before prompt) and re-run.
