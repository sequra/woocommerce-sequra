<?php
/**
 * Extends the WidgetSettingsRepository class.
 * 
 * @package SeQura\WC
 */

namespace SeQura\WC\Core\Extension\BusinessLogic\DataAccess\PromotionalWidgets\Repositories;

use SeQura\Core\BusinessLogic\DataAccess\PromotionalWidgets\Repositories\WidgetSettingsRepository;

use SeQura\WC\Core\Extension\BusinessLogic\DataAccess\PromotionalWidgets\Entities\Widget_Settings;
use SeQura\Core\BusinessLogic\Domain\PromotionalWidgets\Models\WidgetSettings;

/**
 * Extends the WidgetSettingsRepository class.
 */
class Widget_Settings_Repository extends WidgetSettingsRepository {

	/**
	 * Set widget settings.
	 */
	public function setWidgetSettings( WidgetSettings $settings ): void {
        // phpcs:disable WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
		$existing_widget_settings = $this->getWidgetSettingsEntity();

		if ( $existing_widget_settings ) {
			if ( $existing_widget_settings instanceof Widget_Settings ) {
				$existing_widget_settings->setWidgetSettings( $settings );
				$existing_widget_settings->setStoreId( $this->storeContext->getStoreId() );
				$this->repository->update( $existing_widget_settings );
				return;
			}
			$this->repository->delete( $existing_widget_settings );
		}

		$entity = new Widget_Settings();
		$entity->setStoreId( $this->storeContext->getStoreId() );
		$entity->setWidgetSettings( $settings );
		$this->repository->save( $entity );
        // phpcs:enable WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
	}
}
