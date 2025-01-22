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
	protected $long_title;

	/**
	 * Starts at
	 * 
	 * @var string
	 */
	protected $starts_at;

	/**
	 * Ends at
	 *
	 * @var string
	 */
	protected $ends_at;

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
	protected $cost_description;

	/**
	 * Min amount
	 *
	 * @var int
	 */
	protected $min_amount;

	/**
	 * Max amount
	 *
	 * @var int
	 */
	protected $max_amount;

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
		$this->set_product( $data['product'] );
		$this->set_title( $data['title'] );
		$this->set_long_title( $data['longTitle'] );
		$this->set_starts_at( $data['startsAt'] );
		$this->set_ends_at( $data['endsAt'] );
		$this->set_campaign( $data['campaign'] );
		$this->set_claim( $data['claim'] );
		$this->set_description( $data['description'] );
		$this->set_icon( $data['icon'] );
		$this->set_cost_description( $data['costDescription'] );
		$this->set_min_amount( $data['minAmount'] );
		$this->set_max_amount( $data['maxAmount'] );
	}

	/**
	 * Transforms entity to its array format representation.
	 */
	public function toArray(): array {
		$data                    = parent::toArray();
		$data['product']         = $this->get_product();
		$data['title']           = $this->get_title();
		$data['longTitle']       = $this->get_long_title();
		$data['startsAt']        = $this->get_starts_at();
		$data['endsAt']          = $this->get_ends_at();
		$data['campaign']        = $this->get_campaign();
		$data['claim']           = $this->get_claim();
		$data['description']     = $this->get_description();
		$data['icon']            = $this->get_icon();
		$data['costDescription'] = $this->get_cost_description();
		$data['minAmount']       = $this->get_min_amount();
		$data['maxAmount']       = $this->get_max_amount();
		return $data;
	}

	/**
	 * Get product
	 *
	 * @return string|null
	 */
	public function get_product() {
		return $this->product;
	}

	/**
	 * Set product
	 *
	 * @param string|null $product The product.
	 */
	public function set_product( $product ) {
		$this->product = null === $product ? $product : (string) $product;
	}

	/**
	 * Get title
	 *
	 * @return string|null
	 */
	public function get_title() {
		return $this->title;
	}

	/**
	 * Set title
	 *
	 * @param string|null $title The title.
	 */
	public function set_title( $title ) {
		$this->title = null === $title ? $title : (string) $title;
	}

	/**
	 * Get long title
	 *
	 * @return string|null
	 */
	public function get_long_title() {
		return $this->long_title;
	}

	/**
	 * Set long title
	 *
	 * @param string|null $long_title The long title.
	 */
	public function set_long_title( $long_title ) {
		$this->long_title = null === $long_title ? $long_title : (string) $long_title;
	}

	/**
	 * Get starts at
	 *
	 * @return string|null
	 */
	public function get_starts_at() {
		return $this->starts_at;
	}

	/**
	 * Set starts at
	 *
	 * @param string|null $starts_at The starts at.
	 */
	public function set_starts_at( $starts_at ) {
		$this->starts_at = null === $starts_at ? $starts_at : (string) $starts_at;
	}

	/**
	 * Get ends at
	 *
	 * @return string|null
	 */
	public function get_ends_at() {
		return $this->ends_at;
	}

	/**
	 * Set ends at
	 *
	 * @param string|null $ends_at The ends at.
	 */
	public function set_ends_at( $ends_at ) {
		$this->ends_at = null === $ends_at ? $ends_at : (string) $ends_at;
	}

	/**
	 * Get campaign
	 *
	 * @return string|null
	 */
	public function get_campaign() {
		return $this->campaign;
	}

	/**
	 * Set campaign
	 *
	 * @param string|null $campaign The campaign.
	 */
	public function set_campaign( $campaign ) {
		$this->campaign = null === $campaign ? $campaign : (string) $campaign;
	}

	/**
	 * Get claim
	 *
	 * @return string|null
	 */
	public function get_claim() {
		return $this->claim;
	}

	/**
	 * Set claim
	 *
	 * @param string|null $claim The claim.
	 */
	public function set_claim( $claim ) {
		$this->claim = null === $claim ? $claim : (string) $claim;
	}

	/**
	 * Get description
	 *
	 * @return string|null
	 */
	public function get_description() {
		return $this->description;
	}

	/**
	 * Set description
	 *
	 * @param string|null $description The description.
	 */
	public function set_description( $description ) {
		$this->description = null === $description ? $description : (string) $description;
	}

	/**
	 * Get icon
	 *
	 * @return string|null
	 */
	public function get_icon() {
		return $this->icon;
	}

	/**
	 * Set icon
	 *
	 * @param string|null $icon
	 */
	public function set_icon( $icon ) {
		$this->icon = null === $icon ? $icon : (string) $icon;
	}

	/**
	 * Get cost description
	 *
	 * @return string|null
	 */
	public function get_cost_description() {
		return $this->cost_description;
	}

	/**
	 * Set cost description
	 *
	 * @param string|null $cost_description The cost description.
	 */
	public function set_cost_description( $cost_description ) {
		$this->cost_description = null === $cost_description ? $cost_description : (string) $cost_description;
	}

	/**
	 * Get min amount
	 *
	 * @return int|null
	 */
	public function get_min_amount() {
		return $this->min_amount;
	}

	/**
	 * Set min amount
	 *
	 * @param int|null $min_amount The min amount.
	 */
	public function set_min_amount( $min_amount ) {
		$this->min_amount = null === $min_amount ? $min_amount : (int) $min_amount;
	}

	/**
	 * Get max amount
	 *
	 * @return int|null
	 */
	public function get_max_amount() {
		return $this->max_amount;
	}

	/**
	 * Set max amount
	 *
	 * @param int|null $max_amount The max amount.
	 */
	public function set_max_amount( $max_amount ) {
		$this->max_amount = null === $max_amount ? $max_amount : (int) $max_amount;
	}
}
