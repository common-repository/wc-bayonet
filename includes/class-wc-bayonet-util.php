<?php

/**
 * This is a utility class to help with format, validations used  
 * in Bayonet request parameters and other funky stuff.
 *
 * @since      1.0.0
 * @package    WC_Bayonet
 * @subpackage WC_Bayonet/includes
 * @author     Pequeño Cuervo <miguel@pcuervo.com>
 */
class WC_Bayonet_Util {

	/**
	 * Retrieve the total of the current cart.
	 *
	 * @since 	1.0.0
	 * @param   WC_Cart $cart - The current cart in checkout.
	 * @return 	float	$total - The total of the current cart.	
	 */
	public static function get_current_cart_total( $cart ){
		if ( ! $cart->prices_include_tax ) {
		    return round( $cart->cart_contents_total, 2 );
		} 
		return round( $cart->cart_contents_total + $cart->tax_total, 2 );
	}

	/**
	 * Get a valid Bayonet payment method based on WC payment method sent.
	 *
	 * @since 	1.0.0
	 * @todo	ADD MISSING METHODS
	 * @param   string 	$wc_payment_method - The payment method sent at checkout.
	 * @return 	string	$payment_method - A valid payment method for Bayonet.
	 */
	public static function get_payment_method( $wc_payment_method ){
		if( self::is_paypal( $wc_payment_method ) ) return 'paypal';

		if( self::is_card( $wc_payment_method ) ) return 'card';

		if( self::is_crypto_currency( $wc_payment_method ) ) return 'crypto_currency';

		if( self::is_offline( $wc_payment_method ) ) return 'offline';

		return 'wallet';
	}

	/**
	 * Check if payment method was done via PayPal.
	 *
	 * @since 	1.0.0
	 * @param   string 	$wc_payment_method - The payment method sent at checkout.
	 * @return 	boolean
	 */
	public static function is_paypal( $wc_payment_method ){
		if ( strpos( $wc_payment_method, 'paypal' ) !== false) return true;

		return false;
	}

	/**
	 * Check if payment method was done via Credit Card.
	 *
	 * @since 	1.0.0
	 * @param   string 	$wc_payment_method - The payment method sent at checkout.
	 * @return 	boolean
	 */
	public static function is_card( $wc_payment_method ){
		$card_payment_gateways = array( 'stripe', 'openpay_cards', 'conecktacard' );
		if ( in_array ( $wc_payment_method , $card_payment_gateways ) ) return true;
		if( strpos( $wc_payment_method, 'card') !== false ) return true;

		return false;
	}

	/**
	 * Check if payment method was done via a crypto currency.
	 *
	 * @since 	1.0.0
	 * @param   string 	$wc_payment_method - The payment method sent at checkout.
	 * @return 	boolean
	 */
	public static function is_crypto_currency( $wc_payment_method ){
		$crypto_currencies = array( 'bitcoin' );
		if ( in_array ( $wc_payment_method , $crypto_currencies ) ) return true;

		return false;
	}

	/**
	 * Check if payment method was done via an offline gateway.
	 *
	 * @since 	1.0.0
	 * @param   string 	$wc_payment_method - The payment method sent at checkout.
	 * @return 	boolean
	 */
	public static function is_offline( $wc_payment_method ){
		$offline_payment_gateways = array( 'cod', 'compropago', 'openpay_stores' );
		if ( in_array ( $wc_payment_method , $offline_payment_gateways ) ) return true;

		return false;
	}

	/**
	 * Retrieve the coupons used in the current cart (if any)
	 *
	 * @since 	1.0.0
	 * @param   WC_Cart $cart - The current cart in checkout.
	 * @return 	float	$coupons - Coupons used in checkout, as comma separated string.	
	 */
	public static function get_formatted_coupons( $cart ){
		$coupons = $cart->get_coupons();
		$coupons_str = '';
		if( empty( $coupons ) ) return $coupons_str;

		foreach ($coupons as $key => $value) {
			$coupons_str .= $value->get_code() . ', ';
		}
		return rtrim( $coupons_str, ', ' );
	}

