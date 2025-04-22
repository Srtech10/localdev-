<?php
// Bail If Accessed Directly
if( ! defined( 'ABSPATH' ) ) { exit; }

add_action( 'woocommerce_init', function() {
	// Bail If Preexists
	if( class_exists( 'WC_Stronghold_Payment_Gateway' ) ) {
		return;
	}

	class WC_Stronghold_Payment_Gateway extends WC_Payment_Gateway {
		//Server response code constants
		const SERVER_ERROR 			= 500;
		const SERVER_RESPONSE_OK 		= 200;
		const SERVER_UNAUTHORIZED 	= 401;
		const SERVER_PAYMENT_REQUIRED 	= 402;
		
		//Response status code constants
		const PAYMENT_ISBLOCKED 	= 1;
		const PAYMENT_SUCCESS 	= 0;
		const PAYMENT_DISHONOUR 	= 5;
		const PAYMENT_ERROR 		= 6;
		const LIVE_URL 			= 'https://api.strongholdpay.com/';
		
		public function __construct(){
			
			$this->id = 'WC_Stronghold_Payment_Gateway';
			$this->icon = ''; 
			$this->has_fields = true; 
			$this->method_title = 'Stronghold Payment';
			$this->method_description = 'Payment via Stronghold.';
			$this->init_form_fields();
			$this->init_settings();
			$this->title = $this->get_option('title');
			$this->description = $this->get_option('description');
			$this->transaction_fee = $this->get_option('transaction_fee');
			$this->intergration_id = $this->get_option('intergration_id');
			$this->apitype = $this->settings['mode'];
			$this->create_paylink = $this->get_option('create_paylink');
			$this->create_tip = $this->get_option('create_tip');
			$this->tip_1 = $this->get_option('tip_1');
			$this->tip_2 = $this->get_option('tip_2');
			$this->tip_3 = $this->get_option('tip_3');
			$this->publishable_key = $this->get_option('publishable_key');
			$this->apikey = ($this->settings['mode']=='sandbox'? $this->settings['sandboxapi'] : $this->settings['liveapi'] );
			$this->apiurl = ($this->settings['mode']=='sandbox'?  self::LIVE_URL: self::LIVE_URL );
			$this->ajaxscript =admin_url('admin-ajax.php');
			
			$this->supports = array(
				'products',
				'refunds'
			);
			
			if (version_compare(WOOCOMMERCE_VERSION, '2.0.0', '>=')){
				add_action('woocommerce_update_options_payment_gateways_' . $this -> id, array(&$this,'process_admin_options'));
			} else{
				add_action('woocommerce_update_options_payment_gateways', array(&$this,'process_admin_options'));
			}
			add_action('admin_head', array($this,'remove_manual_refunds'));
			
			add_action( 'wp_enqueue_scripts', array( $this, 'payment_scripts' ) );
			
			if(isset($_GET['pay_link_callback']) && $_GET['pay_link_callback'] == 'success'){
				$paylinkId 	= $_GET['pay_link_id'];
				$order_id 	= $_GET['custom_orderId'];
				$this->checkPaylink($paylinkId,$order_id);
			}
		}				
		
		// register stronghold payment scripts/style
		public function payment_scripts(){
			
			if( !is_checkout() ) {
				return;
			}
			if( empty( $this->apikey ) || empty( $this->publishable_key ) ) {
				return;
			} 
			
			wp_enqueue_script( 'stronghold_js', plugins_url( '/js/stronghold.js', __FILE__ ), array( 'jquery' ) );
			wp_enqueue_script( 'stronghold', 'https://api.strongholdpay.com/v2/js', array( 'jquery' ) );
			wp_enqueue_style( 'stronghold_css', plugins_url( '/css/stronghold.css', __FILE__ ) );

			// in most payment processors you have to use PUBLIC KEY to obtain a token
			wp_localize_script( 'stronghold_js', 'stronghold_params', array(
				'ajaxurl' => $this->ajaxscript,
				'publishable_key' => $this->publishable_key,
				'intergration_id' => $this->intergration_id,
				'apitype' => $this->apitype,
				'nonce' 	=> wp_create_nonce( "process_reservation_nonce" )
			) );

		}
		
		// Initialize form fields
		public function init_form_fields(){
			$this->form_fields = array(
				'enabled' => array(
					 'title' => 'Enable/Disable',
					 'type' => 'checkbox',
					 'label' => 'Enable',
					 'default' => 'yes'
				 ),
				 'mode' => array(
					 'title' => 'Api Mode',
					 'type' => 'select',
					 'label' => 'Select Api mode',
					 'options' => array(
						'live' => 'Live',
						'sandbox' => 'Sandbox'
					 )
				 ),
				 'liveapi' => array(
					 'title' => 'Live Api Key',
					 'type' => 'text',
					 'description' => 'Live api key for stronghold payment gateway.',
					 'default' => '',
					 'desc_tip' => true,
				 ),			 
				 'sandboxapi' => array(
					 'title' => 'Sandbox Api Key',
					 'type' => 'text',
					 'description' => 'Sandbox api key for stronghold payment gateway.',
					 'default' => '',
					 'desc_tip' => true,
				 ),
				 'publishable_key' => array(
					 'title' => 'Publishable API key',
					 'type' => 'text',
					 'description' => 'Publishable API key for stronghold payment gateway.',
					 'default' => '',
					 'desc_tip' => true,
				 ),		 
				 'intergration_id' => array(
					 'title' => 'PaymentSource intergration id',
					 'type' => 'text',
					 'description' => 'PaymentSource intergration id to add new payment source for customers.',
					 'default' => '',
					 'desc_tip' => true,
				 ),
				 'transaction_fee' => array(
					 'title' => 'Transaction Fee',
					 'type' => 'number',
					 'description' => 'stronghold payment gateway transaction fee 2.25.',
					 'default' => '2.25',
					 'desc_tip' => true,
				 ),
				 'create_paylink' => array(
					 'title' => 'Enable/Disable',
					 'type' => 'checkbox',
					 'label' => 'Create Paylink',
					 'default' => 'yes'
				 ),
				 'create_tip' => array(
					 'title' => 'Enable/Disable',
					 'type' => 'checkbox',
					 'label' => 'Create Tip',
					 'default' => 'yes'
				 ),
				 'tip_1' => array(
					'title' => 'Tip 1',
					'type' => 'number',
					'description' => '',
					'default' => '2.00',
				),
				'tip_2' => array(
					'title' => 'Tip 2',
					'type' => 'number',
					'description' => '',
					'default' => '3.00',
				),
				'tip_3' => array(
					'title' => 'Tip 3',
					'type' => 'number',
					'description' => '',
					'default' => '5.00',
				),
				'title' => array(
					'title' => 'Title',
					'type' => 'text',
					'description' => 'Stronghold payment gateway.',
					'default' => 'Stronghold payment gateway',
				),
				 'description' => array(
					 'title' => 'Description',
					 'type' => 'textarea',
					 'description' => 'This controls the description which the user sees during checkout.',
					 'default' => 'Pay using our Stronghold payment gateway.',
				 ),
			 );
		}
		
		// Process the payment
		public function process_payment($order_id) {
			global $woocommerce;
			$order = wc_get_order($order_id);
			$amount = $order->get_total();
		
			// Get email safely
			$email = isset($_POST['billing_email']) ? sanitize_email($_POST['billing_email']) : 
					 (isset($_POST['email']) ? sanitize_email($_POST['email']) : '');
		
			if (empty($email)) {
				$message = 'Billing email is required for payment processing.';
				$order->update_status('wc-failed', $message);
				wc_add_notice($message, 'error');
				return array('result' => 'failure');
			}
		
			$this->transaction_reference = $this->stronghold_generateTransactionReference();
		
			// Adjust amount for tip if applicable
			if ($this->create_paylink == 'no' && $this->create_tip == 'yes') {
				$stronghold_tip = WC()->session->get('stronghold_tip_fee');
				$amount = $amount - ($stronghold_tip ?: 0);
			}
		
			if (!isset($_POST['payment_source']) || empty($_POST['payment_source'])) {
				$message = 'Payment source not selected or missing. Please try again.';
				$order->update_status('wc-failed', $message);
				wc_add_notice($message, 'error');
				return array('result' => 'failure');
			}
		
			$payment_source = sanitize_text_field($_POST['payment_source']);
			$findResult = $this->stronghold_findCustomer($email);
			$userResponseData = $findResult->result->items[0] ?? null;
		
			if (!$userResponseData) {
				$message = 'Customer not found in Stronghold. Please try again.';
				$order->update_status('wc-failed', $message);
				wc_add_notice($message, 'error');
				return array('result' => 'failure');
			}
		
			if ($this->create_paylink == 'yes') {
				$createPaylinkResponse = $this->stronghold_CreatePaylink($order_id, $userResponseData, $payment_source);
		
				if ($createPaylinkResponse->status_code == 201) {
					$paylinkID = $createPaylinkResponse->result->id;
					$this->stronghold_SendPaylinkSMS($paylinkID);
					$order->update_status('wc-pending', 'Awaiting payment via Stronghold Paylink.');
					return array(
						'result' => 'success',
						'redirect' => $createPaylinkResponse->result->url
					);
				} else {
					$message = 'Failed to create Stronghold paylink. Please try again.';
					$order->update_status('wc-failed', $message);
					wc_add_notice($message, 'error');
					return array('result' => 'failure');
				}
			}
		
			// Direct charge flow (no paylink)
			$createCharge = $this->stronghold_createCharge($userResponseData, $payment_source, $amount);
		
			if ($createCharge->status_code == 201) {
				$charge_id = $createCharge->result->id;
				$this->stronghold_authorizeCharge($charge_id);
				$this->stronghold_CaptureCharge($charge_id, $amount);
		
				// Handle tip if enabled
				if ($this->create_tip == 'yes' && (isset($_POST['stronghold_tip']) || isset($_POST['stronghold_custom_tip']))) {
					$tip_price = !empty($_POST['stronghold_custom_tip']) ? floatval($_POST['stronghold_custom_tip']) : floatval($_POST['stronghold_tip']);
					if ($tip_price > 0) {
						$this->strongholdTip($userResponseData, $payment_source, $tip_price, $charge_id);
					}
				}
		
				$order->add_order_note(__('Payment completed', 'stronghold') . ' (Transaction reference: ' . $this->transaction_reference . ')');
				$woocommerce->cart->empty_cart();
				$order->set_status('wc-processing'); // Explicitly set status before payment_complete
				$order->payment_complete();
				$order->save();
		
				update_post_meta($order->get_id(), '_stronghold_payment_data', $createCharge);
				update_post_meta($order->get_id(), '_stronghold_payment_type', 'checkout');
				WC()->session->__unset('stronghold_tip_fee');
				WC()->session->__unset('stronghold_custom_tip');
		
				return array(
					'result' => 'success',
					'redirect' => $this->get_return_url($order)
				);
			} else {
				$message = $createCharge->error->message ?? 'Payment processing failed. Please try again.';
				$order->update_status('wc-failed', $message);
				wc_add_notice($message, 'error');
				return array('result' => 'failure');
			}
		}
		
		// Register the payment fields
		public function payment_fields(){
			
			global $woocommerce;
			$email 			= '';			
			$custom_tip 		= '';			
			$session_customer = WC()->session->get('customer'); 
			
			if(isset($session_customer['email']) ){
				$email = $session_customer['email']; 
			}else if(isset($_SESSION['user_billing_email'])){
				$email = $_SESSION['user_billing_email']; 
			}
			
			$button = '<input type="button" class="button" id="get_stronghold_email" value="Check Payment Sources">';
			echo '<div id="payment_sources_list">';
			
			if($email){
				get_stronghold_payment_sources($email);
			}else{
				echo '<p>Please enter your billing email address before clicking the button</p>';
				echo $button;
			}
			
			echo '</div>';
			
			$currencyUSD = get_woocommerce_currency_symbol("USD");			
			$hide = '';
			if($this->create_paylink == 'no' && $this->create_tip == 'yes'){
				
				$selected 	= WC()->session->get( 'stronghold_tip_fee' );
				$custom_tip 	= WC()->session->get( 'stronghold_custom_tip' );
				
				$customTipSession = 0;
				
				if($custom_tip){
					$customTipSession = 1;
				}
				if( ( $selected == 0 || $selected == '' ) && ( $custom_tip == 0 || $custom_tip == '' ) ){
					$hide = 'style="display:none"';
				}
				?>
				<div class="stronghold-tips" <?php echo $hide;?>>
					<h3>Add a tip</h3>
					<div class="stronghold-tip-options" <?php echo ($customTipSession)? 'style="display:none"':'';?>>
						<?php
						$tips  = array($this->tip_1,$this->tip_2,$this->tip_3);
						$t = 1;
						
						foreach($tips as $tip){
							$checked = ($selected == $tip)? "checked": "";
							$previous = ($selected == $tip)? "checked": "false";
							?>
							<label for="tip_<?php echo $t;?>">
								<input <?php echo $checked;?> id="tip_<?php echo $t;?>" class="stronghold_tip" type="radio" name="stronghold_tip" value="<?php echo $tip;?>" previousValue="<?php echo $previous;?>">
								<span class="checkmark"><?php echo $currencyUSD.$tip;?></span>
							</label>
							<?php
							$t++;
						}
					?>
					</div>
					<button type="button" class="button" id="stronghold_custom_tip"><?php echo ($customTipSession)? 'Return to Tips List': 'Enter Custom Amount';?></button>
					<div class="stronghold_custom_tip" <?php echo ($customTipSession)? '': 'style="display:none"';?>>
						<input class="input-text " type="number" id="stronghold_custom_tip_price" name="stronghold_custom_tip" value="<?php echo ($selected != 0) ? $selected :'';?>">
						<button type="button" class="button" id="add_stronghold_custom_tip"><?php echo ($selected != 0) ? 'Remove':'Add';?></button>
					</div>
				</div>
			<?php
			}
			?>		
			<script>
				jQuery(document).ready(function($){
					var ajaxscript ='<?php echo $this->ajaxscript;?>';
					<?php if($custom_tip && $selected){?>
						$('#stronghold_custom_tip_price').on('keypress ',function(){
							$('#add_stronghold_custom_tip').text('Update');
						});
					<?php }?>					
				});
			</script>
			<?php 
		}
		
		// Process the refund
		public function process_refund( $order_id, $amount = null, $reason = ''  ) {
			$order = wc_get_order( $order_id );
			$total = $order->get_total();
						
			if ( $total !==  $amount) {
				return new WP_Error( 'error', __( 'Please enter total amount for payment refund process using Stronghold.'.$total, 'woocommerce' ) );
			}
			
			$refunded_line_items = array();
			
			foreach ( $order->get_items() as $item_id => $item ) {
				$refunded_line_items[ $item_id ]['qty'] = $item->get_quantity();
			}
			
			wc_restock_refunded_items($order,$refunded_line_items);
			
			// Do your refund here. Refund $amount for the order with ID $order_id
			$getchargeData = get_post_meta($order_id,'_stronghold_payment_data');	
			$_stronghold_payment_type = get_post_meta($order_id,'_stronghold_payment_type',true);	
			
			if(!empty($getchargeData)){
				
				if($_stronghold_payment_type == 'paylink'){
					$charge_id = $getchargeData[0]->result->charge->id;
				}else{
					$charge_id  = $getchargeData[0]->result->id;
				}
				
				$data = $this->stronghold_refundCharge($charge_id);
				update_post_meta($order_id,'_stronghold_payment_data','');
			}
			return true;
		}
		
		//Create Paylink
		private function stronghold_CreatePaylink($order_id,$userData,$paymentSource){
			
			$order 			= wc_get_order($order_id);
			$customerName 	= $userData->individual->first_name.' '.$userData->individual->last_name;
			$successUrl 		= site_url().'/checkout/?order=success&orderId='.$order_id;
			$exitUrl 		= site_url().'/checkout/?order=failed&orderId='.$order_id;
			$orderItems 		= array();
			$amount 			= $order->get_total()*100;
			$totalAmount 	= $amount - 225;
			$tax = 0;
			
			$ItemsTotal = 0;
			foreach ( $order->get_items() as $item_id => $item ) {
				
				$product_id 		= $item['product_id'];
				$productTotal 	= $item->get_total() * 100;
				$product 		= $item->get_product();
				$product_detail	= $product->get_data();
				$imageUrl 		= '';
				$image 			= wp_get_attachment_image_src( get_post_thumbnail_id( $product_id ), 'single-post-thumbnail' );
				$ItemsTotal 		= $ItemsTotal + $productTotal;
				
				if(!empty($image)){
					$imageUrl =  $image[0];
				}
				
				$orderItems[] = array(
					'name' 			=> $item->get_name(),
					'quantity' 		=> $item->get_quantity(),
					'total_amount' 	=> $productTotal,
					'image_url' 		=> $imageUrl,
				);				
				
			}
			if($ItemsTotal != $totalAmount){
				$tax = $totalAmount - $ItemsTotal;
			}
			$customer_id = $userData->id;
			
			$dataArr = array(
				"type" 			=> "checkout",
				"customer_id" 	=> $customer_id,
				"charge" 		=> array(
					"type" 				=> "bank_debit",
					"amount" 			=> $amount,
					"currency" 			=> "usd",
					"customer_id" 		=> $customer_id,
					"payment_source_id" 	=> $paymentSource,
					"source_id" 			=> $paymentSource,
					"external_id" 		=> "$order_id",
					'convenience_fee' 	=> 225
				),
				'tip' 			=>  array(
					'beneficiary_name' 	=> $customerName,
					'details' 			=>  array(
						'display_message' => 'Order made by '.$customerName
					)
				),
				'order' 			=>  array(
					'total_amount' 		=> $totalAmount,
					'tax_amount' 		=> $tax,
					'convenience_fee' 	=> 225,
					'items' 				=>  $orderItems
				),
				'callbacks' 		=>  array(
					'success_url' 		=> $successUrl,
					'exit_url' 			=> $exitUrl
				),
				'stand_alone' 	=> true,
				'authorize_only' => false
			);
			
			$responces = $this->initCurl($dataArr,"v2/links","POST");
			return $responces;
		}
		
		private function stronghold_SendPaylinkSMS($paylinkId){
			$responces = $this->initCurl(array(),"v2/links/".$paylinkId."/send","POST");
			return $responces;
		}
		
		//Confirm paylink id after checkout return from Stronghold checkout. 
		public function checkPaylink($paylinkId, $order_id) {
			global $woocommerce;
			$order = wc_get_order($order_id);
		
			if (!$order) {
				wc_add_notice('Invalid order ID in payment callback.', 'error');
				return;
			}
		
			$this->transaction_reference = $this->stronghold_generateTransactionReference();
			$responses = $this->initCurl(array(), "v2/links/" . $paylinkId, "GET");
		
			if ($responses->status_code == 200) {
				$order->add_order_note(__('Payment completed via paylink', 'stronghold') . ' (Transaction reference: ' . $this->transaction_reference . ')');
				$woocommerce->cart->empty_cart();
				$order->set_status('wc-processing'); // Explicitly set status
				$order->payment_complete();
				$order->save();
		
				update_post_meta($order->get_id(), '_stronghold_payment_data', $responses);
				update_post_meta($order->get_id(), '_stronghold_payment_type', 'paylink');
		
				wp_redirect($this->get_return_url($order));
				exit;
			} else {
				$message = 'Paylink expired or payment failed.';
				$order->update_status('wc-failed', $message);
				wc_add_notice($message, 'error');
				wp_redirect(wc_get_checkout_url()); // Redirect back to checkout on failure
				exit;
			}
		}
		
		// Initialize the curl request
		private function initCurl($data,$endpoint,$method,$preferCode = ''){
			$Content_Type = '';
			
			$curlArray = array(
				CURLOPT_URL => $this -> apiurl.$endpoint,
				CURLOPT_RETURNTRANSFER => true,
				CURLOPT_ENCODING => "",
				CURLOPT_MAXREDIRS => 10,
				CURLOPT_TIMEOUT => 30,
				CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
				CURLOPT_CUSTOMREQUEST => $method,
				CURLOPT_HTTPHEADER => array(
					"Accept: application/json",
					"SH-SECRET-KEY: ".$this -> apikey,
				),
			);
			if($method == 'POST'){				
				if(!empty($data)){
					$data = json_encode($data);
				}else{
					$data = '';
				}
				
				$curlArray[CURLOPT_POSTFIELDS] = $data;
				$curlArray[CURLOPT_HTTPHEADER][] = "Content-Type: application/json";
			}
			if($preferCode != ''){
				$curlArray[CURLOPT_HTTPHEADER][] = $preferCode;
			}
			
			$curl = curl_init();
			curl_setopt_array($curl, $curlArray);
			
			$response 	= curl_exec($curl);
			$err 		= curl_error($curl);
			curl_close($curl);
			
			if ($err) {
				throw new stronghold_StrongholdException("Stronghold returned an error. " . $err. ". Please try again");
			 }
			 
			return json_decode($response);
		}
		
		//Search customer before creating.
		public function stronghold_findCustomer($email){
			$billing_email		= urlencode(sanitize_email($email));
			return  $this->initCurl("","v2/customers?email=".$billing_email,"GET");			
		}
		
		//Send Request to create new customer
		public function stronghold_sendCustomer($data){
			$requestDataCustomer = array();

			$billing_first_name	= sanitize_text_field($data['billing_first_name']);
			$billing_last_name	= sanitize_text_field($data['billing_last_name']);
			$billing_email		= sanitize_email($data['billing_email']);
			$billing_state		= sanitize_text_field($data['billing_state']);
			$billing_phone		= sanitize_text_field($data['billing_phone']);
			$billing_phone		= '+1'.str_replace('+1','',$billing_phone);
			$billing_birthdate		= sanitize_text_field($data['billing_birthdate']);
			$external_id		= sanitize_text_field($data['external_id']);
			
			WC()->customer->set_billing_first_name(wc_clean( $billing_first_name )); 
			WC()->customer->set_billing_last_name(wc_clean( $billing_last_name )); 
			WC()->customer->set_billing_email(wc_clean( $billing_email ));  
			WC()->customer->set_billing_state(wc_clean( $billing_state )); 
			WC()->customer->set_billing_phone(wc_clean( $billing_phone )); 
			WC()->customer->set_billing_country(wc_clean( 'US' )); 
			
			$data = array(
					'individual' => array(
						'first_name' => $billing_first_name,
						'last_name' => $billing_last_name,
						'date_of_birth' => $billing_birthdate,
						'email' => $billing_email,
						'mobile' => $billing_phone
					),
					'country' => 'US',
					'state' => $billing_state,
					'external_id' => $external_id
				);
				
			return  $this->initCurl($data,"v2/customers","POST");
			
		}
		
		//Send Request to create new customer token
		public function stronghold_getCustomerToken($data){
			return  $this->initCurl($data,"v2/customers/".$data."/token","GET");			
		}
		
		//Generates a unique transaction reference number
		public function stronghold_generateTransactionReference(){
			$datetime = date("ymdHis");
			return $datetime."-".uniqid();  
		}
		
		//Make Payent Charge Request
		private function stronghold_createCharge($data,$payment_source,$amount){
			$customerId 			= $data->id;			
			$payment_source_id 	= $payment_source;			
			$external_id 		= $data->external_id;	
			$amount 				= $amount * 100;
			
			$chargeArr = array(
					'type' 				=> 'bank_debit',
					'amount' 			=> $amount,
					'currency' 			=> 'usd',
					'customer_id' 		=> $customerId,
					'payment_source_id' 	=> $payment_source_id,
					'source_id' 			=> $payment_source_id,
					'convenience_fee' 	=> 225
				);
			return  $this->initCurl($chargeArr,"v2/charges","POST","Prefer: code=201");			
		}
		
		//Make Authorization request after Payent Charge.
		private function stronghold_authorizeCharge($charge_id){
			return  $this->initCurl(array(),"v2/charges/".$charge_id."/authorize","POST");			
		}
		
		//Make Capture request after Authorization.
		private function stronghold_CaptureCharge($charge_id,$amount){
			return  $this->initCurl(array('amount' => $amount*100),"v2/charges/".$charge_id."/capture","POST");			
		}
		
		//Make Refund request.
		private function stronghold_refundCharge($charge_id){
			return  $this->initCurl(array(),"v2/charges/".$charge_id."/refund","POST","Prefer: ");			
		}

		private function strongholdTip($userData,$payment_source,$tip_price,$charge_id){
			
			$tip_price = $tip_price * 100;
			$username = $_POST['billing_first_name'].' '.$_POST['billing_last_name'];
			
			$tipArr = array(
				'beneficiary_name' => $username,
				'details' => [
					'display_message' => 'Order made by '.$username
				],
				'amount' => $tip_price,
				'customer_id' => $userData->id,
				'payment_source_id' => $payment_source,
				'charge_id' => $charge_id,
				'is_external_charge_id' => false,
				'capture_with_charge' => false
			);
			
			$tipResponce = $this->initCurl($tipArr,"v2/tips","POST");
			
			if($tipResponce->status_code == 201){
				$this->initCurl(array('amount' => $tip_price),"v2/tips/".$tipResponce->result->id."/capture","POST");
			}
		}
		
		//Make a Create Payment Source request.
		public function stronghold_createPaymentSource($token,$email,$publishable_key,$apitype,$intergration_id){
			?>
			<script>
				jQuery(document.body).ready(function($){
					
					jQuery(document.body).on('click','#add_paymeny_saurce',function(){
						
						var strongholdPay = Stronghold.Pay({
							publishableKey: '<?php echo $publishable_key;?>',
							environment: '<?php echo $apitype;?>', // Or live
							integrationId: '<?php echo $intergration_id;?>',
						});
						
						var ajaxscript ='<?php echo $this->ajaxscript;?>';
						$( '#order_methods, #order_review' ).block({ message: null, overlayCSS: { background: '#fff url() no-repeat center', backgroundSize:'16px 16px', opacity: 0.6 } });

						var customerToken = '<?php echo $token;?>';

						strongholdPay.addPaymentSource(customerToken, {
							onSuccess: function (paymentSource) { 							
								jQuery.ajax({
									type : "post",
									url : ajaxscript,
									data : {
											action: "get_stronghold_payment_sources", 
											billing_email : '<?php echo $email;?>',
											nonce : '<?php echo wp_create_nonce( "process_reservation_nonce" );?>'
										},
									success: function(response) {
										//$( '.blockOverlay,.blockUI' ).remove();
										$("#payment_sources_list").html(response);								
									}
								});
							},
							onExit: function () { 
								$( '.blockOverlay,.blockUI' ).remove();
								console.log('source code onExit'); 
							},
							onError: function (err) { 
								$( '.blockOverlay,.blockUI' ).remove();
								console.log('source code onError');  
							}
						});
					});
				});
			</script>
			<?php
		}
		
		public function stronghold_removePaymentSource($payment_source){
			return  $this->initCurl(array(),"v2/payment-sources/".$payment_source,"DELETE");
		}
		
		function remove_manual_refunds() {
			echo '<style>
				.do-manual-refund {
				display: none !important;
				}
			  </style>';
		}
		
	}

	//Built in stronghold exception class
	class stronghold_StrongholdException extends Exception
	{
	}
});