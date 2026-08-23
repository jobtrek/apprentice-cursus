# Frontend framework — React or Vue?

**Status:** ⏳ **open — waiting on Bilal.** Thomas, Igor and Nikyta voted Vue. `docs/spec-d/spec.md` still commits to
React 19 and stays that way until this is answered.

**Deadline: before week 1 ships.** The switch is close to free right now because no `resources/js/` code exists yet.
It stops being free the moment week 1 lands.

---

## The one fact that decides the cost

**Nothing on the backend changes.** Controllers, policies, Form Requests, Mailables, migrations and every Laravel
feature test are identical either way. Inertia is the seam: Laravel hands over a page name and a props array, and the
frontend renders it.

The blast radius is `resources/js/`, `vite.config.js`, and two npm packages. This is not an application decision — it
is a `resources/js/` decision.

## Side by side

| Concern | React (current commitment) | Vue (proposed) |
|---|---|---|
| Backend | Laravel 12 | Laravel 12 — **unchanged** |
| Server ↔ client | `@inertiajs/react` | `@inertiajs/vue3` |
| Build plugin | `@vitejs/plugin-react` | `@vitejs/plugin-vue` |
| Starter kit | `laravel/react-starter-kit` | `laravel/vue-starter-kit` |
| Styling | Tailwind CSS | Tailwind CSS — **unchanged** |
| Components | shadcn/ui (first-party) | shadcn-vue (community port) |
| PDF rendering | `react-pdf` | `vue-pdf-embed` |
| Component tests | Vitest + React Testing Library | Vitest + `@vue/test-utils` |
| Laravel feature tests | — | **unchanged, all of them** |

No REST API and no client-side router in either case. Authorization stays in Laravel policies; the frontend receives
booleans and never makes the decision.

## What Vue genuinely makes easier

- **Templates look like HTML**, which is closer to Blade than JSX is. For a team already reading Laravel views, that's a
  real head start.
- **`v-model` removes form boilerplate.** This app is overwhelmingly forms — grade upload, admin CRUD ×4, comments, the
  dossier entry form. Vue binds `v-model="form.score"` where React needs a `value` / `onChange` pair per field. Across
  this many forms the saving is not cosmetic.
- **Reactivity is automatic.** No dependency arrays, no stale closures, no `useCallback`/`useMemo`, no re-render mental
  model. The most common category of React bug for beginners simply doesn't exist.
- **`vue-pdf-embed` bundles its own pdf.js worker.** `react-pdf` needs a `GlobalWorkerOptions.workerSrc` line that is
  easy to get wrong under Vite — and GR-20 puts an inline PDF on a page every apprenti uses.
- **`$page` is available in every template without importing it.** Small, but it comes up on every page.

## What "we've never written Vue" actually costs

- **`.value`.** Required in `<script>`, forbidden in `<template>`. The single most common beginner stumble; it will hit
  all four devs in week 1.
- **Destructuring kills reactivity.** Pulling fields off a reactive object silently produces dead values. You need
  `toRefs`, and nothing warns you.
- **Two API styles in every search result.** `<script setup>` (modern) versus Options API (older tutorials, most
  StackOverflow answers). Pick `<script setup>`, write it into the project instructions, reject anything else in review
  — otherwise the codebase ends up in both.
- **A directive vocabulary to memorise.** `v-if` vs `v-show`, `v-for` + `:key`, `v-model`, `:` / `@`, `.prevent`. Not
  hard, but it's rote learning before anyone is productive.
- **Thinner help.** React has more StackOverflow volume and stronger LLM assistance. For a team leaning on both, that's
  a real drag.
- **shadcn-vue is a community port,** not the first-party project. It tracks shadcn/ui closely but not instantly. For
  the surfaces this app needs — form controls, table, dialog — it is complete.

## The honest read

The framework comparison is not what decides this. **Open Question #7 in `docs/spec-d/spec.md` — whether the team has
shipped Laravel + Inertia before — is unresolved, and it decides it:**

- **If the team already knows React:** don't switch. A week of ramp-up buys zero features, and the six-week budget
  already spends week 1 on a non-parallel shared spike.
- **If the team knows neither:** Vue is arguably the gentler on-ramp for a form-heavy Inertia app, for the reasons above.
- **Either way the backend is untouched**, so the downside is bounded — a week of ramp-up, not a rewrite.

**Answer Open Question #7 first.** Then this decides itself.

---

## Bilal — what we need from you

1. Which one, and why?
2. If Vue: do you already know it well enough to unblock three people who don't?

Reply here, on Teams, or straight into this file.
