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

	/**
	 * Generate SQL definition for the index.
	 * 
	 * @return string SQL statement to create the index.
	 */
	public function to_sql() {
		$index_name = \sanitize_key( $this->name );
		$columns    = array();
		foreach ( $this->columns as $column ) {
			$columns[] = '`' . \sanitize_key( $column->name ) . '`' . ( null !== $column->char_limit ? "({$column->char_limit})" : '' );
		}
		$columns = implode( ', ', $columns );
		return "INDEX `{$index_name}` ({$columns})";
	}
}
