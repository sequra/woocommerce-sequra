<?php
/**
 * Extends the GeneralSettingsRequest class.
 *
 * @package SeQura\WC
 */

namespace SeQura\WC\Core\Extension\BusinessLogic\AdminAPI\GeneralSettings\Requests;

use SeQura\Core\BusinessLogic\AdminAPI\GeneralSettings\Requests\GeneralSettingsRequest;
use SeQura\Core\BusinessLogic\Domain\GeneralSettings\Models\GeneralSettings;
use SeQura\WC\Core\Extension\BusinessLogic\Domain\GeneralSettings\Models\General_Settings;

/**
 * Extends the GeneralSettingsRequest class.
 */
class General_Settings_Request extends GeneralSettingsRequest {

	/**
	 * Is enabled for services
	 * 
	 * @var bool
	 */
	private $enabled_for_services;

	/**
	 * Allow first service payment delay
	 * 
	 * @var bool
	 */
	private $allow_first_service_payment_delay;

	/**
	 * Allow service registration items
	 * 
	 * @var bool
	 */
	private $allow_service_reg_items;

	/**
	 * Default services end date
	 *
	 * @var string
	 */
	private $default_services_end_date;

	/**
	 * Constructor
	 * 
	 * @param bool $send_order_reports_periodically_to_sequra Send order reports periodically to Sequra.
	 * @param bool|null $show_sequra_checkout_as_hosted_page Show Sequra checkout as hosted page.
	 * @param string[]|null $allowed_ip_addresses Allowed IP addresses.
	 * @param string[]|null $excluded_products Excluded products.
	 * @param string[]|null $excluded_categories Excluded categories.
	 * @param bool $enabled_for_services Is enabled for services.
	 * @param bool $allow_first_service_payment_delay Allow first service payment delay.
	 * @param bool $allow_service_reg_items Allow service registration items.
	 * @param string $default_services_end_date Default services end date.
	 */
	public function __construct(
		bool $send_order_reports_periodically_to_sequra,
		?bool $show_sequra_checkout_as_hosted_page,
		?array $allowed_ip_addresses,
		?array $excluded_products,
		?array $excluded_categories,
		bool $enabled_for_services = false,
		bool $allow_first_service_payment_delay = false,
		bool $allow_service_reg_items = false,
		string $default_services_end_date = 'P1Y'
	) {
		parent::__construct(
			$send_order_reports_periodically_to_sequra,
			$show_sequra_checkout_as_hosted_page,
			$allowed_ip_addresses,
			$excluded_products,
			$excluded_categories
		);

		$this->enabled_for_services              = $enabled_for_services;
		$this->allow_first_service_payment_delay = $allow_first_service_payment_delay;
		$this->allow_service_reg_items           = $allow_service_reg_items;
		$this->default_services_end_date         = $default_services_end_date;
	}

	/**
	 * Transforms the request to a GeneralSettings object.
	 *
	 * @return GeneralSettings
	 */
	public function transformToDomainModel(): object {
		return General_Settings::from_parent(
			parent::transformToDomainModel(),
			$this->enabled_for_services,
			$this->allow_first_service_payment_delay,
			$this->allow_service_reg_items,
			$this->default_services_end_date
		);
	}
}
