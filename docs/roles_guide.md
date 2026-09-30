# Roles Guide

A short guide for anyone touching roles, login or emails without having built them. The full rules are in `docs/project-docs/role_permissions.md`.

## 1. The idea in 4 lines

1. A person logs in with Microsoft.
2. The app asks Microsoft which **group** they are in.
3. The group gives them a **role** and a **section** (IT or EC).
4. The role gives them a list of **permissions**. The code only checks permissions.

```
Microsoft group  ──►  role        ──►  permissions
apprentices_IT   ──►  apprentice  ──►  grades.create, grades.view-own, portfolio.manage-own
apprentices_EC   ──►  apprentice  ──►  (same as IT, different grade tree)
trainer          ──►  trainer     ──►  grades.view-supervised, grades.comment,
                                       portfolio.view-supervised, apprentices.view-list
(no group yet)   ──►  coach       ──►  trainer's list + coaching.assign-self
```

## 2. Where things live

| What                                  | File                                              |
| ------------------------------------- | ------------------------------------------------- |
| Group → role and section              | `app/Enums/AzureGroup.php`                        |
| Role → permissions                    | `app/Enums/Permission.php` (`byRole()`)           |
| Group ids (secrets)                   | `.env` → `MICROSOFT_GROUP_*`                      |
| Login with Microsoft                  | `app/Http/Controllers/Auth/MicrosoftAuthController.php` |
| Create / update the user from Azure   | `app/Services/MicrosoftLoginService.php`, `app/Services/AzureAccountSync.php` |
| Re-check every 15 min while logged in | `app/Http/Middleware/EnsureAzureAccountIsActive.php` |
| "Can this person see that apprentice" | `User::supervises()` + `app/Policies/*`           |
| Local test accounts                   | `database/seeders/UserSeeder.php`                 |

Roles are stored by **Spatie laravel-permission** (tables `roles`, `model_has_roles`, …). There is no `users.role` column. `$user->role` reads the Spatie role.

## 3. Testing locally (no Microsoft needed)

Password login only exists when `APP_ENV=local`.

```bash
./vendor/bin/sail artisan migrate:fresh --seed
```

| Email                       | Password   | Acts like                                   |
| --------------------------- | ---------- | ------------------------------------------- |
| `trainer@example.com`       | `password` | A real Azure trainer, IT section (role, section, permissions all identical) |
| `admin@example.com`         | `password` | A coach: coach of the 8 demo apprentices and of both local apprentices. No Azure group exists for coaches yet |
| `apprentice-it@example.com` | `password` | A real Azure IT apprentice. Coach is `admin@example.com`, supervised by the local trainer |
| `apprentice-ec@example.com` | `password` | A real Azure EC apprentice. Coach is `admin@example.com` |

The 8 `demo-apprentice-N@example.com` accounts have random passwords: they are data to look at, not accounts to log in with.

### What is the same as Azure

- Same roles, same permissions, same landing page after login.
- The local trainer has the IT section, like a real Azure trainer.
- The local apprentices have a section (IT or EC), like real Azure apprentices.
- A deactivated account (`is_active = false`) is refused and logged out, the same way.

### What is different from Azure

- Local accounts have no `azure_id`, so the 15-minute Microsoft re-check is skipped. To test "access removed", set `is_active = false` by hand.
- Coaches cannot log in with Microsoft at all today. The local coach is the only way to test the coach role.

### Change someone's role locally

```bash
./vendor/bin/sail artisan tinker
>>> App\Models\User::where('email', 'apprentice-it@example.com')->first()->syncRoles('trainer');
```

Always use `syncRoles()` (replaces), not `assignRole()` (adds): a user must have exactly one role.

## 4. Common tasks

**Give a role a new permission**
1. Add the case in `app/Enums/Permission.php`.
2. Add it to the role in `Permission::byRole()`.
3. `./vendor/bin/sail artisan db:seed --class=RolesAndPermissionsSeeder`.
4. Protect the route with `->middleware('can:'.Permission::X->value)`.

**Add a new Azure group (e.g. coaches)**
1. Add a case in `app/Enums/AzureGroup.php` and fill `role()` and `apprenticeship()`.
2. Add the `.env` key in `config/services.php` under `azure.groups` and in `.env.example`.
3. Ask the Microsoft admin for the group's object id.

**Check a permission in code**
```php
$user->can(Permission::GradesComment->value);   // yes
$user->hasRole('trainer');                      // avoid: check permissions, not roles
```

In Vue, read `page.props.auth.can.*`. Never rebuild the rules in the frontend.

## 5. Sending emails later (read this first)

**Who to email**

```php
use App\Enums\UserRole;

// Everyone with a role (only active accounts)
User::role(UserRole::Trainer->value)->where('is_active', true)->get();

// The people supervising one apprentice
$apprentice->coach;                                             // their coach, may be null
User::role(UserRole::Trainer->value)
    ->where('apprenticeship_id', $apprentice->apprenticeship_id)
    ->where('is_active', true)
    ->get();                                                    // trainers of their section
```

**Which address `users.email` holds**

- It is the Microsoft **userPrincipalName** (the login name), not the Microsoft `mail` field. In most tenants they are the same; check with the Microsoft admin before relying on it.
- It is written **once**, at the first login. If the address changes in Microsoft later, the app keeps the old one.
- A user only exists in the app after their first login. People who never logged in cannot be emailed.
- Local accounts use `@example.com`: never send real mail from local. Keep `MAIL_MAILER=log` in `.env` (mails land in `storage/logs/laravel.log`).

**Sending through Microsoft**

The app registration today only has `User.Read.All` and `GroupMember.Read.All`. Sending mail through Microsoft (Graph or Office 365 SMTP) needs extra setup from the Microsoft admin.

**Notifications stored in the database**

`User` uses `Notifiable`. If you add database notifications, add them to `Relation::enforceMorphMap()` in `app/Providers/AppServiceProvider.php`, or Laravel will throw.

## 6. When something goes wrong

| Symptom                                   | Likely cause                                            |
| ----------------------------------------- | ------------------------------------------------------- |
| "No access" at Microsoft login            | User is in no mapped group, or in two of them           |
| Logged out after a while                  | Removed from the group or disabled in Microsoft         |
| Login fails, "apprenticeship" in the logs | `ApprenticeshipSeeder` was not run                      |
| Page returns 403                          | Role lacks the permission, or the user does not supervise that apprentice |
| Role change not visible                   | Spatie cache: `./vendor/bin/sail artisan permission:cache-reset` |

Logs: `storage/logs/laravel.log`, search for `Microsoft`.
