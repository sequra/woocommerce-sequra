<?php
/**
 * Tests for the Store_Integration_REST_Controller class.
 *
 * @package    SeQura/WC
 * @subpackage SeQura/WC/Tests
 */

namespace SeQura\WC\Tests\Controllers\Rest;

use SeQura\Core\BusinessLogic\ConfigurationWebhookAPI\Controller\ConfigurationWebhookController;
use SeQura\Core\BusinessLogic\ConfigurationWebhookAPI\Responses\SuccessResponse;
use SeQura\Core\BusinessLogic\ConfigurationWebhookAPI\Responses\TopicMissingErrorResponse;
use SeQura\Core\BusinessLogic\Domain\Multistore\StoreContext;
use SeQura\Core\BusinessLogic\Domain\Translations\Model\TranslatableLabel;
use SeQura\Core\BusinessLogic\Domain\Webhook\Exceptions\WebhookSignatureValidationFailed;
use SeQura\Core\Infrastructure\ServiceRegister;
use SeQura\Core\Infrastructure\Utility\RegexProvider;
use SeQura\WC\Controllers\Rest\Store_Integration_REST_Controller;
use SeQura\WC\Core\Implementation\BusinessLogic\Domain\Integration\StoreIntegration\Interface_Store_Integration_Service;
use SeQura\WC\Services\Log\Interface_Logger_Service;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;
use WP_UnitTestCase;

class StoreIntegrationRestControllerTest extends WP_UnitTestCase {

	/**
	 * @var Interface_Logger_Service&\PHPUnit\Framework\MockObject\MockObject
	 */
	private $logger;

	/**
	 * @var RegexProvider&\PHPUnit\Framework\MockObject\MockObject
	 */
	private $regex;

	/**
	 * @var Interface_Store_Integration_Service&\PHPUnit\Framework\MockObject\MockObject
	 */
	private $store_integration_service;

	/**
	 * @var StoreContext&\PHPUnit\Framework\MockObject\MockObject
	 */
	private $store_context;

	/**
	 * @var Store_Integration_REST_Controller
	 */
	private $controller;

	public function set_up(): void {
		parent::set_up();

		$this->logger = $this->createMock( Interface_Logger_Service::class );
		$this->regex  = $this->getMockBuilder( RegexProvider::class )
			->disableOriginalConstructor()
			->getMock();

		$this->store_integration_service = $this->createMock( Interface_Store_Integration_Service::class );
		$this->store_context             = $this->getMockBuilder( StoreContext::class )
			->disableOriginalConstructor()
			->getMock();

		$this->store_integration_service->method( 'get_rest_base' )->willReturn( '/webhook' );

		$this->controller = new Store_Integration_REST_Controller(
			'sequra/v1',
			$this->logger,
			$this->regex,
			$this->store_integration_service,
			$this->store_context
		);
	}

	public function testCheckPermissions_alwaysReturnsTrue(): void {
		$this->assertTrue( $this->controller->check_permissions() );
	}

	public function testConstructor_setsRestBaseFromService(): void {
		$reflection = new \ReflectionProperty( Store_Integration_REST_Controller::class, 'rest_base' );
		$reflection->setAccessible( true );

		$rest_base = $reflection->getValue( $this->controller );

		$this->assertSame( '/webhook', $rest_base );
	}

	public function testConstructor_setsNamespace(): void {
		$reflection = new \ReflectionProperty( Store_Integration_REST_Controller::class, 'namespace' );
		$reflection->setAccessible( true );

		$namespace = $reflection->getValue( $this->controller );

		$this->assertSame( 'sequra/v1', $namespace );
	}

	public function testHandlePost_nullPayload_coercesToEmptyArrayAndMapsTopicMissingTo400(): void {
		$webhook_controller = $this->register_webhook_controller_mock();
		$webhook_controller->expects( $this->once() )
			->method( 'handleRequest' )
			->with( 'sig', array() )
			->willReturn( new TopicMissingErrorResponse() );

		$this->store_context->method( 'getStoreId' )->willReturn( '1' );

		$request = $this->build_request( null, 'sig' );

		$result = $this->controller->handle_post( $request );

		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertSame( array( 'status' => 400 ), $result->get_error_data() );
	}

