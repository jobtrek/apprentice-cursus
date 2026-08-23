# Technological choices — reference links

Reference material for the stack in `docs/spec-d/spec.md`.

## Backend

- Laravel 12 docs — https://laravel.com/docs/12.x
- Eloquent relationships (`hasOne`, `belongsTo`) — https://laravel.com/docs/12.x/eloquent-relationships
- Migrations — https://laravel.com/docs/12.x/migrations
- Seeding — https://laravel.com/docs/12.x/seeding
- Authentication — https://laravel.com/docs/12.x/authentication
- Authorization / Policies — https://laravel.com/docs/12.x/authorization
- Validation / Form Requests — https://laravel.com/docs/12.x/validation
- File storage — https://laravel.com/docs/12.x/filesystem
- Mail (Mailables) — https://laravel.com/docs/12.x/mail
- Testing — https://laravel.com/docs/12.x/testing
- Pest PHP (test framework) — https://pestphp.com/docs/installation

## Database

- PostgreSQL — chosen over MySQL/SQLite for native enum types and `CHECK` constraints (see `docs/spec-d/spec.md` decisions #5 and #6 — decision #7 moved to the dossier spec)
- Laravel + PostgreSQL — https://laravel.com/docs/12.x/database#postgresql
- Check constraints in migrations — https://laravel.com/docs/12.x/migrations#check-constraints

## Frontend

- Inertia.js — https://inertiajs.com/
- React 19 docs — https://react.dev/
- Tailwind CSS — https://tailwindcss.com/docs/installation
- shadcn/ui — https://ui.shadcn.com/ — component source pulled in per-surface (forms, dialogs, tables), not a package dependency
- Laravel + Inertia + React starter kit — https://laravel.com/docs/12.x/starter-kits#react

## Email testing

- Mailtrap — https://mailtrap.io/
- Laravel `Mail::fake()` — https://laravel.com/docs/12.x/mocking#mail-fake

## Rendering a PDF inline in React (for the grade detail + comment page)

- `react-pdf` (pdf.js wrapper, recommended for this project — no annotation UI needed) — https://github.com/wojtekmaj/react-pdf
- `@react-pdf-viewer/core` (fuller-featured viewer, plugin ecosystem — heavier than needed here but worth knowing) — https://react-pdf-viewer.dev/
- Mozilla pdf.js (underlying engine both wrappers use) — https://mozilla.github.io/pdf.js/

## Dossier PDF export (real app, not the POC)

- `spatie/laravel-pdf` (Browsershot/headless Chrome under the hood) — https://spatie.be/docs/laravel-pdf
- Requires headless Chrome + Node available on the server — deployment target must support this (see Open Question #6 in `docs/spec-d/spec.md`)

## WebSockets in Laravel (for live comment updates — stretch item, not MVP)

- Laravel Broadcasting overview (events, channels, authorization) — https://laravel.com/docs/12.x/broadcasting
- Laravel Reverb (first-party WebSocket server) — https://laravel.com/docs/12.x/reverb
- Laravel Echo (frontend client for broadcast events) — https://laravel.com/docs/12.x/broadcasting#client-side-installation
- Private/presence channel authorization (`routes/channels.php`) — https://laravel.com/docs/12.x/broadcasting#defining-authorization-callbacks

## Rejected — reference only

Kept so nobody re-researches a decision that was already made. Both are out of scope per `docs/spec-d/spec.md`.

- **Excel** — `maatwebsite/excel` is explicitly out; the app replaces the workbook rather than reading it. https://laravel-excel.com/
- **OCR of the scanned test** — the apprenti types the grade (GR-03), so nothing reads the paper. Tesseract in Laravel, if that is ever revisited: https://medium.com/@peterochieng008/text-extraction-from-images-in-laravel-using-tesseract-ocr-a2449031a25c
