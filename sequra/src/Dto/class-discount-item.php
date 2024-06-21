<?php
/**
 * Discount Item DTO
 *
 * @package    SeQura/WC
 */

namespace SeQura\WC\Dto;

/**
 * Discount Item DTO.
 */
class Discount_Item extends Dto {

	/**
	 * Type
	 * 
	 * @var string
	 */
	public $type;

	/**
	 * Reference
	 * 
	 * @var string
	 */
	public $reference;

	/**
	 * Name
	 * 
	 * @var string
	 */
	public $name;

	/**
	 * Total including tax in cents
	 * 
	 * @var int
	 */
	public $total_with_tax;

	/**
	 * Constructor
	 */
	public function __construct( string $reference, int $total_with_tax_in_cents ) {
		$this->type           = 'discount';
		$this->reference      = $reference ?? '';
		$this->name           = esc_attr__( 'Discount', 'sequra' );
		$this->total_with_tax = -1 * $total_with_tax_in_cents;
	}
}
