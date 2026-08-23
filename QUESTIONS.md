# Open decisions — documentation restructure

**Status:** ✅ **answered and implemented, 2026-08-23.** Kept as the record of why the documentation is shaped the way
it is. The question bodies below describe the state *before* the restructure — paths in them are historical.

| | Decision | What it produced |
|---|---|---|
| **Q1** | Rewrite the architecture doc; rename the directory | `docs/plan-d/` → `docs/spec-d/`; `ARCHITECTURE.md` → `docs/spec-d/architecture.md`, rewritten against the spec |
| **Q2** | GR-xx only | `grade_manager.md` deleted; its 12 genuinely-new stories folded into the GR track as GR-16…27, grouped by topic. Existing IDs unchanged |
| **Q3** | DOS cards win | `Dossier_de_formation.md` deleted; its field list absorbed into the dossier spec as the project-fields table |
| **Q4** | One decision brief | `technical_details.md` + `technical_details_vue.md` (660 lines) → `docs/frontend-decision.md` (85 lines) |
| **Q5** | One README | `README.md` — human-first, with an agent-conventions note at the end |
| **Q6** | Rewrite as the product spec | `docs/dossier-formation-part/spec.md` rebuilt for Laravel/Postgres |
| **Q7** | Delete the IMAP notes | `imap-notes.md` deleted; the one load-bearing sentence (why no mailbox access) inlined into the spec's Thesis |
| **Q8** | Skip the architecture grill | Not done. The `GradeService` concern from Q8 is recorded in the spec's "Flows" section instead |

**Two things surfaced during implementation** and are worth knowing:

- **The POC decided the dossier was private to the apprenti — no coach access at all.** DOS-10, DOS-11 and DOS-12 say
  coach, formateur and admin can all read it. The backlog is the source of truth, so the POC decision was reversed and
  the reversal is flagged in `docs/dossier-formation-part/spec.md`.
- **One PDF per project, or one for the whole dossier?** DOS-04/DOS-07 say the dossier; Nikyta's walkthroughs said per
  project. The spec follows DOS and records the disagreement as an open question — it needs a real answer.

---

## Q1 — ARCHITECTURE.md: rewrite, delete, or merge?

`docs/spec-d/architecture.md` is stale. It describes `apprentices.track` (cfc|maturite), a `modules` table, `mapping_table`, `Services/Prefill/FilenameParser`, `WeightedAverage` with CFC weights, a `trainers` table, and `grades.status`/`source`. Decisions #12–#20 in `docs/spec-d/spec.md` killed all seven. It currently carries a warning banner, but a banner is a patch, not a fix.

- **(a) Delete it.** `docs/spec-d/spec.md` already has a Data model, a Request flow and an Architecture section that supersede it.
- **(b) Rewrite it** to match `docs/spec-d/spec.md` — accepting two files to keep in sync for six weeks.
- **(c) Merge what `docs/spec-d/spec.md` lacks** (the system-overview box diagram, the deployment shape, the testability rationale) into `docs/spec-d/spec.md`, then delete the file.

➡️ **(c).** `docs/spec-d/spec.md` already duplicates ~70% of it. The 30% worth keeping is the ASCII system diagram and the "no queue, no cron, no external API" deployment statement — both belong at the top of the spec. Two synced files is a promise that won't survive week 3.

**Answer:**
rewrite it based on the updated spec in /docs/spec-d and change the name to like idk spec-final
---

## Q2 — Which user-story ID system survives?

Two ID systems describe the same 15 features: `GR-01…15` in `docs/user_stories/user_story.md` (declared source of truth) and `CAP`/`REC`/`REV`/`FBK`/`NOT-01…09` in `docs/user_stories/grade_manager.md`. `GR-01`=`CAP-01`, `GR-05`=`REC-01`, `GR-10`=`REV-01`, and so on. `technical_details*.md` cites the CAP/REC IDs; `docs/spec-d/spec.md` cites GR.

