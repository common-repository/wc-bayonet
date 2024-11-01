<?php
/**
 * Provide an admin settings area for WC Bayonet
 *
 * @since      1.0.0
 * @package    WC_Bayonet
 * @subpackage WC_Bayonet/admin/partials
 */

$title = __('Bayonet Settings', 'wc-bayonet');
$all_orders = get_posts(
	array(
	    'numberposts' => -1,
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
$total_orders = count( $all_orders );
?>

<div class="[ wrap ]">
	<div class="bynt-brand">
		<img src="http://pcuervo.com/bayonet-assets/bayonte_logo.png" alt="Bayonet">
	</div>
	<h1><?php echo esc_html( $title ); ?></h1>
	<hr>
	<form method="post" action="admin.php?page=bayonet_settings_page" novalidate="novalidate">
		<?php wp_nonce_field( 'save_settings', '_enable_bayonet_sandbox_nonce' ); ?>
		<?php wp_nonce_field( 'save_settings', '_bynt_sandbox_api_key_nonce' ); ?>
		<?php wp_nonce_field( 'save_settings', '_bynt_live_api_key_nonce' ); ?>
		<?php wp_nonce_field( 'save_settings', '_bynt_sandbox_js_api_key_nonce' ); ?>
		<?php wp_nonce_field( 'save_settings', '_bynt_live_js_api_key_nonce' ); ?>
		<?php wp_nonce_field( 'save_settings', '_bynt_fraud_msg_nonce' ); ?>
		<table class="form-table">
			<tr>
				<th scope="row"><?php _e( 'Enable/Disable', 'wc-bayonet' ); ?></th>
				<td>
					<fieldset>
						<label for="enable_bayonet_sandbox">
						<input name="enable_bayonet_sandbox" type="checkbox" id="enable_bayonet_sandbox" value="1" <?php checked('1', get_option('bynt_is_sandbox') ); ?> />
						<?php _e( 'Enable Bayonet Sandbox Mode', 'wc-bayonet' ); ?>
						</label>
						<br />
					</fieldset>
				</td>
			</tr>
			<tr>
				<th scope="row">
					<label for="bynt_sandbox_api_key"><?php _e('Sandbox API Key', 'wc-bayonet' ) ?></label>
				</th>
				<td>
					<input name="bynt_sandbox_api_key" type="text" id="bynt_sandbox_api_key" value="<?php echo get_option('bynt_sandbox_api_key'); ?>" class="[ regular-text ]" />
				</td>
			</tr>
			<tr>
				<th scope="row"><label for="bynt_live_api_key">
					<?php _e('Live API Key', 'wc-bayonet') ?></label>
				</th>
				<td>
					<input name="bynt_live_api_key" type="text" id="bynt_live_api_key" value="<?php echo get_option('bynt_live_api_key'); ?>" class="[ regular-text ]" />
				</td>
			</tr>
			<tr>
				<th scope="row">
					<label for="bynt_sandbox_js_api_key"><?php _e('Sandbox JS Key', 'wc-bayonet' ) ?></label>
				</th>
				<td>
					<input name="bynt_sandbox_js_api_key" type="text" id="bynt_sandbox_js_api_key" value="<?php echo get_option('bynt_sandbox_js_api_key'); ?>" class="[ regular-text ]" />
				</td>
			</tr>
			<tr>
				<th scope="row"><label for="bynt_live_js_api_key">
					<?php _e('Live API JS Key', 'wc-bayonet') ?></label>
				</th>
				<td>
					<input name="bynt_live_js_api_key" type="text" id="bynt_live_js_api_key" value="<?php echo get_option('bynt_live_js_api_key'); ?>" class="[ regular-text ]" />
				</td>
			</tr>
			<tr>
				<th scope="row">
					<label for="bynt_fraud_msg"><?php _e('Fraud prevention message', 'wc-bayonet') ?></label>
				</th>
				<td>
					<textarea name="bynt_fraud_msg" rows="5" cols="10" id="bynt_fraud_msg" class="[ large-text code ]"><?php echo esc_textarea( __( get_option( 'bynt_fraud_msg' ), 'wc-bayonet' ) ); ?></textarea>
				</td>
			</tr>
		</table>
		<?php submit_button(); ?>
	</form>
	<hr>
	<?php if( $total_orders ) : ?>
		<div class="[ feedbac-historial ]">
			<h2><?php _e('Send Existing Orders Feedback', 'wc-bayonet') ?></h2>
			<p><?php _e('This action will send a CSV file to the Bayonet team to save the transaction history of your eCommerce. This is the first step of a two-step process so keep in mind that we will contact you soon.', 'wc-bayonet'); ?></p>
			<p><strong><?php echo sprintf( __('There are %d orders that have not been sent.', 'wc-bayonet'), $total_orders ) ?></strong></p>
			<button class="[ button button-primary ][ js-csv-feedback ]"><?php _e( 'Send Feedback', 'wc-bayonet' ) ?></button>
		</div>
		<hr>
		<div class="spinner"></div>
		<div class="[ js-feedback-progress ][ hidden ]">
			<p><strong><?php _e( 'Processed orders:' ); ?> <span class="[ js-processed-orders ]">0</span> / <span class="[ js-total-orders ]"><?php echo $total_orders; ?></span></strong><p>
			<p><?php _e( 'This process can take a few minutes. Please wait for the page to be refreshed.' ); ?></p>
		</div>
	<?php endif; ?>
</div>