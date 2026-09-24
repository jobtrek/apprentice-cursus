# DB discussion history — grades_focus rebuild

Living record of modeling decisions for `grades_focus.mmd` (rebuild around grades,
checked against `mcd_actual.mmd` and `db_schema/mcd.mmd`). Newest entries at the bottom.

## 1. Starting point

- Goal: rebuild an MCD from `mcd_actual` + `mcd`, scoped to how **grades** relate
  (not the whole schema). No full rebuild; workaround/modification around grades.
- User's initial vocabulary: grade (subject, domain, value, submission date,
  modification date), subject (name, type), type (name), domain (optional parent,
  name, weight, calculus `average | weighted_average`, rounding `half | tenth`).
- Built `docs/db_schema/grades_focus.mmd` (note: sits next to `mcd_actual.mmd`,
  not in `db_schema/`).

## 2. How the pattern already existed (verified, other files untouched)

- Grade → subject + domain double FK already covered by single
  `grades.evaluation_node_id -> evaluation_nodes (leaf)`; subject reached via
  `evaluation_nodes.subject_id`. Double FK risks `grade.subject != leaf.subject`.
- `subjects` in DB has **no name** (`id + subject_category_id` only); names live on
  `evaluation_nodes.name`. Reason: same label (Fra) under two domains (DCO B, DCO D)
  as distinct leaves sharing one label.
- Type = `subject_category` (`id + name`). Direct match.
- Domain = `evaluation_nodes` + `evaluation_node_connections`, deltas: parent is a
  DAG edge-table (not single `parent_domain_id`); weight on the **edge**, not the row;
  calculus in migrated DB is only `NULL | weighted_average` (richer enum lives only
  in design docs); rounding is `0.5 | NULL` (NULL = keep decimals, no explicit tenth).
- Dates: `submitted_at/updated_at` ≈ `test_date + created_at/updated_at`.

## 3. Rebuild rules agreed with user

- Checker subagent reads `mcd_actual.mmd` per need, verdict: `existing` (reuse as-is)
  / `not_existing` / `can_be_similar` (report to user with matching vs differing
  properties, user rules on it).
- New tables only on explicit user order. Diverging links: stop and report.

## 4. Decisions applied to `grades_focus.mmd`

1. **Apprentice**: checker said `can_be_similar` (no dedicated table; generic
   multi-role `users` with `role` default `apprentice`). User: reuse `users` as-is,
   wire `users ||--o{ grades : "earns"` (+ coach/trainer self-refs).
2. **`projects`, `comments`**: reused as-is with `owns` / `authors` + polymorphic
   dotted links. (`skills`, `project_skill` not yet ordered.)
3. **Subject owned by one domain** (user ruling: "a grade does not share a subject;
   a subject belongs to a domain only"): `subjects.domain_id FK -> domains` added,
   `grades.domain_id` **dropped**. Chain: `grades → subjects → domains → apprenticeships`.
   Same-named subject in two domains = two rows.
4. **Domain owned by apprenticeship** (user order; link absent from `mcd_actual`,
   which uses `variant` instead): `apprenticeships` reused as-is,
   `domains.apprenticeship_id` added (NOT NULL), `apprenticeships ||--o{ domains`.
   Resolves the dangling `users.apprenticeship_id`.
5. **Weight**: `NOT NULL DEFAULT 100` on the row (roots carry unused 100);
   `parent_domain_id` stays nullable. (Weight placement revisited in §6.)
6. **DAG decision**: user chose definitive DAG ("or at least, not strict").
   `domain_connections { parent_id, child_id, weight }` added with
   `as_parent` / `as_child` relationships. `domains.parent_domain_id` **kept for now** —
   pending explicit decision to remove it (see §7).

## 5. Calculus — locked at two modes

- `average` := flat average of **all raw grades** in the subtree (grade counts weigh
  implicitly). `weighted_average` := children combined by weights.
- Rejected a third mode (average of child averages); user: only two average types
  exist (weighted vs weights-ignored).
- **Open verification** (user-side): run both modes against one full real bulletin
  (EC S1 incl. Connaissances-pro node). Rounding to halves hides most flat-vs-child
  differences until a value straddles a boundary (cf. DCO E: 5.17→5.0 vs 5.25→5.5).
  A mismatch pinpoints a node needing another mode.

## 6. Semester — derived, not stored

- Derived from user start date (+ apprenticeship duration; EC = 6 semesters, IT = 8).
- End date excluded from the formula so redo-year end extensions don't shift history.
- Open data question (user-side): on a redo, do pre-redo grades still count or are
  they superseded? May need a voided/superseded flag later.

## 7. MP / maturity design (from `~/Downloads/grades-for-mp.md`)

- Two integration models per (apprenticeship, maturity): **substitution** (IT: MP
  average fills the 20% CG slot; `G = .40 TPI + .30 ICT + .20 MP + .10 CBE`) vs
  **sealed/reweighted** (EC: MP separate; CFC 30/30/40 recomputed).
- MP1 has **double belonging** (MP diploma + CFC) — the documented trigger for
  multi-parent: one MP node, two edges (full weight in MP diploma, 20% in CFC).
- Deferred: `maturities` tag table (`apprenticeship_id, name, integration_model`)
  for variant selection. No grade→maturity link needed while grades reach maturity
  through shared nodes.
- **Pending user decisions**:
  a. Remove `domains.parent_domain_id` now that `domain_connections` exists? (Weight
     then lives only on edges.)
  b. Variant selection: (a) whole-tree-per-variant strict roots vs (b) one root with
     variant-tagged edges (`domain_connections.variant_id → maturities`, NULL = all).
  c. Factual check: does any real bulletin feed the same grades into two CFC blocks
     (e.g. MP math → CBE, CI → two places)? A single "yes" forces (b).
  d. Frozen `evaluation_results`-equivalent: parked until a published final must
     survive a later tree edit (trigger, not a task).

## 8. Suggestions (assistant, not yet ruled)

- DAG schema with strict discipline: allow the edge table but keep one parent per
  node by convention except the MP node (single code-reviewed chokepoint: edge
  creation with cycle + same-apprenticeship checks). Rationale: duplication is safe
  for computed nodes but unsafe for **entered** MP branch grades; DAG failures are
  loud (constraint/code throws), divergence failures are silent.
- Keep `rounding NOT NULL (0.5 | 0.1)` per user rejection of NULL (note: mid-tree
  rounding then feeds upper averages — accepted by user).
- Subjects remain the true leaves (grades attach to subjects, never directly to
  domains) — currently implicit; consider writing it as a stated rule.
