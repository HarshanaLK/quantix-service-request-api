# Quantix Service Request & Approval API — Repository Architecture

A **Laravel-only REST API** for employee service requests, manager approval, and operations fulfillment. Uses **MySQL**, Laravel Sanctum token authentication, a **service + repository-interface architecture**, Laravel Policies, activity history, comments, and queued notifications. **No Inertia.js, React, Vue, or frontend is included.**

## Architecture at a glance

`Controller -> Service -> Repository Interface -> Eloquent Repository -> MySQL`.

Business permissions remain in Policies; state transitions remain centralized in `RequestWorkflow`. `app/Providers/RepositoryServiceProvider.php` registers four interfaces, wired in `bootstrap/providers.php`. Refer to [docs/ARCHITECTURE.md](docs/ARCHITECTURE.md) and [docs/REPOSITORY_PATTERN_GUIDE.md](docs/REPOSITORY_PATTERN_GUIDE.md) for structure, examples, and trade-offs. Existing endpoint URLs, Postman examples and seeded accounts are preserved.

## Prerequisites

- **PHP 8.3 or newer for the supplied composer.lock** (Laravel itself supports PHP 8.2, but the locked PHPUnit 12 and Pint versions need 8.3+) and extensions: `pdo_mysql`, `mbstring`, `xml`, `curl`, `zip`, `openssl`, `fileinfo`, `ctype`, `tokenizer`, `json`, `bcmath`.
- **Composer 2**: <https://getcomposer.org/download/>
- **MySQL 8+** (or a compatible version) running locally, e.g. through XAMPP, Laragon, or standalone MySQL.
- This is **a backend API, not a frontend website**. Use Postman, curl, or another HTTP client to interact with it.

Check your installation:

```bash
php -v
composer --version
php -m
```

## Quick start: Windows (PowerShell, MySQL)

1. Start the MySQL server (e.g. in XAMPP or Laragon).
2. Create **only the application database** using phpMyAdmin or a MySQL client:

```sql
CREATE DATABASE IF NOT EXISTS quantix_requests CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

3. Extract the ZIP and open PowerShell inside the extracted `quantix-service-request-api` folder containing `artisan` and `composer.json`.
4. Install dependencies, create the local environment file, and generate the application key:

```powershell
composer install
Copy-Item .env.example .env
php artisan key:generate
```

5. Edit `.env` for your MySQL installation:

```dotenv
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=quantix_requests
DB_USERNAME=root
DB_PASSWORD=
```

`root` with an empty password is common **only for local XAMPP demos**. Use your actual credentials, never publish this setup. If your MySQL port is different (e.g. `3307`), update `DB_PORT`.

6. Initialize the application database and start the API:

```powershell
php artisan optimize:clear
php artisan migrate --seed
php artisan serve
```

The API is served at **http://127.0.0.1:8000/api** and the health route is **http://127.0.0.1:8000/up**. `/api` itself has no index page. **Do not use `migrate:fresh` on a database with data you wish to keep.**

## Quick start: macOS / Linux (MySQL)

Start MySQL and create the `quantix_requests` database as above, then:

```bash
composer install
cp .env.example .env
# Edit .env to use your MySQL username, password, hostname, and port.
php artisan key:generate
php artisan optimize:clear
php artisan migrate --seed
php artisan serve
```

Make sure `storage/` and `bootstrap/cache/` are writable by PHP.

## MySQL test database setup (required before running PHPUnit)

**Create a second, disposable database. Never run tests against `quantix_requests` or production data.**

```sql
CREATE DATABASE IF NOT EXISTS quantix_requests_test CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

`phpunit.xml` sets `DB_CONNECTION=mysql` and **forces** `DB_DATABASE=quantix_requests_test`. It inherits the MySQL host, port, username, and password from `.env` (or `.env.testing`, if you create one). Ensure that your MySQL user has permission to create, alter, and drop tables **inside the test database**. If your MySQL tests use different credentials, create a local `.env.testing` using your own credentials; do not commit secrets.

Check the CLI PHP extensions:

```powershell
php --ini
php -m
```

The list must include `pdo_mysql` and `PDO`. If missing, uncomment `extension=pdo_mysql` in the `php.ini` reported by `php --ini`, restart the terminal, and check again.

**Before tests**, clear cached Laravel configuration (`php artisan config:clear`), verify `phpunit.xml` still points at `quantix_requests_test`, and then run `php artisan test`. The test runner's `RefreshDatabase` trait will manage schema state in the dedicated test database. You do not need to run `migrate:fresh` yourself.

## Queue and mail

The default `.env.example` uses `QUEUE_CONNECTION=database` and `MAIL_MAILER=log`.
Run a queue worker **in a second terminal** while the server is running:

```bash
php artisan queue:work
```

