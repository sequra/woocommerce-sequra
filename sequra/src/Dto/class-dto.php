<?php
/**
 * DTO
 *
 * @package    SeQura/WC/
 */

namespace SeQura\WC\Dto;

use Throwable;

/**
 * DTO.
 */
abstract class Dto {

	/**
	 * Decode a raw string into a DTO instance. By default, assumes that the raw string is a JSON string.
	 */
	public static function decode( string $raw ): ?self {
		try {
			$data = json_decode( $raw, true );
		} catch ( Throwable ) {
			return null;
		}
		if ( ! is_array( $data ) ) {
			return null;
		}
		$instance = new static();
		foreach ( $data as $key => $value ) {
			if ( property_exists( $instance, $key ) ) {
				$instance->$key = $value;
			}
		}
		return $instance;
	}

	/**
	 * Create a DTO instance from an array.
	 */
	public static function from_array( array $data ): ?self {
		if ( ! is_array( $data ) ) {
			return null;
		}
		$instance = new static();
		foreach ( $data as $key => $value ) {
			if ( property_exists( $instance, $key ) ) {
				$instance->$key = $value;
			}
		}
		return $instance;
	}

	/**
	 * Encode the DTO instance into a raw string. By default, returns a JSON string.
	 */
	public function encode(): string {
		$encoded = wp_json_encode( $this, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE );
		return $encoded ? $encoded : '';
	}
}
