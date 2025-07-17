<?php
/**
 * Shared repository functionality.
 *
 * @package    SeQura/WC
 * @subpackage SeQura/WC/Repositories
 */

namespace SeQura\WC\Repositories;

use Exception;
use SeQura\Core\Infrastructure\ORM\Entity;
use SeQura\Core\Infrastructure\ORM\Exceptions\QueryFilterInvalidParamException;
use SeQura\Core\Infrastructure\ORM\Interfaces\RepositoryInterface;
use SeQura\Core\Infrastructure\ORM\QueryFilter\QueryCondition;
use SeQura\Core\Infrastructure\ORM\QueryFilter\QueryFilter;
use SeQura\Core\Infrastructure\ORM\Utility\IndexHelper;
use SeQura\Core\Infrastructure\ServiceRegister;
use SeQura\WC\Dto\Table_Index;
use wpdb;

/**
 * Shared repository functionality.
 */
abstract class Repository implements RepositoryInterface, Interface_Deletable_Repository, Interface_Table_Migration_Repository {

	/**
	 * Entity class FQN.
	 *
	 * @var string
	 */
	protected $entity_class;

	/**
	 * Database session object.
	 *
	 * @var \wpdb
	 */
	protected $db;

	/**
	 * Returns unprefixed table name.
	 */
	abstract protected function get_unprefixed_table_name(): string;

	/**
	 * Returns full table name.
	 */
	public function get_table_name(): string {
		return $this->db->prefix . $this->get_unprefixed_table_name();
	}

	/**
	 * Get the name that is set to the original table during the migration.
	 * 
	 * @return string The name of the old table.
	 */
	public function get_legacy_table_name() {
		return $this->get_table_name() . '_legacy';
	}

	/**
	 * Constructor.
	 * 
	 * @throws \RuntimeException If database service not found.
	 */
	public function __construct() {
		$db = ServiceRegister::getService( \wpdb::class );
		if ( ! $db instanceof \wpdb ) {
			throw new \RuntimeException( 'Database service not found.' );
		}
		$this->db = $db;
	}

	/**
	 * Returns full class name.
	 *
	 * @return string Full class name.
	 */
	public static function getClassName() {
		return __CLASS__;
	}

	/**
	 * Sets repository entity
	 *
	 * @noinspection PhpDocMissingThrowsInspection
	 *
	 * @param string $entity_class Entity class.
	 * @return void
	 */
	public function setEntityClass( $entity_class ): void {
		$this->entity_class = $entity_class;
	}

	/**
	 * Executes select query.
	 *
	 * @param QueryFilter $filter Filter for query.
	 *
	 * @return Entity[] A list of found entities ot empty array.
	 * @throws QueryFilterInvalidParamException If filter condition is invalid.
	 */
	public function select( QueryFilter $filter = null ) {
		/**
		 * Entity object.
		 *
		 * @var Entity $entity
		 */
		$entity = new $this->entity_class();
		$type   = $entity->getConfig()->getType();
		
		$query = "SELECT * FROM {$this->get_table_name()} WHERE type = '$type' ";
		if ( $filter ) {
			$query .= $this->apply_query_filter( $filter, IndexHelper::mapFieldsToIndexes( $entity ) );
		}
		
		$raw_results = array();
		if ( $this->table_exists() ) {
			$raw_results = $this->db->get_results( $query, ARRAY_A );
			if ( ! is_array( $raw_results ) ) {
				$raw_results = array();
			}
		}
		if ( $this->table_exists( true ) ) {
			// If the legacy table exists the data may be there.
			$query              = str_replace( $this->get_table_name(), $this->get_legacy_table_name(), $query );
			$legacy_raw_results = $this->db->get_results( $query, ARRAY_A );
			if ( ! is_array( $legacy_raw_results ) ) {
				$legacy_raw_results = array();
			}
			$raw_results = array_merge( $raw_results, $legacy_raw_results );
		}

		return $this->translateToEntities( $raw_results );
	}