	/**
	 * Get a valid Bayonet payment gateway based on WC payment gateway sent.
	 *
	 * @since 	1.0.0
	 * @todo	ADD MISSING METHODS
	 * @param   string 	$wc_payment_gateway - The payment gateway sent at checkout.
	 * @return 	string	$payment_gateway - A valid payment gateway for Bayonet.
	 */
	public static function get_payment_gateway( $wc_payment_gateway ){
		$paypal_payment_gateways = array( 'paypal', 'ppec_paypal' );
		if( in_array ( $wc_payment_gateway , $paypal_payment_gateways ) ) return 'paypal';
		if( strpos( $wc_payment_gateway, 'openpay' ) !== false ) return 'openpay';
		if( strpos( $wc_payment_gateway, 'conekta' ) !== false ) return 'conekta';
		if( strpos( $wc_payment_gateway, 'compropago' ) !== false ) return 'compropago';
		if( strpos( $wc_payment_gateway, 'authorize' ) !== false ) return 'authorizenet';
		if( strpos( $wc_payment_gateway, 'ccavenue' ) !== false ) return 'ccavenue';
		if( strpos( $wc_payment_gateway, 'nmwoo_2co' ) !== false ) return '2checkout';

		// $card_payment_gateways = array( 'stripe' );
		// if( in_array ( $payment_gateway , $card_payment_gateways ) ) return 'card';

		// $offline_payment_gateways = array( 'cod' );
		// if( in_array ( $payment_gateway , $offline_payment_gateways ) ) return 'offline';

		return $wc_payment_gateway;
	}

	/**
	 * Get additional (optional) fields but only if
	 *  properly sent in checkout.
	 *
	 * @since 	1.0.0
	 * @param   array 	$args - POST values sent in checkout
	 * @return 	array	$additional_fields - Additional fields with valid Bayonet formats
	 */
	public static function get_additional_consulting_fields( $args ){
		$additional_fields = array();
		if( ! empty( $args['billing_phone'] ) ) {
			$additional_fields['telephone'] = self::get_formatted_telephone( $args['billing_phone'] );
		}
		// Only include address if at least one address field is not empty
		if( ( isset( $args['billing_address_1'] ) || isset( $args['billing_address_1'] ) ) 
			&& isset( $args['billing_city'] ) && isset( $args['billing_state'] )
			&& isset( $args['billing_country'] ) && isset( $args['billing_postcode'] ) ) {
			$additional_fields['shipping_address'] = array(
				'address_line_1' 	=> $args['billing_address_1'],
				'address_line_2' 	=> $args['billing_address_2'],
				'city' 			 	=> $args['billing_city'],
				'state' 			=> $args['billing_state'],
				'country' 			=> self::get_iso_country_code( $args['billing_country'] ),
				'zip_code' 			=> $args['billing_postcode']
			);
		}
		return $additional_fields;
	}
	
	/**
	 * Get telephone with valid Bayonet format.
	 *
	 * @since 	1.0.0
	 * @param   string 	$phone - A telephone number without format
	 * @return 	string	$phone - A telephone with proper format
	 */
	public static function get_formatted_telephone( $phone ){
		if( empty( $phone ) ) return '';

		if( strlen( $phone ) > 10 ) {
			return mb_substr( $phone, 2, 10 );
		}

		return $phone;
	}

	/**
	 * Retrieve the product names in the current cart separted by commas.
	 *
	 * @since 	1.0.0
	 * @param   WC_Cart $cart - The current cart in checkout.
	 * @return 	float	$products - Coupons used in checkout, as comma separated string.	
	 */
	public static function get_formatted_products( $cart ){
		$items = $cart->get_cart();
		$products = '';
		foreach($items as $item => $values) { 
            $product = get_post( $values['data']->get_ID() ); 
            $products .= $product->post_title . ', ';
		}
		return rtrim( $products, ', ' );
	}

