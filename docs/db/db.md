# Database — current model

The schema built by `database/migrations/`. Why it looks this way: `docs/adr/ADR.md`, 2026-10-14 (grade tree and formation plans) and 2026-09-09 (conventions that still apply).

Diagram: `schemas/mcd_current.d2`. When the diagram and the migrations disagree, the migrations win (the d2 still shows `grades.domain_id` and `formation_plans.root_domain_id`).

## 1. Training structure

- `formation_plans`: one row per plan: IT, EC and EC MP. The MP variant is its own plan, not a flag.
- `average_nodes`: the grade tree. `parent_id` (null on a root), `name`, `weight` (fraction of the parent, `0 < weight ≤ 1`), `rounding_step` (`0.5` marks a semester leaf). A node without children is a leaf: the only kind of node that holds grades.
- `average_node_formation_plan`: which nodes belong to which plan. EC and EC MP share every node except their own "Note d'expérience" branch.

Seeded by `AverageNodeSeeder`, read by `App\Support\Gradebook\GradebookTree`.

## 2. Users and grades

- `users`: Entra account (`azure_id` unique, `tenant_id`, `is_active`, `synced_at`). `password` is nullable and only used by the local login. `formation_plan_id` is the apprentice's plan. `coach_id` points at another user.
- `users_formation_plans`: the plans a trainer trains, synced from the Azure group (`trainer_IT` → IT, `trainer_EC` → EC + EC MP).
- `grades`: one apprentice (`user_id`) + one leaf (`average_node_id`). `value` `1.0–6.0` (CHECK), `test_date`, optional proof `file_path`, `notified_at`. The semester is not stored: `App\Support\Gradebook\Semester` derives it from `test_date`.
- `ocr_scans`: one row per PDF read by the automatic grade reading: who uploaded it, the reading, Jev's confidence, the flag, and the Gemini and Jev cost. `grade_id` is set when a grade is saved from the scan. Never stores the text of the test.

## 3. Portfolio (project file)

- `projects`: apprentice project (`user_id`, dates, links, description).
- `project_screenshots`: images for a project (`project_id`).
- `skills`: shared skill list (name unique).
- `project_skill`: many-to-many `projects` ↔ `skills`.

## 4. Comments

- `comments`: `author_id` + exactly one of `project_id` / `grade_id` (CHECK `num_nonnulls(...) = 1`), both cascade on delete. `body` ≤ 2000 characters.

## 5. Roles / permissions (Spatie)

Standard Spatie tables, no custom logic:

- `roles`, `permissions`
- `model_has_roles`, `model_has_permissions` (polymorphic via `model_type` + `model_id`, only `User` used)
- `role_has_permissions`

Created and wired by `App\Support\RolesAndPermissions::sync()` from `App\Enums\Permission` and `App\Enums\UserRole`.

# Not modeled yet

- **Repeated years.** Nothing marks a grade as belonging to a repeated year, so a repeated Year 1 grade does not replace the first one in the averages.
- **Apprenticeship start date.** Semesters are counted from the apprentice's first grade, so an apprentice whose first grade is late gets shifted semesters.

The design this file used to describe (apprenticeship contexts, domain nodes and edges, subjects, periods) is in its git history. Older diagrams are in `schemas/history/`.
