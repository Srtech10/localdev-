<?php
/*
Plugin Name: Stronghold Payment Gateway for Woocommerce
Plugin URI: https://maxenius.agency/
Description: Stronghold Payment Gateway for Woocommerce.
Version: 1.0
Author: M.Ijaz
Author URI: https://maxenius.agency/
*/


error_reporting(0);
ini_set('display_errors', 0);
// Require Gateway PHP Class
require_once( 'class.wc_gateway_sctonghold.php' );

// Add Gateway To WooCommerce
add_filter( 'woocommerce_payment_gateways', function( $methods ) {

	$methods[] = 'WC_Stronghold_Payment_Gateway'; 
	return $methods;

} );
// Bail If Accessed Directly
if( ! defined( 'ABSPATH' ) ) { exit; }

// Declare Support For HPOS
add_action( 'before_woocommerce_init', function() {
	if( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
		\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility(
			'custom_order_tables', __FILE__, true
		);
	}
} );

// Declare Support For Cart+Checkout Blocks
add_action( 'before_woocommerce_init', function() {
	if ( class_exists( '\Automattic\WooCommerce\Utilities\FeaturesUtil' ) ) {
		\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'cart_checkout_blocks', __FILE__, true );
	}
} );


// Blocks Support
add_action( 'woocommerce_blocks_loaded', function() {

	if( class_exists( 'Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType' ) ) {
		require_once( 'class-wc-stronghold-gateway-blocks-support.php' );
		add_action(
			'woocommerce_blocks_payment_method_type_registration',
			function( Automattic\WooCommerce\Blocks\Payments\PaymentMethodRegistry $payment_method_registry ) {
				$payment_method_registry->register( new WC_stronghold_Blocks );
		} );
	}

} );

add_action("wp_ajax_get_stronghold_payment_sources", "get_stronghold_payment_sources");
add_action("wp_ajax_nopriv_get_stronghold_payment_sources", "get_stronghold_payment_sources");
function get_stronghold_payment_sources($email = ''){
	
	if($email == ''){
		$email = $_POST['billing_email'];
		WC()->customer->set_billing_email(wc_clean( $email ));  
	}
	
	$objclass 			= new WC_Stronghold_Payment_Gateway();
	$transaction_reference = $objclass->stronghold_generateTransactionReference();
	$findResult 			= $objclass->stronghold_findCustomer($email);
	
	if(isset($findResult->result->items)){
		$userResponseData 	= $findResult->result->items[0];
		$customer_id 		= $userResponseData->id;
	}
	
	$button = '<input type="button" class="button" id="get_stronghold_email" value="Check Payment Sources">';
	$addbutton = '<input type="button" class="button" id="add_paymeny_saurce" value="Add Payment Sources">';
	
	if(empty($userResponseData)){
		$data = array(
			'billing_first_name' => $_POST['billing_first_name'],
			'billing_last_name' 	=> $_POST['billing_last_name'],
			'billing_email' 		=> $_POST['billing_email'],
			'billing_state' 		=> $_POST['billing_state'],
			'billing_phone' 		=> $_POST['billing_phone'],
			'billing_birthdate' 	=> $_POST['billing_birthdate'],
			'external_id' 		=> $transaction_reference,
		);
				
		$findResult = $objclass->stronghold_sendCustomer($data);
		$userResponseData = $findResult->result;
		$customer_id = $findResult->result->id;
	} 
	
	$tokenArr = $objclass->stronghold_getCustomerToken($customer_id);
	$token = $tokenArr->result->token;
	
	if(isset($findResult->error)){
		echo '<p style="color:red">';
			echo $findResult->error->message;
		echo '</p>';
		echo '<div class="stronghold_peyments_buttons">';
		echo $button;
		echo '</div>';
	}
	else
	if(empty($userResponseData)){		
		echo  '<p style="color:red">Stronghold account not registerd with given email address. Please try again with registerd email address.</p>';
		echo '<div class="stronghold_peyments_buttons">';
		echo $button;
		echo '</div>';
	}else 
	if($userResponseData->is_blocked == 1){
		echo  '<p style="color:red">Your account is blocked and transaction has been declined. Please try again.</p>';
		echo '<div class="stronghold_peyments_buttons">';
		echo $button;
		echo '</div>';
	}else
	if(empty($userResponseData->payment_sources)){
		echo '<p style="color:red">We have not found any payment source related to your account. Please add your payment source by clicking button below.</p>';
		echo '<div class="stronghold_peyments_buttons">';
		echo $addbutton ;
		echo '</div>';
	}else{
		echo '<p>Please Select Payment Source</p>';
		$session_payment_source = WC()->session->get('payment_source');
		foreach($userResponseData->payment_sources as $ps){
			if($ps->active){
				if($session_payment_source == $ps->id){
					$checkd = 'checked="checked"';
				}else{
					$checkd = '';
				}
				?>
				<div class="stronghold_payment_sources">
					<label for="<?php echo $ps->id;?>">
						<input type="radio" <?php echo $checkd;?> name="payment_source" id="<?php echo $ps->id;?>" value="<?php echo $ps->id;?>">
						<?php echo $ps->provider_name;?>
					</label>
					<input type="button" value="Remove" class="button unlink-payment-source" data-payment-id="<?php echo $ps->id;?>">
				</div>
				<?php
			}
		}
		echo '<div class="stronghold_peyments_buttons">';
		echo $addbutton ;
		echo '<input type="button" class="button" id="get_stronghold_email" value="Change Account">';
		echo '</div>';
	}
	if($token){
		$objclass->stronghold_createPaymentSource($token,$email,$objclass->publishable_key,$objclass->apitype,$objclass->intergration_id);
	}
	if(isset($_POST['billing_email'])){
		exit;
	}
}

