# PR #182 review fixes — final report

Branch: `azure/roles` (not pushed). Review source: `issue_table.md` (found in `~/.local/share/Trash/files/issue_table.md`, not at the repo root; left there, never committed).
Plan: `pr-182-fix-plan.md` (session scratchpad), owner decisions D1–D8.

## Owner decisions applied

| id | decision |
|---|---|
| D1 | `coaching.assign-self` kept for coaches, no route yet; matrix row = Y "permission only, route not implemented yet" |
| D2 | Coaches see/comment only on apprentices whose `coach_id` is theirs (review finding P2 rejected) |
| D3 | Deactivation/reactivation at login + re-check middleware only; valid single-group login reactivates |
| D4 | Spatie role is the only role source; `users.role` dropped by migration; `User::role` is a read-only accessor |
| D5 | Password login is local-only; local seed: `admin@example.com` (coach), `trainer@example.com` (IT trainer), password `password` |
| D6 | `apprenticeships.code` not restored; groups map by seeded name via `ApprenticeshipSeeder::IT` / `::EC` |
| D7 | Demo data (`App\Support\Demo\DemoGrade`, demo seeders) only in local |
| D8 | `User::homeRoute()` picks the landing route by permission |

## 1. Findings

| id | finding (short) | status | files touched |
|---|---|---|---|
| S1 | Section change logged the user out | fixed (3d87765b) | `app/Services/AzureAccountSync.php`, `app/Http/Middleware/EnsureAzureAccountIsActive.php`, `app/Services/MicrosoftLoginService.php` |
| S2 | Login didn't set the account re-check cache key | fixed (3d87765b) | `app/Http/Controllers/Auth/MicrosoftAuthController.php`, `app/Services/AzureAccountSync.php` |
| S3 | No back-off after a Microsoft Graph failure | fixed (3d87765b), 60s back-off | `app/Http/Middleware/EnsureAzureAccountIsActive.php`, `app/Services/AzureAccountSync.php` |
| S4 | `supervises()` didn't check the target is an apprentice | fixed (25647af8) | `app/Models/User.php` |
| S5 | Role stored twice, kept in sync by a `saved` hook | fixed per D4 (79d595d2) | `app/Models/User.php`, `database/migrations/2026_09_30_100000_move_user_roles_to_permission_tables.php`, `app/Support/RolesAndPermissions.php`, `database/factories/UserFactory.php`, seeders, `app/Http/Middleware/HandleInertiaRequests.php`, `app/Services/AzureAccountSync.php`, `app/Services/MicrosoftLoginService.php` |
| S6 | Permissions existed only through a seeder (500 on an unseeded DB) | fixed (79d595d2) | migration above, `app/Support/RolesAndPermissions.php`, `database/seeders/RolesAndPermissionsSeeder.php`, `tests/Pest.php` |
| S7 | Demo users and the admin coach seeded in every environment | fixed (79d595d2) | `database/seeders/DatabaseSeeder.php`, `database/seeders/UserSeeder.php`, `database/seeders/DemoApprenticeSeeder.php` |
| S8 | Two duplicate Graph clients | fixed (3d87765b) | `app/Services/Microsoft/MicrosoftGraphService.php`; `app/Services/Microsoft/AzureGraphService.php` and `app/Providers/RiakServiceProvider.php` deleted |
| S9 | Test helpers defined in one test file, so another file failed alone | fixed (8bd1fcb3) | `tests/Pest.php`, `tests/Feature/AuthorizationTest.php`, `tests/Feature/MicrosoftAuthTest.php` |
| S10 | Grade page ignored the server's `can.comment` | fixed (84f62e3a) | `resources/js/pages/GradeDetails.vue`, `resources/js/types/auth.ts` |
| S11 | Demo grade data in the Controllers namespace | fixed per D7 (ad3d422a) | `app/Support/Demo/DemoGrade.php`, `app/Http/Controllers/GradeController.php`, `app/Http/Controllers/ApprenticeController.php`; `app/Http/Controllers/DemoGrade.php` deleted |
| S12 | Azure group names repeated as raw strings | fixed (3d87765b) | `app/Enums/AzureGroup.php`, `app/Services/MappingRolesService.php`, `config/services.php`, tests |
| S13 | Dead code and unused endpoints config | fixed (3d87765b) | `app/Services/MappingRolesService.php`, `config/services.php` |
| S14 | `role_has_permissions.role_id` had no index | fixed (79d595d2) | `database/migrations/2026_09_30_082258_create_permission_tables.php` |
| S15 | Raw `can:` strings in routes, `whereNumber` missing | fixed (acca384c) | `routes/web.php`, `tests/Feature/AuthorizationTest.php` |
| P1 | Section change ended the session | fixed, same change as S1 | as S1 |
| P2 | Coaches should see every apprentice | changed by decision D2 (assigned only; existing test kept) | `docs/project-docs/role_permissions.md`, `docs/adr/ADR.md` |
| P3 | A trainer could open another trainer's page | fixed (25647af8) | `app/Models/User.php`, `tests/Feature/AuthorizationTest.php` |
| P4 | A deactivated user could never be reactivated | fixed per D3 (reactivated on valid login, no command) | `app/Services/AzureAccountSync.php`, `app/Services/MicrosoftLoginService.php`, `tests/Feature/MicrosoftAuthTest.php` |
| P5 | Commenting ignored `is_active`; no `CommentPolicy` | fixed (25647af8); project comments out of scope (no route/permission yet) | `app/Policies/GradePolicy.php`, `app/Policies/CommentPolicy.php` |
| P6 | Coach self-assign: matrix and code disagreed | changed by decision D1 | `docs/project-docs/role_permissions.md`, `docs/adr/ADR.md` |
| P7 | Password login enabled everywhere | fixed per D5 (3e7e0ef0) | `routes/auth.php`, `app/Http/Controllers/Auth/AuthenticatedSessionController.php`, `resources/js/pages/auth/Login.vue`, `tests/Feature/PasswordLoginTest.php`, `docs/project-docs/role_permissions.md` |
| P8 | Missing `Http::fake` tests for the Azure sync | fixed (8bd1fcb3) | `tests/Feature/MicrosoftAuthTest.php`, `tests/Feature/AuthorizationTest.php`, `tests/Pest.php` |
| P9 | Frontend derived comment rights from the role | fixed, same change as S10 | as S10 |
| P10 | No role-based redirect after login | fixed per D8 (acca384c), `User::homeRoute()` | `app/Models/User.php`, `app/Http/Controllers/Auth/MicrosoftAuthController.php`, `app/Http/Controllers/Auth/AuthenticatedSessionController.php`, `tests/Feature/MicrosoftAuthTest.php` |
| P11 | `apprenticeships.code` column dropped | changed by decision D6 (reason recorded in ADR) | `docs/adr/ADR.md` |
| P12 | Demo data not in the plan | fixed per D7 (local only) | `app/Support/Demo/DemoGrade.php`, `database/seeders/DatabaseSeeder.php`, `tests/Feature/AuthorizationTest.php` |



