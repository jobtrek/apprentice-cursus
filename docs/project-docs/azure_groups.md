# Microsoft Entra ID Setup — Role Groups

Setup steps for the Microsoft Entra ID administrator. See `role_permissions.md` for what each role is allowed to do in the app.

Access is decided by **membership in mapped security groups**, read from Microsoft Graph (`transitiveMemberOf`) at login. App roles and the `groups` token claim are **not** used.

| Group | Env variable | App role | Apprenticeship |
|---|---|---|---|
| IT apprentices | `MICROSOFT_GROUP_APPRENTICES_IT` | Apprentice | Informaticien·ne CFC |
| EC apprentices | `MICROSOFT_GROUP_APPRENTICES_EC` | Apprentice | Employé·e de commerce CFC |
| Trainers | `MICROSOFT_GROUP_TRAINER` | Trainer | Informaticien·ne CFC (see note) |

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
- **Changes are picked up at the next login**, and for a signed-in user within 15 minutes (role re-checked; access ended if no group remains or the account is disabled).
- **Moving an apprentice between the IT and EC groups** does not change their apprenticeship until they confirm the section change; until then their current apprenticeship is kept.
- Seeded apprenticeships must exist (`Informaticien·ne CFC`, `Employé·e de commerce CFC`), otherwise apprentice logins are refused.
