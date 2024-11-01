<?php

/**
 * @link              https://bayonet.io
 * @since             1.0.0
 * @package           WC_Bayonet
 *
 * Plugin Name:       WC Bayonet
 * Plugin URI:        https://bayonet.io/wc-plugin
 * Description:       Enabling online merchants and financial institutions to increase sales and manage risk through collective intelligence. Start making smarter business decisions today.
 * Version:           1.0.0
 * Author:            Bayonet Technologies, Inc.
 * Author URI:        https://bayonet.io/
 * License:           MIT License
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       wc-bayonet
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Runs during plugin activation.
 */
function activate_wc_bayonet() {
	// If WooCommerce is not installed, do no activate WC Bayonet.
    if( ! class_exists( 'WooCommerce' ) ){
    	echo '<p>'.__('This plugin requires WooCommerce. <a target="_blank" href="https://wordpress.org/plugins/woocommerce/">You can get it here.</a>', 'wc-bayonet').'</p>';

	    //Adding @ before will prevent XDebug output
	    @trigger_error( __('Please update all AnsPress extensions before activating.', 'wc-bayonet'), E_USER_ERROR );
	    return;
    }

	require_once plugin_dir_path( __FILE__ ) . 'includes/class-wc-bayonet-activator.php';
	WC_Bayonet_Activator::activate();
}

/**
 * Runs during plugin deactivation.
 */
function deactivate_wc_bayonet() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-wc-bayonet-deactivator.php';
	WC_Bayonet_Deactivator::deactivate();
}

register_activation_hook( __FILE__, 'activate_wc_bayonet' );
register_deactivation_hook( __FILE__, 'deactivate_wc_bayonet' );

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require plugin_dir_path( __FILE__ ) . 'includes/class-wc-bayonet.php';

/**
 * Begins execution of WC Bayonet plugin.
 *
 * @since    1.0.0
 */
function run_wc_bayonet() {
	$plugin = new WC_Bayonet();
	$plugin->run();
}
run_wc_bayonet();