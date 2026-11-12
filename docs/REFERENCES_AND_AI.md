# References, packages and AI disclosure

## Framework and packages

- Laravel framework (application, migrations, Eloquent, policies, validation, queues, API Resources, testing): https://laravel.com/docs
- Laravel Sanctum (personal access token authentication): https://laravel.com/docs/sanctum
- Laravel Pint (formatting): https://laravel.com/docs/pint
- PHPUnit (automated tests): https://phpunit.de/
- MySQL (primary and test databases): https://dev.mysql.com/doc/
- PHPStan / Larastan (optional richer static analysis): https://phpstan.org/ and https://github.com/larastan/larastan
- Postman (manual API exercises): https://learning.postman.com/

Actual installed package versions are controlled by `composer.lock`; inspect with `composer show`.

## AI assistance disclosure

**ChatGPT (OpenAI)** was used to review the initial code against the take-home requirements, suggest and draft improvements to audit logging, authorization, tests, and documentation, refactor the API into interface/repository + service layers based on the uploaded travel-site reference structure (without its Inertia code), and produce the project archive. The developer submitting this work must review, understand, and locally verify all code, tests, trade-offs, and documentation, and disclose any other AI usage accurately. The provided project has **not** been verified with a running MySQL/PHPUnit test environment in the review sandbox.

Do not claim an automated suite passes until you have run `php artisan test` on your MySQL test database.

- The shared `BaseRepository` and `EloquentRepositoryInterface` design follows the layering pattern in the user-provided travel-site archive; implementations were rewritten for Quantix, without its frontend code.
