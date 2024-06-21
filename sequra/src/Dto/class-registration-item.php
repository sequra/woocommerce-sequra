<?php
/**
 * Registration Item DTO
 *
 * @package    SeQura/WC
 */

namespace SeQura\WC\Dto;

/**
 * Registration Item DTO.
 */
class Registration_Item extends Dto {

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
	public function __construct( string $reference, string $name, int $total_with_tax_in_cents ) {
		$this->type           = 'registration';
		$this->reference      = "$reference-reg";
		$this->name           = "Reg. $name";
		$this->total_with_tax = $total_with_tax_in_cents;
	}
}
