<?php
/**
 * Link to an action.
 *
 * @package    SeQura/WC
 * @var string $args The arguments. Must contain the 'href' key. Must contain the 'text' key.
 */

defined( 'WPINC' ) || die;
if ( ! isset( $args['href'] ) || ! isset( $args['text'] ) ) {
	return;
}
?>
<a href="<?php echo \esc_url( $args['href'] ); ?>"><?php echo \esc_html( $args['text'] ); ?></a>
