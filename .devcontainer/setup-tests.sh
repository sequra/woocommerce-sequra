#!/bin/bash
export XDEBUG_MODE=off
VERSION='latest'
if [ -n "$1" ]; then
    VERSION=$1
fi

rm -rf \
/var/www/html/wp-content/plugins/_sequra/.circleci \
/var/www/html/wp-content/plugins/_sequra/bin \
/var/www/html/wp-content/plugins/_sequra/tests/bootstrap.php

mv /var/www/html/wp-content/plugins/_sequra/phpunit.xml.dist /var/www/html/wp-content/plugins/_sequra/phpunit.xml.dist.backup
mv /var/www/html/wp-content/plugins/_sequra/.phpcs.xml.dist /var/www/html/wp-content/plugins/_sequra/.phpcs.xml.dist.backup

echo "s" | wp scaffold plugin-tests _sequra --allow-root

mv -f /var/www/html/wp-content/plugins/_sequra/phpunit.xml.dist.backup /var/www/html/wp-content/plugins/_sequra/phpunit.xml.dist
mv -f /var/www/html/wp-content/plugins/_sequra/.phpcs.xml.dist.backup /var/www/html/wp-content/plugins/_sequra/.phpcs.xml.dist
# Add WooCommerce to the test suite
sed -i '/require dirname( dirname( __FILE__ ) ) . \x27\/sequra.php\x27;/c\require \x27load-wc.php\x27;\nrequire dirname( dirname( __FILE__ ) ) . \x27/sequra.php\x27;' /var/www/html/wp-content/plugins/_sequra/tests/bootstrap.php

/var/www/html/wp-content/plugins/_sequra/bin/install-wp-tests.sh wordpress_test root "${MARIADB_ROOT_PASSWORD}" "${WORDPRESS_DB_HOST}" "${VERSION}"

rm -f /var/www/html/wp-content/plugins/_sequra/tests/test-sample.php