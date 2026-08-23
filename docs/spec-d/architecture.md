# Architecture

**Status:** current. Rewritten against `docs/spec-d/spec.md` — the two agree.

`docs/spec-d/spec.md` is the source of design authority: scope, decisions, the data model, MVP boundaries and risks.
This file is the **assembly view** — how the pieces fit, where each rule is enforced, and what the deployed shape is.
Where the two disagree, `docs/spec-d/spec.md` wins and this file is the bug.

Story IDs (`AUTH-xx`, `ADMIN-xx`, `GR-xx`) refer to `docs/user_stories/user_story.md`.

---

## System overview

```
   ┌──────────────┐                                    ┌──────────────┐
   │   Apprenti   │  upload scanned PDF + matière      │    Admin     │
   │  (browser)   │  + score + date  ───────────┐      │  (browser)   │
   └──────────────┘                             │      └──────┬───────┘
          ▲                                     ▼             │ accounts, assignments,
          │ own grades + moyennes    ┌────────────────────────┴────┐ calendar, catalogs
          └──────────────────────────│                             │
                                     │   Laravel 12 + Inertia      │
   ┌──────────────┐   assigned       │   (single monolith)         │
   │    Coach     │───apprentis──────│                             │
   │  (browser)   │   read + comment │   no queue · no cron        │
   └──────────────┘                  │   no inbound integrations   │
   ┌──────────────┐   assigned       │                             │
   │  Formateur   │───apprentis──────│                             │
   │  (browser)   │   read + comment └──┬──────────┬───────────┬───┘
   └──────────────┘                     │          │           │
                                        ▼          ▼           ▼
                          ┌───────────────┐ ┌────────────┐ ┌──────────────┐
                          │  Local disk   │ │ PostgreSQL │ │  SMTP        │
                          │ storage/app/  │ │            │ │  (Mailtrap   │
                          │   grades/     │ │ system of  │ │   in dev)    │
                          │   *.pdf       │ │  record    │ │              │
                          └───────────────┘ └────────────┘ └──────────────┘
```

**One outbound dependency: SMTP.** No inbound network dependency, no scheduled jobs, no OAuth, no mailbox access, no
spreadsheet library, no external API keys beyond SMTP credentials. Mail is swappable for any provider through Laravel's
mail config.

Mailbox intake was investigated and rejected: reading an apprenti's Outlook needs a tenant admin to register an app in
Entra ID plus a per-user Microsoft consent flow, neither of which a student project can self-serve without touching
Jobtrek's real IT infrastructure. The apprenti uploads the PDF instead.

**PostgreSQL is the only system of record.** There is no spreadsheet anywhere in the loop — replacing the Excel workbook
is the thesis of the project.

---

## Where each rule is enforced

The single most important property of this design: **every access rule lives in one place, server-side.** The frontend
receives booleans and never makes a decision.

| Rule | Enforced by | Never by |
|---|---|---|
| A user is who they say they are | Laravel session auth against `users` | — |
| Deactivated accounts can't log in (ADMIN-02) | `users.is_active` checked at login | — |
| An apprenti reads only their own grades (GR-05) | `Grade::where('apprentice_id', …)` + `GradePolicy::view` | Filtering a full list in the page |
| A coach reads only assigned apprentis (GR-10) | `Apprentice::where('coach_id', …)` + `ApprenticePolicy` | Hiding a nav link |
| A formateur reads only assigned apprentis (GR-11) | `Apprentice::where('formateur_id', …)` + `ApprenticePolicy` | Section-wide filtering |
| Only coach/formateur may comment (GR-12) | `CommentPolicy::create`, surfaced as a `can.comment` prop | A role check in the component |
| Coach/formateur can't edit grade data | No write route exists for them; `GradePolicy::update` denies | A disabled button |
| Non-admins can't reach admin routes (AUTH-06) | `AdminPolicy` + route middleware | Not linking the page |
| A score is 1–6 in half-points | Postgres `CHECK` **and** a Form Request | `min`/`max`/`step` on the input |
| The stored PDF isn't world-readable | Served through an authorized controller route, not a public disk | An unguessable filename |

A hand-crafted URL must return **403**, not a rendered page with rows filtered out. Every negative case in this table is
feature-tested explicitly.

