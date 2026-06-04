# sample-hexagonal-api

A reference implementation of a modern PHP REST API built with **Symfony 8**, demonstrating production-ready architectural patterns and engineering practices.

This project serves as a concrete, runnable example of how to combine:

- **API-first development** — the contract is defined before the code
- **Hexagonal Architecture** (Ports & Adapters) — strict separation between domain logic and infrastructure
- **Domain-Driven Design** — bounded contexts with rich domain models and domain events
- **Event-driven development** — decoupled communication via domain events
- **Layered testing strategy** — unit, functional, and BDD acceptance tests

---

## Table of Contents

- [Architecture](#architecture)
- [Project Structure](#project-structure)
- [Getting Started](#getting-started)
- [Composer Commands](#composer-commands)
- [API Documentation](#api-documentation)
- [Testing Strategy](#testing-strategy)
- [Tech Stack](#tech-stack)

---

## Architecture

### Visual Diagrams

- [**Hexagonal Architecture Diagram**](docs/diagrams/architecture.html) — Domain boundaries, application layer (commands/queries), and infrastructure adapters across all three bounded contexts.
- [**Testing Strategy**](docs/diagrams/testing.html) — Quality gates, test pyramid, and the QA pipeline from static analysis through BDD acceptance tests.

### Hexagonal Architecture

The application is divided into three **bounded contexts**, each following the same layered structure:

```
src/
├── Order/
│   ├── Application/     # Use cases: commands (PlaceOrder) and query handlers (ListOrders, GetOrder)
│   ├── Domain/          # Aggregates, repository interfaces, domain events, exceptions
│   └── Infrastructure/  # HTTP controllers, Doctrine repositories, DTOs, persistence mappers
├── Product/
│   ├── Application/     # CRUD commands and query handlers
│   ├── Domain/
│   └── Infrastructure/
├── User/
│   ├── Application/     # RegisterUser command
│   ├── Domain/
│   └── Infrastructure/  # JWT auth listeners, blocklist, Doctrine repository
└── Shared/
    ├── Domain/          # Base classes: AggregateRoot, DomainEvent
    └── Infrastructure/  # OpenAPI builder, data fixtures, health endpoint, error handling
```

**Key rules enforced:**
- The **Domain layer** has zero framework or infrastructure dependencies.
- The **Application layer** depends only on domain interfaces (ports), never on concrete infrastructure.
- **Infrastructure** implements those interfaces (adapters) and is the only layer allowed to import Symfony, Doctrine, or third-party libraries.

### Domain Events

Domain events decouple bounded contexts. When an order is placed, an `OrderPlaced` event is raised on the aggregate root and dispatched by the infrastructure layer — other contexts react without being directly coupled to the Order module.

### API-First Approach

The OpenAPI specification lives in `docs/openapi.yaml` and is the source of truth for the API contract. It is built from modular files under `api-contract/` (paths and schemas) and validated as part of the QA pipeline before any tests run.

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

### 6. Verify everything works

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
| `composer app:test` | Run the complete test suite (unit + functional + Behat) |
| `composer app:test:unit` | Unit tests only |
| `composer app:test:functional` | Functional/integration tests only |
| `composer app:behat` | BDD acceptance tests via Behat |

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

## API Documentation

The full API is described in [`docs/openapi.yaml`](docs/openapi.yaml).

### Endpoints

**Auth** — `/api/auth`
- `POST /api/auth/register` — Register a new user
- `POST /api/auth/login` — Obtain a JWT token
- `POST /api/auth/logout` — Invalidate the current JWT token

**Products** — `/api/products`
- `GET /api/products` — List products
- `POST /api/products` — Create a product
- `GET /api/products/{id}` — Get a product
- `PUT /api/products/{id}` — Update a product
- `DELETE /api/products/{id}` — Delete a product

**Orders** — `/api/orders`
- `POST /api/orders` — Place an order
- `PATCH /api/orders/{id}/pay` — Pay an order (transitions `pending → confirmed`)
- `GET /api/orders` — List orders
- `GET /api/orders/{id}` — Get an order

> **Pluggable ports on `/pay`** — The handler depends on two interfaces, not concrete implementations:
> - `PaymentGatewayInterface` — currently wired to `FakePaymentGateway` (always succeeds). Swap for a real adapter (`StripePaymentGateway`, `AdyenPaymentGateway`, …) in `config/services.yaml`.
> - `NotificationServiceInterface` — on success, `NotifyUserOnOrderPaid` fires and calls this port. Currently wired to `FakeNotificationService` (logs via Monolog). Swap for email, SMS, or push adapters the same way.
>
> **Design note:** Payment is modelled here as a port inside the Order bounded context, which is appropriate for a simple flow. In a system with richer payment concerns — refunds, partial payments, retries, reconciliation, or PCI scope isolation — Payment would deserve its own bounded context with a `PaymentIntent` aggregate, its own status lifecycle, and its own repository.

**System**
- `GET /health` — Health check (public)

Authentication uses **JWT Bearer tokens**. Obtain one via `/api/auth/login` and pass it as `Authorization: Bearer <token>` on protected endpoints.

### Error Responses

All error responses follow a consistent JSON format:

```json
{ "error": "Product with id \"42\" not found." }
```

Validation failures include a `violations` array:

```json
{
  "error": "Validation failed",
  "violations": [
    { "field": "name", "message": "This value should not be blank." }
  ]
}
```

The `ApiExceptionSubscriber` maps all domain exceptions to the appropriate HTTP status codes centrally — controllers contain no try/catch blocks.

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

Covers: authentication flows, product CRUD, order placement, payment, and all error cases (401, 404, 409, 422).

Run with: `composer app:behat`

### QA Pipeline

```
app:openapi:check-sync  →  app:validate:openapi  →  app:cs:check  →  app:analyse  →  app:test  →  app:behat
```

Running `composer app:qa` executes all six gates in order. The pipeline fails fast — a stale or invalid contract stops the run before any code analysis or tests are executed.

---

## Tech Stack

| Layer | Technology |
|---|---|
| Language | PHP 8.4 |
| Framework | Symfony 8.0 |
| ORM | Doctrine ORM 3.6 |
| Database | PostgreSQL 16 |
| Authentication | JWT via LexikJWTAuthenticationBundle |
| Logging | Monolog 3.x — JSON format (file in dev, stderr in prod) |
| Static Analysis | PHPStan 2.x (level 8) with Symfony & Doctrine extensions |
| Code Style | PHP-CS-Fixer 3.x |
| Unit & Functional Tests | PHPUnit 13 |
| Acceptance Tests | Behat 4.x |
| API Specification | OpenAPI 3.x |
| Containerisation | Docker / Docker Compose |
