<?php
/**
 * Tests for the GeneralSettingsController class.
 * 
 * @package Sequra\WC\Tests\Core\Extension\BusinessLogic\AdminAPI\GeneralSettings
 */

namespace SeQura\WC\Tests\Core\Extension\BusinessLogic\AdminAPI\GeneralSettings;

require_once __DIR__ . '/../../../integration-core-test-autoload.php';

use Exception;
use SeQura\Core\BusinessLogic\AdminAPI\AdminAPI;
use SeQura\Core\BusinessLogic\AdminAPI\GeneralSettings\GeneralSettingsController;
use SeQura\Core\Tests\Infrastructure\Common\TestComponents\ORM\TestRepositoryRegistry;
use SeQura\WC\Core\Extension\BusinessLogic\AdminAPI\GeneralSettings\Requests\General_Settings_Request;
use SeQura\Core\BusinessLogic\DataAccess\GeneralSettings\Entities\GeneralSettings;
use SeQura\WC\Core\Extension\BusinessLogic\AdminAPI\GeneralSettings\Responses\General_Settings_Response;
use SeQura\Core\BusinessLogic\AdminAPI\GeneralSettings\Responses\SuccessfulGeneralSettingsResponse;
use SeQura\WC\Core\Extension\BusinessLogic\Domain\GeneralSettings\Models\General_Settings;
use SeQura\Core\BusinessLogic\Domain\GeneralSettings\RepositoryContracts\GeneralSettingsRepositoryInterface;
use SeQura\Core\BusinessLogic\Domain\GeneralSettings\Services\CategoryService;
use SeQura\Core\BusinessLogic\Domain\GeneralSettings\Services\GeneralSettingsService;
use SeQura\Core\BusinessLogic\Domain\Integration\Category\CategoryServiceInterface;
use SeQura\Core\BusinessLogic\Domain\Multistore\StoreContext;
use SeQura\Core\Tests\BusinessLogic\Common\BaseTestCase;
use SeQura\Core\Tests\BusinessLogic\Common\MockComponents\MockCategoryService;
use SeQura\Core\Tests\Infrastructure\Common\TestServiceRegister;
use SeQura\WC\Core\Extension\BusinessLogic\AdminAPI\GeneralSettings\General_Settings_Controller;
use SeQura\WC\Core\Extension\BusinessLogic\DataAccess\GeneralSettings\Repositories\General_Settings_Repository;

/**
 * Class GeneralSettingsControllerTest
 *
 * @package SeQura\Core\Tests\BusinessLogic\AdminAPI\GeneralSettings
 */
class GeneralSettingsControllerTest extends BaseTestCase {

	/**
	 * @var GeneralSettingsRepositoryInterface
	 */
	private $generalSettingsRepository;

	private $dummyGeneralSettings;
	private $dummyGeneralSettingsRequest;

	public function setUp(): void {
		parent::setUp();

		// Extend GeneralSettingsRepository.
		TestServiceRegister::registerService(
			GeneralSettingsRepositoryInterface::class,
			static function () {
				return new General_Settings_Repository(
					TestRepositoryRegistry::getRepository( GeneralSettings::getClassName() ),
					TestServiceRegister::getService( StoreContext::class )
				);
			}
		);

		// Extend GeneralSettingsController.
		TestServiceRegister::registerService(
			GeneralSettingsController::class,
			static function () {
				return new General_Settings_Controller(
					TestServiceRegister::getService( GeneralSettingsService::class ),
					TestServiceRegister::getService( CategoryService::class )
				);
			}
		);

		TestServiceRegister::registerService(
			CategoryServiceInterface::class,
			static function () {
				return new MockCategoryService();
			}
		);

		$this->generalSettingsRepository = TestServiceRegister::getService( GeneralSettingsRepositoryInterface::class );

		$this->dummyGeneralSettings = new General_Settings(
			true,
			true,
			array( 'address 1', 'address 2' ),
			array( 'sku 1', 'sku 2' ),
			array( '1', '2' ),
			false,
			true,
			true,
			'P1Y'
		);

		$this->dummyGeneralSettingsRequest = new General_Settings_Request(
			true,
			true,
			array( 'address 1', 'address 2' ),
			array( 'sku 1', 'sku 2' ),
			array( '1', '2' ),
			false,
			true,
			true,
			'P1Y'
		);
	}

	public function testIsGetGeneralSettingsResponseSuccessful(): void {
		// Arrange.
		$this->generalSettingsRepository->setGeneralSettings( $this->dummyGeneralSettings );

		// Act.
		$response = AdminAPI::get()->generalSettings( '1' )->getGeneralSettings();

		// Assert.
		self::assertTrue( $response->isSuccessful() );
	}

