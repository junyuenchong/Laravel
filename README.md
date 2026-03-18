# Item Management Project (Laravel + Vue) — Frontend/Backend Separate

This repository contains a **separate** Laravel backend API and a Vue frontend SPA.

## Table of contents

- Quick start (Docker backend + Vite frontend)
- Quality, tests, and E2E
- Database migrations & seeders
- Redis caching
- API overview (CSRF + JWT + items)
- Performance notes
- Production setup
- CI/CD
- Git hooks (Husky + lint-staged)
- Commit message format

## Structure

- `backend/` — Laravel 12 API (Dockerized: **single backend image** with Nginx + PHP-FPM + Redis + MySQL + Adminer; TLS only in prod override)
- `frontend/` — Vue 3 + TypeScript (Vite) + Pinia + Vue Router

Backend application layering (easy-to-maintain):

- **Controllers**: `backend/app/Http/Controllers/**` (thin HTTP layer)
- **Requests (input validation)**: `backend/app/Http/Requests/**`
- **DTOs**: `backend/app/DTO/**` (typed input/output objects; pagination lives in `CursorPageDTO`)
- **CQRS**
  - **Commands**: `backend/app/CQRS/**/Commands/**` (write)
  - **Queries**: `backend/app/CQRS/**/Queries/**` (read)
  - **Handlers**: `backend/app/CQRS/**/Handlers/**` (business orchestration)
- **Repositories**: `backend/app/Repositories/**` (database query building)
- **Services**: `backend/app/Services/**` (shared domain helpers like JWT cookie + caching)

Typical request flow:

`Controller` → `FormRequest` → `DTO` → `Command/Query` → `Handler` → `Repository/Service` → JSON response

## Tech Stack

- **Backend**: Laravel 12, PHP 8.2, MySQL, Redis, Nginx, Docker / Docker Compose
- **Frontend**: Vue 3, TypeScript, Vite, Pinia, Vue Router
- **Testing**: PHPUnit, Laravel Pint, PHPStan (Larastan), Vitest, Playwright
- **CI/CD**: GitHub Actions

## Requirements

- Docker Desktop
- Node.js (for running the frontend)
- (Optional) Local PHP 8.2+, Composer, and Node.js if you want to run the backend directly without Docker

## Quick Start

### 1) Start Backend (Docker)

```bash
docker compose -f backend/docker-compose.yml up -d --build
```

- **Backend HTTP (dev)**: `http://localhost:8080`
- **Adminer**: `http://localhost:8081`
- **MySQL**: `localhost:3307` (container `3306`)

Notes:

- Dev mode uses **HTTP** (no TLS) by default.

Environment variables (most have safe defaults):

- `APP_PORT` (default `8080`): host port for backend HTTP.
- `APP_TLS_PORT` (default `8443`): host port for backend HTTPS when TLS override is enabled.
- `DB_PORT_FORWARD` (default `3307`): host port for MySQL (container is always `3306`).
- `ADMINER_PORT` (default `8081`): host port for Adminer UI.
- `FRONTEND_URL` (default `http://localhost:5173`): SPA origin used for CORS and cookies.
- `COOKIE_SECURE` / `SESSION_SECURE_COOKIE` (default `false` in dev): whether cookies are marked `Secure`.
- `COOKIE_SAMESITE` / `SESSION_SAME_SITE` (default `lax`): SameSite mode.
- `JWT_TTL_SECONDS` (default `3600`): JWT lifetime.
- `JWT_SECRET`: symmetric secret for signing JWTs (required in any serious environment).

### (Optional) Backend TLS (Production-like)

Enable TLS via the production override compose file:

```bash
docker compose -f backend/docker-compose.yml -f backend/docker-compose.prod.yml up -d --build
```

- **Backend HTTPS (TLS)**: `https://localhost:8443`

### Backend Redis cache smoke test (verify Redis caching works)

```bash
docker compose -f backend/docker-compose.yml exec backend php artisan tinker --execute "cache()->put('redis_smoke','ok',60); echo cache()->get('redis_smoke');"
```

Expected output: `ok`

Important:

- If you don't want Redis authentication, use `REDIS_PASSWORD=` (empty).
- Do **not** set `REDIS_PASSWORD=null` (string), it will cause confusing NOAUTH issues.

### 2) Start Frontend (Vite)

```bash
cd frontend
npm install
npm run dev
```

