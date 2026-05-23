
To install all the docker build

> docker compose up -d --build



To run a set of prepared tests:

> docker compose exec machine_php vendor/bin/phpunit

To run a static analysis:
> docker compose exec machine_php vendor/bin/phpstan analyse --ansi