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
