# Spec: grade + test type pipeline (production)

> **Superseded in part (2026-10-13):** the pipeline is ported to Laravel. See `docs/adr/ADR.md` ("Automatic reading runs inside Laravel") and [`stack.md`](stack.md) for what is kept and dropped.

Written 2026-10-12 from a design grilling session. Status: ready to implement, ship gated on the holdout (see Evaluation).

Update 2026-10-12: **the uploader confirms every result** ([ADR 0001](../adr/0001-uploader-confirms-every-result.md)). The pipeline pre-fills Test type and Grade; nothing is saved without a Confirmation. Calls 2 and 3 move to phase 2.

Update 2026-10-13 (second grilling): terms follow [`CONTEXT.md`](../../CONTEXT.md) (Test type, Subject, Module, Pre-fill, Flagged, Confirmation). Only students upload; Modules are decided by their printed code; T starts at 1.0; the phase 2 trigger is the production grade edit rate.

## Problem Statement

Students want to upload a scanned, handwritten school test (PDF) and get back **its Test type** (Subject or Module) and **its Grade**, without typing them in. The benchmark shows the best setup (Gemini transcription + Jev subject choice) gets both right on only 82.1% of tests (Test type 93.9%, Grade 88.1%), and it has no way to say "I'm not sure". Every wrong answer is saved silently. A wrong Grade saved without anyone noticing is worse than asking the uploader to confirm one. No model can guarantee 100% on handwriting, so the only way to get there is a human check on every result, made as cheap as possible.

## Solution

A Python pipeline service, running the same code the benchmark measures, pre-fills the Test type and Grade. The uploader **always confirms** in the Laravel app (one click when correct, an edit when not). The pipeline also flags results it is unsure about (Readings disagree, invalid Grade), so the UI can warn the uploader and show every Reading. Phase 2 adds a crop re-read and a stronger-model tie-break for the Grade, only if production Confirmations show too many Grade edits (see Phase 2 trigger).

The Confirmed result is the student's personal record, visible only to them. It is not an authoritative school record, and teachers never upload.

### Goals

1. Accuracy first: every stored result is confirmed by the uploader. Pipeline targets: ≥ 98% Pre-fill accuracy on unflagged results, ≤ 15% of tests Flagged.
2. Cost second: ≤ $0.003 per test end to end (today ~$0.0012).
3. Benchmark = production: every number in the evaluation comes from the code that ships.
4. Room for one experiment at a time (embeddings vote, local grade classifier), shipped only if it beats v1 on the holdout.

### Non-goals (v1)

- Local-only / offline mode.
- Per-Test-type grade-box templates.
- Gemini batch mode for user uploads.
- Auto-saving any result without a Confirmation.
- Teacher uploads, or sharing a Confirmed result with teachers, parents or the school.
- PDFs holding more than one test (one PDF = one Test; the uploader splits it).
- The Laravel confirm UI itself (own spec; this spec only defines the contract it consumes).

## Pipeline

```
PDF ─► cache (SHA-256) ─hit─► stored result JSON
          │miss
          ▼
     render page 1 (200 dpi, PNG, full page)
          ▼
     Call 1  Gemini flash-lite ─► {text, subject, grade, grade_box}
          │                        (page 2-3 fallback if no grade, loop guard on token cap)
   ┌──────┴────────────────────┐
   ▼ test type                 ▼ grade (phase 1: call 1 only)
 rules on text               valid? ─yes─► pre-fill
   │hit + Gemini Module/OTHER  │no
   │  → pre-fill               ▼
   │hit + Gemini Subject       flag grade_invalid
   │  → flag                   (phase 2: call 2 crop re-read,
   │miss                        call 3 tie-break, see below)
   ▼
 Jev on text ─► vote vs
 Gemini subject
   │a Module reading → flag module_rule_miss
   │agree → pre-fill
   │disagree: Jev conf ≥ T → pre-fill Jev
   │else → flag
   └──────────┬────────────────┘
              ▼
       result JSON ─► uploader confirms (image deleted after Confirmation unless opt-in)
```

