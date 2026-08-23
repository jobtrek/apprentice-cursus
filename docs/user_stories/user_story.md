# User Story Backlog

**This file is the source of truth for scope.** Where any spec disagrees with it, this file wins.

| | |
|---|---|
| **Design spec — grades** | `docs/spec-d/spec.md` |
| **Design spec — dossier** | `docs/dossier-formation-part/spec.md` |
| **Assembly view** | `docs/spec-d/architecture.md` |
| **Stack decision, pending** | `docs/frontend-decision.md` |

Cards only — no acceptance criteria. Those come in a later pass.

**One ID per story.** `GR-xx` is the only identifier for a grades story; `AUTH-xx`, `ADMIN-xx` and `DOS-xx` likewise.
Nothing else in the repository defines a competing scheme. Cite these IDs and no others.

---

## Auth (AUTH)

> **Resolved:** roles are a `users.role` enum plus a `hasOne` profile row, using Laravel's built-in auth from the
> starter kit. No `laravel-permission` package. See the Auth row of the tech-stack table in `docs/spec-d/spec.md`.

* **[AUTH-01] Login with email and password:** As a user (apprenti, coach, formateur, or admin), I want to log in with my email and password, so that I can access the features relevant to my role.
* **[AUTH-02] Stay logged in across sessions:** As a logged-in user, I want to remain logged in when I reload or navigate the app, so that I don't have to log in repeatedly.
* **[AUTH-03] Log out:** As a logged-in user, I want to log out of the app, so that I can end my session securely on shared devices.
* **[AUTH-04] Change my password:** As a logged-in user, I want to change my password from my settings at any time, so that I can keep my account secure or memorable to me.
* **[AUTH-05] Guidance when locked out:** As a user who forgot my password, I want to see a message telling me to contact my administrator, so that I know how to regain access since there's no self-service reset.
* **[AUTH-06] Role-based access restriction:** As an administrator, I want each role restricted to only the pages and actions appropriate to it, so that apprentices, coaches, and formateurs can't see or act on data outside their permissions.

---

## Admin (ADMIN)

> **Dependencies:** Depends on AUTH-01 (login) to be usable.

* **[ADMIN-01] Create user accounts:** As an administrator, I want to create accounts (email + initial password) for apprentices, coaches, and formateurs, so that they can log in without self-registering.
* **[ADMIN-02] Edit or deactivate an account:** As an administrator, I want to edit or deactivate an existing account, so that I can correct mistakes or remove access for someone who has left.
* **[ADMIN-03] Assign a coach to an apprenti:** As an administrator, I want to assign one coach to each apprenti, so that the right coach can follow their progress.
* **[ADMIN-04] Assign a formateur to an apprenti:** As an administrator, I want to assign the correct formateur (based on section) to each apprenti, so that the right formateur can review their work.
* **[ADMIN-05] Set an apprenti's year and section:** As an administrator, I want to set each apprenti's current year and section, so that the system applies the correct rules and content to their profile.
* **[ADMIN-06] Define the academic calendar:** As an administrator, I want to define date ranges for each year and semester (e.g. Year 2 Semester 2 = Dec 23–June 24), so that the app automatically knows which semester an apprenti is in when they upload a grade. *(Note: Grades track [GR-04] depends on this.)*
* **[ADMIN-07] Manage the list of matières:** As an administrator, I want to add, edit, or remove matières available for grades, so that the grade section reflects the subjects actually taught. *(Note: Grades track [GR-01/02] depends on this.)*
* **[ADMIN-08] Manage the compétences catalog:** As an administrator, I want to add, edit, or remove entries in the compétences informatique catalog, so that apprentices select accurate, standardized compétences for their projects. *(Note: Dossier track [DOS-02] depends on this.)*
* **[ADMIN-09] View all apprentices and their assignments:** As an administrator, I want to see a list of all apprentices with their assigned coach, formateur, year, and section, so that I can verify assignments and spot anyone missing one.

