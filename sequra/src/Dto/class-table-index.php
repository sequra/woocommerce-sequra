<?php
/**
 * Table Index.
 *
 * @package    SeQura/WC
 */

namespace SeQura\WC\Dto;

/**
 * Table Index.
 */
class Table_Index extends Dto {

	/**
	 * Unique identifier
	 * 
	 * @var string
	 */
	public $name;

	/**
	 * Names of the columns to be indexed
	 * 
	 * @var string[]
	 */
	public $columns;

	/**
	 * Constructor
	 */
	public function __construct( string $name, array $columns ) {
		$this->name    = $name;
		$this->columns = $columns;
	}
}
