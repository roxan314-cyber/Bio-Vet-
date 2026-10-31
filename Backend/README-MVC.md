# PHP web backend MVC

The active PHP web application uses `Proyecto/router.php` as its only public front controller. It reads the route and asset definitions from `Backend/routes/web-routes.json`; the Express server reads that same JSON file for the matching page, asset, redirect, and proxy mappings. Method policies stay surface-specific in the registry.

## Runtime boundaries

| Layer | Location | Responsibility |
|---|---|---|
| HTTP/front controller | `router.php`, `Backend/app/Http/` | Route dispatch, request parsing, response/status/header serialization |
| Controllers | `Backend/app/Http/Controllers/` | HTTP contract and orchestration of a use case |
| Services | `Backend/app/Services/` | Auth, registration, profile, token-verification, and readiness workflows |
| Model | `Backend/app/Models/User.php` | Stable representation of existing `users` fields |
| Repositories | `Backend/app/Repositories/` | Prepared PDO statements, row mapping, database readiness |
| Security | `Backend/app/Security/` | Existing HS256 helper wrapped as an injected token service |
| Composition root | `Backend/bootstrap.php`, `Backend/app/Application.php` | Lightweight namespace autoloading and dependency wiring; no Composer/framework requirement |
| Public route/view data | `Backend/routes/web-routes.json`, existing `Frontend/` files | Shared public route and asset declarations; HTML stays static |

The repository uses the existing `getDBConnection()` configuration and the existing `users` schema. The database connection is lazy, so isolated tests and static page serving do not require MySQL. The old endpoint script paths are retained as thin compatibility adapters and are not direct public entry points; Apache continues routing public traffic through `router.php`.

## Tests and lint

Run the database-free service/controller tests:

```powershell
php .\Backend\tests\run.php
```

Lint the PHP front controller, bootstrap, adapters, application classes, and tests:

```powershell
Get-ChildItem .\Backend\app -Filter *.php -Recurse | ForEach-Object { php -l $_.FullName }
php -l .\router.php
php -l .\Backend\bootstrap.php
php -l '.\Backend\Apis\Frontend\Endpoint de Login.php'
php -l '.\Backend\controllers\Api Register.php'
php -l '.\Backend\Apis\Frontend\api verify token.php'
php -l '.\Backend\Apis\Frontend\api user profile.php'
php -l .\Backend\tests\run.php
```

The isolated test suite uses fake repositories and token services; it does not connect to, read from, or modify a live database. End-to-end authentication tests remain the responsibility of the configured test database environment.