Frontend URL (dev): `http://localhost:5173`

The dev server runs on **HTTP** and proxies `/api` to the backend (default: `http://localhost:8080`).

### 3) Quality & Tests (Backend + Frontend)

#### Testing overview (what exists in this repo)

- **Backend tests (PHPUnit)**: `backend/tests/**` (feature + unit)
- **Frontend unit/integration tests (Vitest)**: `frontend/src/**/*.{test,spec}.ts`
- **End-to-end tests (Playwright)**: `frontend/e2e/**`

#### Backend (Laravel)

From the `backend/` directory:

```bash
# Code style (Laravel Pint)
composer lint

# Static analysis (PHPStan + Larastan)
composer analyse

# Tests (PHPUnit: unit + feature)
php artisan test
```

You can also use Composer scripts:

```bash
# One-shot setup (install deps, copy .env, generate key, run migrations, build assets)
composer setup

# Local dev stack (PHP server + queue + logs + Vite) – mainly useful if NOT using Docker
composer dev
```

#### Frontend (Vue)

From the `frontend/` directory:

```bash
# Lint (ESLint)
npm run lint

# Format check / auto-fix (Prettier)
npm run format
npm run format:fix

# Type-check (Volar-compatible; checks Vue SFC + TS types)
npm run typecheck

# Unit / integration tests (Vitest)
npm test

# E2E tests (Playwright)
npm run e2e
```

All tests (suggested local order):

```bash
# Frontend checks
cd frontend
npm run lint
npm run format
npm run typecheck
npm test

# Backend checks
cd ../backend
composer lint
composer analyse
php artisan test
```

Frontend “what to run and when”:

- **During development**: `npm run lint`, `npm run typecheck`
- **Before pushing / PR**: `npm run lint`, `npm run format`, `npm run typecheck`, `npm test`
- **Before release**: add `npm run e2e` (requires the app to be running)

Important note about unit tests vs E2E:

- `npm test` runs **Vitest** (unit/integration tests under `frontend/src/**`).
- `npm run e2e` runs **Playwright** (end-to-end tests under `frontend/e2e/**`).

E2E prerequisites (local):

- Backend must be running (Docker is recommended): `docker compose -f backend/docker-compose.yml up -d --build`
- Frontend dev server should be running (HTTP): `cd frontend && npm run dev`
- Then run: `cd frontend && npm run e2e`

## Redis caching

Where Redis is configured:

- **Docker wiring**: `backend/docker-compose.yml` starts a `redis` service and sets `CACHE_STORE=redis` for Laravel.
- **Laravel config**:
  - `backend/config/cache.php` defines the `redis` cache store.
  - `backend/config/database.php` defines the Redis `cache` connection (`REDIS_CACHE_DB`).

Where Redis caching is used today:

- **`GET /api/auth/me`**: cached for 1 minute in `backend/app/Http/Controllers/AuthController.php`
  - Key: `users:me:<id>`
  - Invalidation: `PUT /api/auth/me` calls `Cache::forget('users:me:<id>')`
- **`GET /api/items`**: cached for 30 seconds in `backend/app/Http/Controllers/ItemController.php`
  - Keyed by user + query params + cursor
  - Invalidation: create/update/delete bumps an `items:cache_version` key (new requests use new cache keys)

How to add caching elsewhere (pattern):

- Read caching:
  - `Cache::remember($key, $ttl, fn () => expensiveWork())`
- Invalidate on writes:
  - `Cache::forget($key)` for single-object caches
  - Or bump a version key for list caches (fast invalidation)

## Database migrations & seeders (run / rollback / reset)

Recommended (Docker):

```bash
# Run migrations
docker compose -f backend/docker-compose.yml exec -T backend php artisan migrate

# Seed data (runs DatabaseSeeder)
docker compose -f backend/docker-compose.yml exec -T backend php artisan db:seed

# Fresh database (drop all tables, re-run all migrations, then seed)
docker compose -f backend/docker-compose.yml exec -T backend php artisan migrate:fresh --seed

# Rollback last batch of migrations
docker compose -f backend/docker-compose.yml exec -T backend php artisan migrate:rollback

# Rollback everything (dangerous)
docker compose -f backend/docker-compose.yml exec -T backend php artisan migrate:reset
```

Optional (local, without Docker):

