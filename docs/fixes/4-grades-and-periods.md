# Grades and periods

This file is the context for solving the issues below in the package of issue Grades and periods.

- **Depends on:** nothing. Can start immediately.
- **Blocks:** package 7 (Grade app code).
- **Names agreed across packages:** `grades.apprentice_id` (renamed from `user_id`), `grades.apprenticeship_period_id` (singular).
- **Migrations are edited in place** (they are not on `main`). After pulling, everyone runs `./vendor/bin/sail artisan migrate:fresh`.

Files owned by this package (no other package edits them):

- `../../database/migrations/2026_10_07_130000_create_apprenticeship_periods_table.php`
- `../../database/migrations/2026_10_07_140000_rework_grades_table.php`
- `../../app/Models/Grade.php`
- `../../app/Models/ApprenticeshipPeriod.php`
- `../../app/Support/ApprenticeList.php`

`../../app/Models/User.php` is **not** in this package. `User::grades()` already uses `apprentice_id`, so it becomes correct once the rename below lands; package 5 owns that file.

# Problems encountered

- [ ] **Comment 4 — Period foreign key has two spellings.** The migration creates `apprenticeship_period_id`. `Grade` (docblock, fillable, `apprenticeshipPeriod()`) and `ApprenticeshipPeriod::grades()` use `apprenticeship_periods_id`.
- [ ] **M2 — `Grade` uses `apprentice_id`, the table has `user_id`.** No migration renames the column. Fillable, `Grade::apprentice()` and `User::grades()` query a missing column, while `ApprenticeList.php` lines 65 to 69 still use `user_id`.
- [ ] **G1 — `grades` loses its apprentice index.** The original index is `(user_id, evaluation_node_id, semester)`. PostgreSQL drops the whole index when `semester` is dropped (line 21), so "all grades of apprentice X" and the `restrictOnDelete` check become sequential scans.
- [ ] **G2 — New foreign keys are not indexed:** `grades.subject_id` and `grades.apprenticeship_period_id`.
- [ ] **Comment 7 — Rollback allows invalid semesters.** `down()` line 33 restores `semester` without `grades_semester_check` (1 to 8).
- [ ] **G6 — `down()` fails on a non-empty table** (`semester` NOT NULL, no default) and does not restore the composite index.
- [ ] **Comment 2 — Saved grades block the migration.** `subject_id` and `apprenticeship_period_id` are added NOT NULL with no backfill, so `up()` fails if `grades` has rows.
- [ ] **G5 — `apprenticeship_periods` has no CHECK.** `semester` is unconstrained (the `.d2` says "check"; 1 to 8 was the rule on `grades`), and nothing enforces `end_date >= start_date`.

# Fixes suggested

1. **Comment 4.** Keep the migration's singular name. In `Grade.php`: docblock, fillable and `apprenticeshipPeriod()` → `apprenticeship_period_id` (the explicit key argument can then be dropped). In `ApprenticeshipPeriod.php`: `grades()` → `hasMany(Grade::class)`.
2. **M2.** In `2026_10_07_140000` `up()`, add the rename and its constraint:

   ```php
   $table->renameColumn('user_id', 'apprentice_id');
   $table->dropForeign('grades_user_id_foreign');
   $table->foreign('apprentice_id')->references('id')->on('users')->restrictOnDelete();
   ```

   Mirror it in `down()`. In `ApprenticeList.php`, replace `user_id` with `apprentice_id` in `gradeStats()` (query, `groupBy`, `selectRaw`, `keyBy`).
3. **G1 and G2.** In `up()`, add after the column changes:

   ```php
   $table->index(['apprentice_id', 'apprenticeship_period_id']);
   $table->index('subject_id');
   $table->index('apprenticeship_period_id');
   ```

   `domain_id` keeps its own index (`grades_domain_id_index`, already renamed in this migration).
4. **Comment 7 and G6.** In `down()`: add `semester` with `->default(1)`, then re-add the check after the `Schema::table` call:
   `DB::statement('ALTER TABLE grades ADD CONSTRAINT grades_semester_check CHECK (semester BETWEEN 1 AND 8)');`
   and restore `$table->index(['user_id', 'evaluation_node_id', 'semester']);` (add the `DB` facade import).
5. **Comment 2: skip the backfill, on purpose.** The review asks to create matching subjects and periods for existing grades. That cannot be done honestly: `2026_10_01_112517` already drops every tree link and weight, and `2026_10_01_144037` already fails on a non-empty `subjects` table, so the chain never reaches this migration with data. No environment holds real grades (these migrations are not on `main`). Add a short comment in `up()` saying the chain targets a fresh database; package 8 (Docs) records it in `../db/db.md`. Reply to the reviewer with this reason.
6. **G5.** In `2026_10_07_130000`, after `Schema::create`:

   ```php
   DB::statement('ALTER TABLE apprenticeship_periods ADD CONSTRAINT apprenticeship_periods_semester_check CHECK (semester BETWEEN 1 AND 8)');
   DB::statement('ALTER TABLE apprenticeship_periods ADD CONSTRAINT apprenticeship_periods_dates_check CHECK (end_date >= start_date)');
   ```

# Open question (needs a team decision, not a code fix)

- **How does a grade pick its period?** The user story says the semester is derived from `test_date` (August to December = 1, January to July = 2). `apprenticeship_periods.semester` is 1 to 8 with stored dates. Nothing says who creates the periods or how a new grade is attached to one. Package 7 needs the answer.

Check when done: `migrate:fresh` passes, `\d grades` shows `apprentice_id`, the three indexes and no `semester`; in tinker `Grade::query()->with('apprenticeshipPeriod', 'apprentice')->first()` runs without a missing-column error.
