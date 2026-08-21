
# User Story Backlog

## Auth (AUTH)

### note from thomas: if we use a starter kit this should already be done, only thing needing to add is whether we decided to add a role column to the users table or we use a library like laravel-permissions

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

> **Dependencies:** Depends on AUTH-01, ADMIN-01 (accounts), ADMIN-03/04 (assignments), ADMIN-06 (calendar), ADMIN-07 (matière list).

* **[GR-01] Upload a scanned test PDF:** As an apprenti, I want to upload a scanned PDF of my graded test, so that it's stored as part of my official grade record.
* **[GR-02] Select the matière for a grade:** As an apprenti, I want to choose which matière my uploaded test belongs to, so that it's filed under the correct subject.
* **[GR-03] Enter the grade value:** As an apprenti, I want to enter the numeric grade I received, so that it's recorded alongside the scanned PDF.
* **[GR-04] Automatic PDF renaming:** As an apprenti, I want my uploaded PDF automatically renamed following the firm's convention (year_semester_matiere_grade_firstname_lastname), so that coaches and formateurs can identify files consistently.
* **[GR-05] View my own grades by matière:** As an apprenti, I want to see all my submitted grades organized by matière, so that I can track my progress subject by subject.
* **[GR-06] View moyenne per matière:** As an apprenti, I want to see an automatically calculated moyenne for each matière, so that I know how I'm performing in that subject.
* **[GR-07] View overall final moyenne:** As an apprenti, I want to see an overall final moyenne calculated from my matière moyennes, so that I have a single number reflecting my overall performance. (Calculation formula to be defined in a later pass.)
* **[GR-08] Edit a submitted grade:** As an apprenti, I want to edit a grade I previously submitted, so that I can correct mistakes.
* **[GR-09] Delete a submitted grade:** As an apprenti, I want to delete a grade I previously submitted, so that I can remove an entry added by mistake.
* **[GR-10] View assigned apprentices' grades (coach):** As a coach, I want to view the grades and PDFs of the apprentices assigned to me, so that I can follow their academic progress.
* **[GR-11] View assigned apprentices' grades (formateur):** As a formateur, I want to view the grades and PDFs of the apprentices in my section, so that I can follow their academic progress.
* **[GR-12] Comment on a grade:** As a coach or formateur, I want to leave a comment on a specific grade, so that I can give feedback, especially on a low grade.
* **[GR-13] View comments on my grades:** As an apprenti, I want to see comments left by a coach or formateur on my grades, so that I understand their feedback.
* **[GR-14] Email notification on new grade:** As a coach or formateur, I want to receive an automatic email when an apprenti I'm responsible for adds a new grade, so that I know to review it.
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
