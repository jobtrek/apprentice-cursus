# Grade intake by email

Apprentices can send a grade to the shared mailbox instead of using the dialog. `php artisan emails:fetch` (`app/Console/Commands/FetchEmails.php`) reads the unread mails of the `INBOX`, creates the grades and marks each mail as read.

`emails:fetch` is scheduled every 5 minutes (`routes/console.php`, without overlapping); it can also be run by hand.

## Mail format

| Part       | Rule                                                                                |
| ---------- | ----------------------------------------------------------------------------------- |
| Sender     | Authenticated (see below), and an active user's email who may create grades.        |
| Subject    | `<branch name> <grade> <dd.mm.yyyy>`, e.g. `TPI - Documentation 5.0 14.10.2026`.    |
| Branch     | Name of a leaf of the apprentice's own plan (case-insensitive); may contain spaces. |
| Grade      | 1.0 to 6.0 in steps of 0.5.                                                         |
| Date       | Real date, not in the future.                                                       |
| Attachment | First PDF attachment (10 MB max) is the grade's file; other types are ignored.      |

A mail that breaks a rule is skipped without error and still marked as read. A mail whose
attachment cannot be stored stays unread and is retried on the next run. Each grade records
its mailbox and IMAP UID (unique), so a mail is never imported twice.

## Sender authentication

`From` alone is not proof. The receiving server's topmost `Authentication-Results` header,
whose authserv-id must equal `IMAP_TRUSTED_AUTHSERV_ID` (e.g. `mx.google.com`), must report
`dmarc=pass` for the sender's domain. Without that variable, `From` is trusted in `local`
only (GreenMail adds no such header); elsewhere every mail is rejected.

## Notifications

After each import, `GradeAddedNotification` goes to the apprentice's supervisors, as for a
grade added in the app. After the run, `GradesReceivedNotification` is sent once per apprentice to the shared mailbox (`IMAP_USERNAME`), listing the imported grades. `GradeController::store` sends the same mail for a grade added from the app. A mail failure is reported or warned, never blocks the import.

## Configuration

| Variable                                | Purpose                                                         |
| --------------------------------------- | --------------------------------------------------------------- |
| `IMAP_HOST`, `IMAP_PORT`                | Intake server.                                                  |
| `IMAP_ENCRYPTION`, `IMAP_VALIDATE_CERT` | `ssl` / `true` in production, `false` / `false` with GreenMail. |
| `IMAP_USERNAME`, `IMAP_PASSWORD`        | Mailbox credentials; the username also receives summary mails.  |
| `IMAP_TRUSTED_AUTHSERV_ID`              | Receiving server whose DMARC verdict proves the sender.         |
| `MAIL_*`                                | Outgoing mail. Local: Mailpit (`mailpit:1025`).                 |

## Local test services (Sail)

| Service   | Ports (host)                  | Use                                    |
| --------- | ----------------------------- | -------------------------------------- |
| Mailpit   | `1025` SMTP, `8025` dashboard | Shows the mails the app sends.         |
| GreenMail | `3143` IMAP, `3025` SMTP      | Fake intake mailbox; auth is disabled. |

Ports are bound to `127.0.0.1` only.

Override with `FORWARD_MAILPIT_PORT`, `FORWARD_MAILPIT_DASHBOARD_PORT`, `FORWARD_IMAP_PORT`, `FORWARD_IMAP_SMTP_PORT`.

## Not done yet

- Skipped mails give the sender no feedback.
