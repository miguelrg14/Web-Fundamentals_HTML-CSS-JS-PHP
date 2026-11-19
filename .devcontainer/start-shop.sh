#!/usr/bin/env bash
# Starts the shop in the background and waits until it answers, so the browser opens on a working page.
set -euo pipefail
cd "$(dirname "$0")/../furniture-shop"

# Docker starts together with the codespace; give it a moment.
for _ in $(seq 1 30); do docker info > /dev/null 2>&1 && break; sleep 2; done

echo "Starting the furniture shop (the first time takes a couple of minutes)..."
docker compose up --build --detach --wait --wait-timeout 300

for _ in $(seq 1 60); do
    if curl -fsS -o /dev/null http://localhost:8080/login.php; then
        echo "The shop is running on port 8080. Open the Ports tab if the browser didn't open by itself."
        exit 0
    fi
    sleep 2
done
echo "The shop didn't answer on port 8080. See: docker compose logs" >&2
exit 1
