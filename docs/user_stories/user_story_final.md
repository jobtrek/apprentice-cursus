## Authentication

As a user (apprentice, trainer, or coach),
I want to log in with my Microsoft account,
so that I can access the features available to my role without a separate password.

As a logged-in user,
I want to remain logged in when I reload the page or navigate through the application,
so that I do not have to log in repeatedly.

As a logged-in user,
I want to log out,
so that I can securely end my session on a shared device.

As a user,
I want to be able to stay logged in on multiple tabs or devices at the same time,
so that I am not logged out when I use the application elsewhere.

As a user,
I want each role to be restricted to the pages and actions corresponding to its permissions,
so that apprentices, trainers, and coaches cannot view or act on data outside their permissions.

As a user,
I want to be redirected to the page corresponding to my role after logging in,
so that I can access my workspace directly without navigating manually.

As a user without a valid role or section in Microsoft Entra,
I want to see a message telling me to contact my Microsoft administrator,
so that I know why I cannot log in.

As a user whose role or section was removed in Microsoft Entra,
I want my open session to end within 15 minutes,
so that access I no longer have is not kept alive by an old session.

As a new apprentice, trainer, or coach,
I want my account to be created automatically from Microsoft Entra, even before my first login,
so that coaches and trainers can see me on their dashboard right away.

As an EC apprentice,
I want to declare with a checkbox whether I follow the maturité professionnelle (MP),
so that my averages use the correct weights.

As an apprentice moved to another section in Microsoft Entra,
I want to be asked to confirm the change before my grades from the old apprenticeship are deleted,
so that a mistake in Entra does not silently wipe my record. (My projects are kept.)

## Coaching Assignment

As a coach,
I want to assign myself to an apprentice who has no coach,
so that I receive notifications for that apprentice.

As a coach,
I want assigning myself to an apprentice who already has a coach to be refused,
so that another coach never silently loses an apprentice.

As a coach,
I want to de-assign myself from one of my apprentices,
so that another coach can take over.

As a coach,
I want to see all apprentices (EC and IT) and whether they have a coach,
so that I can find apprentices who still need one.

---

## Grade Submission

As an apprentice,
I want to upload the scanned PDF of my graded test,
so that the original document becomes part of my official record.

As an apprentice,
I want to choose which subject my test belongs to,
so that it is classified under the correct subject.


As an apprentice,
I want to enter the grade I received (between 1 and 6, in 0.5 increments),
so that it is recorded alongside the scanned PDF.

As an apprentice,
I want to enter the test date,
so that the application can determine which semester it belongs to.


As an apprentice,
I want a grade outside the allowed range to be rejected when submitted,
so that a typing error does not silently corrupt my average.

As an apprentice,
I want the application to automatically derive the year and semester from the test date,
so that I do not have to know which semester a date belongs to.
(Semester boundaries are fixed: August–December = semester 1, January–July = semester 2.)


As an apprentice,
I want my uploaded PDF to be automatically renamed according to the company's naming convention,
so that coaches and trainers can identify files consistently outside the application.

As an apprentice,
I want the renamed scan to be stored by the application,
so that my coach and trainer can open the original file later.


As an apprentice,
I want the URL of the stored file to be protected by the same access rules as the grade page,
so that knowing the file name is not enough for someone else to access my test.


As an apprentice,
I want a file that is not a PDF to be rejected on both the client and server sides,
so that only valid scans are stored.


As an apprentice,
I want the file size to be limited to 10 MB,
so that excessively large files cannot be uploaded.


As an apprentice,
I want a future date to be rejected,
so that I cannot submit a test that has not taken place yet.


As an apprentice,
I want to see a clear error message if the upload fails,
so that I understand what happened and can try again.

As an apprentice,
I want to see comments left by my coach or trainer on my grades,
so that I can understand their feedback without having to search through my emails.

As an apprentice,
I want to view the PDF of my test directly in the browser,
so that I can check the original scan without having to download the file.

As an apprentice,
I want to be able to filter my grades by subject,
so that I can quickly find grades for a specific subject without scrolling through my entire list.

As an apprentice,
I want to be able to mark a grade as, for example, an oral exam,
so that I can submit it without a PDF,
so that oral results can still be recorded even when there is nothing to scan.

---

## My Grade Record

As an apprentice,
I want to see all my grades grouped by subject,
so that I can track my progress subject by subject.

As an apprentice,
I want to be able to open a grade and view the submitted PDF on the page,
so that I can check the original scan without having to download the file.

As an apprentice,
I want to see an automatically calculated average for each subject,
so that I know how I am performing in that subject without having to calculate it myself.

As an apprentice,
I want to see a single overall average calculated from my subject averages,
so that I have one number representing my overall performance.
(The calculation formula is deliberately postponed to be defined at a later stage.)

As an apprentice,
I want to be able to edit a grade I have already submitted,
so that I can fix a mistake myself.

As an apprentice,
I want the renamed file to be automatically updated if I change the subject or grade when editing a submission,
so that the file name never contradicts the record.

As an apprentice,
I want to be able to delete a grade I added by mistake,
so that my record and averages are not affected.

As an apprentice,
I want another apprentice's grade to be rejected rather than simply hidden,
so that my record is actually private.

As an apprentice,
I want to see an explicit message and a button to add my first grade when I have none,
so that I am not faced with an empty page without knowing what to do.

As an apprentice,
I want grades within the same subject to be sorted chronologically,
so that I can easily see my progress over time.

As an apprentice,
I want existing comments to remain attached to a grade when I edit it,
so that feedback from my coach or trainer is not lost.

---
## Review by Coach/Trainer