	/**
	 * Executes select query and returns first result.
	 *
	 * @param QueryFilter $filter Filter for query.
	 *
	 * @return Entity|null First found entity or NULL.
	 * @throws QueryFilterInvalidParamException If filter condition is invalid.
	 */
	public function selectOne( QueryFilter $filter = null ) {
		if ( ! $filter ) {
			$filter = new QueryFilter();
		}

		$filter->setLimit( 1 );
		$results = $this->select( $filter );

		return ! empty( $results ) ? $results[0] : null;
	}

	/**
	 * Executes insert query and returns ID of created entity. Entity will be updated with new ID.
	 *
	 * @param Entity $entity Entity to be saved.
	 *
	 * @return int Identifier of saved entity.
	 */
	public function save( Entity $entity ) {
		if ( ! $this->table_exists() ) {
			return -1;
		}

		if ( $entity->getId() ) {
			$this->update( $entity );

			return $entity->getId();
		}

		return $this->save_entity_to_storage( $entity );
	}

	/**
	 * Executes update query and returns success flag.
	 *
	 * @param Entity $entity Entity to be updated.
	 *
	 * @return bool TRUE if operation succeeded; otherwise, FALSE.
	 */
	public function update( Entity $entity ) {
		if ( ! $this->table_exists() ) {
			return false;
		}
		$item  = $this->prepare_entity_for_storage( $entity );
		$where = array( 'id' => $entity->getId() );
		
		// Check if entity wasn't already migrated and migrate it including the new data.
		if ( $this->table_exists( true ) && $this->entity_exists( $entity->getId(), true ) ) {
			if ( 1 !== $this->db->update( $this->get_legacy_table_name(), $item, $where ) ) {
				return false;
			}
			// Read from the legacy table.
			$raw_results = $this->db->get_results( "SELECT * FROM {$this->get_legacy_table_name()} WHERE id = {$entity->getId()} LIMIT 1;", ARRAY_A );
			if ( empty( $raw_results ) ) {
				return false;
			}
			$entity = $this->translateToEntities( $raw_results )[0] ?? null;
			if ( ! $entity ) {
				return false;
			}
			// Insert into the new table.
			$item = $this->prepare_entity_for_storage( $entity );
			if ( false !== $this->db->insert( $this->get_table_name(), $item ) ) {
				return false;
			}
			// Delete the row from the legacy table.
			$this->db->delete( $this->get_legacy_table_name(), $where );
			return true;
		}
		// Only one record should be updated.
		return 1 === $this->db->update( $this->get_table_name(), $item, $where );
	}

	/**
	 * Executes delete query and returns success flag.
	 *
	 * @param Entity $entity Entity to be deleted.
	 *
	 * @return bool TRUE if operation succeeded; otherwise, FALSE.
	 */
	public function delete( Entity $entity ) {
		$where   = array( 'id' => $entity->getId() );
		$deleted = false;
		if ( $this->table_exists() ) {
			$result  = $this->db->delete( $this->get_table_name(), $where );
			$deleted = ! empty( $result );
		}
		if ( $this->table_exists( true ) ) {
			// Delete from legacy table.
			$result  = $this->db->delete( $this->get_legacy_table_name(), $where );
			$deleted = $deleted || ! empty( $result );
		}
		return $deleted;
	}

	/**
	 * Counts records that match filter criteria.
	 *
	 * @param QueryFilter $filter Filter for query.
	 *
	 * @return int Number of records that match filter criteria.
	 * @throws QueryFilterInvalidParamException If filter condition is invalid.
	 */
	public function count( QueryFilter $filter = null ) {
		/**
		 * Entity object.
		 *
		 * @var Entity $entity
		 */
		$entity = new $this->entity_class();
		$type   = $entity->getConfig()->getType();

		$query = "SELECT COUNT(*) as `total` FROM {$this->get_table_name()} WHERE type = '$type' ";
		if ( $filter ) {
			$query .= $this->apply_query_filter( $filter, IndexHelper::mapFieldsToIndexes( $entity ) );
		}
		$count = 0;
		if ( $this->table_exists() ) {
			$result = $this->db->get_results( $query, ARRAY_A );
			$count += empty( $result[0]['total'] ) || ! is_numeric( $result[0]['total'] ) ? 0 : (int) $result[0]['total'];
		}
		if ( $this->table_exists( true ) ) {
			// If the legacy table exists, count the data there too.
			$query  = str_replace( $this->get_table_name(), $this->get_legacy_table_name(), $query );
			$result = $this->db->get_results( $query, ARRAY_A );
			$count += empty( $result[0]['total'] ) || ! is_numeric( $result[0]['total'] ) ? 0 : (int) $result[0]['total'];
		}
		return $count;
	}