---

## Request flow: an apprenti submits a grade

```
POST /grades   (GradeController@store)
  │
  ├─ 1. GradeRequest validates
  │       score 1–6 in half-point steps · matière exists and is active
  │       PDF present · date parseable
  │
  ├─ 2. AcademicCalendar::resolve(date)  ──▶ academic_period
  │       ⚠ a date matching no period is the unresolved edge case
  │         — Open Question #4 in docs/spec-d/spec.md
  │
  ├─ 3. GradeService::store()    persist the Grade row
  │                              (academic_period_id stored, not derived on read,
  │                               so a later calendar edit can't rewrite history)
  │
  ├─ 4. GradeService::rename()   year_semester_matiere_grade_firstname_lastname
  │                              move into storage/app/grades/            (GR-04)
  │
  └─ 5. GradeService::notify()   Mail to coach + formateur, PDF attached  (GR-14)

  ──▶ redirect to Grades/List — moyennes recomputed on read
```

Steps 3–5 are synchronous inside one request. That is deliberate at demo scale and it is the design's known soft spot:
**if SMTP is slow or down, step 5 fails after step 3 already committed**, so the apprenti sees an error for a grade that
was in fact saved. Queueing `notify()` is the fix if it ever bites; at demo scale it buys nothing.

Editing a grade (GR-08) re-runs step 4, because the filename encodes the matière and the score. Whether it re-runs
step 5 is **Open Question #8**.

---

## Request flow: coach or formateur reviews

Both roles use the same routes and the same pages — they differ only in which column the scoping query filters on.

```
GET /apprentices                                   ReviewController@index
     └─ apprentis assigned to me (coach_id or formateur_id)
     └─ each annotated with year, section, per-matière moyennes, last activity
     ▼
GET /apprentices/{apprentice}                      ReviewController@show
     └─ ApprenticePolicy::view  →  403 if not assigned to me
     └─ grades grouped by matière, moyenne per matière
     ▼
GET /apprentices/{apprentice}/grades/{grade}       GradeController@show
     └─ GradePolicy::view
     └─ comments eager-loaded; can.comment from CommentPolicy::create
     ▼  Grades/Show — the same component the apprenti reaches at
        GET /grades/{grade} for their own grade.
        Coach/formateur: comment form rendered.
        Apprenti:        thread rendered, no form.        (GR-13)
```

One component serves both audiences because the difference is a server-computed prop, not a role branch in the client.

---

## Component map

```
app/
├── Http/
│   ├── Controllers/
│   │   ├── GradeController.php          store · index · show · update · destroy
│   │   ├── ReviewController.php         coach + formateur: index · show
│   │   ├── CommentController.php        store
│   │   └── Admin/
│   │       ├── UserController.php       ADMIN-01…03, 09 — create, edit, deactivate, assign
│   │       ├── MatiereController.php    ADMIN-07
│   │       ├── AcademicPeriodController.php   ADMIN-06
│   │       └── CompetenceController.php ADMIN-08 — serves the dossier spec
│   └── Requests/                        GradeRequest, CommentRequest, admin requests
├── Models/
│   ├── User.php                         authenticatable · role · is_active
│   ├── Apprentice.php                   user_id · coach_id · formateur_id · year · section
│   ├── Coach.php                        user_id
│   ├── Formateur.php                    user_id · section
│   ├── Matiere.php                      name · is_active
│   ├── AcademicPeriod.php               year · semester · starts_on · ends_on
│   ├── Grade.php                        apprentice_id · matiere_id · academic_period_id
│   │                                    score · date · file_path
│   └── Comment.php                      grade_id · user_id · body
├── Policies/
│   ├── GradePolicy.php                  apprenti: own · coach/formateur: assigned, read-only
│   ├── ApprenticePolicy.php             assigned-to-me only, both reviewer roles
│   ├── CommentPolicy.php                create: coach/formateur on assigned · view: apprenti on own
│   └── AdminPolicy.php                  AUTH-06
├── Mail/
│   ├── GradeSubmitted.php               GR-14 — renamed PDF attached
│   └── CommentPosted.php                GR-15
└── Services/
    ├── GradeService.php                 store() · rename() · notify()
    ├── AcademicCalendar.php             pure: date -> year + semester
    └── Grading/
        └── Moyenne.php                  pure: per-matière mean
                                         overall left as a seam — GR-07 has no formula

database/
├── migrations/    users · apprentices · coaches · formateurs · matieres
│                  academic_periods · grades · comments
└── seeders/       AdminSeeder (bootstrap admin) · DemoDataSeeder

resources/js/Pages/
├── Auth/          Login · ChangePassword
├── Grades/        Upload · List · Show
├── Review/        Apprentices · ApprenticeGrades
├── Admin/         Users · Matieres · AcademicPeriods · Competences
└── Layouts/
```

