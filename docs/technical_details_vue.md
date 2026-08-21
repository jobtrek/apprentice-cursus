# Technical details — Vue 3 + Inertia (evaluation)

**Status:** evaluation material. `docs/plan-d/spec.md` still commits to React 19 — nothing here has been decided.
**Companion:** `docs/technical_details.md` is the same document for the current React stack. Headings are mirrored one-to-one so the two read side by side.

> **Read "What this costs the team" at the bottom first if you're deciding.** The team has never written Vue, and that matters more than any code sample above it.

Story IDs below refer to `docs/user_stories/grade_manager.md`.

---

## Stack at a glance

| Concern | React (current) | Vue equivalent |
|---|---|---|
| Backend | Laravel 12 | Laravel 12 — unchanged |
| Frontend | React 19 | Vue 3 (`<script setup>`) |
| Server ↔ client | `@inertiajs/react` | `@inertiajs/vue3` |
| Build | `@vitejs/plugin-react` | `@vitejs/plugin-vue` |
| Starter | `laravel/react-starter-kit` | `laravel/vue-starter-kit` |
| Styling | Tailwind CSS | Tailwind CSS — unchanged |
| Components | shadcn/ui | shadcn-vue (community port) |
| PDF rendering | `react-pdf` | `vue-pdf-embed` |
| Component tests | Vitest + React Testing Library | Vitest + `@vue/test-utils` |

**Nothing on the backend changes.** Controllers, policies, Form Requests, Mailables, migrations and every Laravel feature test are identical. Inertia is the seam: Laravel hands over a page name and props either way. This is the single most important fact for costing the switch — it is a `resources/js/` change, not an application change.

---

## Bootstrap

`resources/js/app.js`:

```js
import { createApp, h } from 'vue'
import { createInertiaApp } from '@inertiajs/vue3'
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers'

createInertiaApp({
  resolve: (name) =>
    resolvePageComponent(`./Pages/${name}.vue`, import.meta.glob('./Pages/**/*.vue')),
  setup({ el, App, props, plugin }) {
    createApp({ render: () => h(App, props) })
      .use(plugin)
      .mount(el)
  },
})
```

`vite.config.js`:

```js
import { defineConfig } from 'vite'
import laravel from 'laravel-vite-plugin'
import vue from '@vitejs/plugin-vue'

export default defineConfig({
  plugins: [
    laravel({ input: 'resources/js/app.js', refresh: true }),
    vue(),
  ],
})
```

---

## Page component shape

A page is a Single File Component: template, script and (optional) scoped styles in one `.vue` file. Props are declared, not destructured from an argument.

```vue
<script setup>
defineProps({
  grouped: Object,
  moyennes: Object,
})
</script>

<template>
  <div><!-- ... --></div>
</template>
```

---

## Layout and role-based nav

Layout persistence is a compiler macro instead of a static property:

```vue
<script setup>
import AppLayout from '@/Layouts/AppLayout.vue'

defineOptions({ layout: AppLayout })
defineProps({ grouped: Object })
</script>
```

The nav reads the shared auth prop. In Vue, `$page` is available in every template with no import:

```vue
<script setup>
import { Link } from '@inertiajs/vue3'
</script>

<template>
  <nav>
    <Link v-if="$page.props.auth.user.role === 'apprenti'" href="/grades">Mes notes</Link>
    <Link v-if="$page.props.auth.user.role === 'admin'" href="/admin/users">Comptes</Link>
  </nav>
</template>
```

This drives *navigation only*. Every route is independently gated by a policy server-side (AUTH-06) — hiding a link is not access control.

---

## Grade upload form — CAP-01 … CAP-04

The clearest win. `useForm` returns a reactive object, so `v-model` binds straight to it — no `value`/`onChange` pair per field. This app is mostly forms, so the saving is not cosmetic.

```vue
<script setup>
import { useForm } from '@inertiajs/vue3'

defineProps({ matieres: Array })

const form = useForm({
  scan: null,
  matiere_id: '',
  score: '',
  date: '',
})

const submit = () => form.post('/grades')
</script>

<template>
  <form @submit.prevent="submit">
    <input type="file" accept="application/pdf"
           @change="form.scan = $event.target.files[0]" />
    <p v-if="form.errors.scan" class="text-red-600">{{ form.errors.scan }}</p>

    <select v-model="form.matiere_id">
      <option value="">Choisir une matière</option>
      <option v-for="m in matieres" :key="m.id" :value="m.id">{{ m.name }}</option>
    </select>
    <p v-if="form.errors.matiere_id" class="text-red-600">{{ form.errors.matiere_id }}</p>

    <input type="number" step="0.5" min="1" max="6" v-model="form.score" />
    <p v-if="form.errors.score" class="text-red-600">{{ form.errors.score }}</p>

    <input type="date" v-model="form.date" />

    <progress v-if="form.progress" :value="form.progress.percentage" max="100" />
    <button :disabled="form.processing">Enregistrer</button>
  </form>
</template>
```