	/**
	 * Escapes provided value.
	 *
	 * @param mixed $value Value to be escaped.
	 *
	 * @return string Escaped value.
	 */
	protected function escape( $value ) {
		return addslashes( strval( $value ) );
	}

	/**
	 * Checks if value exists and escapes it if it's not.
	 *
	 * @param mixed $value Value to be escaped.
	 *
	 * @return string Escaped value.
	 */
	protected function escape_value( $value ) {
		return null === $value ? 'NULL' : "'" . $this->escape( $value ) . "'";
	}

	/**
	 * Builds WHERE part of select query.
	 *
	 * @param mixed[]$filter_by Filter conditions in query.
	 *
	 * @return string Where condition.
	 */
	protected function build_condition( $filter_by ) {
		if ( empty( $filter_by ) ) {
			return '';
		}

		$where = array();
		foreach ( $filter_by as $key => $value ) {
			if ( null === $value ) {
				$where[] = "`$key` IS NULL";
			} else {
				$where[] = "`$key` = '" . $this->escape( $value ) . "'";
			}
		}

		return ' WHERE ' . implode( ' AND ', $where );
	}

	/**
	 * Converts filter value to index string representation.
	 *
	 * @param QueryCondition $condition Query condition.
	 *
	 * @return string|null Converted value.
	 */
	protected function convert_value( QueryCondition $condition ) {
		$value = IndexHelper::castFieldValue( $condition->getValue(), $condition->getValueType() );
		switch ( $condition->getValueType() ) {
			case 'string':
				$value = $this->escape_value( $condition->getValue() );
				break;
			case 'array':
				/** 
				 * Values
				 *
				 * @var mixed[] $values
				 */
				$values         = $condition->getValue();
				$escaped_values = array();
				foreach ( $values as $value ) {
					$escaped_values[] = is_string( $value ) ? $this->escape_value( $value ) : $value;
				}

				$value = '(' . implode( ', ', $escaped_values ) . ')';
				break;
			default:
				// 'integer', 'dateTime','boolean','double'
				$value = $this->escape_value( $value );
				break;
		}

		return $value;
	}

	/**
	 * Builds query filter part of the query.
	 *
	 * @param QueryFilter $filter Query filter object.
	 * @param mixed[]      $field_index_map Property to index number map.
	 *
	 * @return string Query filter addendum.
	 * @throws QueryFilterInvalidParamException If filter condition is invalid.
	 */
	protected function apply_query_filter( QueryFilter $filter, array $field_index_map = array() ) {
		$query      = '';
		$conditions = $filter->getConditions();
		if ( ! empty( $conditions ) ) {
			$query .= ' AND (';
			$first  = true;
			foreach ( $conditions as $condition ) {
				$this->validate_index_column( $condition->getColumn(), $field_index_map );
				$chain_op = $first ? '' : $condition->getChainOperator();
				$first    = false;
				$column   = 'id' === $condition->getColumn() ? 'id' : 'index_' . $field_index_map[ $condition->getColumn() ];
				$operator = $condition->getOperator();
				$query   .= " $chain_op $column $operator " . $this->convert_value( $condition );
			}

			$query .= ')';
		}

		if ( $filter->getOrderByColumn() ) {
			$this->validate_index_column( $filter->getOrderByColumn(), $field_index_map );
			$order_index = 'id' === $filter->getOrderByColumn() ? 'id' : 'index_' . $field_index_map[ $filter->getOrderByColumn() ];
			$query      .= " ORDER BY {$order_index} {$filter->getOrderDirection()}";
		}

		if ( $filter->getLimit() ) {
			$offset = (int) $filter->getOffset();
			$query .= " LIMIT {$offset}, {$filter->getLimit()}";
		}

		return $query;
	}

