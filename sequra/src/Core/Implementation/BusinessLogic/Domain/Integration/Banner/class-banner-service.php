<?php
/**
 * Implementation of the Banner integration service.
 *
 * @package SeQura\WC
 */

namespace SeQura\WC\Core\Implementation\BusinessLogic\Domain\Integration\Banner;

use SeQura\Core\BusinessLogic\Domain\Integration\Banner\BannerServiceInterface;

/**
 * Banner integration service.
 *
 * Bound because integration-core (>= 5.6) requires this contract, so shared flows that touch banner
 * data — notably the disconnect cleanup, which resolves the core BannerSettingsService — do not
 * fail with a "service not registered" error. WooCommerce does not host seQura banner images yet,
 * so this is an interim no-op: it advertises no display locations and performs no image storage.
 * Replace with a real implementation when the banner feature is wired into the storefront.
 */
class Banner_Service implements BannerServiceInterface {

	/**
	 * Returns available banner display locations in integration.
	 *
	 * @return string[]
	 */
	public function getBannerDisplayLocations(): array {
		return array();
	}

	/**
	 * Persists the banner image on the integration server and returns its public URL.
	 *
	 * @param string $country          Country code.
	 * @param string $display_location Display location key.
	 * @param string $image_base64     Raw Base64-encoded image content.
	 *
	 * @return string
	 */
	public function saveBannerImage( string $country, string $display_location, string $image_base64 ): string {
		return '';
	}

	/**
	 * Removes the banner image associated with the given country and display location.
	 *
	 * @param string $country          Country code.
	 * @param string $display_location Display location key.
	 *
	 * @return void
	 */
	public function deleteBannerImage( string $country, string $display_location ): void {
	}

	/**
	 * Relocates the banner image for the given country from one display location to another.
	 *
	 * @param string $country              Country code.
	 * @param string $old_display_location Current display location key.
	 * @param string $new_display_location Target display location key.
	 *
	 * @return string
	 */
	public function changeBannerImageDisplayLocation( string $country, string $old_display_location, string $new_display_location ): string {
		return '';
	}
}