**Absent by decision:** `Jobs/`, `Exports/`, `Services/Inbox/`, `Services/Prefill/`, any MIME parser, any spreadsheet
library, any OCR. See the "Out" section of `docs/spec-d/spec.md`.

---

## Data model

See the ER diagram in `docs/spec-d/spec.md` under "Data model" — it is not duplicated here. Three properties worth
restating because they shape the code above:

- **`users` is the sole authentication table.** `users.role` is an enum; each non-admin has exactly one profile row via
  `hasOne`. Admin has no profile row. This is why `auth()->user()->apprentice` is a real relationship, not an assumption.
- **`grades.academic_period_id` is stored, not derived on read.** Editing the calendar later must not silently
  reinterpret existing grades.
- **`matieres` is flat** — no category, no weight, no year or section scoping. Whether it *should* be scoped is
  **Open Question #2**, and it is the one that would change the schema.

---

## Moyennes

`Services/Grading/Moyenne.php` replaces what the Excel `Totaux` sheet used to compute.

```
an apprenti's grades
        │
        ├─ group by matière ──▶ mean per matière   ──▶ GR-06 · shipped
        │
        └─ combine across matières ──▶ overall     ──▶ GR-07 · NOT SHIPPED
                                                       no formula exists
```

Plain PHP, no library, computed on read. At demo scale — tens of grades per apprenti — caching is premature.

**Do not ship a guessed overall moyenne.** GR-07 defers the formula and `matieres` carries no weights to compute one
from. Render the per-matière numbers and leave the overall one absent or explicitly labelled undefined. A wrong number
on a grade report is worse than a missing one.

The old CFC weight table (TPI 0.4 / base élargie 0.1 / informatique 0.3 / culture générale 0.2) is a *candidate* answer
recorded in `docs/spec-d/spec.md` under "Moyennes". It is not implemented because nothing in the current data model can
distinguish those four buckets.

---

## Deployment shape

One Laravel app, one PostgreSQL database, local filesystem for uploads. No queue worker, no cron, no WebSocket server,
no object storage, no external API keys beyond SMTP credentials.

This is the smallest deployable shape that still holds every grade, computes a real moyenne, and enforces a genuine
three-role authorization boundary. Where it deploys — localhost for the demo, or hosted — is **Open Question #6**, and
it was deliberately left until the app exists.

One deployment constraint is already known: the dossier's PDF export uses `spatie/laravel-pdf`, which needs headless
Chrome and Node on the server. See `docs/dossier-formation-part/spec.md`.

---

## Testability

The two service families are pure — data in, data out, no network, no auth, no external state — which is what makes the
important rules cheap to test:

| Target | Kind | Why it matters |
|---|---|---|
| `AcademicCalendar` | Unit | A date resolves to the right year + semester, **including the out-of-range case**. Load-bearing: GR-04's filename can't be built without it. |
| `Moyenne` | Unit | Per matière, including an apprenti with one grade and one with none. |
| Rename convention | Unit | Fixtures against `year_semester_matiere_grade_firstname_lastname`. |
| Grade submission | Feature | Full HTTP pipeline with a real fixture PDF: store → rename → notify. |
| Edit and delete | Feature | Including re-rename on edit, and 403 on another apprenti's grade. |
| Every row of the enforcement table above | Feature | **Negative cases explicitly** — unassigned apprenti → 403, non-admin on an admin route → 403. |
| Notifications | Feature | `Mail::fake()`. **The suite never calls Mailtrap.** |

**The whole suite runs with no live external service.** Mailtrap is a dev-time eyeball tool, not a test dependency —
CI needs no token and no network. That was the original reason for abandoning mailbox intake and it still holds.