```bash
cd backend
php artisan migrate
php artisan db:seed
php artisan migrate:fresh --seed
php artisan migrate:rollback
php artisan migrate:reset
```

## Performance notes (how to improve)

What is already optimized:

- **Pagination**: `/api/items` uses **cursor pagination** (better than offset pagination for large tables).
- **Database indexes**:
  - `items.sku` is **unique indexed** (fast exact match).
  - `items.is_active` has an index to keep filtering fast.
  - `items.name` has a **FULLTEXT** index on MySQL/MariaDB (with a safe fallback for other DBs).
- **Caching (Redis)**:
  - `/api/auth/me` is cached for 1 minute.
  - `/api/items` list responses are cached for 30 seconds with versioned keys (fast invalidation on writes).

How to improve further (production checklist):

- **Measure first**: track p95 latency for `/api/items` and cache hit rate before tuning TTLs.
- **Tune search**:
  - FULLTEXT is fast for name search; the `LIKE '%q%'` fallback is the slow path.
  - For very large datasets, consider limiting the `LIKE` fallback or using a dedicated search engine.
- **Add indexes for new filters**: any new query parameter should usually have an index (or a search index).

### Editor setup (Vue 3)

- **Use Volar** (Vue 3 + TypeScript).
- **Do not use Vetur** in the same workspace (it can conflict with Volar).

How to “check Volar” in CI/terminal:

- Volar’s type intelligence is powered by `vue-tsc`. Run `npm run typecheck`.

## Default Test User

The database seeder creates a test user:

- Email: `test@example.com`
- Password: `password`

## API Overview

### Security model (SPA)

- **CSRF cookie**: `GET /api/csrf-cookie`
- **Login**: `POST /api/auth/login` (sets `access_token` **HttpOnly** cookie)
- **Me**: `GET /api/auth/me`
- **Logout**: `POST /api/auth/logout`

SPA rules:

- Frontend uses `withCredentials: true`.
- Call `GET /api/csrf-cookie` before any `POST/PUT/PATCH/DELETE`.

### JWT token & cookie security (business / production)

This project uses a **JWT stored in an HttpOnly cookie** (`access_token`) for API authentication.

How it is configured:

- **Signing algorithm**: `HS256` (symmetric secret).
- **JWT secret source**:
  - Preferred: set `JWT_SECRET` in `backend/.env`.
  - Fallback: if `JWT_SECRET` is empty, the app falls back to Laravel `APP_KEY` (not recommended for production).
- **Lifetime**: controlled by `JWT_TTL_SECONDS` (default `3600`).
- **Cookie flags**:
  - `HttpOnly`: enabled (the SPA cannot read the token via JavaScript).
  - `Secure`: controlled by `COOKIE_SECURE` (must be `true` in production HTTPS).
  - `SameSite`: controlled by `COOKIE_SAMESITE` (recommended `lax` for same-site; use `none` only when cross-site and then `Secure=true` is mandatory).

Production recommendations:

- **Always set `JWT_SECRET`** to a long random value (32+ bytes). Keep it out of git and rotate if leaked.
- **Use HTTPS** and set `COOKIE_SECURE=true` in production.
- **Keep TTL short** (for example 15–60 minutes) and plan a refresh strategy if needed.
- **Rotate secrets safely**: deploy support for multiple secrets during rotation (current code uses a single secret).
- **Limit exposure**: the API already uses throttling on auth endpoints; keep rate limits and monitoring in place.

### Items (CRUD + search + performant pagination)

Items endpoints require JWT cookie auth:

- `GET /api/items`
  - Query params:
    - `q`: search (exact match by `sku` OR partial match by `name`)
    - `per_page`: 1..100
    - `cursor`: for cursor pagination
- `POST /api/items`
- `GET /api/items/{id}`
- `PUT /api/items/{id}`
- `DELETE /api/items/{id}`

Pagination is **cursor-based** (good performance for large datasets).

Database performance notes:

- `items.sku` is **unique indexed** (fast exact lookups).
- `items.is_active` has an index to keep active/inactive filtering fast.
- `items.name` has a **FULLTEXT index** (used to speed up name search in MySQL; the API still keeps a `LIKE` fallback).

### Postman examples (copy-paste friendly)

Assume backend base URL:

- HTTP (dev): `http://localhost:8080`
- HTTPS (TLS): `https://localhost:8443`

Postman setup tips:

