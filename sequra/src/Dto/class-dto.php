<?php
/**
 * DTO
 *
 * @package    SeQura/WC/
 */

namespace SeQura\WC\Dto;

use ValueError;

/**
 * DTO.
 */
abstract class Dto {

	/**
	 * Decode a raw string into a DTO instance. By default, assumes that the raw string is a JSON string.
	 *
	 * @return static|null
	 */
	public static function decode( string $raw ) {
		try {
			$data = json_decode( $raw, true );
		} catch ( ValueError $e ) { // @phpstan-ignore-line
			return null;
		}
		if ( ! is_array( $data ) ) {
			return null;
		}
		$instance = new static(); // @phpstan-ignore-line
		foreach ( $data as $key => $value ) {
			if ( property_exists( $instance, $key ) ) {
				$instance->$key = $value;
			}
		}
		return $instance;
	}

	/**
	 * Create a DTO instance from an array.
	 * 
	 * @param array<string, mixed> $data
	 * @return static|null
	 */
	public static function from_array( array $data ) {
		if ( ! is_array( $data ) ) {
			return null;
		}
		$instance = new static(); // @phpstan-ignore-line
		foreach ( $data as $key => $value ) {
			if ( property_exists( $instance, $key ) ) {
				$instance->$key = $value;
			}
		}
		return $instance;
	}

	/**
	 * Convert the DTO instance into an array.
	 * 
	 * @return array<string, mixed>
	 */
	public function to_array(): array {
		return (array) $this;
	}

	/**
	 * Encode the DTO instance into a raw string. By default, returns a JSON string.
	 */
	public function encode(): string {
		$encoded = wp_json_encode( $this, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE );
		return $encoded ? $encoded : '';
	}
}
