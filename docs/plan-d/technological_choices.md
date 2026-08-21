# Technological choices — links to learn

Reference material for the stack in `spec.md`.

## Backend

- Laravel 12 docs — https://laravel.com/docs/13.x
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

## Frontend

- Inertia.js — https://inertiajs.com/
- React 19 docs — https://react.dev/
- Tailwind CSS — https://tailwindcss.com/docs/installation
- Laravel + Inertia + React starter kit — https://laravel.com/docs/12.x/starter-kits#react

## Email testing

- Mailtrap — https://mailtrap.io/
- Laravel `Mail::fake()` — https://laravel.com/docs/12.x/mocking#mail-fake

## Rendering a PDF inline in React (for the grade detail + comment page)

- `react-pdf` (pdf.js wrapper, recommended for this project — no annotation UI needed) — https://github.com/wojtekmaj/react-pdf
- `@react-pdf-viewer/core` (fuller-featured viewer, plugin ecosystem — heavier than needed here but worth knowing) — https://react-pdf-viewer.dev/
- Mozilla pdf.js (underlying engine both wrappers use) — https://mozilla.github.io/pdf.js/

## WebSockets in Laravel (for live comment updates — stretch item, not MVP)

- Laravel Broadcasting overview (events, channels, authorization) — https://laravel.com/docs/12.x/broadcasting
- Laravel Reverb (first-party WebSocket server) — https://laravel.com/docs/12.x/reverb
- Laravel Echo (frontend client for broadcast events) — https://laravel.com/docs/12.x/broadcasting#client-side-installation
- Private/presence channel authorization (`routes/channels.php`) — https://laravel.com/docs/12.x/broadcasting#defining-authorization-callbacks

## Excel (reference only — not used per spec, `maatwebsite/excel` is explicitly out of scope)

- Laravel Excel — https://laravel-excel.com/
