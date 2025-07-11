#!/bin/bash
if [ ! -f .env ]; then
    cp .env.sample .env
fi

install=0
ngrok=0

# Parse arguments:
# --install: Installation of dependencies
# --ngrok: Use ngrok to expose the site
# --ngrok-token=YOUR_NGROK_TOKEN: Override the ngrok token in .env
while [[ "$#" -gt 0 ]]; do
    if [ "$1" == "--install" ]; then
        install=1
    elif [ "$1" == "--ngrok" ]; then
        ngrok=1
    elif [[ "$1" == --ngrok-token=* ]]; then
        ngrok_token="${1#*=}"
        sed -i.bak "s|NGROK_AUTHTOKEN=.*|NGROK_AUTHTOKEN=$ngrok_token|" .env
        rm .env.bak
    fi
    shift
done

# Reset PUBLIC_URL inside .env
sed -i.bak "s|PUBLIC_URL=.*|PUBLIC_URL=|" .env
rm .env.bak

set -o allexport
source .env
set +o allexport

if [ $ngrok -eq 1 ]; then

    if [ -z "$NGROK_AUTHTOKEN" ]; then
        echo "❌ Please set NGROK_AUTHTOKEN with your ngrok auth token in your .env file (get it from https://dashboard.ngrok.com/)"
        exit 1
    fi
    
    echo "🚀 Starting ngrok..."

    docker run -d -e NGROK_AUTHTOKEN=$NGROK_AUTHTOKEN \
        -p $NGROK_PORT:4040 \
        --name $NGROK_CONTAINER_NAME \
        --add-host=host:host-gateway \
        ngrok/ngrok:alpine \
        http host:$WP_HTTP_PORT
    
    WP_URL=""
    retry=10
    timeout=1
    start=$(date +%s)
    while [ -z "$WP_URL" ]; do
        sleep $timeout
        WP_URL=$(curl -s http://localhost:$NGROK_PORT/api/tunnels | grep -o '"public_url":"[^"]*"' | sed 's/"public_url":"\(.*\)"/\1/' | head -n 1)
        if [ $(($(date +%s) - $start)) -gt $retry ]; then
            docker rm -f $NGROK_CONTAINER_NAME || true
            echo "❌ Error getting public url from ngrok after ${retry} seconds"
            exit 1
        fi
    done

    # Overwrite PUBLIC_URL inside .env
    sed -i.bak "s|PUBLIC_URL=.*|PUBLIC_URL=$WP_URL|" .env
    rm .env.bak

    echo "✅ Ngrok started. Public URL: $WP_URL"
fi

if [ $install -eq 1 ]; then
    ./bin/composer install
    ./bin/npm install
    ./bin/npm run build
else
    echo "Skipping installation of dependencies."   
fi

docker compose up -d --build || exit 1

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