---

## Grades (GR)

> **Dependencies:** AUTH-01 (login), AUTH-06 (role-based access), ADMIN-01 (accounts), ADMIN-03/04 (coach + formateur assignment), ADMIN-05 (year + section), ADMIN-06 (academic calendar), ADMIN-07 (matière list).
>
> Grouped by topic for readability. **IDs are stable and not in reading order** — GR-16…27 were added in a later pass and slotted in beside the stories they belong with.

### Capturing a grade

* **[GR-01] Upload a scanned test PDF:** As an apprenti, I want to upload a scanned PDF of my graded test, so that it's stored as part of my official grade record.
* **[GR-02] Select the matière for a grade:** As an apprenti, I want to choose which matière my uploaded test belongs to, so that it's filed under the correct subject.
* **[GR-03] Enter the grade value:** As an apprenti, I want to enter the numeric grade I received, so that it's recorded alongside the scanned PDF.
* **[GR-16] Enter the date of the test:** As an apprenti, I want to give the date the test was sat, so that the app can work out which year and semester it belongs to.
* **[GR-17] Refuse an impossible grade:** As an apprenti, I want a grade outside 1–6, or off the half-point steps, refused when I submit, so that a typo doesn't quietly corrupt my moyenne.
* **[GR-18] Derive the semester from the test date:** As an apprenti, I want the app to work out the year and semester from the date I gave, so that I don't have to know which semester a date falls in. *(Depends on ADMIN-06.)*
* **[GR-04] Automatic PDF renaming:** As an apprenti, I want my uploaded PDF automatically renamed following the firm's convention (year_semester_matiere_grade_firstname_lastname), so that coaches and formateurs can identify files consistently.
* **[GR-19] Protect the stored file:** As an apprenti, I want the stored PDF's URL guarded by the same rules as the grade page, so that knowing a filename isn't enough for someone else to read my test.

### My own record

