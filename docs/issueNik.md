# Review by Coach/Trainer

## Issue: View Assigned Apprentices

### User Story

As a coach,
I want to see a list of the apprentices assigned to me,
so that I have an entry point to each apprentice's record.

### Acceptance Criteria

- The coach can access a list of apprentices assigned to them.
- The list contains only apprentices currently assigned to the authenticated coach.
- Each apprentice in the list provides an entry point to the apprentice's record.
- Deactivated apprentices do not appear in the active apprentice list.
- If the coach has no assigned apprentices, the list is displayed as empty.

---

## Issue: View Apprentice Grades by Subject

### User Story

As a coach,
I want to open an apprentice's grades grouped by subject with their averages,
so that I can review their progress in the same way they see it.

### Acceptance Criteria

- The coach can open the grades of an apprentice assigned to them.
- Grades are grouped by subject.
- An average is displayed for each subject.
- The displayed grade data corresponds to the selected apprentice.
- The coach can access the grade view from the apprentice's record.
- A coach cannot use this functionality to access grades belonging to an apprentice who is not assigned to them.
- Grades remain accessible to authorized coaches when the apprentice has been deactivated.

---

## Issue: View Grade Scan

### User Story

As a coach,
I want to open a specific grade and view the scanned PDF,
so that I can review the actual test and not just the grade.

### Acceptance Criteria

- The coach can open a specific grade belonging to an apprentice assigned to them.
- The grade page displays the scanned PDF associated with the grade.
- The coach can view the scan without modifying the grade or its associated document.
- The displayed scan corresponds to the selected grade.
- A coach cannot access the scan of a grade belonging to an apprentice who is not assigned to them.
- Archived grades of deactivated apprentices remain accessible to authorized coaches.

---

## Issue: Trainer Access to Apprentice Records

### User Story

As a trainer,
I want the same access (list, grades, scans) for the apprentices assigned to me,
so that I can track their progress without using a separate tool.

### Acceptance Criteria

- The trainer can access a list of apprentices assigned to them.
- The list contains only apprentices currently assigned to the authenticated trainer.
- The trainer can open an assigned apprentice's record.
- The trainer can view the apprentice's grades grouped by subject.
- The trainer can view the average for each subject.
- The trainer can open a specific grade and view its scanned PDF.
- The trainer cannot access the records, grades, or scans of apprentices who are not assigned to them.
- Deactivated apprentices are removed from the trainer's active list.
- Grades belonging to deactivated apprentices remain accessible to the trainer when the trainer is authorized to access them.

---

## Issue: Enforce Apprentice Assignment Access

### User Story

As a coach or trainer,
I want access to an apprentice who is not assigned to me to be rejected,
so that the assignment boundary is actually enforced rather than simply being a hidden link.

### Acceptance Criteria

- A coach or trainer can access apprentice data only when the apprentice is assigned to them.
- Direct access to an unassigned apprentice's record is rejected.
- Direct access to an unassigned apprentice's grades is rejected.
- Direct access to an unassigned apprentice's grade scan is rejected.
- Removing an apprentice from a coach or trainer's assignments removes their access to that apprentice's data.
- Hiding an apprentice from a list is not considered sufficient access control; unauthorized direct access must also be rejected.

---

## Issue: Read-Only Grade Access for Coach and Trainer

### User Story

As a coach or trainer,
I want grade data to be read-only for me,
so that the apprentice's record remains theirs and there is no confusion about who entered what.

### Acceptance Criteria

- A coach can view grade data but cannot modify it.
- A trainer can view grade data but cannot modify it.
- Coaches and trainers cannot change the grade value.
- Coaches and trainers cannot modify the apprentice's grade information through the grade view.
- Coaches and trainers cannot delete a grade.
- Grade data remains owned and editable according to the permissions of the role responsible for managing the apprentice's grades.

---

## Issue: View Comments from Other Roles

### User Story

As a coach or trainer,
I want to be able to see comments left by the other role,
so that I have a complete view of the feedback given to the apprentice.

### Acceptance Criteria

