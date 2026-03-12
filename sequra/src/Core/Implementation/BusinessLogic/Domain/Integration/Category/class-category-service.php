<?php
/**
 * Implementation of the Category service.
 *
 * @package SeQura\WC
 */

namespace SeQura\WC\Core\Implementation\BusinessLogic\Domain\Integration\Category;

use SeQura\Core\BusinessLogic\Domain\GeneralSettings\Models\Category;
use SeQura\Core\BusinessLogic\Domain\Integration\Category\CategoryServiceInterface;
use WP_Term;

/**
 * Implementation of the Category service.
 */
class Category_Service implements CategoryServiceInterface {

	/**
	 * Returns all categories from a shop.
	 *
	 * @param ?int $page Page number for pagination (starting from 1).
	 * @param ?int $limit Number of categories per page.
	 * @param ?string $search Search term to filter categories by name.
	 *
	 * @return Category[]
	 */
	public function getCategories( ?int $page = null, ?int $limit = null, ?string $search = null ): array {
		$categories = array();

		$args = array(
			'taxonomy'   => 'product_cat',
			'hide_empty' => false,
		);

		// Apply search filter if provided.
		if ( ! empty( $search ) ) {
			$args['search'] = $search;
		}

		// Apply pagination if both page and limit are provided.
		if ( null !== $page && null !== $limit && $page > 0 && $limit > 0 ) {
			$args['number'] = $limit;
			$args['offset'] = ( $page - 1 ) * $limit;
		}

		$terms = \get_terms( $args );

		if ( \is_wp_error( $terms ) ) {
			return $categories;
		}

		/**
		 * Terms
		 *
		 * @var WP_Term[] $terms
		 */
		foreach ( $terms as $term ) {
			$categories[] = new Category( strval( $term->term_id ), $this->get_category_name( $term ) );
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
	 * Returns categories by their IDs.
	 *
	 * @param string[] $ids
	 *
	 * @return Category[]
	 */
	public function getCategoriesByIds( array $ids ): array {
		if ( empty( $ids ) ) {
			return array();
		}

		$categories = array();
		$int_ids    = array_map( 'intval', $ids );

		$terms = \get_terms(
			array(
				'taxonomy'   => 'product_cat',
				'hide_empty' => false,
				'include'    => $int_ids,
			)
		);

		if ( \is_wp_error( $terms ) ) {
			return $categories;
		}

		/**
		 * Terms
		 *
		 * @var WP_Term[] $terms
		 */
		foreach ( $terms as $term ) {
			$categories[] = new Category( strval( $term->term_id ), $this->get_category_name( $term ) );
		}

		return $categories;
	}

	/**
	 * Get the name of a category given its term object or ID.
	 *
	 * @param WP_Term|int $term The term object or term ID
	 *
	 * @return string
	 */
	private function get_category_name( $term ): string {
		static $cache = array();

		// If term is an ID, fetch the term object.
		if ( \is_int( $term ) ) {
			$term_id = $term;
			// Return cached result if available.
			if ( isset( $cache[ $term_id ] ) ) {
				return $cache[ $term_id ];
			}

			$term = \get_term( $term_id, 'product_cat' );
			if ( \is_wp_error( $term ) || null === $term ) {
				return '';
			}
		} elseif ( isset( $cache[ $term->term_id ] ) ) {
			// Return cached result if available.
			return $cache[ $term->term_id ];
		}

		$category_name = $term->name;

		if ( ! empty( $term->parent ) ) {
			// Fetch parent term only if needed.
			$parent_term = \get_term( $term->parent, 'product_cat' );
			if ( ! \is_wp_error( $parent_term ) && null !== $parent_term ) {
				$parent_name = $this->get_category_name( $parent_term );
				if ( ! empty( $parent_name ) ) {
					$category_name = $parent_name . ' > ' . $category_name;
				}
			}
		}

		// Cache the result.
		$cache[ $term->term_id ] = $category_name;

		return $category_name;
	}
}
