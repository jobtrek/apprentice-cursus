These are comment left during code review. For each issue below, determine whether it is valid and should be fixed. If so, propose a fix plan and validate with user

---

Path: database/migrations/2026_10_07_090000_drop_subject_id_and_aggregation_from_domains_table.php
Line: 26

Comment:
**Migration cannot run again**

`down()` restores the `subject_id` foreign key as `domains_subject_id_foreign`, but `up()` only drops `evaluation_nodes_subject_id_foreign`. Rolling back this migration and applying it again therefore fails, even on an empty database. Restore the original constraint name explicitly in `down()`.

---

Path: database/migrations/2026_10_07_140000_rework_grades_table.php
Line: 19-21

Comment:
**Saved grades block migration**

`up()` adds required `subject_id` and `apprenticeship_period_id` columns without filling them for existing grades. PostgreSQL rejects these additions when `grades` contains rows, stopping the upgrade. Create and assign the matching subjects and periods before making the columns required, then remove `semester`.

---

Path: database/migrations/2026_10_01_112517_restructure_evaluation_nodes_connections_table.php
Line: 16

Comment:
**Stacked models query missing tables**

The new `domain_links` and `domain_link_weights` tables do not match the models in stacked PR #198. `app/Models/DomainNode.php` still queries `domain_nodes`, and `app/Models/DomainEdge.php` still queries `domain_edges` with `domain_node_id`. Those queries will fail against this schema. Coordinate the table and foreign-key names across the stack.

---

Path: database/migrations/2026_10_07_140000_rework_grades_table.php
Line: 20

Comment:
**Period links use different names**

The new `apprenticeship_period_id` column does not match stacked PR #198. Its `app/Models/Grade.php` saves `apprenticeship_periods_id`, and `app/Models/ApprenticeshipPeriod.php::grades()` queries that plural column. Saving or loading grades through those models will fail because that column does not exist. Align both PRs on one name.

---

Path: database/migrations/2026_10_01_112517_restructure_evaluation_nodes_connections_table.php
Line: 18-20

Comment:
**Domains can link to themselves**

Replacing `evaluation_node_connections` removes its check that a domain cannot link to itself. The new unique constraint still permits `(parent_id, child_id)` values such as `(5, 5)`. Restore the `parent_id <> child_id` check on `domain_links` so direct inserts cannot create this invalid link.

---

Path: database/migrations/2026_10_01_112517_restructure_evaluation_nodes_connections_table.php
Line: 20

Comment:
**Child lookups lose their index**

`domain_links` loses the old table's separate `child_id` index. The unique index on `(parent_id, child_id)` supports parent lookups, but not child-only lookups or checks when deleting a child domain. Keep an index on `child_id` to avoid scanning every link for those operations.

---

Path: database/migrations/2026_10_07_140000_rework_grades_table.php
Line: 33

Comment:
**Rollback allows invalid semesters**

`down()` restores `semester` but not the original `grades_semester_check`. After rollback, the database accepts semesters outside `1–8`, unlike the schema before this PR. Restore that check when adding the column back.

---

Path: docs/db/db.md
Line: 9

Comment:
**Docs show a different schema**

`apprenticeship_periods` is described as not migrated even though this PR creates it. In `docs/db/schemas/mcd_current.d2`, the renamed `domain_links` drawing also omits its `id` and shows the parent/child pair as the primary key. That leaves `domain_link_weights.domain_link_id` without its actual target. Update both descriptions so teammates can follow the schema correctly.
