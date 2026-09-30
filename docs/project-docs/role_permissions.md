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

| Permission | Apprentice | Coach | Trainer |
|---|---|---|---|
| Log in with email/password | Y | Y | Y |
| Stay logged in across reload/tabs/devices | Y | Y | Y |
| Log out | Y | Y | Y |
| Change own password | Y | Y | Y |
| Set own password on first login (temp password) | Y | Y | Y |
| Access pages/actions outside own role's permissions | N | N | N |

## Apprentice Profile

| Permission | Apprentice (own grades) | Coach | Trainer |
|---|---|---|---|
| Upload scanned PDF test | Y | N | N |
| Submit grade (subject, value, date, oral-exam flag) | Y | N | N |
| Edit own submitted grade | Y | N | N |
| Delete own submitted grade | Y | N | N |
| View own grades, averages, PDFs, comments | Y | — | — |
| Filter own grades by subject | Y | — | — |
| Access another apprentice's grades/PDFs | N | — | — |

## Coaching Assignment

| Permission | Coach | Trainer |
|---|---|---|
| Assign self as coach of an apprentice with no coach | N | N |
| Assign self as coach of an apprentice who already has a coach | N | N |
| De-assign self from own coached apprentice | N | N |
| De-assign another coach's apprentice | N | N |
| Assign/de-assign a deactivated apprentice | N | N |

## Grade Submission & My Grade Record

| Permission | Apprentice | Coach | Trainer |
|---|---|---|---|
| Leave a comment on a grade | N | Y (assigned) | Y (assigned) |
| Edit own comment | — | Y | Y |
| View comments on own/assigned grades | Y | Y | Y |

## Notifications

| Permission | Apprentice | Coach | Trainer |
|---|---|---|---|
| Receive email when assigned apprentice adds a grade | — | Y | Y |
| Receive email when a coach/trainer comments on own grade | Y | — | — |
| Receive email when a notified grade is deleted | — | Y | Y |
| Disable own email notifications | — | Y | Y |

## Training Portfolio

| Permission | Apprentice (own portfolio) | Coach | Trainer |
|---|---|---|---|
| Add/edit/delete/reorder a project | Y | N | N |
| Select IT skills from catalog for a project | Y | N | N |
| View HTML preview / export portfolio as PDF | Y | — | — |
| View assigned apprentice's portfolio | N | RO (assigned) | RO (assigned) |
| Leave a comment on a project | N | Y (assigned) | Y (assigned) |
| View comments left on own projects | Y | — | — |
| Access a deactivated apprentice's portfolio | — | Y (if was assigned) | Y (if was assigned) |
