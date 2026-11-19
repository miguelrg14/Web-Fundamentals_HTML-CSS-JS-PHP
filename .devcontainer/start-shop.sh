#!/usr/bin/env bash
# Runs every time the codespace starts: starts MariaDB, creates the shop's database the first
# time, and serves the shop with PHP's built-in server on port 8080.
set -euo pipefail
cd "$(dirname "$0")/../furniture-shop"

export DB_HOST=127.0.0.1 DB_NAME=furniture_shop DB_USER=shop DB_PASSWORD=shop-password

sudo service mariadb start > /dev/null

if ! sudo mariadb -e "SELECT 1 FROM furniture_shop.products LIMIT 1" > /dev/null 2>&1; then
    echo "Creating the shop's database..."
    sudo mariadb -e "CREATE DATABASE IF NOT EXISTS furniture_shop;
        CREATE USER IF NOT EXISTS 'shop'@'localhost' IDENTIFIED BY '${DB_PASSWORD}';
        CREATE USER IF NOT EXISTS 'shop'@'127.0.0.1' IDENTIFIED BY '${DB_PASSWORD}';
        GRANT ALL ON furniture_shop.* TO 'shop'@'localhost';
        GRANT ALL ON furniture_shop.* TO 'shop'@'127.0.0.1';"
    sudo mariadb furniture_shop < database/schema.sql
fi

if ! curl -fsS -o /dev/null http://localhost:8080/login.php 2>/dev/null; then
    # setsid + nohup keep the server running after this script ends.
    setsid nohup php -S 0.0.0.0:8080 -t public > /tmp/furniture-shop.log 2>&1 < /dev/null &
fi

for _ in $(seq 1 30); do
    if curl -fsS -o /dev/null http://localhost:8080/login.php; then
        echo "The furniture shop is running on port 8080 (log: /tmp/furniture-shop.log)."
        echo "If the browser didn't open by itself, open the Ports tab and click the globe icon next to 8080."
        exit 0
    fi
    sleep 1
done
echo "The shop didn't start. See /tmp/furniture-shop.log" >&2
exit 1