Same as React: Inertia detects the `File` and switches to multipart on its own. CAP-05 is still enforced by the Postgres `CHECK` and the Form Request, not by `min`/`max`.

---

## Grades grouped by matière — REC-01, REC-03

```vue
<script setup>
import { Link } from '@inertiajs/vue3'

defineProps({ grouped: Object, moyennes: Object })
</script>

<template>
  <section v-for="(grades, matiere) in grouped" :key="matiere">
    <h2>{{ matiere }} — moyenne {{ moyennes[matiere] ?? '—' }}</h2>
    <ul>
      <li v-for="g in grades" :key="g.id">
        <Link :href="`/grades/${g.id}`">{{ g.date }} — {{ g.score }}</Link>
      </li>
    </ul>
  </section>
</template>
```

`v-for` iterates objects with `(value, key)` directly — no `Object.entries()` wrapper. REC-04 (overall moyenne) is deliberately absent here too; no formula exists yet.

---

## Inline PDF — REC-02, REV-03

`vue-pdf-embed` bundles its own pdf.js worker, so there is no `GlobalWorkerOptions` line to get wrong:

```vue
<script setup>
import VuePdfEmbed from 'vue-pdf-embed'
import 'vue-pdf-embed/dist/styles/annotationLayer.css'
import 'vue-pdf-embed/dist/styles/textLayer.css'

defineProps({ url: String })
</script>

<template>
  <VuePdfEmbed :source="url" :page="1" />
</template>
```

`url` points at the authorized route from CAP-09, not a public disk path.

---

## Comment thread — FBK-01 … FBK-04

Same principle as React: form visibility comes from a server-computed prop, never a client-side role check.

```vue
<script setup>
import { useForm } from '@inertiajs/vue3'

const props = defineProps({
  grade: Object,
  comments: Array,
  can: Object,
})

const form = useForm({ body: '' })

const submit = () =>
  form.post(`/grades/${props.grade.id}/comments`, {
    onSuccess: () => form.reset('body'),
  })
</script>

<template>
  <ul>
    <li v-for="c in comments" :key="c.id">
      <strong>{{ c.author_name }}</strong> <time>{{ c.created_at }}</time>
      <p>{{ c.body }}</p>
    </li>
  </ul>

  <form v-if="can.comment" @submit.prevent="submit">
    <textarea v-model="form.body" />
    <button :disabled="form.processing">Commenter</button>
  </form>
</template>
```

`can.comment` comes from `CommentPolicy::create` in the controller. An apprenti gets `false` and sees the thread without a form (FBK-03).

Note the `props.grade` in the script block: `defineProps` must be captured in a variable to be read from script, even though the template uses the bare name. This trips people up.

---

## Polling for new comments — FBK-05

Identical Inertia call; different lifecycle hooks.

```vue
<script setup>
import { onMounted, onUnmounted } from 'vue'
import { router } from '@inertiajs/vue3'

let timer

onMounted(() => {
  timer = setInterval(() => router.reload({ only: ['comments'] }), 10000)
})

onUnmounted(() => clearInterval(timer))
</script>
```

Laravel Reverb + Echo would replace this with a real-time channel either way. It stays a stretch item.

---

## Component library

shadcn-vue works the same way — it copies source into your project rather than installing a dependency:

```
npx shadcn-vue@latest add button input select textarea table dialog
```

**Caveat:** shadcn-vue is a community port, not the first-party project. It tracks shadcn/ui closely but not instantly, and a handful of components differ or arrive later. For the surfaces this app needs (form controls, table, dialog) it is complete.

---

## Frontend tests

Vitest + `@vue/test-utils`, on components only. As with React, page-level behaviour and every authorization rule stay in Laravel feature tests — that is where the policies actually live, and none of those tests change.

---

## React → Vue translation table

