# Technical details — React 19 + Inertia (current stack)

**Status:** describes the stack `docs/plan-d/spec.md` currently commits to. This is the baseline.
**Companion:** `docs/technical_details_vue.md` is the same document rewritten for Vue 3. The two are structured identically so they can be read side by side. Nothing has been decided — the Vue file is evaluation material.

Story IDs below refer to `docs/user_stories/grade_manager.md`.

---

## Stack at a glance

| Concern | Choice |
|---|---|
| Backend | Laravel 12 |
| Frontend | Vue 3 |
| Server ↔ client | Inertia.js (`@inertiajs/react`) |
| Build | Vite + `@vitejs/plugin-react` |
| Starter | `laravel/react-starter-kit` |
| Styling | Tailwind CSS |
| Components | shadcn/ui |
| PDF rendering | `react-pdf` (pdf.js wrapper) |
| Component tests | Vitest + React Testing Library |

No REST API and no client-side router. Inertia hands each Laravel controller a page component name plus props; React renders it. Authorization stays in Laravel policies — the frontend receives booleans, never makes the decision.

---

## Bootstrap

`resources/js/app.jsx`:

```jsx
import { createInertiaApp } from '@inertiajs/react'
import { createRoot } from 'react-dom/client'
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers'

createInertiaApp({
  resolve: (name) =>
    resolvePageComponent(`./Pages/${name}.jsx`, import.meta.glob('./Pages/**/*.jsx')),
  setup({ el, App, props }) {
    createRoot(el).render(<App {...props} />)
  },
})
```

`vite.config.js`:

```js
import { defineConfig } from 'vite'
import laravel from 'laravel-vite-plugin'
import react from '@vitejs/plugin-react'

export default defineConfig({
  plugins: [
    laravel({ input: 'resources/js/app.jsx', refresh: true }),
    react(),
  ],
})
```

---

## Page component shape

Props come straight from the controller's `Inertia::render()`. A page is a plain function component.

```jsx
export default function List({ grouped, moyennes }) {
  return <div>{/* ... */}</div>
}
```

---

## Layout and role-based nav

Layouts persist across visits (no remount, no flicker) by assigning a static property:

```jsx
import AppLayout from '@/Layouts/AppLayout'

function List({ grouped }) { /* ... */ }

List.layout = (page) => <AppLayout>{page}</AppLayout>

export default List
```

The nav reads the shared auth prop:

```jsx
import { usePage, Link } from '@inertiajs/react'

export default function Nav() {
  const { auth } = usePage().props

  return (
    <nav>
      {auth.user.role === 'apprenti' && <Link href="/grades">Mes notes</Link>}
      {auth.user.role === 'admin' && <Link href="/admin/users">Comptes</Link>}
    </nav>
  )
}
```

This drives *navigation only*. Every route is independently gated by a policy server-side (AUTH-06) — hiding a link is not access control.

---

## Grade upload form — CAP-01 … CAP-04

`useForm` gives you state, validation errors, progress and a submit helper. Inertia detects the `File` and switches to multipart automatically.

```jsx
import { useForm } from '@inertiajs/react'

export default function Upload({ matieres }) {
  const { data, setData, post, processing, errors, progress } = useForm({
    scan: null,
    matiere_id: '',
    score: '',
    date: '',
  })

  function submit(e) {
    e.preventDefault()
    post('/grades')
  }

  return (
    <form onSubmit={submit}>
      <input type="file" accept="application/pdf"
             onChange={(e) => setData('scan', e.target.files[0])} />
      {errors.scan && <p className="text-red-600">{errors.scan}</p>}

      <select value={data.matiere_id} onChange={(e) => setData('matiere_id', e.target.value)}>
        <option value="">Choisir une matière</option>
        {matieres.map((m) => <option key={m.id} value={m.id}>{m.name}</option>)}
      </select>
      {errors.matiere_id && <p className="text-red-600">{errors.matiere_id}</p>}

      <input type="number" step="0.5" min="1" max="6"
             value={data.score} onChange={(e) => setData('score', e.target.value)} />
      {errors.score && <p className="text-red-600">{errors.score}</p>}

      <input type="date" value={data.date} onChange={(e) => setData('date', e.target.value)} />

      {progress && <progress value={progress.percentage} max="100" />}
      <button disabled={processing}>Enregistrer</button>
    </form>
  )
}
```

