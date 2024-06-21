<?php
/**
 * Fee Item DTO
 *
 * @package    SeQura/WC
 */

namespace SeQura\WC\Dto;

/**
 * Fee Item DTO.
 */
class Fee_Item extends Dto {

	public const TYPE_HANDLING = 'handling';
	public const TYPE_DISCOUNT = 'discount';

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
	public function __construct( string $name, int $total_with_tax_in_cents ) {
		$this->type           = $total_with_tax_in_cents ? self::TYPE_HANDLING : self::TYPE_DISCOUNT;
		$this->reference      = $reference ?? '';
		$this->name           = $name ?? '';
		$this->tax_rate       = 0;
		$this->total_with_tax = $total_with_tax_in_cents;
	}
}
