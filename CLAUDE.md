# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Working principles

These reduce the most common ways an LLM goes wrong in this codebase. They favor thoughtfulness over speed; use judgment on trivial work.

**Think before coding.** State assumptions explicitly; if uncertain, ask. When a request has multiple reasonable interpretations, surface the tradeoffs instead of silently picking one. Name confusion rather than proceeding on a guess — this plugin wires into an external `integration-core` package whose contracts you cannot change, so a wrong assumption about where a behavior lives wastes a whole edit.

**Simplicity first.** Deliver what was asked, not speculative features. Skip new abstractions for single-use code. If you wrote 200 lines and 50 would do, rewrite it. The codebase already has a service/controller/repository structure — fit into it rather than inventing a parallel one.

**Surgical changes.** Touch only code tied to the request. Match the surrounding style (see naming conventions below) and do not refactor unrelated code. Remove only the imports/methods your own change made obsolete.

**Goal-driven execution.** Turn the request into verifiable success criteria (a passing test, a clean `bin/phpcs`/`bin/phpstan` run, a working checkout in the Docker env). For multi-step work, state a brief plan with checkpoints before diving in.

## Repository layout

This repo holds **two WordPress plugins** plus the Docker dev harness around them:

- `sequra/` — the production **seQura Payment Gateway for WooCommerce** plugin (the one published to wordpress.org). This is where almost all real work happens.
- `sequra-helper/` — a dev/test-only companion plugin exposing helpers (configure dummy merchants, clear config, force a checkout failure, fill/clear logs, set theme/cart/checkout page versions). Not shipped to users.
- root (`setup.sh`, `teardown.sh`, `docker/`, `docker-compose*.yml`, `bin/`, `.env`) — the dockerized local environment and CLI wrappers.

## Architecture (the big picture)

The plugin is a **thin WooCommerce adapter over the shared `sequra/integration-core` Composer package** (`require: sequra/integration-core ~5.1.0`). All cross-platform business logic (order creation, webhooks, ORM, task queue, settings domain, DI container) lives in that vendored core under the `SeQura\Core\*` namespace. This repo (`SeQura\WC\*`) supplies WooCommerce-specific implementations and hooks.

Key consequences for any change:

- **DI via `ServiceRegister`.** `sequra/src/class-bootstrap.php` extends the core's `BootstrapComponent` and is the single place where WC implementations are bound to core interfaces. To swap or add a service, register it here — do not `new` services directly. Services are resolved with `ServiceRegister::getService(SomeInterface::class)`.
- **Interface contracts come from the core, implementations live here.** The core declares contracts like `ProductServiceInterface`, `DisconnectServiceInterface`, `MerchantDataProviderInterface`, `OrderCreationInterface` under `SeQura\Core\BusinessLogic\Domain\Integration\*`. This repo implements them under:
  - `sequra/src/Core/Implementation/` — concrete WC implementations of core integration interfaces.
  - `sequra/src/Core/Extension/` — WC-specific *extensions* of core domain types (e.g. the `Create_Order_Request_Builder` and `Order_Status_Settings_Service`).
- **`sequra/src/Services/*`** — the WooCommerce domain services (`Cart`, `Order`, `Payment`, `Product`, `Shopper`, `Report`, `Widgets`, `Settings`, `Pricing`, `I18n`, `Log`, `Migration`, plus `Constants`). These are the god nodes of the codebase; most features touch one of them.
- **`sequra/src/Controllers/Hooks/*`** — controllers registered against WordPress/WooCommerce hooks (Order, Payment, Product, Settings, Asset, Process, I18n). **`sequra/src/Controllers/Rest/*`** — REST controllers that back the admin React UI.
- **Admin UI is external.** The settings/onboarding screens are the `sequra-core-admin-fe` package (npm dep `github:sequra/integration-core-ui`). `npm run build` copies its `dist` into `sequra/assets/integration-core-ui/`; the REST controllers above are its backend.
- **Persistence via the core ORM.** Entities and `RepositoryRegistry` come from the core; schema changes are versioned migrations in `sequra/src/Repositories/Migrations/` (e.g. `Migration_Install_430`). Add a new migration class rather than altering tables ad hoc.

### Naming conventions (do not mix)

- `SeQura\WC\*` (this repo) follows **WordPress coding standards**: classes are `Underscore_Cased`, files are `class-foo.php`, methods are `snake_case`. PHPCS enforces this.
- `SeQura\Core\*` (vendored core) is **PSR-style** `CamelCase`. Never edit files under `vendor/`.

## Local development environment

Everything runs in Docker; you rarely run PHP/Node directly on the host.

```bash
./setup.sh              # start WP + WooCommerce + MariaDB; prints the wp-admin URL and credentials
./setup.sh --install    # also install composer/npm deps and build assets (first run / after dep changes)
./setup.sh --cloudflared --cloudflared-token=TOKEN   # expose the site publicly (required for E2E callbacks)
./setup.sh --ngrok --ngrok-token=TOKEN               # alternative public tunnel
./teardown.sh           # stop and clean up the environment
```

Configuration is read from `.env` (copied from `.env.sample` on first run) — duplicate and rename `.env.sample` to customize WordPress/WooCommerce/PHP/MariaDB versions before running `setup.sh`.

### Building front-end assets (run inside `sequra/`)

```bash
npm run build   # production: copy integration-core-ui dist, webpack (production), compile + minify SCSS
npm run dev     # development: same, webpack dev mode with progress, non-minified SCSS
```

### Quality assurance (CLI wrappers in `bin/`, run from repo root)

These wrap dockerized `php:8.4-cli-alpine` and run against **both** `sequra/` and `sequra-helper/`:

```bash
bin/php-syntax-check --php=7.3   # syntax-lint across the supported PHP matrix (7.3–8.4)
bin/phpcs                        # PHPCS, standard .phpcs.xml.dist (WP coding standards)
bin/phpcbf                       # auto-fix PHPCS violations
bin/phpstan                      # PHPStan, config phpstan.neon, level set per project
```

Run `bin/phpcs` and `bin/phpstan` before considering a PHP change done — CI (`.github/workflows/php-qa.yml`) gates on both.

### Unit & integration tests

Tests run **inside the `web` container** (the plugin is mounted at `/var/www/html/wp-content/plugins/_sequra`):

```bash
docker compose exec web /usr/local/bin/setup-tests.sh    # one-time: generate WP test scaffolding
docker compose exec web /usr/local/bin/run-tests.sh      # run the full PHPUnit suite (--testdox)
```

Run a single test or file (PHPUnit `--filter` / path):

```bash
docker compose exec web bash -c \
  'cd /var/www/html/wp-content/plugins/_sequra && vendor/bin/phpunit -c ./phpunit.xml.dist --filter testMethodName'
```

PHPUnit config: `sequra/phpunit.xml.dist`. Test sources live under `sequra/tests/` (`Controllers/`, `Core/`, `Fixtures/`, `Repositories/`, `Services/`), autoloaded via `composer.json` classmaps.

### End-to-end tests (Playwright)

E2E requires the store to be reachable from the internet (seQura performs checkout callbacks), so start the env with `--cloudflared` or `--ngrok` first.

```bash
bin/playwright                      # headless run of sequra/tests-e2e specs (sources .env, runs `npx playwright test`)
bin/playwright --ui                 # UI mode
bin/playwright path/to/spec.spec.js # a single spec; any extra args pass through to `playwright test`
``

