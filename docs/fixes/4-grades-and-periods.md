# Grades and periods

This file is the context for solving the issues below in the package of issue Grades and periods.

- **Depends on:** nothing. Can start immediately.
- **Blocks:** package 7 (Grade app code).
- **Names agreed across packages:** the apprentice foreign key is `user_id` on both tables: `grades.user_id` (kept as is) and `apprenticeship_periods.user_id` (renamed from `apprentice_id`); `grades.apprenticeship_period_id` (singular). Decided 2026-10-07, replacing the earlier plan to rename `grades.user_id` to `apprentice_id`.
- **Migrations are NOT edited in place.** Every schema fix goes in a new migration; the existing files `2026_10_07_130000` and `2026_10_07_140000` stay untouched. After pulling, everyone runs `./vendor/bin/sail artisan migrate`.
- **Migration grouping:** see `./CONTEXT.md`. G1 and G2 share one index migration (group A, which can also take package 5's `users` index); the two CHECKs of G5 share one migration (group B); Comment 7 and G6 are `down()` repairs, done together with package 2's.

Files owned by this package (no other package edits them):

- `../../database/migrations/2026_10_07_130000_create_apprenticeship_periods_table.php` (reference only, not edited)
- `../../database/migrations/2026_10_07_140000_rework_grades_table.php` (reference only, not edited)
- `../../app/Models/Grade.php`
- `../../app/Models/ApprenticeshipPeriod.php`
- `../../app/Support/ApprenticeList.php`

Migrations added by this package:

- `../../database/migrations/2026_10_07_190000_rename_apprentice_id_to_user_id_on_apprenticeship_periods_table.php`
- `../../database/migrations/2026_10_07_200000_add_indexes_to_grades_table.php`
- `../../database/migrations/2026_10_07_210000_add_checks_to_apprenticeship_periods_table.php`

`../../app/Models/User.php` is owned by package 5, but two of its relations were aligned here with the `user_id` decision: `grades()` and `apprenticeshipPeriods()` no longer pass `'apprentice_id'` and let Laravel derive `user_id`.

# Problems encountered

- [x] **Comment 4 — Period foreign key has two spellings.** The migration creates `apprenticeship_period_id`. `Grade` (docblock, fillable, `apprenticeshipPeriod()`) and `ApprenticeshipPeriod::grades()` use `apprenticeship_periods_id`.
- [x] **M2 — `Grade` uses `apprentice_id`, the table has `user_id`.** No migration renames the column. Fillable, `Grade::apprentice()` and `User::grades()` query a missing column, while `ApprenticeList.php` lines 65 to 69 still use `user_id`.
- [x] **G1 — `grades` loses its apprentice index.** The original index is `(user_id, evaluation_node_id, semester)`. PostgreSQL drops the whole index when `semester` is dropped (line 21), so "all grades of apprentice X" and the `restrictOnDelete` check become sequential scans.
- [x] **G2 — New foreign keys are not indexed:** `grades.subject_id` and `grades.apprenticeship_period_id`.
- [x] **Comment 7 — Rollback allows invalid semesters.** `down()` line 33 restores `semester` without `grades_semester_check` (1 to 8).
- [x] **G6 — `down()` fails on a non-empty table** (`semester` NOT NULL, no default) and does not restore the composite index.
- [ ] **Comment 2 — Saved grades block the migration.** `subject_id` and `apprenticeship_period_id` are added NOT NULL with no backfill, so `up()` fails if `grades` has rows.
- [x] **G5 — `apprenticeship_periods` has no CHECK.** `semester` is unconstrained (the `.d2` says "check"; 1 to 8 was the rule on `grades`), and nothing enforces `end_date >= start_date`.

# Fixes suggested

1. **Comment 4.** Keep the migration's singular name. In `Grade.php`: docblock, fillable and `apprenticeshipPeriod()` → `apprenticeship_period_id` (the explicit key argument can then be dropped). In `ApprenticeshipPeriod.php`: `grades()` → `hasMany(Grade::class)`.
2. **M2 — superseded, see the decision below.** The first plan was to rename the column. In `2026_10_07_140000` `up()`, add the rename and its constraint:

   ```php
   $table->renameColumn('user_id', 'apprentice_id');
   $table->dropForeign('grades_user_id_foreign');
   $table->foreign('apprentice_id')->references('id')->on('users')->restrictOnDelete();
   ```

   Mirror it in `down()`. In `ApprenticeList.php`, replace `user_id` with `apprentice_id` in `gradeStats()` (query, `groupBy`, `selectRaw`, `keyBy`).

   **Decided (2026-10-07): the models follow the migrations, and the column is `user_id` everywhere.**
   - `grades.user_id` is kept. `Grade` (docblock, fillable, `apprentice()`) now uses `user_id`. No migration, and `ApprenticeList.php`, `GradePolicy`, `ApprenticeController`, the seeders and the tests need no change for this, since they already use `user_id`.
   - `apprenticeship_periods.apprentice_id` is renamed to `user_id` by the new migration `2026_10_07_190000` (column, foreign key and index names). `ApprenticeshipPeriod` (docblock, fillable, `apprentice()`) follows.
   - The relation methods keep their name `apprentice()`; only the column changes.
3. **G1 and G2.** In a new migration on `grades`, add (and drop them in its `down()`):

   ```php
   $table->index('user_id');
   $table->index('subject_id');
   $table->index('apprenticeship_period_id');
   ```

   `domain_id` keeps its own index (`grades_domain_id_index`, already renamed by `2026_10_07_140000`).
4. **Comment 7 and G6 — to decide, see "Conflict with the no-edit rule" below.** Both are defects of the `down()` of `2026_10_07_140000`, and a new migration cannot change what another migration's `down()` does. The fix as first written: in `down()`, add `semester` with `->default(1)`, then re-add the check after the `Schema::table` call:
   `DB::statement('ALTER TABLE grades ADD CONSTRAINT grades_semester_check CHECK (semester BETWEEN 1 AND 8)');`
   and restore `$table->index(['user_id', 'evaluation_node_id', 'semester']);` (add the `DB` facade import).
5. **Comment 2: skip the backfill, on purpose.** The review asks to create matching subjects and periods for existing grades. That cannot be done honestly: `2026_10_01_112517` already drops every tree link and weight, and `2026_10_01_144037` already fails on a non-empty `subjects` table, so the chain never reaches this migration with data. No environment holds real grades (these migrations are not on `main`). No comment is added in `up()` (the file is not edited): package 8 (Docs) records in `../db/db.md` that the chain targets a fresh database. Reply to the reviewer with this reason.
6. **G5.** In a new migration on `apprenticeship_periods` (its `down()` drops both constraints):

   ```php
   DB::statement('ALTER TABLE apprenticeship_periods ADD CONSTRAINT apprenticeship_periods_semester_check CHECK (semester BETWEEN 1 AND 8)');
   DB::statement('ALTER TABLE apprenticeship_periods ADD CONSTRAINT apprenticeship_periods_dates_check CHECK (end_date >= start_date)');
   ```

# Conflict with the no-edit rule

Fix 4 (Comment 7 and G6) is the only one that cannot be done in a new migration: it repairs the rollback of `2026_10_07_140000`, which lives in that file's `down()`. Choose one:

- **Make an exception** and edit only the `down()` of `2026_10_07_140000`. Its `up()` is unchanged, so nobody has to re-run anything.
- **Leave the rollback as it is** and document that rolling back `2026_10_07_140000` is not supported (lost semester check, fails on a non-empty table). Reply to the reviewer of Comment 7 with this reason.

**Decided (2026-10-07): the exception is made**, as package 2 already did for its own `down()` repairs (commit `19f6997a`). Only the `down()` of `2026_10_07_140000` is edited: `semester` comes back with a temporary default of 1 (dropped right after, as the original column had none), then the composite index `(user_id, evaluation_node_id, semester)` and `grades_semester_check` are restored. `up()` is unchanged, so nobody has to re-run anything.

# Open question (needs a team decision, not a code fix)

- **How does a grade pick its period?** The user story says the semester is derived from `test_date` (August to December = 1, January to July = 2). `apprenticeship_periods.semester` is 1 to 8 with stored dates. Nothing says who creates the periods or how a new grade is attached to one. Package 7 needs the answer.

Check when done: `migrate:fresh` passes, `\d grades` shows `user_id`, the three indexes and no `semester`; in tinker `Grade::query()->with('apprenticeshipPeriod', 'apprentice')->first()` runs without a missing-column error.
