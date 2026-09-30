# Role Permissions

Legend: Y = Allowed, N = Not allowed, RO = Read-only

Roles are defined by membership in Microsoft Entra ID security groups (see `azure_groups.md`) and synced into the app (see [Role sync](#role-sync)). There is no admin role: accounts are managed in Entra, subjects and the IT skills catalog are seeded.

| Role          | Entra group    | Who                              | Scope                                           |
| ------------- | -------------- | -------------------------------- | ----------------------------------------------- |
| Apprentice EC | EC apprentices | EC apprentices in training       | Own data, EC grade tree                         |
| Apprentice IT | IT apprentices | IT (dev) apprentices in training | Own data, IT grade tree                         |
| Trainer EC    | _(none yet)_   | EC trainers (formateurs)         | All apprentices of own section (EC)             |
| Trainer IT    | Trainers       | IT trainers (formateurs)         | All apprentices of own section (IT)             |
| Coach         | _(none yet)_   | Coaches                          | Assigned apprentices (self-assigned, EC and IT) |

Apprentice EC and Apprentice IT have the same permissions. They differ only in the grade tree, pages and average calculations shown (`grade_tree_EC.md` vs `grade_tree_IT.md`). Same for Trainer EC and Trainer IT, which differ only in section. The tables below use "Apprentice" and "Trainer" for both variants; "own section" means the trainer's own apprenticeship, "assigned" means the apprentices whose coach is the signed-in coach.

Trainer EC is part of the matrix but no EC trainer group exists yet: every trainer is mapped to the IT section, and coaches have no Entra group (see `azure_groups.md`).

## Role sync

A user has exactly one role, stored only as a Spatie role (`apprentice`, `coach`, `trainer`); there is no `users.role` column. The role is derived from the single mapped Entra group at each login and re-checked while signed in. Roles, permissions and the role to permission matrix come from `App\Enums\Permission::byRole()` and are created by a migration (and re-synced by `RolesAndPermissionsSeeder`). Code checks permissions, never role names.

## Authentication

| Permission                                          | Apprentice             | Coach                  | Trainer                |
| --------------------------------------------------- | ---------------------- | ---------------------- | ---------------------- |
| Log in with Microsoft (Entra SSO)                   | Y                      | Y                      | Y                      |
| Log in with email/password                          | Local development only | Local development only | Local development only |
| Stay logged in across reload/tabs/devices           | Y                      | Y                      | Y                      |
| Log out                                             | Y                      | Y                      | Y                      |
| Access pages/actions outside own role's permissions | N                      | N                      | N                      |

Passwords are not managed by the app: credentials live in Entra. Password login is a testing tool that exists only in the `local` environment, for the seeded local coach (`admin@example.com`) and IT trainer (`trainer@example.com`). There is no "set password on first login" flow. A `PUT profile/password` endpoint still exists, but no page uses it and SSO accounts have no local password.

## Apprentice Profile

| Permission                                          | Apprentice (own grades) | Coach | Trainer |
| --------------------------------------------------- | ----------------------- | ----- | ------- |
| Upload scanned PDF test                             | Y                       | N     | N       |
| Submit grade (subject, value, date, oral-exam flag) | Y                       | N     | N       |
| Edit own submitted grade                            | Y                       | N     | N       |
| Delete own submitted grade                          | Y                       | N     | N       |
| View own grades, averages, PDFs, comments           | Y                       | —     | —       |
| Filter own grades by subject                        | Y                       | —     | —       |
| Access another apprentice's grades/PDFs             | N                       | —     | —       |

## Coaching Assignment

| Permission                                                    | Coach                                          | Trainer |
| ------------------------------------------------------------- | ---------------------------------------------- | ------- |
| Assign self as coach of an apprentice with no coach           | Y (permission only, route not implemented yet) | N       |
| Assign self as coach of an apprentice who already has a coach | N                                              | N       |
| De-assign self from own coached apprentice                    | N                                              | N       |
| De-assign another coach's apprentice                          | N                                              | N       |
| Assign/de-assign a deactivated apprentice                     | N                                              | N       |

## Grade Submission & My Grade Record

| Permission                                               | Apprentice | Coach        | Trainer         |
| -------------------------------------------------------- | ---------- | ------------ | --------------- |
| Leave a comment on a grade (active apprentice only)      | N          | Y (assigned) | Y (own section) |
| Edit/delete own comment (while the apprentice is active) | —          | Y            | Y               |
| View comments on own/supervised grades                   | Y          | Y (assigned) | Y (own section) |

## Notifications

| Permission                                               | Apprentice | Coach | Trainer |
| -------------------------------------------------------- | ---------- | ----- | ------- |
| Receive email when assigned apprentice adds a grade      | —          | Y     | Y       |
| Receive email when a coach/trainer comments on own grade | Y          | —     | —       |
| Receive email when a notified grade is deleted           | —          | Y     | Y       |
| Disable own email notifications                          | —          | Y     | Y       |

## Training Portfolio

| Permission                                  | Apprentice (own portfolio) | Coach               | Trainer                   |
| ------------------------------------------- | -------------------------- | ------------------- | ------------------------- |
| Add/edit/delete/reorder a project           | Y                          | N                   | N                         |
| Select IT skills from catalog for a project | Y                          | N                   | N                         |
| View HTML preview / export portfolio as PDF | Y                          | —                   | —                         |
| View supervised apprentice's portfolio      | N                          | RO (assigned)       | RO (own section)          |
| Leave a comment on a project                | N                          | Y (assigned)        | Y (own section)           |
| View comments left on own projects          | Y                          | —                   | —                         |
| Access a deactivated apprentice's portfolio | —                          | Y (if was assigned) | Y (if was in own section) |
