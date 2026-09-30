# Permissions Guide

A short guide for anyone adding, removing or checking a permission without having built them. Roles, Microsoft groups and local accounts are in `docs/roles_guide.md`. The full rules are in `docs/project-docs/role_permissions.md`.

## 1. The idea in 4 lines

1. A **permission** is one thing a person may do, named `area.action` (`grades.comment`, `portfolio.manage-own`).
2. A **role** is a list of permissions. The list lives in `Permission::byRole()` (`app/Enums/Permission.php`).
3. The code checks **permissions, never role names**.
4. Roles and permissions are stored in the database by **Spatie laravel-permission**. Changing the enum does nothing until you **sync** (section 2, step 3).

```
Permission enum  ──►  byRole()  ──►  sync  ──►  DB (permissions, roles, role_has_permissions)
                                                     │
                     route `can:` / policy / `auth.can` flag  ◄── checks the DB
```

## 2. Add a permission

Example: `reports.export` for coaches.

1. **Enum case.** In `app/Enums/Permission.php`:
    ```php
    case ReportsExport = 'reports.export';
    ```
2. **Give it to roles.** In `Permission::byRole()`, add `self::ReportsExport` to the roles that get it. Do not touch `UserRole::Admin`: its list stays empty (section 6).
3. **Sync the database.** Locally:
    ```bash
    ./vendor/bin/sail artisan db:seed --class=RolesAndPermissionsSeeder
    ```
    For a deployed environment, read "How it reaches a deployed database" below.
4. **Protect the route** in `routes/web.php`:
    ```php
    Route::get('/reports', ...)->middleware('can:'.Permission::ReportsExport->value);
    ```
5. **Per-record rule (only if needed).** If the permission is not enough ("only for apprentices I supervise"), add a method in the policy (`app/Policies/GradePolicy.php`, `ProjectPolicy.php`, `CommentPolicy.php`, `UserPolicy.php`) and use `->middleware('can:view,grade')` or `$this->authorize()`. Combine the permission with `$user->supervises($apprentice)`. Prefer `$user->can(...)` over `hasPermissionTo(...)` (section 6).
6. **Frontend flag (only if the UI changes).**
    - Add a flag in `app/Http/Middleware/HandleInertiaRequests.php`, inside `auth.can`:
        ```php
        'exportReports' => $user?->can(Permission::ReportsExport->value) ?? false,
        ```
    - Add it to the `Can` type in `resources/js/types/auth.ts`.
    - Menu entry: add `can: 'exportReports'` to the item in `resources/js/constants/navigation.ts` (`NAV_ITEMS`). Hiding is cosmetic; the route in step 4 is the real protection.
7. **Test.** In `tests/Feature/AuthorizationTest.php` (see the `describe('coach', ...)` blocks for the pattern): one test that the right role gets 200 and another role gets 403, and if you added a flag, one line in `auth.can exposes permission booleans per role`.

### How it reaches a deployed database

- Nothing in the repo deploys automatically (no CI or deploy script). A deployed database only changes when someone runs a command on it.
- `composer setup` runs `migrate --force` and then `db:seed --class=RolesAndPermissionsSeeder --force`, but only when someone runs it. `migrate` alone does **not** run the seeder.
- So the safe way is a **small migration** that calls `RolesAndPermissions::sync()`. It is idempotent (it only creates what is missing), so it can be run any number of times. The repo already does this: `database/migrations/2026_09_30_100000_move_user_roles_to_permission_tables.php` and `database/migrations/2026_09_30_110000_add_admin_role.php`.
    ```php
    <?php

    use App\Support\RolesAndPermissions;
    use Illuminate\Database\Migrations\Migration;

    return new class extends Migration
    {
        public function up(): void
        {
            RolesAndPermissions::sync();
        }

        public function down(): void
        {
            //
        }
    };
    ```
    Create it with `./vendor/bin/sail artisan make:migration sync_reports_export_permission`, paste the body above, and commit it with the enum change.
- Without the migration, the deployed app has the code but not the permission in the database: see mistake 2 in section 8.

## 3. Remove a permission or take it away from a role

**Only take it away from a role**

1. Remove the enum case from that role in `Permission::byRole()`.
2. Sync (same as add, step 3).

`sync()` calls `syncPermissions()` on each role, so the role loses the permission.

**Delete the permission completely**

1. Find every usage first:
    ```bash
    grep -rn "GradesComment\|grades.comment" app routes resources/js tests docs
    ```
    Also grep the `auth.can` flag name (for example `viewSupervisedGrades`) if the permission has one.
2. Remove the usages: route middleware, policy checks, the `auth.can` flag, its TS type, nav items, tests.
3. Remove the enum case and its line in `byRole()`.
4. Sync.
5. **The row stays in the `permissions` table.** `RolesAndPermissions::sync()` only creates permissions (`findOrCreate`), it never deletes one. The roles lose it, so it grants nothing, but it stays listed. To delete it:
    ```bash
    ./vendor/bin/sail artisan tinker
    >>> Spatie\Permission\Models\Permission::where('name', 'grades.comment')->delete();
    ```
    Then `./vendor/bin/sail artisan permission:cache-reset`. For a deployed database put the same `delete()` line in a migration (with the `sync()` call before it).

