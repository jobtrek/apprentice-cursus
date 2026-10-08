# Subjects

This file is the context for solving the issues below in the package of issue Subjects.

- **Depends on:** nothing. Can start immediately.
- **Blocks:** package 6 (Tree app code), whose seeder creates subjects.
- **Migrations are edited in place** (they are not on `main`). After pulling, everyone runs `./vendor/bin/sail artisan migrate:fresh`.

Files owned by this package (no other package edits them):

- `../../database/migrations/2026_10_01_144037_remove_subject_category_table.php`
- `../../app/Models/Subject.php`
- `../../app/Models/SubjectCategory.php` (to delete)

# Problems encountered

- [ ] **M4 — `Subject` fillable is one refactor behind.** `#[Fillable(['domain_id', 'subject_category_id'])]`: `subject_category_id` was dropped by `2026_10_01_144037`, and `name` (now NOT NULL) is missing, so `Subject::create()` cannot produce a valid row.
- [ ] **M4 — `Subject` docblock** lists `subject_category_id` and omits `name` and `domain_id`.
- [ ] **M4 — `Subject::subjectCategory()`** points at a table that no longer exists.
- [ ] **M5 — `SubjectCategory` model has no table.** `subject_category` is dropped by the same migration.
- [ ] **G3 — The migration fails on a database that has subjects.** It adds `name` (NOT NULL, no default) and `domain_id` (NOT NULL) with no backfill. Same class of problem as review comment 2, one step earlier in the chain.
- [ ] **G3 — `down()` has the same problem in reverse:** it re-adds `subject_category_id` NOT NULL on a table that may have rows.
- [ ] **`subjects.domain_id` has no delete rule stated.** It uses the default (NO ACTION). Fine if intended, but undocumented.

# Fixes suggested

1. **`Subject.php`.** Fillable → `['name', 'domain_id']`. Docblock → `@property int $id`, `@property string $name`, `@property int $domain_id`. Delete `subjectCategory()` and its import. Keep `domain()` and `grades()`.
2. **Delete `SubjectCategory.php`.** Its remaining users are `Subject` (fixed above) and `../../database/seeders/EvaluationTreeSeeder.php`, which belongs to package 6: tell that owner the class is gone.
3. **G3.** Do not write a backfill: the earlier migration `2026_10_01_112517` already drops every tree link, so the old data cannot be carried over anyway. Add a comment at the top of `up()` stating that this chain is for a fresh database, and ask package 8 (Docs) to record it in `../db/db.md`.
4. **Delete rule — moved to package 10.** If the subject ↔ domain pivot is accepted (decision D2), `subjects.domain_id` is dropped and this fix disappears. If it is refused, the rule goes in migration R of package 10, as a new migration. The rule itself: `->constrained('domains')->restrictOnDelete()`, consistent with the "history-bearing foreign keys restrict" rule in `../adr/ADR.md`, since grades reference subjects.

Check when done: `migrate:fresh` passes, and `Subject::create(['name' => 'x', 'domain_id' => $id])` works in tinker.
