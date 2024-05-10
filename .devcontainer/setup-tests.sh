#!/bin/bash

VERSION='latest'
if [ -n "$1" ]; then
    VERSION=$1
fi

echo "s" | wp scaffold plugin-tests sequra --allow-root
/var/www/html/wp-content/plugins/sequra/bin/install-wp-tests.sh wordpress_test root "${MARIADB_ROOT_PASSWORD}" "${WORDPRESS_DB_HOST}" "${VERSION}"
rm -f /var/www/html/wp-content/plugins/sequra/tests/test-sample.php