# OCR backend tasks

Backend work for Automatic reading, in order. Details: [`stack.md`](stack.md), decision: `docs/adr/ADR.md` ("Automatic reading runs inside Laravel").

## Setup

- [ ] **1. Config.** Publish `laravel/ai`'s config (`sail artisan vendor:publish --provider="Laravel\Ai\AiServiceProvider"`) and add `GEMINI_API_KEY` and `TYPESAFE_API_KEY` to `.env` and `.env.example`. Create `config/ocr.php` for the pinned models, 200 dpi, the 8192 token cap, token prices and the 0.5 flag threshold.
- [ ] **2. Imagick + Ghostscript.** Run `sail:publish`, add `php-imagick` and `ghostscript` to the Sail Dockerfile, then `sail build --no-cache`. Check with `sail php -m | grep imagick` and `gs --version` inside the container.
- [ ] **3. Queue worker.** Add a `queue:work` process (the `database` driver) to `compose.yaml`, since nothing runs queued jobs today.

## Services (`app/Services/Ocr/`)

- [ ] **4. `PdfPage`.** Turn page N of a PDF into PNG bytes at 200 dpi with Imagick (`setResolution`, `readImage("$path[$n]")`, flatten). Compare one page by eye against notar's pymupdf output.
- [ ] **5. `GeminiService`.** A `laravel/ai` agent (`HasStructuredOutput`) on `gemini-3.5-flash-lite`, temperature 0, that sends the prompt from `config/prompt.md` unchanged plus the page image and gets back `{text, subject, grade, grade_box}`. Mark the page truncated when the last step's `finishReason` is not `Stop`, and return the token `usage` for the cost.
- [ ] **6. `JevService`.** Already classifies `text` into an `OcrSubject` with `jev-1.13.0`. Make it also return Jev's `confidence`, because the flag rule needs it.
- [ ] **7. `TestReader`.** The pipeline: read page 1 (pages 2–3 only if page 1 has no grade or is truncated), take the Test type from the Module code regex or else Jev, keep the grade only if it is 1.0–6.0 in 0.5 steps, and set Flagged when the grade is missing, Jev's confidence is below 0.5, or no page could be read.

## Storage and flow

- [ ] **8. `test_readings` migration + model.** Columns: status (`pending`/`done`/`failed`), `pdf_sha256`, `config_hash`, `result` JSON, `confirmed_type`, `confirmed_grade`, `edited`. Index `(pdf_sha256, config_hash)`, which is the same-PDF cache lookup.
- [ ] **9. `StoreTestUploadRequest` + upload route.** Accept a PDF only (max 10 MB), store it on the private `local` disk, create a `pending` reading and dispatch the job. Reuse a finished reading when the same `pdf_sha256` + `config_hash` already exists.
- [ ] **10. `ReadTest` job.** Run `TestReader` and save the result on the reading, with `tries` + backoff that retry only 5xx/429. `failed()` marks the reading `failed` so the page falls back to Manual entry.
- [ ] **11. Status endpoint.** Return the reading's status and result so the page can poll it every 2 s.
- [ ] **12. Confirmation.** Save the grade through the same store endpoint as Manual entry and record `confirmed_type`, `confirmed_grade` and `edited` on the reading. Blocked until the grade store exists (stack.md open question 8).

## Checking

- [ ] **13. Pest tests.** Fake Gemini (`GeminiService::fake()`) and Jev (`Classification::fake()`). Cover the module rule, page fallback, truncated page, invalid grade, each flag condition, a cache hit making no calls, and a failed job.
- [ ] **14. `php artisan ocr:bench`.** Run the pipeline on the 67 PDFs in `pdf_data/` and score it against `data/ground_truth.json`. Pass = both fields right on ≥ 56/67; if it fails, switch `GeminiService` to `Http::post` on `generateContent` (image before prompt) and re-run.
