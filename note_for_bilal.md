# Note for Bilal

Where things stand, and the three things we'd like your opinion on.

Start with [`README.md`](README.md) — it covers what the app does, who uses it, the stack and the timeline in one page.

## Where the documentation is


**1. React or Vue.** We originally planned Inertia.js with React, then thought that was a bit boring since we already
know React, so we looked at Vue. Thomas, Igor and Nikyta voted Vue. The case for each is in
`docs/frontend-decision.md` — about 80 lines, with the honest costs of switching. The backend is identical either way,
so it's a cheap decision *right now* and stops being cheap once week 1 ships. We'd like your read before it's settled.

**2. The backlog.** `docs/user_stories/user_story.md` is now the single list — 56 stories across auth, admin, grades
and the dossier. Nikyta wrote the dossier stories; Thomas and Igor wrote the rest. Tell us what's missing or wrong
before we take it to Bastien.

**3. The proof of concept.** Plain HTML/CSS/JS, in the `poc-grade-manager` directory of
https://github.com/FrstF4ll/second-group-project. Launch a live server against it to see it run.

## Things that are still open

The ones that shape the database, so we'd like them settled before week 1:

- Are matières scoped by year or section, or does every apprenti see every subject?
- What are the valid sections? We only know "Informatique développement d'applications".
- A test dated outside every academic period — reject it, or store it without a semester?

Full list at the end of `docs/spec-d/spec.md`.

## From Thomas, unrelated

What agentic harness do you use? I'm mainly on Claude Code; Nikyta and Igor both use opencode. If you're on Claude, I'm
curious which skills you run. Answer on Teams or just write it into this file.
