<?php
/**
 * Store Integration implementation
 *
 * @package SeQura\WC
 */

namespace SeQura\WC\Core\Implementation\BusinessLogic\Domain\Integration\StoreIntegration;

use SeQura\Core\BusinessLogic\Domain\StoreIntegration\Models\Capability;
use SeQura\Core\BusinessLogic\Domain\URL\Model\URL;

/**
 * Store Integration implementation
 */
class Store_Integration_Service implements Interface_Store_Integration_Service
{
    /**
     * Returns the REST endpoint.
     *
     * @return string
     */
    public function get_endpoint(): string {
        return 'store-integration';
    }

    /**
     * The base of this REST route.
     *
     * @return string
     */
    public function get_rest_base(): string {
        return '/webhook';
    }

	/**
     * Returns webhook url for integration.
     *
     * @return URL
     */
    public function getWebhookUrl(): URL{
        return new URL(\get_rest_url( null, "sequra/v1{$this->get_rest_base()}/{$this->get_endpoint()}" ));
    }

    /**
     * Returns an array of supported capabilities.
     *
     * @return Capability[]
     */
    public function getSupportedCapabilities(): array{
        return array(
            Capability::general(),
            Capability::orderStatus(),
            Capability::widget(),
            Capability::storeInfo(),
            Capability::advanced(),
        );
    }
}
