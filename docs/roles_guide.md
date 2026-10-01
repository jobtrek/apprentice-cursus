# Roles Guide

Full rules: `docs/project-docs/role_permissions.md`. Adding or removing a permission: `docs/permissions_guide.md`.

## How it works

Microsoft group → role + section → permissions. **Code only checks permissions.**

| Group            | Role       | Section |
| ---------------- | ---------- | ------- |
| `apprentices_IT` | apprentice | IT      |
| `apprentices_EC` | apprentice | EC      |
| `trainer_IT`     | trainer    | IT      |
| `trainer_EC`     | trainer    | EC      |
| `coach`          | coach      | —       |

Roles are Spatie roles (no `users.role` column). Accounts are created, updated and deactivated by the daily account sync (`php artisan azure:sync` runs it on demand); logging in never creates an account. Access is re-checked against Microsoft every 15 min.

## Where things live

| What                         | File                                                    |
| ---------------------------- | ------------------------------------------------------- |
| Group → role/section         | `app/Enums/AzureGroup.php`                              |
| Role → permissions           | `app/Enums/Permission.php` (`byRole()`)                 |
| Group ids                    | `.env` → `MICROSOFT_GROUP_*`                            |
| Login + per-user sync        | `MicrosoftLoginService`, `AzureAccountSync`             |
| Daily account sync           | `AzureDirectorySync`, `SyncAzureAccounts`, `azure:sync` |
| 15-min re-check              | `EnsureAzureAccountIsActive` middleware                 |
| Who can see which apprentice | `User::supervises()` + `app/Policies/*`                 |

## Local testing

```bash
./vendor/bin/sail artisan migrate:fresh --seed
```

All passwords are `password` (password login is local only).

| Email                       | Role                                    |
| --------------------------- | --------------------------------------- |
| `admin@example.com`         | admin (bypasses everything, local only) |
| `coach@example.com`         | coach                                   |
| `trainer@example.com`       | trainer, IT (Bastien Nicoud)            |
| `trainer-ec@example.com`    | trainer, EC                             |
| `apprentice-it@example.com` | apprentice, IT                          |
| `apprentice-ec@example.com` | apprentice, EC                          |

- Test features with the real role, not admin, because admin hides missing permissions.
- Local accounts skip the Microsoft re-check. To test lost access, set `is_active = false`.
- To change a role, use `$user->syncRoles('trainer')`, never `assignRole()` (a user must have exactly one role).

## Rules

- Check `$user->can(Permission::X->value)`, never `hasRole()`.
- In Vue, use `page.props.auth.can.*`. Never rebuild rules in the frontend.
- New Azure group: add a case in `AzureGroup`, add the `.env` key in `config/services.php` and `.env.example`, then ask the Microsoft admin for the group id.

## Emails (gotchas)

- `users.email` is the Microsoft login name, saved once at first login and never updated.
- Users who never logged in don't exist in the app.
- Keep `MAIL_MAILER=log` locally.
- Sending via Microsoft needs extra permissions from the admin.

## Troubleshooting

| Symptom                       | Cause                                                    |
| ----------------------------- | -------------------------------------------------------- |
| "No access" at login          | In no mapped group, or in two                            |
| Logged out after a while      | Removed from group or disabled in Microsoft              |
| Login fails, "apprenticeship" | `ApprenticeshipSeeder` not run                           |
| 403                           | Missing permission, or doesn't supervise that apprentice |
| Role change not showing       | `sail artisan permission:cache-reset`                    |

Logs: `storage/logs/laravel.log`, search for `Microsoft`.
