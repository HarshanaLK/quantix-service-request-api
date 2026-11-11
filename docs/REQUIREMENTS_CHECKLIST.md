# Requirements coverage (Quantix internship assignment, pages 3-7)

This checklist maps the supplied take-home specification to code. **Implemented** means source code is present; **not runtime-verified** means migrations and PHPUnit could not be run in the current review environment because Composer, MySQL, and required PHP extensions are absent. Verify these items on your own machine before submission.

| Requirement | Implementation / evidence | Status |
|---|---|---|
| Laravel 11+, PHP 8.2+, MySQL | Laravel 12 in `composer.lock`; `.env.example`, `config/database.php`; PHP 8.3+ recommended for locked tooling | Implemented, not runtime-verified |
| Employee / Manager / Operations | `app/Enums/UserRole.php`, users table, policy and Form Requests | Implemented |
| Sanctum token login | `AuthController`, `auth:sanctum` in `routes/api.php`, token migration | Implemented |
| Draft / returned editing only | `ServiceRequestPolicy::update`, locked update controller | Implemented |
| Direct-report manager decisions only | `ServiceRequestPolicy::decide`, `RequestWorkflow` lock-time Gate check | Implemented |
| Operations-only assignment / status | `AssignRequest`, `StatusRequest`, workflow Gate check | Implemented |
| Legal transitions return 422 | `app/Services/RequestWorkflow.php` central map | Implemented |
| Completed / rejected immutable | workflow map and policy denial of edits/comments | Implemented |
| Full change activity | `RequestActivityLogger` stores creation, edits, comment, and status actions with actor/time/diff | Implemented |
| Queued notifications | `RequestChangedNotification` implements `ShouldQueue`; database queue + worker | Implemented, dispatch test supplied |
| JSON, 401 / 403 / 404 / 422 | API exception handlers in `bootstrap/app.php`, Form Requests and policies | Implemented |
| All minimum endpoints | `routes/api.php`, Postman collection; extra `/activity` endpoint | Implemented |
| Search, sort, status filter, pagination | `ServiceRequestController::index` | Implemented |
| Detail with comments and activity | `ServiceRequestResource`, detail API | Implemented |
| Migrations / indexes / relations / factories | `database/`, `app/Models/` | Implemented |
| Seed roles + 2 reports + 10 status-diverse requests | `database/seeders/DatabaseSeeder.php` with demo activity history | Implemented |
| Feature and unit tests | `tests/Feature/`, `tests/Unit/` | Supplied, **not executed** in this sandbox |
| Queue dispatch test | `AssignmentCoverageTest::test_status_change_pushes_a_queue_job` | Supplied, **not executed** in this sandbox |
| Pint and static quality checks | `composer.json` Pint dependency, `tools/static-check.php`, `docs/STATIC_ANALYSIS.md`; optional Larastan config | Project checker implemented; full Larastan not installed |
| README setup and sample logins | `README.md` | Supplied |
| Postman documentation | `postman/Quantix-Service-Request-API.postman_collection.json` | Supplied |
| Architecture note | `docs/ARCHITECTURE.md` | Supplied |
| References / packages / AI note | `docs/REFERENCES_AND_AI.md` | Supplied |
| Git repo, meaningful commit history, submission links | Must be created and uploaded by the submitting developer | **Still needed** |
| Live MySQL migrations, API tests, code formatting | Must be run locally and any failures fixed | **Still needed** |

Optional stretch goals (file upload, SLA, Docker) are not necessary for the minimum assignment. Basic login rate limiting is already enabled.
