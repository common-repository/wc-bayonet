<?php

/**
 * The public-facing functionality of the plugin.
 *
 * Defines the hooks to send the Consulting API and Feedback API in checkout process.
 *
 * @since      1.0.0
 * @package    WC_Bayonet
 * @subpackage WC_Bayonet/public
 * @author     Pequeño Cuervo <miguel@pcuervo.com>
 */
class WC_Bayonet_Public {

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
	 * The Bayonet Client for API 
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string 	$version  Bayonet API client.
	 */
	private $bayonet_client;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @param      string    		$plugin_name    The name of the plugin.
	 * @param      string    		$version    	The version of this plugin.
	 * @param      BayonetClient    $version    	BayonetClient instance for consulting API.
	 */
	public function __construct( $plugin_name, $version, $bayonet_client ) {
		$this->plugin_name = $plugin_name;
		$this->version = $version;
		$this->bayonet_client = $bayonet_client;
	}

	/*----------  ACTIONS  ----------*/
	
	/**
	 * Register the script used in the checkout page. The script
	 * will be used for validation and other purposes. 
	 *
	 * @since    1.0.0
	 */
	public function enqueue_checkout_scripts() {
		if( ! is_checkout() ) return;

		wp_enqueue_script( 'bayonet-fingerprinting', 'https://cdn.bayonet.io/bayonet-fingerprinting-1.0.min.js', array(), $this->version, true );
		wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'js/wc-bayonet.js', array( 'jquery', 'bayonet-fingerprinting' ), $this->version, true );
		// Localize JS Key
		$js_key = '1' == get_option( 'bynt_is_sandbox' ) ? get_option( 'bynt_sandbox_js_api_key' ) : get_option( 'bynt_live_js_api_key' );