- **(a) GR-xx only.** Delete `docs/user_stories/grade_manager.md`; fold its genuinely-new stories into the GR list as new cards. Those are: CAP-05 (refuse an impossible grade), CAP-09 (protect the stored file's URL), REC-06 (re-rename after an edit), REC-08 / REV-05 (the 403 cases), REV-06 (review is read-only), FBK-04 (comment authorship), FBK-05 (live comment updates), NOT-03 (attach the scan).
- **(b) CAP/REC/REV/FBK/NOT only.** Promote `docs/user_stories/grade_manager.md` to source of truth; delete the GR section from `docs/user_stories/user_story.md`.
- **(c) Keep both** and add an explicit mapping table.

➡️ **(a).** `docs/user_stories/grade_manager.md` contributes about 9 real stories and 15 restatements. Your boss reads one list; an agent cites one ID. (c) is the worst outcome — a mapping table is a third artifact to maintain and it is exactly the indirection that makes an LLM cite the wrong story.

**Answer:**
**(a) GR-xx only
---

## Q3 — Which dossier backlog survives?

`DOS-01…14` in `docs/user_stories/user_story.md` (INVEST cards) and `docs/user_stories/Dossier_de_formation.md` (Nikyta's prose walkthroughs) describe the same feature in different formats, and disagree on scope — the prose version has organisation / technologies / role / screenshots / competence markers; the DOS cards don't.

- **(a) DOS cards win.** Nikyta's field detail is absorbed into the dossier spec as its field table.
- **(b) Nikyta's version wins** and is converted into DOS cards.
- **(c) Both stay,** one marked as the detailed elaboration of the other.

➡️ **(a).** The prose version's real value is the *field list* — that is spec material, not backlog material. Cards belong in the backlog, fields belong in the spec; splitting them that way gives each file exactly one job. This is Nikyta's work, so have that conversation with them first.

**Answer:**
DOS cards win
---

## Q4 — React or Vue: decide now, or document it as still open?

The tech-stack table in `docs/spec-d/spec.md` says React 19. `note_for_bilal.md` says three of four voted Vue and you are waiting on Bilal. 676 lines across `docs/technical_details.md` and `docs/technical_details_vue.md` exist solely because this is unresolved, and roughly 85% of that is the same Inertia tutorial written twice.

- **(a) Assume React.** Reduce the Vue file to a one-page "why we considered it, why we didn't".
- **(b) Assume Vue.** Flip the stack table in `docs/spec-d/spec.md`; reduce the React file the same way.
- **(c) Collapse both into one ~60-line decision brief** — side-by-side stack table, the honest cost section, a recommendation — marked "pending Bilal". Leave `docs/spec-d/spec.md`'s stack table at React until he answers.

➡️ **(c).** You explicitly asked Bilal for an opinion; pre-deciding in the docs undercuts your own question. The code samples in both files are Inertia documentation, not decisions, and they go obsolete the moment the starter kit is installed. A 60-line brief is something Bilal can read and answer; 676 lines is something he will skim.

**Answer:**
Collapse both into one ~60-line decision brief**
---

## Q5 — What is the entry point, and who is it for?

There is no `README.md`. The first file visible at the repo root is `note_for_bilal.md`, an informal internal note.

- **(a) One README** serving both your boss and an AI agent.
- **(b) Two files.** `README.md` for a human reader — problem, roles, user stories in plain language, stack, timeline, current status. `AGENTS.md` (or `CLAUDE.md`) for an agent — where the source of truth lives, ID conventions, which files are authoritative versus archived, what is decided versus open.
- **(c) README only,** relying on the spec being readable enough for an agent.

➡️ **(b).** These are genuinely different documents. Your boss needs "what are we building, who uses it, when is it done" on one screen. An agent needs "`docs/user_stories/user_story.md` is the source of truth, `docs/spec-d/spec.md` is the design, `docs/spec-d/architecture.md` is archived, never cite `.scratch/`". One file serving both ends up too technical for him and too vague for the agent.

**Answer:**
- **(a) One README**
---

## Q6 — Is `docs/dossier-formation-part/spec.md` the POC spec or the product spec?

It is currently the POC spec — localStorage, no backend, single static HTML/CSS/JS. But `docs/spec-d/spec.md` cites it as *the* dossier spec for the Laravel product, and it points at `.scratch/DOSSIER_FORMATION/spec.md` for the real one, which is not in this repo.

- **(a) Rename** to `poc-spec.md`, mark it superseded, and write a thin product spec alongside it from `DOS-01…14` plus Nikyta's field list.
- **(b) Rewrite it in place as the product spec.** Keep only the decisions that survive the move to Laravel/Postgres (decisions #17–#19 already anticipate this); archive the localStorage detail.
- **(c) Leave as-is;** fix only the cross-reference in `docs/spec-d/spec.md`.

➡️ **(b).** The POC is built and its spec has served its purpose. Decisions #9–#16 — the brief-alignment field work — are the durable content and carry over to Laravel unchanged; decisions #1–#8 are mostly localStorage mechanics that die with the POC. One dossier spec, product-shaped, with the POC noted as "already built, informs the field list".

**Answer:**

- **(b) Rewrite it in place as the product spec.**
---

## Q7 — Keep `docs/spec-d/imap-notes.md` and the "Plan D" directory name?

`docs/spec-d/imap-notes.md` documents an approach that was rejected. `docs/spec-d/` references plans A–C that are not in this repo.

- **(a)** Rename `docs/spec-d/` to `docs/spec/`, and move `docs/spec-d/imap-notes.md` to `docs/archive/` with a one-line "why we rejected mailbox intake" pointer left in the spec.
- **(b)** Keep both names — they are internal and the team knows what they mean.
- **(c)** Delete `docs/spec-d/imap-notes.md`; the decision is made.

➡️ **(a).** Keep the research: it is the answer to "why don't you just read their inbox?", which your boss will ask. But it is archive, not spec, and it should not sit at the same level as the thing you are building. `plan-d` is a name for you, not for a reader.

**Answer:**
removed imap-notes.md since it's useless
---

## Q8 — How much architecture grilling do you want on the spec?

There is no code in this repo yet, so an architecture review can only grill the *proposed* design in `docs/spec-d/spec.md` — the `GradeService::store()`/`rename()`/`notify()` trio, `AcademicCalendar`, and the `Moyenne` seam — for whether those are deep modules or thin wrappers.

- **(a)** Run that grill now, as a later round of this session. It will change the Architecture section of the spec.
- **(b)** Skip it. The architecture is adequate; the documentation was the problem.
- **(c)** Do it as a separate pass once the docs are settled.

➡️ **(c).** The docs cleanup is mechanical and unblocked; the architecture grill is a real design conversation that deserves its own session and will produce ADRs. Doing both at once buries the design discussion under file-shuffling.

One thing worth flagging now regardless of the answer: `GradeService::store()` performs a database write, a filesystem rename and an SMTP send inside a single synchronous request. If Mailtrap is slow or down, the apprenti's upload appears to fail after the grade has already been persisted. That is the first thing a grill would go after.

**Answer:**
- **(b)** Skip it. The architecture is adequate; the documentation was the problem.

---

## Downstream, blocked on the answers above

- **`CONTEXT.md` glossary** — apprenti, coach, formateur, admin, matière, moyenne, section, academic period, compétence, dossier entry. Blocked on **Q2** and **Q3** (which vocabulary is canonical). It also has to settle the French/English mixing, which is currently inconsistent: the schema in `docs/spec-d/spec.md` uses `APPRENTICES`/`apprentice_id` while every story and role table says *apprenti*.
- **ADRs** for the decisions that are hard to reverse and non-obvious — PostgreSQL over MySQL/SQLite, admin-created accounts with no self-service reset, storing the resolved academic period on the grade row. Blocked on **Q8**.
