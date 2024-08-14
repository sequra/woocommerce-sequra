<?php
/**
 * Implements the Assets service.
 *
 * @package    SeQura/WC
 * @subpackage SeQura/WC/Services
 */
namespace SeQura\WC\Services\Assets;

/**
 * Assets
*/
class Assets implements Interface_Assets {
	/**
	 * Get the URI of a resource in the CDN. If the environment is not set, it returns an empty string.
	*/
	public function get_cdn_resource_uri( ?string $env, string $resource ): string {
		return !$env ? '' : "https://{$env}.sequracdn.com/assets/{$resource}";
	}
 }
 