	/**
	 * Transforms raw database query rows to entities.
	 *
	 * @param mixed[]$result Raw database query result.
	 *
	 * @return Entity[] Array of transformed entities.
	 */
	protected function translateToEntities( array $result ) {
		/**
		 * Array of decoded entities.
		 *
		 * @var Entity[] $entities
		 */
		$entities = array();
		foreach ( $result as $item ) {
			/**
			 * Raw data.
			 *
			 * @var mixed[] $item
			 */
			if ( ! isset( $item['data'] ) || ! isset( $item['id'] ) ) {
				continue;
			}
			$data = (array) json_decode( strval( $item['data'] ), true );
			/**
			 * Entity object.
			 *
			 * @var Entity $entity
			 */
			$entity = isset( $data['class_name'] ) ? new $data['class_name']() : new $this->entity_class();
			$entity->inflate( $data );
			if ( is_numeric( $item['id'] ) ) {
				$entity->setId( (int) $item['id'] );
			}

			$entities[] = $entity;
		}

		return $entities;
	}

	/**
	 * Saves entity to system storage.
	 *
	 * @param Entity $entity Entity to be stored.
	 *
	 * @return int Inserted entity identifier.
	 */
	protected function save_entity_to_storage( Entity $entity ) {
		if ( ! $this->table_exists() ) {
			return -1;
		}
		$storage_item = $this->prepare_entity_for_storage( $entity );

		$this->db->insert( $this->get_table_name(), $storage_item );

		$insert_id = (int) $this->db->insert_id;
		$entity->setId( $insert_id );

		return $insert_id;
	}

	/**
	 * Prepares entity in format for storage.
	 *
	 * @param Entity $entity Entity to be stored.
	 *
	 * @return mixed[] Item prepared for storage.
	 */
	protected function prepare_entity_for_storage( Entity $entity ) {
		$indexes      = IndexHelper::transformFieldsToIndexes( $entity );
		$storage_item = array(
			'type'    => $entity->getConfig()->getType(),
			'index_1' => null,
			'index_2' => null,
			'index_3' => null,
			'index_4' => null,
			'index_5' => null,
			'index_6' => null,
			'index_7' => null,
			'data'    => \wp_json_encode( $entity->toArray() ),
		);

		if ( $entity->getId() ) {
			$storage_item['id'] = $entity->getId();
		}

		foreach ( $indexes as $index => $value ) {
			$storage_item[ 'index_' . $index ] = $value;
		}

		return $storage_item;
	}

	/**
	 * Validates if column can be filtered or sorted by.
	 *
	 * @param string $column Column name.
	 * @param mixed[] $index_map Index map.
	 *
	 * @throws QueryFilterInvalidParamException If filter condition is invalid.
	 * 
	 * @return void
	 */
	protected function validate_index_column( $column, array $index_map ) {
		if ( 'id' !== $column && ! array_key_exists( $column, $index_map ) ) {
			throw new QueryFilterInvalidParamException( esc_html__( 'Column is not id or index.', 'sequra' ) );
		}
	}

	/**
	 * Delete all the entities.
	 * 
	 * @param string|null $store_id Delete entities from this store. Passing null will delete all entities.
	 */
	public function delete_all( $store_id = null ): bool {
		$deleted = false;
		$sql     = 'DELETE FROM ' . \sanitize_text_field( $this->get_table_name() );
		if ( $store_id ) {
			$column = $this->get_store_id_index_column();
			if ( ! $column ) {
				return false;
			}
			$sql .= ' WHERE ' . \sanitize_text_field( $column ) . ' = ' . \sanitize_text_field( $store_id );
		}
		if ( $this->table_exists() ) {
			$result  = $this->db->query( $sql );
			$deleted = ! empty( $result );
		}
		if ( $this->table_exists( true ) ) {
			$result  = $this->db->query( str_replace( $this->get_table_name(), $this->get_legacy_table_name(), $sql ) );
			$deleted = $deleted || ! empty( $result );
		}
		return $deleted;
	}