## 4. Give or take a role from a person

**Production.** Nobody sets roles in the app. The role comes from the person's Microsoft Entra group. Move them to another group in Entra; the change applies at their next login or within 15 minutes. See `docs/roles_guide.md` (section 1 and "Add a new Azure group").

**Local.**

```bash
./vendor/bin/sail artisan tinker
>>> App\Models\User::where('email', 'apprentice-it@example.com')->first()->syncRoles('trainer');
```

Always `syncRoles()` (replaces), never `assignRole()` (adds): a user must have exactly one role.

## 5. Check the result

Does this person have the permission?

```bash
./vendor/bin/sail artisan tinker
>>> App\Models\User::where('email', 'coach@example.com')->first()->can('grades.comment');
```

What does a role have?

```bash
>>> Spatie\Permission\Models\Role::findByName('coach')->permissions->pluck('name');
```

Every role and permission at once:

```bash
./vendor/bin/sail artisan permission:show
```

Tests:

```bash
./vendor/bin/sail artisan test --filter=AuthorizationTest
```

## 6. The local admin

- Role `admin` (`UserRole::Admin`). It is a **development tool**: access to everything, so developers can build a feature without switching accounts. Log in with `admin@example.com` / `password` after `./vendor/bin/sail artisan migrate:fresh --seed`.
- **How it works.** `Permission::byRole()` gives admin an empty list. Instead, `app/Providers/AppServiceProvider.php` registers Spatie's documented super-admin `Gate::before`: it returns `true` when `$user->isLocalAdmin()`, else `null` (normal rules).
- **New permissions are covered automatically.** No line to add in `byRole()` for admin.
- **Local only.** `User::isLocalAdmin()` is `app()->environment('local') && hasRole('admin')`. The environment is checked each time it is called. Outside `local` the admin role grants nothing: every protected route returns 403.
- **It cannot come from Microsoft.** No `AzureGroup` maps to admin (`app/Enums/AzureGroup.php`).
- **Two places handle it explicitly**, in `app/Models/User.php`, because they do not go through the Gate:
    - `homeRoute()`: the admin lands on `apprentisdashboard`.
    - `supervises()`: the local admin supervises every apprentice.
- **Rule:** the bypass only works for `$user->can(...)`, `can:` middleware and `Gate`. A direct `$user->hasPermissionTo(...)` call ignores it, so the admin will **not** pass. Prefer `$user->can()`. Policies are not affected: the bypass answers before a policy runs. If you must call `hasPermissionTo()` or `supervises()` outside a Gate check (as `homeRoute()` does), add an `isLocalAdmin()` case.
- **In tests:** `User::factory()->admin()->create()`. Tests that need the bypass switch the environment with `app()->detectEnvironment(fn () => 'local')`; see `tests/Feature/AdminBypassTest.php`.

## 7. Command cheat sheet

| Goal                               | Command                                                               |
| ---------------------------------- | --------------------------------------------------------------------- |
| Sync roles and permissions locally | `./vendor/bin/sail artisan db:seed --class=RolesAndPermissionsSeeder` |
| Show roles and permissions         | `./vendor/bin/sail artisan permission:show`                           |
| Clear the Spatie cache             | `./vendor/bin/sail artisan permission:cache-reset`                    |
| Reset local data and accounts      | `./vendor/bin/sail artisan migrate:fresh --seed`                      |
| Run the authorization tests        | `./vendor/bin/sail artisan test --filter=AuthorizationTest`           |

## 8. Common mistakes

1. **`hasPermissionTo()` instead of `can()`.** The admin bypass is skipped (section 6). Also, `hasPermissionTo()` throws `Spatie\Permission\Exceptions\PermissionDoesNotExist` when the permission is not in the database, while `can()` simply answers `false`.
2. **Forgot to sync.** The enum case exists, but the database row does not. Symptom: `PermissionDoesNotExist` from a policy or `homeRoute()`, or a role that quietly lacks the permission. Fix: sync (section 2, step 3), and ship a migration for deployed databases.
3. **Stale Spatie cache.** A change in the database is not visible. Run `./vendor/bin/sail artisan permission:cache-reset`. (`RolesAndPermissions::sync()` already clears it.)
4. **Checking role names.** `$user->hasRole('coach')` in a policy, controller or Vue file. Check a permission instead. If two roles need the same thing, give both the permission.
5. **`assignRole()` instead of `syncRoles()`.** The user ends up with two roles. Use `syncRoles()`.
6. **Rebuilding rules in Vue.** Read `page.props.auth.can.*`. Never copy the role logic to the frontend.
7. **Only hiding the menu item.** The `can` on a `NAV_ITEMS` entry hides the link only. Protect the route too.
8. **Giving the admin explicit permissions.** Leave `UserRole::Admin` empty in `byRole()`.
