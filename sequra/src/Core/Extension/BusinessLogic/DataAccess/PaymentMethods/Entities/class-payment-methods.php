<?php
/**
 * Define the Payment_Methods entity.
 *
 * @package SeQura\WC
 */

namespace SeQura\WC\Core\Extension\BusinessLogic\DataAccess\PaymentMethods\Entities;

use SeQura\Core\Infrastructure\ORM\Configuration\EntityConfiguration;
use SeQura\Core\Infrastructure\ORM\Configuration\IndexMap;
use SeQura\Core\Infrastructure\ORM\Entity;

/**
 * Define the Payment_Methods entity.
 */
class Payment_Methods extends Entity {

	/**
	 * Fully qualified name of this class.
	 */
	public const CLASS_NAME = __CLASS__;

	/**
	 * Store ID
	 *
	 * @var string
	 */
	protected $store_id;

	/**
	 * Merchant ID
	 *
	 * @var string
	 */
	protected $merchant_id;

	/**
	 * Payment methods
	 *
	 * @var PaymentMethod[]
	 */
	protected $payment_methods;

	/**
	 * Returns entity configuration object.
	 *
	 * @return EntityConfiguration Configuration object.
	 */
	public function getConfig(): EntityConfiguration {
		$indexMap = new IndexMap();
		$indexMap->addStringIndex( 'storeId' );
		$indexMap->addStringIndex( 'merchantId' );
		return new EntityConfiguration( $indexMap, 'PaymentMethods' );
	}

	/**
	 * Sets raw array data to this entity instance properties.
	 * 
	 * @param array<string, mixed> $data Raw array data with keys for class fields. @see self::$fields for field names.
	 */
	public function inflate( array $data ): void {
		parent::inflate( $data );
		$this->set_store_id( $data['storeId'] );
		$this->set_merchant_id( $data['merchantId'] );

		$payment_methods = $data['paymentMethods'] ?? array();
		foreach ( $payment_methods as $payment_method ) {
			$this->payment_methods[] = Payment_Method::fromArray( $payment_method );
		}
		$this->set_payment_methods( $this->payment_methods );
	}

	/**
	 * Transforms entity to its array format representation.
	 */
	public function toArray(): array {
		$data                   = parent::toArray();
		$data['storeId']        = $this->get_store_id();
		$data['merchantId']     = $this->get_merchant_id();
		$data['paymentMethods'] = array();

		foreach ( $this->get_payment_methods() as $payment_method ) {
			$data['paymentMethods'][] = $payment_method->toArray();
		}

		return $data;
	}

	/**
	 * Get store ID
	 *
	 * @return string
	 */
	public function get_store_id() {
		return $this->store_id;
	}

	/**
	 * Set store ID
	 *
	 * @param string $store_id The store ID.
	 */
	public function set_store_id( $store_id ) {
		$this->store_id = (string) $store_id;
	}

	/**
	 * Get merchant ID
	 *
	 * @return string|null
	 */
	public function get_merchant_id() {
		return $this->merchant_id;
	}

	/**
	 * Set merchant ID
	 *
	 * @param string|null $merchantId
	 */
	public function set_merchant_id( $merchant_id ) {
		$this->merchant_id = null === $merchant_id ? $merchant_id : (string) $merchant_id;
	}

	/**
	 * Get payment methods
	 *
	 * @return PaymentMethod[]
	 */
	public function get_payment_methods() {
		return (array) $this->payment_methods;
	}

	/**
	 * Set payment methods
	 * 
	 * @param PaymentMethod[] $paymentMethods
	 */
	public function set_payment_methods( $payment_methods ) {
		$this->payment_methods = is_array( $payment_methods ) ? $payment_methods : array();
	}
}