- A coach can view comments left on an assigned apprentice's grades by trainers.
- A trainer can view comments left on an assigned apprentice's grades by coaches.
- Comments are displayed together with the grade to which they belong.
- The author of each comment is identifiable.
- The date on which each comment was written is displayed.
- Viewing comments does not allow the coach or trainer to modify another person's comment.
- Access to comments follows the same apprentice assignment boundary as access to the related grade.

---


# Feedback / Comments

## Issue: Add Comment to a Grade as Coach

### User Story

As a coach,
I want to be able to leave a comment on a specific grade,
so that I can provide targeted feedback, particularly on a poor grade.

### Acceptance Criteria

- A coach can add a comment to a specific grade belonging to an apprentice assigned to them.
- The comment is associated with the selected grade.
- The comment is visible after it has been successfully added.
- The comment records the coach as its author.
- The comment records the date on which it was written.
- A coach cannot add a comment to a grade belonging to an apprentice who is not assigned to them.
- Adding a comment does not modify the grade itself.

---

## Issue: Add Comment to a Grade as Trainer

### User Story

As a trainer,
I want to be able to leave a comment on a specific grade,
so that I can provide feedback on the work I am responsible for.

### Acceptance Criteria

- A trainer can add a comment to a specific grade belonging to an apprentice assigned to them.
- The comment is associated with the selected grade.
- The comment is visible after it has been successfully added.
- The comment records the trainer as its author.
- The comment records the date on which it was written.
- A trainer cannot add a comment to a grade belonging to an apprentice who is not assigned to them.
- Adding a comment does not modify the grade itself.

---

## Issue: View Grade Comments as Apprentice

### User Story

As an apprentice,
I want to see comments left on my grades directly below the scan,
so that I can understand the feedback without searching through my emails.

### Acceptance Criteria

- The apprentice can view comments associated with their grades.
- Comments are displayed on the grade page.
- Comments are displayed directly below the associated scan.
- Only comments belonging to the selected grade are displayed in that grade's comment section.
- The apprentice can identify who wrote each comment.
- The apprentice can see when each comment was written.
- The apprentice cannot modify comments written by a coach or trainer.

---

## Issue: Edit Own Comment

### User Story

As a coach or trainer,
I want to be able to edit a comment I have left,
so that I can correct a mistake or rephrase my thoughts.

### Acceptance Criteria

- A coach can edit comments they have written.
- A trainer can edit comments they have written.
- A coach cannot edit a comment written by another coach or trainer.
- A trainer cannot edit a comment written by another coach or trainer.
- After editing, the updated comment body is displayed.
- Editing a comment does not move it to another grade.
- Editing a comment does not change its original author.
- The comment remains associated with the same grade after editing.

---

## Issue: Delete Inappropriate Comment as Administrator

### User Story

As an administrator,
I want to be able to delete an inappropriate comment,
so that I can maintain the quality of interactions.

### Acceptance Criteria

- An administrator can delete a comment.
- The administrator can delete comments regardless of whether they were written by a coach or trainer.
- A deleted comment is no longer displayed on the grade page.
- Deleting a comment does not delete the associated grade.
- Deleting a comment does not delete the associated scanned PDF.
- The deletion does not affect other comments belonging to the same grade.

---

## Issue: Limit Comment Length

### User Story

As a coach or trainer,
I want the comment body to be limited to 2,000 characters,
so that excessively long comments are avoided.

### Acceptance Criteria

- A comment can contain a maximum of 2,000 characters.
- A comment containing more than 2,000 characters cannot be saved.
- The user receives feedback when the comment exceeds the maximum length.
- A comment containing exactly 2,000 characters can be saved.
- The character limit applies when creating a comment.
- The character limit also applies when editing a comment.

---

## Issue: Preserve Comments When a Grade Is Edited

### User Story

As an apprentice,
I want comments to remain attached to a grade even if the grade is edited,
so that feedback is never lost.

### Acceptance Criteria

- Editing a grade does not delete comments associated with that grade.
- Existing comments remain associated with the same grade after the grade is edited.
- The comments remain accessible to the apprentice after the grade is edited.
- Authorized coaches and trainers can still view the comments after the grade is edited.
- Editing a grade does not change the original author of an existing comment.
- Editing a grade does not create a duplicate of an existing comment.

---

# Notifications