		if( empty( $js_key ) ) $js_key = '-1';
		wp_localize_script( $this->plugin_name, 'jsKey', $js_key );
		wp_localize_script( $this->plugin_name, 'ajax_url', admin_url('admin-ajax.php') );
	}

	/**
	 * Call the Consulting API before processing payment. In case of
	 * receiving a "high-risk" order, the payment will not be processed
	 * by raising a WooCommerce Error.
	 *
	 * @since    1.0.0
	 */
	public function consulting_api_validation() {
		session_start();
		$logger = new WC_Logger();
		$context = array( 'source' => $this->plugin_name );
		$logger->debug( 'Probando con payment_method: ' . $_POST['payment_method'], $context );
		$logger->debug( 'Request params:', $context );
		$logger->debug( print_r( $_POST, true ), $context );
		
		$woocommerce = WC();
		$customer_full_name = $_POST['billing_first_name'] . ' ' . $_POST['billing_last_name'];
		$amount 			= WC_Bayonet_Util::get_current_cart_total( $woocommerce->cart );
		$currency_code 		= get_woocommerce_currency();
		$payment_method 	= WC_Bayonet_Util::get_payment_method( $_POST['payment_method'] );
		if( WC_Bayonet_Util::is_saved_card( $_POST ) && 'stripe' == $_POST['payment_method'] ) $payment_method = 'saved_card';
		$credit_card_number = WC_Bayonet_Util::get_ccn( $_POST );
		$timestamp			= strtotime( "now" );
		$coupon 			= WC_Bayonet_Util::get_formatted_coupons( $woocommerce->cart );
		$payment_gateway 	= WC_Bayonet_Util::get_payment_gateway( $_POST['payment_method'] );
		$product_name		= WC_Bayonet_Util::get_formatted_products( $woocommerce->cart );
		// Required fields for Consulting API
		$consulting_body 	= array(
			'channel' 			 => 'ecommerce',
			'email' 			 => $_POST['billing_email'],
			'consumer_name' 	 => $customer_full_name,
			'cardholder_name' 	 => $customer_full_name,
			'payment_method' 	 => $payment_method,
			'card_number' 		 => $credit_card_number,   
			'transaction_amount' => $amount,
			'currency_code' 	 => $currency_code,
			'transaction_time' 	 => $timestamp,   
			'coupon' 			 => $coupon,
			'product_name' 		 => $product_name
		);
		// Only add payment_gateway if payment method is not offline.
		if( 'offline' != $payment_method ){
			$consulting_body['payment_gateway'] = $payment_gateway;
		}
		if( isset( $_SESSION['device_fingerprint'] ) ){
			$consulting_body['device_fingerprint'] = $_SESSION['device_fingerprint'];
		}
		// Additonal fields. Send them if you possess them
		$additional_consulting_fields = WC_Bayonet_Util::get_additional_consulting_fields( $_POST );
		if( ! empty( $additional_consulting_fields ) ){
			$consulting_body = array_merge( $consulting_body, $additional_consulting_fields );
		}
		
		$logger->debug( 'Consulting API body: ', $context );
		$logger->debug( print_r( $consulting_body, true ), $context );
	
		$this->bayonet_client->consulting([
			'body' => $consulting_body,
			'on_success' => function($response) {
				$this->process_successfull_response( $response );
				$logger = new WC_Logger();
				$context = array( 'source' => $this->plugin_name );
				$logger->debug( 'Consulting API Success!', $context );
			},
			'on_failure' => function($response) {
				$this->process_unsuccessfull_response( $response );
			}
		]);
	}

	/**
	 * Call the Consulting API before processing payment. In case of
	 * receiving a "high-risk" order, the payment will not be processed
	 * by raising a WooCommerce Error.
	 *
	 * @since 1.0.0
	 * @param $succesful_response
	 */
	public function process_successfull_response( $successful_response ) {
		foreach ( $successful_response as $response_key => $response_val ) {
			if( 'risk_level' == $response_key ){
				// Assume empty risk level is green and save to be used with Feedback API.
				if ( empty( $response_val ) ) {
					$_SESSION['risk_level'] = 'GREEN';
					continue;
				}
				$risk_level = $response_val[0];
				// If risk_level is RED, raise a WooCommerce Error-
				if( 'RED' == $risk_level ) wc_add_notice( get_option( 'bynt_fraud_msg' ), 'error' );

				// Save risk_level to be used with Feedback API (RED or YELLOW).
				$_SESSION['risk_level'] = $risk_level;		
			} 
			if( 'feedback_api_trans_code' == $response_key ){
				// Save feedback_api_trans_code to be used with Feedback API.
				$_SESSION['feedback_api_trans_code'] = $response_val;
			}
		}
		// Call Feedback API if fraud detected.
		if( 'RED' == $_SESSION['risk_level'] ) {
			$this->send_feedback( $_SESSION['feedback_api_trans_code'], 'suspected_fraud', -1, 'NA' );
		}
	}

	/**
	 * In case of receiving an unsuccessful response, 
	 * log errors for future debugging.
	 *
	 * @since 1.0.0
	 * @todo DEFINIR QUE HACER CON ESTO Y FORMATO DE LOGGEO....
	 * @param $unsuccesful_response
	 */
	public function process_unsuccessfull_response( $unsuccessful_response ) {
		//wc_add_notice( __( 'just stoppin on failure' ), 'error' );	
		$logger = new WC_Logger();
		$context = array( 'source' => $this->plugin_name );
		$logger->error( '**********************************', $context );
		$logger->error( 'Consulting API Error', $context );
		$logger->error( print_r( $unsuccessful_response, true ), $context );
		$logger->error( '**********************************', $context );
	}

	/**
	 * Call the Feedback API in "Thank you" page after order has been processed.
	 * This is also used to update the "risk_level" in the order, to be 
	 * displayed in the WP Admin Panel.
	 *
	 * @since    1.0.0
	 * @param integer $order_id - The id of the placed order.
	 */
	public function send_feedback_thankyou_page( $order_id ) {
		session_start();
		if( isset( $_SESSION['feedback_api_trans_code'] ) && isset( $_SESSION['risk_level'] ) ){
			$order = wc_get_order( $order_id );
			//$feedback_api_trans_code = get_transient( 'feedback_api_trans_code' );
			$feedback_api_trans_code = $_SESSION['feedback_api_trans_code'];
			$risk_level = $_SESSION['risk_level'];
			//$risk_level = get_transient( 'risk_level' );
			$bayonet_status = 'success';
			update_post_meta( $order_id, '_risk_level', $risk_level );
			update_post_meta( $order_id, '_sent_to_bayonet', 'yes' );
			$this->send_feedback( $feedback_api_trans_code, $bayonet_status, -1, $order_id );
			return;
		}

		$logger = new WC_Logger();
		$context = array( 'source' => $this->plugin_name );
		$logger->error( '**********************************', $context );
		$logger->error( 'Feedback API not called because feedback_api_trans_code and risk_level were not set.', $context );
		$logger->error( '**********************************', $context );
	}
	/**
	 * Call the Feedback API to send the information about an order.
	 *
	 * @since    1.0.0
	 * @param string $feedback_api_trans_code - code return by Consulting API
	 * @param string $transaction_status - Status of the transaction
	 * @param string $order_id - The ID of the created order
	 * @param string $order_id - The ID of the created order
	 */
	private function send_feedback( $feedback_api_trans_code, $transaction_status, $bank_decline_reason=-1, $order_id ) {
		$this->bayonet_client->feedback([
			'body' => [
				// Required fields, otherwise a 400 error will be returned
				'feedback_api_trans_code' 	=> $feedback_api_trans_code,
				'transaction_status' 		=> $transaction_status,
				'transaction_id' 			=> $order_id,   // your internal assigned id
			],
			// callback functions for successful and failed API calls
			'on_success' => function($response) {
				$logger = new WC_Logger();
				$context = array( 'source' => $this->plugin_name );
				$logger->debug( 'SUCCESFUL FEEDBACK RESPONSE:', $context );
				$logger->debug( print_r( $response, true ), $context );
				$logger->debug( '**********************************', $context );
			},
			'on_failure' => function($response) {
				$logger = new WC_Logger();
				$context = array( 'source' => $this->plugin_name );
				$logger->error( 'FEEDBACK RESPONSE ERROR:', $context );
				$logger->error( print_r( $response, true ), $context );
				$logger->error( '**********************************', $context );
					}
		]);
		$this->unset_session_variables();
	}

	/**
	 * Save device fingerprint token for current user in checkout.
	 *
	 * @since    1.0.0
	 */
	public function save_fingerprint_token_ajax(){
		error_log('here');
		session_start();
		$_SESSION['device_fingerprint'] = $_POST['token'];
		$msg = array( 
			'msg' => 'Device fingerprint saved...'
		);
		echo json_encode( $msg );
		wp_die();
	}

	/**
	 * Save device fingerprint token for current user in checkout.
	 *
	 * @since    1.0.0
	 */
	public function unset_session_variables(){
		if( isset( $_SESSION['risk_level'] ) ) unset(  $_SESSION['risk_level'] );
		if( isset( $_SESSION['device_fingerprint'] ) ) unset(  $_SESSION['device_fingerprint'] );
		if( isset( $_SESSION['feedback_api_trans_code'] ) ) unset(  $_SESSION['feedback_api_trans_code'] );
	}

}
