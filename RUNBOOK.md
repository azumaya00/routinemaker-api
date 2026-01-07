# API Runbook (Docker)

## Start containers

```bash
docker compose --env-file .env.docker up -d
```

## Migrate database

```bash
docker compose exec api php artisan migrate
```

## Health check

```bash
curl -i http://localhost:8001/api/health
```

## Sanctum SPA auth flow (cookie)

```bash
COOKIE_JAR=/tmp/rm-cookie.txt

# 1) Get CSRF cookie
curl -i -c "${COOKIE_JAR}" http://localhost:8001/sanctum/csrf-cookie

# 2) Login
XSRF_TOKEN=$(python - <<'PY'
import urllib.parse
with open("/tmp/rm-cookie.txt") as f:
    for line in f:
        if line.startswith("#") or "XSRF-TOKEN" not in line:
            continue
        token = line.strip().split("\t")[-1]
        print(urllib.parse.unquote(token))
        break
PY
)

curl -i -b "${COOKIE_JAR}" -c "${COOKIE_JAR}" \
  -H "Accept: application/json" \
  -H "X-XSRF-TOKEN: ${XSRF_TOKEN}" \
  -X POST http://localhost:8001/login \
  -d "email=you@example.com" \
  -d "password=password"

# 3) Me (authenticated)
curl -i -b "${COOKIE_JAR}" -H "Accept: application/json" http://localhost:8001/api/me

# 4) Logout
curl -i -b "${COOKIE_JAR}" -c "${COOKIE_JAR}" \
  -H "Accept: application/json" \
  -H "X-XSRF-TOKEN: ${XSRF_TOKEN}" \
  -X POST http://localhost:8001/logout

# 5) Me (unauthenticated)
curl -i -b "${COOKIE_JAR}" -H "Accept: application/json" http://localhost:8001/api/me
```
