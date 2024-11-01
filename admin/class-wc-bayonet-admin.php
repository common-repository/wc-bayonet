<?php

/**
 * The admin-specific functionality of the plugin.
 *
 * Creates the page to add Bayonet's API credentials, action to 
 * send existing orders using Feedback-Historial API and other
 * admin-specific functionality. 
 *
 * @package    WC_Bayonet
 * @subpackage WC_Bayonet/admin
 * @author     Pequeño Cuervo <miguel@pcuervo.com>
 */
class WC_Bayonet_Admin {

	/**
	 * The ID of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $plugin_name    The ID of this plugin.
	 */
	private $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $version    The current version of this plugin.
	 */
	private $version;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @param      string    $plugin_name       The name of this plugin.
	 * @param      string    $version    The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {
		$this->plugin_name = $plugin_name;
		$this->version = $version;
	}

	/*----------  ACTIONS  ----------*/

	/**
	 * Add styles in Bayonet Settings page and 
	 * shop_order post type (for labels' color).
	 *
	 * @since    1.0.0
	 */
	public function enqueue_styles() {
		//if( ! isset( $_GET['post_type'] ) ) return;
		//if( 'shop_order' != $_GET['post_type'] ) return;

		wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'css/wc-bayonet.css', array(), $this->version, 'all' );
	}

	/**
	 * Register the script used to send Feedback CSV. 
	 *
	 * @since    1.0.0
	 */
	public function enqueue_csv_script() {
		if( ! isset( $_GET['page'] ) ) return;
		if( 'bayonet_settings_page' !== $_GET['page'] ) return;

		wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'js/csv-feedback.js', array( 'jquery' ), $this->version, true );
		wp_localize_script( $this->plugin_name, 'admin_ajax_url', admin_url('admin-ajax.php') );
	}

	/**
	 * Show notice in Admin if Bayonet API Keys haven't been added.
	 *
	 * @since    1.0.0
	 */
	public function show_missing_api_key_notice() {
		if( ! empty( get_option( 'bynt_sandbox_api_key' ) ) && ! empty( get_option( 'bynt_live_api_key' ) ) ) return;
	?>
	    <div class="notice notice-warning is-dismissible">
	        <p><?php _e( 'Please add your API Keys to begin using Bayonet.', 'wc-bayonet' ); ?> <a href="<?php echo get_admin_url() . 'admin.php?page=bayonet_settings_page'; ?>"><?php _e( 'Settings' ) ?></a></p>
	    </div>
	    <?php
	}

	/**
	 * Show notice in Admin if Bayonet API Keys haven't been added.
	 *
	 * @since    1.0.0
	 */
	public function show_pending_historial_transactions_notice() {
		if( '0' == get_option( 'bynt_pending_historical_transactions' ) ) return;
	?>
	    <div class="notice notice-warning is-dismissible">
	        <p><?php _e( 'Before you start using Bayonet, you have to send Feedback of your existing orders.', 'wc-bayonet' ); ?> <a href="<?php echo get_admin_url(). 'admin.php?page=bayonet_settings_page'; ?>"><?php _e( 'Settings' ) ?></a></p>
	    </div>
	    <?php
	}

	/**
	 * Add a page under "WooCommerce" tab.
	 *
	 * @since    1.0.0
	 */
	public function add_options_page() {
		add_submenu_page( 
			'woocommerce',
			__( 'Bayonet Settings', 'wc-bayonet' ),
			__( 'Bayonet Settings', 'wc-bayonet' ),
			'manage_options', 
			'bayonet_settings_page', 
			array( $this, 'display_settings_page' ) 
		); 
	}

	/**
	 * Load partial for Bayonet's settings page. 
	 *
	 * @since    1.0.0
	 */
	public function  display_settings_page() {
		ob_start();
		require( plugin_dir_path( __FILE__ ) . 'partials/wc-bayonet-admin-settings-page.php' );
		$output = ob_get_clean();
		echo $output;
	}

	/**
	 * Save options added in the settings page.
	 *
	 * @since    1.0.0
	 * @param      integer    $post_id       
	 * @param      string    $version    The version of this plugin.
	 */
	public function  save_settings( $post_id ) {
		$current_page = isset( $_GET['page'] ) ? $_GET['page'] : '-1';
		if( 'bayonet_settings_page' != $current_page || empty( $_POST ) ) return;

		if( isset( $_POST['enable_bayonet_sandbox'] ) && check_admin_referer( 'save_settings', '_enable_bayonet_sandbox_nonce') ){
			update_option( 'bynt_is_sandbox', 1 );
		} else {
			update_option( 'bynt_is_sandbox', 0 );
		}
		if( isset( $_POST['bynt_sandbox_api_key'] ) && check_admin_referer( 'save_settings', '_bynt_sandbox_api_key_nonce') ){
			update_option( 'bynt_sandbox_api_key', $_POST['bynt_sandbox_api_key'] );
		}
		if( isset( $_POST['bynt_live_api_key'] ) && check_admin_referer( 'save_settings', '_bynt_live_api_key_nonce') ){
			update_option( 'bynt_live_api_key', $_POST['bynt_live_api_key'] );
		}
		if( isset( $_POST['bynt_sandbox_js_api_key'] ) && check_admin_referer( 'save_settings', '_bynt_sandbox_js_api_key_nonce') ){
			update_option( 'bynt_sandbox_js_api_key', $_POST['bynt_sandbox_js_api_key'] );
		}
		if( isset( $_POST['bynt_live_js_api_key'] ) && check_admin_referer( 'save_settings', '_bynt_live_js_api_key_nonce') ){
			update_option( 'bynt_live_js_api_key', $_POST['bynt_live_js_api_key'] );
		}
		if( isset( $_POST['bynt_fraud_msg'] ) && check_admin_referer( 'save_settings', '_bynt_fraud_msg_nonce') ){
			update_option( 'bynt_fraud_msg', $_POST['bynt_fraud_msg'] );
		}
	}

	/**
	 * Show value for risk level column in Admin Orders Page.
	 *
	 * @since    1.0.0
	 * @param    string 	$column - Name of the column       
	 * @param    integer    $post_id - The id of the current post
	 */
	public function display_risk_level_admin_orders( $column, $post_id ) {
		if( 'risk_level' == $column ){
			$risk_level = get_post_meta( $post_id, '_risk_level', true );
			if( empty( $risk_level ) ) {
				echo '-';
				return;
			}
			echo '<span class="bynt-' . strtolower( $risk_level )  . '">' . $risk_level . '</span>';
		}
	}

	/**
	 * Process orders that have to be sent to Bayonet
	 * for Feedback API. A CSV file will be filled with orders' data.
	 *
	 * @since    1.0.0
	 */
	public function process_pending_feedback_ajax(){
		$total_orders 		= $_POST['total_orders'];
		$orders_to_process 	= $_POST['orders_to_process'];
		$csv_path 			= plugin_dir_path( __FILE__ ) . 'csv/wc-feedback.csv';
		// Fetch Orders that haven't been processed.
		$pending_orders = get_posts(
			array(
			    'numberposts' => $orders_to_process,
			    'post_type'   => 'shop_order',
			    'post_status' => array_keys( wc_get_order_statuses() ),
			    'meta_query' => array(
					array(
						'key' 		=> '_sent_to_bayonet',
						'compare' 	=> 'NOT EXISTS'
					)
				)
			)
		);
		$bayonet_csv = new WC_Bayonet_Feedback_CSV( $csv_path );
		$bayonet_csv->process_orders_data( $pending_orders );
	
		$remaining_orders = intval( $total_orders ) - count( $pending_orders );
		if( $remaining_orders < 0 ) $remaining_orders = 0;
		$msg = array(
			'processed_orders' => count( $pending_orders ),
			'remaining_orders' => $remaining_orders
		);
		echo json_encode( $msg );
		wp_die();
	}

	/**
	 * Send a CSV file containing order's info to Bayonet.
	 *
	 * @since    1.0.0
	 */
	public function send_feedback_csv_ajax(){
		$csv_path 		= plugin_dir_path( __FILE__ ) . 'csv/wc-feedback.csv';
		$bayonet_csv 	= new WC_Bayonet_Feedback_CSV( $csv_path );
		if( $bayonet_csv->send_csv() ){
			$msg = array( 'errors' => 0 );
			echo json_encode( $msg );
			wp_die();
		}
		$msg = array( 
			'errors' => 1
		);
		echo json_encode( $msg );
		wp_die();
	}

	/*----------  FILTERS  ----------*/

	/**
	 * Add settings link in plugin's page
	 *
	 * @since    1.0.0
	 */
	public function add_plugin_actions_links( $links ) {
		$settings_link = '<a href="'. esc_url( get_admin_url(null, 'admin.php?page=bayonet_settings_page') ) .'">' . __('Settings') . '</a>';
		array_unshift( $links, $settings_link );
		return $links;
	}

	/**
	 * Add the header for risk level column in Admin Orders page
	 *
	 * @since    1.0.0
	 * @param    array 	$columns - The columns for orders   
	 * @param    array  $new_columns - New columns for orders
	 */
	public function risk_level_column_header($columns){
	    $new_columns = (is_array($columns)) ? $columns : array();
	    unset( $new_columns['order_actions'] );

	    $new_columns['risk_level'] = __('Bayonet Risk Level');
	    $new_columns['order_actions'] = $columns['order_actions'];
	    return $new_columns;
	}

}