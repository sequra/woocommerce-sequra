<?php
/**
 * Tests for the Migration_Install_420 class.
 *
 * @package    SeQura/WC
 * @subpackage SeQura/WC/Tests
 */

namespace SeQura\WC\Tests\Repositories\Migrations;

use SeQura\Core\BusinessLogic\Domain\AdvancedSettings\Models\AdvancedSettings;
use SeQura\Core\BusinessLogic\Domain\AdvancedSettings\Services\AdvancedSettingsService;
use SeQura\Core\BusinessLogic\Domain\Order\OrderStates;
use SeQura\Core\BusinessLogic\Domain\OrderStatusSettings\Models\OrderStatusMapping;
use SeQura\WC\Core\Extension\BusinessLogic\Domain\OrderStatusSettings\Services\Order_Status_Settings_Service;
use SeQura\WC\Repositories\Migrations\Migration_Install_420;
use WP_UnitTestCase;

// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQL.NotPrepared

class MigrationInstall420Test extends WP_UnitTestCase {

	/**
	 * Migration instance.
	 *
	 * @var Migration_Install_420
	 */
	private $migration;

	/**
	 * WordPress database object.
	 *
	 * @var \wpdb
	 */
	private $wpdb;

	/**
	 * Advanced settings service mock.
	 *
	 * @var AdvancedSettingsService&\PHPUnit\Framework\MockObject\MockObject
	 */
	private $advanced_settings_service;

	/**
	 * Order status settings service mock.
	 *
	 * @var Order_Status_Settings_Service&\PHPUnit\Framework\MockObject\MockObject
	 */
	private $order_status_settings_service;

	/**
	 * Entity table name.
	 *
	 * @var string
	 */
	private $entity_table;

	public function set_up(): void {
		global $wpdb;
		$this->wpdb         = $wpdb;
		$this->entity_table = $wpdb->prefix . 'sequra_entity';

		$this->advanced_settings_service = $this->getMockBuilder( AdvancedSettingsService::class )
			->disableOriginalConstructor()
			->getMock();

		$this->order_status_settings_service = $this->getMockBuilder( Order_Status_Settings_Service::class )
			->disableOriginalConstructor()
			->getMock();

		$this->order_status_settings_service
			->method( 'getOrderStatusSettings' )
			->willReturn(
				array(
					new OrderStatusMapping( OrderStates::STATE_APPROVED, 'wc-processing' ),
					new OrderStatusMapping( OrderStates::STATE_NEEDS_REVIEW, 'wc-on-hold' ),
					new OrderStatusMapping( OrderStates::STATE_CANCELLED, 'wc-cancelled' ),
					new OrderStatusMapping( OrderStates::STATE_SHIPPED, 'wc-completed' ),
				)
			);

		$this->migration = new Migration_Install_420(
			$this->wpdb,
			$this->advanced_settings_service,
			$this->order_status_settings_service
		);

		$this->clear_legacy_config_rows();
	}

	public function tear_down(): void {
		$this->clear_legacy_config_rows();
		remove_all_filters( 'sequra_shop_status_completed' );
		remove_all_filters( 'woocommerce_sequracheckout_sent_statuses' );
		parent::tear_down();
	}

	/**
	 * Remove legacy Configuration rows used by these tests.
	 */
	private function clear_legacy_config_rows(): void {
		$this->wpdb->delete(
			$this->entity_table,
			array(
				'type'    => 'Configuration',
				'index_1' => 'defaultLoggerEnabled',
			)
		);
		$this->wpdb->delete(
			$this->entity_table,
			array(
				'type'    => 'Configuration',
				'index_1' => 'minLogLevel',
			)
		);
	}

	/**
	 * Insert a legacy Configuration row into the entity table.
	 *
	 * @param string $index_1 The index_1 value (e.g. 'defaultLoggerEnabled').
	 * @param mixed  $value   The value to store.
	 */
	private function insert_legacy_config( string $index_1, $value ): void {
		$data = wp_json_encode( array( 'value' => $value ) );
		$this->wpdb->insert(
			$this->entity_table,
			array(
				'type'    => 'Configuration',
				'index_1' => $index_1,
				'data'    => $data,
			)
		);
	}

	public function testGetVersion_returns420(): void {
		$this->assertSame( '4.2.0', $this->migration->get_version() );
	}