## Issue: Notify Coach When Apprentice Adds a Grade

### User Story

As a coach,
I want to automatically receive an email when an apprentice I supervise adds a grade,
so that I know there is something to review without having to check the application.

### Acceptance Criteria

- An email notification is automatically sent when an apprentice adds a grade.
- The notification is sent to the coach assigned to that apprentice.
- The notification is triggered when the grade is initially added.
- The notification does not require the coach to manually check the application first.
- The notification contains the information required to identify the new grade.
- If email notifications are disabled for the coach, the notification is not sent.

---

## Issue: Notify Trainer When Apprentice Adds a Grade

### User Story

As a trainer,
I want to receive the same email for the apprentices assigned to me,
so that I am notified of new grades as soon as they are submitted.

### Acceptance Criteria

- An email notification is automatically sent to the trainer when an assigned apprentice adds a grade.
- The notification is sent only to trainers assigned to that apprentice.
- The notification is triggered when the grade is initially added.
- The notification contains the information required to identify the new grade.
- If email notifications are disabled for the trainer, the notification is not sent.

---


## Issue: Notify Apprentice When a Comment Is Added

### User Story

As an apprentice,
I want to automatically receive an email when a coach or trainer comments on one of my grades,
so that I am notified without having to constantly open the application.

### Acceptance Criteria

- An email notification is automatically sent when a coach or trainer adds a comment to an apprentice's grade.
- The notification is sent to the apprentice who owns the grade.
- The notification identifies the grade to which the comment was added.
- The notification identifies whether the comment was written by a coach or trainer.
- The notification is triggered after the comment has been successfully added.
- Editing an existing comment does not create a new comment notification.

---

## Issue: Disable Email Notifications

### User Story

As a coach or trainer,
I want to be able to disable email notifications,
so that I am not flooded with emails if I prefer to check the application manually.

### Acceptance Criteria

- A coach can disable their email notifications.
- A trainer can disable their email notifications.
- When notifications are disabled, grade notification emails are not sent to the user.
- When notifications are disabled, comment and grade deletion notifications are not sent to the user.
- Disabling notifications does not affect access to grades, comments, or other application functionality.
- The user can see whether email notifications are currently enabled or disabled.

---

## Issue: Do Not Notify on Grade Edit

### User Story

As a coach or trainer,
I want a grade edited by the apprentice not to trigger a new email notification,
so that I do not receive a second email for the same grade.

### Acceptance Criteria

- Editing an existing grade does not trigger a new grade notification email.
- A coach does not receive a second notification when an apprentice edits an existing grade.
- A trainer does not receive a second notification when an apprentice edits an existing grade.
- The original notification remains the only grade-added notification for that grade.
- Editing a grade does not trigger the grade-deletion notification.

---

## Issue: Include Grade Details in Notification Email

### User Story

As a coach or trainer,
I want the notification email to contain the apprentice's name, subject, grade, date, and a direct link to the grade page,
so that I have all the relevant information at a glance.

### Acceptance Criteria

- The grade notification email contains the apprentice's name.
- The email contains the subject associated with the grade.
- The email contains the grade value.
- The email contains the date associated with the grade.
- The email contains a direct link to the corresponding grade page.
- The link directs the recipient to the same grade referenced by the notification.
- The information in the email corresponds to the grade that triggered the notification.


---

# Training Portfolio

## Issue: Add Project to Training Portfolio

### User Story

As an IT apprentice,
I want to add a project with a title and description,
so that I can document the work I have completed.

### Acceptance Criteria

- An IT apprentice can add a new project to their portfolio.
- A project has a title.
- A project has a description.
- The project is associated with the authenticated apprentice's portfolio.
- A successfully created project appears in the apprentice's portfolio.
- An apprentice cannot create a project directly in another apprentice's portfolio.

---

## Issue: Select Skills for a Project

### User Story

As an IT apprentice,
I want to select the IT skills I acquired through a project (used or developed) from a predefined catalog,
so that my portfolio reflects standardized skills.

### Acceptance Criteria