* **[GR-05] View my own grades by matière:** As an apprenti, I want to see all my submitted grades organized by matière, so that I can track my progress subject by subject.
* **[GR-20] Open a grade and read the scan:** As an apprenti, I want to open a single grade and see the PDF rendered in the page, so that I can check the original without downloading a file.
* **[GR-06] View moyenne per matière:** As an apprenti, I want to see an automatically calculated moyenne for each matière, so that I know how I'm performing in that subject.
* **[GR-07] View overall final moyenne:** As an apprenti, I want to see an overall final moyenne calculated from my matière moyennes, so that I have a single number reflecting my overall performance. *(Blocked: the formula is deliberately undefined — see Open Question #1 in `docs/spec-d/spec.md`. Do not ship a guessed weighting.)*
* **[GR-08] Edit a submitted grade:** As an apprenti, I want to edit a grade I previously submitted, so that I can correct mistakes.
* **[GR-21] Keep the filename correct after an edit:** As an apprenti, I want the stored file renamed again when I change the matière or the grade, so that the filename never disagrees with the record.
* **[GR-09] Delete a submitted grade:** As an apprenti, I want to delete a grade I previously submitted, so that I can remove an entry added by mistake.
* **[GR-22] Nobody else reaches my record:** As an apprenti, I want another apprenti's grade to be refused outright rather than merely hidden from me, so that my record is genuinely private.

### Review by a coach or formateur

* **[GR-10] View assigned apprentices' grades (coach):** As a coach, I want to view the grades and PDFs of the apprentices assigned to me, so that I can follow their academic progress.
* **[GR-11] View assigned apprentices' grades (formateur):** As a formateur, I want to view the grades and PDFs of the apprentices assigned to me, so that I can follow their academic progress. *(Wording: "in my section" in an earlier draft — read as "assigned to me". See Open Question #9 in `docs/spec-d/spec.md`.)*
* **[GR-23] Stay inside my own group:** As a coach or formateur, I want an apprenti who isn't assigned to me to be refused, so that the assignment boundary is real and not merely a link I wasn't shown.
* **[GR-24] Review without editing:** As a coach or formateur, I want grade data to be read-only for me, so that the apprenti's record stays theirs and there's no question who entered what.

### Feedback

* **[GR-12] Comment on a grade:** As a coach or formateur, I want to leave a comment on a specific grade, so that I can give feedback, especially on a low grade.
* **[GR-13] View comments on my grades:** As an apprenti, I want to see comments left by a coach or formateur on my grades, so that I understand their feedback.
* **[GR-25] Know who said what:** As an apprenti, I want each comment to show its author and when it was written, so that I know whether it came from my coach or my formateur.
* **[GR-26] See new comments without reloading:** As an apprenti, I want the comment thread to pick up new feedback on its own, so that I'm not refreshing the page to check. *(Polling for MVP; WebSockets are a stretch item.)*

### Notifications

* **[GR-14] Email notification on new grade:** As a coach or formateur, I want to receive an automatic email when an apprenti I'm responsible for adds a new grade, so that I know to review it.
* **[GR-27] Attach the scan to the notification:** As a coach or formateur, I want the renamed PDF attached to that email, so that I can glance at the test without logging in first.
* **[GR-15] Email notification on new comment:** As an apprenti, I want to receive an automatic email when a coach or formateur comments on one of my grades, so that I'm notified without checking the app constantly.

---

## Dossier de Formation (DOS)

> **Scope & Dependencies:** Informatique développement d'applications apprentices only for v1. Depends on AUTH-01, ADMIN-01/03/04 (accounts/assignments), ADMIN-08 (compétences catalog).

* **[DOS-01] Add a new project:** As an informatique apprenti, I want to add a project with a title and description, so that I document the work I've completed.
* **[DOS-02] Select compétences acquired:** As an informatique apprenti, I want to select the compétences informatique I acquired on a project from the predefined catalog, so that my dossier reflects standardized skills.
* **[DOS-03] Add multiple projects:** As an informatique apprenti, I want to add multiple projects to my dossier over time, so that it grows into a full training record.
* **[DOS-04] Auto-generate HTML preview:** As an informatique apprenti, I want my project data to automatically populate a pre-designed HTML preview, so that I can see my dossier in the firm's official format without manual formatting.
* **[DOS-05] Design the dossier HTML/CSS template:** As a developer, I want a reusable, professionally designed HTML/CSS template for the dossier preview, so that project data renders consistently into a polished, on-brand document.
* **[DOS-06] Auto-regenerate preview on new project:** As an informatique apprenti, I want the HTML preview to update automatically when I add a new project, so that I never see an outdated version.
* **[DOS-07] Export dossier as PDF:** As an informatique apprenti, I want a button to save my dossier preview as a PDF, so that I have a portable copy I can share or submit.
* **[DOS-08] Edit a project:** As an informatique apprenti, I want to edit a project I previously added, so that I can correct or update its details.
* **[DOS-09] Delete a project:** As an informatique apprenti, I want to delete a project I previously added, so that I can remove an entry that no longer belongs.
* **[DOS-10] View an apprenti's dossier (coach):** As a coach, I want to view the dossier of the apprentices assigned to me, so that I can review their documented projects and compétences.
* **[DOS-11] View an apprenti's dossier (formateur):** As a formateur, I want to view the dossier of the apprentices in my section, so that I can review their documented projects and compétences.
* **[DOS-12] View an apprenti's dossier (admin):** As an administrator, I want to view any apprenti's dossier, so that I can oversee training records across the organization.
* **[DOS-13] Comment on a dossier project:** As a coach, formateur, or administrator, I want to leave a comment on a specific project in an apprenti's dossier, so that I can give feedback on their work.
* **[DOS-14] View comments on my dossier projects:** As an informatique apprenti, I want to see comments left on my dossier projects, so that I understand the feedback I've received.