As a coach,
I want to see a dashboard of all apprentices (EC and IT),
so that I have an entry point to each apprentice's record.

As a coach,
I want to open an apprentice's grades grouped by subject with their averages,
so that I can review their progress in the same way they see it.

As a coach,
I want to open a specific grade and view the scanned PDF,
so that I can review the actual test and not just the grade.

As a trainer,
I want the same access (dashboard, grades, scans) for all apprentices of my section,
so that I can track their progress without using a separate tool.

As a trainer,
I want access to an apprentice of the other section to be rejected,
so that the section boundary is actually enforced rather than simply being a hidden link.

As a coach or trainer,
I want grade data to be read-only for me,
so that the apprentice's record remains theirs and there is no confusion about who entered what.

As a coach or trainer,
I want to be able to see comments left by the other role,
so that I have a complete view of the feedback given to the apprentice.


As a coach or trainer,
I want a deactivated apprentice to disappear from my active list while their grades remain read-only under an "Archived" filter,
so that my active view stays clean while preserving the history.
(An account is deactivated when its app role is removed in Microsoft Entra, and reactivated unchanged if it is given back.)

As a coach or trainer,
I want commenting on a deactivated apprentice's grades or projects to be refused,
so that feedback is only given to apprentices still in training.

---

## Feedback / Comments

As a coach,
I want to be able to leave a comment on a specific grade,
so that I can provide targeted feedback, particularly on a poor grade.

As a trainer,
I want to be able to leave a comment on a specific grade,
so that I can provide feedback on the work I am responsible for.

As an apprentice,
I want to see comments left on my grades directly below the scan,
so that I can understand the feedback without searching through my emails.

As an apprentice,
I want each comment to display its author and the date it was written,
so that I know whether it came from my coach or trainer.

As a coach or trainer,
I want to be able to edit a comment I have left,
so that I can correct a mistake or rephrase my thoughts.

As a coach or trainer,
I want to be able to delete a comment I have left,
so that I can remove a comment written by mistake.

As a coach or trainer,
I want deleting someone else's comment to be refused,
so that feedback from others stays on the record.


As a coach or trainer,
I want the comment body to be limited to 2,000 characters,
so that excessively long comments are avoided.

As an apprentice,
I want comments to remain attached to a grade even if the grade is edited,
so that feedback is never lost.

---

## Notifications

As a coach,
I want to automatically receive an email when an apprentice I coach adds a grade,
so that I know there is something to review without having to check the application.

As a trainer,
I want to receive the same email for every apprentice of my section,
so that I am notified of new grades as soon as they are submitted.

As a coach or trainer,
I want the renamed PDF to be attached to the notification email,
so that I can take a quick look at the list without having to log in.

As an apprentice,
I want to automatically receive an email when a coach or trainer comments on one of my grades,
so that I am notified without having to constantly open the application.

As a coach or trainer,
I want to be able to disable email notifications,
so that I am not flooded with emails if I prefer to check the application manually.


As a coach or trainer,
I want to receive an email when a grade is deleted by an apprentice I coach or of my section,
so that I know the test is no longer in the system.


As a coach or trainer,
I want a grade edited by the apprentice not to trigger a new email notification,
so that I do not receive a second email for the same grade.


As a coach or trainer,
I want the notification email to contain the apprentice's name, subject, grade, date, and a direct link to the grade page,
so that I have all the relevant information at a glance.

---

## Training Portfolio

As an IT apprentice,
I want to add a project with a title and description,
so that I can document the work I have completed.

As an IT apprentice,
I want to select the IT skills I acquired through a project (used or developed) from a predefined catalog,
so that my portfolio reflects standardized skills.

As an IT apprentice,
I want to add multiple projects to my portfolio over time,
so that it becomes a complete training portfolio.

As an IT apprentice,
I want my project data to automatically populate an HTML preview using the official format,
so that I can view my portfolio without manual formatting.

As a developer,
I want a reusable and professional HTML/CSS template for the portfolio preview,
so that project data is displayed consistently and in the company's style.

As an IT apprentice,
I want the HTML preview to update automatically when I add a new project,
so that I never see an outdated version.

As an IT apprentice,
I want a button to save my portfolio preview as a PDF,
so that I have a portable copy that I can share or submit.

As an IT apprentice,
I want to be able to edit an existing project,
so that I can correct or update its details.

As an IT apprentice,
I want to be able to delete an existing project,
so that I can remove an entry that is no longer part of my portfolio.

As a coach,
I want to see the portfolio of any apprentice,
so that I can review their documented projects and skills.

As a trainer,
I want to see the portfolio of the apprentices of my section,
so that I can review their documented projects and skills.

As a coach or trainer,
I want to be able to leave a comment on a specific project in an apprentice's portfolio,
so that I can provide feedback on their work.

As an IT apprentice,
I want to see comments left on the projects in my portfolio,
so that I can understand the feedback I have received.


As an IT apprentice,
I want each project to contain a title, organization, start and end dates, description, technologies used, role, demo link, source code link, and screenshots,
so that my portfolio is complete and detailed.

As an IT apprentice,
I want projects to be displayed chronologically by start date,
so that I can track the evolution of my work over time.


As an IT apprentice,
I want existing projects referencing a skill to keep that skill if it is removed from the catalog,
so that historical data is not altered.

As a deactivated apprentice,
I want my portfolio to remain readable by coaches and my section's trainers,
so that their follow-up is not interrupted.
(Deactivation happens when the app role is removed in Microsoft Entra. Not the same as graduating.)

As an IT apprentice,
I want to be able to reorder my projects,
so that I can present them in the order I prefer.