| React | Vue 3 |
|---|---|
| `useState(x)` | `ref(x)` — `.value` in script, bare in template |
| `useMemo(fn, deps)` | `computed(fn)` — no dependency array |
| `useEffect(fn, [])` | `onMounted(fn)` |
| `useEffect` cleanup | `onUnmounted(fn)` |
| `useEffect(fn, [x])` | `watch(x, fn)` |
| `{cond && <El />}` | `v-if="cond"` |
| `arr.map(x => <El key={x.id} />)` | `v-for="x in arr" :key="x.id"` |
| `value={v} onChange={...}` | `v-model="v"` |
| `onClick={fn}` | `@click="fn"` |
| `className` | `class` |
| `{expr}` in JSX | `{{ expr }}` in template |
| `usePage().props` | `usePage().props`, or `$page.props` in template |
| `Page.layout = ...` | `defineOptions({ layout: ... })` |
| `function P({ a, b })` | `defineProps({ a: ..., b: ... })` |

---

## What this costs the team

The team has never written Vue. Read this section as the actual deliverable.

### What genuinely gets easier

- **Templates look like HTML.** Closer to Blade than JSX is. For a team already reading Laravel views, that is a real head start.
- **`v-model` removes form boilerplate.** This app is overwhelmingly forms (upload, admin CRUD ×4, comments). Compare the two upload examples: same behaviour, noticeably less wiring.
- **Reactivity is automatic.** No dependency arrays, no stale closures, no `useCallback`/`useMemo`, no re-render mental model. The most common category of React bug for beginners simply doesn't exist.
- **`vue-pdf-embed` needs no worker configuration**, where `react-pdf` needs a `GlobalWorkerOptions.workerSrc` line that is easy to get wrong under Vite.
- **`$page` in templates without importing** — small, but it comes up on every page.

### What the "never done Vue" cost actually is

- **`.value`.** Required in `<script>`, forbidden in `<template>`. This is the single most common beginner stumble and it will happen to all four devs in week 1.
- **Destructuring kills reactivity.** Pulling fields off a reactive object silently produces dead values; you need `toRefs`. Nothing warns you.
- **Two API styles in every search result.** `<script setup>` (modern) vs Options API (older tutorials, most StackOverflow answers). Pick `<script setup>`, write it into CLAUDE.md, and reject anything else in review — otherwise the codebase ends up in both.
- **A directive vocabulary to memorise.** `v-if` vs `v-show`, `v-for` + `:key`, `v-model`, `v-bind`/`:`, `v-on`/`@`, `.prevent`. Not hard, but it is rote learning before anyone is productive.
- **Thinner help.** React has more Stack Overflow volume and stronger LLM assistance. For a team that will lean on both, this is a real drag.
- **shadcn-vue is a port.** Fine for this app's surfaces, but it is not the first-party project.

### The honest read

The framework comparison is not the deciding question. **Open Question #7 in `spec.md` — the team's prior Laravel + Inertia experience — is unresolved, and it decides this:**

- **If the team already knows React:** don't switch. A week of ramp-up buys zero features, and the 6-week budget already has week 1 as a non-parallel shared spike.
- **If the team knows neither:** Vue is arguably the gentler on-ramp for a form-heavy Inertia app, for the reasons above. The switch is close to free *right now*, because no `resources/js/` code exists yet. It stops being free the moment week 1 ships.
- **Either way, the backend is untouched.** Controllers, policies, Mailables, migrations and all Laravel feature tests are identical. The blast radius is `resources/js/`, `vite.config.js` and two npm packages.

If you want this decided rather than parked, answer Open Question #7 first — and decide before week 1, not during it.

---

## Parked / unrelated

OCR was considered for reading a grade off a scanned test. It is **not** in scope: the apprenti types the grade (CAP-03), and filename prefill was cut entirely (`spec.md` decision #18). Kept only as a reference if that decision is ever revisited:

- Text extraction from images in Laravel using Tesseract OCR — https://medium.com/@peterochieng008/text-extraction-from-images-in-laravel-using-tesseract-ocr-a2449031a25c

---

## Reference links

- Inertia.js — https://inertiajs.com/
- Inertia Vue 3 adapter — https://inertiajs.com/client-side-setup
- Vue 3 docs — https://vuejs.org/
- Vue `<script setup>` — https://vuejs.org/api/sfc-script-setup.html
- Vue reactivity fundamentals (`ref` vs `reactive`) — https://vuejs.org/guide/essentials/reactivity-fundamentals.html
- Laravel Vue starter kit — https://laravel.com/docs/12.x/starter-kits#vue
- shadcn-vue — https://www.shadcn-vue.com/
- `vue-pdf-embed` — https://github.com/hrynko/vue-pdf-embed
- Vue Test Utils — https://test-utils.vuejs.org/
