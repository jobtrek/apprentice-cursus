# Apprentice Cursus

Grade and training-portfolio tracking for an apprenticeship program, where apprentices record grades and coaches and trainers supervise them.

## Accounts

**Account sync**:
The daily copy of Entra group membership into local accounts. It creates, updates and deactivates every account. Logging in never creates an account.
_Avoid_: Provisioning at login, sign-up, registration

**Mapped group**:
An Entra group that grants one role (and a section) in the app. An account in no mapped group, or in several, has no access.
_Avoid_: Azure role, app role

**Section**:
The apprenticeship program an apprentice or trainer belongs to: IT or EC. Coaches have no section.
_Avoid_: Track, department

## Supervision

**Supervised apprentice**:
An apprentice whose grades and portfolio a supervisor may open. For a trainer, any apprentice in the same section. For a coach, only their coachees.
_Avoid_: Visible apprentice, my apprentices

**Coachee**:
An apprentice who has a given coach assigned to them. A coach becomes an apprentice's coach by assigning themselves.
_Avoid_: Assigned apprentice, student

**Apprentice list**:
The supervisor's entry page. Coaches see every active apprentice in both sections, assigned or not. Trainers see only their own section.
_Avoid_: Apprentice dashboard (when meaning the supervisor's list)
