Documentation Review
1. General Assessment

The current documentation contains a lot of useful information, but different types of information are currently mixed together in the same documents.

In particular, docs/plan-d/spec.md currently acts as:

a technical specification;
a requirements overview;
an architecture document;
a technical decision log;
a history of previous decisions;
a six-week development plan;
a test strategy;
a list of open questions;
and partially as onboarding documentation.

This makes the documentation relatively large and difficult to maintain.

The goal of the reorganization should not be to remove documentation, but to separate it by purpose and establish a clear source of truth for each type of information.

2. Main Issue: Multiple Sources of the Same Information

Currently, user_story.md, spec.md, and technical-details.md partially describe the same things.

For example, spec.md repeats requirements from the user stories:

GR-01…GR-15;
ADMIN-01…ADMIN-09;
AUTH-01…AUTH-06.

This creates a risk of documentation drift.

If a user story changes, we also need to remember to update the corresponding information in spec.md.

Proposed Solution

Clearly define the responsibility of each document:

Document	Purpose
user_story.md	What the system should do
architecture/	How the system is structured
decisions.md	Why specific technical decisions were made
development/	How developers work with the project
README.md	Short project overview and quick start

user_story.md should remain the source of truth for requirements.

Technical documentation should reference user stories when necessary instead of duplicating them completely.

3. Critical Issue: React vs Vue

There is a direct contradiction regarding the frontend stack.

spec.md currently specifies:

React 19;
Inertia.js;
shadcn/ui;
react-pdf.

technical-details.md specifies:

Vue 3;
@inertiajs/vue3;
shadcn-vue;
vue-pdf-embed;
@vitejs/plugin-vue;
laravel/vue-starter-kit.

This should be resolved first.

Why This Matters

This is not just a documentation difference.

The frontend choice affects:

project structure;
dependencies;
UI components;
starter kit;
PDF viewer;
testing libraries;
page implementation;
Inertia integration.

Two developers following different documents could potentially implement the same feature using different technologies.

Proposed Solution

First, make one final decision: React or Vue.

Then:

Define one source of truth for the frontend stack.
Update spec.md.
Update or remove technical-details.md.
Remove references to the unused stack.
4. Simplify spec.md

spec.md contains a lot of useful information, so it should not be removed completely.

However, it should focus on being a technical specification rather than a general-purpose document containing everything about the project.

Keep in spec.md
architectural constraints;
data model;
main request flows;
authorization rules;
important technical dependencies;
MVP scope;
important technical constraints;
references to user stories;
unresolved technical questions that affect the implementation.
Move or Remove
History of Plan A / Plan C / Plan D

For example:

Plan C's "the app finds your grade in your inbox"...

Plan D's first pass...

Plan D's second pass...

This information may be useful as decision history, but it should not take up a significant part of the main technical specification.

It could be moved to decisions.md or kept in git history.

5. Separate Technical Decisions from the Specification

spec.md currently contains decisions such as:

PostgreSQL;
local file storage;
polling instead of WebSockets;
Mailtrap;
no IMAP;
no Excel;
no queue;
PDF library;
UI library.

Some of these decisions are important, but they would be easier to maintain separately.

Proposed File

docs/architecture/decisions.md

For example:

# Architecture Decisions

## ADR-001: PostgreSQL

Decision:
Use PostgreSQL as the database.

Reason:
...

Alternatives:
...

Status:
Accepted


The same approach could be used for:

React/Vue;
local PDF storage;
polling;
email handling;
grade calculation;
matieres instead of modules.

This allows the documentation to answer "Why did we do it this way?" without overloading the main specification.

6. Remove Project Management from the Technical Specification

spec.md currently contains a detailed development plan:

Week 1;
Week 2–4;
Week 5;
Week 6;
workstreams A/B/C/D;
fallback plans;
work distribution between four developers.

This information is closer to project management than architecture.

Proposed Solution

Move this type of information to:

GitHub Issues / Projects;
Jira;
Trello;
or a separate planning.md if it is actually required.

spec.md should describe the system rather than who is working on what during a specific week.

7. Remove Temporary Next Steps from Permanent Documentation

The current Next steps section contains the team's current action plan.

This is useful during development, but it becomes outdated quickly.

For example:

Settle Open Questions #2, #3 and #4

Once the question is resolved, the documentation should describe the resulting decision, rather than keeping the old task list.

Proposed Solution

Keep current tasks in the issue tracker.

Permanent technical documentation should contain:

current constraints;
accepted decisions;
unresolved decisions that actually affect the architecture.
8. Keep Open Questions, but Reduce Them

The Open Questions section is useful, but it should only contain questions that can affect the architecture or system behaviour.

For example, these are relevant:

scope of matieres;
valid section values;
grades outside an academic period;
deactivation semantics;
grade editing and notifications;
coach/formateur access scope.

Questions such as:

Team's prior Laravel+Inertia experience

