# Microsoft Entra ID Setup — Role Groups

Setup steps for the Microsoft Entra ID administrator. See `role_permissions.md` for what each role is allowed to do in the app.

Access is decided by **membership in mapped security groups**, read from Microsoft Graph (`transitiveMemberOf`) at login. App roles and the `groups` token claim are **not** used.

| Group          | Env variable                     | `services.azure.groups` key (`AzureGroup`) | App role   | Apprenticeship                  |
| -------------- | -------------------------------- | ------------------------------------------ | ---------- | ------------------------------- |
| IT apprentices | `MICROSOFT_GROUP_APPRENTICES_IT` | `apprentices_IT`                           | Apprentice | Informaticien·ne CFC            |
| EC apprentices | `MICROSOFT_GROUP_APPRENTICES_EC` | `apprentices_EC`                           | Apprentice | Employé·e de commerce CFC       |
| Trainers       | `MICROSOFT_GROUP_TRAINER`        | `trainer`                                  | Trainer    | Informaticien·ne CFC (see note) |

The group object ids are read from `config/services.php` (`services.azure.groups`), keyed by the `App\Enums\AzureGroup` values above. The role is stored as a Spatie role; there is no separate role column.

Coach has no mapped group yet: coach accounts cannot be resolved and are refused at login until one is added.

## Requirements

**Configured:**

- [ ] The three security groups above exist, with users as direct or nested members
- [ ] Graph application permissions `User.Read.All`, `GroupMember.Read.All` added **with admin consent granted**
- [ ] Redirect URIs registered (Web platform): `http://localhost/auth/microsoft/callback` (dev) and the production callback URL

**Sent to the maintainers** (securely, not the secret by plain email): Tenant ID, application (client) ID, client secret + expiry, and the Object ID of each of the three groups.

**Test accounts:** IT apprentice, EC apprentice, trainer, one user in no mapped group (refused login).

## Notes

- **All trainers are IT for now.** Trainer supervision is section-based (`User::supervises()`), so trainers receive the `Informaticien·ne CFC` apprenticeship at login. This changes once an EC trainer group exists.
- **Accounts are matched on the Entra object id only.** An existing local account with the same email but no matching `azure_id` is not adopted; the login is refused.
- **Exactly one mapped group per person.** No group, or several, refuses the login.
- **Changes are picked up at the next login**, and for a signed-in user within 15 minutes (`MICROSOFT_ACCOUNT_CHECK_INTERVAL`, in seconds): the role is re-applied from the group. There is no scheduled job or command; checks happen only at login and on requests of signed-in users.
- **Revoked access deactivates the account.** If a person has no mapped group, several mapped groups, a disabled Entra account, or no longer exists in Entra, the local account is set inactive and the session ended (or the login refused). If they later log in with exactly one mapped group, the account is reactivated. A person refused at their first login gets no local account.
- **Accounts are not created before the first login.** Adding someone to a group is enough; their account is created on first sign-in.
- **Microsoft outages do not log anyone out.** If Graph cannot be reached, signed-in users keep working and the re-check is retried after 60 seconds; a new login is refused.
- **Moving an apprentice between the IT and EC groups** does not change their apprenticeship until they confirm the section change (not built yet); until then their current apprenticeship is kept and a warning is logged, at login and on re-check. The person is not logged out.
- **Groups map to apprenticeships by their seeded name** (`Informaticien·ne CFC`, `Employé·e de commerce CFC`). If the apprenticeship is not seeded, the login is refused; for a signed-in user the re-check logs an error and lets the request through.
