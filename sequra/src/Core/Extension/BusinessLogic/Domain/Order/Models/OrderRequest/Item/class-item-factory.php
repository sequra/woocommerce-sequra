<?php
/**
 * AbstractItemFactory implementation
 * 
 * @package SeQura\Core\BusinessLogic\Domain\Order\Models\OrderRequest\Item
 */

namespace SeQura\WC\Core\BusinessLogic\Domain\Order\Models\OrderRequest\Item;

use InvalidArgumentException;
use SeQura\Core\BusinessLogic\Domain\Order\Models\OrderRequest\Item\Item;
use SeQura\Core\BusinessLogic\Domain\Order\Models\OrderRequest\Item\ItemFactory;

/**
 * AbstractItemFactory implementation
 */
class Item_Factory extends ItemFactory {

	/**
	 * Create Item object from array.
	 *
	 * @param array<string, mixed> $itemData
	 *
	 * @throws InvalidArgumentException
	 */
	public function createFromArray( array $itemData ): Item {
		try {
			return parent::createFromArray( $itemData );
		} catch ( InvalidArgumentException $e ) {
			if ( Registration_Item::TYPE === ( $itemData['type'] ?? null ) ) {
				return Registration_Item::fromArray( $itemData );
			}
			throw $e;
		}
	}
}
