# Architecture: interface/repository-based Laravel REST API

**API only:** Laravel 11/12 compatible, PHP 8.3+ for the pinned dependencies, MySQL, Sanctum, PHPUnit. There is **no Inertia.js, Blade client, React, Vue, or web UI**. Postman exercises the JSON endpoints. The layered separation was inspired by the uploaded `travel-site-main` example, but none of its code or frontend dependencies are copied.

```text
HTTP request -> routes/api.php
   -> FormRequest (input validation and initial permission check)
   -> Controller (HTTP adapter / API Resource output)
   -> Service (use case / transaction / workflow rules)
   -> Repository interface (data-access contract)
   -> Eloquent repository -> Model -> MySQL
```

## Why these layers?

- **Controllers** stay thin and do not issue Eloquent queries directly; they delegate to `AuthService`, `ServiceRequestService`, `RequestCommentService`, and `RequestWorkflow`.
- **Form Requests** validate inputs (including list filters); **Policies** authorize actions. Policies are rechecked *after acquiring the request row lock* for mutations, avoiding a stale pre-validation permission check.
- **Services** own business decisions: creation, updating fields, recording activity, comments, and transitions. `RequestWorkflow::ALLOWED` is the only status-transition map (`draft -> pending -> approved -> assigned -> in_progress -> completed`, with `pending -> returned/rejected`, and returned resubmission). Rejected/completed are terminal.
- **Repository interfaces** (`app/Repositories/Interfaces`) define operations used by the services. **Eloquent repositories** (`app/Repositories/Eloquent`) extend the shared `BaseRepository` and implement database access: role-scoped searches, locked reads, create/save, pagination, comments, activity, user lookup. `RepositoryServiceProvider`, registered in `bootstrap/providers.php`, binds each interface to its Eloquent implementation. Services receive **interfaces**, not concrete repositories.
- **Resources** shape JSON responses. Pagination, eager loading, and safe role scope are implemented at query time. Sanctum provides bearer tokens. A queued `RequestChangedNotification` targets the requester, manager, and assignee after workflow transitions.

## ACID / audit / security

All edits, comments, and state transitions lock the service request using `SELECT ... FOR UPDATE` within a MySQL transaction, re-authorize with a policy, and record an activity event with the actor, time, old/new status, optional comment or changed fields. Operations users alone assign or update work; managers act only on direct reports. Terminal requests cannot be changed or commented on. The list query is scoped before filtering/pagination so hidden records do not appear in totals.

## Trade-offs

The repository pattern introduces four interfaces, four implementations and a provider, which is extra code for a take-home assignment. It is useful here because tests can mock contracts, business logic is kept out of Eloquent query objects, and database access has one place to evolve. There is now a small shared `BaseRepository` and `EloquentRepositoryInterface` in `app/Repositories/Base/`. They provide common read/query helpers without exposing unrestricted generic update/delete operations. Model-specific methods (`paginateVisibleTo`, `findForUpdateOrFail`, `isOperationsUser`) remain explicit and policy-enforced in services. Eloquent models remain the domain data structures rather than using a full DTO/domain-entity mapping layer.

This is a single-tenant demonstration, not a complete multi-tenant SaaS. It assumes a single role per user; any operations user can work on approved requests; a valid manager is required to submit. Notifications are queued **after the workflow database transaction finishes**, but a transactional outbox would be needed to guarantee delivery if the queue is down. `create` with `submit=true` uses pre-validation followed by separate audited transactions; an outbox and one atomic transaction would be possible in a larger production iteration. Future improvements include multi-tenancy, SLAs, attachments, retry-aware notification delivery, assignee-only operation options, and detailed observability.

## Important folders

- `app/Repositories/Base/`: shared `BaseRepository` + `EloquentRepositoryInterface`
- `app/Repositories/Interfaces/`: 4 model-specific data-access contracts extending the base interface
- `app/Repositories/Eloquent/`: 4 MySQL/Eloquent implementations
- `app/Providers/RepositoryServiceProvider.php`: interface bindings
- `app/Services/`: domain/use-case and workflow services
- `app/Policies/ServiceRequestPolicy.php`: role and participant permissions
- `app/Http/Requests/`: validated API inputs
- `app/Http/Resources/`: stable API JSON shape
- `tests/Feature/`: end-to-end HTTP behavior (requires test MySQL database)
- `tests/Unit/`: workflow-map checks, DI wiring, mocked audit logger
