# Database — current model

Source: `schemas/mcd_current.d2`

## 1. Training structure

- `apprenticeships`: formation track (e.g. IT / EC).
- `apprenticeship_contexts` (implemented in `2026_10_01_114802_create_apprenticeship_context_table.php`): one row per variant of an apprenticeship — `is_mp` boolean (MP track or not) + `root_domain_id` entry point into `domains`. Columns: `id`, `is_mp` (boolean, NOT NULL, no default), `apprenticeship_id` FK → `apprenticeships.id`, `root_domain_id` FK → `domains.id` (both `constrained()` with no cascade, i.e. NO ACTION: the database refuses to delete an apprenticeship or a root domain that still has contexts). No timestamps. A UNIQUE (`apprenticeship_id`, `is_mp`) constraint, added by `2026_10_07_110000_add_unique_to_apprenticeship_contexts_table.php`, enforces the “max 2 rows per apprenticeship (`is_mp` true/false)” rule in the database.
- `apprenticeship_periods`: planned, not yet migrated — stores the periods during which the apprenticeship takes place.
- `domains`: training domain blocks, each holds `subjects`.
- `domain_links`: parent → child links between domains (DAG).
- `domain_link_weights`: weight of a domain link for a given context. Primary key (`apprenticeship_context_id`, `domain_link_id`), `weight` is a `decimal(3,2)` fraction (0.01 to 1).
- `subject_category`: grouping for UI (CIE, modules, etc.).
- `subjects`: exam subject, belongs to one `domain_id` + one `subject_category_id`.

Flow: `apprenticeships` → `apprenticeship_contexts` → `domain_link_weights` (weighted) → `domain_links` → `domains`. `subjects` hang under leaf `domains`.

## 2. Users and grades

- `users`: apprentice account (Azure SSO via `azure_id`, email unique). Belongs to one apprenticeship context through `apprenticeship_context_id` (nullable FK → `apprenticeship_contexts.id`). The column is migrated: it replaced `users.apprenticeship_id`, was first created as `context_id`, then renamed by `2026_10_07_150000_rename_context_id_on_users_table.php`. Application wiring is not finished (see the apprenticeship context section); `users.is_mp` remains the transitional per-user flag. Optional `coach_id` / `trainer_id` (self-ref).
- `grades`: one row = one apprentice (`user_id`) + one leaf domain (`domain_id`) + one subject (`subject_id`). Swiss value `1.0–6.0`, `test_date`, `semester (1–8)`, optional proof file (`file_path`).

## 3. Portfolio (project file)

- `projects`: apprentice project (`user_id`, dates, links, description).
- `project_screenshots`: images for a project (`project_id`).
- `skills`: shared skill list (name unique).
- `project_skill`: many-to-many `projects` ↔ `skills`.

## 4. Comments

- `comments`: polymorphic (`commentable_type` + `commentable_id`). Targets: `grades` or `projects`. Author = `users.author_id`.

## 5. Roles / permissions (Spatie)

Standard Spatie tables, no custom logic:

- `roles`, `permissions`
- `model_has_roles`, `model_has_permissions` (polymorphic via `model_type` + `model_id`, only `User` used)
- `role_has_permissions`


# Decision breakdown

## Apprenticeship context

Answers two questions: which track the apprentice follows, and whether it includes MP.

MP changes how domains are wired, differently per track.
To keep it simple, we store both pieces of information.
Used in `domain_link_weights`.

Implemented: `apprenticeship_contexts` (`2026_10_01_114802…`, `down()` = `dropIfExists`). Each row pins one (`apprenticeship_id`, `is_mp`) pair to a `root_domain_id` in `domains` — the root of that variant's grade tree. The database side is done (`apprenticeship_contexts`, `domain_link_weights`, `users.apprenticeship_context_id`). The application wiring is not: no Eloquent model for these tables yet, and code and seeders still read `users.apprenticeship_id`, which no longer exists. `users.is_mp` remains the transitional per-user flag.

## Domain nodes

Wire domains together on a parent-child basis.

Used later in `domain_link_weights`

## Domain link weights

This is the configuration / weight calculation table.

It uses both `apprenticeship_contexts` and `domain_links` to determine a weight for a specific domain.

## Apprenticeship periods

It tracks repeating apprentices and the grades from the repeated year.

A grade occurs in a specific period, and we need to know if it has been repeated for the final average calculation.

Year 1 grades are superseded by repeated Year 1 grades, but kept to see where the apprentice improved and where they can still improve.
