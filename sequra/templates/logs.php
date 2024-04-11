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
<style>
	#sequra-logs-container {
		display: grid;
	}

	#sequra-logs-content{
		width: 100%;
		min-height: 500px;
	}
	
	.sequra-log__content-wrapper{
		margin: 2rem 0;
		position: relative;
	}

	.sequra-logs__title{
		position: absolute;
		top: 8px;
		font-weight: 700;
		font-size: 18px;
		left: 8px;
		opacity: .75;
		transition: all .3s;
	}
	#sequra-logs-content.active + .sequra-logs__title,
	#sequra-logs-content:focus + .sequra-logs__title{
		top: -24px;
		font-size: 14px;
		left: 0;
		opacity: 1;
	}
	
	.sequra-log__actions{
		display: flex;
		justify-content: center;
		gap: 1rem;
	}

	#sequra-logs-container .button{
		display: flex;
		align-items: center;
		padding: .25rem 1rem;
	}
	.button.button-danger, .button.button-danger:focus{
		background-color: #dc3232;
		color: #fff;
		border-color: #dc3232;
		text-decoration: none;
		text-shadow: none;
	}

	.button.button-danger:hover{
		background: #b32d2e;
		border-color: #b32d2e;
		color: #fff;
	}
	
	.button.button-danger:focus {
		box-shadow: 0 0 0 1px #fff, 0 0 0 3px #dc3232;
	}
</style>

<div class="wrap" id="sequra-logs-container">
	<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
	<p><?php echo esc_html__( 'Debug mode', 'sequra' ) . ' ' . ( $is_debug_enabled ? '✅' : '❌' ); ?>  </p>
	<div class="sequra-log__content-wrapper">
		<textarea id="sequra-logs-content"></textarea>
		<span class="sequra-logs__title">Logs</span>
	</div>
	<div class="sequra-log__actions">
		<button class="button" id="sequra-logs-reload">
			<svg xmlns="http://www.w3.org/2000/svg" height="20" viewBox="0 -960 960 960" width="20"><path fill="#2271b1" d="M480-192q-120 0-204-84t-84-204q0-120 84-204t204-84q65 0 120.5 27t95.5 72v-99h72v240H528v-72h131q-29-44-76-70t-103-26q-90 0-153 63t-63 153q0 90 63 153t153 63q84 0 144-55.5T693-456h74q-9 112-91 188t-196 76Z"/></svg>
			<?php esc_html_e( 'Reload', 'sequra' ); ?>
		</button>
		<button class="button button-danger" id="sequra-logs-clear">
			<svg xmlns="http://www.w3.org/2000/svg" height="20" viewBox="0 -960 960 960" width="20"><path fill="#fff" d="M312-144q-29.7 0-50.85-21.15Q240-186.3 240-216v-480h-48v-72h192v-48h192v48h192v72h-48v479.566Q720-186 698.85-165 677.7-144 648-144H312Zm336-552H312v480h336v-480ZM384-288h72v-336h-72v336Zm120 0h72v-336h-72v336ZM312-696v480-480Z"/></svg>
			<?php esc_html_e( 'Clear', 'sequra' ); ?>
		</button>
	</div>
</div>