# Technical details — Vue 3 + Inertia (current stack)

**Status:** describes the stack `docs/plan-d/spec.md` currently commits to. This is the baseline.

Story IDs below refer to `docs/user_stories/grade_manager.md`.

---

## Stack at a glance

| Concern | Choice |
|---|---|
| Backend | Laravel 12 |
| Frontend | Vue 3 |
| Server ↔ client | Inertia.js (`@inertiajs/vue3`) |
| Build | Vite + `@vitejs/plugin-vue` |
| Starter | `laravel/vue-starter-kit` |
| Styling | Tailwind CSS |
| Components | shadcn-vue |
| PDF rendering | `vue-pdf-embed` (pdf.js wrapper) |
| Component tests | Vitest + Vue Testing Library |