	/**
	 * Get the index column name that stores the store ID.
	 * 
	 * @return string Index column name or empty string if not applicable.
	 */
	protected function get_store_id_index_column(): string {
		return 'index_1';
	}

	/**
	 * Check if table exists in the database.
	 * 
	 * @param boolean $legacy If true, check for legacy table.
	 */
	public function table_exists( $legacy = false ): bool {
		$table_name = \sanitize_text_field( ! $legacy ? $this->get_table_name() : $this->get_legacy_table_name() );
		return $this->db->get_var( "SHOW TABLES LIKE '{$table_name}'" ) === $table_name;
	}

	/**
	 * Remove entities that are older than a certain date or that are invalid.
	 * This performs a cleanup of the repository data.
	 */
	public function delete_old_and_invalid() {
		// Do nothing by default. Implement in child class if needed.
	}

	/**
	 * Check if the index exists.
	 * 
	 * @param Table_Index $index The index to check.
	 * @return bool True if the index exists, false otherwise.
	 */
	public function index_exists( $index ) {
		$index_name = \sanitize_key( $index->name );
		return ! empty( $this->db->get_col( "SHOW INDEX FROM `{$this->get_table_name()}` WHERE Key_name = '{$index_name}'" ) );
	}

	/**
	 * Add an index to the table.
	 * 
	 * @param Table_Index $index The index.
	 * @return bool True if the index was added or already exists, false otherwise.
	 */
	public function add_index( $index ) {
		if ( $this->index_exists( $index ) ) {
			return true;
		}
		$index_name = \sanitize_key( $index->name );
		$columns    = $index->columns;
		foreach ( $columns as &$column ) {
			$column = '`' . \sanitize_key( $column ) . '`';
		}
		$columns = implode( ',', $columns );
		return false !== $this->db->query( "ALTER TABLE `{$this->get_table_name()}` ADD INDEX `{$index_name}` ({$columns})" );
	}

	/**
	 * Execute the migration process one by one.
	 * This implementation is intended for migrations that don't change the table structure.
	 */
	public function migrate_next_row() {
		if ( ! $this->table_exists() || ! $this->table_exists( true ) ) {
			return;
		}
		$raw_results = $this->db->get_results( "SELECT * FROM {$this->get_legacy_table_name()} LIMIT 1;", ARRAY_A );
		if ( ! is_array( $raw_results ) ) {
			return;
		}

		$entity = $this->translateToEntities( $raw_results )[0] ?? null;
		if ( ! $entity ) {
			return;
		}
		// Check if entity already exists in the table.
		if ( $this->entity_exists( $entity->getId() ) ) {
			return;
		}

		$storage_item = $this->prepare_entity_for_storage( $entity );
		$result       = $this->db->insert( $this->get_table_name(), $storage_item );
		if ( false !== $result ) {
			// Delete the row from the legacy table.
			$this->db->delete( $this->get_legacy_table_name(), array( 'id' => $entity->getId() ) );
		}
	}

	/**
	 * Check if the migration process is complete.
	 * 
	 * @return bool True if the migration process is complete, false otherwise.
	 */
	public function is_migration_complete() {
		// Check if the legacy table exists.
		if ( $this->table_exists( true ) ) {
			return false;
		}
		// Check if the indexes exist.
		$indexes = $this->get_required_indexes();
		foreach ( $indexes as $index ) {
			if ( ! $this->index_exists( $index ) ) {
				return false;
			}
		}

		return true;
	}

