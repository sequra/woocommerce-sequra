<?php
/**
 * Define the Payment_Method entity.
 *
 * @package SeQura\WC
 */

namespace SeQura\WC\Core\Extension\BusinessLogic\DataAccess\PaymentMethods\Entities;

use SeQura\Core\Infrastructure\ORM\Configuration\EntityConfiguration;
use SeQura\Core\Infrastructure\ORM\Configuration\IndexMap;
use SeQura\Core\Infrastructure\ORM\Entity;

// phpcs:disable WordPress.NamingConventions.ValidVariableName.PropertyNotSnakeCase, WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
/**
 * Class Payment_Method
 */
class Payment_Method extends Entity {

	/**
	 * Fully qualified name of this class.
	 */
	public const CLASS_NAME = __CLASS__;

	/**
	 * Product
	 * 
	 * @var string
	 */
	protected $product;

	/**
	 * Title
	 * 
	 * @var string
	 */
	protected $title;

	/**
	 * Long title
	 * 
	 * @var string
	 */
	protected $longTitle;

	/**
	 * Starts at
	 * 
	 * @var string
	 */
	protected $startsAt;

	/**
	 * Ends at
	 *
	 * @var string
	 */
	protected $endsAt;

	/**
	 * Campaign
	 *
	 * @var string
	 */
	protected $campaign;

	/**
	 * Claim
	 *
	 * @var string
	 */
	protected $claim;

	/**
	 * Description
	 *
	 * @var string
	 */
	protected $description;

	/**
	 * Icon
	 *
	 * @var string
	 */
	protected $icon;

	/**
	 * Cost description
	 *
	 * @var string
	 */
	protected $costDescription;

	/**
	 * Min amount
	 *
	 * @var int
	 */
	protected $minAmount;

	/**
	 * Max amount
	 *
	 * @var int
	 */
	protected $maxAmount;

	/**
	 * Does this payment method support widgets?
	 * 
	 * @var bool
	 */
	protected $supportsWidgets;

	/**
	 * Does this payment method support installment payments?
	 * 
	 * @var bool
	 */
	protected $supportsInstallmentPayments;

	/**
	 * Returns entity configuration object.
	 *
	 * @return EntityConfiguration Configuration object.
	 */
	public function getConfig(): EntityConfiguration {
		$indexMap = new IndexMap();
		$indexMap->addStringIndex( 'product' );
		return new EntityConfiguration( $indexMap, 'PaymentMethod' );
	}

	/**
	 * Sets raw array data to this entity instance properties.
	 * 
	 * @param array<string, mixed> $data Raw array data with keys for class fields. @see self::$fields for field names.
	 */
	public function inflate( array $data ): void {
		parent::inflate( $data );
		$this->setProduct( $data['product'] );
		$this->setTitle( $data['title'] );
		$this->setLongTitle( $data['longTitle'] );
		$this->setStartsAt( $data['startsAt'] );
		$this->setEndsAt( $data['endsAt'] );
		$this->setCampaign( $data['campaign'] );
		$this->setClaim( $data['claim'] );
		$this->setDescription( $data['description'] );
		$this->setIcon( $data['icon'] );
		$this->setCostDescription( $data['costDescription'] );
		$this->setMinAmount( $data['minAmount'] );
		$this->setMaxAmount( $data['maxAmount'] );
		$this->setSupportsWidgets();
		$this->setSupportsInstallmentPayments();
	}

	/**
	 * Transforms entity to its array format representation.
	 */
	public function toArray(): array {
		$data                                = parent::toArray();
		$data['product']                     = $this->getProduct();
		$data['title']                       = $this->getTitle();
		$data['longTitle']                   = $this->getLongTitle();
		$data['startsAt']                    = $this->getStartsAt();
		$data['endsAt']                      = $this->getEndsAt();
		$data['campaign']                    = $this->getCampaign();
		$data['claim']                       = $this->getClaim();
		$data['description']                 = $this->getDescription();
		$data['icon']                        = $this->getIcon();
		$data['costDescription']             = $this->getCostDescription();
		$data['minAmount']                   = $this->getMinAmount();
		$data['maxAmount']                   = $this->getMaxAmount();
		$data['supportsWidgets']             = $this->getSupportsWidgets();
		$data['supportsInstallmentPayments'] = $this->getSupportsInstallmentPayments();
		return $data;
	}

	/**
	 * Get product
	 *
	 * @return string|null
	 */
	public function getProduct() {
		return $this->product;
	}

	/**
	 * Set product
	 *
	 * @param string|null $product The product.
	 */
	public function setProduct( $product ) {
		$this->product = null === $product ? $product : (string) $product;
	}

	/**
	 * Get title
	 *
	 * @return string|null
	 */
	public function getTitle() {
		return $this->title;
	}

