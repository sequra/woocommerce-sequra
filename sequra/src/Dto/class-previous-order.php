<?php
/**
 * Previous Order DTO
 *
 * @package    SeQura/WC
 */

namespace SeQura\WC\Dto;

use SeQura\Core\Infrastructure\ServiceRegister;
use SeQura\WC\Services\Pricing\Interface_Pricing_Service;
use WC_DateTime;
use WC_Order;

/**
 * Previous Order DTO.
 */
class Previous_Order extends Dto {

	/**
	 * ISO 8601 date
	 * 
	 * @var string
	 */
	public $created_at;

	/**
	 * Currency
	 * 
	 * @var string
	 */
	public $currency;

	/**
	 * Amount in cents
	 * 
	 * @var int
	 */
	public $amount;

	/**
	 * Constructor
	 */
	public function __construct( string $created_at, string $currency, int $amount_in_cents ) {
		$this->created_at = $created_at;
		$this->currency   = $currency;
		$this->amount     = $amount_in_cents;
	}

	/**
	 * Create from order
	 */
	public static function from_order( WC_Order $order ): self {
		$amount = ServiceRegister::getService( Interface_Pricing_Service::class )
		->to_cents( (float) $order->get_total( 'edit' ) );
		/**
		 * Order date
		 *
		 * @var WC_DateTime $date
		 */
		$date = $order->get_date_created( 'edit' );

		$created_at = $date ? $date->date( 'c' ) : ''; // TODO: If date is null, should we use the current date?

		return new self( 
			$created_at, 
			$order->get_currency(), 
			$amount 
		);
	}
}
