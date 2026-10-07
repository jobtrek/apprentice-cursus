# Docs

This file is the context for solving the issues below in the package of issue Docs.

- **Depends on:** all names being final. Do this last, after packages 1 to 7, 9 and 10 have landed.
- **Blocks:** nothing.
- Other packages do not edit these files; they send their final names and decisions to this package's owner.

Files owned by this package (no other package edits them):

- `../db/db.md`
- `../db/schemas/mcd_current.d2`
- `../db/CONTEXT.md`
- `../adr/ADR.md`
- `../../CLAUDE.md` (project root)

# Problems encountered

`../db/db.md`

- [ ] **Comment 8 — line 9:** `apprenticeship_periods` is described as "planned, not yet migrated"; `2026_10_07_130000` creates it.
- [ ] **D1 — line 13:** `subject_category` is listed as a current table; it was dropped.
- [ ] **D1 — line 14:** `subjects` "belongs to one `domain_id` + one `subject_category_id`"; the second column was dropped and `name` was added.
- [ ] **D1 — line 21:** `grades` described with `user_id` and `semester (1–8)`; the row now has `user_id`, `apprenticeship_period_id`, no `semester`.
- [ ] **D1 — line 52:** "no Eloquent model for these tables yet" and "code and seeders still read `users.apprenticeship_id`"; models now exist.
- [ ] **D3 — line 54:** heading "Domain nodes" while the table is `domain_links`.
- [ ] **D4 — lines 20 and 52:** `users.is_mp` called "transitional" with no end date and no statement of which `is_mp` wins.
- [ ] **Comment 2 / G3:** nothing says the migration chain from `2026_10_01_095759` onward only works on a fresh database.

`../db/schemas/mcd_current.d2`

- [ ] **Comment 8 — lines 11 to 15:** `domain_links` has no `id`; `parent_id` and `child_id` are drawn as the primary key. `domain_link_weights.domain_link_id` then has no target.
- [ ] **D2 — lines 24 to 36:** `subjects.subject_category_id` and the `subject_category` table are still drawn (and the relation at line 219); `subjects.name` is missing.
- [ ] **D2 — line 75:** `users.apprenticeship_periods` foreign key column does not exist (the link goes the other way, `apprenticeship_periods.user_id`).
- [ ] **D2 — users block:** `is_mp` is missing although the column exists.
- [ ] **D2 — line 86:** `grades.apprenticeship_periods_id` (plural); the agreed name is `apprenticeship_period_id`.
- [ ] **D2 — line 239:** the `subjects -> domains` arrow is drawn the wrong way round (a domain has many subjects).

`../db/CONTEXT.md`

- [ ] **D7 — lines 19 and 33:** points to `docs/db/AGENT.md`, which does not exist.

`../adr/ADR.md`

- [ ] **D5:** these entries describe the pre-redesign model and are not marked superseded: MP as sibling nodes (line 5), `users.apprenticeship_id` (32), timestamps list naming `evaluation_nodes` / `subject_category` (38), Composite pattern (54 to 58), `addChild()` invariants (60 to 64), `aggregation` (66 to 70), enum columns `period_scope` / `variant` (74), `subjects` keeps no `name` (78 to 82), restrict list naming `evaluation_results` (86), `grades.semester` CHECK (98), non-mass-assignable `apprenticeship_id` (132).
- [ ] **D5:** no dated entry records the redesign (domains, links, per-context weights, contexts, periods).

`../../CLAUDE.md`

- [ ] **D8:** lists `docs/project-docs/grade_tree_*.md` and the ADR's evaluation-tree model as source of truth, and says "the MP/Matura grading variant is modeled as sibling nodes in one evaluation tree", which is no longer the design.

Open in the docs

- [ ] **D6:** the user story derives the semester from `test_date` (August to December = 1, January to July = 2); `apprenticeship_periods.semester` is 1 to 8 with stored dates. No doc says who creates periods or how a grade is attached to one.

