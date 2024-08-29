<?php
/**
 * Class Mini_Widget_Config
 * 
 * @package SeQura\WC\Core\Extension\BusinessLogic\Domain\PromotionalWidgets\Models
 */

namespace SeQura\WC\Core\Extension\BusinessLogic\Domain\PromotionalWidgets\Models;

/**
 * Class Mini_Widget_Config
 */
class Mini_Widget_Config {

	public const SEL_FOR_PRICE            = 'selForPrice';
	public const SEL_FOR_DEFAULT_LOCATION = 'selForDefaultLocation';
	public const MINI_WIDGETS             = 'miniWidgets';

	/**
	 * CSS selector for retrieving the price element.
	 *
	 * @var string
	 */
	private $sel_for_price;

	/**
	 * CSS Selector for retrieving the container element where the widget should be inserted.
	 *
	 * @var string
	 */
	private $sel_for_default_location;

	/**
	 * The custom mini widgets configuration.
	 *
	 * @var Mini_Widget[]
	 */
	private $mini_widgets;

	/**
	 * Constructor.
	 * 
	 * @param string $sel_for_price CSS selector for retrieving the price element.
	 * @param string $sel_for_default_location CSS Selector for retrieving the container element where the widget should be inserted.
	 * @param Mini_Widget[] $mini_widgets The custom mini widgets configuration.
	 */
	public function __construct( 
		string $sel_for_price, 
		string $sel_for_default_location, 
		array $mini_widgets 
	) {
		$this->sel_for_price            = $sel_for_price;
		$this->sel_for_default_location = $sel_for_default_location;
		$this->mini_widgets             = $mini_widgets;
	}

	/**
	 * Getter
	 */
	public function get_sel_for_price(): string {
		return $this->sel_for_price;
	}

	/**
	 * Getter
	 *
	 * @return Mini_Widget[]
	 */
	public function get_mini_widgets(): array {
		return $this->mini_widgets;
	}

	/**
	 * Setter
	 */
	public function set_sel_for_price( string $sel_for_price ): void {
		$this->sel_for_price = $sel_for_price;
	}

	/**
	 * Setter
	 * 
	 * @param Mini_Widget[] $mini_widgets
	 */
	public function set_mini_widgets( array $mini_widgets ): void {
		$this->mini_widgets = $mini_widgets;
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
	 * Create a Mini_Widget_Config object from an array.
	 * 
	 * @param array<string, mixed> $data Array containing the data.
	 */
	public static function from_array( array $data ): ?Mini_Widget_Config {
		if (
			! isset( $data[ self::SEL_FOR_PRICE ] )
			|| ! isset( $data[ self::SEL_FOR_DEFAULT_LOCATION ] )
			|| ! isset( $data[ self::MINI_WIDGETS ] )
		) {
			return null;
		}

		$mini_widgets = array();
		if ( is_array( $data[ self::MINI_WIDGETS ] ) ) {
			foreach ( $data[ self::MINI_WIDGETS ] as $mini_widget ) {
				$mini_widget = Mini_Widget::from_array( $mini_widget );
				if ( $mini_widget ) {
					$mini_widgets[] = $mini_widget;
				}
			}
		}

		return new self(
			strval( $data[ self::SEL_FOR_PRICE ] ),
			strval( $data[ self::SEL_FOR_DEFAULT_LOCATION ] ),
			$mini_widgets
		);
	}

	/**
	 * Convert the Mini_Widget_Config object to an array.
	 * 
	 * @return array<string, mixed>
	 */
	public function to_array(): array {
		$mini_widgets = array();
		foreach ( $this->mini_widgets as $mini_widget ) {
			$mini_widgets[] = $mini_widget->to_array();
		}

		return array(
			self::SEL_FOR_PRICE            => $this->sel_for_price,
			self::SEL_FOR_DEFAULT_LOCATION => $this->sel_for_default_location,
			self::MINI_WIDGETS             => $mini_widgets,
		);
	}
}
