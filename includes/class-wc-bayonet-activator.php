<?php 

/**
 * Fired during plugin activation.
 *
 * This class defines all code necessary to run during the plugin's activation.
 *
 * @since      1.0.0
 * @package    WC_Bayonet
 * @subpackage WC_Bayonet/includes
 * @author     Pequeño Cuervo <miguel@pcuervo.com>
 */
class WC_Bayonet_Activator {

	/**
	 * Add options to WP database. 
	 *
	 * @since    1.0.0
	 */
	public static function activate() {
		// Current plugin version
		add_option( 'bynt_version', WC_Bayonet::PLUGIN_VERSION );
		// API Version
		add_option( 'bynt_api_version', WC_Bayonet::API_VERSION );
		// Check if sandbox mode
		add_option( 'bynt_is_sandbox', true );
		// Sandbox Api Key
		add_option( 'bynt_sandbox_api_key', '' );
		// Live Api Key
		add_option( 'bynt_live_api_key', '' );
		// Sandbox Api Key of JS
		add_option( 'bynt_sandbox_js_api_key', '' );
		// Live Api Key JS
		add_option( 'bynt_live_js_api_key', '' );
		// Fraud detection message
		add_option( 'bynt_fraud_msg', __('Transaction stopped because of suspected fraud.', 'wc-bayonet') );
		// Check if historical transactions have been sent
		add_option( 'bynt_pending_historical_transactions', true );
	}

}