	/**
	 * Converts the WooCommerce country codes to 3-letter ISO codes
	 *
	 * @since 1.0.0
	 * @param 	string $wc_country_code WooCommerce's 2 letter country code
	 * @return 	string ISO 3-letter country code
	 */
	public static function get_iso_country_code( $wc_country_code ) {
	    $countries = array(
            'AF' => 'AFG', //Afghanistan
	       	'AX' => 'ALA', //&#197;land Islands
	       	'AL' => 'ALB', //Albania
	       	'DZ' => 'DZA', //Algeria
	       	'AS' => 'ASM', //American Samoa
	       	'AD' => 'AND', //Andorra
	       	'AO' => 'AGO', //Angola
	       	'AI' => 'AIA', //Anguilla
	       	'AQ' => 'ATA', //Antarctica
	       	'AG' => 'ATG', //Antigua and Barbuda
	       	'AR' => 'ARG', //Argentina
	       	'AM' => 'ARM', //Armenia
	       	'AW' => 'ABW', //Aruba
	       	'AU' => 'AUS', //Australia
	       	'AT' => 'AUT', //Austria
	       	'AZ' => 'AZE', //Azerbaijan
	       	'BS' => 'BHS', //Bahamas
	       	'BH' => 'BHR', //Bahrain
	       	'BD' => 'BGD', //Bangladesh
	       	'BB' => 'BRB', //Barbados
	       	'BY' => 'BLR', //Belarus
	       	'BE' => 'BEL', //Belgium
	       	'BZ' => 'BLZ', //Belize
	       	'BJ' => 'BEN', //Benin
	       	'BM' => 'BMU', //Bermuda
	       	'BT' => 'BTN', //Bhutan
	       	'BO' => 'BOL', //Bolivia
	       	'BQ' => 'BES', //Bonaire, Saint Estatius and Saba
	       	'BA' => 'BIH', //Bosnia and Herzegovina
	       	'BW' => 'BWA', //Botswana
	       	'BV' => 'BVT', //Bouvet Islands
	       	'BR' => 'BRA', //Brazil
	       	'IO' => 'IOT', //British Indian Ocean Territory
	       	'BN' => 'BRN', //Brunei
	       	'BG' => 'BGR', //Bulgaria
	       	'BF' => 'BFA', //Burkina Faso
	       	'BI' => 'BDI', //Burundi
	       	'KH' => 'KHM', //Cambodia
	       	'CM' => 'CMR', //Cameroon
	       	'CA' => 'CAN', //Canada
	       	'CV' => 'CPV', //Cape Verde
	       	'KY' => 'CYM', //Cayman Islands
	       	'CF' => 'CAF', //Central African Republic
	       	'TD' => 'TCD', //Chad
	       	'CL' => 'CHL', //Chile
	       	'CN' => 'CHN', //China
	       	'CX' => 'CXR', //Christmas Island
	       	'CC' => 'CCK', //Cocos (Keeling) Islands
	       	'CO' => 'COL', //Colombia
	       	'KM' => 'COM', //Comoros
	       	'CG' => 'COG', //Congo
	       	'CD' => 'COD', //Congo, Democratic Republic of the
	       	'CK' => 'COK', //Cook Islands
	       	'CR' => 'CRI', //Costa Rica
	       	'CI' => 'CIV', //Côte d\'Ivoire
	       	'HR' => 'HRV', //Croatia
	       	'CU' => 'CUB', //Cuba
	       	'CW' => 'CUW', //Curaçao
	       	'CY' => 'CYP', //Cyprus
	       	'CZ' => 'CZE', //Czech Republic
	       	'DK' => 'DNK', //Denmark
	       	'DJ' => 'DJI', //Djibouti
	       	'DM' => 'DMA', //Dominica
	       	'DO' => 'DOM', //Dominican Republic
	       	'EC' => 'ECU', //Ecuador
	       	'EG' => 'EGY', //Egypt
	       	'SV' => 'SLV', //El Salvador
	       	'GQ' => 'GNQ', //Equatorial Guinea
	       	'ER' => 'ERI', //Eritrea
	       	'EE' => 'EST', //Estonia
	       	'ET' => 'ETH', //Ethiopia
	       	'FK' => 'FLK', //Falkland Islands
	       	'FO' => 'FRO', //Faroe Islands
	       	'FJ' => 'FIJ', //Fiji
	       	'FI' => 'FIN', //Finland
	       	'FR' => 'FRA', //France
	       	'GF' => 'GUF', //French Guiana
	       	'PF' => 'PYF', //French Polynesia
	       	'TF' => 'ATF', //French Southern Territories
	       	'GA' => 'GAB', //Gabon
	       	'GM' => 'GMB', //Gambia
	       	'GE' => 'GEO', //Georgia
	       	'DE' => 'DEU', //Germany
	       	'GH' => 'GHA', //Ghana
	       	'GI' => 'GIB', //Gibraltar
	       	'GR' => 'GRC', //Greece
	       	'GL' => 'GRL', //Greenland
	       	'GD' => 'GRD', //Grenada
	       	'GP' => 'GLP', //Guadeloupe
	       	'GU' => 'GUM', //Guam
	       	'GT' => 'GTM', //Guatemala
	       	'GG' => 'GGY', //Guernsey
	       	'GN' => 'GIN', //Guinea
	       	'GW' => 'GNB', //Guinea-Bissau
	       	'GY' => 'GUY', //Guyana
	       	'HT' => 'HTI', //Haiti
	       	'HM' => 'HMD', //Heard Island and McDonald Islands
	       	'VA' => 'VAT', //Holy See (Vatican City State)
	       	'HN' => 'HND', //Honduras
	       	'HK' => 'HKG', //Hong Kong
	       	'HU' => 'HUN', //Hungary
	       	'IS' => 'ISL', //Iceland
	       	'IN' => 'IND', //India
	       	'ID' => 'IDN', //Indonesia
	       	'IR' => 'IRN', //Iran
	       	'IQ' => 'IRQ', //Iraq
	       	'IE' => 'IRL', //Republic of Ireland
	       	'IM' => 'IMN', //Isle of Man
	       	'IL' => 'ISR', //Israel
	       	'IT' => 'ITA', //Italy
	       	'JM' => 'JAM', //Jamaica
	       	'JP' => 'JPN', //Japan
	       	'JE' => 'JEY', //Jersey
	       	'JO' => 'JOR', //Jordan
	       	'KZ' => 'KAZ', //Kazakhstan
	       	'KE' => 'KEN', //Kenya
	       	'KI' => 'KIR', //Kiribati
	       	'KP' => 'PRK', //Korea, Democratic People\'s Republic of
	       	'KR' => 'KOR', //Korea, Republic of (South)
	       	'KW' => 'KWT', //Kuwait
	       	'KG' => 'KGZ', //Kyrgyzstan
	       	'LA' => 'LAO', //Laos
	       	'LV' => 'LVA', //Latvia
	       	'LB' => 'LBN', //Lebanon
	       	'LS' => 'LSO', //Lesotho
	       	'LR' => 'LBR', //Liberia
	       	'LY' => 'LBY', //Libya
	       	'LI' => 'LIE', //Liechtenstein
	       	'LT' => 'LTU', //Lithuania
	       	'LU' => 'LUX', //Luxembourg
	       	'MO' => 'MAC', //Macao S.A.R., China
	       	'MK' => 'MKD', //Macedonia
	       	'MG' => 'MDG', //Madagascar
	       	'MW' => 'MWI', //Malawi
	       	'MY' => 'MYS', //Malaysia
	       	'MV' => 'MDV', //Maldives
	       	'ML' => 'MLI', //Mali
	       	'MT' => 'MLT', //Malta
	       	'MH' => 'MHL', //Marshall Islands
	       	'MQ' => 'MTQ', //Martinique
	       	'MR' => 'MRT', //Mauritania
	       	'MU' => 'MUS', //Mauritius
	       	'YT' => 'MYT', //Mayotte
	       	'MX' => 'MEX', //Mexico
	       	'FM' => 'FSM', //Micronesia
	       	'MD' => 'MDA', //Moldova
	       	'MC' => 'MCO', //Monaco
	       	'MN' => 'MNG', //Mongolia
	       	'ME' => 'MNE', //Montenegro
	       	'MS' => 'MSR', //Montserrat
	       	'MA' => 'MAR', //Morocco
	       	'MZ' => 'MOZ', //Mozambique
	       	'MM' => 'MMR', //Myanmar
	       	'NA' => 'NAM', //Namibia
	       	'NR' => 'NRU', //Nauru
	       	'NP' => 'NPL', //Nepal
	       	'NL' => 'NLD', //Netherlands
	       	'AN' => 'ANT', //Netherlands Antilles
	       	'NC' => 'NCL', //New Caledonia
	       	'NZ' => 'NZL', //New Zealand
	       	'NI' => 'NIC', //Nicaragua
	       	'NE' => 'NER', //Niger
	       	'NG' => 'NGA', //Nigeria
	       	'NU' => 'NIU', //Niue
	       	'NF' => 'NFK', //Norfolk Island
	       	'MP' => 'MNP', //Northern Mariana Islands
	       	'NO' => 'NOR', //Norway
	       	'OM' => 'OMN', //Oman
	       	'PK' => 'PAK', //Pakistan
	       	'PW' => 'PLW', //Palau
	       	'PS' => 'PSE', //Palestinian Territory
	       	'PA' => 'PAN', //Panama
	       	'PG' => 'PNG', //Papua New Guinea
	       	'PY' => 'PRY', //Paraguay
	       	'PE' => 'PER', //Peru
	       	'PH' => 'PHL', //Philippines
	       	'PN' => 'PCN', //Pitcairn
	       	'PL' => 'POL', //Poland
	       	'PT' => 'PRT', //Portugal
	       	'PR' => 'PRI', //Puerto Rico
	       	'QA' => 'QAT', //Qatar
	       	'RE' => 'REU', //Reunion
	       	'RO' => 'ROU', //Romania
	       	'RU' => 'RUS', //Russia
	       	'RW' => 'RWA', //Rwanda
	       	'BL' => 'BLM', //Saint Barth&eacute;lemy
	       	'SH' => 'SHN', //Saint Helena
	       	'KN' => 'KNA', //Saint Kitts and Nevis
	       	'LC' => 'LCA', //Saint Lucia
	       	'MF' => 'MAF', //Saint Martin (French part)
	       	'SX' => 'SXM', //Sint Maarten / Saint Matin (Dutch part)
	       	'PM' => 'SPM', //Saint Pierre and Miquelon
	       	'VC' => 'VCT', //Saint Vincent and the Grenadines
	       	'WS' => 'WSM', //Samoa
	       	'SM' => 'SMR', //San Marino
	       	'ST' => 'STP', //S&atilde;o Tom&eacute; and Pr&iacute;ncipe
	       	'SA' => 'SAU', //Saudi Arabia
	       	'SN' => 'SEN', //Senegal
	       	'RS' => 'SRB', //Serbia
	       	'SC' => 'SYC', //Seychelles
	       	'SL' => 'SLE', //Sierra Leone
	       	'SG' => 'SGP', //Singapore
	       	'SK' => 'SVK', //Slovakia
	       	'SI' => 'SVN', //Slovenia
	       	'SB' => 'SLB', //Solomon Islands
	       	'SO' => 'SOM', //Somalia
	       	'ZA' => 'ZAF', //South Africa
	       	'GS' => 'SGS', //South Georgia/Sandwich Islands
	       	'SS' => 'SSD', //South Sudan
	       	'ES' => 'ESP', //Spain
	       	'LK' => 'LKA', //Sri Lanka
	       	'SD' => 'SDN', //Sudan
	       	'SR' => 'SUR', //Suriname
	       	'SJ' => 'SJM', //Svalbard and Jan Mayen
	       	'SZ' => 'SWZ', //Swaziland
	       	'SE' => 'SWE', //Sweden
	       	'CH' => 'CHE', //Switzerland
	       	'SY' => 'SYR', //Syria
	       	'TW' => 'TWN', //Taiwan
	       	'TJ' => 'TJK', //Tajikistan
	       	'TZ' => 'TZA', //Tanzania
	       	'TH' => 'THA', //Thailand    
	       	'TL' => 'TLS', //Timor-Leste
	       	'TG' => 'TGO', //Togo
	       	'TK' => 'TKL', //Tokelau
	       	'TO' => 'TON', //Tonga
	       	'TT' => 'TTO', //Trinidad and Tobago
	       	'TN' => 'TUN', //Tunisia
	       	'TR' => 'TUR', //Turkey
	       	'TM' => 'TKM', //Turkmenistan
	       	'TC' => 'TCA', //Turks and Caicos Islands
	       	'TV' => 'TUV', //Tuvalu     
	       	'UG' => 'UGA', //Uganda
	       	'UA' => 'UKR', //Ukraine
	       	'AE' => 'ARE', //United Arab Emirates
	       	'GB' => 'GBR', //United Kingdom
	       	'US' => 'USA', //United States
	       	'UM' => 'UMI', //United States Minor Outlying Islands
	       	'UY' => 'URY', //Uruguay
	       	'UZ' => 'UZB', //Uzbekistan
	       	'VU' => 'VUT', //Vanuatu
	       	'VE' => 'VEN', //Venezuela
	       	'VN' => 'VNM', //Vietnam
	       	'VG' => 'VGB', //Virgin Islands, British
	       	'VI' => 'VIR', //Virgin Island, U.S.
	       	'WF' => 'WLF', //Wallis and Futuna
	       	'EH' => 'ESH', //Western Sahara
	       	'YE' => 'YEM', //Yemen
	       	'ZM' => 'ZMB', //Zambia
	       	'ZW' => 'ZWE', //Zimbabwe
	   	);
	   	$iso_code = isset( $countries[$wc_country_code] ) ? $countries[$wc_country_code] : $wc_country_code;
	    return $iso_code;
	}

