#!/bin/bash
if [ ! -f .env ]; then
    cp .env.sample .env
fi

install=0
ngrok=0
cloudflared=0

# Parse arguments:
# --install: Installation of dependencies
# --ngrok: Use ngrok to expose the site
# --ngrok-token=YOUR_NGROK_TOKEN: Override the ngrok token in .env
# --cloudflared: Use cloudflared to expose the site
# --cloudflared-token=YOUR_CLOUDFLARED_TOKEN: Override the cloudflared token in .env
while [[ "$#" -gt 0 ]]; do
    if [ "$1" == "--install" ]; then
        install=1
    elif [ "$1" == "--ngrok" ]; then
        ngrok=1
    elif [ "$1" == "--cloudflared" ]; then
        cloudflared=1
    elif [[ "$1" == --ngrok-token=* ]]; then
        ngrok_token="${1#*=}"
        sed -i.bak "s|NGROK_AUTHTOKEN=.*|NGROK_AUTHTOKEN=$ngrok_token|" .env
        rm .env.bak
    elif [[ "$1" == --cloudflared-token=* ]]; then
        cloudflared_token="${1#*=}"
        sed -i.bak "s|CLOUDFLARED_TUNNEL_TOKEN=.*|CLOUDFLARED_TUNNEL_TOKEN=$cloudflared_token|" .env
        rm .env.bak
    fi
    shift
done

# Extract WP_URL from .env and set PUBLIC_URL to that value
WP_URL=$(grep '^WP_URL=' .env | cut -d'=' -f2)
sed -i.bak "s|PUBLIC_URL=.*|PUBLIC_URL=$WP_URL|" .env
rm .env.bak

set -o allexport
source .env
set +o allexport

if [ $ngrok -eq 1 ]; then

    if [ -z "$NGROK_AUTHTOKEN" ]; then
        echo "❌ Please set NGROK_AUTHTOKEN with your ngrok auth token in your .env file (get it from https://dashboard.ngrok.com/)"
        exit 1
    fi
    
    echo "🔗 Starting ngrok tunnel..."

    if [ -z "$NGROK_CONTAINER_NAME" ]; then
        NGROK_CONTAINER_NAME=woocommerce-sequra-ngrok
    fi

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
elif [ $cloudflared -eq 1 ]; then
    
    if [ -z "$CLOUDFLARED_TUNNEL_TOKEN" ]; then
        echo "❌ Please set CLOUDFLARED_TUNNEL_TOKEN with your cloudflared tunnel token in your .env file (get it from https://developers.cloudflare.com/cloudflare-one/networks/connectors/cloudflare-tunnel/get-started/create-remote-tunnel/)"
        exit 1
    fi

    if [ -z "$CLOUDFLARED_TUNNEL_URL" ]; then
        echo "❌ Please set CLOUDFLARED_TUNNEL_URL with your cloudflared tunnel URL in your .env file"
        exit 1
    fi

    echo "🔗 Starting cloudflared tunnel..."

    if [ -z "$CLOUDFLARED_CONTAINER_NAME" ]; then
        CLOUDFLARED_CONTAINER_NAME=woocommerce-sequra-cloudflared
    fi

    docker run -d \
        --name $CLOUDFLARED_CONTAINER_NAME \
        --add-host=host:host-gateway \
        cloudflare/cloudflared:latest \
        tunnel --no-autoupdate run --token $CLOUDFLARED_TUNNEL_TOKEN

     # Overwrite PUBLIC_URL inside .env
    WP_URL=$CLOUDFLARED_TUNNEL_URL
    sed -i.bak "s|PUBLIC_URL=.*|PUBLIC_URL=$WP_URL|" .env
    rm .env.bak

    echo "✅ Cloudflared started. Public URL: $WP_URL"
fi

if [ $install -eq 1 ]; then
    ./bin/composer install
    ./bin/npm install
    ./bin/npm run build
else
    echo "Skipping installation of dependencies."   
fi


# Log in to GitHub Container Registry
if [ -z "$GITHUB_TOKEN" ]; then
    echo "❌ Please, set an environment variable named GITHUB_TOKEN with your GitHub token. You can define it in your .env file"
    exit 1
fi

echo "🔐 Logging in to the GitHub Container Registry..."
echo $GITHUB_TOKEN | docker login ghcr.io -u sequra --password-stdin || (echo "❌ Login failed" && exit 1)

IMAGE_EXISTS=$(docker images -q ghcr.io/sequra/woocommerce-sequra:$WP_TAG)
if [ -z "$IMAGE_EXISTS" ]; then
    echo "🔍 Checking if image ghcr.io/sequra/woocommerce-sequra:$WP_TAG exists in the GitHub Container Registry..."
    if docker pull ghcr.io/sequra/woocommerce-sequra:$WP_TAG > /dev/null 2>&1; then
        echo "🐳 Image ghcr.io/sequra/woocommerce-sequra:$WP_TAG pulled from the registry."
    else
        echo "🐳 Image ghcr.io/sequra/woocommerce-sequra:$WP_TAG not found in the registry. It will be built now..."
        
        BASEDIR="$(dirname $(realpath $0))"
        $BASEDIR/docker/build-image.sh --wp=$WP_TAG || (echo "❌ Docker image build failed" && exit 1)
    fi
fi

docker compose up -d || exit 1

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
        $BASEDIR/teardown.sh
        exit 1
    fi
    sleep $timeout
done
echo "❌ Timeout after ${retry} seconds"
$BASEDIR/teardown.sh
exit 1