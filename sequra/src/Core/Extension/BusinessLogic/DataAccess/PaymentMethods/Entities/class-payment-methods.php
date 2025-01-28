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

// phpcs:disable WordPress.NamingConventions.ValidVariableName.PropertyNotSnakeCase, WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase

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
	protected $storeId;

	/**
	 * Merchant ID
	 *
	 * @var string
	 */
	protected $merchantId;

	/**
	 * Payment methods
	 *
	 * @var Payment_Method[]
	 */
	protected $paymentMethods;

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
		$this->setStoreId( $data['storeId'] );
		$this->setMerchantId( $data['merchantId'] );

		$dataPaymentMethods = (array) ( $data['paymentMethods'] ?? array() );
		$paymentMethods     = array();
		foreach ( $dataPaymentMethods as $pm ) {
			if ( is_array( $pm ) ) {
				$paymentMethods[] = Payment_Method::fromArray( $pm );
			}
		}
		$this->setPaymentMethods( $paymentMethods );
	}

	/**
	 * Transforms entity to its array format representation.
	 */
	public function toArray(): array {
		$data                   = parent::toArray();
		$data['storeId']        = $this->getStoreId();
		$data['merchantId']     = $this->getMerchantId();
		$data['paymentMethods'] = array();

		foreach ( $this->getPaymentMethods() as $payment_method ) {
			$data['paymentMethods'][] = $payment_method->toArray();
		}

		return $data;
	}

	/**
	 * Get store ID
	 *
	 * @return string
	 */
	public function getStoreId() {
		return $this->storeId;
	}

	/**
	 * Set store ID
	 *
	 * @param string $storeId The store ID.
	 */
	public function setStoreId( $storeId ) {
		$this->storeId = (string) $storeId;
	}

	/**
	 * Get merchant ID
	 *
	 * @return string|null
	 */
	public function getMerchantId() {
		return $this->merchantId;
	}

	/**
	 * Set merchant ID
	 *
	 * @param string|null $merchantId
	 */
	public function setMerchantId( $merchantId ) {
		$this->merchantId = null === $merchantId ? $merchantId : (string) $merchantId;
	}

	/**
	 * Get payment methods
	 *
	 * @return PaymentMethod[]
	 */
	public function getPaymentMethods() {
		return (array) $this->paymentMethods;
	}

	/**
	 * Set payment methods
	 * 
	 * @param PaymentMethod[] $paymentMethods
	 */
	public function setPaymentMethods( $paymentMethods ) {
		$this->paymentMethods = is_array( $paymentMethods ) ? $paymentMethods : array();
	}
}
