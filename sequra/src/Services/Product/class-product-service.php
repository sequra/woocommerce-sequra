<?php
/**
 * Product service
 *
 * @package    SeQura/WC
 * @subpackage SeQura/WC/Services
 */

namespace SeQura\WC\Services\Product;

use DateInterval;
use DateTime;
use SeQura\Core\BusinessLogic\AdminAPI\AdminAPI;
use SeQura\Core\BusinessLogic\AdminAPI\GeneralSettings\Responses\GeneralSettingsResponse;
use SeQura\Core\BusinessLogic\Domain\GeneralSettings\Models\GeneralSettings;
use SeQura\Core\BusinessLogic\Domain\Multistore\StoreContext;
use SeQura\Core\Infrastructure\Utility\RegexProvider;
use SeQura\WC\Services\Payment\Sequra_Payment_Gateway;
use SeQura\WC\Services\Pricing\Interface_Pricing_Service;
use Throwable;
use WC_Product;

/**
 * Handle use cases related to products
 */
class Product_Service implements Interface_Product_Service {

	private const META_KEY_DESIRED_FIRST_CHARGE_DATE  = 'sequra_desired_first_charge_date';
	private const META_KEY_IS_SEQURA_SERVICE          = 'is_sequra_service';
	private const META_KEY_IS_SEQURA_BANNED           = 'is_sequra_banned';
	private const META_KEY_SEQURA_SERVICE_END_DATE    = 'sequra_service_end_date';
	private const META_KEY_SEQURA_REGISTRATION_AMOUNT = 'sequra_registration_amount';

	/**
	 * Pricing service
	 *
	 * @var Interface_Pricing_Service
	 */
	private $pricing_service;

	/**
	 * RegEx service
	 *
	 * @var RegexProvider
	 */
	private $regex;

	/**
	 * Store context
	 *
	 * @var StoreContext
	 */
	private $store_context;

	/**
	 * Constructor
	 */
	public function __construct(
		Interface_Pricing_Service $pricing_service,
		RegexProvider $regex,
		StoreContext $store_context
	) {
		$this->pricing_service = $pricing_service;
		$this->regex           = $regex;
		$this->store_context   = $store_context;
	}

	/**
	 * Get desired first charge date for a product
	 * 
	 * @param int|WC_Product $product The product ID or product object
	 * @return DateTime|null|string
	 */
	public function get_desired_first_charge_date( $product, bool $raw_value = false ) {
		$_product = $this->get_product_instance( $product );
		if ( ! $_product ) {
			return null;
		}
		$raw_date = $_product->get_meta( self::META_KEY_DESIRED_FIRST_CHARGE_DATE, true );
		if ( ! $raw_date ) {
			return null;
		}

		if ( $raw_value ) {
			return $raw_date;
		}

		if ( 'P' === substr( $raw_date, 0, 1 ) ) {
			return ( new DateTime() )->add( new DateInterval( $raw_date ) );
		} 
		return new DateTime( $raw_date );
	}

	/**
	 * Get product instance
	 * 
	 * @param int|WC_Product $product The product ID or product object
	 */
	public function get_product_instance( $product ): ?WC_Product {
		if ( $product instanceof WC_Product ) {
			return $product;
		}
		$_product = \wc_get_product( $product );
		return ! $_product ? null : $_product;
	}

	/**
	 * Check if product is a seQura service
	 * 
	 * @param int|WC_Product $product The product ID or product object
	 */
	public function is_service( $product ): bool {
		$_product = $this->get_product_instance( $product );
		if ( ! $_product ) {
			return false;
		}
		return 'no' !== $_product->get_meta( self::META_KEY_IS_SEQURA_SERVICE, true );
	}

	/**
	 * Get product is_banned value
	 * 
	 * @param int|WC_Product $product The product ID or product object
	 */
	public function get_is_banned( $product ): bool {
		$_product = $this->get_product_instance( $product );
		if ( ! $_product ) {
			return false;
		}
		return 'yes' === $_product->get_meta( self::META_KEY_IS_SEQURA_BANNED, true );
	}

	/**
	 * Get banned categories from SeQura configuration
	 * 
	 * @return array<string>
	 */
	protected function get_banned_products(): array {
		try {
			/**
			 * Array containing the general settings
			 * 
			 * @var array<string, mixed> $config
			 */
			$config = AdminAPI::get()->generalSettings( $this->store_context->getStoreId() )->getGeneralSettings()->toArray();
			if ( ! empty( $config['excludedProducts'] ) && is_array( $config['excludedProducts'] ) ) {
				return $config['excludedProducts'];
			}
			return array();
		} catch ( Throwable $e ) {
			return array();
		}
	}

	/**
	 * Get banned categories from general settings.
	 * 
	 * @return array<int>
	 */
	protected function get_banned_categories(): array {
		try {
			/**
			 * Array containing the general settings
			 * 
			 * @var array<string, mixed> $config
			 */
			$config = AdminAPI::get()
			->generalSettings( $this->store_context->getStoreId() )
			->getGeneralSettings()
			->toArray();
			if ( ! empty( $config['excludedCategories'] ) && is_array( $config['excludedCategories'] ) ) {
				return array_map( 'absint', $config['excludedCategories'] );
			}
			return array();
		} catch ( Throwable $e ) {
			return array();
		}
	}

