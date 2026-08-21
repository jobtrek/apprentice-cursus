# User stories — Grade manager

First pass. Cards only — no acceptance criteria, they come in a later pass.

**Source:** `docs/plan-d/spec.md` (MVP > In) and `docs/plan-d/ARCHITECTURE.md`.

**Scope of this pass**

- Covers the full MVP "In" list, each story kept small.
- **CFC track only.** Maturité stories are deliberately absent until the maturité weighting scheme is read off the source docs (spec.md, Open Question #1).
- Roles: **apprentice** and **coach**. Trainer is notification-only (no UI), admin is unresolved (Open Question #5) — neither gets stories here.
- Tracks mirror the workstream split in `spec.md`: a shared week-1 spike, then streams A–D, then the shared week-5 coach work.

---

## Week 1 — shared spike (CORE)

All four devs, together. Every other stream depends on this; nothing below starts until it lands.

**CORE-01: Users table and role enum**
As a developer, I want one `users` table with a role enum, so that authentication has a single boundary and the role can drive redirects and policy checks.

**CORE-02: Role profile tables**
As a developer, I want `apprentices`, `coaches` and `trainers` profile rows linked one-to-one to `users`, so that `auth()->user()->apprentice` is a real relationship instead of an assumption.

**CORE-03: Apprentice assignment foreign keys**
As a developer, I want `apprentices.coach_id` and `apprentices.trainer_id`, so that coach scoping and the notification step both have something concrete to query.

**CORE-04: Modules table**
As a developer, I want a `modules` table carrying track, category and subject, so that the weighted average and the coach's grouped test list each have the field they need.

**CORE-05: Grades table with a score constraint**
As a developer, I want `grades.score` constrained to 1–6 in half-point steps in the database itself, so that an invalid score cannot be stored even if the app-layer validation is bypassed.

**CORE-06: Comments table**
As a developer, I want a `comments` table hanging off a grade with an author, so that the coach feedback thread has a home before anyone builds it.

**CORE-07: Demo user seeder**
As a developer, I want seeded demo apprentices, coaches and a trainer, so that the app can be logged into and demoed without building a registration flow.

**CORE-08: Policy skeleton**
As a developer, I want `GradePolicy`, `ApprenticePolicy` and `CommentPolicy` registered from the start, so that every stream wires authorization in as it builds rather than retrofitting it in week 5.

**CORE-09: Log in and land on my own page**
As an apprentice or a coach, I want to log in and arrive on the page for my role, so that I don't have to know which URL belongs to me.

**CORE-10: Canevas weights as test fixtures**
As a developer, I want the CFC weighting values transcribed from the canevas into test fixtures, so that the weighted average is built against the real numbers instead of remembered ones.

---

## Stream A — Grade form and apprentice pages

Depends on CORE.

**A-01: Upload a scanned grade PDF**
As an apprentice, I want to pick my scanned grade PDF on an upload page, so that I can hand the document to the app instead of emailing it around.

**A-02: Fill in the grade form**
As an apprentice, I want to choose the module, enter my score and the date of the test, so that the grade is recorded against the right module.

**A-03: Only see modules from my own track**
As an apprentice, I want the module list to offer only modules belonging to my formation, so that I can't accidentally file a grade against a module that isn't mine.

**A-04: Record a grade without a file**
As an apprentice, I want to submit a grade with no PDF attached, so that a grade I was given verbally still ends up in my record.

**A-05: See all my grades in one list**
As an apprentice, I want a list of every grade I've submitted, so that I can track my progress without digging through old emails.

**A-06: Open one grade and read the scan**
As an apprentice, I want to open a grade and see the scanned PDF rendered in the page, so that I can check the original document without downloading a file.
*Depends on B-06 for the authorized file route.*

---

## Stream B — Grade service, storage and notification

Depends on CORE.

**B-01: Persist a submitted grade**
As an apprentice, I want my submitted grade saved the moment I hit submit, so that my record is updated without anyone re-typing it into a spreadsheet.

**B-02: Reject a wrong-track module server-side**
As a developer, I want the store step to re-check that the module belongs to the apprentice's track, so that a hand-crafted request can't bypass what the form hides.

**B-03: Store the uploaded PDF**
As an apprentice, I want my uploaded scan kept by the app, so that my coach can look at the original document later.

**B-04: Rename the file per the JT convention**
As a developer, I want stored scans renamed to the JT naming convention on save, so that the files stay identifiable outside the app.

**B-05: Notify my coach and trainer**
As a coach, I want an email the moment one of my apprentices submits a grade, with the scan attached, so that I know there's something to review without checking the app.

**B-06: Serve the stored PDF through an authorized route**
As a developer, I want the raw file URL gated by the same policy as the grade page, so that knowing a filename isn't enough to read someone else's scan.

---

---

## Stream D — Weighted average, catalog and scoping

Depends on CORE. D-02 depends on the fixtures from CORE-10.

**D-01: Seed the CFC module catalog**
As a developer, I want the CFC modules seeded with their category and subject, so that grades can be filed and weighted against real reference data.

**D-02: Compute the weighted CFC average**
As a developer, I want a service that turns an apprentice's grades into the weighted CFC average, so that the calculation the canevas used to do lives in tested code.

**D-03: See my current average**
As an apprentice, I want my weighted average shown with my grades, so that I know where I stand without keeping my own spreadsheet.

**D-04: Only my own grades are reachable**
As an apprentice, I want another apprentice's grade to be refused rather than merely hidden, so that my record is actually private.

**D-05: Only my assigned apprentices are reachable**
As a coach, I want an apprentice who isn't assigned to me to be refused, so that the assignment boundary is real and not a UI convention.

---

## Week 5 — Coach drill-down and comments (COACH)

Shared stream. Depends on D-02 for the averages and B-06 for the scan.

**COACH-01: See my assigned apprentices**
As a coach, I want a dashboard of only my own apprentices with their track, current average and last activity, so that I can see at a glance who's active and who needs a check-in.

**COACH-02: Open one apprentice's tests**
As a coach, I want an apprentice's tests grouped by subject, so that I can review their progress subject by subject rather than as one long list.

**COACH-03: Open a single test**
As a coach, I want to open one test and see the scan with its feedback below it, so that I'm reading the document and the discussion in one place.

**COACH-04: Leave feedback on a grade**
As a coach, I want to write a comment under a test, so that I can give feedback without opening a separate email.

**COACH-05: Read my coach's feedback**
As an apprentice, I want to read the comments my coach left on my grade, so that I know what to improve.

**COACH-06: See new comments without a manual refresh**
As an apprentice, I want the comment thread to pick up new feedback on its own, so that I'm not reloading the page to check.