## 2. Commits since 0acac961

| hash | subject |
|---|---|
| 3d87765b | fix(auth): share Azure account sync between login and re-check middleware |
| 25647af8 | fix(authz): scope supervision to apprentices and guard comments on inactive apprentices |
| 79d595d2 | fix(authz): make Spatie roles the single source of truth and create them in a migration |
| 3e7e0ef0 | fix(auth): restrict password login to the local environment |
| acca384c | fix(routes): land users by permission and use the Permission enum in route middleware |
| 84f62e3a | fix(ui): use the server can.comment decision on the grade page |
| ad3d422a | refactor(demo): move demo grade data out of controllers and serve it only in local |
| 8bd1fcb3 | test(auth): cover Azure sync edge cases and share test helpers |
| 82b1557b | docs(authz): align role matrix, Entra setup and ADR with the final role sync |

## 3. Check results

| check | result |
|---|---|
| `sail artisan migrate:fresh` (no seed) | OK — 3 roles, 8 permissions, `users.role` gone; rollback + re-migrate of the new migration OK (empty DB) |
| `sail artisan test` | 90 passed, 471 assertions; every Feature file passes alone |
| `composer lint` (pint) | passed |
| `composer types:check` (larastan L7) | failed, 25 errors — all pre-existing (`main`: 34) |
| `pnpm check` | lint clean (0 warnings, 0 errors); formatting fails on 19 files |
| `pnpm types:check` (vue-tsc) | passed |

