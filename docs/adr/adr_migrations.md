# ADR: Migrations & Models vs the original MCD

**Date:** 2026-09-09

**Context:** Turned `db_schema/mcd.mmd` into Laravel migrations + models. A few things in the mcd were incomplete or ambiguous, so decisions had to be made. This lists every place the implementation differs from the diagram, in plain terms.

## 1. `users` table: added to, not replaced

**VALID**
The mcd's `users` table doesn't have `password`/Fortify fields, but the project already had a starter-kit `users` migration with them. We kept the existing migration as-is and added a second migration (`add_sso_columns_to_users_table`) that adds the mcd's columns (`azure_id`, `tenant_id`, `is_mp`, `role`, `apprenticeship_name`, `apprenticeship_id`, `coach_id`, `trainer_id`) on top.

**Why:** SSO isn't wired up yet, so local/Fortify auth still needs to work.


**UP TO DISCUSSION**
## 2. Added an `apprenticeships` table (not in the mcd)

`users.apprenticeship_id` points to "apprenticeships (track)" but that table was never defined in the mcd. We created a minimal one: `id`, `name`, timestamps.

**Why:** the FK needs something to point at. If the real shape of this table turns out different, this migration will need editing.

**VALID**
## 3. Timestamps follow the mcd exactly, table by table

The mcd is inconsistent about timestamps (some tables show only `created_at`, some show none, some show both). Instead of normalizing everything to Laravel's default `timestamps()`, we matched the mcd literally per table:

- `created_at` only: `subjects`, `evaluation_nodes`, `evaluation_node_connections`, `skills`
- neither: `subject_category`, `project_skill`
- both: everything else

**VALID**
## 4. `project_skill` is a plain pivot, no model

No `id` column was shown for `project_skill`, so it's a standard many-to-many pivot table (composite primary key `project_id` + `skill_id`), accessed through `belongsToMany()`. No dedicated Eloquent model.

**VALID**
## 5. `comments` uses Laravel's polymorphic relation with a morph map

`commentable_type` / `commentable_id` map to Eloquent's built-in `morphTo()`/`morphMany()`. A morph map is registered in `AppServiceProvider` so the `commentable_type` column stores short aliases (`grade`, `project`) instead of full class paths — keeps the DB safe if models get renamed/moved later.

**VALID**
## 6. `evaluation_nodes` + `evaluation_node_connections` use the Composite pattern

One `EvaluationNode` model represents both leaf nodes (a single grade source, `aggregation = null`) and composite nodes (nodes that combine children, e.g. "Final grade"). Children/parents are read through `evaluation_node_connections` (a proper pivot model, since it carries its own `id` and `weight`). `EvaluationNode::isLeaf()` just checks whether `aggregation` is null.

**VALID**
## 7. `aggregation` enum only has one value: `weighted_average`

The mcd describes this column ("how to combine children into a value") but never lists its possible values. Since each parent→child connection already carries an explicit `weight`, a single `weighted_average` mode covers everything the current schema supports. More modes (min/max/etc.) can be added later if a real use case shows up.


**VALID**
## 8. All enum columns are real DB enums + PHP backed enums

Every `enum` column in the mcd (`role`, `apprenticeship_name`, `aggregation`, `period_scope`, `variant`) is a native `ENUM` column in the migration, backed by a PHP enum class in `app/Enums` and cast on the model. Gives a DB-level constraint and type safety in code, at the cost of a migration if a value ever needs to change.

*explanation:* if I understand correctly, the enum type in the database ensures that whatever goes into those columns are exactly what is asked at the database level, and the php cast is basically to ensure we don't need to write 'apprentice' in raw string everywhere in the code so it ensures clean code.

**REVERTED.**
## 9. `subjects` stays as the mcd defined it — no `name` or `is_active` column

A code review previously flagged that `subjects` (`id`, `subject_category_id`, `created_at` only) had no name or active state, and `name` (required) + `is_active` (boolean, default `true`) were added to fix it. That's now reverted — the columns and the corresponding `Subject` model changes are removed.

**Why revert:** `subjects` is a thin join row that exists purely to link an `evaluation_node` to its `subject_category`; the category's own name (`subject_category.name`) is what's actually displayed, reached through `subject->subjectCategory->name`. Adding a second `name` on `subjects` duplicated that instead of reusing it. `subjects` rows are seeded, not managed through an admin UI, so there's no catalog-management flow that needs `is_active` either. Keeping the table exactly as the mcd defines it avoids storing the same name in two places for no reason.

**VALID**
## 10. `users` gained an `is_active` column, and history-bearing FKs no longer cascade-delete

`grades.user_id`, `grades.evaluation_node_id`, `evaluation_results.user_id`, and `evaluation_results.evaluation_node_id` were originally `cascadeOnDelete()`. That meant deleting a user or an evaluation node would silently wipe out someone's academic history. Changed to `restrictOnDelete()` — the database now refuses the delete outright if grades/results exist — and added `is_active` (boolean, defaults to `true`) to `users`.

**Why:** the user stories are explicit that apprentices, coaches, trainers, and admins are *deactivated*, never deleted, and that grade history must stay archived and accessible (`docs/user_stories/user_story_final.md`, `docs/project-docs/role_permissions.md`). Deletion cascades directly contradicted that. `is_active` gives the app a real deactivation mechanism instead of deleting the row; `restrictOnDelete()` is the schema-level backstop that makes an accidental hard delete of history impossible rather than just discouraged. Flagged in code review.


**VALID**
## 11. Comments can't orphan when their target is deleted

Two follow-on gaps from #10, both flagged in code review:

- `projects.user_id` and `comments.author_id` were still `cascadeOnDelete()`. Left alone, a user delete could cascade-remove a project or a comment out from under it while `restrictOnDelete()` protected grades but not these. Changed both to `restrictOnDelete()`, so a user with projects, or with authored comments, can't be hard-deleted either — same policy as #10, applied consistently.
- `comments.commentable_type` / `commentable_id` is a polymorphic reference, which **cannot** have a real foreign key in a relational schema — there's no single table it points at. So even with the FKs above fixed, directly deleting a `Grade` or `Project` row (not its owner, the row itself) would leave its comments pointing at nothing, with no DB constraint able to catch it. Fixed at the application layer: `Grade` and `Project` both register a `deleting` model event that deletes their `comments()` first.

**Why it's an app-layer fix, not a DB one:** this is the accepted trade-off of polymorphic relations — Laravel's own docs don't offer a DB-enforced version either. The `deleting` hook only fires on Eloquent deletes (`$model->delete()`, not a raw `DB::table(...)->delete()` or a mass `Model::where(...)->delete()`), so any future bulk-delete code for grades or projects needs to either go through Eloquent instances or explicitly clean up `comments` itself.
