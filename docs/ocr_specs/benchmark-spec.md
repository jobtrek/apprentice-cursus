Set up and organize this project for an OCR accuracy benchmark. Write the code only — do NOT run the app, call any API, or download any model.

## Context

- Ground truth: `jev/real_table.json` — 68 entries of `{"pdf_path": "pdfs/<file>.pdf", "type": "<subject>" | null, "grade": float | null}`. Read it first and match its structure; the document id is `pdf_path`.
    - `type` = subject label as written in the file name: `Anglais`, `Maths`, `Allemand`, `Histoire`, `ECG`, `Eco&Droit`, `CG`, `Français`.
    - `type: null` (e.g. `2026-02-09_sem1_student_C.pdf`) → excluded from subject scoring. `grade: null` (e.g. `2_3_Anglais_student_C.pdf`) → excluded from grade scoring; a model returning `null` there counts as correct.
- Goal: measure how well different models extract the **subject** and **grade** from the first page of each scanned test, and how image/input variables affect that.
- Hardware for local runs: MacBook Pro M4, 16 GB RAM, Apple Silicon (MLX).

## Stack (install with uv)

- `uv` for project + dependencies, Python 3.12 (update `.python-version` and `requires-python` from 3.14 to 3.12)
- `pymupdf` — render the first page of each PDF at a given DPI
- `pillow` + `numpy` — crop, color mode, format/compression
- `opencv-python-headless` — scan-quality measurement (blur, skew, contrast) and optional binarization
- `google-genai` — Gemini client
- `openai` — client for the local model via mlx-vlm's OpenAI-compatible server (`base_url="http://localhost:8080/v1"`, dummy api_key)
- `mlx-vlm` — only document how to start the server, do not start it:
  `mlx_vlm.server --model jwindle47/chandra-ocr-2-8bit-mlx --port 8080`
- `typesafe-sdk` — Jev (already a dependency, used by `jev/jev.py`)
- `pydantic` — output schema `{subject: str, grade: float | null}` and config validation
- `pyyaml` — experiment config files
- `jiwer` — CER/WER on extracted subject text
- `pandas` — results
- `plotly` — charts (standalone HTML)
- `python-dotenv` — `GEMINI_API_KEY` and the TypeSafe key from `.env` (add `.env.example`, gitignore `.env`). Replace the current `dotenv` package with `python-dotenv`.
- `pytest` — unit tests
- Remove `pypdf` (replaced by pymupdf).

## Model configurations

1. Gemini alone
2. Gemini + jev — two steps:
    1. Gemini reads the image and returns `{text: str, grade: float | null}`: a verbatim transcription of the page plus the grade. It does NOT pick the subject.
    2. Jev (`typesafe_sdk.TypeSafeClient().system_one`, see `jev/jev.py`) gets `state={"first_page_text": text}` and one `Choice` question whose criteria are the flattened `jev/subjects.json` (`"subjects/maturity_subjects/fondamental/Anglais": "Anglais"`, …) plus `OTHER`. Its choice is the subject.
    3. Final result = `{subject: <Jev choice mapped to a ground-truth label>, grade: <Gemini grade>}`. Log the Jev choice path, Jev confidence, and the transcription.
3. Local model (chandra-ocr-2-8bit-mlx) alone
4. Local model + jev — same as 2, with the local model doing step 1.

Both providers sit behind one shared interface (e.g. `extract(image, prompt, schema) -> Result`), so configs differ only by provider and the jev flag. Request JSON via structured output / `response_format` json_schema, with regex fallback if JSON is invalid. Temperature 0. Pin exact model version strings in config (no "latest" aliases). The Gemini model name currently in use is `gemini-3.5-flash-lite` (see `src/ocr_recognition/main.py`).

### Subject labels

Jev choices and free-text model outputs use different names than the ground truth (`Math` vs `Maths`, `EcoDroit` vs `Eco&Droit`, `English` vs `Anglais`). Put one alias table in config that maps every variant to a ground-truth label, e.g.:

- `Maths` ← Math, MathRenf, Mathématiques, Mathématiques fondamentales, Mathématiques renforcées
- `Anglais` ← English
- `Eco&Droit` ← EcoDroit, Économie et droit
- `ECG` ← Culture general, Culture générale
- `CG` ← (check: CG and ECG may be the same subject; leave as separate labels and flag in the README)

Both `subject_correct` and the Jev mapping use this table.

## Image variables (experiment dimensions)

Each variable is a configurable list in the YAML config:

- **dpi**: 100, 150, 200, 300
- **crop**: full, top_half, top_quarter. Crop fractions are configurable, and an optional per-document bounding-box override is allowed, for layouts where the grade sits elsewhere.
- **format**: png, jpeg (with jpeg_quality: 95, 75, 50)
- **color_mode**: color, grayscale, binarized (Otsu)
- **max_side_px** (optional): resize so the longest side ≤ N, to test downscaling explicitly instead of letting the model do it silently

Scan quality is a property of the document, not something we vary. For every rendered image, measure and log:

- blur score (Laplacian variance), contrast (std of grayscale), estimated skew angle, final width/height, file size in bytes
  These are used later to check whether low quality correlates with errors.