- Turn **Automatically follow redirects** on (default).
- Turn **Automatically persist cookies** on (Postman cookie jar). This project uses cookies (`XSRF-TOKEN` + `access_token`).

1. Get CSRF cookie (required before any write request):

- **Method**: `GET`
- **URL**: `{{API_BASE_URL}}/api/csrf-cookie`
- **Expected**: `204 No Content`
- **Result**: sets `XSRF-TOKEN` cookie

2. Login:

- **Method**: `POST`
- **URL**: `{{API_BASE_URL}}/api/auth/login`
- **Headers**:
  - `Accept: application/json`
  - `Content-Type: application/json`
  - `X-CSRF-TOKEN: {{XSRF_TOKEN}}` (use the value of the `XSRF-TOKEN` cookie)
- **Body (raw JSON)**:

```json
{
  "email": "test@example.com",
  "password": "password"
}
```

- **Expected**: `200 OK`, sets `access_token` HttpOnly cookie

3. Current user:

- **Method**: `GET`
- **URL**: `{{API_BASE_URL}}/api/auth/me`
- **Expected**: `200 OK`

4. Create an item:

- **Method**: `POST`
- **URL**: `{{API_BASE_URL}}/api/items`
- **Headers**:
  - `Accept: application/json`
  - `Content-Type: application/json`
  - `X-CSRF-TOKEN: {{XSRF_TOKEN}}`
- **Body (raw JSON)**:

```json
{
  "name": "iPhone 15",
  "sku": "SKU-IPHN-001",
  "description": "Phone",
  "price_cents": 99900,
  "is_active": true
}
```

- **Expected**: `201 Created`

5. List items (first page):

- **Method**: `GET`
- **URL**: `{{API_BASE_URL}}/api/items?per_page=20&q=phone`
- **Expected**: `200 OK`, returns `next_cursor`

6. List items (next page via cursor):

- **Method**: `GET`
- **URL**: `{{API_BASE_URL}}/api/items?per_page=20&q=phone&cursor={{NEXT_CURSOR}}`
- **Expected**: `200 OK`

7. Update an item:

- **Method**: `PUT`
- **URL**: `{{API_BASE_URL}}/api/items/{{ITEM_ID}}`
- **Headers**:
  - `Accept: application/json`
  - `Content-Type: application/json`
  - `X-CSRF-TOKEN: {{XSRF_TOKEN}}`
- **Body (raw JSON)**:

```json
{
  "name": "iPhone 15 Pro",
  "is_active": true
}
```

- **Expected**: `200 OK`

8. Delete an item:

- **Method**: `DELETE`
- **URL**: `{{API_BASE_URL}}/api/items/{{ITEM_ID}}`
- **Headers**:
  - `Accept: application/json`
  - `X-CSRF-TOKEN: {{XSRF_TOKEN}}`
- **Expected**: `204 No Content`

9. Logout:

- **Method**: `POST`
- **URL**: `{{API_BASE_URL}}/api/auth/logout`
- **Headers**:
  - `Accept: application/json`
  - `X-CSRF-TOKEN: {{XSRF_TOKEN}}`
- **Expected**: `200 OK`, clears `access_token` cookie

## Dev vs Production (What Changes)

### Backend

- **TLS**
  - **Dev**: HTTP only by default.
  - **Prod**: enable TLS via `backend/docker-compose.prod.yml` (for real prod, use a real certificate and consider HSTS).
- **Cookies / Sessions**
  - **Dev**: secure cookies are **disabled** by default (HTTP).
  - **Prod**: keep `Secure` enabled; tune `SameSite` (`lax/strict/none`) based on your domain setup.
- **CORS**
  - **Dev**: `FRONTEND_URL` is `http://localhost:5173`.
  - **Prod**: set `FRONTEND_URL` to your real frontend origin and keep `Access-Control-Allow-Credentials` enabled if you use cookies.
- **Debug**
  - **Dev**: `APP_DEBUG=true`.
  - **Prod**: `APP_DEBUG=false` and configure proper logging/monitoring.

### Frontend

- **Dev**: `npm run dev` serves the SPA over **HTTP** (see `frontend/vite.config.ts`) and proxies `/api`.
- **Prod**: serve the SPA over **HTTPS** (static host / CDN / reverse proxy) and point API base URL to your backend **HTTPS** domain.

## Simple Production Setup (Step-by-step)