Mail messages are written to `storage/logs/laravel.log` for local development; database notifications are also created. For a quick demo without a second terminal, set `QUEUE_CONNECTION=sync` in `.env` and run `php artisan optimize:clear` (notifications are handled during the request). Configure SMTP for real outgoing email.

## Seeded demo accounts

Run `php artisan migrate --seed` on a fresh database to create the following users (and 10 sample service requests with full seeded lifecycle history):

| Role | Email | Password |
|---|---|---|
| Manager | `manager@example.com` | `Password123!` |
| Employee | `alex@example.com` | `Password123!` |
| Employee | `sam@example.com` | `Password123!` |
| Operations | `operations@example.com` | `Password123!` |

In the seeded database, the manager is ID 1 and the operations user is ID 2. **These passwords are demo-only. Do not expose the seeded database publicly.**

## Test your API

### 1. Login as employee

Send a `POST` request to `http://127.0.0.1:8000/api/login` with:

```json
{
  "email": "alex@example.com",
  "password": "Password123!"
}
```

Include headers `Accept: application/json` and `Content-Type: application/json`. Copy `data.token` from the response and use it as a bearer token for all other endpoints.

### 2. Create a service request

Send `POST http://127.0.0.1:8000/api/requests` with `Authorization: Bearer YOUR_TOKEN` and JSON:

```json
{
  "title": "Laptop access issue",
  "description": "I cannot access my company laptop.",
  "category": "it_support",
  "priority": "high",
  "submit": true
}
```

You should receive **201 Created** and a request with `data.status` set to `pending`. Save `data.id`.

### 3. Finish the workflow

- Log in as `manager@example.com`, then send `POST /api/requests/{id}/decision` with `{ "decision": "approve", "comment": "Approved." }`.
- Log in as `operations@example.com`, then send `POST /api/requests/{id}/assign` with `{ "assigned_to": 2 }` (if using the supplied seed data).
- As operations, send `POST /api/requests/{id}/status` with `{ "status": "in_progress" }`, then `{ "status": "completed" }`.
- An illegal state change returns a `422` validation error. A forbidden action returns `403`.

**Postman:** Import `postman/Quantix-Service-Request-API.postman_collection.json`. It defines `base_url`, `token`, `request_id`, and `operations_user_id`. Each login saves the new bearer token; creating a request saves its ID. **Run requests individually in the order appropriate to the role**, rather than running the entire collection as a single sequence.

## Endpoints

All routes start with `/api`.

| Method | Route | Who can use it |
|---|---|---|
| POST | `/login` | Public |
| POST | `/logout` | Authenticated |
| GET | `/me` | Authenticated |
| GET | `/requests` | Employee (own), Manager (direct reports), Operations (all) |
| POST | `/requests` | Employee |
| GET | `/requests/{id}` | Permitted participants |
| GET | `/requests/{id}/activity` | Permitted participants (paginated audit history) |
| PUT | `/requests/{id}` | Request owner, while draft/returned |
| POST | `/requests/{id}/submit` | Owner, while draft/returned |
| POST | `/requests/{id}/decision` | Direct manager, while pending |
| POST | `/requests/{id}/assign` | Operations, while approved |
| POST | `/requests/{id}/status` | Operations, assigned → in_progress → completed |
| POST | `/requests/{id}/comments` | Permitted participants (except terminal requests) |

Valid manager decisions: `approve`, `reject`, `return` (the decision request requires a comment). A returned request can be edited and resubmitted. Rejected and completed requests are terminal.

Filters on `GET /requests`: `search`, `status`, `sort` (`created_at`, `updated_at`, `title`, `priority`), `direction` (`asc`, `desc`), `page` (positive integer), `per_page` (1–100).

## Tests

After creating the **separate MySQL** database `quantix_requests_test` and enabling `pdo_mysql`, run:

```powershell
php artisan config:clear
php artisan test
# or: composer test
```

The PHPUnit configuration uses **MySQL**, not SQLite. Tests cover authentication, visibility and roles, state transitions, audit history, validation, assignment constraints, and notification dispatch. A database error will prevent them from exercising the API logic; resolve database connectivity before interpreting remaining test failures.

Optional formatting:

```bash
vendor/bin/pint
```

## Troubleshooting

