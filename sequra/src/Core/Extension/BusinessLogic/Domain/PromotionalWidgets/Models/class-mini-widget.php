<?php
/**
 * Class Mini_Widget
 * 
 * @package SeQura\WC\Core\Extension\BusinessLogic\Domain\PromotionalWidgets\Models
 */

namespace SeQura\WC\Core\Extension\BusinessLogic\Domain\PromotionalWidgets\Models;

/**
 * Class Mini_Widget
 */
class Mini_Widget {

	private const SEL_FOR_PRICE       = 'selForPrice';
	private const SEL_FOR_LOCATION    = 'selForLocation';
	private const MESSAGE             = 'message';
	private const MESSAGE_BELOW_LIMIT = 'messageBelowLimit';
	private const PRODUCT             = 'product';
	private const CAMPAIGN            = 'campaign';
	private const COUNTRY             = 'countryCode';

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
	private $sel_for_location;

	/**
	 * The message to display when the price is above the limit.
	 *
	 * @var string
	 */
	private $message;

	/**
	 * The message to display when the price is below the limit.
	 *
	 * @var string
	 */
	private $message_below_limit;

	/**
	 * The seQura product identifier.
	 *
	 * @var ?string
	 */
	private $product;

	/**
	 * The seQura product country.
	 *
	 * @var ?string
	 */
	private $country;

	/**
	 * The seQura product campaign.
	 *
	 * @var string
	 */
	private $campaign;

	/**
	 * Constructor.
	 */
	public function __construct( 
		string $sel_for_price, 
		string $sel_for_location,
		string $message,
		string $message_below_limit,
		?string $product = null,
		?string $country = null,
		?string $campaign = null
	) {
		$this->sel_for_price       = $sel_for_price;
		$this->sel_for_location    = $sel_for_location;
		$this->message             = $message;
		$this->message_below_limit = $message_below_limit;
		$this->product             = $product;
		$this->country             = $country;
		$this->campaign            = $campaign;
	}

	/**
	 * Getter
	 */
	public function get_sel_for_price(): string {
		return $this->sel_for_price;
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
	public function set_sel_for_location( string $sel_for_location ): void {
		$this->sel_for_location = $sel_for_location;
	}

	/**
	 * Getter
	 */
	public function get_sel_for_location(): string {
		return $this->sel_for_location;
	}

	/**
	 * Create a Mini_Widget object from an array.
	 * 
	 * @param array<string, mixed> $data Array containing the data.
	 */
	public static function from_array( array $data ): ?Mini_Widget {
		if (
			! isset( $data[ self::SEL_FOR_PRICE ] )
			|| ! isset( $data[ self::SEL_FOR_LOCATION ] )
			|| ! isset( $data[ self::MESSAGE ] )
			|| ! isset( $data[ self::MESSAGE_BELOW_LIMIT ] )
		) {
			return null;
		}

		return new self(
			strval( $data[ self::SEL_FOR_PRICE ] ),
			strval( $data[ self::SEL_FOR_LOCATION ] ),
			strval( $data[ self::MESSAGE ] ),
			strval( $data[ self::MESSAGE_BELOW_LIMIT ] ),
			$data[ self::PRODUCT ] ?? null,
			$data[ self::COUNTRY ] ?? null,
			$data[ self::CAMPAIGN ] ?? null
		);
	}

	/**
	 * Convert the Mini_Widget object to an array.
	 * 
	 * @return array<string, mixed>
	 */
	public function to_array(): array {
		return array(
			self::SEL_FOR_PRICE       => $this->sel_for_price,
			self::SEL_FOR_LOCATION    => $this->sel_for_location,
			self::MESSAGE             => $this->message,
			self::MESSAGE_BELOW_LIMIT => $this->message_below_limit,
			self::PRODUCT             => $this->product,
			self::COUNTRY             => $this->country,
			self::CAMPAIGN            => $this->campaign,
		);
	}
}
