<?php

/**
 * WC Bayonet
 *
 * Here we handle the connection with Bayonet's API, create admin page for
 * settings and register all hooks required to send order transactions
 * to Bayonet.
 *
 * @since      1.0.0
 * @package    WC_Bayonet
 * @subpackage WC_Bayonet/includes
 * @author     Pequeño Cuervo <miguel@pcuervo.com>
 */

// Load Bayonet PHP Client
require plugin_dir_path( __FILE__ ) . 'src/autoload.php';
use Bayonet\BayonetClient;

class WC_Bayonet {

	const PLUGIN_VERSION = '1.0.0';
	const API_VERSION = '1';

	/**
	 * The loader that's responsible for maintaining and registering all hooks that power
	 * the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      WC_Bayonet_Loader    $loader    Maintains and registers all hooks for the plugin.
	 */
	protected $loader;

	/**
	 * The unique identifier of this plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string    $plugin_name 	The string used to uniquely identify this plugin.
	 */
	protected $plugin_name;

	/**
	 * The current version of the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string    $version    The current version of the plugin.
	 */
	protected $version;

	/**
	 * The Bayonet client
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      BayonetClient    $bayonet_client  Bayonet API client
	 */
	protected  $bayonet_client;

	/**
	 * Set the plugin name and the plugin version that can be used throughout the plugin.
	 * Load the dependencies, define the locale, and set the hooks for the admin area and
	 * the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function __construct() {
		$api_key = '1' == get_option( 'bynt_is_sandbox' ) ? get_option( 'bynt_sandbox_api_key' ) : get_option( 'bynt_live_api_key' );
		$this->plugin_name = 'wc-bayonet';
		$this->version = self::PLUGIN_VERSION;
		$this->bayonet_client = new BayonetClient([
		    'api_key' => $api_key,
		    'version' => self::API_VERSION
		]);
		$this->load_dependencies();
		$this->set_locale();
		if( is_admin() ){
			$this->define_admin_hooks();
		}
		$this->define_public_hooks();
	}

	/**
	 * Load the required dependencies for this plugin.
	 *
	 * - WC_Bayonet_Loader. Orchestrates the hooks of the plugin.
	 * - WC_Bayonet_i18n. Defines internationalization functionality.
	 * - WC_Bayonet_Admin. Defines all hooks for the admin area.
	 * - WC_Bayonet_Public. Defines all hooks for the public side of the site.
	 *
	 * Create an instance of the loader which will be used to register the hooks
	 * with WordPress.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function load_dependencies() {
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-wc-bayonet-loader.php';
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-wc-bayonet-i18n.php';
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-wc-bayonet-util.php';
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-wc-bayonet-csv.php';
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/class-wc-bayonet-admin.php';
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'public/class-wc-bayonet-public.php';

		$this->loader = new WC_Bayonet_Loader();
	}

	/**
	 * Define the locale for this plugin for internationalization.
	 *
	 * Uses the WC_Bayonet_i18n class in order to set the domain and to register the hook
	 * with WordPress.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function set_locale() {
		$plugin_i18n = new WC_Bayonet_i18n( $this->plugin_name );
		$this->loader->add_action( 'plugins_loaded', $plugin_i18n, 'load_plugin_textdomain' );
	}

	/**
	 * Register all of the hooks related to the admin area functionality
	 * of the plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function define_admin_hooks() {
		$plugin_admin = new WC_Bayonet_Admin( $this->get_plugin_name(), $this->get_version() );
		// actions
		$this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_styles' );
		$this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_csv_script' );
		$this->loader->add_action( 'admin_notices', $plugin_admin, 'show_missing_api_key_notice' );
		$this->loader->add_action( 'admin_notices', $plugin_admin, 'show_pending_historial_transactions_notice' );
		$this->loader->add_action( 'admin_menu', $plugin_admin, 'add_options_page' );
		$this->loader->add_action( 'admin_init', $plugin_admin, 'save_settings' );
		$basename = 'wc-bayonet/wc-bayonet.php';
		$this->loader->add_action( 'manage_shop_order_posts_custom_column', $plugin_admin, 'display_risk_level_admin_orders', 10, 2 );
		$this->loader->add_action( 'wp_ajax_process_orders_with_pending_feedback', $plugin_admin, 'process_pending_feedback_ajax' );
		$this->loader->add_action( 'wp_ajax_send_feedback_csv', $plugin_admin, 'send_feedback_csv_ajax' );
		// filters
		$this->loader->add_filter( 'plugin_action_links_' . $basename, $plugin_admin, 'add_plugin_actions_links', 10, 5 );
		$this->loader->add_filter( 'manage_edit-shop_order_columns', $plugin_admin, 'risk_level_column_header' );
	}

	/**
	 * Register all of the hooks related to the public-facing functionality
	 * of the plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function define_public_hooks() {
		$plugin_public = new WC_Bayonet_Public( $this->get_plugin_name(), $this->get_version(), $this->get_bayonet_client() );
		$this->loader->add_action( 'wp_enqueue_scripts', $plugin_public, 'enqueue_checkout_scripts' );
		$this->loader->add_action( 'woocommerce_checkout_process', $plugin_public, 'consulting_api_validation' );
		$this->loader->add_action( 'woocommerce_thankyou', $plugin_public, 'send_feedback_thankyou_page' );
		$this->loader->add_action( 'wp_ajax_save_fingerprint_token', $plugin_public, 'save_fingerprint_token_ajax' );
		$this->loader->add_action( 'wp_ajax_save_fingerprint_token_no_priv', $plugin_public, 'save_fingerprint_token_ajax' );
		// $this->loader->add_action( 'wp_ajax_get_post_title_ajax', $plugin_public, 'is_order_high_risk_ajax' );
		// $this->loader->add_action( 'wp_ajax_get_post_title_ajax_no_priv', $plugin_public, 'is_order_high_risk_ajax' );
	}

	/**
	 * Run the loader to execute all of the hooks with WordPress.
	 *
	 * @since    1.0.0
	 */
	public function run() {
		$this->loader->run();
	}

	/**
	 * The name of the plugin used to uniquely identify it within the context of
	 * WordPress and to define internationalization functionality.
	 *
	 * @since     1.0.0
	 * @return    string    The name of the plugin.
	 */
	public function get_plugin_name() {
		return $this->plugin_name;
	}

	/**
	 * The reference to the class that orchestrates the hooks with the plugin.
	 *
	 * @since     1.0.0
	 * @return    WC_Bayonet_Loader    Orchestrates the hooks of the plugin.
	 */
	public function get_loader() {
		return $this->loader;
	}

	/**
	 * Retrieve the version number of the plugin.
	 *
	 * @since     1.0.0
	 * @return    string    The version number of the plugin.
	 */
	public function get_version() {
		return $this->version;
	}

	/**
	 * Retrieve the Bayonet Client instance.
	 *
	 * @since     1.0.0
	 * @return    BayonetClient  
	 */
	public function get_bayonet_client() {
		return $this->bayonet_client;
	}

}
