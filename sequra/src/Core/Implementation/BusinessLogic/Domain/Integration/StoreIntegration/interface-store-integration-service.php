<?php
/**
 * Store Integration Extension
 *
 * @package SeQura\WC
 */

namespace SeQura\WC\Core\Implementation\BusinessLogic\Domain\Integration\StoreIntegration;

use SeQura\Core\BusinessLogic\Domain\Integration\StoreIntegration\StoreIntegrationServiceInterface;

/**
 * Store Integration Extension
 */
interface Interface_Store_Integration_Service extends StoreIntegrationServiceInterface
{
    /**
     * Returns the REST endpoint.
     *
     * @return string
     */
    public function get_endpoint(): string;

    /**
     * The base of this REST route.
     *
     * @return string
     */
    public function get_rest_base(): string;
}
