# Purpose

This directory store a fixes plan, organized and stored to be read and splitted. You don't forcefully need to read all documentation under, only package of fixe you're working. 

# Order of the packages

Packages 1 and 4 have no migration left, and package 2 only repairs `down()` methods (see "Not movable to a new migration"). For the rest:

1. **Decisions** D1 to D5 are taken and **package 10** is written (`./10-remaining-migrations.md`). Only D6 (`period_scope`) is open; package 6 answers it.
2. **Package 5**, then **6**, then **7**: app code only.
3. **Package 8**: docs, last. Package 9 keeps the reasons and the model changes of the subject ↔ domain pivot.

# Migration grouping

Rule: when fixes need a migration, make as few migrations as possible. Before creating one, look at the table below and merge with the fixes that fit together.

- **Group by kind of change first.** Index additions go together in one migration, even across tables and packages (as `2026_10_07_160000` does). Same for CHECK constraints on one table, or for several drops on one table.
- **Otherwise group by table.** Two changes on the same table that land at the same time share one migration.
- **Keep apart** what depends on a decision not taken yet, and what must run after another migration (for example a foreign key to a table that does not exist yet).
- **A migration already committed and run by teammates is not reopened**: add to it only while it is still local.
- **Repairs of a `down()`** cannot be moved to a new migration: they live in the file they repair. They are listed apart below.

## Already done

| Migration | Groups | Package |
|---|---|---|
| `2026_10_07_160000_add_self_loop_check_and_foreign_key_indexes_to_domain_tables` | self-loop CHECK on `domain_links` + indexes on `domain_links.child_id`, `domain_link_weights.domain_link_id`, `apprenticeship_contexts.root_domain_id` | 1 |
| `2026_10_07_170000_cascade_domain_link_deletes` | cascade on three foreign keys (`domain_links` ×2, `domain_link_weights`) | 1 |
| `2026_10_07_180000_add_weight_range_check_to_domain_link_weights_table` | weight CHECK + drop of the default | 1 |
| `2026_10_07_190000_rename_apprentice_id_to_user_id_on_apprenticeship_periods_table` | column, foreign key and index rename | 4 |
| `2026_10_07_200000_add_indexes_to_grades_table` | indexes on `grades.user_id`, `grades.subject_id`, `grades.apprenticeship_period_id` (G1, G2) | 4 |
| `2026_10_07_210000_add_checks_to_apprenticeship_periods_table` | `semester BETWEEN 1 AND 8` + `end_date >= start_date` (G5) | 4 |
| `2026_10_07_220000_index_context_and_drop_is_mp_on_users_table` | index on `users.apprenticeship_context_id` (G2) + drop of `users.is_mp` | 10 (from 5) |
| `2026_10_07_230000_create_domain_subject_table` | `domain_subject` pivot + data copy + drop of `subjects.domain_id` + grade guard and its index on `grades` | 10 (from 9) |

## Remaining, grouped

No migration is planned at the moment. Two things could still add one, both tracked in package 10 (`./10-remaining-migrations.md`):

- **D6 — a replacement for `period_scope`**, if the front end turns out to need it (package 6 fix 7).
- **Explicit delete rules** on the three NO ACTION foreign keys: decided against (D5), listed here so it is not proposed again.

## Not movable to a new migration

These repair the `down()` of an existing file, so they can only be done in that file:

| File | Fixes | Package |
|---|---|---|
| `2026_10_07_090000_drop_subject_id_and_aggregation_from_domains_table` | Comment 1 and two G6 items | 2 |
| `2026_10_07_100000_drop_evaluation_node_id_from_apprenticeships_table` | G6 (index not restored) | 2 |
| `2026_10_07_140000_rework_grades_table` | Comment 7 and G6 (done) | 4 |

Do them together, in one commit, if the exception to "migrations are not edited in place" is accepted.
