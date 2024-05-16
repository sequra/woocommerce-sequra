<?php
/**
 * Helper for REST Controllers
 *
 * @package    SeQura/WC
 * @subpackage SeQura/WC/Controllers/Rest
 */

namespace SeQura\WC\Controllers\Rest;

/**
 * Helper for REST Controllers
 */
abstract class REST_Controller extends \WP_REST_Controller {

	/**
	 * Check if the current user can manage options.
	 */
	public function can_user_manage_options() {
		return user_can( get_current_user_id(), 'manage_options' );
	}

	/**
	 * Register GET endpoint.
	 * 
	 * @param string $endpoint The endpoint.
	 * @param string $fun       The function.
	 * @param string $permission_callback The permission callback.
	 */
	protected function register_get( $endpoint, $fun, $permission_callback = 'can_user_manage_options' ) {
		register_rest_route(
			$this->namespace,
			"{$this->rest_base}/$endpoint",
			array(
				'methods'             => \WP_REST_Server::READABLE,
				'callback'            => array( $this, $fun ),
				'permission_callback' => array( $this, $permission_callback ),
			), 
		);
	}
}
