# Tree app code

This file is the context for solving the issues below in the package of issue Tree app code.

- **Depends on:** package 1 (Domain links: `DomainLink`, `DomainLinkWeight`), package 3 (Subjects: `Subject` with `name`, no `SubjectCategory`), package 5 (Users to context: `User::apprenticeshipContext`).
- **Blocks:** package 7 (Grade app code).
- The seeder and its test (first two files) only need packages 1 and 3. `GradebookTree` also needs package 5.
- The file list below comes from a search for the stale names, not from reading each file in full. Some files may need no change.

Files owned by this package (no other package edits them):

- `../../database/seeders/EvaluationTreeSeeder.php`
- `../../tests/Feature/EvaluationTreeSeederTest.php`
- `../../app/Support/Gradebook/GradebookTree.php`
- `../../resources/js/lib/gradebook.ts`
- `../../app/Enums/PeriodScope.php`, `../../app/Enums/EvaluationVariant.php`, `../../app/Enums/AggregationType.php`

# Problems encountered

- [ ] **Deleted classes are still imported.** `App\Models\EvaluationNode` and `EvaluationNodeConnection` no longer exist. Used in `EvaluationTreeSeeder.php` (lines 9, 45, 83, 90), `EvaluationTreeSeederTest.php` (throughout) and `GradebookTree.php` (lines 6, 7, 35, 40, 46).
- [ ] **The seeder writes dropped columns.** `aggregation`, `period_scope`, `variant` (lines 93 to 97) were dropped from `domains`; `subject_category_id` and `SubjectCategory` (lines 120 to 122) are gone; `apprenticeships.evaluation_node_id` (lines 68, 75) is gone, the root now lives on `apprenticeship_contexts.root_domain_id`.
- [ ] **The seeder calls `addChild()`** (line 109), a method of the deleted model. It carried the weight on the link and the cycle guard.
- [ ] **Weights moved.** They are no longer on the link but in `domain_link_weights`, one row per (`apprenticeship_context_id`, `domain_link_id`).
- [ ] **MP is modelled differently.** The seeder creates a standard and an MP sibling node flagged by `variant` (lines 185 to 221). The new schema has no `variant`: MP is a separate context with its own root and its own weights.
- [ ] **`GradebookTree` reads the old path.** `$user->apprenticeship?->evaluation_node_id` (line 23), `$user->is_mp` (line 29), `$child->variant` (line 49), `$node->period_scope` (line 76).
- [ ] **No cycle guard exists any more.** The database only blocks self-loops (package 1). `../adr/ADR.md` says a cycle makes the aggregation walk loop forever.
- [ ] **Enums may be orphaned.** `PeriodScope`, `EvaluationVariant`, `AggregationType` describe columns that no longer exist.
- [ ] **The test asserts the old shape.** `evaluation_node_connections` count (line 113), `Apprenticeship::pluck('evaluation_node_id')` (line 115), `variant` and `period_scope` expectations (lines 78 to 79, 104, 127).

# Fixes suggested

1. **Seeder, structure.** Create `Domain` rows (`name`, `rounding_step`), then one `DomainLink` per parent → child edge. Create two `ApprenticeshipContext` rows per apprenticeship that has an MP variant (`is_mp` false / true), each with its `root_domain_id`.
2. **Seeder, weights.** For each context, insert `DomainLinkWeight` rows for the links that apply to it. Weights are fractions (`decimal(3,2)`, 0.01 to 1): convert the current percentages (50 → 0.50). The EC table in `../adr/ADR.md` (lines 10 to 18) gives the standard and MP values.
3. **Seeder, MP.** Replace the `variant` siblings by weights: a link that does not apply to a context simply has no weight row for it. Shared leaves (CIE, workplace) stay single domains linked once.
4. **Seeder, subjects.** `Subject::create(['name' => …, 'domain_id' => $leaf->id])`; remove `SubjectCategory`.
5. **Cycle guard.** Add a method on `Domain` (file owned by package 1, so ask that owner) or a small service used by the seeder, for example `Domain::linkChild(Domain $child)`, that refuses a self-link and any edge closing a cycle. Port the walk from the old `EvaluationNode::addChild()` (see `git show main:app/Models/EvaluationNode.php`).
6. **`GradebookTree`.** Start from `$user->apprenticeshipContext?->root_domain_id`. Load the links reachable from the root and the weights for that context in two queries, and keep only links that have a weight row for the context. Drop the `variant` filter and `is_mp` read.
7. **Leaf and scope.** "Leaf" was `aggregation === null`; it is now "has no child link in this context". `period_scope` has no replacement in the schema: check `../../resources/js/lib/gradebook.ts` for what the front end needs before removing it from the payload, and raise it with the team if the page depends on it.
8. **Enums.** Delete the ones with no remaining use after the changes above (`grep -rn "PeriodScope\|EvaluationVariant\|AggregationType" app database tests`).
9. **Test.** Rewrite `EvaluationTreeSeederTest` around the new shape: weights per context sum to 1 under each parent, both EC contexts exist with different weights, seeding twice is idempotent.

Check when done: `./vendor/bin/sail artisan migrate:fresh --seed` passes and `./vendor/bin/sail artisan test --filter=EvaluationTreeSeeder` is green.
