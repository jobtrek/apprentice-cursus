# Database — current model

Source: `schemas/mcd_current.d2`

## 1. Training structure

- `apprenticeships`: formation track (e.g. IT / EC).
- `apprenticeship_contexts` (implemented in `2026_10_01_114802_create_apprenticeship_context_table.php`): one row per variant of an apprenticeship — `is_mp` boolean (MP track or not) + `root_domain_id` entry point into `domains`. Columns: `id`, `is_mp` (boolean, NOT NULL, no default), `apprenticeship_id` FK → `apprenticeships.id`, `root_domain_id` FK → `domains.id` (both `constrained()` with no cascade, i.e. NO ACTION: the database refuses to delete an apprenticeship or a root domain that still has contexts). No timestamps. A UNIQUE (`apprenticeship_id`, `is_mp`) constraint, added by `2026_10_07_110000_add_unique_to_apprenticeship_contexts_table.php`, enforces the “max 2 rows per apprenticeship (`is_mp` true/false)” rule in the database.
- `apprenticeship_periods` (implemented in `2026_10_07_130000_create_apprenticeship_periods_table.php`): the semesters an apprentice goes through. Columns: `id`, `user_id` FK → `users.id` (restrict; created as `apprentice_id`, renamed by `2026_10_07_190000`), `semester` smallint, `year` generated as `(semester + 1) / 2`, `start_date`, `end_date`. Index (`user_id`, `year`, `semester`). Two CHECKs added by `2026_10_07_210000`: `semester BETWEEN 1 AND 8` and `end_date >= start_date`. No timestamps.
- `domains`: training domain blocks (`name`, `rounding_step`, `created_at`). A root domain has children and no parent; a leaf domain has a parent and no child, and is where grades are entered.
- `domain_links`: parent → child links between domains. Own `id`, UNIQUE (`parent_id`, `child_id`), CHECK `parent_id <> child_id`; both foreign keys cascade on delete.
- `domain_link_weights`: weight of a domain link for a given context. Primary key (`apprenticeship_context_id`, `domain_link_id`), `weight` is a `decimal(3,2)` fraction, CHECK `weight > 0 AND weight <= 1`, no default.
- `subjects`: exam subject or module (`name`, `created_at`).
- `domain_subject` (implemented in `2026_10_07_230000_create_domain_subject_table.php`): many-to-many between `domains` and `subjects`. Primary key (`domain_id`, `subject_id`), index on `subject_id`, both foreign keys cascade on delete.

Flow: `apprenticeships` → `apprenticeship_contexts` → `domain_link_weights` (weighted) → `domain_links` → `domains`. `subjects` are attached to leaf `domains` through `domain_subject`.

## 2. Users and grades

- `users`: apprentice account (Azure SSO via `azure_id`, email unique). Belongs to one apprenticeship context through `apprenticeship_context_id` (nullable FK → `apprenticeship_contexts.id`). The column replaced `users.apprenticeship_id`, was first created as `context_id`, then renamed by `2026_10_07_150000_rename_context_id_on_users_table.php`; it is indexed by `2026_10_07_220000`, which also dropped `users.is_mp` (MP lives on the context only). Coaches and admins have no context. Optional `coach_id` / `trainer_id` (self-ref).
- `grades`: one row = one apprentice (`user_id`) + one leaf domain (`domain_id`) + one subject (`subject_id`) + one period (`apprenticeship_period_id`). Swiss value `1.0–6.0`, `test_date`, optional proof file (`file_path`). No `semester` column: the semester is the one of the period. One index per foreign key (`2026_10_07_200000`). A composite foreign key (`domain_id`, `subject_id`) → `domain_subject` refuses a grade whose subject is not attached to its domain.

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

Implemented: `apprenticeship_contexts` (`2026_10_01_114802…`, `down()` = `dropIfExists`). Each row pins one (`apprenticeship_id`, `is_mp`) pair to a `root_domain_id` in `domains`.

