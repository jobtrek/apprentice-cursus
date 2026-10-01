# Database — current model

Source: `schemas/mcd_current.d2`

## 1. Training structure

- `apprenticeships`: formation track (e.g. IT / EC).
- `apprenticeship_contexts` (implemented in `2026_10_01_114802_create_apprenticeship_context_table.php`): one row per variant of an apprenticeship — `is_mp` boolean (MP track or not) + `root_domain_id` entry point into `domains`. Columns: `id`, `is_mp` (boolean, NOT NULL, no default), `apprenticeship_id` FK → `apprenticeships.id`, `root_domain_id` FK → `domains.id` (both `constrained()`, cascade on delete). No timestamps, no unique constraint yet — so the “max 2 rows per apprenticeship (`is_mp` true/false)” rule is currently conventional, not DB-enforced.
- `apprenticeship_periods`: planned, not yet migrated — stores the periods during which the apprenticeship takes place.
- `domains`: training domain blocks, each holds `subjects`.
- `domain_links`: parent → child links between domains (DAG).
- `domain_edges`: weight of a domain node for a given context (`weight`).
- `subject_category`: grouping for UI (CIE, modules, etc.).
- `subjects`: exam subject, belongs to one `domain_id` + one `subject_category_id`.

Flow: `apprenticeships` → `apprenticeship_contexts` → `domain_edges` (weighted, planned) → `domain_links` → `domains`. `subjects` hang under leaf `domains`.

## 2. Users and grades

- `users`: apprentice account (Azure SSO via `azure_id`, email unique). Planned: belongs to one `apprenticeship_context_id` (not yet migrated; currently `is_mp` flag on `users`). Optional `coach_id` / `trainer_id` (self-ref).
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
Used later in `domain_edges` (planned, not yet migrated).

Implemented: `apprenticeship_contexts` (`2026_10_01_114802…`, `down()` = `dropIfExists`). Each row pins one (`apprenticeship_id`, `is_mp`) pair to a `root_domain_id` in `domains` — the root of that variant's grade tree. No model / `domain_edges` / `users.apprenticeship_context_id` wiring yet; `users.is_mp` remains the transitional per-user flag.

## Domain nodes

Wire domains together on a parent-child basis.

Used later in `domain_edges`

## Domain edges

This is the configuration / weight calculation table.

It uses both `apprenticeship_contexts` and `domain_links` to determine a weight for a specific domain.

## Apprenticeship periods

It tracks repeating apprentices and the grades from the repeated year.

A grade occurs in a specific period, and we need to know if it has been repeated for the final average calculation.

Year 1 grades are superseded by repeated Year 1 grades, but kept to see where the apprentice improved and where they can still improve.
