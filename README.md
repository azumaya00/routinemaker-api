# RoutineMaker API

Laravel API-only backend for Routine Maker.

## Local setup
- `composer install`
- `cp .env.example .env`
- `php artisan key:generate`
- Configure database settings in `.env`
- `php artisan migrate`
- `php artisan serve`

## Sanctum SPA (cookie) notes
- Set `SESSION_DOMAIN` to the parent domain (example: `.example.com`).
- Set `SANCTUM_STATEFUL_DOMAINS` to the frontend origin(s).
- CORS must allow the frontend origin and `supports_credentials=true`.
- The frontend should call `/sanctum/csrf-cookie` before login.

## Docker (local development)
Ports are offset to avoid conflicts with the work Docker setup.
Database container uses MariaDB 10.11 (LTS).

- `docker compose --env-file .env.docker up -d`
- `docker compose --env-file .env.docker exec api php artisan migrate`
- `docker compose --env-file .env.docker exec api php artisan test`
- Switch from MySQL to MariaDB: `docker compose --env-file .env.docker down -v` (this deletes DB data)
