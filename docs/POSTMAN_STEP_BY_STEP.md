# Beginner's Postman checklist (MySQL)

This is a **REST API**, so you do not need a web frontend. Run `composer install`, copy `.env.example` to `.env`, add your local MySQL credentials, run `php artisan key:generate`, and run `php artisan migrate:fresh --seed` **only on your empty demo database**. Then start the server with `php artisan serve`.

1. Open Postman and choose **Import**. Select `postman/Quantix-Service-Request-API.postman_collection.json`.
2. Click the imported collection and inspect its variables: `base_url` should be `http://127.0.0.1:8000/api`; `operations_user_id` should be `2` for the supplied fresh seed.
3. Run **Login as employee** (`POST /api/login`). It uses `alex@example.com` and `Password123!` on the seeded database. Confirm HTTP `200` and that the response contains `data.token`. The collection script stores that token in the `token` collection variable.
4. Run **Create and submit request** (`POST /api/requests`). Confirm HTTP `201`, `data.status: pending`, and `data.id`. The script saves the new request ID into `request_id`.
5. Run **Request details** (`GET /api/requests/{{request_id}}`) and **Paginated activity history**. The status should be `pending`; activity should include `created` and `submit`.
6. Run **Login as manager**, then **Manager approve**. Confirm HTTP `200` and the new `approved` status. The collection now uses the manager's token.
7. Run **Login as operations**, then **Operations assign**; confirm status `assigned`. Run **Operations start** (status becomes `in_progress`), then **Operations complete** (status becomes `completed`).
8. Run **Request details** and **Paginated activity history** again. Look for all transition events and the actor names. The request should be completed and immutable.
9. To verify error handling, try **Operations start** on the completed request again; it should return `422`. Log in as an employee and try **Operations assign**; it should return `403`.
10. To test returns, create a **new** request as an employee, log in as manager, use `POST /api/requests/{{request_id}}/decision` with `{"decision":"return","comment":"Please add more detail."}`. Then log in as that employee, edit it with `PUT /api/requests/{{request_id}}` and submit again.

**Common problems:** If you get `401`, log in again, check the token variable, and ensure the request includes `Authorization: Bearer {{token}}`. If you get `404`, check your new `request_id`. If you get a database driver error, check the PHP CLI extensions and enable `pdo_mysql` (not `pdo_sqlite`). If the queue is set to `database`, start `php artisan queue:work` in another terminal to process notifications. Do not run all Postman collection items in sequence automatically: login role and request status matter.
