# Purpose

This directory store a fixes plan, organized and stored to be read and splitted. You don't forcefully need to read all documentation under, only package of fixe you're working. 

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

## Remaining, grouped

| Planned migration | Fixes that go in it | Packages | Note |
|---|---|---|---|
| **A. Missing index on `users`** | `users.apprenticeship_context_id` index (G2) | 5 | Can join `2026_10_07_200000_add_indexes_to_grades_table` only while that file is not committed (rename it if it covers both tables); otherwise a migration of its own. |
| **C. Subject ↔ domain many-to-many** | create `domain_subject` + copy data + drop `subjects.domain_id` | 9 (+ 3) | Makes package 3 fix 4 (delete rule on `subjects.domain_id`) pointless: the column is dropped. Do not write a migration for that fix if package 9 is accepted. |
| **D. Grade guard** | composite foreign key `grades (domain_id, subject_id)` → `domain_subject` | 9 + 4 | Only if accepted. Must run after C, so it goes in the same migration as C, at the end, not in A. |
| **E. Drop `users.is_mp`** | drop the column | 5 | Waits for the "where does MP live" decision. If it is decided before A is committed, it can not join A (different kind of change): it is a migration on `users` of its own. |

## Not movable to a new migration

These repair the `down()` of an existing file, so they can only be done in that file:

| File | Fixes | Package |
|---|---|---|
| `2026_10_07_090000_drop_subject_id_and_aggregation_from_domains_table` | Comment 1 and two G6 items | 2 |
| `2026_10_07_100000_drop_evaluation_node_id_from_apprenticeships_table` | G6 (index not restored) | 2 |
| `2026_10_07_140000_rework_grades_table` | Comment 7 and G6 (done) | 4 |

Do them together, in one commit, if the exception to "migrations are not edited in place" is accepted.
