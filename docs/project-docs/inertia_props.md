# Inertia props rule

Targets Laravel 13.x and inertia-laravel 3.x.

## Rule

1. **If a value comes from an Eloquent model, it goes through a Resource and `->resolve()`.** That includes shared props and dropdown options. A plain array is only allowed when no model is involved: counts, booleans, `can` flags, enum options, config, static JSON.
   _Why:_ every prop is sent to the client, so a model's `$hidden`, `$appends` and casts would change the page payload without anyone noticing. `->resolve()` removes the `{data}` wrapper that `toResponse()` would add.
   Docs: [the protocol](https://inertiajs.com/docs/v3/core-concepts/the-protocol), [data wrapping](https://laravel.com/docs/13.x/eloquent-resources#data-wrapping)
2. **Keep the `meta` on paginated lists.** A paginated list is passed without `->resolve()`, or through `Inertia::scroll`. Per-apprentice lists stay unpaginated.
3. **Each resource declares its relations in a `RELATIONS` constant, and the controller eager-loads them.** `whenLoaded()` only leaves the key out when a relation is missing. It doesn't stop lazy loading. Turn on `Model::preventLazyLoading(! app()->isProduction())` to enforce this.
   Docs: [conditional relationships](https://laravel.com/docs/13.x/eloquent-resources#conditional-relationships), [preventing lazy loading](https://laravel.com/docs/13.x/eloquent-relationships#preventing-lazy-loading)
4. **Resources only format.** No calculations and no role checks in a resource:
    - query scopes decide which rows are loaded;
    - policies decide access;
    - per-row `can*` flags call a policy ability;
    - page-level flags go in a `can` prop.

    If two roles need different shapes, write a second resource instead of adding `when()`. If you do use `when()`, never pass a default and never put it in a nested array, or you get `null` or `{}` instead of a missing key.
    Docs: [conditional attributes](https://laravel.com/docs/13.x/eloquent-resources#conditional-attributes)

5. **Wrap expensive props in a closure** (`fn () => …`, `Inertia::optional`, `Inertia::defer`). Without the closure, the query runs even when a partial reload excludes that prop.
   Docs: [partial reloads](https://inertiajs.com/docs/v3/data-props/partial-reloads), [deferred props](https://inertiajs.com/docs/v3/data-props/deferred-props)
6. **TypeScript types are written by hand.** Each resource has a Pest contract test that asserts its exact keys, so a renamed field fails CI. Switch to `spatie/laravel-data` with `spatie/laravel-typescript-transformer` once there are more than 8 resources or a drift bug reaches production. The transformer can't generate types from a `JsonResource`.
   Docs: [fluent JSON testing](https://laravel.com/docs/13.x/http-tests#fluent-json-testing)

## Current code that breaks the rule

- `DossierController::skills()` sends a raw `Skill` collection; it needs a `SkillOptionResource`.
- `DossierController::preview` builds `owner` inline, and its `track` value doesn't match the one in `ApprenticeResource`.
- `HandleInertiaRequests::share` builds `auth.user` as an inline array; it needs a `CurrentUserResource`.
- `AppServiceProvider` doesn't call `preventLazyLoading`.
- None of the 3 resources has a contract test.
