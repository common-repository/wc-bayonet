<?php

/**
 * Creates a CSV to be sent for the Historical Backfill
 *
 * Loops through all the pending orders and creates a CSV
 * based on the Sample CSV at bayonet.io
 *
 * @package    WC_Bayonet
 * @subpackage WC_Bayonet/includes
 * @author     Your Name <email@example.com>
 */
class WC_Bayonet_Feedback_CSV {

	/**
	 * The CSV file to be sent.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      File pointer resource 	$output    
	 */
	private $output;

	/**
	 * Path to CSV file.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string $filepath
	 */
	private $filepath;

	/**
	 * Initialize the collections used to maintain the actions and filters.
	 *
	 * @since    1.0.0
	 * @param    string 	$filepath - The relative path to the CSV file.
	 */
	public function __construct( $filepath ) {
		$this->filepath = $filepath;
	}

	// /**
	//  * Add headers for CSV file as found in Bayonet sample CSV.
	//  *
	//  * @since    1.0.0
	//  */
	// public function addCSVHeaders(){
	// 	$headers = array( 'type', 'payment_method', 'transaction_status', 'bank_decline_reason', 'currency_code', 'product_name', 'transaction_id', 'transaction_amount', 'transaction_timestamp', 'chargeback_timestamp', 'channel', 'email', 'consumer_name', 'cardholder_name', 'card_bin', 'card_last4', 'payment_gateway', 'user_id', 'device_fingerprint', 'telephone', 'address_line_1', 'address_line_2', 'city', 'state', 'country', 'zip_code', 'expedited_shipping', 'chargeback_reason', 'coupon_name', 'bank_auth_code' );	
	// 	fputcsv( $this->output, $headers );
	// }
 
	public function process_orders_data( $orders ){
		$this->output = fopen( $this->filepath, "a");
		foreach ( $orders as $order_post ) {
			$order_id = $order_post->ID;
			$order = new WC_Order( $order_id );
			$type = 'transaction';
			$payment_method 		= get_post_meta( $order_id, '_payment_method', true );
			$transaction_status 	= 'n/a';
			$bank_decline_reason 	= '';
			$currency_code 			= get_woocommerce_currency();
			$products				= WC_Bayonet_Util::get_formatted_order_products( $order );
			$transaction_id			= $order_id;
			$transaction_amount 	= $order->get_total();
			$transaction_timestamp	= strtotime( $order_post->post_date );
			$chargeback_timestamp 	= '';
			$channel				= 'ecommerce';
			$email					= get_option( 'admin_email' );
			$consumer_name			= $order->get_billing_first_name() . ' ' . $order->get_billing_last_name();
			$cardholder_name		= '';
			$card_bin				= '';
			$card_last4				= '';
			$payment_gateway		= wc_get_payment_gateway_by_order( $order );
			$user_id				= $order->get_user_id();
			$device_fingerprint 	= '';
			$telephone				= $order->get_billing_phone();
			$address_line_1			= $order->get_billing_address_1();
			$address_line_2			= $order->get_billing_address_2();
			$city					= $order->get_billing_city();
			$state					= $order->get_billing_state();
			$country				= WC_Bayonet_Util::get_iso_country_code( $order->get_billing_country() );
			$postcode				= $order->get_billing_postcode();
			$expedited_shipping		= false;
			$chargeback_reason    	= '';
			$coupon_name			= WC_Bayonet_Util::get_formatted_order_coupons( $order );
			$bank_auth_code			= '';
			$order_data = array( $type, $payment_method, $transaction_status, $bank_decline_reason, $currency_code, $products, $transaction_id, $transaction_amount, $transaction_timestamp, $chargeback_timestamp, $channel, $email, $consumer_name, $cardholder_name, $card_bin, $card_last4, $payment_gateway->get_method_title(), $user_id, $device_fingerprint, $telephone, $address_line_1, $address_line_2, $city, $state, $country, $postcode, $expedited_shipping, $chargeback_reason, $coupon_name );
			fputcsv( $this->output, $order_data );
			update_post_meta( $order_id, '_sent_to_bayonet', 'yes' );
		}
		fclose( $this->output );
	}

	public function send_csv(){
		$api_key = '1' == get_option( 'bynt_is_sandbox' ) ? get_option( 'bynt_sandbox_api_key' ) : get_option( 'bynt_live_api_key' );
		$post_values = Array(
		    'email' 	=>  get_option( 'admin_email' ),
		    'api_key' 	=> $api_key,
		    'source' 	=> "WordPress/WooCommerce"
		);
		$file = $this->filepath;
		$post_values["file[name]"] = new CurlFile( $file, "text/csv" );

		$curl = curl_init();
		curl_setopt( $curl, CURLOPT_URL, "https://bayonet.io/historic-backfill/csv-upload" );
		curl_setopt( $curl, CURLOPT_HTTPHEADER , array('Content-Type: multipart/form-data') );
		curl_setopt( $curl, CURLOPT_POST, true );
		curl_setopt( $curl, CURLOPT_POSTFIELDS, $post_values );
		curl_setopt( $curl, CURLOPT_RETURNTRANSFER, TRUE );

		$curlResult = curl_exec( $curl);

		if ( curl_error( $curl ) ) {			
			$logger = new WC_Logger();
			$context = array( 'source' => 'wc-bayonet' );
			$logger->debug( '**********************************', $context );
			$logger->error( 'Bayonet CSV Upload', $context );
			$logger->error( curl_error( $curl ), $context );
			$logger->debug( '**********************************', $context );
			curl_close ( $curl );
			return false;
		}
		
		curl_close ( $curl );
		update_option( 'bynt_pending_historical_transactions', 0 );
		$this->empty_csv();
		return true;
	}

	public function empty_csv(){
		$this->output = fopen( $this->filepath, "w");
		fclose( $this->output );
	}

}