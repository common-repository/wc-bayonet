/**
 * This script will be used to send Feedback of existing orders to Bayonet
 * while tracking the progress in real time.
 *
 * @version     1.0.0
 * @author      Pequeño Cuervo <miguel@pcuervo.com>
 */

$ = jQuery.noConflict();
$( document ).ready( function() {
    $('.js-csv-feedback').click( function(e){
        e.preventDefault();
        //toggleButton( $(this) );
        initOrdersFeedback();
    });
});
    
/**
 * This process will generate a CSV file 
 * with information about past order
 * to be sent to the Bayonet team.
 *
 */
function initOrdersFeedback(){
    var totalOrders = getTotalOrders();
    var ordersToProcess = 30;
    $('.js-feedback-progress').show();
    processOrders( totalOrders, ordersToProcess );
}

/**
 * Return the total orders to process.
 * 
 * @param {integer} totalOrders - Total number of orders that haven't been processed/sent.
 * @param {string}  ordersToProcess - Batch of orders to process.
 */
function processOrders(  totalOrders, ordersToProcess  ){
    $.post(
        admin_ajax_url,
        {
            total_orders:       totalOrders,
            orders_to_process:  ordersToProcess,
            action:             'process_orders_with_pending_feedback'
        },
        function( response ){
            console.log( response );
            jsonResponse = $.parseJSON( response );
            increaseProcessedOrders( jsonResponse.processed_orders );
            if( '0' == jsonResponse.remaining_orders ) {
                sendCSV();
                console.log( 'finishing process...' );
                return;
            }
            var remainingOrders = totalOrders - ordersToProcess;
            processOrders( remainingOrders, ordersToProcess );
        }
    );
}

/**
 * Update the number of processed orders in UI.
 * 
 * @param {integer} ordersNum - The number of processed orders to add.
 */
function increaseProcessedOrders( ordersNum ){
    var currentlyProcessed = parseInt( $('.js-processed-orders').text() );
    $('.js-processed-orders').text( currentlyProcessed + ordersNum ) 
}

/**
 * Send the CSV file with orders information to the Bayonet team.
 */
function sendCSV(){
    $.post(
        admin_ajax_url,
        {
            action: 'send_feedback_csv'
        },
        function( response ){ 
            console.log( response );
            location.reload(); 
    }
    );
}

/**
 * Hide or show a button. 
 *
 *  @param elemnt buttonEl - The button to hide/show.
 */
function toggleButton( buttonEl ){
    if( buttonEl.is(":visible") ){
        buttonEl.hide();
        return;
    }

    buttonEl.show();
}

/**
 * Get the total number of orders that have not been
 * sent to Bayonet.
 *
 *  @return {integer} totalOrders
 */
function getTotalOrders(){
    return parseInt( $('.js-total-orders').text() );
}

