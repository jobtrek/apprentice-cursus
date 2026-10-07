# Remaining migrations

This file is the context for writing every migration that is still missing. It gathers the schema changes that were spread across packages 3, 5, 7 and 9, so that they land in as few migrations as possible.

- **Depends on:** the decisions listed below. Each migration waits only for its own decisions.
- **Blocks:** package 5 (only for `users.is_mp`), package 6 (seeder attaches subjects through the pivot), package 7 (grade guard, MP read), package 8 (Docs).
- **Order of the remaining work:** decisions → this package → 5 → 6 → 7 → 8. The model fixes of package 3 can run in parallel with this package.
- **Migrations are NOT edited in place.** Everything here is a new migration (rules in `./CONTEXT.md`). After pulling, everyone runs `./vendor/bin/sail artisan migrate`.
- **Packages 5 to 9 write no migration any more.** They keep the app code, the models and the docs, and point here for the schema.

Files owned by this package (no other package edits them):

- `../../database/migrations/2026_10_07_220000_*` and later (timestamps to adjust to the next free one)

# State of the schema (audited 2026-10-07)

- **One foreign key has no index:** `users.apprenticeship_context_id`. Every other foreign key is covered.
- **Four foreign keys have no stated delete rule** (PostgreSQL default, NO ACTION): `subjects.domain_id`, `apprenticeship_contexts.apprenticeship_id`, `apprenticeship_contexts.root_domain_id`, `domain_link_weights.apprenticeship_context_id`.
- **`users.is_mp` and `apprenticeship_contexts.is_mp` both exist.**
- **A subject belongs to exactly one domain** (`subjects.domain_id`), so a module shared by two formations cannot be modelled.

# Decisions to take first

- [ ] **D1 — Where does MP live?** Drop `users.is_mp` or keep it. Recommendation: drop it, the context already carries it. Came from package 5; packages 6, 7 and 8 wait on it. Gates the `is_mp` part of migration U.
- [ ] **D2 — Is the subject ↔ domain many-to-many accepted?** See `./9-subject-domain-many-to-many.md` for the reasons. Gates migration S.
- [ ] **D3 — Delete rule on the pivot.** Recommendation: `cascadeOnDelete()` on both foreign keys. Gates migration S.
- [ ] **D4 — Is the grade guard accepted?** Composite foreign key `grades (domain_id, subject_id)` → `domain_subject`. Recommendation: yes. Gates the last part of migration S.
- [ ] **D5 — Should the NO ACTION foreign keys get an explicit rule?** NO ACTION already refuses the delete, so this only states the intent. Gates migration R.
- [ ] **D6 — Does `period_scope` need a replacement column?** Raised by package 6 fix 7: the column was dropped from `domains` and the front end may still depend on it. If yes, it is one more migration, to plan here.
- **No migration, listed for the order only:** how a grade picks its period (packages 4 and 7). It blocks package 7, not this package.

# Migrations to write

## U — `users` (index, and `is_mp` if D1 says drop)

- [ ] `$table->index('apprenticeship_context_id');` (G2 of package 5). No decision needed: it can be written now.
- [ ] `$table->dropColumn('is_mp');` if D1 says drop. `down()` restores it as `2026_09_09_070032` created it (`boolean`, nullable).

Both changes are on `users`, so they share one migration **if D1 is taken before U is committed**. Otherwise U lands with the index alone and the drop becomes a migration of its own.

## S — `domain_subject` (needs D2, D3; D4 for the last step)

One migration, in this order (details in package 9, fixes 1 and 2):

- [ ] Create `domain_subject` (`domain_id`, `subject_id`, primary key on the pair, delete rule from D3) and `$table->index('subject_id')`.
- [ ] Copy `subjects.domain_id` into `domain_subject`, then drop `subjects.domain_id` and `subjects_domain_id_index`.
- [ ] If D4 is accepted, at the end: the composite foreign key `grades (domain_id, subject_id)` → `domain_subject (domain_id, subject_id)`, and `$table->index(['domain_id', 'subject_id'])` on `grades` so that removing a pivot row does not scan `grades`.
- [ ] `down()` undoes the three steps in reverse. It only works while every subject has a single domain: say so in a comment.

This replaces package 3 fix 4 (delete rule on `subjects.domain_id`): the column is dropped.

## R — explicit delete rules (only if D5 says yes)

- [ ] `restrictOnDelete()` on `apprenticeship_contexts.apprenticeship_id`, `apprenticeship_contexts.root_domain_id` and `domain_link_weights.apprenticeship_context_id`.
- [ ] If D2 is refused, `subjects.domain_id` joins this migration (package 3 fix 4).

# What the other packages do once these land

| Migration | Package | What follows |
|---|---|---|
| U (`is_mp` dropped) | 5 | nothing to declare on `User` for `is_mp` (M6 closes itself) |
| U (`is_mp` dropped) | 6, 7 | `GradebookTree` and `HomeController` read `apprenticeshipContext->is_mp` |
| S | 3, 9 | `Subject` and `Domain` relations become `BelongsToMany`; `domain_id` leaves the `Subject` fillable |
| S | 6 | the seeder attaches subjects through the pivot |
| S (guard) | 7 | `GradeController` validates that the subject is attached to the domain, so the user gets a form error instead of a database error |
| U, S, R | 8 | `db.md` and the `.d2` describe the new index, the pivot, the guard and the dropped columns |

Check when done: `migrate:fresh` passes; each new migration survives `migrate:rollback --step=1` then `migrate`; no foreign key is left without an index; `\d users`, `\d domain_subject`, `\d subjects` and `\d grades` match the decisions taken.