# Fixes suggested

1. **`db.md` line 9.** Describe `apprenticeship_periods` as implemented by `2026_10_07_130000`: `id`, `user_id` FK → `users.id` (restrict; created as `apprentice_id`, renamed by `2026_10_07_190000`), `semester` smallint, `year` generated as `(semester + 1) / 2`, `start_date`, `end_date`, index (`user_id`, `year`, `semester`), plus the two CHECKs added by `2026_10_07_210000` (`semester BETWEEN 1 AND 8`, `end_date >= start_date`).
2. **`db.md` lines 13, 14.** Remove `subject_category`; `subjects` = `name` + `domain_id` + `created_at`.
3. **`db.md` line 21.** `grades`: `user_id`, `domain_id`, `subject_id`, `apprenticeship_period_id`, value 1.0 to 6.0, `test_date`, optional proof file. No `semester`. One index per foreign key (`2026_10_07_200000`).
4. **`db.md` line 52.** Replace the "not wired" paragraph with the real state after packages 5 to 7.
5. **`db.md` line 54.** Rename the section "Domain links"; use `DomainLink` / `DomainLinkWeight` everywhere.
6. **`db.md`, new short section "Migrating".** State that the chain from `2026_10_01_095759` drops the old tree and requires `migrate:fresh` followed by seeding; existing grades are not carried over.
7. **`.d2` `domain_links`.**

   ```
   domain_links: {
     shape: sql_table
     id: bigint {constraint: primary_key}
     parent_id: bigint {constraint: foreign_key}
     child_id: bigint {constraint: foreign_key}
     # UNIQUE (parent_id, child_id), CHECK (parent_id <> child_id)
   }
   ```

8. **`.d2` subjects.** Remove `subject_category_id`, the `subject_category` table and its arrow; add `name: varchar`. Reverse line 239 to `domains -> subjects`.
9. **`.d2` users and grades.** Remove `apprenticeship_periods` from `users`; add or drop `is_mp` according to decision D1 in package 10; rename line 86 to `apprenticeship_period_id`.
10. **`../../CONTEXT.md`.** Remove the `AGENT.md` lines, or rename them to `../../CONTEXT.md` if that is the file meant.
11. **`ADR.md`.** Add a dated entry "2026-10-01 — Grade tree redesign: domains, links, per-context weights" covering: why weights moved from the link to (`context`, `link`), MP as a separate context instead of sibling nodes, `subjects` now carrying `name` and `domain_id`, periods replacing `grades.semester`, the delete rule chosen for `domain_links` (package 1), and where the cycle guard lives (package 6). Mark each old entry listed above with "_(Superseded by 2026-10-01 — Grade tree redesign.)_", the pattern the file already uses at line 136.
12. **`../../CLAUDE.md`.** Update the ADR one-liner and point the grade-tree source of truth at `../db/db.md` and `../db/schemas/mcd_current.d2`; say whether `grade_tree_IT.md` / `grade_tree_EC.md` still describe the business weights (they probably do) even though the table names changed.
13. **D6.** Once packages 4 and 7 settle how a grade picks its period, write the rule in the "Apprenticeship periods" section of `db.md`.
14. **Migrations of package 10.** Once they land, describe in `db.md` and the `.d2` what each one changed: the `users.apprenticeship_context_id` index and the `is_mp` outcome (migration U), the `domain_subject` pivot, the dropped `subjects.domain_id` and the grade guard (migration S), and the explicit delete rules if any (migration R). Record the decisions D1 to D6 of `../fixes/10-remaining-migrations.md` in `ADR.md`.

Check when done: every table and column named in `db.md` and the `.d2` exists after `migrate:fresh` (compare with `\dt` and `\d <table>`), and `grep -rn "subject_category\|evaluation_node\|apprenticeship_periods_id" docs/db/db.md docs/db/schemas/mcd_current.d2` returns nothing.
