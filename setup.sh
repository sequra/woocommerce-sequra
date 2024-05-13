#!/bin/bash
if [ ! -f .env ]; then
    cp .env.sample .env
fi

# Variables
install=1  # Valor por defecto

# Parse arguments:
# --install=0: Skip installation of dependencies
while [[ "$#" -gt 0 ]]; do
    if [ "$1" == "--install=0" ]; then
        install=0
        break
    fi
    shift
done

if [ $install -eq 1 ]; then
    ./bin/composer install
else
    echo "Skipping installation of dependencies."   
fi

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