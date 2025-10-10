<?php
/**
 * Implementation of WidgetConfiguratorInterface
 *
 * @package SeQura\WC
 */

namespace SeQura\WC\Core\Implementation\BusinessLogic\Domain\Integration\PromotionalWidgets;

use SeQura\Core\BusinessLogic\Domain\Integration\PromotionalWidgets\WidgetConfiguratorInterface;
use SeQura\WC\Services\I18n\Interface_I18n;

/**
 * Widget Configurator
 */
class Widget_Configurator implements WidgetConfiguratorInterface {

	/**
	 * I18n Service
	 * 
	 * @var Interface_I18n
	 */
	protected $i18n;

	/**
	 * Construct
	 */
	public function __construct( Interface_I18n $i18n ) {
		$this->i18n = $i18n;
	}

	/**
	 * Returns current locale
	 *
	 * @return string
	 */
	public function getLocale(): string {
		return $this->i18n->get_locale();
	}

	/**
	 * Returns current currency
	 *
	 * @return string
	 */
	public function getCurrency(): string {
		return \get_woocommerce_currency();
	}

	/**
	 * Returns decimal separator
	 *
	 * @return string
	 */
	public function getDecimalSeparator(): string {
		return \wc_get_price_decimal_separator();
	}

	/**
	 * Returns thousand separator
	 *
	 * @return string
	 */
	public function getThousandsSeparator(): string {
		return \wc_get_price_thousand_separator();
	}
}
