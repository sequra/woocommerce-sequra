<?php
/**
 * Class WidgetLocation
 * 
 * @package SeQura\WC\Core\Extension\BusinessLogic\Domain\PromotionalWidgets\Models
 */

namespace SeQura\WC\Core\Extension\BusinessLogic\Domain\PromotionalWidgets\Models;

/**
 * Class WidgetLocation
 */
class Widget_Location {

	private const DISPLAY_WIDGET = 'displayWidget';
	private const SEL_FOR_TARGET = 'selForTarget';
	private const WIDGET_STYLES  = 'widgetStyles';
	private const PRODUCT        = 'product';
	private const COUNTRY        = 'country';
	private const CAMPAIGN       = 'campaign';

	/**
	 * CSS selector for retrieving the element where the widget should be inserted.
	 *
	 * @var string
	 */
	private $sel_for_target;

	/**
	 * Widget styles.
	 *
	 * @var string
	 */
	private $widget_styles;

	/**
	 * Display widget.
	 *
	 * @var bool
	 */
	private $display_widget;

	/**
	 * The seQura product identifier.
	 *
	 * @var ?string
	 */
	private $product;

	/**
	 * The country identifier.
	 *
	 * @var ?string
	 */
	private $country;

	/**
	 * The campaign of the payment method.
	 *
	 * @var ?string
	 */
	private $campaign;

	/**
	 * Constructor.
	 */
	public function __construct( 
		bool $display_widget, 
		string $sel_for_target,
		string $widget_styles,
		?string $product = null,
		?string $country = null,
		?string $campaign = null 
	) {
		$this->display_widget = $display_widget;
		$this->widget_styles  = $widget_styles;
		$this->sel_for_target = $sel_for_target;
		$this->product        = $product;
		$this->campaign       = $campaign;
		$this->country        = $country;
	}

	/**
	 * Getter
	 */
	public function get_sel_for_target(): string {
		return $this->sel_for_target;
	}

	/**
	 * Getter
	 */
	public function get_product(): ?string {
		return $this->product;
	}

	/**
	 * Getter
	 */
	public function get_country(): ?string {
		return $this->country;
	}

	/**
	 * Getter
	 */
	public function get_campaign(): ?string {
		return $this->campaign;
	}

	/**
	 * Setter
	 */
	public function set_sel_for_target( string $sel_for_target ): void {
		$this->sel_for_target = $sel_for_target;
	}

	/**
	 * Setter
	 */
	public function set_product( string $product ): void {
		$this->product = $product;
	}

	/**
	 * Setter
	 */
	public function set_country( string $country ): void {
		$this->country = $country;
	}

	/**
	 * Setter
	 */
	public function set_campaign( ?string $campaign ): void {
		$this->campaign = $campaign;
	}

	/**
	 * Getter
	 */
	public function get_widget_styles(): string {
		return $this->widget_styles;
	}

	/**
	 * Setter
	 */
	public function set_widget_styles( string $widget_styles ): void {
		$this->widget_styles = $widget_styles;
	}

	/**
	 * Getter
	 */
	public function get_display_widget(): bool {
		return $this->display_widget;
	}

	/**
	 * Setter
	 */
	public function set_display_widget( bool $display_widget ): void {
		$this->display_widget = $display_widget;
	}


	/**
	 * Create a WidgetLocation object from an array.
	 * 
	 * @param array<string, mixed> $data Array containing the data.
	 */
	public static function from_array( array $data ): ?Widget_Location {
		if ( ! isset( $data['selForTarget'] ) ) {
			return null;
		}

		return new self(
			isset( $data[ self::DISPLAY_WIDGET ] ) ? boolval( $data[ self::DISPLAY_WIDGET ] ) : false,
			isset( $data[ self::SEL_FOR_TARGET ] ) ? strval( $data[ self::SEL_FOR_TARGET ] ) : null,
			isset( $data[ self::WIDGET_STYLES ] ) ? strval( $data[ self::WIDGET_STYLES ] ) : null,
			isset( $data[ self::PRODUCT ] ) ? strval( $data[ self::PRODUCT ] ) : null,
			isset( $data[ self::COUNTRY ] ) ? strval( $data[ self::COUNTRY ] ) : null,
			isset( $data[ self::CAMPAIGN ] ) ? strval( $data[ self::CAMPAIGN ] ) : null
		);
	}

	/**
	 * Convert the WidgetLocation object to an array.
	 * 
	 * @return array<string, string|null> Array containing the data.
	 */
	public function to_array(): array {
		return array(
			self::DISPLAY_WIDGET => $this->display_widget,
			self::SEL_FOR_TARGET => $this->sel_for_target,
			self::WIDGET_STYLES  => $this->widget_styles,
			self::PRODUCT        => $this->product,
			self::COUNTRY        => $this->country,
			self::CAMPAIGN       => $this->campaign,
		);
	}
}
