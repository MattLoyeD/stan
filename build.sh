#!/usr/bin/env bash
set -euo pipefail

PLATFORM="${1:-linux-amd64}"

echo "==> Building Stan for ${PLATFORM}..."

docker build \
    --pull \
    --platform "linux/amd64" \
    -f static-build.Dockerfile \
    -t stan-builder .

CONTAINER_ID=$(docker create stan-builder)
docker cp "${CONTAINER_ID}:/go/src/app/dist/frankenphp-linux-x86_64" "./stan-${PLATFORM}"
docker rm "${CONTAINER_ID}"

chmod +x "./stan-${PLATFORM}"
echo "==> Built: ./stan-${PLATFORM}"
echo "==> Run:   ./stan-${PLATFORM} php-cli artisan stan:start"
