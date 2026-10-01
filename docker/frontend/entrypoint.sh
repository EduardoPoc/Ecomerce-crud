#!/bin/sh
set -eu

current_hash="$(sha256sum package-lock.json | cut -d ' ' -f 1)"
installed_hash="$(cat node_modules/.docker-lock-hash 2>/dev/null || true)"

if [ "$current_hash" != "$installed_hash" ]; then
  npm ci
  printf '%s\n' "$current_hash" > node_modules/.docker-lock-hash
fi

exec "$@"
