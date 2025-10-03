<?php
/**
 * Shopper service
 *
 * @package    SeQura/WC
 * @subpackage SeQura/WC/Services
 */

namespace SeQura\WC\Services\Shopper;

use SeQura\Core\BusinessLogic\AdminAPI\AdminAPI;
use SeQura\Core\BusinessLogic\Domain\Multistore\StoreContext;

/**
 * Handle use cases related to Shopper
 */
class Shopper_Service implements Interface_Shopper_Service {

	private const META_KEY_DATE_OF_BIRTH = 'sequra_dob';

	/**
	 * Store context
	 *
	 * @var StoreContext
	 */
	private $store_context;

	/**
	 * Constructor
	 */
	public function __construct( StoreContext $store_context ) {
		$this->store_context = $store_context;
	}

	/**
	 * Get customer IP
	 */
	public function get_ip(): string {
		// phpcs:disable WordPressVIPMinimum.Variables.ServerVariables.UserControlledHeaders, WordPressVIPMinimum.Variables.RestrictedVariables.cache_constraints___SERVER__REMOTE_ADDR__
		if ( ! empty( $_SERVER['HTTP_CLIENT_IP'] ) ) {
			return \sanitize_text_field( \wp_unslash( $_SERVER['HTTP_CLIENT_IP'] ) );
		} elseif ( ! empty( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) {
			return \sanitize_text_field( \wp_unslash( $_SERVER['HTTP_X_FORWARDED_FOR'] ) );
		} elseif ( ! empty( $_SERVER['REMOTE_ADDR'] ) ) {
			return \sanitize_text_field( \wp_unslash( $_SERVER['REMOTE_ADDR'] ) );
		}
		// phpcs:enable WordPressVIPMinimum.Variables.ServerVariables.UserControlledHeaders, WordPressVIPMinimum.Variables.RestrictedVariables.cache_constraints___SERVER__REMOTE_ADDR__
		return '';
	}

	/**
	 * Check if the IP is allowed in SeQura settings
	 * 
	 * @param ?string $ip The IP address to check. If null, the current shopper's IP will be used.
	 */
	public function is_ip_allowed( ?string $ip = null ): bool {
		$ip = trim( $ip ?? $this->get_ip() );
		if ( empty( $ip ) ) {
			return true; // If we can't determine the IP, we assume it's allowed.
		}
		/**
		 * Array containing the general settings
		 * 
		 * @var array<string, mixed> $general_settings
		 */
		$general_settings = AdminAPI::get()->generalSettings( $this->store_context->getStoreId() )->getGeneralSettings()->toArray();
		if ( empty( $general_settings['allowedIPAddresses'] ) 
			|| ! is_array( $general_settings['allowedIPAddresses'] ) ) {
			return true; // No IP restrictions set.
		}
		
		foreach ( $general_settings['allowedIPAddresses'] as $allowed_ip ) {
			$allowed_ip = trim( (string) $allowed_ip );
			if ( ! empty( $allowed_ip ) && $allowed_ip === $ip ) {
				return true; // IP is explicitly allowed.
			}
		}
		return false; // IP is not in the allowed list.
	}

	/**
	 * Get User Agent
	 */
	public function get_user_agent(): string {
		// phpcs:disable WordPressVIPMinimum.Variables.RestrictedVariables.cache_constraints___SERVER__HTTP_USER_AGENT__
		if ( ! empty( $_SERVER['HTTP_USER_AGENT'] ) ) {
			return \sanitize_text_field( \wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) );
		}
		// phpcs:enable WordPressVIPMinimum.Variables.RestrictedVariables.cache_constraints___SERVER__HTTP_USER_AGENT__
		return '';
	}



	/**
	 * Check if the User Agent is a bot
	 */
	public function is_bot(): bool {
		return ! empty( preg_match( '/bot|crawl|slurp|spider|mediapartners|GoogleOther|Google-Safety|FeedFetcher-Google|Google-Read-Aloud|Google-Site-Verification|Google-InspectionTool/i', $this->get_user_agent() ) );
	}

