# Domain links

This file is the context for solving the issues below in the package of issue Domain links.

- **Depends on:** nothing. Can start immediately.
- **Blocks:** package 6 (Tree app code).
- **Names agreed across packages:** table `domain_links`, model `DomainLink`, foreign key `domain_link_id`; table `domain_link_weights`, model `DomainLinkWeight`.
- **Status: all fixes applied (2026-10-07).** See "What was done" at the end.
- **Schema fixes were added as new migrations**, not edited in place as first planned: the original migrations below are untouched. After pulling, everyone runs `./vendor/bin/sail artisan migrate:fresh`.

Files owned by this package (no other package edits them):

- `../../database/migrations/2026_10_01_112517_restructure_evaluation_nodes_connections_table.php`
- `../../database/migrations/2026_10_01_122818_create_domain_link_weights_table.php`
- `../../database/migrations/2026_10_01_114802_create_apprenticeship_context_table.php`
- `../../app/Models/Domain.php`
- `../../app/Models/DomainNode.php` (to rename `DomainLink.php`)
- `../../app/Models/DomainEdge.php` (to rename `DomainLinkWeight.php`)
- `../../app/Models/ApprenticeshipContext.php`

Migrations added by this package:

- `../../database/migrations/2026_10_07_160000_add_self_loop_check_and_foreign_key_indexes_to_domain_tables.php`
- `../../database/migrations/2026_10_07_170000_cascade_domain_link_deletes.php`
- `../../database/migrations/2026_10_07_180000_add_weight_range_check_to_domain_link_weights_table.php`

# Problems encountered

- [x] **Comment 3 / M1 — Models query tables that do not exist.** `DomainNode` uses table `domain_nodes`, `DomainEdge` uses `domain_edges` and the column `domain_node_id`. The migrations create `domain_links` and `domain_link_weights` with `domain_link_id`.
- [x] **M1 — `DomainNode` is declared as a keyless pivot.** It extends `Pivot` with `$primaryKey = null` and `$incrementing = false`, but `domain_links` has an `id` that `domain_link_weights.domain_link_id` points at.
- [x] **M1 — `Domain` relations use the wrong table.** `children()` and `parents()` pass `'domain_nodes'` to `belongsToMany()`; `childNodes()` and `parentNodes()` return `DomainNode`.
- [x] **M1 — `ApprenticeshipContext::domainEdges()`** returns `DomainEdge`, so it queries `domain_edges`.
- [x] **Comment 5 — Domains can link to themselves.** `domain_links` has no `parent_id <> child_id` check; the old `evaluation_node_connections` table had one (`2026_10_01_112517`, lines 16 to 21).
- [x] **Comment 6 — `child_id` has no index.** The unique `(parent_id, child_id)` only covers parent lookups. The old table had `index('child_id')`.
- [x] **G4 — Link foreign keys lost `cascadeOnDelete`.** Old table cascaded; `domain_links` now uses the default (NO ACTION), so a domain that has links cannot be deleted. Not documented as a decision.
- [x] **G2 — `domain_link_weights.domain_link_id` has no index** (`2026_10_01_122818`). The composite primary key only covers `apprenticeship_context_id`.
- [x] **G5 — `domain_link_weights.weight` has no range check.** `../db/db.md` says it is a fraction from 0.01 to 1.
- [x] **G2 — `apprenticeship_contexts.root_domain_id` has no index** (`2026_10_01_114802`).
- [x] **M7 — `Domain::$rounding_step` docblock says `string`**, the column is nullable.

# Fixes suggested

1. **Rename the models to match the tables.**
    - `DomainNode.php` → `DomainLink.php`: extend `Model` (not `Pivot`), `protected $table = 'domain_links'`, `public $timestamps = false`, keep the default `id` key. Keep `parent()` and `child()`. Rename `domainEdges()` to `weights()` returning `HasMany<DomainLinkWeight>` on `domain_link_id`.
    - `DomainEdge.php` → `DomainLinkWeight.php`: `protected $table = 'domain_link_weights'`, docblock `@property int $domain_link_id`, relation `domainLink()` → `belongsTo(DomainLink::class)`. It has a composite primary key, so keep `$incrementing = false` and `$primaryKey = null`, and do not call `save()`/`delete()` on an instance without scoping by both keys.
