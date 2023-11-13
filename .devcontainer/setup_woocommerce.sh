#!/bin/bash
echo "Installing WooCommerce"
echo "Wait 5 seconds to have the db availbale"
pwd
sleep 5

export XDEBUG_MODE=off
wp core install --url="${WP_URL}" --title="${WP_TITLE}" --admin_user="${WP_ADMIN_USER}" --admin_password="${WP_ADMIN_PASSWORD}" --admin_email="${WP_ADMIN_EMAIL}"
wp plugin install wordpress-importer --activate
wp plugin install woocommerce --activate
wp import wp-content/plugins/woocommerce/sample-data/sample_products.xml --authors=create
wp plugin activate sequra

wp option update woocommerce_store_address 'No enviar'
wp option update woocommerce_store_address_2 'No enviar'
wp option update woocommerce_store_city	Barcelona
wp option update woocommerce_default_country 'ES:B'
wp option update woocommerce_store_postcode	08010
wp option update woocommerce_currency EUR
wp option update woocommerce_currency_pos right_space
wp option update woocommerce_price_thousand_sep .
wp option update woocommerce_price_decimal_sep ,

wp option set woocommerce_sequra_settings --format=json '{"enabled":"yes","title":"Fraccionar pago","merchantref":"dummy","user":"dummy","password":"ZqbjrN6bhPYVIyram3wcuQgHUmP1C4","assets_secret":"ADc3ZdOLh4","enable_for_virtual":"no","default_service_end_date":"P1Y","allow_payment_delay":"no","allow_registration_items":"no","env":"1","test_ips":"","debug":"yes","active_methods_info":"","communication_fields":"","price_css_sel":".summary .price&gt;.amount,.summary .price ins .amount"}'
wp wc shipping_zone_method create 0 --method_id="flat_rate" --settings='{"cost":"'10.00'"}' --user=admin
curl "http://127.0.0.1/?post_type=product&RESET_SEQURA_ACTIVE_METHODS=true" > /dev/null