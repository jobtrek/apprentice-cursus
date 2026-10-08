# Grade app code

This file is the context for solving the issues below in the package of issue Grade app code.

- **Depends on:** package 4 (Grades and periods: `user_id`, `apprenticeship_period_id`, no `semester`), package 6 (Tree app code: seeded tree, `GradebookTree`), package 10 (Remaining migrations: the grade guard and the `users.is_mp` decision).
- **Blocks:** nothing.
- The file list below comes from a search for the stale names, not from reading each file in full. Some files may need no change.

Files owned by this package (no other package edits them):

- `../../app/Http/Resources/GradeResource.php`
- `../../tests/Feature/GradeResourceTest.php`
- `../../app/Http/Controllers/GradeController.php`
- `../../app/Http/Controllers/HomeController.php`
- `../../database/seeders/DemoGradeSeeder.php`
- `../../tests/Feature/DemoGradeSeederTest.php`
- `../../resources/js/data/gradebook.ts`

# Problems encountered

- [x] **`GradeResource` uses the deleted `EvaluationNode`** (lines 5, 75 to 77) and reads dropped columns: `evaluation_node_id` (line 39) and `semester` (line 47).
- [x] **`GradeResourceTest`** builds nodes with `EvaluationNode` and grades with `user_id`, `evaluation_node_id`, `semester` (lines 15 to 33).
- [x] **`DemoGradeSeeder` cannot run.** _Removed, see "What was done"._ Imports `EvaluationNode` and `EvaluationNodeConnection` (lines 6, 7), reads `$apprenticeship->evaluation_node_id` (lines 70, 79), inserts `user_id`, `evaluation_node_id`, `semester` (lines 87 to 90), and filters leaves with `aggregation` and `period_scope` (lines 127 to 128).
- [x] **`DemoGradeSeeder` does not supply the new required columns.** _Removed._ `grades.subject_id` and `grades.apprenticeship_period_id` are NOT NULL; no demo periods exist.
- [x] **`DemoGradeSeederTest`** _Removed._ reads `$user->apprenticeship->evaluation_node_id` (line 26).
- [x] **`HomeController` line 35** sends `'variant' => $user->is_mp ? 'mp' : 'standard'`, based on `users.is_mp`.
- [ ] **`GradeController`** was flagged by the search for grade writes: check that it validates and stores `subject_id` and a period, and no longer `semester`.

# Fixes suggested

1. **`GradeResource`.** `node_id` → `$this->domain_id`. Replace `semester` by the period: `$this->apprenticeshipPeriod->semester` (and `year` if the page shows it), with the relation eager-loaded by the caller. Rewrite `firstParent()` on `Domain` using the parent links of package 1; note a domain can have several parents, so decide which path is displayed (the one inside the apprentice's context).
2. **`GradeResourceTest`.** Build `Domain` + `DomainLink` rows instead of `EvaluationNode`, and grades with `user_id`, `domain_id`, `subject_id`, `apprenticeship_period_id`. Ask the package 5 owner for the shared helper in `../../tests/Pest.php` instead of editing it.
3. **`DemoGradeSeeder`.**
    - Start from `$user->apprenticeshipContext->root_domain_id`.
    - Walk `DomainLink` rows (breadth-first, visited set, as today) to collect leaf domains: leaves are domains with no child link.
    - For each demo apprentice, create `ApprenticeshipPeriod` rows first (the current fixtures use semesters 1 and 2), then attach each grade to the period matching its fixture semester.
    - Pick a `Subject` of the leaf domain for `subject_id` (through `domain_subject` once migration S of package 10 has landed).
4. **`DemoGradeSeederTest`.** Assert through the context root instead of `apprenticeship->evaluation_node_id`.
5. **`HomeController`.** Read MP from `$user->apprenticeshipContext?->is_mp`. If the team keeps `users.is_mp` (decision D1 in package 10), leave it and add a comment naming the decision.
6. **`GradeController`.** Validate `subject_id` (exists, belongs to the chosen domain) and resolve the period server-side rather than trusting a posted `semester`. "Belongs to the chosen domain" means a `domain_subject` row once migration S of package 10 has landed (`subjects.domain_id` before that). If the grade guard is accepted (decision D4), the database refuses an invalid pair too: the validation rule is what turns it into a form error.
7. **`../../resources/js/data/gradebook.ts`.** Update the grade type if `semester` / `node_id` changed shape in the resource.

# What was done

- **Fixes 1 and 2 (`bed8e5be`).** `GradeResource` reads the domain, its parents through `domain_links`, and the semester of the grade's period. The payload keys `node_id` and `semester` are unchanged, so fix 7 needs no change. `GradeResourceTest` builds its own domains and links instead of running the tree seeder.
- **Groundwork (`dfc84539`).** `GradePolicy` and `CommentPolicy` read `$grade->user`; the relation is `apprentice()`.
- **Fixes 3 and 4: not done, removed instead (decided 2026-10-08).** `DemoGradeSeeder`, its test and its call in `DatabaseSeeder` are deleted. The demo grades will be rebuilt later on the new schema.
- **Fix 5 (`b3777a17`).** `HomeController` reads MP from `apprenticeshipContext->is_mp`.
- **Fix 6: waiting.** `GradeController` has no route that stores a grade yet, so there is nothing to validate. To do with that route.
- **Period rule (decided 2026-10-08).** A grade's period is found from `test_date`. Semester 1 runs from August to January, semester 2 until June/July. EC has 6 semesters, IT 8. A repeated year adds a period row with the same semester number, so `(user_id, semester)` is not unique.

# Open question (shared with package 4)

- **How does a new grade pick its period?** Derived from `test_date` against the apprentice's `apprenticeship_periods` date ranges, or chosen by the user? Fix 3 and fix 6 depend on the answer. Recommendation: derive it from `test_date`, which matches the user story ("the application automatically derives the year and semester from the test date").

Check when done: `./vendor/bin/sail artisan test --filter=GradeResource`, `--filter=Home` and `--filter=Authorization` are green. `./vendor/bin/sail artisan migrate:fresh --seed` and the grades dashboard for a seeded apprentice also need the tree seeder and `GradebookTree` of package 6.
