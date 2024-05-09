#!/bin/bash
# ARGS in docker-compose cannot be dynamically set.
# This script will generate the docker-compose.yml file with the correct values from the .env files.
set -o allexport
source ./.env

if [ -f override.env ]; then
    source ./override.env
fi
set +o allexport

envsubst < docker-compose-template.yml > docker-compose.yml

# Run composer install
docker run --rm -v "$(pwd)"/sequra:/app -w /app composer:latest composer install

# TODO: Run npm install?

docker compose up -d --build

echo "Waiting for installation to complete..."

retry=30
timeout=1
start=$(date +%s)
while [ $(($(date +%s) - $start)) -lt $retry ]; do
    if docker compose exec web ls /var/www/html/.post-install-complete > /dev/null 2>&1; then
        seconds=$(($(date +%s) - $start))
        echo "✅ Done in ${seconds} seconds. Access your site at ${WP_URL}"
        exit 0
    fi
    sleep $timeout
done
echo "❌ Timeout after ${retry} seconds"
exit 1