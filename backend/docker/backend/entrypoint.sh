#!/usr/bin/env sh
set -eu

ENABLE_TLS="${ENABLE_TLS:-0}"
CERT_DIR="/etc/nginx/certs"
CRT="$CERT_DIR/localhost.crt"
KEY="$CERT_DIR/localhost.key"
HTTP_DIR="/etc/nginx/http.d"

# Ensure writable dirs for Laravel (Windows bind mounts often need permissive perms in dev)
mkdir -p storage/logs bootstrap/cache "$CERT_DIR" /run/nginx
chmod -R 777 storage bootstrap/cache || true

mkdir -p "$HTTP_DIR"

# Configure Nginx based on TLS flag
if [ "$ENABLE_TLS" = "1" ] || [ "$ENABLE_TLS" = "true" ]; then
  cp -f /etc/nginx/templates/default.tls.conf "$HTTP_DIR/default.conf"

  if [ ! -f "$CRT" ] || [ ! -f "$KEY" ]; then
    echo "[backend] Generating local self-signed TLS cert..."
    openssl req -x509 -nodes -newkey rsa:2048 \
      -keyout "$KEY" \
      -out "$CRT" \
      -days 3650 \
      -subj "/CN=localhost" \
      -addext "subjectAltName=DNS:localhost,IP:127.0.0.1"
  fi
else
  cp -f /etc/nginx/templates/default.dev.conf "$HTTP_DIR/default.conf"
fi

exec "$@"

