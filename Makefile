.PHONY: up down build install migrate fresh artisan composer npm shell logs

up:
	docker compose up -d

down:
	docker compose down

build:
	docker compose build

install:
	docker compose run --rm app composer install
	docker compose run --rm node npm install --legacy-peer-deps

migrate:
	docker compose exec app php artisan migrate

fresh:
	docker compose exec app php artisan migrate:fresh --seed

artisan:
	docker compose exec app php artisan $(cmd)

composer:
	docker compose run --rm app composer $(cmd)

npm:
	docker compose run --rm node npm $(cmd)

shell:
	docker compose exec app bash

logs:
	docker compose logs -f