	/**
	 * @throws Exception
	 */
	public function testGetCountryConfigurationResponse(): void {
	
		$generalSettings = $this->dummyGeneralSettings;

		StoreContext::doWithStore( '1', array( $this->generalSettingsRepository, 'setGeneralSettings' ), array( $generalSettings ) );
		$expectedResponse = new General_Settings_Response( $generalSettings );

		$response = AdminAPI::get()->generalSettings( '1' )->getGeneralSettings();

		self::assertEquals( $expectedResponse, $response );
	}

	/**
	 * @throws Exception
	 */
	public function testGetGeneralSettingsResponseToArray(): void {
		$generalSettings = $this->dummyGeneralSettings;

		StoreContext::doWithStore( '1', array( $this->generalSettingsRepository, 'setGeneralSettings' ), array( $generalSettings ) );

		$response = AdminAPI::get()->generalSettings( '1' )->getGeneralSettings();

		self::assertEquals(
			array(
				'sendOrderReportsPeriodicallyToSeQura' => true,
				'showSeQuraCheckoutAsHostedPage'       => true,
				'allowedIPAddresses'                   => array( 'address 1', 'address 2' ),
				'excludedProducts'                     => array( 'sku 1', 'sku 2' ),
				'excludedCategories'                   => array( '1', '2' ),
				'enabledForServices'                   => false,
				'allowFirstServicePaymentDelay'        => true,
				'allowServiceRegItems'                 => true,
				'defaultServicesEndDate'               => 'P1Y',
			),
			$response->toArray() 
		);
	}

	public function testIsSaveResponseSuccessful(): void {
		$response = AdminAPI::get()->generalSettings( '1' )->saveGeneralSettings( $this->dummyGeneralSettingsRequest );

		self::assertTrue( $response->isSuccessful() );
	}

	public function testSaveResponse(): void {
		$response         = AdminAPI::get()->generalSettings( '1' )->saveGeneralSettings( $this->dummyGeneralSettingsRequest );
		$expectedResponse = new SuccessfulGeneralSettingsResponse();

		self::assertEquals( $expectedResponse, $response );
	}

	public function testSaveResponseToArray(): void {
		$response = AdminAPI::get()->generalSettings( '1' )->saveGeneralSettings( $this->dummyGeneralSettingsRequest );

		self::assertEquals( array(), $response->toArray() );
	}

	/**
	 * @throws Exception
	 */
	public function testIsUpdateResponseSuccessful(): void {
		StoreContext::doWithStore( '1', array( $this->generalSettingsRepository, 'setGeneralSettings' ), array( $this->dummyGeneralSettings ) );

		$generalSettingsRequest = new General_Settings_Request(
			false,
			false,
			array( 'address 3', 'address 4' ),
			array( 'sku 3', 'sku 4' ),
			array( '1', '2' ),
			true,
			false,
			false,
			'P2Y'
		);

		$response = AdminAPI::get()->generalSettings( '1' )->saveGeneralSettings( $generalSettingsRequest );

		self::assertTrue( $response->isSuccessful() );
	}

	/**
	 * @throws Exception
	 */
	public function testUpdateResponse(): void {
		StoreContext::doWithStore( '1', array( $this->generalSettingsRepository, 'setGeneralSettings' ), array( $this->dummyGeneralSettings ) );

		$generalSettingsRequest = new General_Settings_Request(
			false,
			false,
			array( 'address 3', 'address 4' ),
			array( 'sku 3', 'sku 4' ),
			array( '1', '2' ),
			true,
			false,
			false,
			'P2Y'
		);

		$response         = AdminAPI::get()->generalSettings( '1' )->saveGeneralSettings( $generalSettingsRequest );
		$expectedResponse = new SuccessfulGeneralSettingsResponse();

		self::assertEquals( $expectedResponse, $response );
	}

	/**
	 * @throws Exception
	 */
	public function testUpdateResponseToArray(): void {
		StoreContext::doWithStore( '1', array( $this->generalSettingsRepository, 'setGeneralSettings' ), array( $this->dummyGeneralSettings ) );

		$generalSettingsRequest = new General_Settings_Request(
			false,
			false,
			array( 'address 3', 'address 4' ),
			array( 'sku 3', 'sku 4' ),
			array( '1', '2' ),
			true,
			false,
			false,
			'P2Y'
		);

		$response = AdminAPI::get()->generalSettings( '1' )->saveGeneralSettings( $generalSettingsRequest );

		self::assertEquals( array(), $response->toArray() );
	}
}
