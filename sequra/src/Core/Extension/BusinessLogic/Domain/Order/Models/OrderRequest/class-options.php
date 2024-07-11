<?php
/**
 * Class Options
 *
 * @package SeQura\WC\Core\BusinessLogic\Domain\Order\Models\OrderRequest
 */

namespace SeQura\WC\Core\Extension\BusinessLogic\Domain\Order\Models\OrderRequest;

use SeQura\Core\BusinessLogic\Domain\Order\Models\OrderRequest\Options as CoreOptions;

/**
 * Class Options
 */
class Options extends CoreOptions {

	/**
	 * Desired first charge date
	 *
	 * @var string|null
	 */
	private $desired_first_charge_on;

	/**
	 * Constructor
	 */
	public function __construct(
		bool $has_jquery = null,
		bool $uses_shipped_cart = null,
		bool $addresses_may_be_missing = null,
		bool $immutable_customer_data = null,
		string $desired_first_charge_on = null
	) {
		parent::__construct(
			$has_jquery,
			$uses_shipped_cart,
			$addresses_may_be_missing,
			$immutable_customer_data 
		);
		$this->desired_first_charge_on = $desired_first_charge_on;
	}

	/**
	 * Create a new Options instance from an array of data.
	 *
	 * @param array $data Array containing the data.
	 *
	 * @return Options Returns a new Options instance.
	 */
	public static function fromArray( array $data ): CoreOptions {
		$desired_first_charge_on = self::getDataValue( $data, 'desired_first_charge_on' );

		return new self(
			boolval( self::getDataValue( $data, 'has_jquery' ) ),
			boolval( self::getDataValue( $data, 'uses_shipped_cart' ) ),
			boolval( self::getDataValue( $data, 'addresses_may_be_missing' ) ),
			boolval( self::getDataValue( $data, 'immutable_customer_data' ) ),
			empty( $desired_first_charge_on ) ? null : $desired_first_charge_on
		);
	}

	/**
	 * Get desired first charge date
	 */
	public function get_desired_first_charge_on(): ?string {
		return $this->desired_first_charge_on;
	}
}
