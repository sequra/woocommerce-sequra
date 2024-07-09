<?php
/**
 * Class WidgetLocationConfig
 * 
 * @package SeQura\WC\Core\Extension\BusinessLogic\Domain\PromotionalWidgets\Models
 */

namespace SeQura\WC\Core\Extension\BusinessLogic\Domain\PromotionalWidgets\Models;

/**
 * Class WidgetLocationConfig
 */
class Widget_Location_Config {

	/**
	 * CSS selector for retrieving the price element.
	 *
	 * @var string
	 */
	private $sel_for_price;

	/**
	 * CSS selector for retrieving the price element from an alternative location.
	 * Intended for cases where the product layout changes for some products.
	 *
	 * @var string
	 */
	private $sel_for_alt_price;

	/**
	 * CSS Selector for detecting when to use the alternative price selector.
	 *
	 * @var string
	 */
	private $sel_for_alt_price_trigger;

	/**
	 * CSS Selector for retrieving the container element where the widget should be inserted.
	 *
	 * @var string
	 */
	private $sel_for_default_location;

	/**
	 * The locations where the widget should be displayed.
	 *
	 * @var Widget_Location[]
	 */
	private $custom_locations;

	/**
	 * Constructor.
	 * 
	 * @param string $sel_for_price CSS selector for retrieving the price element.
	 * @param string $sel_for_alt_price CSS selector for retrieving the price element from an alternative location.
	 * @param string $sel_for_alt_price_trigger CSS Selector for detecting when to use the alternative price selector.
	 * @param string $sel_for_default_location CSS Selector for retrieving the container element where the widget should be inserted.
	 * @param Widget_Location[] $custom_locations The locations where the widget should be displayed.
	 */
	public function __construct( 
		string $sel_for_price, 
		string $sel_for_alt_price, 
		string $sel_for_alt_price_trigger, 
		string $sel_for_default_location, 
		array $custom_locations 
	) {
		$this->sel_for_price             = $sel_for_price;
		$this->sel_for_alt_price         = $sel_for_alt_price;
		$this->sel_for_alt_price_trigger = $sel_for_alt_price_trigger;
		$this->sel_for_default_location  = $sel_for_default_location;
		$this->custom_locations          = $custom_locations;
	}

	/**
	 * Getter
	 */
	public function get_sel_for_price(): string {
		return $this->sel_for_price;
	}

	/**
	 * Getter
	 */
	public function get_sel_for_alt_price(): string {
		return $this->sel_for_alt_price;
	}

	/**
	 * Getter
	 */
	public function get_sel_for_alt_price_trigger(): string {
		return $this->sel_for_alt_price_trigger;
	}

	/**
	 * Getter
	 *
	 * @return Widget_Location[]
	 */
	public function get_custom_locations(): array {
		return $this->custom_locations;
	}

	/**
	 * Setter
	 */
	public function set_sel_for_price( string $sel_for_price ): void {
		$this->sel_for_price = $sel_for_price;
	}

	/**
	 * Setter
	 */
	public function set_sel_for_alt_price( string $sel_for_alt_price ): void {
		$this->sel_for_alt_price = $sel_for_alt_price;
	}

	/**
	 * Setter
	 */
	public function set_sel_for_alt_price_trigger( string $sel_for_alt_price_trigger ): void {
		$this->sel_for_alt_price_trigger = $sel_for_alt_price_trigger;
	}

	/**
	 * Setter
	 * 
	 * @param Widget_Location[] $locations
	 */
	public function set_custom_locations( array $locations ): void {
		$this->custom_locations = $locations;
	}

	/**
	 * Setter
	 */
	public function set_sel_for_default_location( string $sel_for_default_location ): void {
		$this->sel_for_default_location = $sel_for_default_location;
	}

	/**
	 * Getter
	 */
	public function get_sel_for_default_location(): string {
		return $this->sel_for_default_location;
	}

	/**
	 * Create a WidgetLocationConfig object from an array.
	 * 
	 * @param array<string, mixed> $data Array containing the data.
	 */
	public static function from_array( array $data ): ?Widget_Location_Config {
		if (
			! isset( $data['sel_for_price'] )
			|| ! isset( $data['sel_for_alt_price'] )
			|| ! isset( $data['sel_for_alt_price_trigger'] )
			|| ! isset( $data['sel_for_default_location'] )
			|| ! isset( $data['custom_locations'] )
		) {
			return null;
		}

		$locations = array();
		if ( is_array( $data['custom_locations'] ) ) {
			foreach ( $data['custom_locations'] as $location ) {
				$location = Widget_Location::from_array( $location );
				if ( $location ) {
					$locations[] = $location;
				}
			}
		}

		return new self(
			strval( $data['sel_for_price'] ),
			strval( $data['sel_for_alt_price'] ),
			strval( $data['sel_for_alt_price_trigger'] ),
			strval( $data['sel_for_default_location'] ),
			$locations
		);
	}

	/**
	 * Convert the WidgetLocationConfig object to an array.
	 * 
	 * @return array<string, mixed>
	 */
	public function to_array(): array {
		$locations = array();
		foreach ( $this->custom_locations as $location ) {
			$locations[] = $location->to_array();
		}

		return array(
			'sel_for_price'             => $this->sel_for_price,
			'sel_for_alt_price'         => $this->sel_for_alt_price,
			'sel_for_alt_price_trigger' => $this->sel_for_alt_price_trigger,
			'sel_for_default_location'  => $this->sel_for_default_location,
			'custom_locations'          => $locations,
		);
	}
}
