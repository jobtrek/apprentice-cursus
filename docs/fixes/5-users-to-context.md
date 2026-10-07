# Users to context

This file is the context for solving the issues below in the package of issue Users to context.

- **Depends on:** nothing for fixes 1 to 6, which can start immediately. Only the `is_mp` problem (M6) waits for decision D1 of package 10.
- **Blocks:** package 6 (Tree app code), which needs the user → context → root domain path.
- **This package writes no migration.** Its two schema changes (the `apprenticeship_context_id` index and the `users.is_mp` drop) moved to package 10 (`./10-remaining-migrations.md`, migration U). Committed migrations are not edited in place.
- The file list below comes from a search for the stale names, not from reading each file in full. Some files may need no change.

Files owned by this package (no other package edits them):

- `../../database/migrations/2026_10_07_150000_rename_context_id_on_users_table.php` (reference only, not edited)
- `../../app/Models/User.php`
- `../../app/Policies/UserPolicy.php`
- `../../app/Http/Controllers/SupervisionController.php`, `ApprenticeController.php`, `DossierController.php`
- `../../app/Http/Middleware/HandleInertiaRequests.php`, `EnsureAzureAccountIsActive.php`
- `../../app/Http/Resources/ApprenticeResource.php`
- `../../app/Services/AzureAccountSync.php`, `AzureDirectorySync.php`, `MicrosoftLoginService.php`
- `../../resources/js/types/auth.ts`
- `../../database/seeders/UserSeeder.php`, `DemoApprenticeSeeder.php`
- `../../tests/Pest.php`
- `../../tests/Feature/ApprenticeListTest.php`, `AuthorizationTest.php`, `AzureAccountSyncJobTest.php`, `LocalAccountsTest.php`, `MicrosoftAuthTest.php`, `SelfAssignmentTest.php`, `SupervisionTest.php`

`../../tests/Pest.php` also builds a node and a grade for other tests. Packages 6 and 7 ask this package's owner for helper changes instead of editing it.

# Problems encountered

- [ ] **M3 — `users.apprenticeship_id` no longer exists but is used everywhere.** It was dropped by `2026_10_01_114802` and replaced by `apprenticeship_context_id`. Still read or written in:
  - `User.php`: `listedApprentices()` (line 179), `assignableApprentices()` (lines 211 to 212), `supervises()` (lines 230 to 231)
  - `UserPolicy.php` lines 30 to 31
  - `SupervisionController.php` lines 54 to 55
  - `AzureAccountSync.php` lines 86 to 97, `AzureDirectorySync.php` lines 124 and 132
  - `HandleInertiaRequests.php` line 50 (shared prop `apprenticeship_id`)
  - `UserSeeder.php` line 70, `DemoApprenticeSeeder.php` line 48
  - `../../tests/Pest.php` line 68 and about ten test files (`forceFill(['apprenticeship_id' => …])`)
- [ ] **M3 — `User` has no `apprenticeship()` relation any more**, but `GradebookTree.php` (package 6) and `DemoGradeSeederTest.php` (package 7) call `$user->apprenticeship`.
- [ ] **G2 — `users.apprenticeship_context_id` has no index.** The old `apprenticeship_id` index disappeared with the column; the new column never got one. **Moved to package 10 (migration U).**
- [ ] **M6 — `users.is_mp` still exists but `User` does not declare it** (no docblock, no cast), while `HomeController.php` line 35 and `GradebookTree.php` line 29 still read it. **Waits for decision D1 of package 10:** if the column is dropped there, nothing is left to declare here.
- [ ] **`../../tests/Pest.php` line 53** creates an `EvaluationNode`, a class that was deleted.

# Fixes suggested

1. **Give `User` the section through its context.** Add a relation:

   ```php
   /** @return HasOneThrough<Apprenticeship, ApprenticeshipContext, $this> */
   public function apprenticeship(): HasOneThrough
   {
       return $this->hasOneThrough(Apprenticeship::class, ApprenticeshipContext::class,
           'id', 'id', 'apprenticeship_context_id', 'apprenticeship_id');
   }
   ```

   and a small accessor `apprenticeshipId(): ?int` returning `$this->apprenticeshipContext?->apprenticeship_id`, so call sites change in one predictable way.
2. **Rewrite the three `User` queries.** Replace `->where('apprenticeship_id', $this->apprenticeship_id)` with
   `->whereHas('apprenticeshipContext', fn ($q) => $q->where('apprenticeship_id', $this->apprenticeshipId()))`,
   and `whereNotNull('apprenticeship_id')` with `whereNotNull('apprenticeship_context_id')`. `supervises()` compares `apprenticeshipId()` on both sides.
3. **Same substitution** in `UserPolicy`, `SupervisionController` and the `HandleInertiaRequests` shared prop (keep the prop name `apprenticeship_id` so `../../resources/js/types/auth.ts` and the pages do not change).
4. **Sync services.** `AzureAccountSync` / `AzureDirectorySync` must now resolve an `apprenticeship_contexts` row instead of an apprenticeship id. That needs a rule for `is_mp` at sync time (see open question). Until decided, pick the non-MP context of the section.
5. **Trainers.** A trainer has a section but no MP notion. Confirm trainers also point at a context (the non-MP one), since `supervises()` compares sections for trainers.
6. **Seeders and tests.** Replace `forceFill(['apprenticeship_id' => $x->id])` with the context id. Add a helper in `../../tests/Pest.php` (for example `contextFor(Apprenticeship $section, bool $mp = false)`) so the ten test files change one call each. Replace the `EvaluationNode` use on line 53 with `Domain`, and the grade it creates with the columns from package 4 (`user_id`, `domain_id`, `subject_id`, `apprenticeship_period_id`).
7. **G2 — moved to package 10 (migration U).** `2026_10_07_150000` is committed and is not edited.

# Open question (needs a team decision, not a code fix)

Tracked as decision D1 in `./10-remaining-migrations.md`, which owns the migration that follows from it.

- **Where does MP live?** `apprenticeship_contexts.is_mp` and `users.is_mp` both exist. `../db/db.md` calls `users.is_mp` "transitional" with no end date. Decide: drop `users.is_mp` (new migration, then update `HomeController` in package 7 and `GradebookTree` in package 6) or keep it and document which one wins. Recommendation: drop it, the context already carries it.

Check when done: `./vendor/bin/sail artisan test --filter=Supervision`, `--filter=MicrosoftAuth`, `--filter=ApprenticeList` pass; `composer types:check` reports no `apprenticeship_id` property error on `User`.