### Stages, inputs and outputs

| #   | Stage                                                    | Input                                              | Output                                                                                                                                                   |
| --- | -------------------------------------------------------- | -------------------------------------------------- | -------------------------------------------------------------------------------------------------------------------------------------------------------- |
| 0   | Cache lookup                                             | PDF bytes                                          | SHA-256; on hit, the stored result JSON (no model calls)                                                                                                 |
| 1   | Render                                                   | PDF                                                | Page 1 image, 200 dpi, PNG, full page (benchmark best settings). Pages 2–3 rendered only on fallback                                                     |
| 2   | Call 1 (Gemini flash-lite)                               | Page image                                         | `text` (verbatim transcription), `subject` (enum of Test types + `OTHER`), `grade` (raw + normalized), `grade_box` (Gemini normalized `box_2d`, or null) |
| 3   | Rules                                                    | `text`                                             | Module or no match (module codes such as `M293`, `Modul 293`, `Module 293`)                                                                              |
| 4   | Jev                                                      | `text`                                             | Test type choice + confidence                                                                                                                            |
| 5   | Test type decision                                       | Rule result, Jev choice/confidence, Gemini subject | `test_type`, `test_type_source`, maybe a flag reason                                                                                                     |
| 6   | Crop (phase 2)                                           | Page image + `grade_box`                           | Grade crop image (small padding around the box)                                                                                                          |
| 7   | Call 2 (phase 2, Gemini flash-lite)                      | Crop only                                          | `grade_crop`                                                                                                                                             |
| 8   | Call 3 (phase 2, stronger Gemini tier), only when needed | Page + crop                                        | `grade_tiebreak`                                                                                                                                         |
| 9   | Grade decision                                           | All Grade Readings                                 | `grade`, `grade_source`, maybe a flag reason                                                                                                             |
| 10  | Output + retention                                       | Everything above                                   | Result JSON; image deleted or kept per retention rules                                                                                                   |

### Pre-fill / flag rules

**Valid Grade** = one of {1.0, 1.5, 2.0 … 6.0}, or null (ungraded test; a valid Confirmed result). Anything else (e.g. 5.75, 18) is invalid.

**Test type**

The Module code is printed on every Module test, so Modules are decided by the rules only.

1. Rule hit and Gemini subject is a Module or `OTHER` → pre-fill the rule's Module, `test_type_source = "rule"`.
2. Rule hit and Gemini subject is a Subject (e.g. an "English Test" about websites with "Module 293" in its header) → pre-fill the rule's Module, flag `subject_disagree`.
3. No rule hit but Jev or Gemini reads a Module → the transcription lost the code. Pre-fill the best guess (Jev, or Gemini if they agree), flag `module_rule_miss`.
4. Jev choice == Gemini subject → pre-fill, `test_type_source = "agree"`.
5. Disagree and Jev confidence ≥ **T** → pre-fill Jev, `test_type_source = "jev"`.
6. Gemini subject `OTHER` (not a test, e.g. a semester report, or Gemini missed it) → pre-fill Jev's choice (null if Jev also says `OTHER`), flag `not_recognized`.
7. Otherwise → flag `subject_disagree`, pre-fill Jev's choice.

The uploader confirms by picking from the fixed Test type list or cancels the upload. There is no free-text Test type and no "not a test" Confirmed result.

T starts at **1.0** (every disagreement is flagged). It is lowered only once ≥ 30 disagreements are logged (from the holdout or production Confirmations), to the lowest value at which Jev is never wrong on them, then checked on the holdout. Every agreeing pair is logged with Jev's confidence so a "flag even when agreeing below T_low" rule can be added later if the evaluation shows agreeing-but-wrong cases.

**Grade, phase 1** (call 1 only)

1. `grade` valid → pre-fill, `grade_source = "call1"`.
2. Invalid (e.g. 5.75, 18) → flag `grade_invalid`; pre-fill null.

