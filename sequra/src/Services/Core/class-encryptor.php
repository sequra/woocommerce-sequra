<?php
/**
 * Logger service.
 *
 * @package SeQura\WC
 */

namespace SeQura\WC\Services\Core;

use SeQura\Core\BusinessLogic\Utility\EncryptorInterface;
/**
 * Class Encryptor
 *
 * @package Sequra\Core\Services\BusinessLogic\Utility
 */
class Encryptor implements EncryptorInterface {

	/**
	 * Get key used for encryption and decryption.
	 */
	private function get_key() {
		return hash( 'sha256', AUTH_KEY, true );
	}

	/**
	 * Encrypts a given string.
	 *
	 * @param string $data
	 *
	 * @return string
	 */
	public function encrypt( string $data ): string {
		$nonce = random_bytes( SODIUM_CRYPTO_SECRETBOX_NONCEBYTES );
		return base64_encode( $nonce . sodium_crypto_secretbox( $data, $nonce, $this->get_key() ) ); // use value from wp-config.php AUTH_KEY as key.
	}

	/**
	 * Decrypts a given string.
	 *
	 * @param string $encryptedData
	 *
	 * @return string
	 */
	public function decrypt( string $encryptedData ): string { // phpcs:ignore WordPress.NamingConventions.ValidVariableName.VariableNotSnakeCase
		$data  = base64_decode( $encryptedData );// phpcs:ignore WordPress.NamingConventions.ValidVariableName.VariableNotSnakeCase
		$nonce = mb_substr( $data, 0, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES, '8bit' );
		$value = mb_substr( $data, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES, null, '8bit' );
		return sodium_crypto_secretbox_open( $value, $nonce, $this->get_key() );
	}
}
