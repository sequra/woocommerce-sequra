<?php
/**
 * Define saving and retrieving configuration values.
 *
 * @package    SeQura/WC
 * @subpackage SeQura/WC/Repositories
 */

namespace SeQura\WC\Core\Extension\Infrastructure\Configuration;

use SeQura\Core\Infrastructure\Configuration\ConfigEntity;
use SeQura\Core\Infrastructure\Configuration\ConfigurationManager;
use SeQura\Core\Infrastructure\ORM\Exceptions\QueryFilterInvalidParamException;

// phpcs:disable WordPress.NamingConventions.ValidVariableName.VariableNotSnakeCase, Universal.NamingConventions.NoReservedKeywordParameterNames.defaultFound
/**
 * Define saving and retrieving configuration values.
 */
class Configuration_Manager extends ConfigurationManager {

	private const OPTION_NAME = 'sequra';

	/**
	 * The preferences
	 *
	 * @var      mixed[]
	 */
	private $preferences;

	/**
	 * Constructor.
	 */
	protected function __construct() {
		parent::__construct();
		$this->load_preferences();
	}

	/**
	 * Gets configuration value for given name.
	 *
	 * @param string $name Name of the config parameter.
	 * @param mixed $default Default value if config entity does not exist.
	 * @param bool $isContextSpecific Flag that identifies whether value is config specific.
	 *
	 * @return mixed Value of config entity if found; otherwise, default value provided in $default parameter.
	 */
	public function getConfigValue( $name, $default = null, $isContextSpecific = true ) {
		if ( ! isset( $this->preferences[ $name ] ) ) {
			return $default;
		}
		return $this->preferences[ $name ];
	}

	/**
	 * Saves configuration value or updates it if it already exists.
	 *
	 * @param string $name Configuration property name.
	 * @param mixed $value Configuration property value.
	 * @param bool $isContextSpecific Flag that indicates whether config property is context specific.
	 *
	 * @return ConfigEntity
	 * @throws QueryFilterInvalidParamException
	 */
	public function saveConfigValue( $name, $value, $isContextSpecific = true ) {
		$this->preferences[ $name ] = $value;
		update_option( self::OPTION_NAME, $this->preferences );
		return ConfigEntity::fromArray(
			array(
				'name'    => $name,
				'value'   => $value,
				'context' => '',
			)
		);
	}

	/**
	 * Load preferences from wp-options.
	 */
	private function load_preferences(): void {
		if ( null !== $this->preferences ) {
			return;
		}
		$this->preferences = (array) get_option( self::OPTION_NAME, array() );
		$need_update       = false;
		foreach ( $this->get_defaults() as $key => $value ) {
			if ( isset( $this->preferences[ $key ] ) ) {
				continue;
			}
			$this->preferences[ $key ] = $value;
			if ( ! $need_update ) {
				$need_update = true;
			}
		}
		if ( $need_update ) {
			update_option( self::OPTION_NAME, $this->preferences );
		}
	}

	/**
	 * Get default values.
	 *
	 * @return mixed[]
	 */
	private function get_defaults(): array {
		return array(
			'debug'   => 'no',
			'version' => '',
		);
	}
}
