# Laravel Modulith skeleton

A Laravel application set up with
[laravel-modulith](https://github.com/mk-josias/laravel-modulith). It comes with three example
modules, `iam`, `analytics` and `notifications`, each with its own database. You can read them to see how a module
is written, then replace them with your own.

```bash
composer create-project mk-josias/laravel-modulith-skeleton my-app
```

Requires PHP 8.4+, and Redis for the event stream.

## Running it

```bash
cp .env.example .env
php artisan key:generate
php artisan auth:jwt-keys     # the RSA pair iam signs tokens with, in storage/jwt-*.key
php artisan migrate           # migrates the application's database, then each module's
php artisan iam:sync-permissions --prune   # the permissions listed in auth.permissions, see Permissions
php artisan serve
```

`composer create-project` runs every step but `serve` itself. The modules use sqlite files by
default (`database/iam.sqlite`, `database/analytics.sqlite`, `database/notifications.sqlite`).
`migrate` creates them.

Register a user in `iam`:

```bash
curl -X POST http://127.0.0.1:8000/iam/api/v1/users \
    -H 'Accept: application/json' -H 'Content-Type: application/json' \
    -d '{"name":"<name>","email":"<email>","password":"<password, 8 characters or more>"}'
```

```json
{"success":true,"data":{"id":1,"name":"<name>","token":"<token>"}}
```

The token lasts an hour (`AUTH_JWT_TTL`). Ask for a new one with the same email and password:

```bash
curl -X POST http://127.0.0.1:8000/iam/api/v1/tokens \
    -H 'Accept: application/json' -H 'Content-Type: application/json' \
    -d '{"email":"<email>","password":"<password>"}'
```

Both routes accept 6 requests a minute. A wrong password and an unknown email get the same 401.

`iam` announces the registration with an event. Start a consumer per module to handle it, each
in its own terminal:

```bash
php artisan modulith:events:consume --module=analytics
php artisan modulith:events:consume --module=notifications
```

`analytics` now has a signup and `notifications` a welcome message, and each keeps its own copy of
the user. Read them with the token `iam` returned:

```bash
curl http://127.0.0.1:8000/analytics/api/v1/signups \
    -H 'Accept: application/json' -H 'Authorization: Bearer <token>'
curl http://127.0.0.1:8000/notifications/api/v1/notifications \
    -H 'Accept: application/json' -H 'Authorization: Bearer <token>'
```

`php artisan analytics:compute` builds the hourly datasets (the scheduler runs it every hour), and
`GET /analytics/api/v1/datasets?group=day` reads them.

## What is in the skeleton

```
app/                  your Laravel application
config/modulith.php   declares the modules, and where they run when they run elsewhere
apps/
├── Iam/              owns the users
├── Analytics/        records signups, computes datasets, keeps a copy of the users
└── Notifications/    a personal inbox, fed by events, keeps a copy of the users
foundation/
├── FoundationServiceProvider.php
├── Common/           base classes every module uses (see below)
└── Iam/              what iam shares with the other modules
```

### The `iam` module

| File | What it does |
|---|---|
| `app/Models/User.php` | The user, stored in the `iam_users` table. The `name` field is copied to the modules that keep a copy. |
| `app/Actions/RegisterUser.php` | Creates the user and emits `iam.user.registered` in the same transaction. |
| `app/Actions/IssueToken.php` | Issues the user's token: a JWT under the `jwt` strategy, an opaque token otherwise. |
| `app/Http/Controllers/UserController.php` | `POST /iam/api/v1/users` and `GET /iam/api/v1/me`. |
| `app/Http/Controllers/TokenController.php` | `POST /iam/api/v1/tokens` checks the email and password and issues a new token. |
| `app/Services/IamService.php` | Answers the `IamService` contract with iam's own data. Declared in `IamServiceProvider::$services`, it serves every caller, in this process or another. |
| `config/database.php` | The `iam` and `iam_owner` connections. |
| `config/auth.php` | Names `User` as the authenticated user of iam's routes. |

### The foundation

`foundation/Iam/` holds what the other modules are allowed to use from `iam`:

| File | What it does |
|---|---|
| `Contracts/IamService.php` | The contract: `findUser()` and `findUserByToken()`. |
| `Services/IamRpcService.php` | Every module calls `iam` through it: in this process when `iam` runs here, over HTTP otherwise. |
| `Auth/JwtTokens.php`, `Auth/RpcTokens.php`, `Auth/GatewayTokens.php` | The three ways to turn a token into a user id (see [Authentication](#authentication)). |
| `Events/IamEvent.php`, `Events/UserRegisteredPayload.php` | The names of the events iam publishes, and the typed payload of each: iam builds it, a consumer reads it with `UserRegisteredPayload::from($payload)`. The payload class also declares its versions (`version()`, `upcast()`), so a handler only ever receives the current shape. |
| `Shadows/UserShadow.php` | The shape of a copy of iam's users, and the authenticated user of the module that keeps it. |
| `database/shadows/` | The migration that creates the copy's table in the module that keeps it. |

`foundation/FoundationServiceProvider.php` maps `IamService` to `IamRpcService`. The package then
runs iam's implementation in iam's context, so `Apps\Iam\Services\IamService` queries iam's
database whichever module calls it.

### The `analytics` module

| File | What it does |
|---|---|
| `app/Handlers/RecordSignup.php` | Handles `iam.user.registered` and stores a signup. |
| `app/Models/UserShadow.php` | The copy of iam's users, in the `analytics_iam_users` table. |
| `app/Http/Controllers/SignupController.php` | `GET /analytics/api/v1/signups` lists the signups. `GET /analytics/api/v1/users/{id}` asks `iam` through the contract. |
| `app/Http/Resources/SignupResource.php` | Adds the user to each signup with `UserResource`, read from the copy: no call to `iam` per row. |
| `app/Rules/ExistingUser.php` | A `ReferenceRule` that asks `iam` through the contract. `?user_id=` on the signups list uses it. |
| `app/Console/ComputeDatasets.php` | `analytics:compute` rebuilds `analytics_datasets`, one row per hour, from the signups. `AnalyticsServiceProvider` schedules it hourly. |
| `app/Enums/DatasetMeasure.php`, `MeasureNature.php` | The measures, and how a period folds them: a flow (`signups_count`) adds up, a state (`users_total`) keeps the last value. |
| `app/Enums/DatasetGroup.php`, `app/Queries/ReadDatasets.php` | `GET /analytics/api/v1/datasets?group=hour\|day\|week\|month\|none&measures[]=…&from=…&to=…` folds the hourly rows onto the group. |
| `config/modulith.php` | Declares the handler. |

A reading never walks the signups: it filters and folds the pre-computed rows, so its cost
depends on the period, not on the volume.

A module reads another module's data in two ways, and the skeleton shows both:

| | Through the contract (`ExistingUser`) | From the copy (`SignupResource`) |
|---|---|---|
| Cost | one call per value, an HTTP call when `iam` runs elsewhere | a local query, joins included |
| Freshness | always current | current once the consumer has handled the events |
| Use it for | checking one value, reading one record | lists, filters, sorts |

### The `notifications` module

| File | What it does |
|---|---|
| `app/Handlers/SendWelcome.php` | Handles `iam.user.registered` and stores a welcome for the new user, once however many times the event arrives. |
| `app/Models/Notification.php` | What one person was told, in `notifications_inbox`. It is addressed to its recipient, so every query scopes on the authenticated user: there is no policy to write. |
| `app/Enums/NotificationType.php` | What a notification is about; it renders the title from `lang/en/messages.php` and the recipient's copy. |
| `app/Http/Controllers/NotificationController.php` | `GET /notifications/api/v1/notifications?filter[unread]=1&sort=-created_at&paginate=20` and `PATCH /notifications/api/v1/notifications/{id}/read`. Both scope on `principalIdOrFail()`: someone else's notification is a 404. |
| `app/Repositories/NotificationRepository.php` | The example of `EloquentRepository`: it declares the filters and sorts a request may use (any other is a 400), and the controller passes the recipient scope as `$constrain`. |
| `app/Models/UserShadow.php` | Its copy of iam's users, in `notifications_iam_users`: the authenticated user of its routes. |

### `foundation/Common`

`Foundation\Common\` holds the base classes every module uses. Like the rest of the foundation,
any module can use it. These classes are yours: the package doesn't depend on them, so you can
change or delete them.

| Path | What it does |
|---|---|
| `Http/Controller`, `ApiRequest`, `Resource`, `ApiErrorCode` | Base controller and request, and the response format: `{success, data}` or `{success, code, message}`. |
| `Auth/Authenticate`, `Principal`, `Principals`, `IsPrincipal`, `TokenValidator`, `HasPrincipal` | Token authentication for module routes. |
| `Contracts/Action`, `Operation` | One class per use case. |
| `Contracts/Repository`, `Database/EloquentRepository`, `Searchable` | Queries with filters, sorts and includes, and a single place for writes. |
| `Data/Dto` | Typed input and event payloads, built on spatie/laravel-data. |
| `Validation/ReferenceRule` | A field holding the id of another module's record: checks it exists through that module's contract, then runs the constraints a subclass adds to `$checks`. |
| `Exceptions/DomainException`, `IntegrityException` | Business errors (400) and system errors (500). |

`bootstrap/app.php` renders the exceptions that implement `RendersApiEnvelope` in the response
format above.

## Authentication

A route is protected with the `Foundation\Common\Auth\Authenticate` middleware:

```php
Route::get('me', [UserController::class, 'me'])->middleware(Authenticate::class);
```

The middleware works in two steps:

1. The `TokenValidator` turns the token into a user id. `AUTH_TOKEN_VALIDATION_STRATEGY` picks it:

   | Strategy | The token | How the module checks it | Calls iam |
   |---|---|---|---|
   | `jwt` (default) | a JWT iam signs at registration | with iam's public key, `AUTH_JWT_PUBLIC_KEY`; iam signs with `AUTH_JWT_PRIVATE_KEY` | no |
   | `rpc` | an opaque token, iam stores its hash | `IamService::findUserByToken()` | yes |
   | `gateway` | `X-Identity: {id}.{exp}.{hmac}`, set by a gateway that already authenticated the client | the HMAC, with `AUTH_GATEWAY_SECRET` | no |

2. `Principals` loads that user from the running module's own database, through the model the
   module names in its `config/auth.php`: `iam` reads its `User`, `analytics` its `UserShadow`.

A token is refused (401, Laravel's `AuthenticationException`) when it proves nothing, and also
when the module has no copy of the user yet: a user who has just registered reaches `analytics`
once it has consumed `iam.user.registered`.

In a controller that extends `Foundation\Common\Http\Controller`, `$this->principalId()` returns the id of
the authenticated user.

## Permissions

Roles and permissions belong to `iam`, which keeps them with `spatie/laravel-permission` in its
`iam_*` tables. Every other module asks for them through `IamService::grants($userId)`. That call
goes through RPC and the answer is cached.

- A module declares its permissions as an enum in the foundation, for example
  `Foundation\Analytics\Enums\AnalyticsPermission`, and lists it in `auth.permissions`.
  `php artisan iam:sync-permissions` then creates the declared permissions and drops every
  cached grant. With `--prune` it also deletes the permissions no enum declares, and every role's
  and user's hold on them: an enum missing from the list loses its grants. `composer setup`,
  `create-project` and the container whose `SYNC_PERMISSIONS` is `true` (iam's) run it with `--prune`.
- A policy extends `Foundation\Common\Policies\Policy` and checks
  `$this->allows($user, AnalyticsPermission::ReadDatasets)`. A controller calls
  `$this->authorize('viewAny', Dataset::class)`, which answers 403 when the user lacks the permission.
- Change permissions only through iam's actions, never by calling spatie directly, so the cache
  stays right. Each action clears the cache once its transaction commits:

  | Action | Clears |
  |---|---|
  | `GrantRole::grant()` / `revoke()` | that user's cached grants |
  | `SetRolePermissions::execute()` | every user's cached grants |

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

`config/modulith.php` declares every module in every process, so `analytics` still listens to
iam's events and calls iam over HTTP through `IamRpcService`. Both processes must share `APP_KEY`,
or the same `MODULITH_RPC_SECRET`.

When you build an image for one module, delete the folders of the others before
`composer dump-autoload`:

```bash
MODULITH_RUNS=analytics php artisan modulith:purge --force
```

Starting that image with `MODULITH_RUNS=iam` then fails at boot, because iam's folder is gone.

## Docker

The skeleton ships both setups, on Postgres (one database per module) and Redis (the event
stream), served by Octane with Swoole:

```bash
docker compose -f docker-compose.mono.yml up -d   # every module in one container, on :8000
docker compose up -d                              # one container per module: iam on :8001, analytics on :8002, notifications on :8003
```

`APP_PORT`, `IAM_PORT`, `ANALYTICS_PORT` and `NOTIFICATIONS_PORT` change the published ports. In the second setup, each
image is built with `--build-arg MODULITH_RUNS=<module>`, so it holds only its module: the
Dockerfile runs `modulith:purge` before `composer dump-autoload`. Each container also gets its own
application database (`app_iam`, `app_analytics`, `app_notifications`), so two containers never
migrate the same one. Both compose files validate tokens with the `rpc` strategy: they ship no JWT
keys. With `jwt`, give every container `AUTH_JWT_PUBLIC_KEY`, and iam `AUTH_JWT_PRIVATE_KEY`.

| File | What it does |
|---|---|
| `docker/Dockerfile` | Installs the dependencies, purges the modules the image doesn't run, then builds the PHP and Swoole runtime. |
| `docker/entrypoint.sh` | Runs `optimize`, migrates (on the `http` role only), writes one consumer per module of `WITH_CONSUMERS`, then starts supervisord. |
| `docker/supervisord.conf` | The roles a container can take, each switched on by a variable. |
| `docker/postgres/` | A Postgres image that creates the databases of `MODULE_DATABASES`, owned by `modulith`, written by `modulith_app`. |

| Role | Variable | Default | Run at most |
|---|---|---|---|
| `http` (Octane) | `WITH_HTTP` | `true` | as many as you need |
| `worker` (`queue:work`) | `WITH_WORKER` | `false` | as many as you need |
| `publisher` (`modulith:events:publish`) | `WITH_PUBLISHER` | `false` | one per module set: two would publish the outbox out of order |
| `scheduler` (supercronic) | `WITH_SCHEDULER` | `false` | one per module set: two would run each task twice |
| `consumer-<module>` | `WITH_CONSUMERS=iam,analytics,notifications` | none | one per module: two would break the order it reads in |

The values in the compose files are for development. In production, set real secrets.

## Tests

```bash
php artisan test
```

`tests/TestCase.php` gives each module an empty sqlite file and runs `migrate` before every
test. `tests/Feature/ModulesTest.php` covers the flow above, and checks that no module uses
another module's classes (`Modulith\Testing\Boundaries`) and that `modulith:doctor` passes.

| Where | What it holds |
|---|---|
| `tests/Feature/` | the tests that cross modules: registration, then what analytics and notifications make of it |
| `apps/{Module}/tests/` | the tests that only need that module, such as `apps/Iam/tests/Feature/TokensTest.php`; `phpunit.xml` lists them in the `Modules` suite |

`php artisan make:test InvoiceTest --module=billing` writes a test in the module.

To write to a module's database in a test, use `inModuleOf`. The module is read from the class, so
the test names no module:

```php
$user = $this->inModuleOf(User::class, fn () => User::query()->create([...]));
```

`composer check` runs Pint, PHPStan and the tests.

## Adding code to a module

Laravel's `make:*` commands take `--module`:

```bash
php artisan modulith:make-module billing --database          # a new module, declared in config/modulith.php and composer.json
php artisan make:model Invoice -mf --module=billing          # apps/Billing/app/Models, its migration and its factory
php artisan make:controller InvoiceController --module=billing
```

`composer.json` lists each module and the foundation under `autoload.psr-4`. The application doesn't
need those entries: the package autoloads the modules itself. They let your IDE resolve the classes.

## License

MIT.
