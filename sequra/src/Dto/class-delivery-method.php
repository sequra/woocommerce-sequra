<?php
/**
 * Delivery Method DTO
 *
 * @package    SeQura/WC
 */

namespace SeQura\WC\Dto;

/**
 * Delivery Method DTO.
 */
class Delivery_Method extends Dto {

	/**
	 * The name
	 * 
	 * @var string
	 */
	public $name;

	/**
	 * The provider
	 * 
	 * @var string
	 */
	public $provider;

	/**
	 * Days to deliver
	 * 
	 * @var string
	 */
	public $days;

	/**
	 * Constructor
	 */
	public function __construct( string $name, string $provider, string $days = '' ) {
		$this->name     = $name;
		$this->provider = $provider;
		$this->days     = $days;
	}
}
