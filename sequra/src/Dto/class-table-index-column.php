<?php
/**
 * Table Index Column.
 *
 * @package    SeQura/WC/Dto
 */

namespace SeQura\WC\Dto;

/**
 * Table Index Column.
 */
class Table_Index_Column extends Dto {

	/**
	 * The name of the column to be indexed.
	 * 
	 * @var string
	 */
	public $name;

	/**
	 * The character limit for the column if applicable.
	 * 
	 * @var null|int
	 */
	public $char_limit;

	/**
	 * Constructor
	 * 
	 * @param string $name The name of the column.
	 * @param int|null $char_limit The character limit for the column. Set to null if not applicable.
	 */
	public function __construct( string $name, $char_limit = null ) {
		$this->name       = $name;
		$this->char_limit = ! is_null( $char_limit ) && is_int( $char_limit ) ? $char_limit : null;
	}
}
