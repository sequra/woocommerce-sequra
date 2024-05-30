<?php
/**
 * Wrapper to ease the read and write of configuration values.
 * Delegate to the ConfigurationManager instance to access the data in the database.
 *
 * @package SeQura\WC
 */

namespace SeQura\WC\Services\Core;

use SeQura\Core\BusinessLogic\Domain\GeneralSettings\Models\Category;
use SeQura\Core\BusinessLogic\Domain\Integration\Category\CategoryServiceInterface;
use WP_Term;

/**
 * Wrapper to ease the read and write of configuration values.
 */
class Category_Service implements CategoryServiceInterface {

	/**
	 * Returns all categories from a shop.
	 *
	 * @return Category[]
	 */
	public function getCategories(): array {
		$categories = array();

		$terms = get_terms(
			array(
				'taxonomy'   => 'product_cat',
				'hide_empty' => false,
			)
		);

		if ( is_wp_error( $terms ) ) {
			return $categories;
		}

		/**
		 * Terms
		 *
		 * @var WP_Term[] $terms
		 */
		foreach ( $terms as $term ) {
			$categories[] = new Category( strval( $term->term_id ), $this->get_category_name( $term->term_id, $terms ) );
		}

		usort(
			$categories,
			function ( $a, $b ) {
				return strcasecmp( $a->getName(), $b->getName() );
			}
		);

		return $categories;
	}

	/**
	 * Get the name of a category given its id.
	 *
	 * @param int $term_id The id of the category
	 * @param WP_Term[] $terms An array of categories
	 *
	 * @return string
	 */
	private function get_category_name( $term_id, $terms ) {
		$filtered = array_filter(
			$terms,
			function ( $cat ) use ( $term_id ) {
				return $term_id === $cat->term_id;
			}
		);
		/**
		 * Term
		 *
		 * @var WP_Term $term
		 */
		$term = array_shift( $filtered );
		if ( null === $term->parent ) {
			return '';
		}
		$category_name = $term->name;

		if ( ! empty( $term->parent ) ) {
			$parent_name = $this->get_category_name( $term->parent, $terms );
			if ( ! empty( $parent_name ) ) {
				$category_name = $parent_name . ' > ' . $category_name;
			}
		}

		return $category_name;
	}
}
