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

| Permission | Apprentice | Trainer | Coach |
|---|---|---|---|
| Log in with Microsoft account | Y | Y | Y |
| Stay logged in across reload/tabs/devices | Y | Y | Y |
| Log out | Y | Y | Y |
| Access pages/actions outside own role's permissions | N | N | N |

## Apprentice Profile

| Permission | Apprentice | Trainer | Coach |
|---|---|---|---|
| Declare own MP (maturité professionnelle) status — EC only | Y | N | N |
| View an apprentice's MP status | Y (own) | RO (sector) | RO (all) |
| Confirm own apprenticeship change (see [Sector change](#sector-change)) | Y | — | — |

## Coaching Assignment

| Permission | Apprentice | Trainer | Coach |
|---|---|---|---|
| Assign self as coach of an apprentice with no coach | N | N | Y |
| Assign self as coach of an apprentice who already has a coach | N | N | N |
| De-assign self from own coached apprentice | N | N | Y |
| De-assign another coach's apprentice | N | N | N |
| Assign/de-assign a deactivated apprentice | N | N | N |

## Grade Submission & My Grade Record

| Permission | Apprentice (own grades) | Trainer | Coach |
|---|---|---|---|
| Upload scanned PDF test | Y | N | N |
| Submit grade (subject, value, date, oral-exam flag) | Y | N | N |
| Edit own submitted grade | Y | N | N |
| Delete own submitted grade | Y | N | N |
| View own grades, averages, PDFs, comments | Y | — | — |
| Filter own grades by subject | Y | — | — |
| Access another apprentice's grades/PDFs | N | — | — |

## Review by Trainer/Coach

| Permission | Trainer | Coach |
|---|---|---|
| View dashboard of active apprentices | Y (sector) | Y (all) |
| View archived (deactivated) apprentices | RO (sector) | RO (all) |
| View apprentice's detailed grades grouped by subject + averages | RO (sector) | RO (all) |
| View scanned PDF of apprentice's test | RO (sector) | RO (all) |
| Edit/delete an apprentice's grade | N | N |
| Access an apprentice outside their scope | N (other sector) | — |
| View comments left by trainers/coaches | Y (sector) | Y (all) |

## Feedback / Comments (on grades and projects)

| Permission | Apprentice | Trainer | Coach |
|---|---|---|---|
| Leave a comment on a grade | N | Y (sector) | Y (all) |
| Leave a comment on a deactivated apprentice's grade/project | N | N | N |
| Edit own comment | — | Y | Y |
| Delete own comment (hard delete) | — | Y | Y |
| Delete someone else's comment | N | N | N |
| View comments on own/visible grades | Y | Y | Y |

## Notifications

| Permission | Apprentice | Trainer | Coach |
|---|---|---|---|
| Receive email (PDF attached) when an apprentice adds a grade | — | Y (sector) | Y (own coached apprentices) |
| Receive email when a trainer/coach comments on own grade | Y | — | — |
| Receive email when a grade is deleted | — | Y (sector) | Y (own coached apprentices) |
| Disable own email notifications | — | Y | Y |

Delete-email recipients are re-queried at delete time (current coach + active trainers of the sector), not stored per grade.

## Training Portfolio

| Permission | Apprentice (own portfolio) | Trainer | Coach |
|---|---|---|---|
| Add/edit/delete/reorder a project | Y | N | N |
| Select IT skills from catalog for a project | Y | N | N |
| View HTML preview / export portfolio as PDF | Y | — | — |
| View apprentice's portfolio | N | RO (sector) | RO (all) |
| Leave a comment on a project | N | Y (sector) | Y (all) |
| View comments left on own projects | Y | — | — |
| Access a deactivated apprentice's portfolio | — | RO (sector) | RO (all) |

## Role Sync

- **Microsoft is the source of truth for roles and sections.** The role is an Entra **app role**; the section is membership of a **section group** (`Section-IT` / `Section-EC`). See `azure_groups.md`.
- **Exactly one role per person.** Having no known app role, or more than one, refuses the login (and is logged). Roles are mutually exclusive: nobody is both coach and trainer.
- **Exactly one section for apprentices and trainers.** No section group, or both, refuses the login. A coach's section group is ignored.
- **Role and sector are stored separately.** `users.role` is `apprentice | trainer | coach` (`App\Enums\UserRole`). The sector is `users.apprenticeship_id` → `apprenticeships`, matched on `apprenticeships.code` (`it` / `ec`) via the section group's Object ID. Coaches get `NULL`.
- **Sync runs in three places, through the same mapper:**
  - **Login** — syncs role, sector and `is_active`.
  - **Middleware** (`EnsureAzureAccountIsActive`, every 15 min per session) — re-checks the account and its role; updates it in place, or logs the user out if they no longer have a role.
  - **Daily job** (`app:sync-entra-roles`) — provisions every user assigned to an app role (upsert on `azure_id`, before their first login), sets `is_active = false` on anyone no longer assigned, and reactivates anyone assigned again.
- **Graph outage fails open:** if the role cannot be resolved, the stored role is kept and nobody is logged out.
- **Users are matched on `azure_id` only** (Entra object ID). No email-based account linking, no default role.
- **Trainers are not assigned to apprentices.** Their access is decided by sector alone.
- **Coaching is a database relationship** (`coach_id` on the apprentice). It drives coach notifications, not access: coaches can see all apprentices.
  - A coach can only self-assign an apprentice with no coach (atomic `UPDATE … WHERE coach_id IS NULL`); taking over requires the current coach to de-assign first.
  - When a coach loses the coach role or is deactivated, `coach_id` is cleared on all their apprentices.
- **Authorization is enforced server-side** (gates/policies). Role checks in the frontend only hide UI.
- **Trust model for now:** coaches assign/de-assign themselves and apprentices declare their MP status, with no approval step. A request/validation flow may be layered on later as app logic, with no new Entra role.

## MP Status

- Only EC apprentices have an MP variant (`docs/adr/ADR.md`). The EC apprentice declares it with a checkbox (`users.is_mp`) and can change it later.
- `is_mp` is `NULL` for IT apprentices, trainers and coaches.

## Sector Change

When an apprentice is moved to the other section group (e.g. `Section-EC` → `Section-IT`):

1. The sync never deletes anything: it stores the new apprenticeship in `users.pending_apprenticeship_id`.
2. On the next login, the apprentice is blocked on a confirmation page: "Your apprenticeship changed from EC to IT. Your N EC grades will be permanently deleted."
3. On confirmation, their grades (with PDFs, comments and `evaluation_results`) are deleted and `apprenticeship_id` switches. **Projects are kept.**

An apprentice with no grades switches sector directly. See `docs/adr/ADR.md` for why this is an exception to "history is never deleted".

## Deactivated Apprentices

- `is_active = false` is set by the sync when the person no longer has an app role. Users are never deleted.
- Hidden from the default dashboard list, available under an **Archived** filter.
- Grades, PDFs and portfolio stay read-only under the same scope rules (sector for trainers, all for coaches). No new comments, no coach assign/de-assign. `coach_id` is kept for history.
- Re-adding the person to the Entra group reactivates them unchanged.

## Open Questions

- **How the middleware and daily job resolve role and section.** At login the ID token carries the `roles` and `groups` claims, but the middleware and the daily job have no token and must use Graph. `/users/{id}/appRoleAssignments` only lists direct assignments, not roles inherited through a group, so it cannot be used as-is when app roles are assigned to groups. Proposed: one resolver used everywhere — app roles from `servicePrincipals/{id}/appRoleAssignedTo` (expanding assigned groups), sections from `/users/{id}/transitiveMemberOf` (login, middleware) and `/groups/{id}/transitiveMembers` (daily job) — ignoring the token claims so the three paths cannot disagree. Not yet decided.
