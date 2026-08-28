# Definition of Done

## General Backend (Laravel 13)
- Validates input via Form Requests, checks authorization via Policies/Gates on any new/changed route
- Handles empty/error states with proper status codes and Inertia-friendly error/flash responses
- No debug artifacts (dd(), dump(), var_dump(), ray(), etc.) or unhandled exceptions left in code
- Follows existing project conventions, reuses existing services/actions instead of duplicating them
- No obvious N+1 queries introduced (eager-load relations, check with Laravel Debugbar/Telescope if available)

## General Frontend (Vue 3 + Inertia)
- Handles empty/loading/error states, including Inertia's shared/validation error bag (`$page.props.errors`) and `router.visit` loading indicators
- No debug artifacts (console.log, debugger, etc.) left in code
- Follows existing project conventions, reuses existing shadcn-vue components/composables instead of duplicating them
- Uses `<script setup>` + Composition API consistent with the rest of the codebase, not Options API
- Props/types match what the Laravel controller actually shares via Inertia (no assumed fields)
- Accessible (keyboard-navigable, semantic HTML, not color-only for state) and responsive at supported breakpoints
- No unnecessary re-renders/watchers introduced; reactive state (`ref`/`reactive`) scoped appropriately

