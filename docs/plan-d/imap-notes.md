# IMAP / Outlook intake — background notes (not part of Plan D scope)

**Status:** research notes only. Plan D does not implement this — see `.scratch/plan-d/spec.md` for why. Kept here in case the team revisits live inbox intake later (Plan B territory).

## What IMAP is

IMAP (Internet Message Access Protocol, port 143 plain / 993 TLS = "IMAPS") is how a client reads mail that lives on a server, without downloading-and-deleting it. The client connects, authenticates, and can list folders, fetch message headers/bodies/attachments, search, flag as read, etc. — the mail stays on the server the whole time. This is different from POP3 (older, downloads and typically removes mail from the server) and different from a REST API like Microsoft Graph (higher-level, not the raw mail protocol).

Relevant distinction for this project: IMAP is a protocol for reading a mailbox. It says nothing about how you prove who you are to that mailbox — that's the authentication layer, and it's the part that actually matters here.

## Two ways to authenticate to Outlook/M365 IMAP

### 1. Basic auth (username + password) — dead end
This used to be `IMAP login: apprentice@jobtrek.ch / their-password`. Microsoft disabled this for Exchange Online/Outlook.com in October 2022 (deprecated well before that). Not available anymore for normal accounts. Don't design around it.


# We can't do this. we have to make it as simple as possible. without intefering with real jobtrek it's infra.
### 2. OAuth2 / "Modern Auth" — the only real option
The flow, roughly:

1. **App registration in Entra ID (Azure AD).** Someone with tenant admin rights (Jobtrek's IT, not you) registers your app, gets a client ID/secret, and configures redirect URIs and requested scopes (e.g. `IMAP.AccessAsUser.All` or, more likely, Microsoft Graph's `Mail.Read`).
2. **Consent screen per user.** Each apprentice, once, visits a Microsoft login page your app redirects them to, signs in with their own Outlook credentials directly to Microsoft (never touches your app/server), and clicks "allow this app to read your mail."
3. **Token exchange.** Microsoft redirects back to your app with an authorization code; your backend exchanges that code for an access token + refresh token (standard OAuth2 authorization-code flow).
4. **Using the token.** Your app uses the access token as the IMAP "password" via SASL XOAUTH2, or — more commonly today — just calls the Microsoft Graph API directly instead of IMAP at all (`GET /me/messages`), since Graph is the modern, documented, non-deprecated path Microsoft actually wants you on.
5. **Token refresh.** Access tokens expire (typically ~1hr); your app stores the refresh token (encrypted, per apprentice) and silently re-requests new access tokens as needed, until the apprentice revokes consent or the refresh token itself expires.

## Why this is a bigger lift than it sounds

- Requires Jobtrek IT / an Entra tenant admin to register the app and approve the requested permission scope — you can't self-serve this as a student building a school project.
- Requires each apprentice to go through a real Microsoft consent flow — not something you can fake or stub for a demo without an actual tenant.
- Requires securely storing a refresh token per apprentice (a real credential, encrypted at rest) — this *is* a genuine data-protection concern, unlike the rest of this school-project scope.
- Graph API (not raw IMAP) is what Microsoft actually steers integrators toward now — worth researching Graph directly rather than IMAP+OAuth if this gets picked up later, since Graph is REST/JSON, better documented, and is the same mechanism Plan A's spec already flagged under "Microsoft Graph API / Entra ID integration" as deferred.

## Where this sits relative to the plans

- Plan A: lists "Microsoft Graph API / Entra ID integration" and "OneDrive/SharePoint sync" as explicitly deferred, later-phase (Plan B) work.
- Plan C: assumed either a shared forwarding inbox (no OAuth needed, but requires Jobtrek to set up a real forwarding address) or per-apprentice IMAP polling (needs the OAuth flow above).
- Plan D: sidesteps this entirely for the 6-week demo by using a dropped `.eml` file instead of any live connection. This document exists so that if the project continues past the school deadline, the team has a starting map instead of rediscovering all of this from scratch.

## Terms worth searching next

- "Microsoft Graph API Mail.Read delegated permission"
- "OAuth2 authorization code flow"
- "IMAP XOAUTH2 SASL"
- "Entra ID app registration"
- "Exchange Online basic auth deprecation"
