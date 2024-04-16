<?php
/**
 * Logs template.
 * 
 * @package woocommerce-sequra
 * 
 * @var bool $is_debug_enabled Is Debug mode enabled.
 */

defined( 'ABSPATH' ) || die;

// Check if required variables are defined.
if ( ! isset( $is_debug_enabled ) ) {
	return;
}

?>
<div class="wrap" id="sequra-logs-container">
	<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
	<p><?php echo ( $is_debug_enabled ? '✅' : '❌' ) . ' Debug mode'; ?> </p>
	<div class="sequra-log__content-wrapper">
		<textarea id="sequra-logs-content"></textarea>
		<span class="sequra-logs__title">Logs</span>
		<span class="sequra-logs__error"></span>
		<span class="sequra-logs__success"></span>
	</div>
	<div class="sequra-log__actions">
		<button class="button" id="sequra-logs-reload">
			<svg xmlns="http://www.w3.org/2000/svg" height="20" viewBox="0 -960 960 960" width="20"><path fill="#2271b1" d="M480-192q-120 0-204-84t-84-204q0-120 84-204t204-84q65 0 120.5 27t95.5 72v-99h72v240H528v-72h131q-29-44-76-70t-103-26q-90 0-153 63t-63 153q0 90 63 153t153 63q84 0 144-55.5T693-456h74q-9 112-91 188t-196 76Z"/></svg>
			Reload
		</button>
		<button class="button button-danger" id="sequra-logs-clear">
			<svg xmlns="http://www.w3.org/2000/svg" height="20" viewBox="0 -960 960 960" width="20"><path fill="#fff" d="M312-144q-29.7 0-50.85-21.15Q240-186.3 240-216v-480h-48v-72h192v-48h192v48h192v72h-48v479.566Q720-186 698.85-165 677.7-144 648-144H312Zm336-552H312v480h336v-480ZM384-288h72v-336h-72v336Zm120 0h72v-336h-72v336ZM312-696v480-480Z"/></svg>
			Clear
		</button>
	</div>
</div>

<dialog class="sequra-modal">
	<span class="sequra-modal__title">Delete logs</span>
	<span class="sequra-modal__subtitle">Are you sure you want to clear the logs? You won't be able to recover them.</span>
	<div class="sequra-modal__actions">
		<button class="sequra-modal__ko button">Cancel</button>
		<button class="sequra-modal__ok button button-danger">Delete</button>
	</div>
</dialog>
