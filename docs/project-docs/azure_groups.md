# Microsoft Entra ID Setup — Roles & Groups

Setup steps for the Microsoft Entra ID administrator. See `role_permissions.md` for what each role is allowed to do in the app.

Access is decided by two independent things:

- **App role** — *what* the user is (Apprentice, Trainer, Coach).
- **Section group** — *which section* the user belongs to (IT or EC). Coaches have no section.

## Requirements

What the app maintainers need back from the Entra administrator before development can continue.

**Configured** (steps 1–6 below):

- [ ] App roles `apprentice`, `trainer`, `coach` created on the app registration
- [ ] Security groups `Section-IT` and `Section-EC` created
- [ ] Groups claim (Security groups, Group ID) added to the ID token
- [ ] Assignment required = Yes on the enterprise application
- [ ] Graph application permissions `User.Read.All`, `GroupMember.Read.All`, `Application.Read.All` added **with admin consent granted**
- [ ] Redirect URIs registered (Web platform): `http://localhost/auth/microsoft/callback` (dev) and the production callback URL

**Sent to the maintainers** (securely, not by plain email for the secret):

| Value | Where to find it |
|---|---|
| Tenant ID | Entra ID → Overview |
| Application (client) ID | App registrations → *[app]* → Overview |
| Client secret + expiry date | App registrations → *[app]* → Certificates & secrets |
| Object ID of `Section-IT` | Groups → Section-IT → Overview |
| Object ID of `Section-EC` | Groups → Section-EC → Overview |
| Enterprise application (service principal) Object ID | Enterprise applications → *[app]* → Overview |

**Test accounts**, one per case, assigned as in step 4:

- [ ] IT apprentice, EC apprentice
- [ ] IT trainer, EC trainer
- [ ] Coach
- [ ] One user with no app role (to test refused login)

## 1. App roles

App registrations → *[app]* → App roles → Create app role.

| Display name | Allowed member types | Value | Description | Enabled |
|---|---|---|---|---|
| Apprentice | Users/Groups | `apprentice` | Apprentice access to own grades and portfolio | Yes |
| Trainer | Users/Groups | `trainer` | Trainer access to all apprentices of their section | Yes |
| Coach | Users/Groups | `coach` | Coach access to all apprentices (EC and IT) | Yes |

The **Value** must be typed exactly as shown: the app matches it character for character.

## 2. Section groups

Entra ID → Groups → New group.

| Group name | Group type | Membership type | Microsoft Entra roles can be assigned to the group | Members | Description |
|---|---|---|---|---|---|
| Section-IT | Security | Assigned | No | IT (dev) apprentices and IT trainers, added directly as users | IT section of the apprenticeship cursus app |
| Section-EC | Security | Assigned | No | EC apprentices and EC trainers, added directly as users | EC section of the apprenticeship cursus app |

## 3. Groups claim

App registrations → *[app]* → Token configuration → Add groups claim.

| Setting | Value |
|---|---|
| Group types | Security groups |
| ID token | Group ID |

The app reads the section from the `groups` claim, matching each section group's **Object ID**. Give the Object IDs of Section-IT and Section-EC to the app maintainers.

## 4. How to assign

Enterprise applications → *[app]* → Users and groups → Add user/group (app role). Entra ID → Groups → *[section group]* → Members (section).

| User type | App role | Section group |
|---|---|---|
| IT apprentice | `Apprentice` | `Section-IT` |
| EC apprentice | `Apprentice` | `Section-EC` |
| Trainer (works IT) | `Trainer` | `Section-IT` |
| Trainer (works EC) | `Trainer` | `Section-EC` |
| Coach | `Coach` | *(none — no section group at all)* |

## 5. Enterprise app property

Enterprise applications → *[app]* → Properties.

| Setting | Value |
|---|---|
| Assignment required? | Yes |

## 6. API permissions

App registrations → *[app]* → API permissions → Add a permission → Microsoft Graph → **Application permissions**, then **Grant admin consent**.

| Permission | Why |
|---|---|
| `User.Read.All` | Check that a signed-in account is still enabled |
| `GroupMember.Read.All` | Read section group members (daily sync, role checks) |
| `Application.Read.All` | Read who is assigned to which app role |

## Notes

- **Members must be users, not groups.** If a group contains other groups as members, the people in those groups get no role or section.
- **Licence:** assigning a group to an app role requires Entra ID P1 or P2. Assigning users directly does not.
- **One section per apprentice or trainer.** Never put a person in both Section-IT and Section-EC: the app refuses the login.
- **Apprentices and trainers need a section.** An apprentice or trainer with no section group is refused at login.
- **Coaches must not be in a section group.** Their access covers all apprentices; a section group is ignored.
- **One app role per person.** A person with several app roles (e.g. Coach and Trainer) is refused at login.
- **Changes are not instant.** The app picks up changes at the next login, within 15 minutes for someone already signed in, and at the latest after the daily sync.
- **Removing someone's app role deactivates them.** Their data is kept and they reappear if the role is given back.
- **Moving an apprentice between Section-IT and Section-EC deletes their grades** once they confirm it at their next login (projects are kept). Double-check before moving someone.
