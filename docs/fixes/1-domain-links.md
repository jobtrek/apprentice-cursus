# Domain links

This file is the context for solving the issues below in the package of issue Domain links.

- **Depends on:** nothing. Can start immediately.
- **Blocks:** package 6 (Tree app code).
- **Names agreed across packages:** table `domain_links`, model `DomainLink`, foreign key `domain_link_id`; table `domain_link_weights`, model `DomainLinkWeight`.
- **Migrations are edited in place** (they are not on `main`). After pulling, everyone runs `./vendor/bin/sail artisan migrate:fresh`.

Files owned by this package (no other package edits them):

- `../../database/migrations/2026_10_01_112517_restructure_evaluation_nodes_connections_table.php`
- `../../database/migrations/2026_10_01_122818_create_domain_link_weights_table.php`
- `../../database/migrations/2026_10_01_114802_create_apprenticeship_context_table.php`
- `../../app/Models/Domain.php`
- `../../app/Models/DomainNode.php` (to rename `DomainLink.php`)
- `../../app/Models/DomainEdge.php` (to rename `DomainLinkWeight.php`)
- `../../app/Models/ApprenticeshipContext.php`

# Problems encountered

- [ ] **Comment 3 / M1 — Models query tables that do not exist.** `DomainNode` uses table `domain_nodes`, `DomainEdge` uses `domain_edges` and the column `domain_node_id`. The migrations create `domain_links` and `domain_link_weights` with `domain_link_id`.
- [ ] **M1 — `DomainNode` is declared as a keyless pivot.** It extends `Pivot` with `$primaryKey = null` and `$incrementing = false`, but `domain_links` has an `id` that `domain_link_weights.domain_link_id` points at.
- [ ] **M1 — `Domain` relations use the wrong table.** `children()` and `parents()` pass `'domain_nodes'` to `belongsToMany()`; `childNodes()` and `parentNodes()` return `DomainNode`.
- [ ] **M1 — `ApprenticeshipContext::domainEdges()`** returns `DomainEdge`, so it queries `domain_edges`.
- [ ] **Comment 5 — Domains can link to themselves.** `domain_links` has no `parent_id <> child_id` check; the old `evaluation_node_connections` table had one (`2026_10_01_112517`, lines 16 to 21).
- [ ] **Comment 6 — `child_id` has no index.** The unique `(parent_id, child_id)` only covers parent lookups. The old table had `index('child_id')`.
- [ ] **G4 — Link foreign keys lost `cascadeOnDelete`.** Old table cascaded; `domain_links` now uses the default (NO ACTION), so a domain that has links cannot be deleted. Not documented as a decision.
- [ ] **G2 — `domain_link_weights.domain_link_id` has no index** (`2026_10_01_122818`). The composite primary key only covers `apprenticeship_context_id`.
- [ ] **G5 — `domain_link_weights.weight` has no range check.** `../db/db.md` says it is a fraction from 0.01 to 1.
- [ ] **G2 — `apprenticeship_contexts.root_domain_id` has no index** (`2026_10_01_114802`).
- [ ] **M7 — `Domain::$rounding_step` docblock says `string`**, the column is nullable.

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
7. **G2 weights.** In `2026_10_01_122818`, add `$table->index('domain_link_id');`.
8. **G5 weights.** Add a CHECK. The column is created as `decimal(3,1)` here and changed to `decimal(3,2)` by `2026_10_07_120000`; put the CHECK in `2026_10_01_122818` as `CHECK (weight > 0 AND weight <= 1)`.
9. **G2 contexts.** In `2026_10_01_114802`, add `$table->index('root_domain_id');` inside the `apprenticeship_contexts` create. Do not touch the `users` part of that migration: the users index belongs to package 5 and goes in `2026_10_07_150000`.
10. **M7.** Docblock → `@property string|null $rounding_step`.

Check when done: `migrate:fresh` passes, `\d domain_links` shows the CHECK and the `child_id` index, and in tinker `Domain::first()?->children` runs without a missing-table error.

Nothing guards against longer cycles (A → B → A) any more: the old `EvaluationNode::addChild()` did it and was deleted. Flag to package 6 where the new guard should live.
