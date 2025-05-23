<?php
/**
 * Queue item repository.
 *
 * @package    SeQura/WC
 * @subpackage SeQura/WC/Repositories
 */

namespace SeQura\WC\Repositories;

use SeQura\Core\Infrastructure\ORM\Entity;
use SeQura\Core\Infrastructure\ORM\Interfaces\QueueItemRepository;
use SeQura\Core\Infrastructure\ORM\QueryFilter\QueryFilter;
use SeQura\Core\Infrastructure\ORM\Utility\IndexHelper;
use SeQura\Core\Infrastructure\TaskExecution\Exceptions\QueueItemSaveException;
use SeQura\Core\Infrastructure\TaskExecution\Interfaces\Priority;
use SeQura\Core\Infrastructure\TaskExecution\QueueItem;
use SeQura\WC\Dto\Table_Index;

/**
 * Queue item repository.
 */
class Queue_Item_Repository extends Repository implements QueueItemRepository {

	/**
	 * Returns unprefixed table name.
	 */
	protected function get_unprefixed_table_name(): string {
		return 'sequra_queue';
	}

	/**
	 * Finds list of earliest queued queue items per queue. Following list of criteria for searching must be satisfied:
	 *      - Queue must be without already running queue items
	 *      - For one queue only one (oldest queued) item should be returned
	 *
	 * @param int $priority Queue item priority.
	 * @param int $limit Result set limit. By default max 10 earliest queue items will be returned.
	 *
	 * @return Entity[] Found queue item list
	 */
	public function findOldestQueuedItems( $priority, $limit = 10 ) {
		if ( ! $this->table_exists() || Priority::NORMAL !== $priority ) {
			return array();
		}

		/**
		 * Entity object.
		 *
		 * @var Entity $entity
		 */
		$entity    = new $this->entity_class();
		$type      = $this->escape_value( $entity->getConfig()->getType() );
		$index_map = IndexHelper::mapFieldsToIndexes( $entity );

		$status_index     = 'index_' . $index_map['status'];
		$queue_name_index = 'index_' . $index_map['queueName'];

		$running_queues_query = "SELECT $queue_name_index FROM `{$this->get_table_name()}` q2 WHERE q2.`$status_index` = '"
								. QueueItem::IN_PROGRESS . "' AND q2.`type` = $type";

		$sql = "SELECT queueTable.* 
	            FROM (
	                 SELECT $queue_name_index, MIN(id) AS id
	                 FROM `{$this->get_table_name()}` AS q
	                 WHERE q.`type` = $type AND q.`$status_index` = '" . QueueItem::QUEUED . "' AND q.`$queue_name_index` NOT IN ($running_queues_query)
	                 GROUP BY `$queue_name_index` LIMIT $limit
	            ) AS queueView  
	            INNER JOIN `{$this->get_table_name()}` as queueTable
	            ON queueView.id = queueTable.id";

		$result = $this->db->get_results( $sql, ARRAY_A );
		if ( ! is_array( $result ) ) {
			$result = array();
		}
		$pending_items = $limit - count( $result );

		if ( $pending_items > 0 && $this->table_exists( true ) ) {
			$legacy_result = $this->db->get_results( str_replace( $this->get_table_name(), $this->get_legacy_table_name(), $sql ), ARRAY_A );
			if ( is_array( $legacy_result ) ) {
				$length = count( $legacy_result );
				for ( $i = 0; $i < $length && $pending_items > 0; $i++ ) {
					$result[] = $legacy_result[ $i ];
					--$pending_items;
				}
			}
		}

		return $this->translateToEntities( $result );
	}

	/**
	 * Creates or updates given queue item. If queue item id is not set, new queue item will be created otherwise update will be performed.
	 *
	 * @param QueueItem $queue_item Item to save.
	 * @param mixed[]     $additional_where List of key/value pairs that must be satisfied upon saving queue item.
	 *                                    Key is queue item property and value is condition value for that property.
	 *
	 * @return int Id of saved queue item.
	 * @throws QueueItemSaveException If queue item could not be saved.
	 */
	public function saveWithCondition( QueueItem $queue_item, array $additional_where = array() ): int {
		if ( ! $this->table_exists() ) {
			return -1;
		}
		$item_id = null;
		try {
			$queue_item_id = $queue_item->getId();
			if ( null === $queue_item_id || $queue_item_id <= 0 ) {
				$item_id = $this->save( $queue_item );
			} else {
				$filter = $this->build_query_filter(
					array_merge( $additional_where, array( 'id' => $queue_item->getId() ) )
				);

				if ( null === $this->selectOne( $filter ) ) {
					throw new QueueItemSaveException( \esc_html( 'Failed to save queue item, update condition(s) not met.' ) );
				}
				$item_id = $this->save( $queue_item );
			}
		} catch ( \Exception $exception ) {
			throw new QueueItemSaveException(
				\esc_html( 'Failed to save queue item with id: ' . $item_id ),
				0,
				$exception // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
			);
		}

		return $item_id;
	}

	/**
	 * Updates status of a batch of queue items.
	 *
	 * @param mixed[] $ids
	 * @param string $status
	 *
	 * @return void
	 */
	public function batchStatusUpdate( array $ids, $status ): void {
		// Not used in this implementation.
	}

	/**
	 * Builds query filter from conditions array.
	 *
	 * @noinspection PhpDocMissingThrowsInspection
	 *
	 * @param mixed[]$conditions Array of conditions.
	 *
	 * @return QueryFilter Query filter object.
	 */
	private function build_query_filter( array $conditions ) {
		$filter = new QueryFilter();
		$filter->setOffset( 0 );
		$filter->setLimit( 1 );
		foreach ( $conditions as $column => $value ) {
			if ( null === $value ) {
				$filter->where( $column, 'IS' );
			} else {
				$filter->where( $column, '=', $value );
			}
		}

		return $filter;
	}

	/**
	 * Get the index column name that stores the store ID.
	 * 
	 * @return string Index column name or empty string if not applicable.
	 */
	protected function get_store_id_index_column(): string {
		return '';
	}

	/**
	 * Get a list of indexes that are required for the table.
	 * 
	 * @return Table_Index[] The list of indexes.
	 */
	public function get_required_indexes() {
		return array_merge(
			parent::get_required_indexes(),
			array(
				new Table_Index( $this->get_table_name() . '_type_index_1', array( 'type', 'index_1' ) ),
				new Table_Index( $this->get_table_name() . '_type_index_2', array( 'type', 'index_2' ) ),
				new Table_Index( $this->get_table_name() . '_type_index_3', array( 'type', 'index_3' ) ),
				new Table_Index( $this->get_table_name() . '_type_index_4', array( 'type', 'index_4' ) ),
			)
		);
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
			`id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			`type` VARCHAR(255),
			`index_1` VARCHAR(127),
			`index_2` VARCHAR(127),
			`index_3` VARCHAR(127),
			`index_4` VARCHAR(127),
			`index_5` VARCHAR(127),
			`index_6` BIGINT UNSIGNED,
			`index_7` BIGINT UNSIGNED,
			`index_8` BIGINT UNSIGNED,
			`index_9` BIGINT UNSIGNED,
			`data` LONGTEXT,
			PRIMARY KEY (id) %s) $charset_collate;";
	}
}
