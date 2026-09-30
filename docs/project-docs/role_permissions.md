# Role Permissions

Legend: Y = Allowed, N = Not allowed, RO = Read-only

Roles are defined in Microsoft Entra ID (see `azure_groups.md`) and synced into the app (see [Role sync](#role-sync)). There is no admin role: accounts are managed in Entra, subjects and the IT skills catalog are seeded.

| Role | Entra app role | Section group | Who | Scope |
|---|---|---|---|---|
| Apprentice EC | `apprentice` | `Section-EC` | EC apprentices in training | Own data, EC grade tree |
| Apprentice IT | `apprentice` | `Section-IT` | IT (dev) apprentices in training | Own data, IT grade tree |
| Trainer EC | `trainer` | `Section-EC` | EC trainers (formateurs) | All EC apprentices |
| Trainer IT | `trainer` | `Section-IT` | IT trainers (formateurs) | All IT apprentices |
| Coach | `coach` | *(none)* | Coaches | All apprentices (EC and IT) |

Apprentice EC and Apprentice IT have the same permissions. They differ only in the grade tree, pages and average calculations shown (`grade_tree_EC.md` vs `grade_tree_IT.md`). Same for Trainer EC and Trainer IT, which differ only in section. The tables below use "Apprentice" and "Trainer" for both variants; "sector" means the trainer's own section.

## Authentication

| Permission | Apprentice | Coach | Trainer | Super-Admin |
|---|---|---|---|---|
| Log in with email/password | Y | Y | Y | Y |
| Stay logged in across reload/tabs/devices | Y | Y | Y | Y |
| Log out | Y | Y | Y | Y |
| Change own password | Y | Y | Y | Y |
| Set own password on first login (temp password) | Y | Y | Y | Y |
| Access pages/actions outside own role's permissions | N | N | N | N |

## Administration

| Permission | Apprentice | Coach | Trainer | Super-Admin |
|---|---|---|---|---|
| Create apprentice/coach/trainer accounts | N | N | N | Y |
| Edit/deactivate apprentice/coach/trainer accounts | N | N | N | Y |
| Create super-administrator accounts | N | N | N | Y |
| Deactivate super-administrator accounts (confirmation required) | N | N | N | Y |
| Deactivate own super-admin account | N | N | N | N |
| Assign coach to apprentice | N | N | N | Y |
| Assign trainer to apprentice (by section) | N | N | N | Y |
| Define apprentice year/section | N | N | N | Y |
| Add/edit/delete subjects | N | N | N | Y |
| Deactivate a subject (delete blocked if grades exist) | N | N | N | Y |
| Add/edit/delete IT skills catalog entries | N | N | N | Y |
| View list of all apprentices (coach/trainer/year/section) | N | N | N | Y |
| View admin dashboard/overview stats | N | N | N | Y |

## Apprentice Profile

| Permission | Apprentice (own grades) | Coach | Trainer | Super-Admin |
|---|---|---|---|---|
| Upload scanned PDF test | Y | N | N | N |
| Submit grade (subject, value, date, oral-exam flag) | Y | N | N | N |
| Edit own submitted grade | Y | N | N | N |
| Delete own submitted grade | Y | N | N | N |
| View own grades, averages, PDFs, comments | Y | — | — | — |
| Filter own grades by subject | Y | — | — | — |
| Access another apprentice's grades/PDFs | N | — | — | — |

## Coaching Assignment

| Permission | Coach | Trainer | Super-Admin |
|---|---|---|---|
| Assign self as coach of an apprentice with no coach | N | N | Y |
| Assign self as coach of an apprentice who already has a coach | N | N | N |
| De-assign self from own coached apprentice | N | N | Y |
| De-assign another coach's apprentice | N | N | N |
| Assign/de-assign a deactivated apprentice | N | N | N |

## Grade Submission & My Grade Record

| Permission | Apprentice | Coach | Trainer | Super-Admin |
|---|---|---|---|---|
| Leave a comment on a grade | N | Y (assigned) | Y (assigned) | N |
| Edit own comment | — | Y | Y | N |
| Delete any comment (moderation) | N | N | N | Y |
| View comments on own/assigned grades | Y | Y | Y | — |

## Notifications

| Permission | Apprentice | Coach | Trainer | Super-Admin |
|---|---|---|---|---|
| Receive email when assigned apprentice adds a grade | — | Y | Y | — |
| Receive email when a coach/trainer comments on own grade | Y | — | — | — |
| Receive email when a notified grade is deleted | — | Y | Y | — |
| Disable own email notifications | — | Y | Y | — |

## Training Portfolio

| Permission | Apprentice (own portfolio) | Coach | Trainer | Super-Admin |
|---|---|---|---|---|
| Add/edit/delete/reorder a project | Y | N | N | N |
| Select IT skills from catalog for a project | Y | N | N | N |
| View HTML preview / export portfolio as PDF | Y | — | — | — |
| View assigned apprentice's portfolio | N | RO (assigned) | RO (assigned) | RO (any) |
| Leave a comment on a project | N | Y (assigned) | Y (assigned) | Y (any) |
| View comments left on own projects | Y | — | — | — |
| Access a deactivated apprentice's portfolio | — | Y (if was assigned) | Y (if was assigned) | Y |