	/**
	 * Set title
	 *
	 * @param string|null $title The title.
	 */
	public function setTitle( $title ) {
		$this->title = null === $title ? $title : (string) $title;
	}

	/**
	 * Get long title
	 *
	 * @return string|null
	 */
	public function getLongTitle() {
		return $this->longTitle;
	}

	/**
	 * Set long title
	 *
	 * @param string|null $longTitle The long title.
	 */
	public function setLongTitle( $longTitle ) {
		$this->longTitle = null === $longTitle ? $longTitle : (string) $longTitle;
	}

	/**
	 * Get starts at
	 *
	 * @return string|null
	 */
	public function getStartsAt() {
		return $this->startsAt;
	}

	/**
	 * Set starts at
	 *
	 * @param string|null $startsAt The starts at.
	 */
	public function setStartsAt( $startsAt ) {
		$this->startsAt = null === $startsAt ? $startsAt : (string) $startsAt;
	}

	/**
	 * Get ends at
	 *
	 * @return string|null
	 */
	public function getEndsAt() {
		return $this->endsAt;
	}

	/**
	 * Set ends at
	 *
	 * @param string|null $endsAt The ends at.
	 */
	public function setEndsAt( $endsAt ) {
		$this->endsAt = null === $endsAt ? $endsAt : (string) $endsAt;
	}

	/**
	 * Get campaign
	 *
	 * @return string|null
	 */
	public function getCampaign() {
		return $this->campaign;
	}

	/**
	 * Set campaign
	 *
	 * @param string|null $campaign The campaign.
	 */
	public function setCampaign( $campaign ) {
		$this->campaign = null === $campaign ? $campaign : (string) $campaign;
	}

	/**
	 * Get claim
	 *
	 * @return string|null
	 */
	public function getClaim() {
		return $this->claim;
	}

	/**
	 * Set claim
	 *
	 * @param string|null $claim The claim.
	 */
	public function setClaim( $claim ) {
		$this->claim = null === $claim ? $claim : (string) $claim;
	}

	/**
	 * Get description
	 *
	 * @return string|null
	 */
	public function getDescription() {
		return $this->description;
	}

	/**
	 * Set description
	 *
	 * @param string|null $description The description.
	 */
	public function setDescription( $description ) {
		$this->description = null === $description ? $description : (string) $description;
	}

	/**
	 * Get icon
	 *
	 * @return string|null
	 */
	public function getIcon() {
		return $this->icon;
	}

	/**
	 * Set icon
	 *
	 * @param string|null $icon
	 */
	public function setIcon( $icon ) {
		$this->icon = null === $icon ? $icon : (string) $icon;
	}

	/**
	 * Get cost description
	 *
	 * @return string|null
	 */
	public function getCostDescription() {
		return $this->costDescription;
	}

	/**
	 * Set cost description
	 *
	 * @param string|null $costDescription The cost description.
	 */
	public function setCostDescription( $costDescription ) {
		$this->costDescription = null === $costDescription ? $costDescription : (string) $costDescription;
	}

	/**
	 * Get min amount
	 *
	 * @return int|null
	 */
	public function getMinAmount() {
		return $this->minAmount;
	}

	/**
	 * Set min amount
	 *
	 * @param int|null $minAmount The min amount.
	 */
	public function setMinAmount( $minAmount ) {
		$this->minAmount = null === $minAmount ? $minAmount : (int) $minAmount;
	}

	/**
	 * Get max amount
	 *
	 * @return int|null
	 */
	public function getMaxAmount() {
		return $this->maxAmount;
	}

	/**
	 * Set max amount
	 *
	 * @param int|null $maxAmount The max amount.
	 */
	public function setMaxAmount( $maxAmount ) {
		$this->maxAmount = null === $maxAmount ? $maxAmount : (int) $maxAmount;
	}

	/**
	 * Get supports widgets
	 *
	 * @return bool
	 */
	public function getSupportsWidgets() {
		if ( null === $this->supportsWidgets ) {
			$this->setSupportsWidgets();
		}
		return $this->supportsWidgets;
	}

	/**
	 * Set supports widgets
	 */
	protected function setSupportsWidgets() {
		$this->supportsWidgets = is_string( $this->product ) && in_array( $this->product, array( 'i1', 'pp5', 'pp3', 'pp6', 'pp9', 'sp1' ), true );
	}

	/**
	 * Get supports installment payments
	 *
	 * @return bool
	 */
	public function getSupportsInstallmentPayments() {
		if ( null === $this->supportsInstallmentPayments ) {
			$this->setSupportsInstallmentPayments();
		}
		return $this->supportsInstallmentPayments;
	}

	/**
	 * Set supports installment payments
	 */
	public function setSupportsInstallmentPayments() {
		$this->supportsInstallmentPayments = is_string( $this->product ) && in_array( $this->product, array( 'pp3' ), true );
	}
}