	public function testRun_withLegacyLoggerConfig_migratesAdvancedSettings(): void {
		$this->insert_legacy_config( 'defaultLoggerEnabled', true );
		$this->insert_legacy_config( 'minLogLevel', 2 );

		$this->advanced_settings_service
			->expects( $this->once() )
			->method( 'setAdvancedSettings' )
			->with(
				$this->callback(
					function ( AdvancedSettings $settings ) {
						return true === $settings->isEnabled() && 2 === $settings->getLevel();
					}
				)
			);

		$this->migration->migrate_advanced_settings();
	}

	public function testRun_withNoLegacyConfig_skipsAdvancedSettingsMigration(): void {
		// No legacy rows inserted.
		$this->advanced_settings_service
			->expects( $this->never() )
			->method( 'setAdvancedSettings' );

		$this->migration->migrate_advanced_settings();
	}

	public function testRun_withLoggerEnabledTrue_setsIsEnabledTrue(): void {
		$this->insert_legacy_config( 'defaultLoggerEnabled', true );

		$captured = null;
		$this->advanced_settings_service
			->method( 'setAdvancedSettings' )
			->willReturnCallback(
				function ( AdvancedSettings $settings ) use ( &$captured ) {
					$captured = $settings;
				}
			);

		$this->migration->migrate_advanced_settings();

		$this->assertNotNull( $captured );
		$this->assertTrue( $captured->isEnabled() );
	}

	public function testRun_withLoggerEnabledFalse_setsIsEnabledFalse(): void {
		$this->insert_legacy_config( 'defaultLoggerEnabled', false );

		$captured = null;
		$this->advanced_settings_service
			->method( 'setAdvancedSettings' )
			->willReturnCallback(
				function ( AdvancedSettings $settings ) use ( &$captured ) {
					$captured = $settings;
				}
			);

		$this->migration->migrate_advanced_settings();

		$this->assertNotNull( $captured );
		$this->assertFalse( $captured->isEnabled() );
	}

	public function testRun_withCustomLogLevel_setsCorrectLevel(): void {
		$this->insert_legacy_config( 'defaultLoggerEnabled', true );
		$this->insert_legacy_config( 'minLogLevel', 5 );

		$captured = null;
		$this->advanced_settings_service
			->method( 'setAdvancedSettings' )
			->willReturnCallback(
				function ( AdvancedSettings $settings ) use ( &$captured ) {
					$captured = $settings;
				}
			);

		$this->migration->migrate_advanced_settings();

		$this->assertNotNull( $captured );
		$this->assertSame( 5, $captured->getLevel() );
	}

	public function testRun_deletesLegacyConfigRows(): void {
		$this->insert_legacy_config( 'defaultLoggerEnabled', true );
		$this->insert_legacy_config( 'minLogLevel', 3 );
		$this->advanced_settings_service->method( 'setAdvancedSettings' );

		$this->migration->migrate_advanced_settings();

		// Assert legacy rows are gone.
		$enabled_row = $this->wpdb->get_row(
			$this->wpdb->prepare(
				'SELECT `data` FROM ' . $this->entity_table . ' WHERE `type` = %s AND `index_1` = %s LIMIT 1',
				'Configuration',
				'defaultLoggerEnabled'
			)
		);
		$level_row   = $this->wpdb->get_row(
			$this->wpdb->prepare(
				'SELECT `data` FROM ' . $this->entity_table . ' WHERE `type` = %s AND `index_1` = %s LIMIT 1',
				'Configuration',
				'minLogLevel'
			)
		);

		$this->assertNull( $enabled_row );
		$this->assertNull( $level_row );
	}

	public function testRun_withExistingAdvancedSettings_skipsAdvancedSettingsMigration(): void {
		$this->insert_legacy_config( 'defaultLoggerEnabled', true );

		$this->advanced_settings_service
			->method( 'getAdvancedSettings' )
			->willReturn( new AdvancedSettings( true, 2 ) );

		$this->advanced_settings_service
			->expects( $this->never() )
			->method( 'setAdvancedSettings' );

		$this->migration->migrate_advanced_settings();
	}

	public function testMigrateShippedStatus_withNoFilter_doesNotSave(): void {
		$this->order_status_settings_service
			->expects( $this->never() )
			->method( 'saveOrderStatusSettings' );

		$this->migration->migrate_shipped_status_from_filter();
	}

