# !/bin/sh
# This script copies the source files from within the container to the host.
# Usefull for debugging and development.

# get id of the container
container_id=$(docker ps | grep woocommerce-sequra-web | head -n 1 | awk '{print $1}')

if [ -z "$container_id" ]; then
    echo "Container not found! Skipping copy."
else
    echo "Copying source files from container $container_id to host..."
    # wp-includes
    docker cp "$container_id":/var/www/html/wp-includes ./.devcontainer
    # wp-admin
    docker cp "$container_id":/var/www/html/wp-admin ./.devcontainer
    # woocommerce plugin
    mkdir -p ./.devcontainer/wp-content/plugins
    docker cp "$container_id":/var/www/html/wp-content/plugins/woocommerce ./.devcontainer/wp-content/plugins
    
    echo "Done!"
fi