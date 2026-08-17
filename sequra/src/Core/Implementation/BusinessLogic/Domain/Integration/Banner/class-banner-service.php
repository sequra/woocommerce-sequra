<?php
/**
 * Banner Service implementation
 *
 * @package SeQura\WC
 */

namespace SeQura\WC\Core\Implementation\BusinessLogic\Domain\Integration\Banner;

use RuntimeException;
use SeQura\Core\BusinessLogic\Domain\Integration\Banner\BannerServiceInterface;

/**
 * Banner Service implementation.
 *
 * Banners are not offered on this platform. Reporting no display locations is what keeps the
 * rest of the contract unreachable: without one, no banner can ever be configured or stored.
 */
class Banner_Service implements BannerServiceInterface {

	/**
	 * Display locations where a banner can be shown.
	 *
	 * @return string[]
	 */
	public function getBannerDisplayLocations(): array {
		return array();
	}

	/**
	 * Store a banner image and return its public URL.
	 *
	 * @param string $country          Country the banner belongs to.
	 * @param string $display_location Location where the banner is shown.
	 * @param string $image_base64     Base64 encoded image.
	 *
	 * @throws RuntimeException No display location accepts a banner.
	 */
	public function saveBannerImage( string $country, string $display_location, string $image_base64 ): string {
		throw new RuntimeException( 'Banners are not supported.' );
	}

	/**
	 * Remove a stored banner image.
	 *
	 * @param string $country          Country the banner belongs to.
	 * @param string $display_location Location where the banner is shown.
	 */
	public function deleteBannerImage( string $country, string $display_location ): void {
		// Nothing can be stored, so there is nothing to remove.
	}

	/**
	 * Move a stored banner image to another display location and return its public URL.
	 *
	 * @param string $country              Country the banner belongs to.
	 * @param string $old_display_location Location the banner is moved from.
	 * @param string $new_display_location Location the banner is moved to.
	 *
	 * @throws RuntimeException No display location accepts a banner.
	 */
	public function changeBannerImageDisplayLocation(
		string $country,
		string $old_display_location,
		string $new_display_location
	): string {
		throw new RuntimeException( 'Banners are not supported.' );
	}
}
