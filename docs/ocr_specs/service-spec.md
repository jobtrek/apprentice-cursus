# Spec: notar v2 as a service, plus the next fixes

> **Superseded in part (2026-10-13):** the pipeline is ported to Laravel. See `docs/adr/ADR.md` ("Automatic reading runs inside Laravel") and [`stack.md`](stack.md) for what is kept and dropped.

Written 2026-10-13 from [`final_bench_mark.md`](final_bench_mark.md). Builds on [`pipeline-spec.md`](pipeline-spec.md) and respects [ADR 0001](../adr/0001-uploader-confirms-every-result.md) (the uploader confirms every result). Status: ready for an agent.

## Problem Statement

notar v2 is the most accurate setup measured: both fields right on 89.6% of the 67 test PDFs, against 82.1% for the best `OCR_recognition` setup, for $0.0014 per PDF. Right now it only runs as a command-line tool on the developer's laptop. The Laravel app can't call it, so students still type their Test type and Grade by hand.

Three known gaps also remain:

1. The loop guard gives up on any page where the transcription runs away. One test (`2_3_Eco&Droit_5`) loses both fields because of it.
2. `subjects.json` has no Module codes, so the Module rule accepts any 3-digit number printed on the page.
3. v2 has been run only once, so its grade noise is unknown. Any later grade fix that gains fewer than ~4 files can't be trusted until that noise is measured.

## Solution

A small Python HTTP service wraps the same `process()` the benchmark uses. Laravel sends it a PDF from a queue job and gets back the result JSON (the output contract in `final_bench_mark.md`). It pre-fills the confirm screen from that JSON and warns the uploader when the result is Flagged. If the same PDF is uploaded again, the service returns the stored result straight away, with no model calls and no cost.

In the same change:

- The loop guard re-reads a runaway page as its top half before moving on to the next page.
- The Module rule only accepts the real Module codes.
- The benchmark can keep repeated runs side by side, so grade noise can be measured.

## User Stories

1. As a student, I want my uploaded test's Test type and Grade pre-filled within ~10 seconds, so that I don't type them in.
2. As a student, I want a correct Pre-fill to take one click to confirm, so that checking every test costs me seconds (ADR 0001).
3. As a student, I want a warning and every Reading shown when the result is Flagged, so that I look closer exactly where it matters.
4. As a student, I want to fix a wrong Pre-fill before it is stored, so that my record is right.
5. As a student who uploads the same PDF twice, I want the same Pre-fill instantly, so that I don't wait again.
6. As a student whose page 1 makes the transcription run away, I want the system to try again on a smaller part of the page, so that I still get a Pre-fill.
7. As a student whose page 1 runs away even on the top half, I want the system to move on to pages 2 and 3 as before, so that the retry never makes things worse.
8. As a student with a Module test, I want its printed code (e.g. `293`) as the Test type, so that the result says which Module it is.
9. As a student with a Subject test that mentions an unrelated 3-digit number (a room, a page count, a year fragment), I want that number not to be taken as a Module, so that my Subject isn't replaced by a fake Module.
10. As a student whose test has a Module code in the header but a Subject in the content, I want it flagged, so that I check it (unchanged rule 2).
11. As a student, I want uploads that aren't a PDF rejected with a clear error, so that I know to upload the right file.
12. As a student, I want an oversized or corrupt PDF rejected with a clear error instead of hanging, so that I can try another scan.
13. As a student, I want a temporary Gemini or Jev outage to show as "try again later" and not as a wrong Pre-fill, so that nothing wrong is pre-filled.
14. As the Laravel app, I want one endpoint that takes a PDF and returns the result JSON, so that the app holds no pipeline logic.
15. As the Laravel app, I want the response to follow the output contract exactly (`test_type`, `test_type_source`, `grade`, `grade_source`, `flagged`, `flag_reasons`, `readings`, `fallback`, `calls`, `cost_usd`, `models`), so that the confirm UI can be built against it separately.
16. As the Laravel app, I want a different error status for "bad input" and for "upstream model failed", so that the queue job knows whether to retry.
17. As the Laravel app, I want the endpoint protected by a shared secret, so that only the app can spend the API budget.
18. As the Laravel app, I want a health endpoint, so that the container can be monitored.
19. As the developer, I want the service and the benchmark to call the very same `process()`, so that the benchmark number is the production number.
20. As the developer, I want the cache keyed by the PDF's SHA-256 plus a hash of the pipeline config, so that changing a model, prompt, threshold or `subjects.json` never serves a stale result.
21. As the developer, I want the cache to hold result JSON only, never images or PDFs, so that no student page is kept by the service.
22. As the developer, I want the service to keep no PDF on disk after the response, so that page retention stays Laravel's job (pipeline-spec privacy rules).
23. As the developer, I want each result to say when the top-half retry was used (in `fallback`), so that I can count how often it fires and whether it helps.
24. As the developer, I want the retry's extra Gemini call counted in `calls` and `cost_usd`, so that the cost budget stays checkable.
25. As the developer, I want the 40 Module codes copied from the Jev tree into `subjects.json`, so that the Module rule, the Gemini enum and Jev's choices all use the same list.
26. As the developer, I want to run the benchmark several times into separate result files, so that I can compare runs file by file.
27. As the developer, I want a per-file comparison of repeated runs, so that I know how many files flip between runs (grade noise) before trusting a grade fix.
28. As the developer, I want one Docker image that runs the service with the two API keys from the environment, so that it deploys the same everywhere.
29. As the developer, I want the pinned model versions unchanged by this work, so that the 89.6% still describes what ships.
30. As the developer, I want offline tests only (fake providers), so that the test suite costs nothing and never flakes on the network.

