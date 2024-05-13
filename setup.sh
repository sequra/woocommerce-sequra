#!/bin/bash
# ARGS in docker-compose cannot be dynamically set.
# This script will generate the docker-compose.yml file with the correct values from the .env files.

if [ ! -f .env ]; then
    cp .env.sample .env
fi

# set -o allexport
# source .env
# set +o allexport

# envsubst < docker-compose-template.yml > docker-compose.yml

# Run composer install
docker run --rm -v "$(pwd)"/sequra:/app -w /app composer:latest composer install

# TODO: Run npm install?

docker compose up -d --build

echo "Waiting for installation to complete..."

retry=60
timeout=1
start=$(date +%s)
while [ $(($(date +%s) - $start)) -lt $retry ]; do
    if docker compose exec web ls /var/www/html/.post-install-complete > /dev/null 2>&1; then
        seconds=$(($(date +%s) - $start))
        echo "✅ Done in ${seconds} seconds."
        exit 0
    fi
    sleep $timeout
done
echo "❌ Timeout after ${retry} seconds"
exit 1