	/**
	 * Get the SQL statement to create the table without the indexes definition.
	 * Resulting string should include an additional %s placeholder for the indexes.
	 * 
	 * @return string The SQL statement to create the table.
	 */
	protected function get_create_table_sql() {
		$charset_collate = $this->db->get_charset_collate();
		return "CREATE TABLE {$this->get_table_name()} (
			`id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			`type` VARCHAR(255),
			`index_1` VARCHAR(127),
			`index_2` VARCHAR(127),
			`index_3` VARCHAR(127),
			`index_4` VARCHAR(127),
			`index_5` VARCHAR(127),
			`index_6` VARCHAR(127),
			`index_7` VARCHAR(127),
			`data` LONGTEXT,
			PRIMARY KEY (id) %s) $charset_collate;";
	}

	/**
	 * Create the table if it doesn't exist.
	 * 
	 * @throws Exception If the table creation fails.
	 */
	public function create_table() {
		$indexes = array();
		foreach ( $this->get_required_indexes() as $index ) {
			$index_name = $index->name;
			$columns    = $index->columns;
			foreach ( $columns as &$column ) {
				$column = '`' . \sanitize_key( $column ) . '`';
			}
			$columns   = implode( ',', $columns );
			$indexes[] = "KEY `{$index_name}` ({$columns})";
		}
		$indexes = implode( ', ', $indexes );
		if ( ! empty( $indexes ) ) {
			$indexes = ', ' . $indexes;
		}

		$sql = sprintf( $this->get_create_table_sql(), $indexes );
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		$result = \dbDelta( $sql );
		if ( ! $this->table_exists() ) {
			throw new Exception( \esc_html( "SQL: $sql\nResult: " . implode( '. ', $result ) ) );
		}
	}

	/**
	 * Make sure that the required tables for the migration are created.
	 * 
	 * @throws Exception If cannot prepare tables for migration.
	 */
	public function prepare_tables_for_migration() {
		// Rename the table to legacy table if it doesn't exist.
		if ( ! $this->table_exists( true ) && false === $this->db->query( "RENAME TABLE {$this->get_table_name()} TO {$this->get_legacy_table_name()};" ) ) {
			throw new Exception( \esc_html( "Could not rename table {$this->get_table_name()} to {$this->get_legacy_table_name()}" ) );
		}

		if ( ! $this->table_exists() ) { 
			// Create the table if not exists.
			$this->create_table();
			
			// Add the auto-increment next value to the new table.
			$raw_id         = $this->db->get_var( "SELECT MAX(id) FROM {$this->get_legacy_table_name()};" );
			$auto_increment = null !== $raw_id && is_numeric( $raw_id ) ? (int) $raw_id + 1 : 1;
			if ( false === $this->db->query( "ALTER TABLE {$this->get_table_name()} AUTO_INCREMENT = {$auto_increment};" ) ) {
				throw new Exception( \esc_html( "Could not set auto-increment value for table {$this->get_table_name()} to {$auto_increment}" ) );
			}
		}
	}

	/**
	 * Evaluates if the legacy table should be removed and if so, removes it.
	 * 
	 * @return bool True if the legacy table was removed or did not exist, false otherwise.
	 */
	public function maybe_remove_legacy_table() {
		if ( ! $this->table_exists( true ) ) {
			return true;
		}
		$raw_results = $this->db->get_results( "SELECT 1 FROM `{$this->get_legacy_table_name()}` LIMIT 1;", ARRAY_A );
		if ( ! empty( $raw_results ) ) {
			// Legacy table is not empty, do not remove it.
			return false;
		}
		return false !== $this->db->query( "DROP TABLE IF EXISTS `{$this->get_legacy_table_name()}`;" );
	}

	/**
	 * Get a list of indexes that are required for the table.
	 * 
	 * @return Table_Index[] The list of indexes.
	 */
	public function get_required_indexes() { 
		return array(
			new Table_Index( $this->get_table_name() . '_type', array( 'type' ) ),
		);
	}

	/**
	 * Check if entity exists in the database.
	 * 
	 * @param int $id Entity ID.
	 * @param bool $legacy If true, check for legacy table.
	 * @return bool True if entity exists, false otherwise.
	 */
	protected function entity_exists( $id, $legacy = false ): bool {
		$table_name  = $legacy ? $this->get_legacy_table_name() : $this->get_table_name();
		$raw_results = $this->db->get_results( "SELECT 1 FROM `$table_name` WHERE id = {$id} LIMIT 1;", ARRAY_A );
		return ! empty( $raw_results );
	}
}
