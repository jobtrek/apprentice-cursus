# Spec: Dossier de Formation (POC section)

**Status:** grilled and settled — ready for implementation (revision 2: aligned against the official brief).
**Source:** feature description in this session, `poc-grade-manager/app.js` + `index.html` + `style.css` (existing POC to extend), `poc-grade-manager/explication.md`, official brief `JT_DEV_B09_1.5 — Création et maintenance du dossier de formation` (`~/Downloads/jt_dev_b09_création-et-maintenance-du-dossier-de-formation.pdf`).
**Scope note:** this is **not** the full Laravel/Inertia "dossier de formation" product already specced at `.scratch/DOSSIER_FORMATION/spec.md` (formateur review loop, catalog, evaluations — a separate, larger project). This spec adds a lighter "Dossier de Formation" **section to the existing grade-manager POC** — same constraints as that POC: no backend, `localStorage`, single static HTML/CSS/JS, seeded fake data. Unlike revision 1, this revision's *field list* now matches the brief's §3.1/§3.2 requirements — see "Revision 2: brief alignment" below.

## Thesis

The grade-manager POC currently has one section (notes). This adds a second: each apprentice builds a **dossier de formation** — a header block plus a list of completed projects, each carrying the fields the official brief requires (§3.2) — inside the same app, using the same identity switcher (`role-select` / `identity-select`) already in `index.html`. The dossier page is generated from that data and exportable to PDF via the browser's own print-to-PDF, matching the POC's zero-backend, zero-library posture.

## User stories

**Apprentice.** I switch my identity to myself (existing role/identity selects), open the "Dossier" section, and see my dossier header (name, formation, period, sommaire) followed by my list of projects. I add a project: title, period (start/end), organisation, description, technologies, role/responsibilities, competences (checkboxes, each marked *mobilisée* or *développée*), screenshots (uploaded, same pattern as grade PDFs), an optional demo link, and a code-source link or a reason it's not shared. I can edit or delete an existing project. At any point I can open "Voir le dossier" — a generated page with the header and all my projects — and print/export it to PDF from there.

