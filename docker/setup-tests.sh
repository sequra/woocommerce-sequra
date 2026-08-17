#!/bin/bash
export XDEBUG_MODE=off
# Default to the WordPress this container runs, so the suite tests the version under test
# instead of whatever release happens to be latest.
VERSION="${1:-$(wp core version --allow-root 2>/dev/null)}"
VERSION="${VERSION:-latest}"

# install-wp-tests.sh reuses any core it finds, which would silently keep testing the
# previous version after WP_TAG changes.
if [ "$VERSION" != 'latest' ] && [ -f /tmp/wordpress/wp-includes/version.php ]; then
    INSTALLED=$(sed -n "s/^\$wp_version = '\([^']*\)'.*/\1/p" /tmp/wordpress/wp-includes/version.php)
    if [ "$INSTALLED" != "$VERSION" ]; then
        rm -rf /tmp/wordpress /tmp/wordpress-tests-lib
    fi
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