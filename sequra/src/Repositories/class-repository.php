<?php
/**
 * Shared repository functionality.
 *
 * @package    SeQura/WC
 * @subpackage SeQura/WC/Repositories
 */

namespace SeQura\WC\Repositories;

use SeQura\Core\Infrastructure\ORM\Entity;
use SeQura\Core\Infrastructure\ORM\Exceptions\QueryFilterInvalidParamException;
use SeQura\Core\Infrastructure\ORM\Interfaces\RepositoryInterface;
use SeQura\Core\Infrastructure\ORM\QueryFilter\QueryCondition;
use SeQura\Core\Infrastructure\ORM\QueryFilter\QueryFilter;
use SeQura\Core\Infrastructure\ORM\Utility\IndexHelper;
use SeQura\Core\Infrastructure\ServiceRegister;
use wpdb;

/**
 * Shared repository functionality.
 */
abstract class Repository implements RepositoryInterface, Interface_Deletable_Repository {

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
	protected function get_table_name(): string {
		return $this->db->prefix . $this->get_unprefixed_table_name();
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
	public function setEntityClass( $entity_class ) {
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
		if ( ! $this->table_exists() ) {
			return array();
		}

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

		$raw_results = $this->db->get_results( $query, ARRAY_A );
		if ( ! is_array( $raw_results ) ) {
			return array();
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

		$item = $this->prepare_entity_for_storage( $entity );

		// Only one record should be updated.
		return 1 === $this->db->update( $this->get_table_name(), $item, array( 'id' => $entity->getId() ) );
	}

	/**
	 * Executes delete query and returns success flag.
	 *
	 * @param Entity $entity Entity to be deleted.
	 *
	 * @return bool TRUE if operation succeeded; otherwise, FALSE.
	 */
	public function delete( Entity $entity ) {
		if ( ! $this->table_exists() ) {
			return false;
		}
		return false !== $this->db->delete( $this->get_table_name(), array( 'id' => $entity->getId() ) );
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
		if ( ! $this->table_exists() ) {
			return 0;
		}
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

		$result = $this->db->get_results( $query, ARRAY_A );

		return empty( $result[0]['total'] ) ? 0 : intval( $result[0]['total'] );
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
			$entity->setId( $item['id'] );

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
			'data'    => wp_json_encode( $entity->toArray() ),
		);

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
	 */
	public function delete_all(): bool {
		if ( ! $this->table_exists() ) {
			return false;
		}
		return false !== $this->db->query( 'DELETE FROM ' . sanitize_text_field( $this->get_table_name() ) );
	}

	/**
	 * Check if table exists in the database.
	 */
	protected function table_exists(): bool {
		$table_name = sanitize_text_field( $this->get_table_name() );
		return $this->db->get_var( "SHOW TABLES LIKE '{$table_name}'" ) === $table_name;
	}
}
