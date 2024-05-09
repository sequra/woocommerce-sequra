#!/bin/bash
# docker run --rm --interactive --tty \
#   --volume $PWD/sequra:/app \
#   composer install
# docker-compose up -d
# docker-compose exec -u www-data web /bin/bash -c "/tmp/setup_woocommerce.sh"

# ARGS in docker-compose cannot be dynamically set.
# This script will generate the docker-compose.yml file with the correct values from the .env files.
set -o allexport
source ./.env

if [ -f override.env ]; then
    source ./override.env
fi
set +o allexport

envsubst < docker-compose-template.yml > docker-compose.yml

# TODO: Run composer install
# TODO: Run npm install?

docker compose up -d --build

# retry=20
# timeout=1
# start=$(date +%s)
# while [ $(($(date +%s) - $start)) -lt $retry ]; do
# if docker compose exec wp ls /var/www/html/.post-install-complete  > /dev/null 2>&1  ; then
#     echo "✅ Post install done!"
#     exit 0
# fi
# echo "⏳ Waiting for post install to complete..."
# sleep $timeout
# done
# echo "❌ Post install timeout after $retry seconds"
# exit 1