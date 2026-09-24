# Technical details — Vue 3 + Inertia (current stack)

**Status:** describes the current stack. User stories live in `docs/user_stories/user_story_final.md`.

---

## Stack at a glance

| Concern | Choice |
|---|---|
| Backend | Laravel 13 (PHP 8.3+) |
| Frontend | Vue 3 |
| Server ↔ client | Inertia.js (`@inertiajs/vue3`) |
| Build | Vite + `@vitejs/plugin-vue` |
| Starter | `laravel/vue-starter-kit` |
| Styling | Tailwind CSS |
| Components | shadcn-vue |
| PDF rendering | `vue-pdf-embed` (pdf.js wrapper) |
| Database | PostgreSQL |
| Auth | Microsoft Entra ID via Socialite (`socialiteproviders/microsoft-azure`) |
| Component tests | Vitest + Vue Testing Library (planned, not wired up yet) |

