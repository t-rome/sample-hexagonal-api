# sample-hexagonal-api

A reference implementation of a modern PHP REST API built with **Symfony 8**, demonstrating production-ready architectural patterns and engineering practices.

This project serves as a concrete, runnable example of how to combine:

- **API-first development** — the contract is defined before the code
- **Hexagonal Architecture** (Ports & Adapters) — strict separation between domain logic, application and infrastructure
- **Domain-Driven Design** — bounded contexts with rich domain models and domain events
- **CQRS** — commands mutate state, queries only read it; each use case is a single-responsibility handler dispatched through a bus port
- **Event-driven development** — domain events are raised on aggregates and published via a `DomainEventPublisherInterface` port. Application event handlers (`NotifyUserOnOrderPaidHandler`, `ReserveStockOnOrderPlacedHandler`) are pure PHP classes with no framework imports. They are triggered by thin Infrastructure Messenger handlers that receive a `DomainEventMessage` from the queue. The Messenger transport (Doctrine queue, AMQP, Redis, sync) is a single config value — no business logic changes needed to switch.
- **Layered testing strategy** — unit, functional, and BDD acceptance tests

---

## Table of Contents

- [Architecture](#architecture)
- [Project Structure](#project-structure)
- [API Documentation](#api-documentation)
- [Error Responses](#error-responses)
- [Testing Strategy](#testing-strategy)
- [Getting Started](#getting-started)
- [Composer Commands](#composer-commands)
- [Tech Stack](#tech-stack)

---

## Architecture

### Visual Diagrams

- [**Hexagonal Architecture Diagram**](docs/diagrams/architecture.html) — Domain boundaries, application layer (commands/queries), infrastructure adapters, and the dual sync/async event publishing approach.
- [**Testing Strategy**](docs/diagrams/testing.html) — Quality gates, test pyramid, and the QA pipeline from static analysis through BDD acceptance tests.
- [**Security & Authorization**](docs/diagrams/security.html) — Role hierarchy, access control matrix, ProductVoter architecture, and JWT authentication flow.

### Hexagonal Architecture

The application is divided into three **bounded contexts**(Order, Product, User), each following the same layered structure:

```
src/
├── Order/
│   ├── Application/     # Use cases: commands (PlaceOrder), query handlers (ListOrders, GetOrder), event handlers
│   ├── Domain/          # Aggregates, repository interfaces, domain events, exceptions
│   └── Infrastructure/  # HTTP controllers, Doctrine repositories, DTOs, persistence mappers, event subscribers
├── Product/
│   ├── Application/     # CRUD commands, query handlers, event handlers
│   ├── Domain/
│   └── Infrastructure/  # HTTP controllers, persistence, security voter, event subscribers
├── User/
│   ├── Application/     # RegisterUser command
│   ├── Domain/          # Aggregates, repository interface, ports (PasswordHasher, TokenRevocation)
│   └── Infrastructure/  # JWT auth listeners, blocklist, Doctrine repository, security adapters
└── Shared/
    ├── Application/     # Cross-cutting ports: CommandBusInterface, QueryBusInterface
    ├── Domain/          # Base classes: AggregateRoot, DomainEvent, DomainEventPublisherInterface
    └── Infrastructure/  # Bus impl, event publisher, OpenAPI builder, data fixtures, error handling
```

**Key rules enforced:**
- The **Domain layer** has zero framework or infrastructure dependencies.
- The **Application layer** depends only on domain interfaces (ports), never on concrete infrastructure. Cross-cutting concerns such as event publishing (`DomainEventPublisherInterface`) and message dispatching (`CommandBusInterface`, `QueryBusInterface`) are expressed as ports defined in the Application or Domain layer.
- **Infrastructure** implements those interfaces (adapters) and is the only layer allowed to import Symfony, Doctrine, or third-party libraries. Symfony's `EventSubscriberInterface` lives in Infrastructure event subscribers, not in Application event handlers.

### CQRS

Every use case is either a **Command** (mutates state, returns void) or a **Query** (reads state, never mutates). Commands such as `PlaceOrderCommand`, `PayOrderCommand`, and `RegisterUserCommand` are dispatched through `CommandBusInterface`; queries such as `GetOrderQuery` and `ListProductsQuery` are dispatched through `QueryBusInterface`. Both interfaces are ports defined in `Shared/Application/` and wired to Symfony Messenger in Infrastructure, so the transport (sync, async queue) can be swapped without touching any handler. Handlers live in `Application/Command/` and `Application/Query/` within each bounded context — each class has a single responsibility, making them straightforward to test in isolation.

### Domain Events and Async Messaging

Domain events decouple bounded contexts without coupling them through shared services. Aggregates (e.g. `Order`) extend `AggregateRoot`, which provides `recordEvent()` and `releaseEvents()` — events accumulate in memory during a command and are flushed only after the aggregate is persisted. The command handler then publishes them through `DomainEventPublisherInterface`.

This is **Ports & Adapters in action**: there are exactly **two adapters** behind one port. The active one is selected by a single line in `config/services.yaml` — zero changes in Domain or Application layer either way.

**Adapter 1 — `SymfonyDomainEventPublisher` (sync)**

```
CommandHandler → DomainEventPublisherInterface::publish(event)
  → SymfonyDomainEventPublisher
    → Symfony EventDispatcher::dispatch(event)
      → ReserveStockOnOrderPlacedSubscriber  →  ReserveStockOnOrderPlacedHandler  ← pure PHP
      → NotifyUserOnOrderPaidSubscriber       →  NotifyUserOnOrderPaidHandler       ← pure PHP
```

Handlers run within the same request and DB transaction. If a handler throws, the whole request fails — which can be desirable for strong consistency (e.g. stock reservation). No broker or worker needed.

**Adapter 2 — `MessengerDomainEventPublisher` (async, currently active)**

```
CommandHandler → DomainEventPublisherInterface::publish(event)
  → MessengerDomainEventPublisher
    → bus->dispatch(DomainEventMessage(event))
      → transport  (configured via MESSENGER_TRANSPORT_DSN)
        → worker: php bin/console messenger:consume domain_events
          → ReserveStockOnOrderPlacedMessengerHandler  →  ReserveStockOnOrderPlacedHandler  ← pure PHP
          → NotifyUserOnOrderPaidMessengerHandler       →  NotifyUserOnOrderPaidHandler       ← pure PHP
```

The HTTP response is returned immediately; handlers run in the background. The **transport is a single config value** — no PHP changes needed to switch:

| `MESSENGER_TRANSPORT_DSN` | Behaviour |
|---|---|
| `sync://` | Inline (no broker) — used in the **test environment** |
| `doctrine://default` | Postgres-backed queue — zero extra infrastructure |
| `amqp://guest:guest@rabbitmq:5672/%2f/domain_events` | RabbitMQ — **default in Docker** |

These are not three separate approaches: they are three transport options within the same async adapter. The application handlers (`NotifyUserOnOrderPaidHandler`, `ReserveStockOnOrderPlacedHandler`) are identical regardless of which adapter or transport is active. See `config/services.yaml` for the adapter swap comment and `src/Shared/Infrastructure/EventPublisher/` for both adapters with detailed trade-off documentation.

### API-First Approach

The OpenAPI specification lives in `docs/openapi.yaml` and is the source of truth for the API contract. It is built from modular files under `api-contract/` (paths and schemas). The contract is enforced at three layers:

1. **Spec validity** — `app:validate:openapi` validates `docs/openapi.yaml` against the OpenAPI standard.
2. **Sync check** — `app:openapi:check-sync` verifies that the generated `docs/openapi.yaml` is in sync with the `api-contract/` source fragments, catching any drift between the two.
3. **Contract enforcement** — every Behat scenario that sends or returns a JSON body includes `And the request body matches the OpenAPI spec` and `And the response matches the OpenAPI spec` steps, ensuring the endpoints both accept and return data that conforms to the contract.

### Security & Authorization

Access control uses Symfony's role hierarchy and a dedicated voter:

| Role | Granted via | Can do |
|---|---|---|
| Public | No token | Read products, read orders, auth endpoints, health |
| `ROLE_USER` | Registration | Everything public + place and pay orders |
| `ROLE_ADMIN` | Assigned in DB | Everything public + create, update, and delete products |

`ROLE_ADMIN` implies `ROLE_USER` via Symfony's role hierarchy — admins can also place and pay orders.

Product write operations are enforced by `ProductVoter` (`src/Product/Infrastructure/Security/`), a Symfony voter that checks `ROLE_ADMIN` on the token and returns `403 Access denied.` for authenticated users with insufficient privileges. See the [Security & Authorization diagram](docs/diagrams/security.html) for a full access matrix and flow.

---

## Project Structure

```
src/                    # Application source — bounded contexts + Shared
tests/
├── Unit/               # PHPUnit unit tests (isolated, no DB or HTTP)
└── Behat/              # Behat step definitions and context classes
features/               # Gherkin feature files (BDD acceptance scenarios)
docs/
├── openapi.yaml        # Generated OpenAPI spec (source of truth)
└── diagrams/           # Architecture, testing, and security diagrams
api-contract/           # OpenAPI source fragments (paths + schemas)
config/                 # Symfony configuration
```

---

## API Documentation

The full API is described in [`docs/openapi.yaml`](docs/openapi.yaml).

### Endpoints

**Auth** — `/api/auth`
- `POST /api/auth/register` — Register a new user
- `POST /api/auth/login` — Obtain a JWT token
- `POST /api/auth/logout` — Invalidate the current JWT token

**Products** — `/api/products`
- `GET /api/products` — List products _(public)_
- `GET /api/products/{id}` — Get a product _(public)_
- `POST /api/products` — Create a product `[ROLE_ADMIN]`
- `PUT /api/products/{id}` — Update a product `[ROLE_ADMIN]`
- `DELETE /api/products/{id}` — Delete a product `[ROLE_ADMIN]`

**Orders** — `/api/orders`
- `GET /api/orders` — List orders _(public)_
- `GET /api/orders/{id}` — Get an order _(public)_
- `POST /api/orders` — Place an order `[ROLE_USER]`
- `PATCH /api/orders/{id}/pay` — Pay an order (transitions `pending → confirmed`) `[ROLE_USER]`

> **Pluggable ports on `/pay`** — The handler depends on interfaces, not concrete implementations:
> - `PaymentGatewayInterface` — currently wired to `FakePaymentGateway` (always succeeds). Swap for a real adapter (`StripePaymentGateway`, `AdyenPaymentGateway`, …) in `config/services.yaml`.
> - `DomainEventPublisherInterface` — wired to `MessengerDomainEventPublisher`, which dispatches domain events to RabbitMQ via AMQP. The transport is configured in `config/packages/messenger.yaml`; changing `MESSENGER_TRANSPORT_DSN` in `.env` switches to a different broker (Redis, Doctrine, sync) without touching any handler.
> - `NotificationServiceInterface` — on success, `NotifyUserOnOrderPaid` fires and calls this port. Currently wired to `FakeNotificationService` (logs via Monolog). Swap for email, SMS, or push adapters the same way.
>
> **Design note:** Payment is modelled here as a port inside the Order bounded context, which is appropriate for a simple flow. In a system with richer payment concerns — refunds, partial payments, retries, reconciliation, or PCI scope isolation — Payment would deserve its own bounded context with a `PaymentIntent` aggregate, its own status lifecycle, and its own repository.

**System**
- `GET /health` — Health check (public)

Authentication uses **JWT Bearer tokens**. Obtain one via `/api/auth/login` and pass it as `Authorization: Bearer <token>` on protected endpoints.

---

## Error Responses

All error responses follow a consistent JSON format:

```json
{ "code": 2001, "error": "Product with id \"42\" not found." }
```

Validation failures include a `violations` array:

```json
{
  "code": 4002,
  "error": "Validation failed",
  "violations": [
    { "field": "name", "message": "This value should not be blank." }
  ]
}
```

Error code conventions: `1xxx` Order · `2xxx` Product · `3xxx` User · `4xxx` Shared (access control, validation).

Every domain exception extends `ApiBaseException` and carries an `#[ApiException(errorCode, httpStatusCode, message)]` attribute. The base class reads this attribute via reflection at construction time, interpolates `{{ placeholder }}` tokens from the context array, and exposes `errorCode()` and `statusCode()` — so exception mappers never hard-code HTTP status codes.

The `ApiExceptionSubscriber` maps exceptions to HTTP responses centrally — controllers contain no try/catch blocks. It delegates to two `ExceptionMapperInterface` implementations: `ApiBaseExceptionMapper` handles every domain exception that extends `ApiBaseException` (status code and error code come from the `#[ApiException]` attribute — no mapper changes needed when adding a new exception), and `HttpExceptionMapper` handles Symfony framework exceptions (`AccessDeniedException`, `ValidationFailedException`) that cannot extend `ApiBaseException`.

---

## Testing Strategy

See the [**Testing Strategy diagram**](docs/diagrams/testing.html) for a visual overview of the quality gates.

The project uses three complementary test types to cover different concerns:

### Unit Tests (`tests/Unit/`)

Test individual classes in isolation — no database, no HTTP, no framework. Focus on:
- Domain model invariants and business rules
- Application command/query handler logic
- Event handler behaviour

Run with: `composer app:test:unit`

### BDD Acceptance Tests (`features/`, `tests/Behat/`)

Written in Gherkin and executed by Behat. Make full HTTP round-trips against a real test database and describe behaviour from an outside-in perspective using natural language scenarios. Each scenario that returns a JSON body includes an `And the response matches the OpenAPI spec` step — meaning **every acceptance test simultaneously validates behaviour and verifies that the concrete endpoint implementation conforms to the API contract**.

Covers: authentication flows, product CRUD with `ROLE_ADMIN` enforcement (401, 403), order placement and payment, and all error cases (401, 403, 404, 409, 422).

Run with: `composer app:behat`

### QA Pipeline

```
app:openapi:check-sync  →  app:validate:openapi  →  app:cs:check  →  app:analyse  →  app:test  →  app:behat
```

Running `composer app:qa` executes all six gates in order. The pipeline fails fast — a stale or invalid contract stops the run before any code analysis or tests are executed.

---

## Getting Started

### Prerequisites

- Docker & Docker Compose
- PHP 8.4 and Composer available locally

> **Note:** JWT keys (`config/jwt/private.pem` / `public.pem`) are committed for convenience so you can clone and run immediately. **Replace them before deploying to any real environment.**

### 1. Build and start the stack

```bash
docker compose build
docker compose up -d
```

This starts:
- **PHP 8.4** (app server on port `8000`)
- **PostgreSQL 16** (port `5432`)
- **RabbitMQ 4** (AMQP on port `5672`, management UI on port `15672`)

### 2. Configure environment

```bash
cp .env.local.dist .env.local
```

Edit `.env.local` and adjust values as needed. `.env.local` is gitignored and never committed.

### 3. Install dependencies

```bash
composer install
```

### 4. Set up the dev database

```bash
composer app:db:reset
```

### 5. Set up the test database

```bash
cp .env.local.dist .env.test.local
```

Edit `.env.test.local` and change the database name from `api` to `api_test`:

```
DATABASE_URL="postgresql://app:secret@localhost:5432/api_test?serverVersion=16&charset=utf8"
```

Then initialise the test database:

```bash
composer app:db:reset:test
```

### 6. Start the Messenger worker

Domain events (e.g. stock reservation, user notification) are processed asynchronously via RabbitMQ. Start the worker in a separate terminal:

```bash
docker compose exec php php bin/console messenger:consume domain_events --time-limit=3600 -vv
```

The `-vv` flag prints each received message to the console — useful for watching the async flow live.

**To observe the full async loop:**
1. Place an order (`POST /api/orders`) or pay one (`PATCH /api/orders/{id}/pay`)
2. Open the RabbitMQ management UI at [http://localhost:15672](http://localhost:15672) (guest / guest) and watch the message appear in the `domain_events` queue
3. The worker picks it up, reserves stock and sends the notification

### 7. Verify everything works

```bash
composer app:qa
```

---

## Composer Commands

All project workflows are wired up as Composer scripts so there is a single, consistent interface regardless of environment.

### Quality Assurance

| Command | Description |
|---|---|
| `composer app:qa` | **Full QA pipeline** — runs OpenAPI validation → code style check → static analysis → all tests in sequence |
| `composer app:analyse` | PHPStan static analysis (level 8) |
| `composer app:cs:check` | Check code style against rules (dry-run, safe to run in CI) |
| `composer app:cs:fix` | Auto-fix code style violations |

### Testing

| Command | Description |
|---|---|
| `composer app:test` | PHPUnit unit tests (`tests/Unit/`) |
| `composer app:test:unit` | PHPUnit unit tests (explicit path) |
| `composer app:behat` | BDD acceptance tests via Behat (7 feature files, 29 scenarios) |

### Database

| Command | Description |
|---|---|
| `composer app:db:reset` | Drop, recreate, migrate, and seed the **dev** database |
| `composer app:db:reset:test` | Drop, recreate, migrate, and seed the **test** database |

### API Specification

| Command | Description |
|---|---|
| `composer app:openapi:build` | Merge `api-contract/` fragments into `docs/openapi.yaml` |
| `composer app:openapi:check-sync` | Verify `docs/openapi.yaml` is in sync with `api-contract/` |
| `composer app:validate:openapi` | Validate `docs/openapi.yaml` against the OpenAPI spec |

---

## Tech Stack

| Layer | Technology |
|---|---|
| Language | PHP 8.4 |
| Framework | Symfony 8.0 |
| ORM | Doctrine ORM 3.6 |
| Database | PostgreSQL 16 |
| Message Broker | RabbitMQ 4 (AMQP) via Symfony Messenger |
| Authentication | JWT via LexikJWTAuthenticationBundle |
| Logging | Monolog 3.x — JSON format (file in dev, stderr in prod) |
| Static Analysis | PHPStan 2.x (level 8) with Symfony & Doctrine extensions |
| Code Style | PHP-CS-Fixer 3.x |
| Unit & Functional Tests | PHPUnit 13 |
| Acceptance Tests | Behat 4.x |
| API Specification | OpenAPI 3.x |
| Containerisation | Docker / Docker Compose |