	/**
	 * Retrieve the product names in the current cart separted by commas.
	 *
	 * @since 	1.0.0
	 * @param   WC_Order 	$cart - The order from which to extract products.
	 * @return 	float		$products - Products in orders, as comma separated string.	
	 */
	public static function get_formatted_order_products( $order ){
		$items = $order->get_items();;
		$products = '';
		foreach($items as $item) { 
            $products .= $item['name'] . ', ';
		}
		return rtrim( $products, ', ' );
	}

	/**
	 * Retrieve the coupons used in the order (if any)
	 *
	 * @since 	1.0.0
	 * @param   WC_Order 	$order 	- The current order.
	 * @return 	float		$coupons - Coupons used in checkout, as comma separated string.	
	 */
	public static function get_formatted_order_coupons( $order ){
		$coupons = $order->get_used_coupons();
		$coupons_str = '';
		if( empty( $coupons ) ) return $coupons_str;

		foreach ( $coupons as $coupon ) {
			$coupons_str .= $coupon . ', ';
		}
		return rtrim( $coupons_str, ', ' );
	}

	/**
	 * Get the credit card number used in checkout. 
	 *
	 * @since 	1.0.0
	 * @param   array 	$post - POST variables.
	 * @return 	string	$credit_card_number	
	 */
	public static function get_ccn( $post ){
		$post_vars = array_keys( $post );
		foreach( $post_vars as $key ){
			if( strpos( $key, 'ccn' ) === false ) continue;

			if( empty( $post[$key] ) ) continue;
			return preg_replace( '/\s+/', '', ( $post[$key]  ) );
		}
		return '-1';
	}

	/**
	 * Check if payment was done with a saved card.  
	 *
	 * @since 	1.0.0
	 * @param   array 	$post - POST variables.
	 * @return 	boolean
	 */
	public static function is_saved_card( $post ){
		return isset( $post['wc-stripe-payment-token'] );
	}

}
