# Role Permissions

Legend: Y = Allowed, N = Not allowed, RO = Read-only

## Authentication

| Permission | Apprentice | Coach | Trainer | Admin | Super-Admin |
|---|---|---|---|---|---|
| Log in with email/password | Y | Y | Y | Y | Y |
| Stay logged in across reload/tabs/devices | Y | Y | Y | Y | Y |
| Log out | Y | Y | Y | Y | Y |
| Change own password | Y | Y | Y | Y | Y |
| Set own password on first login (temp password) | Y | Y | Y | Y | Y |
| Access pages/actions outside own role's permissions | N | N | N | N | N |

## Administration

| Permission | Apprentice | Coach | Trainer | Admin | Super-Admin |
|---|---|---|---|---|---|
| Create apprentice/coach/trainer accounts | N | N | N | Y | Y |
| Edit/deactivate apprentice/coach/trainer accounts | N | N | N | Y | Y |
| Create administrator accounts | N | N | N | N | Y |
| Create super-administrator accounts | N | N | N | N | Y |
| Deactivate administrator/super-administrator accounts (confirmation required) | N | N | N | N | Y |
| Deactivate own (super-)admin account | N | N | N | N | N |
| Assign coach to apprentice | N | N | N | Y | Y |
| Assign trainer to apprentice (by section) | N | N | N | Y | Y |
| Define apprentice year/section | N | N | N | Y | Y |
| Add/edit/delete subjects | N | N | N | Y | Y |
| Deactivate a subject (delete blocked if grades exist) | N | N | N | Y | Y |
| Add/edit/delete IT skills catalog entries | N | N | N | Y | Y |
| View list of all apprentices (coach/trainer/year/section) | N | N | N | Y | Y |
| View admin dashboard/overview stats | N | N | N | Y | Y |

## Grade Submission & My Grade Record

| Permission | Apprentice (own grades) | Coach | Trainer | Admin |
|---|---|---|---|---|
| Upload scanned PDF test | Y | N | N | N |
| Submit grade (subject, value, date, oral-exam flag) | Y | N | N | N |
| Edit own submitted grade | Y | N | N | N |
| Delete own submitted grade | Y | N | N | N |
| View own grades, averages, PDFs, comments | Y | — | — | — |
| Filter own grades by subject | Y | — | — | — |
| Access another apprentice's grades/PDFs | N | — | — | — |

## Review by Coach/Trainer (assigned apprentices only)

| Permission | Coach | Trainer | Admin |
|---|---|---|---|
| View list of assigned apprentices | Y | Y | — |
| View assigned apprentice's grades grouped by subject + averages | RO | RO | Y (any apprentice) |
| View scanned PDF of assigned apprentice's test | RO | RO | — |
| Edit/delete an apprentice's grade | N | N | N |
| Access an apprentice not assigned to them | N | N | — |
| View comments left by the other role (coach ↔ trainer) | Y | Y | — |

## Feedback / Comments (on grades)

| Permission | Apprentice | Coach | Trainer | Admin |
|---|---|---|---|---|
| Leave a comment on a grade | N | Y (assigned) | Y (assigned) | N |
| Edit own comment | — | Y | Y | N |
| Delete any comment (moderation) | N | N | N | Y |
| View comments on own/assigned grades | Y | Y | Y | — |

## Notifications

| Permission | Apprentice | Coach | Trainer | Admin |
|---|---|---|---|---|
| Receive email when assigned apprentice adds a grade | — | Y | Y | — |
| Receive email when a coach/trainer comments on own grade | Y | — | — | — |
| Receive email when a notified grade is deleted | — | Y | Y | — |
| Disable own email notifications | — | Y | Y | — |

## Training Portfolio

| Permission | Apprentice (own portfolio) | Coach | Trainer | Admin |
|---|---|---|---|---|
| Add/edit/delete/reorder a project | Y | N | N | N |
| Select IT skills from catalog for a project | Y | N | N | N |
| View HTML preview / export portfolio as PDF | Y | — | — | — |
| View assigned apprentice's portfolio | N | RO (assigned) | RO (assigned) | RO (any) |
| Leave a comment on a project | N | Y (assigned) | Y (assigned) | Y (any) |
| View comments left on own projects | Y | — | — | — |
| Access a deactivated apprentice's portfolio | — | Y (if was assigned) | Y (if was assigned) | Y |