## Implementation Decisions

### Deployment

- **Python service, not a Laravel port.** The 89.6% was measured on this Python code (pymupdf rendering, `google-genai`, `typesafe-sdk`). Laravel's AI SDK (`laravel/ai`) could make the Gemini call (image attachment, JSON schema, finish reason, token usage). But Jev has no PHP SDK that I found, rendering PDFs in PHP needs Imagick/Ghostscript, and a port would split benchmark and production into two codebases. That would break Goal 3 of the pipeline spec ("Benchmark = production").
- **FastAPI + uvicorn in one Docker container.** Laravel keeps uploads, the queue, the confirm UI, storage and retention, and calls the service from a queue job.
- **API keys** (`GEMINI_API_KEY`, `TYPESAFE_API_KEY`) and the shared secret come from the environment. `.env` is for local runs only.

### Service module (new)

- `POST /process`: the body is the PDF bytes (multipart upload). The response is the result JSON, unchanged from `process()`.
- `GET /health`: liveness check, no model calls.
- **Auth:** a shared-secret header, compared in constant time. A missing or wrong secret returns 401.
- **Input validation at the boundary:** reject anything that isn't a readable PDF (415/422) and anything above a size limit (413). The size limit is a config value.
- **Upstream errors:** a Gemini or Jev failure returns 502/503 with no partial result. The queue job retries those statuses and never retries 4xx.
- **Providers are built once at startup** and injected, so tests can replace them with fakes.
- **Cache:** looked up before `process()` runs.
    - Key = SHA-256 of the PDF + a hash of the config, which covers the `Config` fields, the prompts and `subjects.json`.
    - Value = the result JSON only.
    - A hit returns the stored JSON and makes no provider calls.
    - A plain local store is enough (one container, one writer). The cache lives in the service, not in `process()`, so the benchmark keeps its own per-PDF cache.

### Pipeline changes (`process()`)

- **Loop guard retry (rank 1):** when a page's Gemini reading is `truncated`, render the top half of that same page and read it once more.
    - If that reading isn't truncated either, it counts as the page's reading: text and grade work the same as for a full page.
    - If it is truncated too, skip the page as today.
    - At most one retry per page.
    - `fallback` gains a way to say the retry was used, for example `{"loop_retry": true}` alongside `grade_page`. The retry's call and cost are counted.
- **Module codes (rank 2):** copy the 40 Module codes (with descriptions) from the Jev subject tree into the `modules` section of `subjects.json`.
    - Once the list is filled, the existing "any 3-digit code counts while the list is empty" fallback in the Module rule has no effect. Remove it, together with its `ponytail:` comment.
    - The Gemini `subject` enum and Jev's choices pick up the codes automatically, because they are already built from `subjects.json`.
