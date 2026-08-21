
# Grade Manager — User Story Backlog

> **Source of truth:** `docs/user_stories/user_story.md`. This file expands its **Grades (GR)** track into buildable stories. Every story below refines a `GR-xx` card or follows directly from one — where they disagree, `user_story.md` wins.
>
> **Spec:** `docs/plan-d/spec.md`, realigned against the SoT.
>
> Cards only — no acceptance criteria, they come in a later pass.

**Global dependencies:** AUTH-01 (login), AUTH-06 (role-based access), ADMIN-01 (accounts), ADMIN-03/04 (coach + formateur assignment), ADMIN-05 (year + section), ADMIN-06 (academic calendar), ADMIN-07 (matière list).

---

## Grade Capture (CAP)

> **Refines:** GR-01, GR-02, GR-03, GR-04.
> **Dependencies:** ADMIN-06 (calendar — CAP-06 has no semester without it), ADMIN-07 (matière list — CAP-02 has nothing to offer without it).

* **[CAP-01] Upload a scanned test PDF:** As an apprenti, I want to upload the scanned PDF of my graded test, so that the original document is part of my official record.
* **[CAP-02] Select the matière:** As an apprenti, I want to choose which matière the test belongs to, so that it's filed under the right subject.
* **[CAP-03] Enter the grade value:** As an apprenti, I want to enter the grade I received, so that it's recorded alongside the scan.
* **[CAP-04] Enter the date of the test:** As an apprenti, I want to give the date of the test, so that the app can work out which year and semester it belongs to.
* **[CAP-05] Refuse an impossible grade:** As an apprenti, I want a grade outside 1–6 or off the half-point steps refused when I submit, so that a typo doesn't quietly corrupt my moyenne.
* **[CAP-06] Derive the semester from the test date:** As an apprenti, I want the app to work out the year and semester from the date I gave, so that I don't have to know which semester a date falls in.
* **[CAP-07] Rename the file automatically:** As an apprenti, I want my uploaded PDF renamed to `year_semester_matiere_grade_firstname_lastname`, so that coaches and formateurs can identify files consistently outside the app.
* **[CAP-08] Keep the scan:** As an apprenti, I want the renamed scan stored by the app, so that my coach and formateur can open the original later.
* **[CAP-09] Protect the stored file:** As an apprenti, I want the file's URL guarded by the same rules as the grade page, so that knowing a filename isn't enough for someone else to read my test.

---

## My Record (REC)

> **Refines:** GR-05, GR-06, GR-07, GR-08, GR-09.
> **Dependencies:** CAP-01…08 (there is nothing to show until grades exist).

* **[REC-01] See my grades by matière:** As an apprenti, I want all my grades grouped by matière, so that I can follow my progress subject by subject.
* **[REC-02] Open one grade and read the scan:** As an apprenti, I want to open a grade and see the PDF rendered in the page, so that I can check the original without downloading a file.
* **[REC-03] See my moyenne per matière:** As an apprenti, I want an automatically calculated moyenne for each matière, so that I know how I'm doing in that subject without working it out myself.
* **[REC-04] See my overall moyenne:** As an apprenti, I want a single overall moyenne across my matières, so that I have one number for where I stand. *(Blocked: the formula is deliberately undefined — GR-07 and Open Question #1 in `spec.md`. Do not ship a guessed weighting.)*
* **[REC-05] Correct a grade I submitted:** As an apprenti, I want to edit a grade I already submitted, so that I can fix a mistake without asking an admin.
* **[REC-06] Keep the filename correct after an edit:** As an apprenti, I want the stored file renamed again when I change the matière or the grade, so that the filename never disagrees with the record. *(Follows from CAP-07 + GR-08.)*
* **[REC-07] Remove a grade I submitted:** As an apprenti, I want to delete a grade I added by mistake, so that my record and my moyennes aren't skewed by it.
* **[REC-08] Nobody else reaches my record:** As an apprenti, I want another apprenti's grade to be refused outright rather than merely hidden, so that my record is genuinely private.

---

## Review (REV)

> **Refines:** GR-10, GR-11.
> **Dependencies:** ADMIN-03 (coach assignment), ADMIN-04 (formateur assignment).

* **[REV-01] See the apprentices I follow:** As a coach, I want a list of the apprentices assigned to me, so that I have a way into each of their records.
* **[REV-02] Open an apprenti's grades:** As a coach, I want one apprenti's grades grouped by matière with their moyennes, so that I can review their progress the way they see it themselves.
* **[REV-03] Read the original scan:** As a coach, I want to open a single grade and see the scanned test, so that I can look at the actual paper and not just the number.
* **[REV-04] Same access as a formateur:** As a formateur, I want the same list, grades and scans for the apprentices assigned to me, so that I can follow their progress without a separate tool.
* **[REV-05] Stay inside my own group:** As a coach or formateur, I want an apprenti who isn't assigned to me to be refused, so that the assignment boundary is real and not just a link I wasn't shown.
* **[REV-06] Review without editing:** As a coach or formateur, I want the grade data to be read-only for me, so that the apprenti's record stays theirs and there's no question who entered what.

---

## Feedback (FBK)

> **Refines:** GR-12, GR-13.
> **Dependencies:** REV-03 (comments live under the scan).

* **[FBK-01] Comment on a grade as a coach:** As a coach, I want to leave a comment on a specific grade, so that I can give feedback where it belongs — especially on a low grade.
* **[FBK-02] Comment on a grade as a formateur:** As a formateur, I want to leave a comment on a specific grade, so that I can give feedback on the work I'm responsible for.
* **[FBK-03] Read the feedback on my grade:** As an apprenti, I want to see the comments left on my grade, right under the scan, so that I understand the feedback without hunting through email.
* **[FBK-04] Know who said what:** As an apprenti, I want each comment to show its author and when it was written, so that I know whether it came from my coach or my formateur.
* **[FBK-05] See new comments without reloading:** As an apprenti, I want the comment thread to pick up new feedback on its own, so that I'm not refreshing the page to check.

---

## Notifications (NOT)

> **Refines:** GR-14, GR-15.
> **Dependencies:** CAP-01…08, FBK-01/02, ADMIN-03/04 (there is nobody to notify without assignments).

* **[NOT-01] Tell my coach about a new grade:** As a coach, I want an email when an apprenti I follow adds a grade, so that I know there's something to review without checking the app.
* **[NOT-02] Tell my formateur about a new grade:** As a formateur, I want the same email for the apprentices assigned to me, so that I hear about new work as it lands.
* **[NOT-03] Attach the scan to the notification:** As a coach or formateur, I want the renamed PDF attached to that email, so that I can glance at the test without logging in first.
* **[NOT-04] Tell me when someone comments:** As an apprenti, I want an email when a coach or formateur comments on one of my grades, so that I don't have to keep opening the app to check for feedback.

---

## Open questions touching this track

Carried from `docs/plan-d/spec.md`. Each one blocks or reshapes a story above.

1. **The overall moyenne formula** — blocks REC-04 outright.
2. **Are matières scoped by year or section?** — CAP-02 currently offers every active matière to every apprenti; the old track-gating that would have prevented that is gone.
3. **A test dated outside every academic period** — reject it, or store it without a semester? CAP-06 and CAP-07 both need an answer.
4. **Does an edit re-notify?** — REC-05 lets an apprenti change a grade that NOT-01/02 already emailed out. Re-send, or let the coach's copy go stale?
5. **"Apprentices in my section" (GR-11)** — read here as "assigned to me" (REV-04). Section-wide access would be a materially wider boundary.