- An IT apprentice can select skills for a project from a predefined skill catalog.
- Only skills available in the predefined catalog can be selected.
- Multiple skills can be associated with a project.
- Selected skills are stored as part of the project.
- The selected skills are displayed when viewing the project.
- The apprentice cannot create an arbitrary catalog skill directly from the project form.

---

## Issue: Add Multiple Projects to Portfolio

### User Story

As an IT apprentice,
I want to add multiple projects to my portfolio over time,
so that it becomes a complete training portfolio.

### Acceptance Criteria

- An apprentice can create more than one project.
- Each project is stored as a separate portfolio entry.
- Adding a new project does not overwrite existing projects.
- All existing projects remain accessible after a new project is added.
- The portfolio can contain multiple projects belonging to the same apprentice.

---

## Issue: Generate Portfolio HTML Preview

### User Story

As an IT apprentice,
I want my project data to automatically populate an HTML preview using the official format,
so that I can view my portfolio without manual formatting.

### Acceptance Criteria

- The portfolio can be displayed as an HTML preview.
- The preview uses the official portfolio format.
- Project data from the apprentice's portfolio is automatically inserted into the preview.
- The apprentice does not need to manually format the project data in HTML.
- The preview reflects the projects currently stored in the portfolio.
- The preview includes the available project information supported by the official format.

---

## Issue: Provide Reusable Portfolio HTML/CSS Template

### User Story

As a developer,
I want a reusable and professional HTML/CSS template for the portfolio preview,
so that project data is displayed consistently and in the company's style.

### Acceptance Criteria

- The portfolio preview uses a reusable HTML/CSS template.
- The template separates portfolio content from presentation and layout.
- Project data can be inserted into the template without changing the template structure.
- The template provides consistent styling across portfolio previews.
- The visual presentation follows the company's defined style.
- The template supports all project fields required by the official portfolio format.

---

## Issue: Automatically Update Portfolio Preview

### User Story

As an IT apprentice,
I want the HTML preview to update automatically when I add a new project,
so that I never see an outdated version.

### Acceptance Criteria

- Adding a new project causes the portfolio preview to reflect the new project.
- The newly added project appears in the generated preview without requiring manual HTML editing.
- Existing projects remain present in the preview after a new project is added.
- The preview represents the current portfolio data when it is opened or regenerated.
- The system does not display a stale version of the portfolio after a successful project addition.

---

## Issue: Export Portfolio Preview as PDF

### User Story

As an IT apprentice,
I want a button to save my portfolio preview as a PDF,
so that I have a portable copy that I can share or submit.

### Acceptance Criteria

- The apprentice can initiate PDF export from the portfolio preview.
- A button or equivalent action is available to save the portfolio as a PDF.
- The generated PDF contains the portfolio information displayed in the preview.
- The PDF can be saved by the apprentice.
- The generated document preserves the official portfolio formatting as represented by the preview.

---

## Issue: Edit Existing Portfolio Project

### User Story

As an IT apprentice,
I want to be able to edit an existing project,
so that I can correct or update its details.

### Acceptance Criteria

- An apprentice can open an existing project for editing.
- The existing project data is available when the edit form is opened.
- The apprentice can update the project's supported fields.
- Saving the changes updates the existing project rather than creating a duplicate.
- The updated project data is displayed in the portfolio after saving.
- Editing one project does not modify other projects.

---

## Issue: Delete Existing Portfolio Project

### User Story

As an IT apprentice,
I want to be able to delete an existing project,
so that I can remove an entry that is no longer part of my portfolio.

### Acceptance Criteria

- An apprentice can delete an existing project from their portfolio.
- The selected project is removed from the active portfolio after successful deletion.
- Deleting one project does not delete other projects.
- A deleted project is no longer included in the portfolio preview.
- The system does not delete the apprentice's entire portfolio when a single project is deleted.

---

## Issue: View Assigned Apprentice Portfolio as Coach

### User Story

As a coach,
I want to see the portfolio of the apprentices assigned to me,
so that I can review their documented projects and skills.

### Acceptance Criteria

- A coach can view the portfolio of an apprentice assigned to them.
- The coach can view the projects belonging to the selected apprentice.
- The coach can view the skills associated with the projects.
- The coach has read access to the portfolio.
- The coach cannot modify the apprentice's portfolio through this functionality.
- A coach cannot access the portfolio of an apprentice who is not assigned to them.

