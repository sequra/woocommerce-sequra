<?php
/**
 * Handling Item DTO
 *
 * @package    SeQura/WC
 */

namespace SeQura\WC\Dto;

/**
 * Handling Item DTO.
 */
class Handling_Item extends Dto {
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
	 * Tax rate
	 * 
	 * @var int
	 */
	public $tax_rate;

	/**
	 * Total including tax in cents
	 * 
	 * @var int
	 */
	public $total_with_tax;

	/**
	 * Constructor
	 */
	public function __construct( int $total_with_tax_in_cents ) {
		$this->type           = 'handling';
		$this->reference      = 'handling';
		$this->name           = esc_attr__( 'Shipping cost', 'sequra' );
		$this->tax_rate       = 0;
		$this->total_with_tax = $total_with_tax_in_cents;
	}
}