**Grade, phase 2** (only if the Phase 2 trigger fires)

1. `grade` (call 1) and `grade_crop` (call 2) both valid and equal → pre-fill, `grade_source = "agree"`.
2. Otherwise run call 3. If `grade_tiebreak` is valid and equals one of the earlier valid Readings → pre-fill, `grade_source = "tiebreak"`.
3. Otherwise → flag `grade_disagree` or `grade_invalid`.
4. No `grade_box` from call 1: call 2 is skipped and call 3 runs directly (the agreement is between call 1 and call 3).

### Output contract (Python → Laravel)

```json
{
    "pdf_sha256": "…",
    "test_type": "Anglais",
    "test_type_source": "rule | agree | jev | embed | none",
    "grade": 5.5,
    "grade_source": "call1 | agree | tiebreak | none",
    "flagged": false,
    "flag_reasons": [],
    "readings": {
        "rule": null,
        "jev": { "choice": "Anglais", "confidence": 0.93 },
        "gemini_subject": "Anglais",
        "grade_full_page": 5.5,
        "grade_crop": 5.5,
        "grade_tiebreak": null,
        "grade_box": [120, 610, 210, 820]
    },
    "fallback": null,
    "calls": { "gemini": 2, "gemini_strong": 0, "jev": 1 },
    "cost_usd": 0.0014,
    "models": {
        "gemini": "gemini-3.5-flash-lite",
        "gemini_strong": "…",
        "jev": "jev-1.13.0"
    }
}
```

When `flagged` is true, `test_type` / `grade` hold the best guess (or null) and the UI shows all `readings`. `flag_reasons` ⊆ {`subject_disagree`, `module_rule_miss`, `not_recognized`, `grade_invalid`, `grade_disagree`}. A source of `"none"` means no Reading was trusted enough to name one.

## User Stories

1. As a student, I want to upload a PDF of my graded test and see its Test type and Grade within ~10 seconds, so that I don't have to type them in.
2. As a student, I want my Confirmed results visible only to me, so that my record stays private.
3. As an uploader, I want Test type and Grade pre-filled so that confirming a correct result takes one click, so that checking every test costs me seconds.
4. As an uploader, I want a warning with every Reading and the reason when the system is unsure, so that I look closer exactly where it matters.
5. As an uploader, I want to type the correct value when every reading is wrong, so that the stored result is right.
6. As an uploader, I want to be told before uploading that the page is sent to Google and TypeSafe, so that I can decide whether to proceed.
7. As an uploader, I want my scanned page deleted once I've confirmed the result, so that my test isn't stored longer than needed.
8. As an uploader, I want an opt-in checkbox to let my test improve the system, and to withdraw it later, so that I control whether my page is kept.
9. As an uploader of a Module test, I want the module code (e.g. 293) as the Test type, so that the result is useful, not just "IT".
10. As an uploader of a test whose grade sits in the margin or lower on the page, I want it found anyway, so that unusual layouts still work.
11. As an uploader of a test whose grade is on page 2 or 3, I want those pages checked when page 1 has none, so that out-of-order scans still work.
12. As an uploader of an ungraded test, I want "no grade" returned rather than a point score from an exercise, so that wrong grades aren't invented.
13. As an uploader, I want a point score like "18/24" or "5.75" never to be saved as a grade, so that only valid Swiss grades are stored.
14. As an uploader who uploads a PDF already processed, I want the same Pre-fill instantly, so that I don't wait or pay twice.
15. As the developer, I want production and the benchmark to run the same code, so that the holdout number is the production number.
16. As the developer, I want every result to record which rule or reading decided it, so that I can see where errors come from.
17. As the developer, I want per-test cost and call counts in the output, so that I can check the cost budget from real traffic.
18. As the developer, I want T set from measured data, so that the threshold isn't a guess.
19. As the developer, I want the evaluation to report unflagged Pre-fill accuracy, Flagged share, tie-break rate and cost with 95% confidence intervals, so that a small sample isn't over-trusted.
20. As the developer, I want Module accuracy reported separately, so that weak Module recognition doesn't hide in the Subject average.
21. As the developer, I want opt-in Confirmed results added to the Benchmark set (unedited ones directly, edited ones after I check the image), so that the evaluation set grows without wrong labels.
22. As the developer, I want to run an embeddings vote for Subjects as an experiment and keep it only if it beats v1 on the holdout, so that complexity has to earn its place.
23. As the developer, I want to train a local grade classifier on opt-in crops as a later experiment, so that I can test whether it can replace call 2 or 3.
24. As the developer, I want every Confirmation (and edit) logged against the Pre-fill, so that I can measure real Pre-fill accuracy and the Phase 2 trigger from production traffic.
25. As the developer, I want model versions pinned (no "latest"), so that results don't change under me.
26. As the Laravel app, I want one HTTP endpoint that takes a PDF and returns the result JSON, so that the app doesn't contain pipeline logic.
27. As the Laravel app, I want a stable `flagged` + `flag_reasons[]` contract, so that the confirm UI can be built separately.

