<?php

/**
 * Fired during plugin deactivation.
 *
 * This class defines all code necessary to run during the plugin's deactivation.
 *
 * @since      1.0.0
 * @package    WC_Bayonet
 * @subpackage WC_Bayonet/includes
 * @author     Pequeño Cuervo <miguel@pcuervo.com>
 */
class WC_Bayonet_Deactivator {

	/**
	 * Remove options from WP database. 
	 *
	 * @since    1.0.0
	 */
	public static function deactivate() {
		delete_option( 'bynt_version' );
		delete_option( 'bynt_api_version' );
		delete_option( 'bynt_is_sandbox' );
		delete_option( 'bynt_sandbox_api_key' );
		delete_option( 'bynt_live_api_key' );
		delete_option( 'bynt_sandbox_js_api_key' );
		delete_option( 'bynt_live_js_api_key' );
		delete_option( 'bynt_fraud_msg' );
		delete_option( 'bynt_pending_historical_transactions' );
	}

}