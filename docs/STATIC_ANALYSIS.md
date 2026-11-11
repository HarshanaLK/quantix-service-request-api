# Code quality and static analysis

## Checks supplied in this repository

- `php tools/static-check.php` verifies PHP syntax (for every project PHP source file), workflow enum map integrity, basic authentication-route markers, JSON integrity, isolated MySQL test settings and required documentation. It requires only PHP and can run **before** Composer installation.
- `vendor/bin/pint --test` checks Laravel code style once Composer dependencies are installed. `vendor/bin/pint` formats the project.
- The `phpstan.neon.dist` file is a starting configuration for stronger analysis with **Larastan / PHPStan**. Because Larastan is not included in the dependency lock file, install it locally explicitly using the command below and commit the resulting `composer.json` and `composer.lock` if you want it as a permanent project dependency:

```bash
composer require --dev larastan/larastan
vendor/bin/phpstan analyse -c phpstan.neon.dist
```

A syntax/project-invariant checker is **not** a replacement for type inference or full PHPStan analysis. Review any analysis findings and keep your dependency lock in sync before submitting.