One tree per apprenticeship: the standard and the MP context of an apprenticeship share the same root and the same domains. What differs between them is the weights (see "Domain link weights").

MP lives on the context only: `users.is_mp` was dropped. An apprentice is MP when their context is.

State of the application code (2026-10-08):

- Done: Eloquent models for every table; `User::apprenticeship()` and `User::apprenticeshipId()` read the section through the context; `User::inSection()` filters users by section; the Azure sync and the user seeders assign a context; `GradeResource` reads domains and periods.
- Azure sync rule: a new account gets the standard context of its section; an apprentice already in its section keeps its context, so an MP context is not reset; a section without a context yet leaves the user without one.
- Still on the old model, to be rewritten (fix package 6): `EvaluationTreeSeeder`, which must create the domains, links, contexts and weights, and `GradebookTree`, which sends the tree to the gradebook pages. Until then nothing creates contexts, so users have none.

## Domain links

Wire domains together on a parent-child basis.

A link cannot point a domain at itself (CHECK). Nothing in the database prevents a longer cycle (A → B → A); only the tree seeder creates links today, and it builds a plain tree.

Deleting a domain deletes its links, and with them their weights (cascade). A domain or a subject that has grades cannot be deleted (`grades.domain_id` and `grades.subject_id` are RESTRICT).

Used later in `domain_link_weights`

## Domain link weights

This is the configuration / weight calculation table.

It uses both `apprenticeship_contexts` and `domain_links` to determine a weight for a specific domain.

- A weight is mandatory, strictly above 0 and at most 1, with no default: a forgotten weight fails instead of storing 0.
- Weights are relative: an average divides by the sum of the weights of the children that count, so 0.5 / 0.5 and 1 / 1 give the same result.
- A plain average is not a separate mode: every child gets the same weight (`1` each).
- A link that does not count for a context has no weight row for it. This is how MP is expressed: for EC, the school-teaching domain has no weight in the MP context; for IT, "Culture générale" and "Compétences de base élargies" have none, and the two remaining domains keep their 40 / 30 ratio (about 57 % / 43 %).
- Rounding (`domains.rounding_step`): the root is rounded to 0.1, every other domain to 0.5.

## Subjects and domains

Some formations share the same module (for example the developer and the infrastructure CFC). A module is a subject, and each formation has its own tree of domains, so one subject must be attachable to several domains: hence the `domain_subject` pivot instead of a `subjects.domain_id` column.

- A shared module exists once in `subjects` and is linked to the right leaf domain of each tree.
- A grade still counts for exactly one domain, the one in `grades.domain_id`; the composite foreign key to `domain_subject` guarantees the subject belongs to that domain, and stops a pivot row from being removed while grades use it.
- Deleting a domain or a subject removes its pivot rows (cascade), unless grades use them.

## Apprenticeship periods

It tracks repeating apprentices and the grades from the repeated year.

A grade occurs in a specific period, and we need to know if it has been repeated for the final average calculation.

Year 1 grades are superseded by repeated Year 1 grades, but kept to see where the apprentice improved and where they can still improve.

Rules (decided 2026-10-08):

- A grade's period is found from its `test_date`, not chosen by the user: it is the apprentice's period whose dates contain it.
- Semester 1 of a year runs from August to January, semester 2 until June/July.
- EC has 6 semesters, IT 8. The database only caps `semester` at 8.
- A repeated year adds a new period row with the same semester number, so (`user_id`, `semester`) is not unique.
- Not built yet: nothing creates period rows, and no route stores a grade. Both are to do together.

## Foreign keys without a delete rule

`apprenticeship_contexts.apprenticeship_id`, `apprenticeship_contexts.root_domain_id` and `domain_link_weights.apprenticeship_context_id` state no rule, which in PostgreSQL means NO ACTION: the delete is refused while rows point at the target. Decided 2026-10-07 to leave them as they are.

## Migrating

The migration chain from `2026_10_01_095759` onward drops the old evaluation tree and only works on a fresh database: run `migrate:fresh`, then seed. Existing grades are not carried over.
