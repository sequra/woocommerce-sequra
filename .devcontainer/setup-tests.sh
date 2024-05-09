#!/bin/bash

VERSION='latest'
if [ -n "$1" ]; then
    VERSION=$1
fi

wp scaffold plugin-tests sequra --allow-root --force
/var/www/html/wp-content/plugins/sequra/bin/install-wp-tests.sh wordpress_test root "${MARIADB_ROOT_PASSWORD}" "${WORDPRESS_DB_HOST}" "${VERSION}"