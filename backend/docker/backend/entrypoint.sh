#!/usr/bin/env sh
set -eu

# Ensure writable dirs for Laravel (Windows bind mounts often need permissive perms in dev)
mkdir -p storage/logs bootstrap/cache
chmod -R 777 storage bootstrap/cache || true

exec "$@"