### Larastan errors (pre-existing)
- 24 × `missingType.generics` on model relations: `Comment::author/commentable`, `EvaluationNode::subject/children/parents/grades/evaluationResults`, `EvaluationResult::user/evaluationNode`, `Grade::evaluationNode/comments`, `Project::comments/skills`, `Skill::projects`, `Subject::subjectCategory/evaluationNodes`, `SubjectCategory::subjects`, `User::coach/trainer/coachees/trainees/grades/evaluationResults/comments`.
- 1 × `return.missing` at `database/factories/UserFactory.php:57`: "Method Database\Factories\UserFactory::withTwoFactor() should return static(Database\Factories\UserFactory) but return statement is missing." (unchanged from `main`).

### `pnpm check` formatting failures ("Found formatting issues in 19 files")
- `.claude/write-user-story/SKILL.md`, `CLAUDE.md`, `COMPONENTS.md`, `README.md`, `eslint.config.js`, `package.json`, `pnpm-workspace.yaml`, `resources/css/app.css`, `resources/js/data/modules.json`
- `docs/adr/ADR.md`, `docs/adr/azure_id_groupd.md`, `docs/meeting.md`, `docs/plans/2026-09-28-roles-and-permissions.md`
- `docs/project-docs/DoD.md`, `docs/project-docs/notes.md`, `docs/project-docs/technical_details.md`
- `docs/user_stories/user_story_final.md`, `docs/workflows/dossier-formation.md`, `docs/workflows/nommage-note.md`
- 16 also fail on `main`; `azure_id_groupd.md`, `notes.md` and the `docs/plans` file were added by this PR.

## 4. Test coverage added (P8)
- Graph 500 at login → refused with "Could not verify your Microsoft groups…", guest, no `users` row.
- Graph 500 in middleware → request passes, back-off key cached, second request makes no Graph call, key expires after 60s.
- No mapped group / several mapped groups at login → refused, existing user deactivated, new user gets no row.
- Several mapped groups in middleware → session ended, `is_active = false`.
- Role change mid-session → Spatie role updated, still logged in.
- Section change at login and mid-session → apprenticeship kept, warning logged, not logged out.
- Entra `accountEnabled: false` and Graph 404 → session ended, `is_active = false`.
- Deactivated user reactivated on a valid login.
- Coach comments only on assigned apprentices; IT trainer views/comments only IT apprentices, never another trainer or itself.
- Migrated-but-unseeded DB: policy checks return 200/403, not 500.
- Demo payload present only in local; `pdfUrl: null`, `comments: []` elsewhere.
- Non-numeric project/screenshot ids → 404; `homeRoute()` per permission.

## 5. Deviations from the plan
- Step 4 split into two commits (routes/landing, UI); `GradeDetails.vue` run through the project formatter.
- Pre-existing larastan and formatting failures left alone (outside the findings' scope).
- `ADR.md` still fails the format check: only the "Roles and permissions" section was rewritten; the formatter wanted to reflow older entries, which were left untouched. `azure_groups.md` and `role_permissions.md` pass.
- `role_permissions.md`: removed "Change own password" / "Set own password on first login" rows, added a Microsoft SSO row; notes that `PUT profile/password` still exists without a page.
- An existing test's fake Graph URL (`users/azure-6`) never matched because of the query string; now `users/azure-6?*`. No app bugs found by the new tests.
- The redirect after local password login is not HTTP-tested (the route only exists when booted in `local`); `homeRoute()` and the SSO redirect are tested instead.
- `migrate:fresh` wiped the local dev database; not reseeded. Run `./vendor/bin/sail artisan db:seed` to restore local accounts.
- `issue_table.md` was in the Trash, not the repo root; read from there, left there.
