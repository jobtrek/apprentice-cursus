# Database — current model

Source: `schemas/mcd_current.d2`

## 1. Training structure

- `apprenticeships`: formation track (e.g. IT / EC).
- `apprenticeship_context`: one variant per apprenticeship (`is_mp` true/false) + entry point `root_domain_id`.
- `domains`: formations domain blocks, contains `subjects`. 
- `domain_nodes`: parent → child links between domains (DAG).
- `domain_edges`: weight of a domain node for a given context (`weight`).
- `subject_category`: grouping for UI (CIE, modules, etc.).
- `subjects`: exam subject, belongs to one `domain_id` + one `subject_category_id`.

Flow: `apprenticeships` → `apprenticeship_context` → `domain_edges` (weighted) → `domain_nodes` → `domains`. `subjects` hang under leaf `domains`.

## 2. Users and grades

- `users`: apprentice account (Azure SSO via `azure_id`, email unique). Belongs to one `apprenticeship_context_id`. Optional `coach_id` / `trainer_id` (self-ref).
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