	/**
	 * Get customer date of birth
	 */
	public function get_date_of_birth( int $customer_id ): string {
		return strval( \get_user_meta( $customer_id, self::META_KEY_DATE_OF_BIRTH, true ) );
	}

	/**
	 * Check if the shopper is using a mobile device
	 */
	public function is_using_mobile(): bool {
		// phpcs:ignore WordPressVIPMinimum.Variables.RestrictedVariables.cache_constraints___SERVER__HTTP_USER_AGENT__
		$user_agent = isset( $_SERVER['HTTP_USER_AGENT'] ) ? \sanitize_text_field( $_SERVER['HTTP_USER_AGENT'] ) : '';

		if ( empty( $user_agent ) ) {
			return false;
		}

		$regex_match = '/(nokia|iphone|android|motorola|^mot\-|softbank|foma|docomo|kddi|up\.browser|up\.link|'
						. 'htc|dopod|blazer|netfront|helio|hosin|huawei|novarra|CoolPad|webos|techfaith|palmsource|'
						. 'blackberry|alcatel|amoi|ktouch|nexian|samsung|^sam\-|s[cg]h|^lge|ericsson|philips|sagem|wellcom|bunjalloo|maui|'
						. 'symbian|smartphone|mmp|midp|wap|phone|windows ce|iemobile|^spice|^bird|^zte\-|longcos|pantech|gionee|^sie\-|portalmmm|'
						. 'jig\s browser|hiptop|^ucweb|^benq|haier|^lct|opera\s*mobi|opera\*mini|320x320|240x320|176x220'
						. ')/i';

		if ( preg_match( $regex_match, strtolower( $user_agent ) ) ) {
			return true;
		}

		$http_accept = isset( $_SERVER['HTTP_ACCEPT'] ) ? strtolower( \sanitize_text_field( $_SERVER['HTTP_ACCEPT'] ) ) : '';

		if ( strpos( $http_accept, 'application/vnd.wap.xhtml+xml' ) > 0 
		|| isset( $_SERVER['HTTP_X_WAP_PROFILE'] ) 
		|| isset( $_SERVER['HTTP_PROFILE'] ) ) {
			return true;
		}

		$mobile_ua     = strtolower( substr( $user_agent, 0, 4 ) );
		$mobile_agents = array(
			'w3c ',
			'acs-',
			'alav',
			'alca',
			'amoi',
			'audi',
			'avan',
			'benq',
			'bird',
			'blac',
			'blaz',
			'brew',
			'cell',
			'cldc',
			'cmd-',
			'dang',
			'doco',
			'eric',
			'hipt',
			'inno',
			'ipaq',
			'java',
			'jigs',
			'kddi',
			'keji',
			'leno',
			'lg-c',
			'lg-d',
			'lg-g',
			'lge-',
			'maui',
			'maxo',
			'midp',
			'mits',
			'mmef',
			'mobi',
			'mot-',
			'moto',
			'mwbp',
			'nec-',
			'newt',
			'noki',
			'oper',
			'palm',
			'pana',
			'pant',
			'phil',
			'play',
			'port',
			'prox',
			'qwap',
			'sage',
			'sams',
			'sany',
			'sch-',
			'sec-',
			'send',
			'seri',
			'sgh-',
			'shar',
			'sie-',
			'siem',
			'smal',
			'smar',
			'sony',
			'sph-',
			'symb',
			't-mo',
			'teli',
			'tim-',
			'tosh',
			'tsm-',
			'upg1',
			'upsi',
			'vk-v',
			'voda',
			'wap-',
			'wapa',
			'wapi',
			'wapp',
			'wapr',
			'webc',
			'winw',
			'winw',
			'xda ',
			'xda-',
		);

		if ( in_array( $mobile_ua, $mobile_agents, true ) ) {
			return true;
		}

		if ( isset( $_SERVER['ALL_HTTP'] ) && strpos( strtolower( \sanitize_text_field( $_SERVER['ALL_HTTP'] ) ), 'OperaMini' ) > 0 ) {
			return true;
		}

		return false;
	}
}
