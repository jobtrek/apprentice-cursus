# Documentation links: Automatic reading

External docs for every part of [`stack.md`](stack.md), grouped by layer. All links checked on 2026-10-13.

## In this repo

| File                                                                                                                                               | Why read it                                                                                                                        |
| -------------------------------------------------------------------------------------------------------------------------------------------------- | ---------------------------------------------------------------------------------------------------------------------------------- |
| [`stack.md`](stack.md)                                                                                                                             | What to build: scope, flow, layers, code layout, open questions                                                                    |
| [`RECOMMENDATION.md`](RECOMMENDATION.md)                                                                                                           | Why notar v2: scores of all 27 runs, the flag rule                                                                                 |
| [`final_bench_mark.md`](final_bench_mark.md)                                                                                                       | Where the 89.6% comes from                                                                                                         |
| [`pipeline-spec.md`](pipeline-spec.md)                                                                                                             | Original Python pipeline spec (superseded in part). Its holdout ship gate no longer applies: the gate is `ocr:bench` ≥ 56/67 (ADR) |
| [`../adr/ADR.md`](../adr/ADR.md)                                                                                                                   | Decision: Automatic reading runs inside Laravel, not as a Python service                                                           |
| [`../../CONTEXT.md`](../../CONTEXT.md)                                                                                                             | Terms (Reading, Pre-fill, Flagged, Confirmation, Processing consent)                                                               |
| [`resources/prompts/ocr.md`](../../resources/prompts/ocr.md), [`config/subjects.json`](config/subjects.json), [`config/jev.json`](config/jev.json) | The prompt, Test types and Jev request to port unchanged                                                                           |

## Jev (TypeSafe) through `laravel/ai`

| Link                                                                          | Used for                                                                            |
| ----------------------------------------------------------------------------- | ----------------------------------------------------------------------------------- |
| [Laravel AI SDK](https://laravel.com/docs/13.x/ai-sdk)                        | Install, `config/ai.php`, providers                                                 |
| [AI SDK: Classification](https://laravel.com/docs/13.x/ai-sdk#classification) | `Classification::of()->questions()->classify()`, `Choice`, `Classification::fake()` |
| [`laravel/ai` on GitHub](https://github.com/laravel/ai)                       | Releases, to pin the exact version                                                  |
| [TypeSafe docs](https://docs.typesafe.ai)                                     | System One / Jev overview                                                           |
| [Choice primitive](https://docs.typesafe.ai/primitives/choice)                | Instructions, criteria, answer shape                                                |
| [Confidence](https://docs.typesafe.ai/confidence)                             | What `confidence` means (open question 1: the 0.5 threshold)                        |
| [Models](https://docs.typesafe.ai/models)                                     | Pinning `jev-1.13.0` (open question 2)                                              |

## Gemini (`laravel/ai` agent `GeminiService`; raw `generateContent` only as the fallback)

| Link                                                                             | Used for                                                      |
| -------------------------------------------------------------------------------- | ------------------------------------------------------------- |
| [`generateContent` API reference](https://ai.google.dev/api/generate-content)    | Request body, `finishReason`, `usageMetadata`                 |
| [Structured output](https://ai.google.dev/gemini-api/docs/structured-output)     | The exact JSON schema for `{text, subject, grade, grade_box}` |
| [Image understanding](https://ai.google.dev/gemini-api/docs/image-understanding) | Sending the PNG inline, image before prompt                   |
| [Models](https://ai.google.dev/gemini-api/docs/models)                           | `gemini-3.5-flash-lite`                                       |
| [Pricing](https://ai.google.dev/gemini-api/docs/pricing)                         | Prices in `config/ocr.php` (cost per PDF)                     |

## Laravel

| Link                                                                                        | Used for                                                                                   |
| ------------------------------------------------------------------------------------------- | ------------------------------------------------------------------------------------------ |
| [HTTP client](https://laravel.com/docs/13.x/http-client)                                    | Fallback only: `Http::post` on `generateContent` if `ocr:bench` fails with `GeminiService` |
| [HTTP client: testing](https://laravel.com/docs/13.x/http-client#testing)                   | `Http::fake()`, only if the fallback is used (otherwise `GeminiService::fake()`)           |
| [Queues](https://laravel.com/docs/13.x/queues)                                              | `ReadTest` job, `database` driver, `tries`, backoff                                        |
| [Queues: failed jobs](https://laravel.com/docs/13.x/queues#dealing-with-failed-jobs)        | `failed()` → reading marked failed → Manual entry                                          |
| [Form request validation](https://laravel.com/docs/13.x/validation#form-request-validation) | `StoreTestUploadRequest` (PDF only, 10 MB)                                                 |
| [Filesystem: local driver](https://laravel.com/docs/13.x/filesystem#the-local-driver)       | Storing scans on the private `local` disk                                                  |
| [Migrations](https://laravel.com/docs/13.x/migrations)                                      | `test_readings` table                                                                      |
| [Artisan commands](https://laravel.com/docs/13.x/artisan)                                   | `php artisan ocr:bench`                                                                    |
| [Configuration](https://laravel.com/docs/13.x/configuration)                                | `config/ocr.php`, `.env` keys                                                              |
| [Sail](https://laravel.com/docs/13.x/sail)                                                  | `sail:publish`, adding Imagick + Ghostscript to the image                                  |

## Frontend and tests

| Link                                             | Used for                                  |
| ------------------------------------------------ | ----------------------------------------- |
| [Inertia polling](https://inertiajs.com/polling) | `usePoll` every 2 s on the reading status |
| [shadcn-vue](https://www.shadcn-vue.com/)        | Confirm form and warning UI               |
| [Pest](https://pestphp.com/docs/installation)    | Feature tests                             |

## PDF rendering

| Link                                                                                | Used for                            |
| ----------------------------------------------------------------------------------- | ----------------------------------- |
| [Imagick](https://www.php.net/manual/en/book.imagick.php)                           | `PdfPage.php`                       |
| [`Imagick::setResolution`](https://www.php.net/manual/en/imagick.setresolution.php) | 200 dpi (call before reading)       |
| [`Imagick::readImage`](https://www.php.net/manual/en/imagick.readimage.php)         | `readImage("$path[$n]")` for page N |
| [Ghostscript](https://ghostscript.readthedocs.io/en/latest/)                        | PDF backend Imagick needs           |

## Diagrams

| Link                      | Used for                  |
| ------------------------- | ------------------------- |
| [D2](https://d2lang.com/) | Rendering `diagrams/*.d2` |