CAP-05 (refuse an impossible grade) is enforced by the Postgres `CHECK` and the Form Request. The `step`/`min`/`max` above are a courtesy, not the rule.

---

## Grades grouped by matière — REC-01, REC-03

The controller already grouped and computed; the component just renders.

```jsx
import { Link } from '@inertiajs/react'

export default function List({ grouped, moyennes }) {
  return (
    <>
      {Object.entries(grouped).map(([matiere, grades]) => (
        <section key={matiere}>
          <h2>{matiere} — moyenne {moyennes[matiere] ?? '—'}</h2>
          <ul>
            {grades.map((g) => (
              <li key={g.id}>
                <Link href={`/grades/${g.id}`}>{g.date} — {g.score}</Link>
              </li>
            ))}
          </ul>
        </section>
      ))}
    </>
  )
}
```

REC-04 (overall moyenne) is deliberately absent — no formula exists yet.

---

## Inline PDF — REC-02, REV-03

`react-pdf` needs its pdf.js worker wired up once:

```jsx
import { Document, Page, pdfjs } from 'react-pdf'
import 'react-pdf/dist/Page/TextLayer.css'
import 'react-pdf/dist/Page/AnnotationLayer.css'

pdfjs.GlobalWorkerOptions.workerSrc = new URL(
  'pdfjs-dist/build/pdf.worker.min.mjs',
  import.meta.url,
).toString()

export default function ScanViewer({ url }) {
  return (
    <Document file={url}>
      <Page pageNumber={1} />
    </Document>
  )
}
```

`url` points at the authorized route from CAP-09, not a public disk path.

---

## Comment thread — FBK-01 … FBK-04

The form's visibility comes from a server-computed prop, never a client-side role check.

```jsx
import { useForm } from '@inertiajs/react'

export default function Comments({ grade, comments, can }) {
  const { data, setData, post, processing, reset } = useForm({ body: '' })

  function submit(e) {
    e.preventDefault()
    post(`/grades/${grade.id}/comments`, { onSuccess: () => reset('body') })
  }

  return (
    <>
      <ul>
        {comments.map((c) => (
          <li key={c.id}>
            <strong>{c.author_name}</strong> <time>{c.created_at}</time>
            <p>{c.body}</p>
          </li>
        ))}
      </ul>

      {can.comment && (
        <form onSubmit={submit}>
          <textarea value={data.body} onChange={(e) => setData('body', e.target.value)} />
          <button disabled={processing}>Commenter</button>
        </form>
      )}
    </>
  )
}
```

`can.comment` comes from `CommentPolicy::create` in the controller. An apprenti gets `false` and sees the thread without a form (FBK-03).

---

## Polling for new comments — FBK-05

Inertia partial reload — refetches one prop, leaves the rest of the page alone.

```jsx
import { useEffect } from 'react'
import { router } from '@inertiajs/react'

useEffect(() => {
  const id = setInterval(() => router.reload({ only: ['comments'] }), 10000)
  return () => clearInterval(id)
}, [])
```

Laravel Reverb + Echo would replace this with a real-time channel. It stays a stretch item.

---

## Component library

shadcn/ui copies component source into `resources/js/components/ui/`. It is not an npm dependency — you own and edit the files. Pull in per-surface (form controls, dialog, table), not wholesale.

```
npx shadcn@latest add button input select textarea table dialog
```

---

## Frontend tests

Vitest + React Testing Library, on components only. Page-level behaviour and every authorization rule are covered by Laravel feature tests instead — that is where the policies actually live.

---

## Parked / unrelated

OCR was considered for reading a grade off a scanned test. It is **not** in scope: the apprenti types the grade (CAP-03), and filename prefill was cut entirely (`spec.md` decision #18). Kept only as a reference if that decision is ever revisited:

- Text extraction from images in Laravel using Tesseract OCR — https://medium.com/@peterochieng008/text-extraction-from-images-in-laravel-using-tesseract-ocr-a2449031a25c

---

## Reference links

- Inertia.js — https://inertiajs.com/
- Inertia React adapter — https://inertiajs.com/client-side-setup
- React 19 — https://react.dev/
- Laravel React starter kit — https://laravel.com/docs/12.x/starter-kits#react
- shadcn/ui — https://ui.shadcn.com/
- `react-pdf` — https://github.com/wojtekmaj/react-pdf
- React Testing Library — https://testing-library.com/docs/react-testing-library/intro/
