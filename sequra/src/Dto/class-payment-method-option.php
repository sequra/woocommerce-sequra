<?php
/**
 * Payment Method Option DTO
 *
 * @package    SeQura/WC
 */

namespace SeQura\WC\Dto;

/**
 * Payment Method Option DTO.
 */
class Payment_Method_Option extends Payment_Method_Data {

	/**
	 * SeQura product long title
	 * 
	 * @var string
	 */
	public $long_title;

	/**
	 * SeQura product claim
	 * 
	 * @var string
	 */
	public $claim;

	/**
	 * Unix timestamp when the payment method starts being available
	 * 
	 * @var int
	 */
	public $starts_at;

	/**
	 * Unix timestamp when the payment method stops being available
	 * 
	 * @var int
	 */
	public $ends_at;

	/**
	 * Payment method description
	 * 
	 * @var string
	 */
	public $description;

	/**
	 * Payment method icon in svg format
	 * 
	 * @var string
	 */
	public $icon;
	
	/**
	 * Payment method cost description
	 * 
	 * @var string
	 */
	public $cost_description;

	/**
	 * Minimum amount for the payment method to be available
	 * 
	 * @var int|null
	 */
	public $min_amount;

	/**
	 * Maximum amount for the payment method to be available
	 * 
	 * @var int|null
	 */
	public $max_amount;

	/**
	 * Payment method cost. Contains the following keys:
	 * - setupFee: int
	 * - instalmentFee: int
	 * - downPaymentFees: int
	 * - instalmentTotal: int
	 * 
	 * @var array<string, int>
	 */
	public $cost;


	/**
	 * Check if the instance matches a payment method
	 * 
	 * @param Payment_Method_Data $data
	 */
	public function match( $data ): bool {
		return $data instanceof Payment_Method_Data && $this->product === $data->product && $this->campaign === $data->campaign;
	}

	/**
	 * Check if the payment method should show more info
	 */
	public function should_show_more_info(): bool {
		return ! in_array( $this->product, array( 'fp1' ), true );
	}

	/**
	 * Check if the payment method should show cost description
	 */
	public function should_show_cost_description(): bool {
		return $this->cost_description && strtolower( $this->cost_description ) !== strtolower( $this->claim );
	}

	/**
	 * Encode the parent DTO instance into a raw string. Returns a base64 encoded JSON string.
	 */
	public function encode_data(): string {
		return parent::encode();
	}
}