	/**
	 * Check if product and category ban lists are empty
	 */
	public function is_ban_list_empty(): bool {
		return empty( $this->get_banned_products() ) && empty( $this->get_banned_categories() );
	}

	/**
	 * Check if product is banned
	 * 
	 * @param int|WC_Product $product The product ID or product object
	 */
	public function is_banned( $product ): bool {
		$_product = $this->get_product_instance( $product );
		if ( ! $_product ) {
			return false;
		}
		// TODO: Deprecate the ban using metadata in favor of the configuration.
		$banned = $this->get_is_banned( $_product );
		if ( ! $banned ) {
			$banned_products = $this->get_banned_products();
			$banned          = in_array( $_product->get_sku(), $banned_products, true ) || in_array( strval( $_product->get_id() ), $banned_products, true );

			if ( ! $banned ) {
				$banned_categories = $this->get_banned_categories();
				$banned            = ! empty( $banned_categories ) && ! empty( array_intersect( $banned_categories, $_product->get_category_ids() ) );
			}
		}
		
		return $banned;
	}

	/**
	 * Get product service end date
	 *
	 * @param WC_Product|int $product the product we are building item info for.
	 */
	public function get_service_end_date( $product, bool $raw_value = false ): string {
		$product = $this->get_product_instance( $product );
		
		/**
		 * Filter the service end date.
		 *
		 * @since 2.0.0
		 */
		$service_end_date = \apply_filters(
			'woocommerce_sequra_add_service_end_date',
			$product->get_meta( self::META_KEY_SEQURA_SERVICE_END_DATE, true ),
			$product,
			array()
		);

		if ( $raw_value ) {
			return $service_end_date;
		}
		if ( ! preg_match( $this->regex->getDateOrDurationRegex(), $service_end_date ) ) {
			$service_end_date = $this->get_default_services_end_date();
		}
		return $service_end_date;
	}

	/**
	 * Get registration amount
	 *
	 * @param WC_Product|int $product the product we are building item info for.
	 */
	public function get_registration_amount( $product, bool $to_cents = false ): float {
		$product = $this->get_product_instance( $product );
		$value   = (float) $product->get_meta( self::META_KEY_SEQURA_REGISTRATION_AMOUNT, true );
		return $to_cents ? $this->pricing_service->to_cents( $value ) : $value;
	}

	/**
	 * Check if the seQura payment gateway is active
	 */
	protected function is_payment_gateway_active(): bool {
		if ( ! function_exists( 'WC' ) ) {
			return false;
		}
		
		/**
		 * Payment gateway
		 * 
		 * @var Sequra_Payment_Gateway
		 */
		$gateway = null;
		foreach ( WC()->payment_gateways()->payment_gateways() as $g ) {
			if ( $g instanceof Sequra_Payment_Gateway && 'yes' === $g->enabled ) {
				$gateway = $g;
				break;
			}
		}

		if ( ! $gateway ) {
			return false;
		}
		return true;
	}

	/**
	 * Check if we can display widgets for a product
	 *
	 * @param WC_Product|int $product the product.
	 */
	protected function can_display_widgets( $product ): bool {
		$product = $this->get_product_instance( $product );

		$return = $this->is_payment_gateway_active()
		&& $product
		&& ( $this->is_enabled_for_services() || $product->needs_shipping() )
		&& ! $this->is_banned( $product );

		/**
		* Filter seQura availability at product page
		*
		* @since 2.0.0
		*/
		$return = (bool) \apply_filters_deprecated( 'woocommerce_sq_is_available_in_product_page', array( $return, $product->get_id() ), '3.0.0', 'sequra_can_display_widgets' );
		
		/**
		 * Filter widget availability for a given product
		 *
		 * @since 3.0.0
		 */
		return (bool) \apply_filters( 'sequra_can_display_widgets', $return, $product );
	}

	/**
	 * Check if we can display widget of a payment method for a product
	 * 
	 * @param WC_Product|int $product the product.
	 * @param array<string, string> $method the payment method. See PaymentMethodsResponse::toArray() output
	 */
	public function can_display_widget_for_method( $product, $method ): bool {
		$product = $this->get_product_instance( $product );
		
		$return = $product 
		&& $this->can_display_widgets( $product )
		// Check if price is too high.
		&& ( empty( $method['maxAmount'] ) || $method['maxAmount'] >= $this->pricing_service->to_cents( (float) $product->get_price( 'edit' ) ) )
		// Check if is too early to display the widget.
		&& ( empty( $method['startsAt'] ) || time() >= strtotime( $method['startsAt'] ) )
		// Check if is too late to display the widget.
		&& ( empty( $method['endsAt'] ) || strtotime( $method['endsAt'] ) >= time() ); 

		/**
		* Filter widget availability for a given seQura method
		*
		* @since 3.0.0
		*/
		return (bool) \apply_filters( 'sequra_can_display_widget_for_method', $return, $product, $method );
	}