**Coach.** No access. The dossier is a personal record — the coach role has no dossier view in this POC (decision #6, a reversal from the original draft, which had assumed a read-only mirror of the grades pattern).

## Decisions (grilled — revision 1)

| # | Question | Decision | Why |
|---|----------|-----------|-----|
| 1 | Per-apprentice or multiple dossiers per apprentice? | **One dossier per apprentice.** No separate `dossiers` entity — the project list itself *is* the dossier. | Matches the CFC deliverable (one dossier, many project entries) and the POC's existing one-record-per-apprentice pattern (grades, average). |
| 2 | Freeform tags or fixed CFC code list? | **Fixed, selectable list**, sourced from the **official ICT competency list**. | Prevents typos (`B601` vs `B60`) breaking downstream competence rollups. |
| 2a | Is the official list available now? | **No — not yet downloaded.** The real list belongs in a proper database in the eventual product; this repo's `context/` folder is currently empty. For this POC, seed a small **placeholder** `competenceCatalog` clearly marked as placeholder data, swapped for the real codes once sourced. | Unblocks building the feature now without faking "final" data. |
| 3 | Ordering: chronological / grouped by competence / manual? | **Chronological** (by `periodStart`, revision 2) for MVP. Grouping-by-competence is a display toggle, deferred; manual reorder cut entirely. | No demo payoff for `sort_order` + drag UI in a POC; grouping is cheap to bolt on later since competences are already structured data. |
| 4 | Regenerate = live render on each view, or cached HTML rebuilt on save? | **Live client-side render on each view** — no cache, no stored HTML, computed from `state.dossierEntries` every time the dossier page opens, exactly like `weightedAverageCFC()` is computed on read today. | No server to cache on; `localStorage` already holds the source of truth. |
| 5 | PDF export: server-side or client-side? | **Client-side, `window.print()`** + a `@media print` stylesheet. No PDF library. | Matches the POC's zero-backend, zero-dependency posture. |
| 6 | Coach access? | **None.** The dossier is apprentice-only/personal — the coach role gets no dossier section at all in this POC. | User call: "it's a personal thing for each apprentice." |
| 7 | Print output polish? | **Rough-but-functional is acceptable** — no requirement for clean per-entry page breaks or a print-only header before calling this done. | Explicit POC-scope call — not worth the CSS effort here. |
| 8 | Scope vs. the full Laravel dossier spec? | **Confirmed out of scope.** No review workflow, no official project catalog, no evaluations. Not a first slice of `.scratch/DOSSIER_FORMATION/spec.md`; a separate, lighter POC feature. | User confirmed explicitly. |

## Revision 2: brief alignment (grilled against `JT_DEV_B09_1.5`)

Revision 1 covered the *mechanics* (rendering, PDF, scope boundary) but its field list fell short of what the brief actually requires. Re-read against §3.1/§3.2, then grilled field-by-field:

| # | Gap found vs. brief | Decision | Why |
|---|----------------------|-----------|-----|
| 9 | §3.1 dossier header (name, formation, period, sommaire) was entirely absent | **Add it.** New apprentice fields `formationTitle`, `formationStart`, `formationEnd` (name already exists on `apprentices`); dossier view renders name + formation + period + a live-computed sommaire (list of entry titles) at the top. | Brief-mandated, and the POC had zero representation of it. |
| 10 | §3.2 wants period **start and end**; spec had one `date` | **Replace `date` with `periodStart` + `periodEnd`.** Chronological ordering now sorts by `periodStart`. | Matches the brief exactly; a single date can't represent a project's duration. |
| 11 | §3.2 "contexte de réalisation" (entreprise/organisation + buts) had no field | **Add `organisation` (string).** "Buts principaux" stays folded into the existing free-text `description` — no separate context textarea. | User call: a dedicated org field is worth it, a third textarea isn't. |
| 12 | §3.2 "technologies utilisées" was entirely absent | **Add `technologies` (free text)**, no catalog. | User call: "listing every technology is painful" — free text, not a tag system. |
| 13 | §3.2 "rôle et responsabilités" was entirely absent | **Add `role` (free text)**, same treatment as technologies. | Same reasoning — no fixed list needed. |
| 14 | §3.2 wants each competence marked **mobilisée** or **développée**; spec had a flat code list | **`competences` becomes `[{ code, marker: "mobilisee" \| "developpee" }]`.** Each checked competence gets a marker toggle (radio/select) next to it in the form. | Brief-mandated distinction; a flat list silently drops it. |
| 15 | §3.2 "captures d'écran ou liens de démo" — revision 1 explicitly cut proofs as out of scope | **Reversed. Add `screenshots: [{ fileName, dataUrl }]`** using the exact same upload pattern as grade PDFs (`readAsDataURL()`, base64 in `localStorage`), plus an optional `demoUrl` text field. | Brief-mandated; the POC already has a working upload pattern to reuse (`readAsDataURL` in `app.js`), so this isn't new infrastructure. |
| 16 | §3.2 "lien code source, ou raison si impossible" — revision 1 also cut this | **Reversed. Add `codeRepositoryUrl` (optional URL)** and **`codeNotSharedReason` (text, expected when no URL is given)** — same pair as the full Laravel spec's `PROJECT_ENTRIES` model, so this POC's shape doesn't diverge from the bigger product's if ever extended. | Brief-mandated; matching the existing bigger spec's field names is free and keeps the two specs comparable. |

**Net effect:** decisions #7 (proofs/attachments) and #8 (screenshots... code source) from revision 1's "Out" list are **overturned** — see the updated MVP scope below. The competence-marker gap (#14) changes the shape of `dossierEntries.competences` from a flat array to an array of objects.

## Data model (extends the existing `state` object in `app.js`)

```js
// On each apprentice record (state.apprentices) — dossier header source (decision #9):
// { id, name, ..., formationTitle: "Informaticien CFC - DEV", formationStart: "2024-08-01", formationEnd: "2028-07-31" }

state.dossierEntries = [
  {
    id: 1,
    apprenticeId: 1,              // FK into state.apprentices, same pattern as grades
    title: "Site vitrine associatif",
    organisation: "Association Les Colibris",          // decision #11
    periodStart: "2026-04-01",                          // decision #10
    periodEnd: "2026-05-12",
    description: "Développement d'un site en HTML/CSS/JS pour une association locale...",
    technologies: "HTML, CSS, JavaScript vanilla",      // decision #12, free text
    role: "Développement complet, seul",                // decision #13, free text
    competences: [                                      // decision #14
      { code: "B60", marker: "mobilisee" },
      { code: "B20", marker: "developpee" },
    ],
    screenshots: [                                      // decision #15
      { fileName: "accueil.png", dataUrl: "data:image/png;base64,..." },
    ],
    demoUrl: "",                                        // decision #15, optional
    codeRepositoryUrl: "https://github.com/...",        // decision #16
    codeNotSharedReason: "",                             // decision #16, expected if no URL
    createdAt: "2026-05-12T10:00:00",
    updatedAt: "2026-05-12T10:00:00",
  },
];

// PLACEHOLDER catalog — real codes not yet sourced (decision #2a).
// Swap for the official ICT competency list once downloaded.
state.competenceCatalog = [
  { code: "B20", label: "Analyser et modéliser des données (placeholder)" },
  { code: "B41", label: "Développer et adapter une application côté serveur (placeholder)" },
  { code: "B60", label: "Assurer la qualité d'un développement logiciel (placeholder)" },
];
```

No new top-level entity beyond `dossierEntries` — no `dossiers` table/array, per decision #1: an apprentice's dossier is just "their `dossierEntries`" plus their own `formationTitle`/`formationStart`/`formationEnd` fields, the same way their grade record today is just "their `grades`".

## Core features (as specified)

1. **Dossier header** — name, formation title, formation period, and a live-computed sommaire (project titles) at the top of the dossier view and the print output (decision #9).
2. **Add/edit a project** — form covering every §3.2 field: title, organisation, period start/end, description, technologies, role, competences (checkbox list from `competenceCatalog` with a per-competence mobilisée/développée marker, min 1 required), screenshots (upload via `readAsDataURL`), optional demo URL, code repository URL or not-shared reason. Same inline-form-in-a-`<section class="card">` pattern as the existing grade form. Apprentice-only — no coach path.
3. **Generate the dossier page** — a view (new section in the same SPA, or a distinct print-friendly view swapped into `#app`) rendering the header followed by every entry for the current apprentice, in chronological order (`periodStart`), each showing all fields above.
4. **Export to PDF** — a "Télécharger en PDF" button on the dossier view that calls `window.print()`; a `@media print` block hides the app chrome (header, nav, footer, buttons) and prints the dossier content — rough/functional, no polish requirement (decision #7).
5. **Auto-regeneration** — not a build step: because the dossier view always renders from live `state.dossierEntries` (and the apprentice's formation fields), saving a new/edited entry and reopening (or re-rendering) the dossier view shows it immediately. "Regeneration" is really just "the view is never stale," matching decision #4.

## Architecture (extends the existing single-file POC)

```
poc-grade-manager/
├── index.html      # add a section/tab toggle: "Notes" | "Dossier de formation"
├── app.js          # add: dossierEntries + competenceCatalog to seed/state,
│                   #      formationTitle/formationStart/formationEnd on apprentices,
│                   #      renderDossier() (header + entries), dossier entry form + handlers,
│                   #      reuse existing save()/load()/renderApp()/readAsDataURL() plumbing
└── style.css       # add: dossier card/list styles, @media print rules
```

No new files, no new dependencies — same IIFE, same `localStorage` key (`poc-grade-manager-v1`) extended with the new fields, same `renderApp()` dispatch pattern extended with a section switch alongside the existing role switch. Screenshot upload reuses the existing `readAsDataURL()` helper already used for grade PDFs — no new file-handling code path.

## MVP scope

### In
- Placeholder, seeded competence catalog (flagged as placeholder pending the real official ICT list)
- Dossier header (name, formation title, period, live sommaire) — decision #9
- Add / edit / delete a dossier entry (apprentice role only, own entries), full §3.2 field set: organisation, period start/end, description, technologies, role, competences with marker, screenshots, demo URL, code source URL/reason
- Generated dossier page: chronological list (`periodStart`), header + full entry detail
- Client-side PDF export via print stylesheet (functional, not polished)
- Reset button (existing) also clears dossier data

### Out
- Coach access to the dossier — none, at all, in this POC (decision #6)
- Multiple dossiers per apprentice
- Manual drag-reorder or grouped-by-competence view (deferred — cheap add later, not MVP)
- Server-side PDF generation
- Print-stylesheet polish (clean page breaks, print-only header)
- Formateur review/annotation loop, evaluations, catalog of official Jobtrek *projects* (that's the separate Laravel spec at `.scratch/DOSSIER_FORMATION/spec.md`)
- Real official competency codes (placeholder only until sourced)
- Screenshot anonymization tooling (brief mentions "captures anonymisées si nécessaire" as the apprentice's own responsibility — no in-app blurring/redaction tool)

## Risks

| Risk | Mitigation |
|------|------------|
| Placeholder competence codes get mistaken for real/final data later | Clearly labeled `(placeholder)` in the seed data and in this spec; swap out once the official ICT list is sourced |
| Scope creep toward the full Laravel dossier product (review loop, catalog projects, coach access) | This spec explicitly excludes it (decisions #6, #8); anything from that direction goes to `.scratch/DOSSIER_FORMATION/spec.md` instead |
| Multiple base64 screenshots per entry bloat `localStorage` (5-10MB browser limit) | Acceptable for a POC with seeded demo data; flag as a real limitation if this pattern were to carry into a production build |
| Revision 1 was implemented and shipped *before* this brief-alignment pass — the already-built code needs a follow-up patch, not a from-scratch build | Message sent to `poc-grade-manager-f2` with the delta explicitly called out (see below) |

## Next steps

1. Source the real official ICT competency list and swap it into `competenceCatalog` (not blocking for the POC build itself)
2. Patch the already-implemented `poc-grade-manager/` code (app.js + index.html + style.css) with the revision 2 field additions
