<?php
/**
 * REST Log Controller
 *
 * @package    SeQura/WC
 * @subpackage SeQura/WC/Controllers/Rest
 */

namespace SeQura\WC\Controllers\Rest;

use SeQura\Core\BusinessLogic\AdminAPI\AdminAPI;
use SeQura\Core\BusinessLogic\AdminAPI\TransactionLogs\Responses\TransactionLogsResponse;
use SeQura\Core\BusinessLogic\DataAccess\TransactionLog\Entities\TransactionLog;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;

/**
 * REST Log Controller
 */
class Log_REST_Controller extends REST_Controller {

	/**
	 * Constructor.
	 *
	 * @param string            $rest_namespace The namespace.
	 */
	public function __construct( $rest_namespace ) {
		$this->namespace = $rest_namespace;
		$this->rest_base = '/log';
	}

	/**
	 * Register the API endpoints.
	 */
	public function register_routes(): void {
		$store_id_args = array( self::PARAM_STORE_ID => $this->get_arg_string() );
		$store_id      = $this->url_param_pattern( self::PARAM_STORE_ID );

		$this->register_get( "{$store_id}", 'get_logs', $store_id_args );
	}

	/**
	 * GET logs.
	 * 
	 * @param WP_REST_Request $request The request.
	 * 
	 * @return WP_REST_Response|WP_Error
	 */
	public function get_logs( WP_REST_Request $request ) {
		$response = null;
		try {
			$page_num = max( 1, intval( $request->get_param( 'page' ) ) );
			$limit    = max( 10, intval( $request->get_param( 'limit' ) ) );
			
			// $response = AdminAPI::get()
			// ->transactionLogs( strval( $request->get_param( self::PARAM_STORE_ID ) ) )
			// ->getTransactionLogs( $page_num, $limit )
			// ->toArray();


			$response = ( new TransactionLogsResponse(
				false,
				array(
					TransactionLog::fromArray(
						array(
							'id'                 => 1,
							'storeId'            => '1',
							'merchantReference'  => 'dummy',
							'executionId'        => 1,
							'paymentMethod'      => 'sequra',
							'timestamp'          => 0,
							'eventCode'          => 'EVENT_CODE',
							'isSuccessful'       => true,
							'queueStatus'        => 'QUEUE_STATUS',
							'reason'             => 'REASON',
							'failureDescription' => 'FAILURE_DESCRIPTION',
							'sequraLink'         => 'sequraLink',
							'shopLink'           => 'shopLink',
						)
					),
				)
			) )->toArray();
		} catch ( \Throwable $e ) {
			// TODO: Log error.
			$response = new WP_Error( 'error', $e->getMessage() );
		}
		return rest_ensure_response( $response );
	}
}
