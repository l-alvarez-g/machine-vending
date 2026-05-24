# Enterprise Vending Machine

A robust, enterprise-grade Vending Machine simulation built with PHP 8.4+. This project demonstrates advanced software engineering principles including Domain-Driven Design (DDD), Command Query Responsibility Segregation (CQRS), and Hexagonal Architecture (Ports and Adapters).

## 🏛 Architecture & Patterns

*   **Hexagonal Architecture (Ports and Adapters):** The core domain is strictly isolated from external concerns (CLI, persistence).
*   **Domain-Driven Design (DDD):** Rich domain model utilizing an Aggregate Root (`VendingMachine`) to protect business invariants and Immutable Value Objects (`Coin`, `Product`, `MoneyCollection`) to eliminate side-effects and floating-point precision issues.
*   **CQRS (Pragmatic):** Application use cases are isolated into strict `Commands` (DTOs) and `CommandHandlers`, orchestrating the domain and persistence seamlessly without leaking business logic.
*   **Test-Driven Development (TDD):** 100% code coverage ensuring stability.

## 🚀 Requirements

*   Docker
*   Docker Compose

## 📦 Installation & Setup (Dockerized)

This project runs entirely inside Docker to guarantee environment immutability.

1. **Spin up the containers:**
```bash
docker-compose up -d
```

2. **Install Composer dependencies inside the PHP container:**
```bash
docker exec -it machine_service_php composer install
```

## 💻 Usage (CLI REPL)

The application features an interactive Read-Eval-Print Loop (REPL) built with Symfony Console. Start the machine by running the executable entry point through the Docker container:

```bash
docker exec -it machine_service_php ./bin/console
```

Once the machine is initialized and ready, you can input commands separated by commas. 

**Supported Inputs:**
*   `[Insert Coin]`: Valid denominations are `0.05`, `0.10`, `0.25`, `1`
*   `[Return Coins]`: `RETURN-COIN`
*   `[Buy Product]`: `GET-SODA`, `GET-WATER`, `GET-JUICE`
*   `[Service Machine]`: `SERVICE` (Refills change and stock)
*   `[Exit]`: `EXIT`

**Example Interaction:**
```text
> 1, 0.25, 0.25, GET-SODA
-> SODA, 0.50
```

## 🛠 Testing & Quality Assurance

This project enforces maximum strictness in static analysis and testing to guarantee enterprise-level quality.

**Run the Test Suite (PHPUnit - Unit & Integration):**
```bash
docker exec -it machine_service_php vendor/bin/phpunit
```

**Run Static Analysis (PHPStan Level 9 Strict):**
```bash
docker exec -it machine_service_php vendor/bin/phpstan analyse --ansi
```