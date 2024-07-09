<?php
/**
 * Extends the GeneralSettings class.
 *
 * @package SeQura\WC
 */

namespace SeQura\WC\Core\Extension\BusinessLogic\Domain\GeneralSettings\Models;

use SeQura\Core\BusinessLogic\Domain\GeneralSettings\Models\GeneralSettings;

/**
 * Extends the GeneralSettings class.
 */
class General_Settings extends GeneralSettings {

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
		bool $enabled_for_services,
		bool $allow_first_service_payment_delay,
		bool $allow_service_reg_items,
		string $default_services_end_date
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
	 * Create a new General_Settings instance from a GeneralSettings instance.
	 */
	public static function from_parent(
		GeneralSettings $instance,
		bool $enabled_for_services,
		bool $allow_first_service_payment_delay,
		bool $allow_service_reg_items,
		string $default_services_end_date  
	): General_Settings {
		return new self(
			$instance->isSendOrderReportsPeriodicallyToSeQura(),
			$instance->isShowSeQuraCheckoutAsHostedPage(),
			$instance->getAllowedIPAddresses(),
			$instance->getExcludedProducts(),
			$instance->getExcludedCategories(),
			$enabled_for_services,
			$allow_first_service_payment_delay,
			$allow_service_reg_items,
			$default_services_end_date
		);
	}

	/**
	 * Getter
	 */
	public function is_enabled_for_services(): bool {
		return $this->enabled_for_services;
	}

	/**
	 * Setter
	 */
	public function set_enabled_for_services( bool $enabled_for_services ): void {
		$this->enabled_for_services = $enabled_for_services;
	}

	/**
	 * Getter
	 */
	public function is_allow_first_service_payment_delay(): bool {
		return $this->allow_first_service_payment_delay;
	}

	/**
	 * Setter
	 */
	public function set_allow_first_service_payment_delay( bool $allow_first_service_payment_delay ): void {
		$this->allow_first_service_payment_delay = $allow_first_service_payment_delay;
	}

	/**
	 * Getter
	 */
	public function is_allow_service_reg_items(): bool {
		return $this->allow_service_reg_items;
	}

	/**
	 * Setter
	 */
	public function set_allow_service_reg_items( bool $allow_service_reg_items ): void {
		$this->allow_service_reg_items = $allow_service_reg_items;
	}

	/**
	 * Getter
	 */
	public function get_default_services_end_date(): string {
		return $this->default_services_end_date;
	}

	/**
	 * Setter
	 */
	public function set_default_services_end_date( string $default_services_end_date ): void {
		$this->default_services_end_date = $default_services_end_date;
	}
}