This section is a **minimal, copy-paste friendly guide** to get a production-like setup.

Assume you have:

- **Frontend URL**: `https://app.example.com`
- **Backend URL (API)**: `https://api.example.com`

Replace `example.com` with your real domains.

### 1) Backend `.env` (Laravel)

Edit `backend/.env` and make sure at least these keys are set:

```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://api.example.com

FRONTEND_URL=https://app.example.com

COOKIE_SECURE=true
COOKIE_SAMESITE=lax
SESSION_SECURE_COOKIE=true
SESSION_SAME_SITE=lax

JWT_SECRET=CHANGE_ME_TO_A_LONG_RANDOM_STRING
```

Other DB / Redis settings can stay at their defaults or be adjusted to your infrastructure.

### 2) Frontend `.env.production` (Vite)

Create or edit `frontend/.env.production`:

```env
VITE_API_BASE_URL=https://api.example.com
```

This is the base URL used by the SPA to talk to your backend.

Then build the frontend:

```bash
cd frontend
npm install
npm run build
```

Serve `frontend/dist` with any static file host or behind your reverse proxy.

### 3) Docker ports for backend

The containers expose:

- HTTP: container port `80`
- HTTPS (TLS): container port `443`

You control **host ports** via environment variables when starting Docker Compose:

- For HTTP only:

```bash
APP_PORT=8080 docker compose -f backend/docker-compose.yml up -d --build
```

- For HTTPS (production-like TLS):

```bash
APP_TLS_PORT=8443 docker compose -f backend/docker-compose.yml -f backend/docker-compose.prod.yml up -d --build
```

In a real production setup you typically:

- Put a reverse proxy (Nginx, Traefik, etc.) in front,
- Terminate TLS there,
- And forward traffic to the `backend` container ports.

## CI/CD

This repository includes a GitHub Actions workflow in `.github/workflows/ci.yml`.
On every push / pull request to `main` or `master` it will:

- Run **Laravel Pint**, **PHPStan (Larastan)**, and **PHPUnit** for the backend.
- Run **ESLint**, **`vue-tsc` typecheck**, **Vitest**, and a **Vite build** for the frontend.
- Run **Playwright** E2E tests against the Dockerized backend + HTTPS dev frontend.

## Git hooks (Husky + lint-staged)

This repo supports local git hooks to keep commits clean and consistent.

- **One command before push**: from repo root run `npm run check` (frontend + backend gates).
- **pre-commit**: runs `lint-staged` (only checks files you staged)
  - Frontend: ESLint `--fix` + Prettier `--write` on staged files
  - Backend: `composer lint` (Pint)
- **pre-push**: runs a stronger gate
  - Frontend: `lint + format + typecheck + unit tests`
  - Backend: `composer lint + composer analyse + php artisan test`

Setup:

```bash
# 1) Initialize git (only needed once)
git init

# 2) Install root dev tools (husky + lint-staged)
npm install

# 3) Install hooks
npm run prepare
```

Notes:

- Hooks run locally; CI still runs on GitHub Actions.
- On Windows, Husky runs in a git bash shell (installed with Git for Windows).
- Cursor/IDE files are ignored by git via the root `.gitignore` (e.g. `.cursor/`).

## Commit message format (examples)

Use a simple Conventional Commits style:

```text
<type>(optional-scope): short summary
```

Rules:

- Keep the summary in **present tense** and **under ~72 chars**.
- Use a scope when it helps (e.g. `frontend`, `backend`, `auth`, `items`, `ci`).

Allowed types:

- `feat` (new user-visible feature)
- `fix` (bug fix)
- `refactor` (code change without behavior change)
- `test` (add/update tests)
- `docs` (documentation only)
- `chore` (tooling, deps, scripts)
- `ci` (CI workflow changes)

Examples:

```text
feat(items): add cursor pagination and search
fix(auth): return 401 for invalid token cookie
refactor(frontend): centralize API error message parsing
test(backend): cover items CRUD with pagination cursors
docs(readme): document all testing commands and prerequisites
chore(frontend): add eslint-config-prettier and typecheck script
ci: run frontend typecheck in GitHub Actions
```

## Notes

- Cookies default to `Secure` (HTTPS only) in docker-compose; you can override via environment variables if needed.
- This repository intentionally keeps a **single source of truth** for documentation in this `README.md`.
