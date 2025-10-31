=== seQura ===
Contributors: sequradev
Tags: woocommerce, payment gateway, BNPL, installments, buy now pay later
Requires at least: 5.9
Tested up to: 6.8.3
Stable tag: 4.1.0
Requires PHP: 7.3
License: GPL-3.0+
License URI: https://www.gnu.org/licenses/gpl-3.0.txt
Flexible payment platform that enhances business conversion and recurrence. The easiest, safest, and quickest way for customers to pay installments.

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

* PHP version 7.3 or greater
* PHP extensions enabled: cURL, JSON
* WordPress 5.9 or greater
* WooCommerce 4.7.0 or greater
* Merchant account at seQura, [sign up here](https://share.hsforms.com/1J2S1J2NPTi-pZERcgJPOVw1c4yg)

= Automatic installation =

1. Install the plugin via Plugins -> New plugin. Search for 'seQura'.
2. Activate the 'seQura' plugin through the 'Plugins' menu in WordPress
3. Set your seQura credentials at WooCommerce -> seQura
4. You're done, the seQura payment methods should be visible in the checkout of your WooCommerce.

= Manual installation =

1. Unpack the download package
2. Upload the directory `sequra` to the `/wp-content/plugins/` directory
3. Activate the 'seQura' plugin through the 'Plugins' menu in WordPress
4. Set your seQura credentials at WooCommerce -> seQura
5. You're done, the seQura payment methods should be visible in the checkout of your WooCommerce.

Please contact sat@sequra.com if you need help installing the seQura WooCommerce plugin.

= Updating =

Automatic updates should work like a charm; as always though, ensure you backup your site just in case.

Contributors:
== Changelog ==
= 4.1.0	=
* Changed: Remove duplicated code and improve error handling.
* Fixed: Allow selling multiple services in the same order.
* Added: The shopper address information can be set to optional when service selling is available using the sequra_merchant_options_addresses_may_be_missing hook.
* Fixed: Payment gateway availability check was not using the data returned from the solicitation process, which caused errors when trying to determine the country in some cases.
* Added: Allow showing product promotional widget on any page via shortcode.
* Added: The promotional widget shortcode also accepts the price as a numeric value.
* Changed: If no destination CSS selector is provided, the promotional widget will be displayed in the same container as the shortcode.

= 4.0.0	=
* Added: PHP 8.4 compatibility.
* Changed: Update integration-core library to version v3.0.0.
* Added: Support for managing multiple deployment targets credentials.
* Added: Integration-Core-UI library providing the assets required for the configuration page.
* Fixed: The link to read more about the payment method was not always working.
* Added: The service related configuration is automatically set based on the available contract options and the enabled countries.
* Changed: Tested up to WordPress 6.8.3 and WooCommerce 10.2.2

= 3.2.2	=
* Fixed: Ensure that the order completion date can be retrieved.
* Fixed: A bug in the Onboarding JavaScript that prevented completion of the credentials step.

= 3.2.1	=
* Fixed: Allow sequra_order table to be created when migration is running if it does not exists but sequra_order_legacy table does.
* Changed: Tested up to WordPress 6.8.2 and WooCommerce 10.0.2

= 3.2.0	=
* Added: Filter to allow modifying the locale.
* Added: SeQura orders are now indexed on new installations.
* Fixed: Existing SeQura orders are indexed in batches in the background using a Cron Job to take advantage of the performance boost.
* Added: Filter to set the start time for running database migrations.
* Added: Filter to set the end time for running database migrations.
* Added: Filter to set how many entities will be processed every time the database migration runs.
* Added: Filter for delaying the execution of the listener for the WooCommerce updated_checkout JS event.
* Added: Filter to set the delay in milliseconds of the listener for the WooCommerce updated_checkout JS event.
* Added: Order notes for refund errors.
* Changed: Tested up to WordPress 6.8.1 and WooCommerce 9.8.5.

= 3.1.1	=
* Fixed: Use WC Order ID from notification parameters for retrieving the Order for confirmation at seQura.
* Fixed: Error that prevents the process that handles the deletion of old seQura order data from running.
* Fixed: Error related to implicit conversion from float to int.
= 3.1.0	=
* Fixed: Remove blank lines from template files to prevent errors when wpautop or similar functions are used.
* Fixed: Add missing value checks in the Configuration page scripts to prevent the loading state from getting stuck.
* Changed: Enhance the disconnection process to support store-specific deletions.
* Fixed: Prevent the migration process from running repeatedly unnecessarily.
* Added: Cron job to delete old seQura order data from database to reduce the amount of space taken.
* Fixed: Use the right merchant id based on the current order.
= 3.0.7	=
* Fixed: Bug in the migration SQL query to create new tables.
* Changed: Update integration-core library to version v1.0.17.
* Added: Improved checkout performance by eliminating unnecessary requests.
* Added: The sequra_shopper_country filter, allowing customization of the detected shopper's country code.
* Added: Restored the educational pop-up present in every seQura payment option on the checkout page in v2.
* Added: Payment options are now cached to enhance performance when rendering widgets.
* Fixed: Banned products will not display widgets.
* Fixed: Stop calling OrderUpdate API for orders not paid using seQura.
* Fixed: Orders containing only virtual and downloadable products are automatically marked as completed once paid through seQura.
= 3.0.6	=
* Fixed: Type comparison bug that prevents orders from being eligible for service sales in some scenarios.
* Fixed: Allow null payment method data in validation method.
* Fixed: Implement an adapter for the is_store_api_request method to avoid errors in older versions of WooCommerce.
* Changed: Add namespace prefix to WordPress and WooCommerce functions used for better compatibility.
* Changed: Tested up to WordPress 6.7.1 and WooCommerce 9.5.1.
= 3.0.5	=
* Fixed: CSS rules for the payment method component.
* Fixed: Performance improvements due the ignore of bot requests.
* Fixed: Update place order text button when seQura's payment method is selected.
* Changed: Remove request to seQura's API from the cart page to improve performance.
= 3.0.4	=
* Fixed: Fatal error detected in WordPress 6.4.5 when using shortcode without passing attributes.
= 3.0.3	=
* Fixed: Improve performance skipping previous orders retrieval for guest shoppers.
* Fixed: Don't offer seQura payment methods to bots.
= 3.0.2	=
* Fixed: Error when accessing order listing in wp-admin with the HPOS feature activated.
* Fixed: PHP 7.4 compatibility error.
= 3.0.1	=
* Changed: Move local fonts to CDN.
* Fixed: Bug that prevents from showing seQura's payment methods on the mobile version of the checkout page.
= 3.0.0	=
* Added: Compatibility with pages using WooCommerce's Gutenberg blocks.
* Added: Support for different merchant-ref within the same installation.
* Added: Order completion and refund events are now synched instantly with seQura.
* Added: Better widget configuration with new parameters and improved price changing detection.
* Added: Support for mini widgets on both cart page and product listings.
* Added: Onboarding screen for new installations.
* Added: Orders now show a link to seQura's back-office.
* Added: Out of the box compatibility with third party WooCommerce addons (such as LearnPress).
* Changed: Revamped configuration page with modern UI and better UX.
* Changed: Tested up to WordPress 6.6.2 and WooCommerce 9.3.3.
* Fixed: Now is possible to pay with seQura for orders created manually via wp-admin.
= 2.0.12	=
* Changed: Sign-up URLs.
* Fixed: Widget's JavaScript to read prices from product pages.
* Changed: ISO 8061 date format regular expression used for validations.
= 2.0.11	=
* Fixed: Filter order to report by payment_method field instead of _payment_method order meta.
= 2.0.10	=
* Added: Translations for ES, PT, IT, FR
* Fixed: Warning in php 8.2
* Fixed: Emptying firsts desired date in product details page
= 2.0.9	=
* Fixed: Deprecation warning on plugin's settings page.
* Fixed: Hide widgets when payment methods start/end dates don't match.
= 2.0.8	=
* Fixed: Information about plugin version.
* Fixed: Avoid warning in SequraTempOrder class.
= 2.0.7 =
* Fixed: rounding amount total for the +info popup in the checkout.
= 2.0.6 =
* Fixed: Javascript warning due to changes in the integrations assets.
* Fixed: PHP warning due to sequra/php-client package upgrade.
* Fixed: Removed all references to deprecated `without_tax` values.
* Fixed: Add dependance on the "enabled in product" admin option to "Simulator params" option.
= 2.0.5 =
* Fixed: Copy billing address to shipping address when ship_to_different_address is not set.
* Added: compatibility till WooCommerce 8.2.
= 2.0.4 =
* Added: HPOS Compatibility declaration.
* Added: Compatibility with WooCommerce 8.0.
= 2.0.3 =
* Fixed: Delivery report generation when order had multiple discounts.
= 2.0.2 =
* Changed: Information in readme.txt
* Added: Compatibility with WooCommerce 7.9.
* Added: Log to file when debug mode is activated. 
= 2.0.1 =
* Changed: Information in readme.txt.
= 2.0.0 =
