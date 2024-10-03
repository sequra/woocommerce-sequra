<?php
/**
 * This file is part of the Sequra package.
 *
 * Call this script in your _manually_load_plugin function to load WooCommerce.
 *
 * @package Sequra
 */

/**
 * Load WooCommerce and run necessary installation steps.
 */
require dirname( dirname( __DIR__ ) ) . '/woocommerce/woocommerce.php';
WC_Install::create_tables();