## Experiment modes

- **baseline**: one default setting (e.g. 200 DPI, top_half, png, color)
- **ablation** (default): start from baseline, vary ONE variable at a time across its values
- **grid**: full cartesian product, opt-in only. Print the total number of API/model calls and require `--yes` to proceed.
- `--limit N` to run on a subset of documents, `--configs` to pick model configs, `--repeats N` to run each case N times and measure non-determinism
- Results are cached by a hash of (document, image settings, provider, model version, prompt hash, jev flag), so re-running skips completed cases

## Prompts

All prompts live as plain files in a top-level `prompts/` folder, never hardcoded in Python, so they can be edited without touching code.

- One file per prompt, e.g. `prompts/extract_subject_grade.md` (configs 1 and 3), `prompts/extract_with_jev.md` (configs 2 and 4: transcribe + grade, no subject)
- The Jev `Choice` instruction also lives in `prompts/` (e.g. `prompts/jev_subject_choice.md`)
- `{placeholders}` for dynamic values
- The config selects which prompt each model config uses; switch via config or `--prompt <name>`
- Store the prompt name + content hash in every result line

## Logged per call (results/raw/*.jsonl)

document id, model config, provider, exact model version, prompt name + hash, dpi, crop, format, jpeg_quality, color_mode, max_side_px, image width/height/bytes, blur, contrast, skew, temperature, repeat index, raw model output, parsed subject/grade, jev choice + confidence (jev configs only), input/output tokens, cost_usd, latency_s (total and per step for jev configs), error (timeout / invalid JSON / API error), timestamp

Cost: port the pricing logic from `src/ocr_recognition/main.py` (`query_cost`, Jev input price) into config. Local model cost = 0.

## Metrics (score.py, from raw results — no re-calling models)

- `grade_found`, `grade_correct` (exact after normalizing "5,5" → 5.5), `grade_abs_error`
- `subject_correct` (case/accent-insensitive, via the alias table), `subject_cer` (jiwer)
- `latency_s`, `cost_usd`, `error_rate`
- Failures (timeout, invalid JSON, no grade) count as incorrect but are also reported separately
- Consistency across repeats (% of repeats with identical output)

## Visualization (report.py → plotly HTML in results/reports/)

- Grade & subject accuracy per model config (grouped bar)
- One line chart per image variable: accuracy vs dpi / crop / format / jpeg_quality / color_mode, one line per model config
- Expected vs extracted grade scatter, colored by config, diagonal reference line
- Subject CER box plot per config
- Accuracy vs scan quality (blur, contrast, skew) scatter
- Latency per config and per dpi; cost per config
- A results table filterable by document, to inspect individual failures

## Suggested structure

prompts/
extract_subject_grade.md
extract_with_jev.md
jev_subject_choice.md
configs/
baseline.yaml
ablation.yaml
grid.yaml
src/ocr_bench/
config.py # load + validate YAML (pydantic)
prompts.py # load + fill prompt files, compute hash
imaging/
render.py # PDF → image at DPI
transform.py # crop, color mode, format/compression, resize
quality.py # blur, contrast, skew measurement
providers/
base.py
gemini.py
local_mlx.py
jev.py # Jev subject step (moved logic from jev/jev.py)
experiments.py # build baseline / ablation / grid case lists
run.py # CLI: execute cases, cache, write raw jsonl
score.py # raw → results/scores.csv
report.py # plotly charts
tests/ # pytest, mocked providers, no network: # rendering at each DPI gives the expected pixel size, # crop boundaries, color modes, jpeg quality, quality metrics, # grade parsing/normalization, subject matching + alias table, # jev label mapping, prompt loading, # ablation/grid case generation counts, cache keys, scoring
results/ # gitignored except .gitkeep
README.md # setup, starting the mlx server, running baseline/ablation/grid, reading reports

### Existing files

- Keep `jev/real_table.json` and `jev/subjects.json` where they are; point config at them.
- `jev/jev.py`: its logic moves into `src/ocr_bench/jev.py`; delete the old file or keep it as a thin CLI wrapper — say which.
- `src/ocr_recognition/` (`main.py`, `rules.py`): superseded by `ocr_bench`. Port anything still useful (cost calc, grade enum 1–6 by 0.5, `print_table`), then delete the package and update `[project.scripts]` to the new CLI.
- `spec.md`, `tests.md`: leave untouched.
- `pdfs/` is gitignored; do not change that.

## Then

1. Run `uv sync` and `uv run pytest`. Tests are offline, so this is allowed. All tests must pass; paste the summary line.
2. Do NOT run `run.py`, call Gemini/Jev, or start/download the mlx model.
3. Final report, in this order:
    1. File tree of what was created, moved, or deleted, with one line per move explaining why
    2. Exact commands to: start the mlx server, run baseline on 3 documents, run ablation, score, build the report
    3. Number of model calls for baseline, ablation, and grid with the default configs (68 documents, 4 configs, 1 repeat)
    4. Ground-truth issues found in `real_table.json` (nulls, `CG` vs `ECG`, files with two grades like `1_3_CG_3.5_4_student_B.pdf`)
    5. Open questions or assumptions made
4. Do not commit.
