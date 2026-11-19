#!/usr/bin/env bash
# Runs once when the codespace is created: installs MariaDB (a MySQL-compatible server),
# the PHP MySQL driver and the project's tools.
set -euo pipefail
cd "$(dirname "$0")/.."

sudo apt-get update -qq
sudo DEBIAN_FRONTEND=noninteractive apt-get install -y -qq mariadb-server > /dev/null

if ! php -m | grep -qi '^pdo_mysql$'; then
    sudo docker-php-ext-install pdo_mysql > /dev/null
fi

npm ci --no-audit --no-fund
(cd furniture-shop && composer install --no-interaction --no-progress)
