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
	 * Constructor.
	 */
	public function __construct( string $sel_for_target, ?string $product = null, ?string $country = null ) {
		$this->sel_for_target = $sel_for_target;
		$this->product        = $product;
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
	 * Create a WidgetLocation object from an array.
	 * 
	 * @param array<string, mixed> $data Array containing the data.
	 */
	public static function from_array( array $data ): ?Widget_Location {
		if ( ! isset( $data['sel_for_target'] ) ) {
			return null;
		}

		return new self(
			strval( $data['sel_for_target'] ),
			isset( $data['product'] ) ? strval( $data['product'] ) : null,
			isset( $data['country'] ) ? strval( $data['country'] ) : null
		);
	}

	/**
	 * Convert the WidgetLocation object to an array.
	 * 
	 * @return array<string, string|null> Array containing the data.
	 */
	public function to_array(): array {
		return array(
			'sel_for_target' => $this->sel_for_target,
			'product'        => $this->product,
			'country'        => $this->country,
		);
	}

	/**
	 * Check if the location is the default one.
	 */
	public function is_default_location(): bool {
		return null === $this->product && null === $this->country;
	}
}
