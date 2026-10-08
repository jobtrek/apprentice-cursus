# Add many-to-many between subjects and domains

This file is the context for solving the issues below in the package of issue Subject ↔ domain many-to-many.

- **Depends on:** package 3 (Subjects), which owns `Subject.php` and fixes its fillable and docblock first.
- **Blocks:** package 6 (Tree app code), whose seeder must attach subjects to domains through the new table.
- **Touches package 4 (Grades and periods)** if the grade guard below is accepted: `grades` is owned by that package.
- **Names proposed:** table `domain_subject` (Laravel pivot convention), relations `Domain::subjects()` and `Subject::domains()`.
- **The migration is written under package 10** (`./10-remaining-migrations.md`, migration S), as a new migration. This file keeps the reasons, the specification of that migration (fixes 1 and 2) and the model and doc changes (fixes 3 to 5).
- **One migration** creates the pivot, copies the data and drops `subjects.domain_id`. The grade guard, if accepted, goes at the end of that same migration, because it needs `domain_subject` to exist.

Files concerned:

- a new migration creating `domain_subject` (owned by package 10, migration S)
- `../../app/Models/Subject.php` (owned by package 3)
- `../../app/Models/Domain.php` (owned by package 1)
- `../db/db.md`
- `./3-subjects.md` and `./6-tree-app-code.md` (wording to update, see below)

# Why

Some formations (CFC) share the same module. For example, the developer CFC and the infrastructure CFC have modules in common. A module is a **subject** in our model, and each formation has its own grade tree made of **domains**.

Today a subject belongs to exactly one domain: `subjects.domain_id` is a single NOT NULL column. So a shared module cannot be attached to the developer tree and to the infrastructure tree at the same time. The only way to model it is to duplicate the subject row once per formation, which gives two unrelated rows for the same real module (two names to keep in sync, two ids in grades, no way to know they are the same module).

# What should be achieved

- One subject row can be attached to several domains, and a domain still has several subjects.
- A shared module exists once in `subjects` and is linked to the right leaf domain of each formation's tree.
- A grade still counts for exactly one domain: the one in the apprentice's own tree.

# Problems encountered

- [x] **The schema cannot express a shared subject.** `subjects.domain_id` is a single foreign key (`2026_10_01_144037`), with no pivot table between `subjects` and `domains`.
- [x] **The models are one-to-many.** `Subject::domain()` is a `belongsTo`, `Domain::subjects()` is a `hasMany`.
- [x] **`Subject` fillable contains `domain_id`**, which will no longer exist.
- [x] **A grade's domain can no longer be derived from its subject.** Once a subject has several domains, `grades.subject_id` alone does not say which domain the grade counts for. `grades.domain_id` becomes the only source for it, and nothing checks that the subject is really attached to that domain.
- [ ] **Docs describe the one-to-many.** `../db/db.md` says subjects hang under leaf domains through `subjects.domain_id`; `./3-subjects.md` fixes 1 and 4 and its final check still rely on that column.

# Fixes suggested

1. **New migration, `up()`.**
    - Create `domain_subject` with `domain_id` → `domains` and `subject_id` → `subjects`.
    - Primary key `(domain_id, subject_id)`, so the same pair cannot be inserted twice.
    - Add `$table->index('subject_id')`: the primary key only covers lookups that start from the domain (PostgreSQL does not index foreign keys by itself).
    - Copy the existing `subjects.domain_id` values into `domain_subject`, then drop `subjects.domain_id` and its index `subjects_domain_id_index`.
2. **New migration, `down()`.** Re-add `subjects.domain_id`, fill it from `domain_subject`, drop `domain_subject`. This only works while every subject has a single domain: say so in a comment.
3. **`Subject.php`.** Replace `domain()` by `domains(): BelongsToMany` on `domain_subject`. Remove `domain_id` from the fillable and from the docblock.
4. **`Domain.php`.** `subjects()` becomes a `BelongsToMany` on `domain_subject`.
5. **Docs.**
    - `../db/db.md`: describe `domain_subject` and replace the "subjects hang under leaf domains" wording.
    - `./3-subjects.md`: fix 1 (fillable `['name']` only), fix 4 (the delete rule moves to the pivot), and the final check (`Subject::create(['name' => 'x'])` then `$subject->domains()->attach($id)`).
    - `./6-tree-app-code.md`: the seeder attaches each subject to its domain(s) through the pivot and creates a shared module only once.
    - Ask package 8 (Docs) to record the decision in `../adr/ADR.md`.

# Decisions to take before starting

Decided on 2026-10-07 (D2, D3 and D4 in `./10-remaining-migrations.md`): the pivot is accepted, both recommendations below are followed, and `2026_10_07_230000_create_domain_subject_table` implements them.

- **Delete rule on the pivot.** Recommendation: `cascadeOnDelete()` on both foreign keys. A pivot row has no meaning without both ends, and history stays protected because `grades.domain_id` and `grades.subject_id` are RESTRICT: a domain or a subject that has grades still cannot be deleted.
- **Guard grades against invalid pairs.** Recommendation: add a composite foreign key `grades (domain_id, subject_id)` → `domain_subject (domain_id, subject_id)`, so a grade can only use a subject that is attached to that domain. It also stops a pivot row from being removed while grades use it. It is written at the end of migration S (package 10), together with an index on `grades (domain_id, subject_id)`.

# Impact on the rest of the app

- No controller, resource or route reads subjects yet, so nothing else breaks in PHP.
- The grade form (`../../resources/js/components/grade/GradeForm.vue`) reads subjects from static JSON (`resources/js/data/normal.json`, `mp.json`), not from the database: no change needed for this package.
- `../../database/seeders/EvaluationTreeSeeder.php` and `../../tests/Feature/EvaluationTreeSeederTest.php` are already broken and belong to package 6.

Check when done: `migrate:fresh` passes, `\d domain_subject` shows the composite primary key and the `subject_id` index, `subjects` has no `domain_id` column, and in tinker one subject attached to two domains is returned by both `$domainA->subjects` and `$domainB->subjects`.
