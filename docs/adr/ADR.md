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

## 2026-09-24 — Inertia props go through an API Resource

**Decision:** Portfolio projects are sent to the pages as `ProjectResource::collection(...)->resolve()` / `(new ProjectResource(...))->resolve()`, never as raw models. The resource is the single mapping to the `PortfolioProject` type in `resources/js/types/portfolio.ts`. `->resolve()` drops the `{ data: ... }` wrapper, since Inertia props are not a JSON API response.

**Why:** Passing the model straight to `Inertia::render()` serializes it with `toArray()`, which leaks columns the page must not see (`user_id`, timestamps, the private storage `path` of each screenshot) and emits the wrong shapes: dates as full ISO timestamps where `<input type="date">` needs `Y-m-d`, skills as full objects with pivot data where the form needs `skill_ids`, and screenshots as rows where the page needs an authorized `url`. The same shape is needed by `index`, `preview` and `edit`, so mapping inline in each controller method would triplicate it. Model-level `$hidden` / `date:` casts / appended accessors were rejected: they apply to every serialization of the model app-wide and would split the page contract across `Project` and `ProjectScreenshot`.

## 2026-09-30 — Roles and permissions

**Decision:** Entra ID groups are the only source of a user's role and apprenticeship, and the Spatie role is the only place the role is stored. A migration creates the roles and permissions from `Permission::byRole()`, backfills `model_has_roles` from `users.role`, then drops `users.role`. `User::role` is a read-only accessor that derives a `UserRole` from the Spatie role; there is no `saved` hook, and writers (SSO sync, seeders) call `syncRoles()`. `RolesAndPermissionsSeeder` re-syncs roles and permissions idempotently through the same `RolesAndPermissions::sync()`. Code checks `App\Enums\Permission` values (policies, routes), never role names; `Permission::byRole()` holds the role to permission matrix from `role_permissions.md`.

**Why:** A single source avoids role drift between Entra, a column and the Spatie tables, and permission-based checks let the matrix change without touching policies. Creating roles in a migration means a migrated database is always usable, even without seeding. `role_has_permissions.role_id` is indexed explicitly because PostgreSQL does not index foreign keys.

### Role and track are not mass-assignable

**Decision:** Only self-service profile fields are fillable on `User`. `is_active`, `is_mp`, `apprenticeship_id`, `coach_id` and `trainer_id` are set explicitly via `forceFill` by the flow that owns them (SSO sync, administration); the role is only changed through `syncRoles()`.

**Why:** These fields decide what a user may do or who they are attached to; they must never be filled from a request payload.

### SSO identity and group mapping

**Decision:** A user is matched on `azure_id` only. An existing account with the same email but no `azure_id` is refused, not adopted. The `AzureGroup` enum lists the mapped Entra groups; its backing value is the key under `services.azure.groups` that holds the Graph group object id, and it knows its role and apprenticeship. `MappingRolesService::resolveGroup` returns the single mapped `AzureGroup`, or null when the account is in no mapped group or in more than one (an administrator fixes the conflict in Entra), and throws when Graph fails. A new `User` sets `is_active = true` explicitly. Pre-provisioning accounts before their first login is out of scope.

**Why:** An email is not proof of identity, so adopting by email allows account takeover. Overlapping groups make the role ambiguous. The DB default is not loaded on an unsaved model, so without the explicit value the `is_active` check sees null.

### One shared account sync

**Decision:** `AzureAccountSync` is the single implementation of "keep the local user in line with Entra", used by `MicrosoftLoginService` and by `EnsureAzureAccountIsActive`. `check()` asks Graph whether the account is enabled and which group it is in; `apply()` sets the role (`syncRoles`), the apprenticeship and `is_active = true`; `deactivate()` sets `is_active = false`. There is one Graph client, `MicrosoftGraphService`. A successful login primes the `azure-account-check:{id}` cache key, so the middleware does not repeat the check straight after login.

**Why:** Login and re-check used to hold two copies of the mapping logic and two Graph clients that could diverge.

### Deactivation and reactivation

**Decision:** Access is revoked when the account has no mapped group, several mapped groups, is disabled in Entra, or returns 404 there. Revoking sets `is_active = false` and ends the session (middleware) or refuses the login. A later login with a valid single-group mapping sets `is_active = true` again. A refused new user gets no `users` row. Both happen at login and in the re-check middleware only; there is no command or scheduler.

**Why:** Users are never deleted (history-bearing foreign keys), so the flag is the record of revoked access, and it must be reversible without an administrator when Entra is fixed. A scheduled job would add infrastructure for a state that the next request already detects.

### Trainers are mapped to the IT apprenticeship

**Decision:** The `trainer` group maps to the IT apprenticeship.

