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
	 * The title of the payment method.
	 *
	 * @var string
	 */
	private $title;

	/**
	 * Constructor.
	 */
	public function __construct( bool $display_widget, string $sel_for_target, string $widget_styles, ?string $product = null, ?string $country = null, ?string $title = null ) {
		$this->display_widget = $display_widget;
		$this->widget_styles  = $widget_styles;
		$this->sel_for_target = $sel_for_target;
		$this->product        = $product;
		$this->country        = $country;
		$this->title          = $title;
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
	public function get_title(): string {
		return $this->title;
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
	public function set_title( string $title ): void {
		$this->title = $title;
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
		if ( ! isset( $data['sel_for_target'] ) ) {
			return null;
		}

		return new self(
			isset( $data['display_widget'] ) ? boolval( $data['display_widget'] ) : false,
			isset( $data['sel_for_target'] ) ? strval( $data['sel_for_target'] ) : null,
			isset( $data['widget_styles'] ) ? strval( $data['widget_styles'] ) : null,
			isset( $data['product'] ) ? strval( $data['product'] ) : null,
			isset( $data['country'] ) ? strval( $data['country'] ) : null,
			isset( $data['title'] ) ? strval( $data['title'] ) : null
		);
	}

	/**
	 * Convert the WidgetLocation object to an array.
	 * 
	 * @return array<string, string|null> Array containing the data.
	 */
	public function to_array(): array {
		return array(
			'display_widget' => $this->display_widget,
			'widget_styles'  => $this->widget_styles,
			'sel_for_target' => $this->sel_for_target,
			'product'        => $this->product,
			'country'        => $this->country,
			'title'          => $this->title,
		);
	}
}
