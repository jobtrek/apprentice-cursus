---
name: user-story-writer
description: >
  Turns a raw project description, meeting notes, or feature idea into a clean, organized backlog of Agile user stories — Cards only, no acceptance criteria, no code, no diagrams, no schemas. Always use this skill whenever the user asks to "write user stories," "break this down into stories/tasks," "create a backlog," "turn this into an Agile backlog," mentions splitting a project across multiple teammates without merge conflicts, or shares a project/feature description and asks for a task breakdown for a team. Also trigger on mentions of INVEST, the 3 C's, Card/Conversation/Confirmation, product backlog, or organizing sprint work for several people. This skill's defining rule — it NEVER writes a single story from a guess. It interviews the user with clarifying questions, as many rounds as needed, until it has zero significant unknowns about the project, and only then produces the backlog.
---

# User Story Writer

A skill for turning a rough project description into a well-organized backlog of Agile user stories, written for a small team (default: 5 people) who want to work in parallel without stepping on each other's code.

This skill produces **Cards only** — the first of the 3 C's (Card, Conversation, Confirmation). Acceptance criteria ("Confirmation") are explicitly out of scope and handled in a later pass by the user — never generate them here, even if it would be easy to.

## The one rule that overrides everything else

**Never guess.** If any part of the project is unclear — scope, user roles, features, tech boundaries, what's in vs. out, how the team is split, priorities — do not write a single story. Ask instead. Guessing and quietly hedging with a "reasonable assumption" is exactly the failure mode this skill exists to prevent. It is always better to ask one more question than to invent scope.

---

## Step 1: Wait for the project description

Do not do anything until the user shares a description of the project or feature set they want broken down. If they invoke the skill with no context yet, ask them to paste or describe it.

## Step 2: Read it, then find the gaps — do not write stories yet

Read the whole description carefully. Before asking anything, mentally map out what a full backlog would need to cover, and diff that against what was actually said. Typical gaps to look for:

- **Scope boundaries** — what's explicitly in v1 vs. later? Any features mentioned in passing that aren't actually part of this effort?
- **User roles/personas** — who are the actual users (e.g. "guest," "admin," "member")? Don't invent role names that weren't implied.
- **Feature completeness** — are there obvious pieces of a working system that weren't mentioned (auth, data persistence, error states, notifications, deployment) that the user actually wants covered, or are those out of scope?
- **Technical shape** — enough about the architecture/stack to know how to split work into independent tracks (e.g. is there a separate frontend/backend, a mobile app, a specific framework that implies natural module boundaries)?
- **Team structure** — do all 5 people have the same skills, or are some frontend-leaning and others backend-leaning? This changes how tracks should be drawn.
- **Priorities** — is there a must-have core vs. nice-to-have, or is everything equally important for this pass?

## Step 3: Ask — in rounds, until 100% clear

Ask clarifying questions in reasonably sized batches (don't dump 25 questions at once — group by theme, a handful at a time). After each round, re-check: is there anything left that would force you to guess when writing stories? If yes, ask another round. Only stop when you'd be comfortable defending every story to the user as something they actually asked for, not something you inferred.

Do not narrate this process ("I am now checking for gaps") — just ask the questions naturally, the way a sharp business analyst would in a kickoff meeting.

## Step 4: Write the backlog

Once — and only once — everything is clear, produce the backlog directly in the chat as organized plain text (no file). Structure:

### Organize into independent tracks, not a flat list

Group stories into tracks/epics based on natural technical or feature boundaries (e.g. `Frontend`, `API/Backend`, `Auth`, `Data/Database`, `Infra/DevOps`, or whatever boundaries actually fit this project — don't force a fixed set of categories). The goal is that each of the team's people can pick up one track and work in their own area of the codebase without their changes colliding with a teammate's. This is more important than making exactly 4 tracks — draw the lines where the real seams in the system are.

- If the natural breakdown doesn't produce enough independent, substantial tracks for everyone to have real work, say so plainly instead of padding a track with filler stories.
- If two tracks have a hard dependency (Track B genuinely cannot start until a story in Track A is done), call that out explicitly next to the relevant story so the team can sequence around it. Don't hide dependencies to make tracks look cleaner than they are.
- Order stories within a track in a sensible build sequence (foundational pieces first).

### Give every story a short ID

Prefix stories with a short track code and number, e.g. `FE-01`, `API-03`, `AUTH-02`, `INFRA-01`. This gives the team something to reference later when they add acceptance criteria or discuss in standup.

### Use the standard template — adapted for technical stories too

Default format:

> **[ID] Title**
> As a [role], I want [goal], so that [benefit].

For end-user-facing functionality, `[role]` is an actual user type (e.g. "registered member," "guest," "admin"). For technical/infrastructure work that has no natural end-user — CI setup, database schema, deployment pipeline, internal tooling — don't skip it or fake a user role. Adapt the role to whoever the story actually serves: "As a developer, I want a CI pipeline that runs tests on every push, so that broken code can't reach main." The "so that" clause still has to be a real reason, not filler.

### Apply INVEST as a quality bar (don't label it)

Every story should be, silently, checked against:

- **Independent** — doesn't require another unfinished story to be meaningfully started (flag real exceptions, per above).
- **Negotiable** — describes an outcome, not a prescribed implementation. Leave room for the person doing it to decide how.
- **Valuable** — delivers something real, even if internal/technical.
- **Estimable** — specific enough that someone could size it without more questions. If a story is too vague to size, it's not ready — split it or sharpen it.
- **Small** — one deliverable per story. Big features become multiple stories, not one giant one. Given this team has time and wants thorough coverage, default toward splitting further rather than leaving broad, multi-part stories.
- **Testable** — there's a clear, observable "this works" outcome, even without formally writing acceptance criteria yet.

Don't display INVEST labels or explain the checks in the output — this is a quality bar you apply while writing, not something to show the user.

### What NOT to include

- No acceptance criteria (explicitly deferred by the user to a later pass).
- No code, pseudocode, code snippets, or file/folder structures.
- No diagrams, wireframes, or schemas.
- No story point estimates or priority labels unless the user asked for them.

Just the organized, ID'd, templated story cards, grouped by track.

---

## Example shape of the final output (illustrative only — real tracks depend on the actual project)

```
## Frontend (FE)

FE-01: Landing page
As a visitor, I want to see a landing page describing the product, so that I understand what it does before signing up.

FE-02: Signup form
As a visitor, I want to create an account with email and password, so that I can access member features.

## Auth (AUTH)

AUTH-01: Session handling
As a registered member, I want to stay logged in across page reloads, so that I don't have to re-enter credentials constantly.

## Infra (INFRA)

INFRA-01: CI pipeline
As a developer, I want automated tests to run on every pull request, so that broken code is caught before merging.
```

---

## Reminders

- This skill's whole value is in the interview, not the output — most of the effort should go into asking good questions, not writing stories fast.
- If the user updates or corrects something mid-interview, treat that as authoritative and don't fall back to an earlier guess.
- If, while writing the backlog, you realize you still need one more piece of information, stop and ask rather than filling the gap yourself — even at the writing stage.
