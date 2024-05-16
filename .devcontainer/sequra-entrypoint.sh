#!/bin/bash

# Check if need to install WordPress
if [ ! -f /var/www/html/.post-install-complete ]; then
    
    cd /var/www/html
    export XDEBUG_MODE=off

    wait_for() {
        local retry=60
        local timeout=1
        local start=$(date +%s)

        while [ $(($(date +%s) - $start)) -lt $retry ]; do
            if "$@" > /dev/null 2>&1; then
                return 0
            fi
            sleep $timeout
        done
        return 1
    }
    
    # Wait for database to be ready and then create it.
    DB_PORT=3306
    result=$(wait_for nc -z "${WORDPRESS_DB_HOST}" "${DB_PORT}")
    if [ "$result" == "1" ]; then
        echo "❌ ${WORDPRESS_DB_HOST}:${DB_PORT} is not available"
        exit 1
    fi

    wp db create --allow-root \
    --dbuser="${WORDPRESS_DB_USER}" \
    --dbpass="${WORDPRESS_DB_PASSWORD}"

    # Run WordPress installation wizard
    wp core install --allow-root \
    --url="${WP_URL}" \
    --title="${WP_TITLE}" \
    --admin_user="${WP_ADMIN_USER}" \
    --admin_password="${WP_ADMIN_PASSWORD}" \
    --admin_email="${WP_ADMIN_EMAIL}" \
    --locale="${WP_LOCALE}" \
    --skip-email

    get_pkg_and_version() {
        IFS=':' read -r pkg version <<< "$1"
        if [ -n "${version}" ]; then
            version=" --version=${version}"
        fi
        echo "${pkg}${version}"
    }

    # Install plugins
    wp plugin deactivate --allow-root --all

    if [ -n "${WP_PLUGINS}" ]; then
        IFS=',' read -ra plugins <<< "${WP_PLUGINS}"
        for plugin in "${plugins[@]}"; do
            plugin_version=$(get_pkg_and_version "${plugin}")
            wp plugin install --allow-root "${plugin_version}" --activate
        done
    fi

    # Check if WooCommerce is installed and run the setup script
    if ! wp plugin is-active --allow-root woocommerce; then
        echo "❌ WooCommerce is not installed"
        exit 1
    fi

    # Install theme
    if [ -n "${WP_THEME}" ]; then
        theme_version=$(get_pkg_and_version "${WP_THEME}")
        wp theme install --allow-root "${theme_version}" --activate
    fi

    wp plugin install --allow-root wordpress-importer --activate
    wp import --allow-root wp-content/plugins/woocommerce/sample-data/sample_products.xml --authors=create

    # Setup WooCommerce options
    wp option update --allow-root woocommerce_store_address "${WC_STORE_ADDRESS}"
    wp option update --allow-root woocommerce_store_address_2 "${WC_STORE_ADDRESS_2}"
    wp option update --allow-root woocommerce_store_city "${WC_STORE_CITY}"
    wp option update --allow-root woocommerce_default_country "${WC_DEFAULT_COUNTRY}"
    wp option update --allow-root woocommerce_store_postcode "${WC_STORE_POSTCODE}"
    wp option update --allow-root woocommerce_currency "${WC_CURRENCY}"
    wp option update --allow-root woocommerce_currency_pos "${WC_CURRENCY_POSITION}"
    wp option update --allow-root woocommerce_price_thousand_sep "${WC_PRICE_THOUSAND_SEPARATOR}"
    wp option update --allow-root woocommerce_price_decimal_sep "${WC_PRICE_DECIMAL_SEPARATOR}"

    wp wc shipping_zone_method create 0 --method_id="${WC_SHIPPING_ZONE_METHOD_ID}" --settings="${WC_SHIPPING_ZONE_METHOD_SETTINGS}" --user=admin --allow-root
    
    wp plugin activate --allow-root sequra
    # wp option set woocommerce_sequra_settings --format=json '{"enabled":"yes","title":"Fraccionar pago","merchantref":"dummy","user":"dummy","password":"ZqbjrN6bhPYVIyram3wcuQgHUmP1C4","assets_secret":"ADc3ZdOLh4","enable_for_virtual":"no","default_service_end_date":"P1Y","allow_payment_delay":"no","allow_registration_items":"no","env":"1","test_ips":"","debug":"yes","active_methods_info":"","communication_fields":"","price_css_sel":".summary .price&gt;.amount,.summary .price ins .amount"}'
    # curl "http://127.0.0.1/?post_type=product&RESET_SEQURA_ACTIVE_METHODS=true" > /dev/null

    wp plugin deactivate --allow-root wordpress-importer --uninstall
    wp plugin uninstall --allow-root $(wp plugin list --allow-root --status=inactive --field=name)

    touch /var/www/html/.post-install-complete
    
    chown -R www-data:www-data /var/www/html
fi
echo "✅ seQura plugin installed and configured."