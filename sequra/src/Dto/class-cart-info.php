<?php
/**
 * Cart Info DTO
 *
 * @package    SeQura/WC
 */

namespace SeQura\WC\Dto;

/**
 * Cart Info DTO.
 */
class Cart_Info extends Dto {

	/**
	 * Unique identifier
	 * 
	 * @var string
	 */
	public $ref;

	/**
	 * ISO 8601 date of creation
	 * 
	 * @var string
	 */
	public $created_at;

	/**
	 * Constructor
	 */
	public function __construct() {
		$this->ref        = uniqid();
		$this->created_at = gmdate( 'c' );
	}
}