## Implementation Decisions

- **Deployment:** Python FastAPI service in one Docker container; Laravel calls it from a queue job (synchronous from the user's view, ~5–10 s). Laravel keeps uploads, queue, confirm UI and storage. The PHP port in `properties.md` (notar repo, not in this one) is replaced, not extended.
- **Shared core:** one `process(pdf_bytes, providers) → PipelineResult` function used by both the service and the benchmark runner. Providers (Gemini flash-lite, stronger Gemini, Jev) are injected.
- **Reuse:** rendering, cropping, grade normalization, JSON/regex parsing, subject aliases, Jev flattening, the page fallback and the loop guard all exist in the benchmark and are reused as-is.
- **Call 1 schema** extends the current `{text, grade}` with `subject` (enum of Test types + `OTHER`, as in the `direct_subject` variant) and `grade_box`.
- **Rules module:** small regex table on `text`, mapping printed module codes to Modules. No local OCR.
- **Test types:** the 9 Subjects (Anglais, Maths, Allemand, Français, Histoire, Eco&Droit, Sciences, ECG, TIB), level ignored: CBE Math, maturity Math and MathRenf map to Maths; CG and ECG map to ECG, as `subject_aliases` already does. Plus each Module code from the Jev subject tree (40 codes, unique across years and école/CIE). Plus `OTHER` as a Gemini Reading only, never a Confirmed result. Ground-truth `type` for Module tests is the code (e.g. `"293"`).
- **Thresholds and model choices live in config:** T, the stronger Gemini tier, padding around `grade_box`, pinned model versions.
- **Cache:** keyed by PDF SHA-256 plus a hash of the pipeline config (prompts, models, T); stores only the result JSON with the raw Pre-fill, never another uploader's Confirmed values.
- **Image settings:** 200 dpi, PNG, color, full page (benchmark best). No extra downscaling unless an ablation shows no accuracy loss.

### Data and privacy

- Paid API tiers only (Gemini paid API: no training on inputs). Jev version pinned.
- Uploaders accept a notice that page 1 (including name/class) is sent to Google and TypeSafe for processing.
- The page image is kept **only until the Confirmation** (the confirm screen shows it), then deleted. Unconfirmed uploads and their images are deleted after **7 days**, with no result stored.
- **Opt-in** ("let my test improve the system"): image + Confirmed result are kept. Separate consent from processing consent. Unedited labels join the Benchmark set directly; edited ones go to a "to verify" pile that the developer checks against the image first (a student may edit wrongly).
- **Withdrawing opt-in** deletes the image and removes the labels from the Benchmark set. Evaluation numbers already reported are not recomputed.
- The cache stores result JSON only, never images.
- The ~100 4th-year tests are used only after permission is granted.

### Cost estimate

Per test:

```
cost = (1 − h) · [ C1 · (1 + f) + (1 − r) · Cjev + C2 + p3 · C3 + e · Cemb ]

Ci   = in_tokens_i · price_in_i + out_tokens_i · price_out_i   (per 1M tokens)
h    = cache hit rate
f    = share of tests that need fallback pages (each extra page ≈ one more C1)
r    = share decided by rules (Jev skipped)
p3   = share reaching call 3 (tie-break rate)
e    = share reaching the embeddings vote (experiment; Cemb = 0 for a local model)
```

Known today: C1 + Cjev ≈ $0.0012 (flash-lite at $0.25 in / $1.50 out, Jev at $0.042 in). C2 is a small crop with a short answer (estimate ≲ $0.0002). Budget check: `p3 · C3 ≤ 0.003 − 0.0012 − C2`. With p3 = 15% the stronger tier may cost up to ~$0.01 per call. The evaluation measures h, f, r, p3 and the real Ci from logged tokens.

### Experiment: embeddings subject vote

Looked up 2026-10-12 on the MTEB leaderboards (Multilingual v2, French v1) and the Gemini API docs. Our input is short French/German/English page text plus 9 Subjects, so the relevant MTEB column is **classification**, not retrieval.

| candidate                       | where it runs       | size | MTEB multilingual (mean / class.) | licence    | cost per test                                                       | why                                                                                                                                                              |
| ------------------------------- | ------------------- | ---- | --------------------------------- | ---------- | ------------------------------------------------------------------- | ---------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| `gemini-embedding-2`            | Google API          | —    | not listed yet (001: 68.4 / 71.8) | API        | ~$0.0002 (≈1k text tokens at $0.20 / 1M) or $0.00012 per page image | Same processor as Gemini, so no new privacy notice. **Multimodal:** can embed the page image itself, which gives a vote that doesn't depend on the transcription |
| `microsoft/harrier-oss-v1-0.6b` | local (Docker, CPU) | 0.6B | 69.0 / 73.9                       | MIT        | 0                                                                   | Best small model on the multilingual board (#10 overall, Pareto-optimal); class. score beats gemini-embedding-001                                                |
| `Qwen/Qwen3-Embedding-0.6B`     | local               | 0.6B | 64.3 / 66.8                       | Apache-2.0 | 0                                                                   | Widely used, well supported; fallback if harrier is awkward to run                                                                                               |

Not considered: >1B models (F2LLM-v2, Qwen3-Embedding-4B/8B, harrier-27b). They top the boards but are too big for a CPU container, and 9 Subjects don't need them.

**Method (no vector DB):** one embedding per labelled example → mean vector (centroid) per Subject → cosine similarity of the test's embedding to each centroid. Examples and centroids live in one JSON/NumPy file in the repo. pgvector is out of scope: 9 Subjects × ~10 examples is about 90 vectors.

**Scope:** Subjects only. Modules are decided by their printed code, so the vote has nothing to add there.

**Where it votes:** only when Jev and Gemini disagree on a Subject **and** Jev confidence < T (the cases that would otherwise be Flagged). If the embedding's top Subject matches one side with similarity margin ≥ M over the runner-up, pre-fill that side unflagged (`test_type_source = "embed"`). Otherwise flag as before. M is set from the data like T.

**Order of evaluation:**

1. `gemini-embedding-2` on text, then on the page image (cheapest to try; no new processor).
2. `harrier-oss-v1-0.6b` on text (free, local).
3. Keep the best one only if it lowers flagged share on the holdout without lowering unflagged pre-fill accuracy.

**Prerequisite:** ≥ 10 labelled examples per Subject (~90 tests). The 67 docs give that for Anglais, Maths, Allemand only; the rest come from the holdout and opt-in Confirmations.

## Testing Decisions

- **Good tests check external behavior:** given scripted Readings, assert the final `test_type`, `grade`, sources, `flagged` and reasons. They don't assert call order, prompt text or internal helpers.
- **Seam 1, `process(pdf_bytes, providers)`:** fake Gemini/Jev providers return scripted readings. Cases:
    - rule hit with Gemini Module / `OTHER` → pre-fill
    - rule hit with Gemini Subject → `subject_disagree`
    - no rule hit, Module Reading → `module_rule_miss`
    - not a test → `not_recognized`
    - Subject agree
    - disagree with Jev ≥ T and < T
    - invalid Grade → `grade_invalid`
    - ungraded test → Grade null, not flagged
    - phase 2: Grades agree; disagree → tie-break pre-fills; tie-break still disagrees → flag; no `grade_box` → call 3 directly
    - page fallback
    - cache hit makes no calls
- **Seam 2, FastAPI endpoint:** one thin test: post a PDF, get the contract JSON, and the image is gone afterwards (privacy rule at the boundary).
- **Prior art:** the benchmark's run-case tests already drive the runner with fake providers (including loop retry → page fallback); parsing/scoring tests cover grade normalization and subject matching; imaging tests cover crops.
- **No live API calls in unit tests**, as today.

### Evaluation plan

- **Data:** the 67 tuning docs and the **holdout = ~100 4th-year tests**, labelled with `type` and `grade` in the holdout table. Holdout is run once per candidate, never tuned on.
- **Baseline:** current best `gemini_jev` (`best_v2` once run) on the same docs.
- **Metrics:**

| metric                        | definition                                                                                |
| ----------------------------- | ----------------------------------------------------------------------------------------- |
| Pre-fill accuracy (unflagged) | share of unflagged tests with Test type **and** Grade correct                             |
| Flagged share                 | share with `flagged`                                                                      |
| edit rate (production)        | share of Confirmations where the uploader changed a value, Grade edits counted separately |
| tie-break rate (p3)           | share reaching call 3                                                                     |
| Jev accuracy on disagreements | how often Jev was right when it disagreed with Gemini                                     |
| end-to-end accuracy           | unflagged correct + Flagged (assumed corrected at Confirmation)                           |
| cost per test                 | from logged tokens and the formula above                                                  |
| Module accuracy               | Test type accuracy on Module tests only                                                   |

All with 95% confidence intervals (Wilson).

- **Success criteria (ship gate, on the holdout):** unflagged Pre-fill accuracy ≥ 98%, Flagged share ≤ 15%, cost ≤ $0.003/test, end-to-end accuracy ≥ baseline.
- **Phase 2 trigger:** build calls 2 and 3 if ≥ 3% of Grade Confirmations are edits over the first 200 production uploads. (The earlier "> 2% on the 67 docs" trigger is dropped: 1 vs 2 docs decides it, and the 95% interval on 1/67 spans ~0.3–8%.) Confirmed results are private, so students have no reason to inflate Grades and the edit rate stays a clean signal.
- **Experiments** (embeddings Subject vote once data gives ≥ 10 examples per Subject; local 11-class Grade classifier on crops) ship only if they beat v1 on the holdout.

## Out of Scope

- Local-only mode and local OCR.
- Grade-box templates per Test type.
- Batch mode for uploads (benchmark runs may use it).
- The Laravel confirm UI, roles, audit log and bulk actions (separate spec).
- Name masking / anonymization before sending to Gemini (only if the school requires it later).
- Jev reading grades (it only takes text).

## Further Notes

### Open questions (v1)

1. The real value of T, once ≥ 30 disagreements are logged.
2. Whether the holdout contains enough Module tests to judge Module accuracy at all. None of the 67 tuning docs is a Module test.
3. When permission for the 4th-year tests arrives (it blocks shipping).
4. Whether the school has its own rules for sending student work to cloud services (would pull name masking back in), and whether a minor student's own consent is enough for processing and opt-in, or a parent or the school must agree (blocks shipping).
5. Embeddings: does a page-image embedding (`gemini-embedding-2`) beat text embeddings for the Subject, given that layouts repeat per teacher?

### Open questions (phase 2)

1. Which stronger Gemini tier is the call-3 tie-breaker, and its exact price (must fit the budget above).
2. Whether Gemini's `box_2d` is accurate enough on handwritten Grades. Measure the crop hit rate on the 67 docs before relying on call 2.