---

## Issue: View Assigned Apprentice Portfolio as Trainer

### User Story

As a trainer,
I want to see the portfolio of the apprentices assigned to me,
so that I can review their documented projects and skills.

### Acceptance Criteria

- A trainer can view the portfolio of an apprentice assigned to them.
- The trainer can view the projects belonging to the selected apprentice.
- The trainer can view the skills associated with the projects.
- The trainer has read access to the portfolio.
- The trainer cannot modify the apprentice's portfolio through this functionality.
- A trainer cannot access the portfolio of an apprentice who is not assigned to them.

---

## Issue: View Any Apprentice Portfolio as Administrator

### User Story

As an administrator,
I want to be able to view any apprentice's portfolio,
so that I can supervise training portfolios across the organization.

### Acceptance Criteria

- An administrator can access an apprentice's portfolio.
- The administrator can view the apprentice's projects.
- The administrator can view the skills associated with the projects.
- Administrator access is not restricted by coach/trainer assignment.
- The administrator has read access to the portfolio through this functionality.
- Viewing a portfolio does not modify its content.

---

## Issue: Comment on an Apprentice Portfolio Project

### User Story

As a coach, trainer, or administrator,
I want to be able to leave a comment on a specific project in an apprentice's portfolio,
so that I can provide feedback on their work.

### Acceptance Criteria

- A coach can add a comment to a specific project belonging to an apprentice they are authorized to access.
- A trainer can add a comment to a specific project belonging to an apprentice they are authorized to access.
- An administrator can add a comment to a specific apprentice portfolio project.
- The comment is associated with the selected project.
- The comment records its author.
- The comment records the date on which it was written.
- A coach or trainer cannot comment on a project they are not authorized to access.
- Adding a project comment does not modify the project itself.
- The comment follows the same 2,000-character limit defined for grade comments.

---

## Issue: View Portfolio Project Comments

### User Story

As an IT apprentice,
I want to see comments left on the projects in my portfolio,
so that I can understand the feedback I have received.

### Acceptance Criteria

- The apprentice can view comments associated with their portfolio projects.
- Comments are displayed with the project to which they belong.
- The author of each comment is displayed.
- The date of each comment is displayed.
- Comments from coaches, trainers, and administrators can be distinguished by their author.
- The apprentice cannot modify comments written by another user.
- Comments remain associated with their original project.

---

## Issue: Complete Project Information

### User Story

As an IT apprentice,
I want each project to contain a title, organization, start and end dates, description, technologies used, role, demo link, source code link, and screenshots,
so that my portfolio is complete and detailed.

### Acceptance Criteria

- Each project supports a title.
- Each project supports an organization.
- Each project supports a start date.
- Each project supports an end date.
- Each project supports a description.
- Each project supports technologies used.
- Each project supports the apprentice's role in the project.
- Each project supports a demo link.
- Each project supports a source code link.
- Each project supports screenshots.
- The supported project information is displayed in the portfolio preview.
- Existing projects can be updated with these fields through the project editing functionality.
- The system preserves the stored values when a project is viewed or included in the portfolio preview.

---


## Issue: Preserve Skills Removed from Catalog

### User Story

As an IT apprentice,
I want existing projects referencing a skill to keep that skill if it is removed from the catalog,
so that historical data is not altered.

### Acceptance Criteria

- Removing a skill from the predefined skill catalog does not remove it from existing projects.
- Existing projects continue to display the removed skill.
- Historical project data remains unchanged when a catalog skill is removed.
- A removed skill is no longer available for selection for new project associations unless it is reintroduced into the catalog.
- Existing references to the removed skill remain valid.

---


## Issue: Reorder Portfolio Projects

### User Story

As an IT apprentice,
I want to be able to reorder my projects,
so that I can present them in the order I prefer.

### Acceptance Criteria

- An apprentice can change the order of projects in their portfolio.
- The portfolio displays projects according to the apprentice's selected order.
- Reordering projects does not modify the project data itself.
- Reordering does not change the start or end dates of projects.
- The manually selected order takes precedence over automatic chronological ordering when both behaviors apply.