2. **Update `Domain`.** `children()` / `parents()` → `belongsToMany(self::class, 'domain_links', …)`. If the pivot class is kept in `->using()`, it must be a `Pivot` subclass, so either drop `->using()` or keep a small pivot class separate from `DomainLink`. Simplest: drop `->using()`. `childNodes()` / `parentNodes()` → `childLinks()` / `parentLinks()` returning `DomainLink`.
3. **Update `ApprenticeshipContext`.** `domainEdges()` → `domainLinkWeights()` returning `HasMany<DomainLinkWeight>`.
4. **Comment 5.** In `2026_10_01_112517` `up()`, after `Schema::create`, add:
   `DB::statement('ALTER TABLE domain_links ADD CONSTRAINT domain_links_no_self_loop_check CHECK (parent_id <> child_id)');`
5. **Comment 6.** In the same `Schema::create`, add `$table->index('child_id');`.
6. **G4.** Decide and write it down: either add `->cascadeOnDelete()` on both foreign keys (old behaviour), or keep NO ACTION and tell package 8 (Docs) to record it. Recommendation: `cascadeOnDelete()`, because a link has no meaning without both domains.
   **Decided (2026-10-07):** cascade. `2026_10_07_170000_cascade_domain_link_deletes` sets `ON DELETE CASCADE` on `domain_links.parent_id`, `domain_links.child_id` and `domain_link_weights.domain_link_id`, so deleting a domain removes its links and their weights. `apprenticeship_contexts.root_domain_id`, `subjects.domain_id` and `grades.domain_id` are left as they are and still block the delete. Package 8 records this in the ADR.
7. **G2 weights.** In `2026_10_01_122818`, add `$table->index('domain_link_id');`.
8. **G5 weights.** Add a CHECK. The column is created as `decimal(3,1)` here and changed to `decimal(3,2)` by `2026_10_07_120000`; put the CHECK in `2026_10_01_122818` as `CHECK (weight > 0 AND weight <= 1)`.
9. **G2 contexts.** In `2026_10_01_114802`, add `$table->index('root_domain_id');` inside the `apprenticeship_contexts` create. Do not touch the `users` part of that migration: the users index belongs to package 5 and goes in `2026_10_07_150000`.
10. **M7.** Docblock → `@property string|null $rounding_step`.

Check when done: `migrate:fresh` passes, `\d domain_links` shows the CHECK and the `child_id` index, and in tinker `Domain::first()?->children` runs without a missing-table error.

Nothing guards against longer cycles (A → B → A) any more: the old `EvaluationNode::addChild()` did it and was deleted. Flag to package 6 where the new guard should live.

# What was done

The model fixes (1, 2, 3, 10) were applied as suggested. The schema fixes (4 to 9) were applied through three new migrations instead of the original files:

| Fix            | Where               | Note                                                                                                                  |
| -------------- | ------------------- | --------------------------------------------------------------------------------------------------------------------- |
| 4. Comment 5   | `2026_10_07_160000` | `domain_links_no_self_loop_check CHECK (parent_id <> child_id)`                                                       |
| 5. Comment 6   | `2026_10_07_160000` | index on `domain_links.child_id`. No separate `parent_id` index: the unique `(parent_id, child_id)` already covers it |
| 7. G2 weights  | `2026_10_07_160000` | index on `domain_link_weights.domain_link_id`                                                                         |
| 9. G2 contexts | `2026_10_07_160000` | index on `apprenticeship_contexts.root_domain_id`                                                                     |
| 6. G4          | `2026_10_07_170000` | cascade, see the decision under fix 6                                                                                 |
| 8. G5          | `2026_10_07_180000` | `domain_link_weights_weight_check CHECK (weight > 0 AND weight <= 1)`, and the `0.00` default is dropped              |

Decision on weights (2026-10-07): a weight is mandatory, strictly above 0 and at most 1, with no default, so a forgotten weight fails instead of silently storing 0. Weighted averages are the rule. A plain average is not a separate mode: it is written as the same weight on every child of the domain (for example `1` each).

# Hand-offs

- **Package 6 (Tree app code), cycle guard.** The database only blocks direct self-links. Longer cycles (A → B → A) need the application guard described in `./6-tree-app-code.md`.
- **Package 6, aggregation formula.** Compute `Σ(weight × value) / Σ(weight)`: divide by the sum of the weights. Without the division, children that all have weight `1` would not give a plain average, and equal thirds cannot be stored exactly in `decimal(3,2)`.
- **Package 8 (Docs).** Record in `../adr/ADR.md` and `../db/db.md`: the cascade rule of fix 6, and the weight rule above (range, no default, plain average as equal weights).
- **Package 9 (Subject ↔ domain many-to-many).** It changes `Domain::subjects()` in `Domain.php`, a file owned by this package.
