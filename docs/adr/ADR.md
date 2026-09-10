# Architecture Decision Log

## 2026-09-03 — Matura (MP) variant stays in one evaluation tree

**Decision:** The Matura/MP grading variant is not a separate tree — it's modeled as sibling nodes (`B` vs `E`) inside the same `evaluation_nodes` tree, flagged per node, with the apprentice's MP status deciding which sibling counts.

**Why:** MP only changes one branch's weights (Note d'expérience), while the rest of the tree (Travail pratique, Connaissances professionnelles) is identical — duplicating a whole second tree would just copy those shared branches for no reason.



the table for the weight of each grade inside of EC's program. MP = Maturité
┌───────────────────────────────────────────────────────┬──────────────┬─────────────────┐
│                                                       │ Standard (B) │ MP variant (E)  │
├───────────────────────────────────────────────────────┼──────────────┼─────────────────┤
│ Enseignement des connaissances pro + culture générale │ 50%          │ (doesn't exist) │
├───────────────────────────────────────────────────────┼──────────────┼─────────────────┤
│ Cours interentreprises                                │ 25%          │ 50%             │
├───────────────────────────────────────────────────────┼──────────────┼─────────────────┤
│ Formation à la pratique professionnelle               │ 25%          │ 50%             │
└───────────────────────────────────────────────────────┴──────────────┴─────────────────┘


## 2026-09-09 — Migrations & models derived from the MCD

**Decision:** `db_schema/mcd.mmd` is turned into Laravel migrations and Eloquent models. Where the diagram was incomplete or ambiguous, the entries below record what was chosen instead.

### Users: extended, not replaced

**Decision:** The starter-kit `users` migration stays untouched; a second migration (`add_sso_columns_to_users_table`) adds the MCD's columns on top.

**Why:** SSO is not wired up yet, so local Fortify auth still has to work — and its `password` / two-factor columns are absent from the MCD.

### `apprenticeships` table added, `apprenticeship_name` dropped

**Decision:** `users.apprenticeship_id` points at a new `apprenticeships` table (`id`, `name`, timestamps). The MCD's `apprenticeship_name` enum column is not implemented, and the `ApprenticeshipName` PHP enum is removed.

**Why:** The MCD carried both a `IT | EC` enum and an FK to a table it never defined — two representations of the same thing, free to disagree with no constraint able to catch it. The FK wins: adding a track becomes an insert rather than a migration.

### Timestamps follow the MCD table by table

**Decision:** Not normalized to Laravel's `timestamps()`. `created_at` only on `subjects`, `evaluation_nodes`, `evaluation_node_connections`, `skills`; none on `subject_category`, `project_skill`; both everywhere else. The `created_at`-only columns carry a `useCurrent()` DB default.

**Why:** The MCD is deliberately inconsistent here. The DB default means a raw insert or a bare `attach()` still gets a timestamp, rather than relying on every call site remembering to set one.

### `project_skill` is a plain pivot, no model

**Decision:** Composite primary key (`project_id`, `skill_id`), reached through `belongsToMany()`. No Eloquent model.

**Why:** The MCD gives it no `id` column, so there is no pivot payload to model.

### Comments are polymorphic, behind an enforced morph map

**Decision:** `commentable_type` / `commentable_id` map to Eloquent's `morphTo()` / `morphMany()`. `AppServiceProvider` registers a morph map in `enforce` mode, so the column stores `grade` / `project` rather than class paths.

**Why:** Keeps the stored value stable if a model is renamed or moved. `enforce` mode means a morph relation added later fails loudly instead of silently writing a class path — the map has to be kept up to date.

### `evaluation_nodes` uses the Composite pattern

**Decision:** One `EvaluationNode` model is both leaf (`aggregation = null`, carries grades) and composite (`aggregation` set, combines children). Children and parents are read through `evaluation_node_connections`, which has its own model because it carries an `id` and a `weight`. `isLeaf()` is exactly `aggregation === null`.

**Why:** `addChild()` refuses to attach a child to a node with no aggregation strategy, so "has no aggregation" and "has no children" can never contradict each other — which is what lets `isLeaf()` answer without a query, and keeps tree walks free of N+1.

### Graph invariants live in `addChild()`, not the schema

**Decision:** `EvaluationNode::addChild()` rejects a self-attachment and any edge that would close a cycle (it walks down from the prospective child looking for the parent). A `parent_id <> child_id` CHECK backs up the trivial case in the database.

**Why:** The MCD calls the graph a DAG, but a relational schema cannot express "no cycles" — only the degenerate self-loop. A cycle would make any recursive aggregation walk loop forever, so the guard has to exist somewhere; `addChild()` is the single writing path.

### `aggregation` has one value: `weighted_average`

**Decision:** The MCD describes the column but never lists its values. One mode for now.

**Why:** Every parent→child edge already carries an explicit `weight`, which covers everything the current schema can express. More modes can be added later without a migration.

### Enum columns are `string` + PHP backed enum + CHECK

**Decision:** `role`, `aggregation`, `period_scope` and `variant` are `string` columns with a `CHECK (col IN (...))` constraint, a PHP enum in `app/Enums`, and an Eloquent cast.

**Why:** `$table->enum()` does not produce a native `ENUM` on PostgreSQL — it produces exactly this varchar + CHECK, but hidden behind an API that suggests otherwise and that `->change()` cannot edit cleanly. Writing it out makes the constraint visible and editable, and adding a role becomes a one-line `ALTER`. The PHP enum and cast give the type safety in code.

### `subjects` keeps no `name` and no `is_active`

**Decision:** `id`, `subject_category_id`, `created_at` only, exactly as the MCD defines it.

**Why:** `subjects` is a thin join row linking an `evaluation_node` to its `subject_category`; the displayed name is `subject_category.name`, reached through `subject->subjectCategory->name`. A second `name` would store it twice. Rows are seeded rather than managed through an admin UI, so there is no catalog flow needing `is_active`.

### History-bearing foreign keys restrict instead of cascading

**Decision:** `grades.user_id`, `grades.evaluation_node_id`, `evaluation_results.user_id`, `evaluation_results.evaluation_node_id`, `projects.user_id` and `comments.author_id` are `restrictOnDelete()`. `users` carries an `is_active` flag.

**Why:** The user stories are explicit that apprentices, coaches, trainers and admins are *deactivated*, never deleted, and that grade history stays archived (`docs/user_stories/user_story_final.md`, `docs/project-docs/role_permissions.md`). A cascade would silently wipe someone's academic record. `is_active` is the deactivation mechanism; `restrictOnDelete()` is the schema-level backstop that makes an accidental hard delete impossible rather than merely discouraged.

### Comments are cleaned up in the application layer

**Decision:** `Grade` and `Project` each register a `deleting` model event that removes their comments first, and override `delete()` to wrap the pair in a transaction.

**Why:** A polymorphic reference cannot have a real foreign key — there is no single table it points at — so deleting a `Grade` row directly would orphan its comments with no constraint able to catch it. The transaction is what makes the two deletes atomic: without it, a failure on the parent's own DELETE leaves the comments already gone. The hook only fires on Eloquent deletes, so bulk deletes (`DB::table(...)->delete()`, `Grade::where(...)->delete()`) must clean up comments themselves.

### Value ranges are CHECK constraints where PostgreSQL allows it

**Decision:** `grades.value` and `evaluation_results.rounded_value` are constrained to 1.0–6.0, `grades.semester` to 1–8, `evaluation_results.semester` to 0–8, `comments.body` to 2000 characters. `evaluation_results.semester` uses sentinel `0` for `cursus`-scoped rows.

**Why:** The MCD specifies these ranges, and a CHECK holds even against a raw `DB::table()->insert()` that bypasses every FormRequest. The sentinel keeps the `(user_id, evaluation_node_id, semester)` unique index null-safe so recompute upserts update in place. The MCD's `test_date <= today` is *not* implemented: `CURRENT_DATE` is `STABLE`, not `IMMUTABLE`, and PostgreSQL rejects it in a CHECK — that rule belongs to the FormRequest.

### Foreign keys are indexed explicitly

**Decision:** Every foreign key not already covered by the leading column of a unique index or primary key gets an explicit `index()`.

**Why:** PostgreSQL does not index foreign keys automatically the way MySQL/InnoDB does. Without them, the hot read paths and every `restrictOnDelete` / `nullOnDelete` check would seq scan the child table.

### These migrations target PostgreSQL

**Decision:** The `DB::statement()` CHECK constraints above make the migrations PostgreSQL-specific.

**Why:** `compose.yaml` and `.env.example` already commit the project to PostgreSQL. The `sqlite` fallback left in `config/database.php` is the framework default, not a supported target.
