<?php
/**
 * Receipt page for seQura payment gateway.
 *
 * @package    SeQura/WC
 * @var string $args The arguments. Must contain:
 */

defined( 'WPINC' ) || die;

?>

<p><?php echo wp_kses_post( __( 'Thank you for your order, please click the button below to pay with seQura.', 'sequra' ) ); ?></p>