	/**
	 * Update is service value
	 */
	public function set_is_service( int $product_id, ?string $value ): void {
		if ( null === $value ) {
			\delete_post_meta( $product_id, self::META_KEY_IS_SEQURA_SERVICE );
		} else {
			\update_post_meta( $product_id, self::META_KEY_IS_SEQURA_SERVICE, 'yes' === $value ? 'yes' : 'no' );
		}
	}

	/**
	 * Update is_banned value
	 */
	public function set_is_banned( int $product_id, ?string $value ): void {
		if ( null === $value ) {
			\delete_post_meta( $product_id, self::META_KEY_IS_SEQURA_BANNED );
		} else {
			\update_post_meta( $product_id, self::META_KEY_IS_SEQURA_BANNED, 'yes' === $value ? 'yes' : 'no' );
		}
	}

	/**
	 * Update service end date value
	 */
	public function set_service_end_date( int $product_id, ?string $value ): void {
		if ( null === $value ) {
			\delete_post_meta( $product_id, self::META_KEY_SEQURA_SERVICE_END_DATE );
		} else {
			\update_post_meta( $product_id, self::META_KEY_SEQURA_SERVICE_END_DATE, $value );
		}
	}

	/**
	 * Update desired_first_charge_date value
	 */
	public function set_desired_first_charge_date( int $product_id, ?string $value ): void {
		if ( null === $value ) {
			\delete_post_meta( $product_id, self::META_KEY_DESIRED_FIRST_CHARGE_DATE );
		} else {
			\update_post_meta( $product_id, self::META_KEY_DESIRED_FIRST_CHARGE_DATE, $value );
		}
	}

	/**
	 * Update registration amount value
	 */
	public function set_registration_amount( int $product_id, ?float $value ): void {
		if ( null === $value ) {
			\delete_post_meta( $product_id, self::META_KEY_SEQURA_REGISTRATION_AMOUNT );
		} else {
			\update_post_meta( $product_id, self::META_KEY_SEQURA_REGISTRATION_AMOUNT, $value );
		}
	}

	/**
	 * Get enabledForServices from general settings.
	 */
	public function is_enabled_for_services( ?string $country = null ): bool {
		try {
			/**
			 * Array containing the general settings
			 * 
			 * @var array<string, mixed> $config
			 */
			$config = AdminAPI::get()
			->generalSettings( $this->store_context->getStoreId() )
			->getGeneralSettings()
			->toArray();
			return $this->validate_config_array_for_country( $config, 'enabledForServices', $country );
		} catch ( Throwable $e ) {
			return false;
		}
	}
	
	/**
	 * Get allowFirstServicePaymentDelay from general settings.
	 */
	public function is_allow_first_service_payment_delay( ?string $country = null ): bool {
		try {
			/**
			 * Array containing the general settings
			 * 
			 * @var array<string, mixed> $config
			 */
			$config = AdminAPI::get()
			->generalSettings( $this->store_context->getStoreId() )
			->getGeneralSettings()
			->toArray();
			return $this->validate_config_array_for_country( $config, 'allowFirstServicePaymentDelay', $country );
		} catch ( Throwable $e ) {
			return false;
		}
	}

	/**
	 * Get allowServiceRegistrationItems from general settings.
	 */
	public function is_allow_service_registration_items( ?string $country = null ): bool {
		try {
			/**
			 * Array containing the general settings
			 * 
			 * @var array<string, mixed> $config
			 */
			$config = AdminAPI::get()
			->generalSettings( $this->store_context->getStoreId() )
			->getGeneralSettings()
			->toArray();
			return $this->validate_config_array_for_country( $config, 'allowServiceRegistrationItems', $country );
		} catch ( Throwable $e ) {
			return false;
		}
	}

	/**
	 * Validate config array and optionally filter by country
	 *
	 * @param array<string, mixed> $config Configuration array
	 * @param string $key Configuration key to validate
	 * @param string|null $country Optional country to filter by
	 * @return bool
	 */
	private function validate_config_array_for_country( array $config, string $key, ?string $country = null ): bool {
		if ( empty( $config[ $key ] ) || ! is_array( $config[ $key ] ) ) {
			return false;
		}
		return ! empty( $country ) ? in_array( $country, $config[ $key ], true ) : true;
	}

	/**
	 * Get defaultServicesEndDate from general settings.
	 */
	public function get_default_services_end_date(): string {
		/**
		 * Response containing the general settings
		 * 
		 * @var GeneralSettingsResponse
		 */
		$response = AdminAPI::get()->generalSettings( $this->store_context->getStoreId() )->getGeneralSettings();
		$arr      = $response->isSuccessful() ? $response->toArray() : array();
		return strval( $arr['defaultServicesEndDate'] ?? GeneralSettings::DEFAULT_SERVICE_END_DATE );
	}
}
