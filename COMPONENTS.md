# Components

Guidance for building and reusing UI components in this project.

## Before creating a component

1. Check if a shadcn-vue component already covers the need (`resources/js/components/ui/*`, or the shadcn-vue registry).
2. If it exists, add/use it via the shadcn-vue CLI — don't hand-roll a replacement.
3. If it doesn't exist, don't silently build a custom one: tell the user it's missing and state what you intend to create (name, purpose, props) before writing it.
4. if adding logic or reactivity to the component for like hiding/showing modals etc.. create them inside of `resources/js/composables` with the format of `useXX` example: `useGradeForm`.
5. when adding fake data, create a new json file inside of `resources/js/data` call it whatever you want to call it, and use either objects or arrays.

## shadcn/vue best practices
- load the shadncn/vue skill.
- Treat `resources/js/components/ui/*` as vendored: add/update via the shadcn-vue CLI, don't hand-edit internals.
- Build feature UI in `resources/js/components/` or `resources/js/pages/`, composed on top of `ui/*` primitives — don't duplicate what a primitive already does.
- Keep variant/style logic in `cva`/`class-variance-authority` definitions co-located with the component, matching existing `ui/*` patterns.
- Use `cn()` (class-merge helper) for conditional/merged class lists instead of manual string concatenation.
- Respect existing prop and slot conventions of the primitive you're composing (e.g. `asChild`, `v-model` on form controls) rather than inventing new ones.

## New Vue components

- Use the Composition API with `<script setup lang="ts">` — no Options API.
- Style with modern Tailwind CSS v4 utility classes; avoid custom CSS unless a utility genuinely can't express it.
- Keep components typed (TypeScript props/emits) and consistent with neighboring components' structure.
- 
