# Production env files (English)

## Backend (Laravel)

- Copy `backend/.env.production.example` to `backend/.env`
- Fill these required values:
  - `APP_KEY` (Laravel app key)
  - `JWT_SECRET` (random long secret)
  - `APP_URL` and `FRONTEND_URL` (your public URL)
  - `DB_*` (must match your MySQL settings)

Generate secrets on the server:

```bash
# APP_KEY
php -r "echo 'APP_KEY=base64:'.base64_encode(random_bytes(32)).PHP_EOL;"

# JWT_SECRET
openssl rand -base64 48
```

## Frontend (Vite)

If you use host nginx to proxy `/api` to the backend (same domain), keep:

- `VITE_API_BASE_URL=` (empty)

Then build:

```bash
cd frontend
npm ci
npm run build
```

