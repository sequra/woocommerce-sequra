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
use WC_Customer;
use WC_Order;
use WP_User;

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

		/**
		 * Get client first name. If the order is null, attempt to retrieve data from the session.
		 */
	public function get_first_name( ?WC_Order $order, $is_delivery = true ): string {
		$prefix = $is_delivery ? 'shipping' : 'billing';
		return $this->get_customer_field( $order, "get_{$prefix}_first_name", 'get_billing_first_name', "{$prefix}_first_name", 'first_name' );
	}

	/**
	 * Get client last name. If the order is null, attempt to retrieve data from the session.
	 */
	public function get_last_name( ?WC_Order $order, $is_delivery = true ): string {
		$prefix = $is_delivery ? 'shipping' : 'billing';
		return $this->get_customer_field( $order, "get_{$prefix}_last_name", 'get_billing_last_name', "{$prefix}_last_name", 'last_name' );
	}

	/**
	 * Get client company. If the order is null, attempt to retrieve data from the session.
	 */
	public function get_company( ?WC_Order $order, $is_delivery = true ): string {
		$prefix = $is_delivery ? 'shipping' : 'billing';
		return $this->get_customer_field( $order, "get_{$prefix}_company", 'get_billing_company', "{$prefix}_company", 'company' );
	}

	/**
	 * Get client address's first line. If the order is null, attempt to retrieve data from the session.
	 */
	public function get_address_1( ?WC_Order $order, $is_delivery = true ): string {
		$prefix = $is_delivery ? 'shipping' : 'billing';
		return $this->get_customer_field( $order, "get_{$prefix}_address_1", 'get_billing_address_1', "{$prefix}_address_1", 'address_1' );
	}

	/**
	 * Get client address's second line. If the order is null, attempt to retrieve data from the session.
	 */
	public function get_address_2( ?WC_Order $order, $is_delivery = true ): string {
		$prefix = $is_delivery ? 'shipping' : 'billing';
		return $this->get_customer_field( $order, "get_{$prefix}_address_2", 'get_billing_address_2', "{$prefix}_address_2", 'address_2' );
	}

	/**
	 * Get client postcode. If the order is null, attempt to retrieve data from the session.
	 */
	public function get_postcode( ?WC_Order $order, $is_delivery = true ): string {
		$prefix = $is_delivery ? 'shipping' : 'billing';
		return $this->get_customer_field( $order, "get_{$prefix}_postcode", 'get_billing_postcode', "{$prefix}_postcode", 'postcode' );
	}

	/**
	 * Get client city. If the order is null, attempt to retrieve data from the session.
	 */
	public function get_city( ?WC_Order $order, $is_delivery = true ): string {
		$prefix = $is_delivery ? 'shipping' : 'billing';
		$city   = $this->get_customer_field( $order, "get_{$prefix}_city", 'get_billing_city', "{$prefix}_city", 'city' );
		if ( ! $city ) {
			$city = $this->get_customer_field( $order, 'get_city', 'get_city', 'city', 'city' );
		}
		return $city;
	}

	/**
	 * Get client country code. If the order is null, attempt to retrieve data from the session.
	 */
	public function get_country( ?WC_Order $order, $is_delivery = true ): string {
		$prefix = $is_delivery ? 'shipping' : 'billing';
		return $this->get_customer_field( $order, "get_{$prefix}_country", 'get_billing_country', "{$prefix}_country", 'country' );
	}

	/**
	 * Get client state. If the order is null, attempt to retrieve data from the session.
	 */
	public function get_state( ?WC_Order $order, $is_delivery = true ): string {
		$prefix     = $is_delivery ? 'shipping' : 'billing';
		$state_code = $this->get_customer_field( $order, "get_{$prefix}_state", 'get_billing_state', "{$prefix}_state", 'state' );
		if ( ! $state_code ) {
			return '';
		}
		$states = WC()->countries->get_states( $this->get_country( $order ) );
		if ( ! $states || ! isset( $states[ $state_code ] ) ) {
			return '';
		}
		return $states[ $state_code ];
	}

	/**
	 * Get client phone. If the order is null, attempt to retrieve data from the session.
	 */
	public function get_phone( ?WC_Order $order, $is_delivery = true ): string {
		$prefix = $is_delivery ? 'shipping' : 'billing';
		return $this->get_customer_field( $order, "get_{$prefix}_phone", 'get_billing_phone', "{$prefix}_phone", 'phone' );
	}

	/**
	 * Get client email. If the order is null, attempt to retrieve data from the session.
	 */
	public function get_email( ?WC_Order $order ): string {
		return $this->get_customer_field( $order, 'get_billing_email', 'get_billing_email', 'email', 'email' );
	}

	/**
	 * TODO: Review this with Mikel. I need to know what is the origin of the VAT number field because it is not a default field in WooCommerce.
	 * Get shopper vat number. If the order is null, attempt to retrieve data from the session.
	 */
	public function get_vat( ?WC_Order $order, $is_delivery = true ): string {
		$prefix = $is_delivery ? 'shipping' : 'billing';
		$vat    = $this->get_customer_field( $order, "get_{$prefix}_nif", 'get_billing_nif', "{$prefix}_nif", 'nif' );
		if ( ! $vat ) {
			$vat = $this->get_customer_field( $order, 'get_nif', 'get_nif', 'nif', 'nif' );
		}
		if ( ! $vat ) {
			$vat = $this->get_customer_field( $order, "get_{$prefix}_vat", 'get_billing_vat', "{$prefix}_vat", 'vat' );
		}
		
		return $vat;
	}

	/**
	 * Get shopper NIN number. If the order is null, attempt to retrieve data from the session.
	 */
	public function get_nin( ?WC_Order $order ): ?string {
		/**
		 * Get NIN number
		 *
		 * @since 3.0.0
		 */
		$nin = \apply_filters( 'sequra_get_nin', null, $order );
		return is_string( $nin ) || null === $nin ? $nin : null;
	}

	/**
	 * Get date of birth. If the order is null, attempt to retrieve data from the session.
	 */
	public function get_dob( ?WC_Order $order ): ?string {
		/**
		 * Get Date of Birth number
		 *
		 * @since 3.0.0
		 */
		$dob = \apply_filters( 'sequra_get_dob', null, $order );
		return is_string( $dob ) || null === $dob ? $dob : null;
	}

	/**
	 * Get shopper title. If the order is null, attempt to retrieve data from the session.
	 */
	public function get_shopper_title( ?WC_Order $order ): ?string {
		/**
		 * Get Shopper title
		 *
		 * @since 3.0.0
		 */
		$title = \apply_filters( 'sequra_get_shopper_title', null, $order );
		return is_string( $title ) || null === $title ? $title : null;
	}

	/**
	 * Get shopper created at date. If the order is null, attempt to retrieve data from the session.
	 */
	public function get_shopper_created_at( ?WC_Order $order ): ?string {
		/**
		 * Get Shopper created at date
		 *
		 * @since 3.0.0
		 */
		$date = \apply_filters( 'sequra_get_shopper_created_at', $this->get_shopper_registration_date( $order ), $order );
		return is_string( $date ) || null === $date ? $date : null;
	}


	/**
	 * Get shopper updated at date. If the order is null, attempt to retrieve data from the session.
	 */
	public function get_shopper_updated_at( ?WC_Order $order ): ?string {
		/**
		 * Get Shopper updated at date
		 *
		 * @since 3.0.0
		 */
		$date = \apply_filters( 'sequra_get_shopper_updated_at', $this->get_shopper_registration_date( $order ), $order );
		return is_string( $date ) || null === $date ? $date : null;
	}

	/**
	 * Get shopper rating. If the order is null, attempt to retrieve data from the session.
	 */
	public function get_shopper_rating( ?WC_Order $order ): ?int {
		/**
		 * Get Shopper rating. Must return an integer between 0 and 100 or null.
		 *
		 * @since 3.0.0
		 */
		$rating = \apply_filters( 'sequra_get_shopper_rating', null, $order );
		return ( is_int( $rating ) && 0 <= $rating && 100 >= $rating ) || null === $rating ? $rating : null;
	}

	/**
	 * Get customer data from order, session or POST. If not found, return an empty string.
	 */
	private function get_customer_field( 
		?WC_Order $order, 
		string $order_func, 
		string $order_alt_func, 
		string $session_key, 
		string $session_alt_key
	): string {
		if ( ! $order ) {
			$customer = $this->get_customer_from_session();
			return ! empty( $customer[ $session_key ] ) ? $customer[ $session_key ] : (
				$session_alt_key !== $session_key && ! empty( $customer[ $session_alt_key ] ) ? $customer[ $session_alt_key ] : ''
			);
		}
		$value = '';
		if ( method_exists( $order, $order_func ) ) {
			$value = call_user_func( array( $order, $order_func ) );
		} 
		
		if ( ! $value && $order_alt_func !== $order_func && method_exists( $order, $order_alt_func ) ) {
			$value = call_user_func( array( $order, $order_alt_func ) );
		}

		return $value;
	}

	/**
	 * Get customer data from session. If not found, return an empty array.
	 */
	private function get_customer_from_session(): array {
		$data = array();
		if ( function_exists( 'WC' ) && WC()->customer ) {
			/**
			 * Customer instance.
			 *
			 * @var WC_Customer $customer
			 */
			$customer = WC()->customer;
			$data     = array(
				'email'               => empty( $customer->get_billing_email() ) ? $customer->get_email() : $customer->get_billing_email(),
				'billing_first_name'  => $customer->get_billing_first_name(),
				'billing_last_name'   => $customer->get_billing_last_name(),
				'billing_company'     => $customer->get_billing_company(),
				'billing_address_1'   => $customer->get_billing_address_1(),
				'billing_address_2'   => $customer->get_billing_address_2(),
				'billing_postcode'    => $customer->get_billing_postcode(),
				'billing_city'        => $customer->get_billing_city(),
				'billing_country'     => $customer->get_billing_country(),
				'billing_state'       => $customer->get_billing_state(),
				'billing_phone'       => $customer->get_billing_phone(),
				'billing_nif'         => method_exists( $customer, 'get_billing_nif' ) ? $customer->get_billing_nif() : '',
				'billing_vat'         => method_exists( $customer, 'get_billing_vat' ) ? $customer->get_billing_vat() : '',
				'shipping_first_name' => $customer->get_shipping_first_name(),
				'shipping_last_name'  => $customer->get_shipping_last_name(),
				'shipping_company'    => $customer->get_shipping_company(),
				'shipping_address_1'  => $customer->get_shipping_address_1(),
				'shipping_address_2'  => $customer->get_shipping_address_2(),
				'shipping_postcode'   => $customer->get_shipping_postcode(),
				'shipping_city'       => $customer->get_shipping_city(),
				'shipping_country'    => $customer->get_shipping_country(),
				'shipping_state'      => $customer->get_shipping_state(),
				'shipping_phone'      => $customer->get_shipping_phone(),
				'shipping_nif'        => method_exists( $customer, 'get_shipping_nif' ) ? $customer->get_shipping_nif() : '',
				'shipping_vat'        => method_exists( $customer, 'get_shipping_vat' ) ? $customer->get_shipping_vat() : '',
			);
		}
		return $data;
	}

	/**
	 * Get shopper registration date
	 */
	private function get_shopper_registration_date( ?WC_Order $order ): ?string {
		if ( ! $order ) {
			return null;
		}
		/**
		 * Order user
		 *
		 * @var WP_User $shopper
		 */
		$shopper = \get_user_by( 'id', $order->get_customer_id() );
		if ( ! $shopper instanceof WP_User ) {
			return null;
		}
		$timestamp = strtotime( $shopper->user_registered );
		return $timestamp ? gmdate( 'c', $timestamp ) : null;
	}
}
