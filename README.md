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

The OpenAPI specification lives in `docs/openapi.yaml` and is the source of truth for the API contract. It is built from modular files under `docs/openapi/` (paths and schemas) and validated as part of the QA pipeline before any tests run.

---

## Getting Started

### Prerequisites

- Docker & Docker Compose
- PHP 8.4 and Composer available locally

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
| `composer app:openapi:build` | Merge modular OpenAPI files into `docs/openapi.yaml` |
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
- `GET /api/orders` — List orders
- `GET /api/orders/{id}` — Get an order

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

### Functional Tests (`tests/Functional/`)

Full HTTP round-trips against a real test database. Each test boots the Symfony kernel, hits an endpoint, and asserts on the response. Covers:
- Authentication flows (register, login, logout)
- Product CRUD endpoints
- Order placement and retrieval

Run with: `composer app:test:functional`

### BDD Acceptance Tests (`features/`, `tests/Behat/`)

Written in Gherkin (`features/product/products.feature`) and executed by Behat. Describe behaviour from an outside-in perspective using natural language scenarios. Useful for documenting and validating complete user-facing flows.

Run with: `composer app:behat`

### QA Pipeline

```
app:validate:openapi  →  app:cs:check  →  app:analyse  →  app:test  →  app:behat
```

Running `composer app:qa` executes all five gates in order. The pipeline fails fast — a broken contract or a style violation stops the run before any tests are executed.

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
