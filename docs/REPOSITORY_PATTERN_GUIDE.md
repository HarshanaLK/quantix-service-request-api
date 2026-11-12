# Repository Pattern Guide (Quantix API)

The updated Laravel code uses the reference project's main idea: **interface + Eloquent implementation**, with a distinct service layer. It does not use Inertia.js or any frontend.

## Folder structure

```text
app/
  Enums/                              RequestStatus.php, UserRole.php
  Http/
    Controllers/Api/                  thin HTTP endpoints
    Requests/                         Form Request validation and authorization
    Resources/                        response JSON formatting
  Models/                             User, ServiceRequest, RequestComment, RequestActivity
  Notifications/                      RequestChangedNotification
  Policies/                           ServiceRequestPolicy
  Providers/                          RepositoryServiceProvider
  Repositories/
    Base/                             BaseRepository.php, EloquentRepositoryInterface.php
    Interfaces/                       4 repository interfaces
      ServiceRequestRepositoryInterface.php
      UserRepositoryInterface.php
      RequestActivityRepositoryInterface.php
      RequestCommentRepositoryInterface.php
    Eloquent/                         4 concrete implementations extending BaseRepository
      ServiceRequestRepository.php
      UserRepository.php
      RequestActivityRepository.php
      RequestCommentRepository.php
  Services/
    AuthService.php
    ServiceRequestService.php
    RequestCommentService.php
    RequestActivityLogger.php
    RequestWorkflow.php
```

## How the application flows

For `POST /api/requests`: `StoreServiceRequest` validates inputs and authorizes the employee -> `ServiceRequestController::store()` delegates -> `ServiceRequestService::create()` uses `ServiceRequestRepositoryInterface` to persist a draft, `RequestActivityLogger` to audit, then optionally `RequestWorkflow` to submit -> returned model is formatted by `ServiceRequestResource`.

For `POST /api/requests/{id}/decision`: `DecisionRequest` validates the manager's decision -> `RequestWorkflowController` delegates -> `RequestWorkflow::transition()` locks the record via `ServiceRequestRepositoryInterface`, checks the direct-manager policy, uses its central transition table, saves, writes audit through `RequestActivityRepositoryInterface`, writes optional comment via `RequestCommentRepositoryInterface`, then queues notifications.

For `GET /api/requests`: `IndexServiceRequestsRequest` validates search/filter/sort -> `ServiceRequestService::listFor()` -> `ServiceRequestRepository::paginateVisibleTo()` filters **in the SQL query** according to employee/manager/operations scope, with eager loads and pagination.

For `POST /api/login`: `AuthService` retrieves a user using `UserRepositoryInterface`, validates the password, and issues a Sanctum token.

## Adding new data access without editing controllers

1. Add a purpose-specific method to the appropriate `Repositories/Interfaces/*Interface.php`.
2. Implement it inside the matching `Repositories/Eloquent/*.php` class.
3. Use the interface method from a **service**, not directly from the controller.
4. Add tests for access rules, validation and workflow behavior.
5. If adding a new interface, bind it in `RepositoryServiceProvider` and register that provider in `bootstrap/providers.php` (already registered in the template).

## MySQL and testing

`composer install`, `cp .env.example .env` (Windows PowerShell: `Copy-Item .env.example .env`), edit DB credentials, `php artisan key:generate`, `php artisan migrate:fresh --seed`, `php artisan serve`.

For automated tests, **separately create** `quantix_requests_test`. Then run `php artisan test`. The sample test suite also checks DI binding and mocks the activity repository contract. The environment in which this ZIP was authored has PHP but no Composer/vendor packages or MySQL service, so the Laravel feature tests cannot be certified as passing here.

## Shared base layer (matching the reference architecture)

- `Base/EloquentRepositoryInterface.php` declares `findById()` and `existsById()`.
- `Base/BaseRepository.php` implements common Eloquent read/query helpers and protected persistence helpers.
- Each model-specific repository interface **extends** `EloquentRepositoryInterface`.
- Each concrete Eloquent repository **extends** `BaseRepository` and **implements** its model-specific interface.
- All repository bindings remain in `RepositoryServiceProvider.php`.

Unlike the uploaded travel-site reference, this API does not add public `deleteById()`, `update()`, `allTrashed()` or broad CRUD methods to the base interface. That would invite bypassing the Quantix authorization workflow, especially for rejected/completed requests. Use the service layer and policy checks for every mutation. A model-specific repository may still expose a query helper, but it must not be called from a public controller without an authorization check.
