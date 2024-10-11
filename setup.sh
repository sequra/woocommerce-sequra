#!/bin/bash
if [ ! -f .env ]; then
    cp .env.sample .env
fi

install=1

# Parse arguments:
# --install=0: Skip installation of dependencies
# --ngrok-token=YOUR_NGROK_TOKEN: Override the ngrok token in .env
while [[ "$#" -gt 0 ]]; do
    if [ "$1" == "--install=0" ]; then
        install=0
    elif [[ "$1" == --ngrok-token=* ]]; then
        ngrok_token="${1#*=}"
        sed -i.bak "s|NGROK_AUTHTOKEN=.*|NGROK_AUTHTOKEN=$ngrok_token|" .env
        rm .env.bak
    fi
    shift
done

set -o allexport
source .env
set +o allexport

if [ -z "$NGROK_AUTHTOKEN" ]; then
    echo "❌ Please enter your ngrok auth token under the key NGROK_AUTHTOKEN in your .env file (get it from https://dashboard.ngrok.com/)"
    exit 1
fi

if [ $install -eq 1 ]; then
    ./bin/composer install
    ./bin/npm install
    ./bin/npm run build
else
    echo "Skipping installation of dependencies."   
fi

docker compose up -d --build

echo "🚀 Waiting for installation to complete..."

retry=120
timeout=1
start=$(date +%s)
while [ $(($(date +%s) - $start)) -lt $retry ]; do
    if docker compose exec web ls /var/www/html/.post-install-complete > /dev/null 2>&1; then
        seconds=$(($(date +%s) - $start))
        echo "✅ Done in ${seconds} seconds."
        echo "🔗 Access seQura settings at ${WP_URL}/wp-admin/admin.php?page=wc-settings&tab=checkout&section=sequra"
        echo "🔗 Or browse products at ${WP_URL}/shop/"
        echo "User: $WP_ADMIN_USER"
        echo "Password: $WP_ADMIN_PASSWORD"
        exit 0
    elif docker compose exec web ls /var/www/html/.post-install-failed > /dev/null 2>&1; then
        seconds=$(($(date +%s) - $start))
        echo "❌ Installation failed after ${seconds} seconds."
        exit 1
    fi
    sleep $timeout
done
echo "❌ Timeout after ${retry} seconds"
exit 1