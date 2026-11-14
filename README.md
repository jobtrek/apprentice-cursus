# Apprentice Cursus

## Description

A grade-tracking application designed to help coaches, trainers, and apprentices efficiently monitor and communicate grades and training progress, while following the existing conventions for grade reporting and training documentation.

Source-workflow is detailed in the [workflows](docs/workflows) directory

## Project set up

### Requirements

- Git
- Docker & Docker Compose (everything else, PHP, Composer, Node, pnpm and PostgreSQL, runs inside Laravel Sail)

### Start from zero

1. Clone the repository

```bash
git clone git@github.com:jobtrek/apprentice-cursus.git
cd apprentice-cursus
```

2. Install PHP dependencies (no local PHP needed: this runs Composer in a throwaway container)

```bash
docker run --rm -u "$(id -u):$(id -g)" -v "$(pwd):/var/www/html" -w /var/www/html \
    laravelsail/php84-composer:latest composer install --ignore-platform-reqs
```

This also points Git at `.githooks/`, so every commit runs Pint, Larastan and `vp check` on the staged files (Sail must be running).

3. Create the environment file and start the containers

```bash
cp .env.example .env
./vendor/bin/sail up -d
```

4. Generate the app key, then migrate and seed the database

```bash
./vendor/bin/sail artisan key:generate
./vendor/bin/sail artisan migrate --seed
```

With `APP_ENV=local` the seeders also create demo apprentices, grades and the local accounts below.

5. Install JS dependencies and start Vite

```bash
./vendor/bin/sail pnpm install
./vendor/bin/sail pnpm dev
```

The app is now available at http://localhost.

### Local accounts

Password login only exists in `local`. Every account uses the password `password`:

| Email                       | Role                           |
| --------------------------- | ------------------------------ |
| `admin@example.com`         | Admin (bypasses every check)   |
| `coach@example.com`         | Coach of the local apprentices |
| `trainer@example.com`       | IT trainer                     |
| `trainer-ec@example.com`    | EC trainer                     |
| `apprentice-it@example.com` | IT apprentice                  |
| `apprentice-ec@example.com` | EC apprentice                  |

### Optional: Microsoft Entra SSO and account sync

Fill the `MICROSOFT_*` variables in `.env` (tenant, client id/secret and the five group ids). Then pull the accounts from the Entra groups:

```bash
./vendor/bin/sail artisan azure:sync
```

In production the scheduler runs this sync daily (`routes/console.php`).

### Optional: OCR grade reading

OCR is on by default (`OCR_ENABLED=true`). It needs `GEMINI_API_KEY` and `TYPESAFE_API_KEY` in `.env`.

### Optional: grades by email

Apprentices can also send a grade by email; `emails:fetch` imports it. Sail starts two test services for this: **Mailpit** (catches the mails the app sends, http://localhost:8025) and **GreenMail** (a fake IMAP/SMTP server acting as the intake mailbox). The defaults in `.env.example` already point to them. To try it:

1. Send a mail to GreenMail (subject `<branch> <grade> <dd.mm.yyyy>`, sent from the apprentice's address, PDF attached). Quick text-only version:

```bash
printf 'From: apprentice-it@example.com\nTo: grades@example.com\nSubject: Anglais 5.0 14.10.2026\n\nhello\n' \
    | curl -s --url smtp://localhost:3025 --mail-from apprentice-it@example.com \
        --mail-rcpt grades@example.com -T -
```

2. Import it:

```bash
./vendor/bin/sail artisan emails:fetch
```

3. Open Mailpit: the shared mailbox received one summary mail per apprentice. Grades added from the app send the same summary.

See [docs/email-intake.md](docs/email-intake.md) for the format and the rules.

### Checks

| What                 | Command                                  |
| -------------------- | ---------------------------------------- |
| Tests (Pest)         | `./vendor/bin/sail artisan test`         |
| PHP format (Pint)    | `./vendor/bin/sail composer lint`        |
| PHP static analysis  | `./vendor/bin/sail composer types:check` |
| JS/Vue format + lint | `./vendor/bin/sail pnpm check:fix`       |
| Vue types            | `./vendor/bin/sail pnpm types:check`     |
| Everything, like CI  | `./vendor/bin/sail composer ci:check`    |

### CI

`.github/workflows/ci.yml` runs on every pull request and on pushes to `main`:

1. **Lint & format**: Pint, Larastan, `vp check` (Oxfmt, Oxlint, ESLint) and `vue-tsc`. Checks only, it never rewrites code: run the fix commands above locally.
2. **Seed & tests**: builds the assets, runs `migrate:fresh --seed` on PostgreSQL 18 (local seeders included), then the Pest suite.

## Stack

| Tool       | Version |
| ---------- | ------- |
| PHP        | 8.5     |
| Vue        | 3.x     |
| Laravel    | 13.x    |
| Inertia    | latest  |
| Vite       | 8.x     |
| Tailwind   | 4.x     |
| shadcn-vue | latest  |
| PostgreSQL | latest  |

## Features

Application distinguish in two main features: a grade managment tool and a formation file managment tool

### Grade managment

Source : [Nommage et envoi des notes](docs/workflows/nommage-note.md)

#### Grade transmission automation

Apprentices will be able to drag and drop their test PDF in the app. A notification will then be sent to both their trainers and assigned coaches.

Grades can also be sent by email to the shared mailbox: `emails:fetch` imports them and sends one summary mail per apprentice ([details](docs/email-intake.md)).

#### Grade dashboard

The grades will be accessible in dashboard, giving apprentices an overview of their own grades, and trainers / coaches an overview of the grades of apprentice they're responsible of.

#### Grades comment

Trainers and coaches will be able to add commentaries to apprentice grades. They also will be able to download the PDF of the test

### Formation file

Apprentice need to complete a formation file during their cursus, so if something happen that could endanger their formation, they can justify with their formation file.

Source : [Création et maintenance du dossier de formation](docs/workflows/dossier-formation.md)

#### Formation file update

An apprentice can access and update their formation file via a dedicated form, featuring all the required shield.

#### Formation file overview

Apprentice, coaches and trainers are able to see overview of formation file. They can also download that preview, taking the shape of a PDF files.

#### Formation file commentary

Trainers and coaches are able to add commentary to part of the project, so apprentice can come back to modify required part or discuss with coaches / trainers.

### Notification system

For some event, notification may be useful and sent via email to users, so they know when they need to act on something, read something etc.