add_action( 'woocommerce_cart_calculate_fees', 'stronghold_add_checkout_fee_for_gateway',20 );
function stronghold_add_checkout_fee_for_gateway() {
	if (is_admin() && !defined('DOING_AJAX')) {
		return;
	}
	
	$chosen_gateway = WC()->session->get( 'chosen_payment_method' );
	
	if ( $chosen_gateway == 'WC_Stronghold_Payment_Gateway' ) {
		$strongholdfee = new WC_Stronghold_Payment_Gateway();
		WC()->cart->add_fee( 'Stronghold Fee', $strongholdfee->transaction_fee );
		$tipfee = WC()->session->get( 'stronghold_tip_fee' );
		if($tipfee){
			WC()->cart->add_fee( 'Order Tip', $tipfee );
		}
	}
}
 
function stronghold_payment_method_checker(){
	if ( is_checkout() ) {
		wp_enqueue_script( 'jquery' ); ?>
		<script>
		jQuery(document).ready( function (e){
			var $ = jQuery;
			var updateTimer,dirtyInput = false,xhr;

			function update_shipping(billingstate){

				if ( xhr ) xhr.abort();
				$( '#order_methods, #order_review' ).block({ message: null, overlayCSS: { background: '#fff url() no-repeat center', backgroundSize:'16px 16px', opacity: 0.6 } });

				var data = {
					action: 'woocommerce_update_order_review',
					security: wc_checkout_params.update_order_review_nonce,
					payment_method: billingstate,
					post_data: $( 'form.checkout' ).serialize()
				};
				
				xhr = $.ajax({
					type: 'POST',
					url: '<?php echo admin_url('admin-ajax.php');?>',
					data: data,
					success: function( response ) {
						var order_output = $(response);
						$( '#order_review' ).html( response['fragments']['.woocommerce-checkout-review-order-table']+response['fragments']['.woocommerce-checkout-payment']);
						$('body').trigger('updated_checkout');
					},
					error: function(code){
						console.log('ERROR');
					}
				});
			}

			$( 'form.checkout' ).on( 'change', 'input[name^="payment_method"]', function() {
				update_shipping(jQuery(this).val());
			});
		});
		</script>	    
	<?php 
	}
}
add_action( 'wp_footer', 'stronghold_payment_method_checker', 50 );

add_action("wp_ajax_add_stronghold_payment_tip", "add_stronghold_payment_tip");
add_action("wp_ajax_nopriv_add_stronghold_payment_tip", "add_stronghold_payment_tip");
function add_stronghold_payment_tip(){
	session_start();
	$amount 			= $_POST['amount'];
	$session 		= $_POST['session'];	
	$payment_source 	= $_POST['payment_source'];
	
	if($session){
		WC()->session->set( 'stronghold_custom_tip', $session );
	}else{
		WC()->session->__unset( 'stronghold_custom_tip' );
	}
	
	WC()->session->__unset( 'stronghold_tip_fee' );
	WC()->session->set( 'stronghold_tip_fee', $amount );
	WC()->session->set( 'payment_source', $payment_source );
	exit;
}

//temp birthday fields functions
add_filter( 'woocommerce_billing_fields', 'stronghold_display_birthdate_billing_field', 20, 1 );
function stronghold_display_birthdate_billing_field($billing_fields) {
	$billing_fields['billing_birthdate'] = array(
		'type'        => 'date',
		'label'       => __('Birthdate'),
		'class'       => array('form-row-wide'),
		'priority'    => 25,
		'required'    => true,
		'clear'       => true,
	);
	return $billing_fields;
}

// Save Billing birthdate field value as user meta data
add_action( 'woocommerce_checkout_update_customer', 'stronghold_save_account_billing_birthdate_field', 10, 2 );
function stronghold_save_account_billing_birthdate_field( $customer, $data ){
	if ( isset($_POST['billing_birthdate']) && ! empty($_POST['billing_birthdate']) ) {
		$customer->update_meta_data( 'billing_birthdate', sanitize_text_field($_POST['billing_birthdate']) );
	}
}

// Admin orders Billing birthdate editable field and display
add_filter('woocommerce_admin_billing_fields', 'stronghold_admin_order_billing_birthdate_editable_field');
function stronghold_admin_order_billing_birthdate_editable_field( $fields ) {
	$fields['birthdate'] = array( 'label' => __('Birthdate', 'woocommerce') );

	return $fields;
}

// WordPress User: Add Billing birthdate editable field
add_filter('woocommerce_customer_meta_fields', 'stronghold_user_account_billing_birthdate_field');
function stronghold_user_account_billing_birthdate_field( $fields ) {
	$fields['billing']['fields']['billing_birthdate'] = array(
		'label'       => __('Birthdate', 'woocommerce'),
		'description' => __('', 'woocommerce')
	);
	return $fields;
}


add_action("wp_ajax_remove_payment_source", "remove_payment_source");
add_action("wp_ajax_nopriv_remove_payment_source", "remove_payment_source");
function remove_payment_source(){	
	$objclass 		= new WC_Stronghold_Payment_Gateway();
	$payment_source 	= $_POST['payment_source'];		
	$storedCartPaymentSource = WC()->session->get( 'payment_source' );
	
	if($storedCartPaymentSource == $payment_source){
		WC()->session->__unset( 'payment_source' );
	}
	
	$objclass->stronghold_removePaymentSource($payment_source);
	exit;	
}
?>