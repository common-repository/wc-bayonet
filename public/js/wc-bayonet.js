/**
 * This script will be used to handle validations before processing a payment. 
 *
 * Some of the things it will handle are:
 * - Add name attribute to credit-card fields so that they 
 * can be used in Consulting API.
 * - TODO
 *
 * @version     1.0.0
 * @author      Pequeño Cuervo <miguel@pcuervo.com>
 */

$ = jQuery.noConflict();

$( document ).ready( function() {
    initBayonetFingerprinting( jsKey );
    $( document.body ).on( 'updated_checkout wc-credit-card-form-init', function() {
        addNameAttrsToCCForm();
    }).trigger( 'wc-credit-card-form-init' ); 
});

/**
 * Initialize Bayonet Fingerprinting API. 
 *
 * {string} jsKey - The JS key used (comes from WP Admin)
 */
function initBayonetFingerprinting( jsKey ){
    if( '-1' == jsKey ) return;

    _bayonet.init({
        js_key: jsKey,
        callback_function: "fingerprintingCallback"
    });
    _bayonet.track();
}

/**
 * Callback function for Fingerprinting API. 
 *
 * {object} response - The JS key used (comes from WP Admin)
 */
function fingerprintingCallback( response ) {
    console.log( "Status: " + response.status );
    saveFingerprintToken( response.bayonet_fingerprint_token );
}

/**
 * Save in session Device Fingerprint.
 */
function saveFingerprintToken( token ){
    $.post(
        ajax_url,
        {
            action: 'save_fingerprint_token',
            token: token
        },
        function( response ){
            console.log( response );
        }
    );
}
    
/**
 * Add name attribute to credit card field so that the 
 * value can be catch in the POST request. This is
 * used for the Consulting API validation.
 */
function addNameAttrsToCCForm(){
    if( $("[id*=card-number]").length ){
        $("[id*=card-number]").each( function(i, el){
            $(el).attr('name', 'ccn' + i );
        })
    }
}
