=== Pasarela de pago para seQura ===
Contributors: sequradev
Tags: woocommerce, payment, gateway, BNPL, installments, buy now pay later
Requires at least: 5.9
Tested up to: 6.4
Stable tag: 2.0.8
Requires PHP: 7.3
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

== Description ==

seQura is the flexible payment platform that will help your business improve conversion and recurrence. 
The easiest, safest, and quickest way for your customers to pay on installments.

+6.000 e-commerce and +1.5 million delight shoppers already use seQura. Are you still thinking about it?

This WooCommerce plugin allows you to make payments with [seQura](https://sequra.es).



= Benefits for merchants =

> Flexible payment solutions adapted to your business.

Widest flexible payment solutions in the market:

* Buy now pay later 
* Pay in 3, no interest
* Installments, up to 24 months
* Flexi, combines interest-free bnpl with long-term financing in a single purchase experience

Your customers in good hands:

* Cost transparency and clarity
* Local support teams to deliver the best shopper experience
* Secure data, we don’t share your data with anyone or use your information to sell our own or third-party products 


Obsessed with conversion and recurrence

* We adapt to your business, solutions for every sector, and buyer profile
* The highest acceptance rate in Southern Europe thanks to our own risk algorithm, created and optimized for the local market
* Instant approval. A frictionless credit-purchase experience, buy-in seconds without document uploads
* seQura marketing collateral to support your campaigns

= Benefits for customers =

* Widest range of flexible payment solutions available on the market, up to 4 different solutions to pay as you want.
* Access to credit with no paperwork, just complete 5 fields to be instantly approved
* Security and privacy, we do not sell your personal data to third parties nor share with other companies


== Frequently Asked Questions ==

= I can't install the plugin, the plugin is displayed incorrectly =

Please temporarily enable the [WordPress Debug Mode](https://wordpress.org/documentation/article/debugging-in-wordpress/). Edit your `wp-config.php` and set the constants `WP_DEBUG` and `WP_DEBUG_LOG` to `true` and try
it again. When the plugin triggers an error, WordPress will log the error to the log file `/wp-content/debug.log`. Please check this file for errors. When done, don't forget to turn off
the WordPress debug mode by setting the two constants `WP_DEBUG` and `WP_DEBUG_LOG` back to `false`.

= I get a white screen when opening ... =

Most of the time a white screen means a PHP error. Because PHP won't show error messages on default for security reasons, the page is white. Please turn on the WordPress Debug Mode to turn on PHP error messages (see the previous answer).

== Screenshots ==
1. Líder en pagos flexibles para la conversión y recurrencia
2. Ofrece a tus clientes 4 métodos de pago flexibles
3. Impulsa la rentabilidad de tu e-commerce
4. seQura

== Installation ==

= Minimum Requirements =

* PHP version 7.2 or greater
* PHP extensions enabled: cURL, JSON
* WordPress 5.9 or greater
* WooCommerce 6.0 or greater
* Merchant account at seQura, [sign up here](https://share.hsforms.com/1J2S1J2NPTi-pZERcgJPOVw1c4yg)

= Automatic installation =

1. Install the plugin via Plugins -> New plugin. Search for 'seQura'.
2. Activate the 'seQura' plugin through the 'Plugins' menu in WordPress
3. Set your seQura credentials at WooCommerce -> Settings -> payments -> seQura
4. You're done, the seQura payment methods should be visible in the checkout of your WooCommerce.

= Manual installation =

1. Unpack the download package
2. Upload the directory `sequra` to the `/wp-content/plugins/` directory
3. Activate the 'seQura' plugin through the 'Plugins' menu in WordPress
4. Set your seQura credentials at WooCommerce -> Settings -> payments -> seQura
5. You're done, the seQura payment methods should be visible in the checkout of your WooCommerce.

Please contact sat@sequra.com if you need help installing the seQura WooCommerce plugin.

= Updating =

Automatic updates should work like a charm; as always though, ensure you backup your site just in case.

Contributors:
== Changelog ==
= 2.0.8	=
* FIX: Information about plugin version
* IMP: Avoid warning in sequratemporder class
= 2.0.7 =
* FIX rounding amount total for the +info popup in the checkout
= 2.0.6 =
* FIX javascript warning due to changes in the integrations assets
* FIX php warning due to sequra/php-client package upgrade
* IMP Removed all referencces to deprecated `without_tax` values.
* FIX Add dependance on the "enabled in product" admin option to "Simulator params" option.
= 2.0.5 =
* FIX: Copy billing address to shiping address when ship_to_different_address is not set
* Update: compatibility till WooCommerce 8.2
= 2.0.4 =
* Added: HPOS Compatibility declaration
* Update: Compatibility with WooCommerce 8.0
= 2.0.3 =
* Fix: Delivery report generation when order had multiple discounts
= 2.0.2 =
* Update: Information in readme.txt
* Update: Compatibility with WooCommerce 7.9
* Added: Log to file when debug mode is activated. 
= 2.0.1 =
* Update: Information in readme.txt
= 2.0.0 =
