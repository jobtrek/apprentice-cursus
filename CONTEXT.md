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

## Grade entry

**Manual entry**:
An apprentice adds a grade by typing every field themselves.
_Avoid_: Normal upload

**Automatic reading**:
An apprentice uploads a scanned test and lets the system read its Test type and Grade. Optional; Manual entry is always available.
_Avoid_: OCR, auto-detection, AI upload

**Reading**:
One source's answer for a field of a scanned test (Gemini, Jev, the Module rule). The Test type is the Module rule's code, else Jev's pick; Gemini's own subject is not used.
_Avoid_: Prediction, guess

**Pre-fill**:
The values an Automatic reading puts in the form before the apprentice confirms. Never stored as a grade on its own.
_Avoid_: Result, auto-grade

**Flagged**:
An Automatic reading the system is unsure about: the grade is missing, Jev's confidence in the Test type is below 0.5, or no page could be read. It is still offered for Confirmation, pre-filled, with a warning and every Reading shown. A reading whose job failed falls back to Manual entry with the scan kept.
_Avoid_: Low confidence, error

**Confirmation**:
The apprentice accepting or correcting a Pre-fill. Nothing from an Automatic reading becomes a grade without it.
_Avoid_: Validation, approval

**Processing consent**:
The checkbox an apprentice ticks to accept that their scan is sent to Google and TypeSafe. Required before every Automatic reading.
_Avoid_: Opt-in, data collection consent
