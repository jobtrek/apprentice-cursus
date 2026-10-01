# Roles and Permissions

The only document on who can do what, and how people get their role. Design decisions and their reasons are in `docs/adr/ADR.md` (2026-09-30).

## In short

- There are **3 roles**: Apprentice, Trainer, Coach. There is no admin in production. A local-only `admin` role exists for development (see `docs/permissions_guide.md`, section 6).
- Your role comes from the **Microsoft Entra group** you are in. Nobody sets roles inside the app.
- Everyone logs in with **Microsoft**. Password login only exists on a developer's machine.
- **Apprentices** manage their own grades and portfolio. **Trainers and coaches** read and comment on the apprentices they supervise. They never change an apprentice's data.

## The roles

| Role       | How you get it             | Who you supervise                                              |
| ---------- | -------------------------- | -------------------------------------------------------------- |
| Apprentice | IT or EC apprentices group | Nobody. You only see your own data                             |
| Trainer    | IT or EC trainers group    | Every apprentice of your section                               |
| Coach      | Coaches group (no section) | Apprentices whose coach you are (IT and EC)                    |

Accounts are created by the daily account sync (`azure:sync`), not at login. A person not synced yet is refused until the next run.

The table lists the production roles. The `admin` role (local development only) is not one of them: it passes every check when `APP_ENV=local`, grants nothing elsewhere, and no Entra group maps to it.

IT and EC apprentices have the same permissions. Only their grade tree differs (`grade_tree_IT.md`, `grade_tree_EC.md`).

## What each role can do

Y = yes, N = no, RO = read-only. Anything on a **deactivated** apprentice is read-only.

| Action                                                         | Apprentice        | Trainer           | Coach             |
| -------------------------------------------------------------- | ----------------- | ----------------- | ----------------- |
| Submit, edit, delete own grades and PDF scans                  | Y                 | N                 | N                 |
| See own grades, averages and comments                          | Y                 | –                 | –                 |
| See a supervised apprentice's grades and scans                 | N                 | RO                | RO                |
| Comment on a supervised apprentice's grade/project             | N                 | Y                 | Y                 |
| Edit or delete a comment                                       | N                 | Own only          | Own only          |
| Manage own portfolio (projects, skills, PDF export)            | Y                 | N                 | N                 |
| See a supervised apprentice's portfolio                        | N                 | RO                | RO                |
| See the apprentices list                                       | N                 | Y                 | Y                 |
| Assign self as coach of an apprentice with no coach            | N                 | N                 | Y                 |
| Get an email when a supervised apprentice adds/deletes a grade | –                 | Y (not built yet) | Y (not built yet) |
| Get an email when someone comments on own grade                | Y (not built yet) | –                 | –                 |

After login, apprentices land on their grades, trainers and coaches on the apprentices list.

## How a user gets their role

1. The user logs in with Microsoft. The app asks Microsoft which of the mapped groups they are in.
2. **Exactly one group:** the account is created (first login) or updated with that group's role and section.
3. **No group, several groups, or disabled in Entra:** the login is refused. An existing account is set inactive; it is never deleted.
4. While logged in, this check runs again **every 15 minutes**. If access was removed, the session ends. If Microsoft is down, the user keeps working and the check retries a minute later.

Other rules:

- Accounts are matched on the Microsoft account id, never on email.
- An account only exists after its first login.
- Adding the person back to one group reactivates their account at the next login.
- Moving a trainer to the other trainers group changes their section at the next sync or login.
- Moving an apprentice from IT to EC (or back) does not change their section yet: that needs a confirmation page that is not built. A warning is logged.

## Entra setup (for the Microsoft administrator)

| Group          | `.env` variable                  | Role       | Section                   |
| -------------- | -------------------------------- | ---------- | ------------------------- |
| IT apprentices | `MICROSOFT_GROUP_APPRENTICES_IT` | Apprentice | Informaticien·ne CFC      |
| EC apprentices | `MICROSOFT_GROUP_APPRENTICES_EC` | Apprentice | Employé·e de commerce CFC |
| IT trainers    | `MICROSOFT_GROUP_TRAINER_IT`     | Trainer    | Informaticien·ne CFC      |
| EC trainers    | `MICROSOFT_GROUP_TRAINER_EC`     | Trainer    | Employé·e de commerce CFC |

- [ ] Each group above exists (nested members count).
- [ ] App registration has Graph permissions `User.Read.All` and `GroupMember.Read.All`, **with admin consent**.
- [ ] Redirect URIs: `http://localhost/auth/microsoft/callback` (dev) and the production callback.
- [ ] Send the maintainers, securely: tenant id, client id, client secret and its expiry, and the object id of each group.

## For developers

- Code checks **permissions, never role names**. The list of permissions per role is `App\Enums\Permission::byRole()`. To change what a role can do, edit it there and re-run `RolesAndPermissionsSeeder`.
- "Supervised" means `User::supervises($apprentice)`: trainer = same section, coach = apprentice's `coach_id` is theirs.
- Assigning a coach: a coach takes an active apprentice with no coach (`coaching.assign-self`, `ApprenticeController::assign`). Setting, changing or removing any apprentice's coach needs `supervision.manage`, which no production role has: only the local admin can do it, from the coach select of the apprentices list (`SupervisionController`).
- Policies (`GradePolicy`, `ProjectPolicy`, `CommentPolicy`, `UserPolicy`) add the per-record checks: supervision, author only, active apprentice only.
- Routes use `can:` middleware. The frontend reads the `auth.can` flags shared by `HandleInertiaRequests` and never re-derives rules.
- Local login (password `password`): `admin@example.com` (local-only admin, everything), `coach@example.com` (coach), `trainer@example.com` (IT trainer, Bastien Nicoud), `trainer-ec@example.com` (EC trainer), `apprentice-it@example.com` (IT apprentice), `apprentice-ec@example.com` (EC apprentice).
- The local admin comes from Spatie's `Gate::before` in `AppServiceProvider`, not from `byRole()`. Direct `hasPermissionTo()` calls bypass the Gate, so `User::homeRoute()` and `User::supervises()` handle it explicitly. Guide: `docs/permissions_guide.md`.