are more related to team planning than to the system itself and should therefore not be part of the technical documentation.

9. Simplify the Tech Stack Section

The technology stack is currently described in multiple places and in different levels of detail.

The main README should only contain a short overview:

Backend: Laravel
Frontend: React/Vue + Inertia
Database: PostgreSQL
Styling: Tailwind CSS + shadcn
Storage: Local filesystem


More detailed technical information should be kept in the appropriate technical documentation.

The main rule should be:

The stack must have one source of truth and must not contradict other documentation.

10. Add Proper Onboarding Documentation

The current documentation contains a lot of information about what the project does, but much less information about how a developer can start working on it.

A dedicated development/setup.md should contain:

requirements;
installation;
environment variables;
database setup;
migrations;
seeders;
backend startup;
frontend startup;
test commands;
build commands;
required development services;
test/demo accounts, if applicable.

This is likely to be significantly more useful for a new developer than a long description of technologies the team already knows.

11. Separate Testing Documentation

The test strategy is currently part of spec.md.

It could be moved to:

docs/development/testing.md

This document should describe:

types of tests used;
how to run the tests;
which parts of the system must be tested;
testing conventions;
external service mocking;
unit tests;
feature tests.

For example, the following rule is useful and should be kept:

Mailtrap is used for manually checking emails during development, but the test suite must not depend on the Mailtrap API.

This is a useful technical convention.

12. Move the Data Model into Architecture Documentation

The ER diagram is one of the most useful parts of the current spec.md.

It should be kept, but preferably moved to:

docs/architecture/data-model.md

This document can contain the ER diagram and explanations of important relationships and constraints.

For example:

why users and role-specific profiles are separated;
why academic_period_id is stored on a grade;
why file_path is not nullable;
why multiple grades for the same matière are allowed.
13. Keep Request Flows

Sections such as:

Request flow: submitting a grade

and

Review flow: coach and formateur

are useful because they explain system behaviour at a level above individual classes.

They should be kept in:

docs/architecture/overview.md

or a dedicated flows.md.

Useful flows to document include:

who initiates the action;
endpoint;
validation;
service;
persistence;
authorization;
notifications.

This is information that is not always obvious from the code alone.

14. Proposed Documentation Structure

The documentation could be reorganized as follows:

docs/
├── README.md
│
├── user_stories/
│   └── user_story.md
│
├── architecture/
│   ├── overview.md
│   ├── data-model.md
│   └── decisions.md
│
├── development/
│   ├── setup.md
│   ├── testing.md
│   └── conventions.md
│
└── dossier-formation-part/
    └── spec.md

Responsibilities
README.md

Short project overview:

what the project is;
what problem it solves;
main technology stack;
quick start;
links to the rest of the documentation.
user_story.md

Requirements only:

What should the application do?

This remains the source of truth for the project scope.

architecture/overview.md

Architecture:

How does the application work?

main components;
roles;
flows;
authorization;
major boundaries.
architecture/data-model.md

How is the data structured?

ER diagram and important constraints.

architecture/decisions.md

Why did we choose this solution?

Architecture decisions and technical rationale.

development/setup.md

How do I run the project?

Everything required for a new developer to get started.

development/testing.md

How do I test the project?

Testing strategy, commands, and conventions.

development/conventions.md

How should we write code in this project?

Only if the project actually needs dedicated development conventions.

15. Priority of Changes

The documentation should not be completely rewritten at once.

🔴 Priority 1 — Resolve contradictions
Decide between React and Vue.
Choose the source of truth for the frontend stack.
Synchronize all documentation.
Check for other contradictions.
🟠 Priority 2 — Separate document responsibilities
Simplify spec.md.
Extract architecture documentation.
Extract technical decisions.
Separate development documentation.
Remove duplicated user stories.
🟡 Priority 3 — Remove information that becomes outdated quickly
Workstream planning.
Week-by-week planning.
Temporary next steps.
Detailed history of previous plans from the main specification.
🟢 Priority 4 — Improve onboarding
Setup guide.
Testing guide.
README.
Development conventions.
16. Final Position

The goal of this review should not be to say:

"Documentation is unnecessary because the current team already knows the project."

A stronger position is:

Documentation is useful, but the current structure is not optimal.

Currently, too much information is stored in the same document, some information is duplicated, some information becomes outdated quickly, and some documents directly contradict each other.

The proposed reorganization should make the documentation useful for three main scenarios:

New developer — can quickly understand and run the project.
Current developer — can quickly find an architectural decision or technical rule.
Future team — can understand not only what the system does, but also why it is structured the way it is.

The main principle should be:

User stories describe WHAT, architecture describes HOW, decisions explain WHY, and development documentation explains HOW TO WORK WITH IT.

Documentation should not exist simply for the sake of having documentation. If information is obvious, changes constantly, or already has a reliable source of truth elsewhere, it should not be duplicated.