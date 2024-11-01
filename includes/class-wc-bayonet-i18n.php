<?php

/**
 * Define the internationalization functionality.
 *
 * Loads and defines the internationalization files for this plugin
 * so that it is ready for translation.
 *
 * @since      1.0.0
 * @package    WC_Bayonet
 * @subpackage WC_Bayonet/includes
 * @author     Pequeño Cuervo <miguel@pcuervo.com>
 */
class WC_Bayonet_i18n {

	/**
	 * The ID of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $text_domain    The text domain for translations.
	 */
	private $text_domain;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @param    string    $text_domain
	 */
	public function __construct( $text_domain ) {
		$this->text_domain = $text_domain;
	}

	/**
	 * Load the plugin text domain for translation.
	 *
	 * @since    1.0.0
	 */
	public function load_plugin_textdomain() {
		load_plugin_textdomain( $this->text_domain, false, dirname( dirname( plugin_basename( __FILE__ ) ) ) . '/languages/'
		);
	}

}