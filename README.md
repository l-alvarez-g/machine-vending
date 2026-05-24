# Enterprise Vending Machine

A robust, enterprise-grade Vending Machine simulation built with PHP 8.4+. This project demonstrates advanced software engineering principles including Domain-Driven Design (DDD), Command Query Responsibility Segregation (CQRS), and Hexagonal Architecture (Ports and Adapters).

## 🏛 Architecture & Patterns

* **Hexagonal Architecture (Ports and Adapters):** The core domain is strictly isolated from external concerns (CLI, persistence).
* **Domain-Driven Design (DDD):** Rich domain model utilizing an Aggregate Root (`VendingMachine`) to protect business invariants and Immutable Value Objects (`Coin`, `Product`, `MoneyCollection`) to eliminate side-effects and floating-point precision issues.
* **CQRS (Command Query Responsibility Segregation):** Application use cases are strictly isolated. Write operations (`Commands`) mutate state via the Aggregate Root, while read operations (`Queries`) bypass complex domain logic to deliver fast, immutable `DTOs` to the presentation layer.
* **Test-Driven Development (TDD):** 100% code coverage ensuring stability.

## 🚀 Requirements

* Docker
* Docker Compose

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
* `[Insert Coin]`: Valid denominations are `0.05`, `0.10`, `0.25`, `1`
* `[Return Coins]`: `RETURN-COIN`
* `[Buy Product]`: `GET-SODA`, `GET-WATER`, `GET-JUICE`
* `[Machine Status]`: `STATUS` (Displays current vault and inventory state)
* `[Service Machine]`: `SERVICE[<coins>|<inventory>]` (Reconfigures coins and/or stock).
* `[Exit]`: `EXIT`

---

## 🎮 Example Interactions (Consumer)

**Example 1: Buy Soda with exact change**
```text
> 1, 0.25, 0.25, GET-SODA
-> SODA
```

**Example 2: Start adding money, but ask for coin return**
```text
> 0.10, 0.10, RETURN-COIN
-> 0.10, 0.10
```

**Example 3: Buy Water requiring change**
```text
> 1, GET-WATER
-> WATER, 0.25, 0.10
```

**Example 4: Overpay for Soda**
```text
> 1, 1, GET-SODA
-> SODA, 0.50
```

---

## 🔧 Service & Maintenance Commands (Operator)

The `SERVICE` command allows technicians to securely reconfigure the machine's state without rebooting. It leverages smart routing to support full or partial updates. Successful service operations automatically trigger a status report.

**Full Service (Replace all coins and inventory):**
```text
> SERVICE[0.05:10;0.10:10;0.25:10|WATER:10;SODA:5;JUICE:15]
```

**Partial Service (Update specific coins and full inventory):**
```text
> SERVICE[0.25:10|WATER:10;SODA:5;JUICE:15]
```

**Service Only Coins (Preserves current inventory):**
```text
> SERVICE[0.05:20;0.10:15;0.25:5]
```

**Service Only Inventory (Preserves current change vault):**
```text
> SERVICE[WATER:10;SODA:25;JUICE:15]
```

**Check Current Machine Status:**
```text
> STATUS
```

---

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