<?php
/**
 * Payment service interface
 *
 * @package    SeQura/WC
 * @subpackage SeQura/WC/Services
 */

namespace SeQura\WC\Services\Payment;

use Exception;
use PhpParser\Builder\Interface_;
use SeQura\Core\BusinessLogic\AdminAPI\AdminAPI;
use SeQura\Core\BusinessLogic\Domain\CountryConfiguration\Services\CountryConfigurationService;
use SeQura\Core\BusinessLogic\Domain\Order\Models\PaymentMethod;
use SeQura\Core\BusinessLogic\Domain\PaymentMethod\Models\SeQuraPaymentMethod;
use SeQura\Core\BusinessLogic\Domain\PaymentMethod\Services\PaymentMethodsService;
use SeQura\Core\Infrastructure\ServiceRegister;
use SeQura\WC\Services\Core\Configuration;
use SeQura\WC\Services\I18n\Interface_I18n;
use Throwable;
use WC_Payment_Gateway;

/**
 * Handle use cases related to payments
 */
class Payment_Service implements Interface_Payment_Service {

	/**
	 * Configuration
	 *
	 * @var Configuration
	 */
	private $configuration;

	/**
	 * I18n service
	 *
	 * @var Interface_I18n
	 */
	private $i18n;

	/**
	 * Holds the payment gateway original class name
	 * and maps it to the class name that will be used in the plugin
	 * and the associated data. Contains the following structure:
	 * - class: string (e.g. 'Class_Name')
	 * - alias: string (e.g. 'Sequra_Payment_Gateway_PP3')
	 * - data: array<string, mixed> (e.g. ['product' => 'pp3', 'title' => 'Pay later'])
	 *
	 * @var array<string, mixed[]>
	 */
	private $payment_gateway_registry;

	
	/**
	 * Constructor
	 */
	public function __construct( 
		Configuration $configuration,
		Interface_I18n $i18n
	) {
		$this->configuration = $configuration;
		$this->i18n          = $i18n;
	}

	/**
	 * Get payment gateways as an array. Each element is an array with the following structure:
	 * - product: string (e.g. 'pp3')
	 * - title: string (e.g. 'Pay later')
	 * 
	 * @throws Throwable|Exception
	 * 
	 * @return array<string, mixed>[]
	 */
	public function get_payment_gateways(): array {
		if ( null === $this->payment_gateway_registry ) {
			$store_id = $this->configuration->get_store_id();
			
			$countries       = AdminAPI::get()->countryConfiguration( $store_id )->getCountryConfigurations()->toArray();
			$merchant        = null;
			$current_country = $this->i18n->get_current_country();

			foreach ( $countries as $country ) {
				if ( $country['countryCode'] === $current_country ) {
					$merchant = $country['merchantId'];
					break;
				}
			}
			if ( empty( $merchant ) ) {
				throw new Exception( 'Merchant not found' );
			}

			$payment_methods_arr            = AdminAPI::get()->paymentMethods( $store_id )->getPaymentMethods( $merchant )->toArray();
			$this->payment_gateway_registry = array();
			
			foreach ( $payment_methods_arr as $payment_method ) {
				$this->register_payment_gateway_class( $payment_method );
			}
		}

		return array_keys( $this->payment_gateway_registry );
	}

	/**
	 * Find the payment gateway with empty class and link it to the given class
	 * 
	 * @return array<string, mixed>|null The payment gateway data or null if not found
	 */
	public function link_class_to_payment_gateway( string $class_name ): mixed {
		foreach ( $this->payment_gateway_registry as &$data ) {
			if ( null === $data['class'] ) {
				$data['class'] = $class_name;
				return $data;
			}
		}
		return null;
	}

	/**
	 * Find the payment gateway with empty class and link it to the given class
	 */
	private function add_empty_class_payment_gateway( array $payment_method ): void {
		$this->payment_gateway_registry[ $this->get_class_alias( $payment_method ) ] = array(
			'class'          => null,
			'payment_method' => $payment_method,
		);
	}

	/**
	 * Define the payment gateway class
	 */
	public function register_payment_gateway_class( array $payment_method ): void {
		
		$this->add_empty_class_payment_gateway( $payment_method );

		$class_name = get_class(
			( new class() extends WC_Payment_Gateway{
					/**
					 * Payment service
					 *
					 * @var Interface_Payment_Service
					 */
					private $payment_service;

					/**
					 * Constructor
					 */
				public function __construct() {

					$this->payment_service = ServiceRegister::getService( Interface_Payment_Service::class );
					$data                  = $this->payment_service->link_class_to_payment_gateway( get_class( $this ) );

					$this->id = 'sequra_' . $data['payment_method']['product'];
				}
	
					/**
					 * Plugin options, we deal with it in Step 3 too
					 */
				public function init_form_fields() {
				}
	
				public function process_payment( $order_id ) { 
					// TODO: Implement process_payment() method.
				}
	
				public function webhook() {
					// TODO: Implement webhook() method.
				}
			} )
		);
		
		class_alias( $class_name, $this->get_payment_gateway_alias( $class_name ) );
	}

	/**
	 * Get the payment gateway class alias
	 * 
	 * @return string|null The payment gateway alias or null if not found
	 */
	public function get_payment_gateway_alias( string $class_name ): ?string {
		foreach ( $this->payment_gateway_registry as $alias => $data ) {
			if ( $data['class'] === $class_name ) {
				return $alias;
			}
		}
		return null;
	}

	/**
	 * Get the class name for the payment method
	 * 
	 * @param array<string, mixed> $payment_method. Must contain at least the following:
	 * - product: string
	 * - title: string
	 */
	private function get_class_alias( array $payment_method ): string {
		return 'Sequra_Payment_Gateway_' . $payment_method['product'] . '_' . md5( $payment_method['title'] );
	}
}