**Why:** `User::supervises()` needs a section to match apprentices against, and all trainers are IT until an EC trainer group exists.

### Groups map to apprenticeships by seeded name

**Decision:** `apprenticeships.code` is not restored. `AzureGroup::apprenticeship()` returns the seeded display name through the `ApprenticeshipSeeder::IT` and `::EC` constants, so the string lives once in code. If the apprenticeship is not seeded, the login is refused, and the middleware logs an error and fails open.

**Why:** A second identifier column for two rows adds a migration and a value to keep in sync for no gain; the constants give one place to change the name.

### Apprenticeship kept on section change

**Decision:** When the Entra group maps a user to a different apprenticeship than their current one, the current one is kept and a warning is logged, both at login and in the middleware. The user is not logged out.

**Why:** A section change needs the apprentice's confirmation before grades move (user story); that flow does not exist yet.

### Supervision rule

**Decision:** `User::supervises($apprentice)` requires the target to hold the apprentice role and is never true for oneself. For a coach it is true when the apprentice's `coach_id` is theirs (self-assignment system, EC and IT); for a trainer, when the apprentice is in the trainer's own apprenticeship section. It backs the "supervised" permissions (`UserPolicy::view`, `ProjectPolicy::view`, `GradePolicy::view` and `comment`). The apprentices list page still lists every apprentice (static page for now); access to a given apprentice goes through the policy.

**Why:** Supervisors get read-only access to the apprentices they follow (`role_permissions.md`, Training Portfolio); only the apprentice adds, edits or deletes their own projects. Without the role check a coach or trainer could be "supervised" by another supervisor.

### Coaches: self-assign permission without a route

**Decision:** `Permission::CoachingAssignSelf` stays granted to coaches although no route uses it yet.

**Why:** The permission belongs to the matrix (`role_permissions.md`); the assignment route and UI come with the coaching feature, and coaches only see apprentices whose `coach_id` is theirs until then.

### Commenting rules

**Decision:** Commenting on a grade requires `grades.comment`, supervision of the apprentice, and an active apprentice (`GradePolicy::comment`). `CommentPolicy` lets only the author update or delete a comment, and only while the apprentice it is attached to is active.

**Why:** A deactivated apprentice's record is read-only for supervisors, and no one can edit another person's comment.

### Landing page after login

**Decision:** `User::homeRoute()` picks the landing route from permissions: `grades.view-own` goes to `grades.dashboard`, `apprentices.view-list` to `apprentisdashboard`, otherwise `home`. The SSO callback and the local password login both use it.

**Why:** Keying on permissions rather than role names means a new role only needs permissions.

### Password login is local only

**Decision:** `POST /login` is registered only when `app()->environment('local')`. The login page shows the password form only when the server passes its URL (`passwordLoginUrl`). Local seeding creates a coach `admin@example.com` and an IT trainer `trainer@example.com`, both with the password `password`. Everywhere else, Microsoft SSO is the only way in.

**Why:** Credentials live in Entra; a password path in production would bypass group-based access control and deactivation. It stays as a testing tool because SSO needs a real tenant.

### Demo data only in local

**Decision:** `App\Support\Demo\DemoGrade` is merged into the `GradeDetails` props only in the local environment; elsewhere the props are `pdfUrl: null` and `comments: []`. `DemoApprenticeSeeder`, the Test User, `UserSeeder` and the demo `coach_id` update run only in local.

**Why:** Grade files and comments are not served from the database yet, and demo people and passwords must never exist in a real environment.

### Screenshot route sits outside `portfolio.manage-own`

**Decision:** `portfolio.screenshots.show` is not behind the `portfolio.manage-own` permission; `ProjectPolicy::view` authorizes it.

**Why:** Supervisors load screenshots too, and they do not hold the manage permission.

### Account re-validation fails open

**Decision:** `EnsureAzureAccountIsActive` checks `is_active` on every request for all users, outside the cache. For SSO users it re-checks account status and group-derived role through `AzureAccountSync` at most once per `account_check_interval`. If Graph is unreachable (`AzureAccountSync::check()` throws a `RuntimeException`), the request proceeds and the check is retried after a back-off (`AzureAccountSync::BACKOFF_SECONDS`, 60 s). A disabled or unmapped account is deactivated and its session ended. At login, a Graph failure refuses the login instead.

**Why:** Failing closed would log out every SSO user during a Microsoft outage, whereas at login there is no session to protect and no known state to fall back on. The back-off avoids calling a failing Graph on every request. The local deactivation flag is cheap, so it is never cached.

### Users have no timestamps

**Decision:** `User::$timestamps = false`.

**Why:** The columns were dropped (`2026_09_17_083508_drop_default_columns_from_users_table`); Azure SSO is the sole write path and does not need them.
