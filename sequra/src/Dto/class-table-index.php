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
	 * Columns to be indexed
	 * 
	 * @var Table_Index_Column[]
	 */
	public $columns;

	/**
	 * Constructor
	 * 
	 * @param string $name The name of the index.
	 * @param Table_Index_Column[] $columns The columns to be indexed.
	 */
	public function __construct( string $name, array $columns ) {
		$this->name    = $name;
		$this->columns = $columns;
	}
}
