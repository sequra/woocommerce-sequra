<?php
/**
 * Payment Method Data DTO
 *
 * @package    SeQura/WC
 */

namespace SeQura\WC\Dto;

/**
 * Payment Method Data DTO.
 */
class Payment_Method_Data extends Dto {

	/**
	 * SeQura product
	 * 
	 * @var string
	 */
	public $product;

	/**
	 * Campaign
	 * 
	 * @var string
	 */
	public $campaign;

	/**
	 * SeQura product title
	 * 
	 * @var string
	 */
	public $title;

	/**
	 * Encode the DTO instance into a raw string. Returns a base64 encoded JSON string.
	 */
	public function encode(): string {
		$encoded = parent::encode();
		$encoded = base64_encode( $encoded );
		return $encoded ? $encoded : '';
	}

	/**
	 * Decode a raw string into a DTO instance. Assumes that the raw string is a base64 encoded JSON string.
	 * 
	 * @return static|null
	 */
	public static function decode( string $raw ) {
		$decoded = base64_decode( $raw );
		if ( ! $decoded ) {
			return null;
		}
		return parent::decode( $decoded );
	}

	/**
	 * Check if the instance contains valid data.
	 */
	public function is_valid(): bool {
		return ! empty( $this->product );
	}
}
