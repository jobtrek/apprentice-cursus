# Plan — week of 2026-09-28: map Entra users, roles, permission-based views

Goal: every user who logs in gets the right `role` and section, is matched on `azure_id`, and only sees the pages and data their role allows (`docs/project-docs/role_permissions.md`).

## Where we are (2026-09-24)

- `MappingRolesService` returns `[['id', 'mail'], ...]` for three Entra groups via `AzureGraphService::getGroupMembers()`:
  - `getITApprentices()` → `MICROSOFT_GROUP_APPRENTICES_IT` (Apprentis - Dev centre de formation, 13 members)
  - `getECApprentices()` → `MICROSOFT_GROUP_APPRENTICES_EC` (Apprentis - EC centre de formation, 22 members)
  - `getCollaborators()` → `MICROSOFT_GROUP_TRAINER` (it@jobtrek.ch, 4 members)
- Graph member `id` == `users.azure_id` saved at login (verified). `tenant_id` is the org id, the same for everyone: useless for matching.
- `php artisan azure:roles {it|ec|trainer}` prints each group.
- Nothing assigns `users.role` yet: every user gets the DB default `apprentice`.
- No policies, no gates, no role data shared with Inertia. All routes are only behind `auth` + `verified`.

## Decide first (Monday morning, blocks everything else)

1. **Groups now vs. app roles later.** `role_permissions.md` / `azure_groups.md` describe Entra **app roles** + `Section-IT`/`Section-EC` groups. None of that is configured yet (the checklist in `azure_groups.md` is empty). Proposal: ship this week on the **existing mail groups**, behind one resolver, so switching to app roles later only changes the resolver.
2. **Trainer section.** The trainer group (it@jobtrek.ch) has no IT/EC split. Either all 4 are IT trainers for now, or ask the Entra admin for an EC trainer group.
3. **Coaches.** No coach group exists. Ask which group to use, or keep coaches unmapped this week.
4. **Clean up the role enum.** `UserRole` and the `users_role_check` constraint still contain `admin` / `super_admin`; the doc says there is no admin role. Also `users.trainer_id` exists but the doc says trainers are not assigned to apprentices.
5. **Email linking.** `MicrosoftAuthController@callback` falls back to matching on `email`; the doc says `azure_id` only. Remove the fallback once the sync provisions users.

## Monday — mapping resolver

- [ ] Add `apprenticeships.code` (`it` / `ec`, unique) + seed both rows (the table only has `name` today).
- [ ] `RoleResolver` (in `app/Services/Microsoft/`): builds `azure_id => ['role' => UserRole, 'apprenticeship' => 'it'|'ec'|null]` from `MappingRolesService`.
  - A person in more than one role group → conflict, logged, not mapped.
  - Graph failure → return `null` (fail open: keep stored roles).
- [ ] Remove `admin` / `super_admin` from `UserRole` + migration that replaces `users_role_check` (after decision 4).
- [ ] Pest tests with `Http::fake()` on the three group responses: IT apprentice, EC apprentice, trainer, conflict, Graph 500.

## Tuesday — sync users

- [ ] `app:sync-entra-roles` command: upsert on `azure_id` (name, email, role, `apprenticeship_id`, `is_active = true`); anyone with an `azure_id` no longer in any group → `is_active = false`. Never delete.
- [ ] Schedule it daily (`routes/console.php`).
- [ ] Login (`MicrosoftAuthController@callback`): resolve the single user through the same resolver; refuse the login when they have no role or a conflict.
- [ ] `EnsureAzureAccountIsActive`: re-check role on the same 15 min cache and update it in place; log out if the role is gone.
- [ ] Clear `coach_id` on apprentices when their coach loses the role (if coaches are mapped).
- [ ] Tests: provision before first login, deactivate, reactivate, role change mid-session.

## Wednesday — permissions (server side)

- [ ] Helpers on `User`: `isApprentice()`, `isTrainer()`, `isCoach()`, `canSeeApprentice(User $apprentice)` (coach: all; trainer: same `apprenticeship_id`; apprentice: self).
- [ ] Policies: `GradePolicy`, `ProjectPolicy`, `CommentPolicy`, `UserPolicy` (viewing an apprentice) following the tables in `role_permissions.md`. Deactivated apprentice = read-only.
- [ ] Role middleware (`role:apprentice`, `role:trainer,coach`) registered in `bootstrap/app.php`.
- [ ] Group `routes/web.php`:
  - apprentice: `grades.create`, `portfolio.*`
  - trainer + coach: `apprentisdashboard`, viewing an apprentice's grades/portfolio
  - `administration`: decide who (no admin role exists)
- [ ] Feature tests: each role hitting each route (allowed → 200, forbidden → 403), trainer IT on an EC apprentice → 403.

## Thursday — permission-based views

- [ ] Share on Inertia (`HandleInertiaRequests::share`): `auth.user.role`, `auth.user.apprenticeship`, and a `can` map (`createGrade`, `viewDashboard`, `comment`, …) computed from the policies. Frontend never re-derives rules.
- [ ] Type it in `resources/js/types` so `vue-tsc` checks usages.
- [ ] Sidebar/nav: show only links the role can open.
- [ ] `Home.vue`: apprentice → own grades; trainer/coach → redirect or link to `ApprentisDashboard`.
- [ ] Hide comment box for apprentices, hide edit/delete on others' comments, hide actions on deactivated apprentices.
- [ ] Replace the hardcoded demo data in `routes/web.php` (`grades.show`) only where needed to test the views.

## Friday — buffer, checks, PR

- [ ] Test the full flow with real accounts: IT apprentice, EC apprentice, trainer (and coach if mapped).
- [ ] `composer ci:check` + `pnpm check` + `pnpm types:check` green.
- [ ] Update `role_permissions.md` "Open Questions" + `docs/adr/ADR.md` with the groups-vs-app-roles decision.
- [ ] Remove the `azure:*` test commands or keep them as documented debug tools.
- [ ] Open PR from `azure/roles`, small commits per step.

## Out of scope this week

- Sector change confirmation page (`pending_apprenticeship_id`).
- Coach self-assign / de-assign.
- Email notifications.
- Pagination of group members (`@odata.nextLink`, only needed above 999 members).
