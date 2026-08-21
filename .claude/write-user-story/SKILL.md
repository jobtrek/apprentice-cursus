---
name: write-user-story
description: >
  Turns a project description into an organized backlog of Agile user stories (Cards only — no acceptance criteria, code, or diagrams). Trigger on "write user stories," "break this into stories/tasks," "create a backlog," splitting work across a team, or mentions of INVEST, the 3 C's, Card/Conversation/Confirmation. Never guesses — interviews the user until no unknowns remain, then writes the backlog.
---

# Write User Story

Turns a rough project description into a backlog of Agile user stories for a small team (default: 4 people) working in parallel without stepping on each other's code.

Produces **Cards only** — the first of the 3 C's (Card, Conversation, Confirmation). Acceptance criteria ("Confirmation") are out of scope and handled by the user in a later pass — never generate them here.

## The one rule that overrides everything else

**Never guess.** If any part of the project is unclear — scope, user roles, features, tech boundaries, in vs. out, team split, priorities — do not write a single story. Ask instead. It is always better to ask one more question than to invent scope. You may use the `grill-me` skill to interview the user about unclear scope.

## Workflow

1. **Wait for the project description.** Do nothing until the user shares one. If invoked with no context, ask them to paste or describe it.

2. **Find the gaps before writing anything.** Read the description, then diff it against what a full backlog needs:
   - Scope boundaries — what's v1 vs. later? Anything mentioned in passing that's out of scope?
   - User roles/personas — actual user types (e.g. "guest," "admin," "member"); don't invent names not implied.
   - Feature completeness — obvious pieces (auth, persistence, error states, notifications, deployment) the user wants covered vs. explicitly out.
   - Technical shape — architecture/stack enough to split work into independent tracks.
   - Team structure — are all 4 people equally skilled, or frontend/backend leaning? One team member has vision problems and cannot do frontend tasks — route accordingly.
   - Priorities — must-have core vs. nice-to-have, or all equal for this pass?

3. **Ask in rounds until 100% clear.** Group questions by theme, a handful at a time — don't dump everything at once. After each round, re-check for anything left that would force a guess; if so, ask again. Ask naturally, like a business analyst in a kickoff meeting — don't narrate ("I am now checking for gaps").

4. **Write the backlog** once everything is clear. Output directly in chat as organized plain text (no file).

## Backlog structure

**Independent tracks, not a flat list.** Group stories into tracks/epics by natural technical or feature boundaries (`Frontend`, `API/Backend`, `Auth`, `Data/Database`, `Infra/DevOps`, or whatever actually fits — don't force a fixed set). Each teammate should be able to pick up one track without colliding with another's code.
- If the breakdown can't produce enough independent, substantial tracks for everyone, say so plainly instead of padding with filler stories.
- If Track B genuinely can't start until a Track A story is done, call that dependency out next to the story — don't hide it to make tracks look cleaner.
- Order stories within a track in build sequence (foundational pieces first).

**Short IDs.** Prefix with a track code and number: `FE-01`, `API-03`, `AUTH-02`, `INFRA-01`.

**Standard template, adapted for technical stories too:**

> **[ID] Title**
> As a [role], I want [goal], so that [benefit].

For end-user features, `[role]` is a real user type. For technical/infra work with no natural end-user (CI, schema, deploy pipeline), adapt the role to whoever it serves: "As a developer, I want a CI pipeline that runs tests on every push, so that broken code can't reach main." The "so that" clause must be a real reason, not filler.

**Apply INVEST silently** (Independent, Negotiable, Valuable, Estimable, Small, Testable) as a quality bar — don't label it or explain it in the output. Default toward splitting stories further rather than leaving broad, multi-part ones.

**What NOT to include:** acceptance criteria, code/pseudocode/file structures, diagrams/wireframes/schemas, story points or priority labels (unless asked).

## Example output shape

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

## Gotchas

- If the user updates or corrects something mid-interview, treat it as authoritative — don't fall back to an earlier guess.
- If, while writing the backlog, one more piece of information turns out to be missing, stop and ask rather than filling the gap yourself, even at the writing stage.
- The value here is the interview, not the output — most effort should go into asking good questions, not writing stories fast.