	public function testMigrateShippedStatus_withDefaultFilterValue_doesNotSave(): void {
		add_filter(
			'sequra_shop_status_completed',
			static function () {
				return array( 'wc-completed' );
			}
		);

		$this->order_status_settings_service
			->expects( $this->never() )
			->method( 'saveOrderStatusSettings' );

		$this->migration->migrate_shipped_status_from_filter();
	}

	public function testMigrateShippedStatus_withCustomFilterValue_savesFirstValue(): void {
		add_filter(
			'sequra_shop_status_completed',
			static function () {
				return array( 'wc-custom-status', 'wc-another-status' );
			}
		);

		$captured = null;
		$this->order_status_settings_service
			->expects( $this->once() )
			->method( 'saveOrderStatusSettings' )
			->willReturnCallback(
				function ( array $mappings ) use ( &$captured ) {
					$captured = $mappings;
				}
			);

		$this->migration->migrate_shipped_status_from_filter();

		$this->assertNotNull( $captured );
		$shipped_mapping = null;
		foreach ( $captured as $mapping ) {
			if ( $mapping->getSequraStatus() === OrderStates::STATE_SHIPPED ) {
				$shipped_mapping = $mapping;
				break;
			}
		}
		$this->assertNotNull( $shipped_mapping );
		$this->assertSame( 'wc-custom-status', $shipped_mapping->getShopStatus() );
	}

	public function testMigrateShippedStatus_withCustomFilterValue_preservesOtherMappings(): void {
		add_filter(
			'sequra_shop_status_completed',
			static function () {
				return array( 'wc-custom-status' );
			}
		);

		$captured = null;
		$this->order_status_settings_service
			->method( 'saveOrderStatusSettings' )
			->willReturnCallback(
				function ( array $mappings ) use ( &$captured ) {
					$captured = $mappings;
				}
			);

		$this->migration->migrate_shipped_status_from_filter();

		$this->assertNotNull( $captured );
		$this->assertCount( 4, $captured );

		$statuses = array();
		foreach ( $captured as $mapping ) {
			$statuses[ $mapping->getSequraStatus() ] = $mapping->getShopStatus();
		}

		$this->assertSame( 'wc-processing', $statuses[ OrderStates::STATE_APPROVED ] );
		$this->assertSame( 'wc-on-hold', $statuses[ OrderStates::STATE_NEEDS_REVIEW ] );
		$this->assertSame( 'wc-cancelled', $statuses[ OrderStates::STATE_CANCELLED ] );
		$this->assertSame( 'wc-custom-status', $statuses[ OrderStates::STATE_SHIPPED ] );
	}

	public function testMigrateShippedStatus_withEmptyArrayFilter_doesNotSave(): void {
		add_filter(
			'sequra_shop_status_completed',
			static function () {
				return array();
			}
		);

		$this->order_status_settings_service
			->expects( $this->never() )
			->method( 'saveOrderStatusSettings' );

		$this->migration->migrate_shipped_status_from_filter();
	}

	public function testMigrateShippedStatus_withWhitespaceOnlyStatus_doesNotSave(): void {
		add_filter(
			'sequra_shop_status_completed',
			static function () {
				return array( '   ' );
			}
		);

		$this->order_status_settings_service
			->expects( $this->never() )
			->method( 'saveOrderStatusSettings' );

		$this->migration->migrate_shipped_status_from_filter();
	}

	public function testMigrateShippedStatus_withLegacyFilter_savesFirstValue(): void {
		add_filter(
			'woocommerce_sequracheckout_sent_statuses',
			static function () {
				return array( 'wc-legacy-status' );
			}
		);

		$captured = null;
		$this->order_status_settings_service
			->expects( $this->once() )
			->method( 'saveOrderStatusSettings' )
			->willReturnCallback(
				function ( array $mappings ) use ( &$captured ) {
					$captured = $mappings;
				}
			);

		$this->migration->migrate_shipped_status_from_filter();

		$this->assertNotNull( $captured );
		$shipped_mapping = null;
		foreach ( $captured as $mapping ) {
			if ( $mapping->getSequraStatus() === OrderStates::STATE_SHIPPED ) {
				$shipped_mapping = $mapping;
				break;
			}
		}
		$this->assertNotNull( $shipped_mapping );
		$this->assertSame( 'wc-legacy-status', $shipped_mapping->getShopStatus() );
	}
}
