# Grade intake by email

Apprentices can send a grade to the shared mailbox instead of using the dialog. `php artisan emails:fetch` (`app/Console/Commands/FetchEmails.php`) reads the unread mails of the `INBOX`, creates the grades and marks each mail as read.

`emails:fetch` is scheduled every 5 minutes (`routes/console.php`, without overlapping); it can also be run by hand.

## Mail format

| Part       | Rule                                                                                      |
| ---------- | ----------------------------------------------------------------------------------------- |
| Sender     | Must match a user's email (case-insensitive).                                             |
| Subject    | Exactly three words: `<branch name> <grade> <dd.mm.yyyy>`, e.g. `Anglais 5.0 14.10.2026`. |
| Branch     | Name of an `average_nodes` row (case-insensitive).                                        |
| Grade      | 1.0 to 6.0 in steps of 0.5.                                                               |
| Date       | Real date, not in the future.                                                             |
| Attachment | First non-inline attachment is stored as the grade's file (`grades/{user_id}/…`).         |

A mail that breaks a rule is skipped without error and still marked as read.

## Summary mail

After the run, `GradesReceivedNotification` is sent once per apprentice to the shared mailbox (`IMAP_USERNAME`), listing the imported grades. `GradeController::store` sends the same mail for a grade added from the app. A mail failure is reported or warned, never blocks the import.

## Configuration

| Variable                                | Purpose                                                         |
| --------------------------------------- | --------------------------------------------------------------- |
| `IMAP_HOST`, `IMAP_PORT`                | Intake server.                                                  |
| `IMAP_ENCRYPTION`, `IMAP_VALIDATE_CERT` | `ssl` / `true` in production, `false` / `false` with GreenMail. |
| `IMAP_USERNAME`, `IMAP_PASSWORD`        | Mailbox credentials; the username also receives summary mails.  |
| `MAIL_*`                                | Outgoing mail. Local: Mailpit (`mailpit:1025`).                 |

## Local test services (Sail)

| Service   | Ports (host)                  | Use                                    |
| --------- | ----------------------------- | -------------------------------------- |
| Mailpit   | `1025` SMTP, `8025` dashboard | Shows the mails the app sends.         |
| GreenMail | `3143` IMAP, `3025` SMTP      | Fake intake mailbox; auth is disabled. |

Override with `FORWARD_MAILPIT_PORT`, `FORWARD_MAILPIT_DASHBOARD_PORT`, `FORWARD_IMAP_PORT`, `FORWARD_IMAP_SMTP_PORT`.

## Not done yet

- Skipped mails give the sender no feedback.
