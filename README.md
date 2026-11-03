# PlusSave HR

The real HR system for PlusSave Financial Solutions: staff records, leave and maker-checker
approvals on Laravel, with role permissions and an audit trail.

This repository holds two branches.

- `main` is the clickable prototype. It is static HTML, CSS and JavaScript, and GitHub Pages
  serves it at https://gorretgolden.github.io/PlussaveHRSystem/. No build step, no database.
- `master` (this branch) is the implementation: a Laravel app reading and writing a MySQL
  database, with real logins, real permissions and real approval chains.

## What is built here

- Sign in with a username and password, account lockout after failed attempts, and a forced
  password change on first use.
- Roles: staff, HR manager, managing director, finance, internal auditor, system admin. Each
  role gets its own dashboard and its own set of permissions.
- Employee register and employee file, filtered and searchable, with document status.
- Leave applications with working-day counts that skip weekends and public holidays, balance
  checks on the server, overlap detection and an approval path.
- Maker-checker workflow: someone initiates, HR verifies, the managing director approves. The
  person who raised a request cannot approve it, and a role must hold the privilege for its
  stage.
- Audit trail of every action, and the roles and permissions screen, which itself changes
  through the same approval path.

Attendance, payroll runs, performance, recruitment and the other prototype screens are not
built yet. The data in the seeder is sample data. It is not real staff information.

## Setup

```sh
composer install
cp .env.example .env
php artisan key:generate
```

Set the database keys in `.env` (`DB_CONNECTION=mysql`, `DB_HOST`, `DB_DATABASE`,
`DB_USERNAME`, `DB_PASSWORD`), then:

```sh
php artisan migrate --seed
npm ci
npm run build
php artisan serve
```

The compiled assets in `public/build` are not committed. Run `npm run build` after every
checkout that changes a view, on your machine and on the server.

One scheduled command keeps employee status in step with the approved leave calendar:

```sh
php artisan schedule:run   # hr:sync-leave-status runs daily at 00:05
```

## Test accounts from the seeder

| Username | Role | Signs in as |
| --- | --- | --- |
| brenda | staff | self-service only, her own leave and payslips |
| ruth | hr manager | verifies requests, maintains employee files |
| daniel | managing director | approves at the final stage |
| ivan | system admin | roles screen and settings, no employee data |

Password for all of them: `PlusSave#2026`. Change it before any real use.

## Tests

```sh
php artisan test
vendor/bin/pint --test
```
