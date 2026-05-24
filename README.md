
To install all the docker build

> docker compose up -d --build



To run a set of prepared tests:

> docker compose exec machine_php vendor/bin/phpunit
> docker compose exec machine_php vendor/bin/phpunit --testsuite Unit
> docker compose exec machine_php vendor/bin/phpunit --testsuite Unit --filter CoinTest

To run a static analysis:
> docker compose exec machine_php vendor/bin/phpstan analyse
> docker compose exec machine_php vendor/bin/phpstan analyse --ansi

Clean PHP Stan
> vendor/bin/phpstan clear-result-cache
> composer dump-autoload -o