<?php
/**
 * Extends the GeneralSettingsRepository class.
 * 
 * @package SeQura\WC
 */

namespace SeQura\WC\Core\Extension\BusinessLogic\DataAccess\GeneralSettings\Repositories;

use SeQura\Core\BusinessLogic\DataAccess\GeneralSettings\Repositories\GeneralSettingsRepository;
use SeQura\Core\BusinessLogic\Domain\GeneralSettings\Models\GeneralSettings;
use SeQura\WC\Core\Extension\BusinessLogic\DataAccess\GeneralSettings\Entities\General_Settings;
use SeQura\Core\Infrastructure\ORM\Exceptions\QueryFilterInvalidParamException;

/**
 * Extends the GeneralSettingsRepository class.
 */
class General_Settings_Repository extends GeneralSettingsRepository {


	/**
	 * Set the general settings.
	 *
	 * @throws QueryFilterInvalidParamException
	 */
	public function setGeneralSettings( GeneralSettings $general_settings ): void {
         // phpcs:disable WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
		$existing_general_settings = $this->getGeneralSettingsEntity();

		if ( $existing_general_settings ) {
			if ( $existing_general_settings instanceof General_Settings ) {
				$existing_general_settings->setGeneralSettings( $general_settings );
				$existing_general_settings->setStoreId( $this->storeContext->getStoreId() );
				$this->repository->update( $existing_general_settings );
				return;
			}

			$this->repository->delete( $existing_general_settings );
		}

		$entity = new General_Settings();
		$entity->setStoreId( $this->storeContext->getStoreId() );
		$entity->setGeneralSettings( $general_settings );
		$this->repository->save( $entity );
         // phpcs:enable WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
	}
}
