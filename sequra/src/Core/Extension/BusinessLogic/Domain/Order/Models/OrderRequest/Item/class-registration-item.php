<?php
/**
 * RegistrationItem
 * 
 * @package SeQura\Core\BusinessLogic\Domain\Order\Models\OrderRequest\Item
 */

namespace SeQura\WC\Core\BusinessLogic\Domain\Order\Models\OrderRequest\Item;

use SeQura\Core\BusinessLogic\Domain\Order\Models\OrderRequest\Item\Item;

/**
 * Class RegistrationItem
 */
class Registration_Item extends Item {

	/**
	 * Item type
	 */
	public const TYPE = 'registration';

	/**
	 * A unique code that refers to this registration.
	 * 
	 * @var string|int
	 */
	private $reference;

	/**
	 * A name to describe this registration.
	 * 
	 * @var string
	 */
	private $name;

	/**
	 * Constructor
	 */
	public function __construct( $reference, string $name, int $totalWithTax ) {
		parent::__construct( $totalWithTax, self::TYPE );

		$this->reference = $reference;
		$this->name      = $name;
	}

	/**
	 * Create RegistrationItem object from array.
	 *
	 * @param array $data
	 *
	 * @return Registration_Item
	 */
	public static function fromArray( array $data ): Item {
		$totalWithTax = self::getDataValue( $data, 'total_with_tax', 0 );
		$reference    = self::getDataValue( $data, 'reference' );
		$name         = self::getDataValue( $data, 'name' );

		return new self( $reference, $name, $totalWithTax );
	}

	/**
	 * Getter
	 *
	 * @return int|string
	 */
	public function getReference() {
		return $this->reference;
	}

	/**
	 * Getter
	 */
	public function getName(): string {
		return $this->name;
	}

	/**
	 * Convert object to array.
	 * 
	 * @return array<string, mixed>
	 */
	public function toArray(): array {
		return $this->transformPropertiesToAnArray( get_object_vars( $this ) );
	}
}
