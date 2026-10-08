# Domains rollback

This file is the context for solving the issues below in the package of issue Domains rollback.

- **Depends on:** nothing. Can start immediately.
- **Blocks:** nothing.
- **Migrations are edited in place** (they are not on `main`). After pulling, everyone runs `./vendor/bin/sail artisan migrate:fresh`.

Files owned by this package (no other package edits them):

- `../../database/migrations/2026_10_07_090000_drop_subject_id_and_aggregation_from_domains_table.php`
- `../../database/migrations/2026_10_07_100000_drop_evaluation_node_id_from_apprenticeships_table.php`

# Problems encountered

- [ ] **Comment 1 — The migration cannot run a second time.** `up()` drops the foreign key `evaluation_nodes_subject_id_foreign` (the name it got when the table was still `evaluation_nodes`). `down()` line 26 recreates it with `constrained('subjects')`, which Laravel names `domains_subject_id_foreign`. Rollback then migrate fails on the `dropForeign`, even on an empty database.
- [ ] **G6 — `down()` does not restore the `subject_id` index.** The original table had `index('subject_id')` (`evaluation_nodes_subject_id_index`); dropping the column removed it.
- [ ] **G6 — `aggregation` CHECK comes back under another name.** The original was a `string` column plus `evaluation_nodes_aggregation_check`. `down()` uses `enum()`, which creates `domains_aggregation_check`. Harmless, but not the schema that existed before.
- [ ] **G6 — `2026_10_07_100000` `down()` does not restore the `evaluation_node_id` index** on `apprenticeships` (the original migration `2026_09_24_043324` added it explicitly).

# Fixes suggested

1. **Comment 1.** In `2026_10_07_090000` `down()`, create the column and the constraint separately so the name is explicit:

    ```php
    $table->foreignId('subject_id')->nullable();
    $table->foreign('subject_id', 'evaluation_nodes_subject_id_foreign')
        ->references('id')->on('subjects')->nullOnDelete();
    $table->index('subject_id', 'evaluation_nodes_subject_id_index');
    ```

2. **`aggregation`.** Replace the `enum()` with what the original migration did: `$table->string('aggregation')->nullable();` then, after the `Schema::table` call,
   `DB::statement("ALTER TABLE domains ADD CONSTRAINT evaluation_nodes_aggregation_check CHECK (aggregation IS NULL OR aggregation IN ('weighted_average'))");`
   (add the `DB` facade import).
3. **`2026_10_07_100000`.** In `down()`, add `$table->index('evaluation_node_id');` after the `foreignId`.

Check when done: `migrate:fresh`, then `migrate:rollback --step=7`, then `migrate` again, all pass.