	public function testHandlePost_nonArrayJsonPayload_coercesToEmptyArrayAndMapsTopicMissingTo400(): void {
		$webhook_controller = $this->register_webhook_controller_mock();
		$webhook_controller->expects( $this->once() )
			->method( 'handleRequest' )
			->with( 'sig', array() )
			->willReturn( new TopicMissingErrorResponse() );

		$this->store_context->method( 'getStoreId' )->willReturn( '1' );

		$request = $this->build_request( 42, 'sig' );

		$result = $this->controller->handle_post( $request );

		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertSame( array( 'status' => 400 ), $result->get_error_data() );
	}

	public function testHandlePost_payloadWithoutTopic_returnsTopicMissingMappedTo400(): void {
		$webhook_controller = $this->register_webhook_controller_mock();
		$webhook_controller->expects( $this->once() )
			->method( 'handleRequest' )
			->with( 'sig', array( 'foo' => 'bar' ) )
			->willReturn( new TopicMissingErrorResponse() );

		$this->store_context->method( 'getStoreId' )->willReturn( '1' );

		$request = $this->build_request( array( 'foo' => 'bar' ), 'sig' );

		$result = $this->controller->handle_post( $request );

		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertSame( array( 'status' => 400 ), $result->get_error_data() );
	}

	public function testHandlePost_validPayload_returnsSuccessResponse(): void {
		$webhook_controller = $this->register_webhook_controller_mock();
		$webhook_controller->method( 'handleRequest' )->willReturn( new SuccessResponse() );

		$this->store_context->method( 'getStoreId' )->willReturn( '1' );

		$request = $this->build_request( array( 'topic' => 'order.created' ), 'sig' );

		$result = $this->controller->handle_post( $request );

		$this->assertNotInstanceOf( WP_Error::class, $result );
		$this->assertInstanceOf( WP_REST_Response::class, $result );
	}

	public function testHandlePost_signatureFailure_doesNotApply400Mapping(): void {
		$webhook_controller = $this->register_webhook_controller_mock();
		$webhook_controller->method( 'handleRequest' )
			->willThrowException(
				new WebhookSignatureValidationFailed(
					new TranslatableLabel( 'Webhook signature validation failed.', 'webhook.signature.validation.failed' )
				)
			);

		$this->store_context->method( 'getStoreId' )->willReturn( '1' );

		$request = $this->build_request( null, 'bad-sig' );

		$result = $this->controller->handle_post( $request );

		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertNotSame( array( 'status' => 400 ), $result->get_error_data() );
	}

	/**
	 * Replace the bootstrapped ConfigurationWebhookController with a mock so the Aspects proxy
	 * resolves to it. Returns the mock so individual tests can configure expectations.
	 *
	 * @return ConfigurationWebhookController&\PHPUnit\Framework\MockObject\MockObject
	 */
	private function register_webhook_controller_mock() {
		$mock = $this->getMockBuilder( ConfigurationWebhookController::class )
			->disableOriginalConstructor()
			->getMock();
		ServiceRegister::registerService(
			ConfigurationWebhookController::class,
			static function () use ( $mock ) {
				return $mock;
			}
		);
		return $mock;
	}

	/**
	 * Build a WP_REST_Request mock that returns the given JSON payload and signature.
	 *
	 * @param mixed $json_params Value returned from get_json_params().
	 * @param string $signature Value returned from get_param('signature').
	 *
	 * @return WP_REST_Request&\PHPUnit\Framework\MockObject\MockObject
	 */
	private function build_request( $json_params, string $signature ) {
		$request = $this->getMockBuilder( WP_REST_Request::class )
			->disableOriginalConstructor()
			->getMock();
		$request->method( 'get_param' )->willReturnCallback(
			static function ( $key ) use ( $signature ) {
				return 'signature' === $key ? $signature : null;
			}
		);
		$request->method( 'get_json_params' )->willReturn( $json_params );
		return $request;
	}
}
