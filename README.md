# Laravel Modulith skeleton

A Laravel application set up with
[laravel-modulith](https://github.com/mk-josias/laravel-modulith). It comes with two example
modules, `iam` and `analytics`, each with its own database. You can read them to see how a module
is written, then replace them with your own.

```bash
composer create-project mk-josias/laravel-modulith-skeleton my-app
```

Requires PHP 8.4+, and Redis for the event stream.

## Running it

```bash
cp .env.example .env
php artisan key:generate
php artisan migrate           # migrates the application's database, then each module's
php artisan serve
```

The modules use sqlite files by default (`database/iam.sqlite`, `database/analytics.sqlite`).
`migrate` creates them.

Register a user in `iam`:

```bash
curl -X POST http://127.0.0.1:8000/iam/api/v1/users \
    -H 'Accept: application/json' -H 'Content-Type: application/json' \
    -d '{"name":"Ada","email":"ada@example.com"}'
```

```json
{"success":true,"data":{"id":1,"name":"Ada","token":"MYUvvHJIXDKy0RAeZK2I6B4MSYbnKLMSHxKQAWdK"}}
```

`iam` announces the registration with an event. Start the `analytics` consumer to handle it:

```bash
php artisan modulith:events:consume --module=analytics
```

`analytics` now has a signup and its own copy of the user. Read them with the token `iam` returned:

```bash
curl http://127.0.0.1:8000/analytics/api/v1/signups \
    -H 'Accept: application/json' -H 'Authorization: Bearer <token>'
```

## What is in the skeleton

```
app/                  your Laravel application
config/modulith.php   declares the modules, and where they run when they run elsewhere
apps/
├── Iam/              owns the users
└── Analytics/        records signups and keeps a copy of the users
foundation/
├── FoundationServiceProvider.php
└── Iam/              what iam shares with the other modules
shared/               base classes for your modules (see below)
```

### The `iam` module

| File | What it does |
|---|---|
| `app/Models/User.php` | The user, stored in the `iam_users` table. The `name` field is copied to the modules that keep a copy. |
| `app/Actions/RegisterUser.php` | Creates the user and emits `iam.user.registered` in the same transaction. |
| `app/Http/Controllers/UserController.php` | `POST /iam/api/v1/users` and `GET /iam/api/v1/me`. |
| `app/Services/IamService.php` | Answers the `IamService` contract with iam's own data. |
| `routes/rpc.php` | Answers the same contract for the modules that run in another process. |
| `config/database.php` | The `iam` and `iam_owner` connections. |

### The foundation

`foundation/Iam/` holds what the other modules are allowed to use from `iam`:

| File | What it does |
|---|---|
| `Contracts/IamService.php` | The contract: `findUser()` and `findUserByToken()`. |
| `Services/IamRpcService.php` | Calls `iam` over HTTP. Used when `iam` runs in another process. |
| `Auth/Tokens.php`, `Auth/AuthenticatedUser.php` | Turn a bearer token into the authenticated user, through the contract. |
| `Shadows/UserShadow.php` | The shape of a copy of iam's users. |
| `database/shadows/` | The migration that creates the copy's table in the module that keeps it. |

`foundation/FoundationServiceProvider.php` maps `IamService` to `IamRpcService`. When `iam` runs
in the same process, `IamServiceProvider` binds its own `IamService` instead.

### The `analytics` module

| File | What it does |
|---|---|
| `app/Handlers/RecordSignup.php` | Handles `iam.user.registered` and stores a signup. |
| `app/Models/UserShadow.php` | The copy of iam's users, in the `analytics_iam_users` table. |
| `app/Http/Controllers/SignupController.php` | `GET /analytics/api/v1/signups` lists the signups. `GET /analytics/api/v1/users/{id}` asks `iam` through the contract. |
| `app/Http/Resources/SignupResource.php` | Adds the user to each signup with `UserResource`, read from the copy: no call to `iam` per row. |
| `app/Rules/ExistingUser.php` | A validation rule that asks `iam` through the contract. `?user_id=` on the signups list uses it. |
| `config/modulith.php` | Declares the handler. |

A module reads another module's data in two ways, and the skeleton shows both:

| | Through the contract (`ExistingUser`) | From the copy (`SignupResource`) |
|---|---|---|
| Cost | one call per value, an HTTP call when `iam` runs elsewhere | a local query, joins included |
| Freshness | always current | current once the consumer has handled the events |
| Use it for | checking one value, reading one record | lists, filters, sorts |

### The `shared/` directory

These classes are yours. The package doesn't depend on them, so you can change or delete them.

| Path | What it does |
|---|---|
| `shared/Http/Controller`, `ApiRequest`, `Resource`, `ApiErrorCode` | Base controller and request, and the response format: `{success, data}` or `{success, code, message}`. |
| `shared/Auth/Authenticate`, `Principal`, `TokenValidator`, `HasPrincipal` | Token authentication for module routes. |
| `shared/Contracts/Action`, `Operation` | One class per use case. |
| `shared/Contracts/Repository`, `shared/Database/EloquentRepository`, `Searchable` | Queries with filters, sorts and includes, and a single place for writes. |
| `shared/Data/Dto` | Typed input, built on spatie/laravel-data. |
| `shared/Exceptions/DomainException`, `IntegrityException` | Business errors (400) and system errors (500). |

`bootstrap/app.php` renders the exceptions that implement `RendersApiEnvelope` in the response
format above.

## Authentication

A route is protected with the `Shared\Auth\Authenticate` middleware:

```php
Route::get('me', [UserController::class, 'me'])->middleware(Authenticate::class);
```

The middleware reads the bearer token and asks the bound `TokenValidator` for the user. In this
skeleton the validator is `Foundation\Iam\Auth\Tokens`, which calls the `IamService` contract, so
it works the same whether `iam` runs in this process or in another one. Without a valid token the
middleware throws Laravel's `AuthenticationException`, rendered as a 401.

In a controller that extends `Shared\Http\Controller`, `$this->principalId()` returns the id of
the authenticated user.

## Running a module in its own process

Every module runs in one process by default. To move `iam` to its own process, start two copies
of the same code with different settings:

```dotenv
# the iam process
MODULITH_RUNS=iam

# the analytics process
MODULITH_RUNS=analytics
MODULITH_IAM_HOST=http://iam.internal:8000
```

`config/modulith.php` declares both modules in both processes, so `analytics` still listens to
iam's events and calls iam over HTTP through `IamRpcService`. Both processes must share `APP_KEY`,
or the same `MODULITH_RPC_SECRET`.

When you build an image for one module, delete the folders of the others before
`composer dump-autoload`:

```bash
MODULITH_RUNS=analytics php artisan modulith:purge --force
```

Starting that image with `MODULITH_RUNS=iam` then fails at boot, because iam's folder is gone.

## Tests

```bash
php artisan test
```

`tests/TestCase.php` gives each module an empty sqlite file and runs `migrate` before every
test. `tests/Feature/ModulesTest.php` covers the flow above, and checks that no module uses
another module's classes (`Modulith\Testing\Boundaries`) and that `modulith:doctor` passes.

To write to a module's database in a test, use `inModule`:

```php
$user = $this->inModule('iam', fn () => User::query()->create([...]));
```

## License

MIT.