- Nothing else in the decision rules changes. T stays 1.0, the embeddings vote stays off by default, and the models stay pinned.

### Benchmark changes (rank 0)

- `notar bench` can write to a named results file (e.g. a run label), so 3 runs of v2 sit side by side without deleting `results/bench.jsonl`.
- `notar report` (or a small compare command) lists the files whose Test type or Grade differs between runs, and counts them.
- The noise measurement is done by running v2 three times (~$0.20 total) **after** ranks 1–2 land. The result goes into `benchmark-results.md`.

### Output contract

- Unchanged from `final_bench_mark.md`, apart from the extra key `fallback` may now carry for the top-half retry. `grade_crop` and `grade_tiebreak` stay null.

## Testing Decisions

- **A good test checks external behavior.** Give it scripted Readings or an HTTP request, then assert the returned JSON: values, sources, flags, `fallback`, `calls`. Don't assert prompt text, internal helpers, or the order of calls inside a stage.
- **No live API calls**, as today. Real-API checks happen only through `notar bench`.
- **Seam 1, `process(pdf_bytes, providers)`** (existing, extend `test_pipeline.py`):
    - Page 1 full read truncated, top-half retry succeeds with a grade: grade pre-filled, `fallback` shows the retry, 2 Gemini calls.
    - Page 1 full and top half both truncated: moves on to page 2 as today.
    - Retry succeeds but finds no grade: its text is used for the Test type, and the page fallback looks for the grade on page 2.
    - A printed code that is in `subjects.json`: `test_type_source = "rule"`.
    - A printed 3-digit number that isn't a known code: no rule hit, Jev runs.
    - Update the existing `test_page_fallback_and_loop_guard` for the new retry call.
- **Seam 2, `POST /process`** (new, FastAPI test client with fake providers injected):
    - A valid PDF with the secret returns the contract JSON.
    - The same PDF twice: the second response is identical and the fakes record 0 extra calls.
    - The same PDF after a config change is a cache miss.
    - Non-PDF returns 4xx, oversized returns 413, missing secret returns 401. Fakes record 0 calls for all three.
    - A fake provider that raises returns 502/503, and nothing is cached.
- **Prior art:** `tests/test_pipeline.py` already drives `process()` with `FakeGemini`/`FakeJev` (including loop guard and page fallback), and has `test_bench_score` / `test_bench_diagnose` for the benchmark. Reuse those fakes for seam 2.
- **Benchmark compare:** one test on two in-memory run files with a known number of differing files, in the style of `test_bench_score`.

## Out of Scope

- **Ranks 3–4: crop re-read around `grade_box` and the grade tie-break.** These are phase 2 calls. The pipeline spec gates them on the Phase 2 trigger (≥ 3% Grade edits over the first 200 production uploads). Rank 0's noise numbers are also needed before judging them.
- The Laravel side: queue job, confirm UI, storage, retention, opt-in. Each needs its own spec. This spec only fixes the contract the job consumes.
- A Laravel/PHP port of the pipeline.
- Changing models, prompts, T or M, or turning the embeddings vote on.
- Batch mode, local OCR, local-only mode.
- Horizontal scaling or a shared cache across several containers.

## Further Notes

- The 7 remaining misses (`final_bench_mark.md`): rank 1 targets `2_3_Eco&Droit_5`. Rank 2 targets Module tests in production (none are in the 67). The other 5 are grade misses and wait for phase 2.
- _(Superseded: the gate is now `ocr:bench` ≥ 56/67, see `docs/adr/ADR.md`.)_ Ship gate for production remains the holdout gate from the pipeline spec: unflagged Pre-fill accuracy ≥ 98%, Flagged share ≤ 15%, cost ≤ $0.003 per test. The service can be deployed before the holdout, but it should stay behind the confirm step (ADR 0001).
- Open question: where the Laravel app and the service run (same host or not). This decides whether the shared secret is enough or TLS is needed between them.
