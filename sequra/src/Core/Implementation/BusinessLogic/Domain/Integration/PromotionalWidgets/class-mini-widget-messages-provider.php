<?php
/**
 * Implementation of MiniWidgetMessagesProviderInterface
 *
 * @package SeQura\WC
 */

namespace SeQura\WC\Core\Implementation\BusinessLogic\Domain\Integration\PromotionalWidgets;

use SeQura\Core\BusinessLogic\Domain\Integration\PromotionalWidgets\MiniWidgetMessagesProviderInterface;
use SeQura\WC\Services\I18n\Interface_I18n;

/**
 * Mini Widget Messages Provider
 */
class Mini_Widget_Messages_Provider implements MiniWidgetMessagesProviderInterface {

	/**
	 * I18n Service
	 * 
	 * @var Interface_I18n
	 */
	protected $i18n;

	/**
	 * Construct
	 */
	public function __construct( Interface_I18n $i18n ) {
		$this->i18n = $i18n;
	}

	/**
	 * Returns mini widget message
	 *
	 * @return ?string
	 */
	public function getMessage(): ?string {
		return self::MINI_WIDGET_MESSAGE[ $this->i18n->get_current_country() ] ?? null;
	}

	/**
	 * Returns mini widget below limit message
	 *
	 * @return ?string
	 */
	public function getBelowLimitMessage(): ?string {
		return self::MINI_WIDGET_BELOW_LIMIT_MESSAGE[ $this->i18n->get_current_country() ] ?? null;
	}
}
