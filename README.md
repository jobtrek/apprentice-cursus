# Apprentice Cursus

A web app for Jobtrek that replaces the Excel workbook apprentis currently use to track their grades, and gives them a
place to build their *dossier de formation*.

**Status: planning.** No application code yet — this repository holds the specification, the user stories and the
technical decisions. A working proof of concept exists separately, in
[`second-group-project`](https://github.com/FrstF4ll/second-group-project) under `poc-grade-manager/`.

Team of 4 · 6-week build · school project, demo-quality, seeded fake data.

---

## What it does

**Grades.** An apprenti photographs or scans a graded test, uploads the PDF, picks the matière and types in the grade.
The app files it, renames the file to Jobtrek's convention (`year_semester_matiere_grade_firstname_lastname`), works
out which semester it belongs to, shows the apprenti their record and their moyenne per subject, and emails their coach
and formateur that there's something new to look at. Those two can read the scan and leave a comment on it; the apprenti
gets an email when they do.

**Dossier de formation.** An apprenti records each project they've completed — what it was, for whom, when, which
technologies, what their role was, which CFC compétences they mobilised or developed, screenshots, links — and the app
renders it into the firm's official layout and exports a PDF. Their coach, formateur and the admin can read it and
comment.

**No spreadsheet anywhere in the loop.** That is the point of the project.

## Who uses it

| Role | What they can do |
|------|------------------|
| **Apprenti** | Upload scanned tests and enter grades. See their own record, their moyenne per matière, and their feedback. Build and export their dossier de formation. |
| **Coach** | Read grades, scans and dossiers for the apprentis assigned to them. Comment. Cannot edit anything. |
| **Formateur** | The same access, for the apprentis assigned to them. |
| **Admin** | Create and deactivate accounts, assign coaches and formateurs, set each apprenti's year and section, define the academic calendar, and manage the matière and compétence catalogs. |

Accounts are created by the admin. There is no public sign-up and no self-service password reset — a locked-out user
contacts their admin.

## The stack

| Layer | Choice |
|---|---|
| Backend | Laravel 12 |
| Server ↔ client | Inertia.js — no separate REST API, no client-side router |
| Frontend | ⏳ **React 19 or Vue 3 — not yet decided.** See [`docs/frontend-decision.md`](docs/frontend-decision.md) |
| Styling | Tailwind CSS + shadcn/ui |
| Database | PostgreSQL |
| Files | Local disk, served through an authorized route |
| Email | Laravel Mail, Mailtrap in development |
| PDF export | `spatie/laravel-pdf` |

One app, one database, local file storage. No queue worker, no cron, no external integrations, no Microsoft/OneDrive
connection. The only outbound call is SMTP.

**The frontend framework is the one open stack decision.** The backend is identical either way — Inertia is the seam,
so the choice only touches `resources/js/`. It needs to be settled before week 1 ships, because that is when it stops
being free to change.

## Timeline

| Week | Focus |
|------|-------|
| 1 | All four devs together: database schema, auth, roles, policies. Ends with a schema nobody needs to change. |
| 2–4 | Four parallel streams: grade upload · file handling + email · admin accounts and assignments · admin catalogs, calendar and moyennes |
| 5 | Coach and formateur review pages, comments, integration, authorization tests |
| 6 | Polish, demo script, buffer |

Week 1 is deliberately **not** parallel — every stream depends on the schema and the role model, so those get built
once, together, first.

## What's still open

Decisions that are made are recorded in the specs. These are the ones that aren't, and the first three shape the
database, so they want answering before week 1:

1. **Are matières scoped by year or section?** Right now every apprenti is offered every subject.
2. **What are the valid sections?** Only "Informatique développement d'applications" has been named.
3. **A test dated outside every academic period** — reject it, or store it without a semester?
4. **The overall moyenne formula.** Deliberately deferred — the app will show a moyenne per matière and *not* a made-up
   overall number. A wrong grade average is worse than a missing one.
5. **React or Vue**, and **where this gets deployed**.

The full list, with what each one blocks, is at the end of [`docs/spec-d/spec.md`](docs/spec-d/spec.md).

---

## Repository map

```
README.md                              you are here
QUESTIONS.md                           documentation decisions — answered, implemented
note_for_bilal.md                      handover note

docs/
├── user_stories/
│   └── user_story.md                  ← SOURCE OF TRUTH for scope
├── spec-d/
│   ├── spec.md                        grades: decisions, data model, MVP, risks
│   ├── architecture.md                how it's assembled and where rules are enforced
│   └── technological_choices.md       reference links
├── dossier-formation-part/
│   └── spec.md                        dossier de formation, full spec
└── frontend-decision.md               React or Vue — open
```

**Conventions.** Every story has exactly one ID: `AUTH-xx`, `ADMIN-xx`, `GR-xx`, `DOS-xx`, all defined in
`docs/user_stories/user_story.md`. Nothing else in this repository defines a competing scheme. That file outranks
every spec — where a spec disagrees with it, the spec is the bug.

**For AI agents:** read `docs/user_stories/user_story.md` first for scope, then `docs/spec-d/spec.md` for design
decisions and `docs/spec-d/architecture.md` for structure. Cite story IDs. Do not invent an overall moyenne formula
(GR-07), and do not assume a frontend framework until `docs/frontend-decision.md` is settled.