| Problem | Solution |
|---|---|
| `composer` is not recognized | Install Composer and reopen your terminal. Make sure PHP and Composer are in `PATH`. |
| `php` is not recognized | Install PHP 8.3+ (for the locked dependencies) or configure the PHP path (e.g. via XAMPP / Laragon), then reopen your terminal. |
| `could not find driver` | Enable `extension=pdo_mysql` in the **CLI** `php.ini`; run `php --ini` to locate it and verify using `php -m`. |
| `Access denied for user` | Check the MySQL username/password in `.env`, and grant your user access to both the application and test databases. |
| `Unknown database` | Create `quantix_requests` for the app and `quantix_requests_test` for tests (see setup above). |
| `vendor/autoload.php` missing | Run `composer install` from the project folder; do not skip this step. |
| `No application encryption key` | Run `php artisan key:generate`. |
| `no such table` | Confirm `DB_DATABASE` exists in MySQL and run `php artisan migrate --seed` for the application. PHPUnit manages schema in the separate test database. |
| 401 Unauthorized | Log in again and supply `Authorization: Bearer TOKEN`. |
| 403 Forbidden | Use an account with the required role and, for manager decisions, the employee's actual manager. |
| 422 Unprocessable Entity | Check the JSON field values and the request's current status. |
| Notifications not appearing | Run `php artisan queue:work`, or switch to `QUEUE_CONNECTION=sync` for local development. |
| 404 at `/` or `/api` | The project is an API: check `/up` or one of the defined API routes. |

## Implementation notes

- `app/Services/RequestWorkflow.php` defines the allowed workflow transitions and coordinates audited, authorized mutations in a database transaction via repository interfaces.
- Route model binding and policies in `app/Policies/ServiceRequestPolicy.php` enforce participant access.
- `app/Http/Requests` performs input validation and initial role/resource authorization. `app/Repositories/Interfaces` and `app/Repositories/Eloquent` separate persistence from business rules.
- `app/Notifications/RequestChangedNotification.php` queues mail and database notifications after a state change.
- `docs/ARCHITECTURE.md` and `docs/REPOSITORY_PATTERN_GUIDE.md` explain design choices, interface bindings, testing, and future improvements.

The archive includes PHP source, configuration, tests, and Postman requests, but **not the third-party `vendor/` dependencies**. Install them locally with Composer as above.


## Enhanced audit trail and terminal request rules

Every new request creates a `created` activity. Edits to draft/returned requests generate `updated` events with field-level `from`/`to` values. New comments create `commented` activity; approval/return/rejection and operations transitions log their action, previous and next statuses, timestamp, actor, and optional comment. A `GET /api/requests/{id}/activity` call returns paginated activity (20 events per page) to participants authorized to view the request. The existing request detail response includes comments and activities as well.

Rejected and completed requests are **read-only**, including comments. Only direct-report managers can take decisions; only operations users may assign, start, or complete; submission requires a valid reporting manager. Any operations user may work on approved/assigned requests (a deliberate single-team demo assumption, not a claim that only the assignee can update work).

## Additional verification (MySQL required)

In an isolated demo database, run the exact assignment single-command seed:

```bash
php artisan migrate:fresh --seed
```

**Warning:** This destroys ALL tables and data in the configured **application** database; do not use it in a populated or production database. `migrate --seed` is safer for an existing installation.

With the **separate** MySQL `quantix_requests_test` database created, run:

```bash
php artisan test
php tools/static-check.php
vendor/bin/pint --test
```

Pint formatting (changes files): `vendor/bin/pint`. For richer optional static analysis using PHPStan/Larastan, read `docs/STATIC_ANALYSIS.md`. Full MySQL/PHPUnit tests and Pint were **not runnable in the archive-review environment** because Composer and required MySQL/PHP extensions were unavailable; run all three commands locally before submitting.

## Deliverables and submission

- `docs/ARCHITECTURE.md` — architecture and trade-offs.
- `docs/REQUIREMENTS_CHECKLIST.md` — mapping from each assignment requirement to code and test coverage.
- `docs/REFERENCES_AND_AI.md` — references, packages, and honest AI disclosure.
- `docs/STATIC_ANALYSIS.md` — static quality tool instructions.
- `docs/POSTMAN_STEP_BY_STEP.md` — beginner-friendly Postman workflow.
- `postman/Quantix-Service-Request-API.postman_collection.json` — import in Postman and run requests with tokens for each role.
- `.github/workflows/tests.yml` — GitHub Actions for MySQL migrations, tests, and a static project checker.

The assignment requests a **GitHub/GitLab repository with meaningful commits** and a shared ZIP link excluding `vendor` and `node_modules`. Create these in your own account and make sure the reviewer can access both links. Do not commit `.env`, credentials, `vendor`, or `node_modules`. The Git repository and live test results cannot be created/verified from the static review alone.


### Shared Eloquent base repository

The refactored project includes `app/Repositories/Base/EloquentRepositoryInterface.php` and `app/Repositories/Base/BaseRepository.php`, modeled on the layering of the supplied reference project. Every specific repository interface extends the shared interface, and every Eloquent repository extends the base class. Common `findById` and `existsById` read methods are reusable; advanced request queries stay in the service-request repository. The base class intentionally **does not** expose public generic update/delete methods: all changes must go through services, policies, row locks and the `RequestWorkflow` transition map. See `docs/REPOSITORY_PATTERN_GUIDE.md`.
