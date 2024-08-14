<?php
/**
 * Assets interface
 *
 * @package    SeQura/WC
 * @subpackage SeQura/WC/Services
 */

namespace SeQura\WC\Services\Assets;

/**
 * Assets interface
 */
interface Interface_Assets {

	/**
	 * Get the URI of a resource in the CDN.
	 */
	public function get_cdn_resource_uri( ?string $env, string $resource ): string;
}
