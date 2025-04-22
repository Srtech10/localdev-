jQuery(document).ready(function($){
	var nonce 			= stronghold_params.nonce;
	var ajaxscript 		= stronghold_params.ajaxurl;
	var publishable_key = stronghold_params.publishable_key;
	var intergration_id = stronghold_params.intergration_id;
	var apitype 		= stronghold_params.apitype;	
	var payment_source 	= '';
	var show_payment 	= 0;
	
	function updateChcekout (){
		var paymentMethod = $('input[name="payment_method"]:checked').val();	
				
		var data = {
			action: 'woocommerce_update_order_review',
			security: wc_checkout_params.update_order_review_nonce,
			payment_method: paymentMethod,
			post_data: $( 'form.checkout' ).serialize()
		};
		
		xhr = $.ajax({
			type: 'POST',
			url: ajaxscript,
			data: data,
			success: function( response ) {
				var order_output = $(response);
				$( '#order_review' ).html( response['fragments']['.woocommerce-checkout-review-order-table']+response['fragments']['.woocommerce-checkout-payment']);
				$( '.blockOverlay,.blockUI' ).remove();	
				$('body').trigger('updated_checkout');
				if(show_payment > 0){
					$('#'+payment_source).attr('checked','checked');
				}else{
					$('#'+payment_source).removeAttr('checked');
				}
			},
			error: function(code){
				console.log('ERROR');
			}
		});
	}	
	
	var clickcount = 1;	
	var errors = {
			mer :false,
			fer :false,
			ler :false,
			ser :false,
			per :false,
			ber :false,
		};
	
	$( document.body ).off().on('click','#get_stronghold_email',function(ev){
		
		var email = $('body').find('#email').val();
		var billing_email = $('body').find('#billing_email').val();
		if(billing_email != ''){
			billing_email = billing_email;
		}else{
			billing_email = email;
		}
		var billing_first_name 	= $('body').find('#billing_first_name').val();
		var billing_last_name 	= $('body').find('#billing_last_name').val();
		var billing_state 		= $('body').find('#billing_state').val();
		var billing_phone 		= $('body').find('#billing_phone').val();
		var billing_birthdate 	= $('body').find('#billing_birthdate').val();
		
		if(billing_first_name == ''){
			if(errors.fer === false){
				$('#billing_first_name').after('<p class="billing_f_error" style="color:red">Please enter your first name.</p>').focus();
				errors.fer = true;
			}
			$('#billing_first_name').focus();
			return;
		}else
		if(billing_last_name == ''){
			$('.billing_f_error').remove();
			if(errors.ler === false){
				$('#billing_last_name').after('<p class="billing_l_error" style="color:red">Please enter your last name.</p>').focus();
				errors.ler = true;
			}
			$('#billing_last_name').focus();
			return;
		}else
		if(billing_birthdate == ''){
			$('.billing_l_error,.billing_f_error').remove();
			if(errors.ber === false){
				$('#billing_birthdate').after('<p class="billing_b_error" style="color:red">Please enter birthdate.</p>').focus();
				errors.ber = true;
			}
			$('#billing_birthdate').focus();
			return;
		}else
		if(billing_state == ''){
			$('.billing_b_error,.billing_l_error,.billing_f_error').remove();
			if(errors.ser === false){
				$('#billing_state').after('<p class="billing_s_error" style="color:red">Please enter your State.</p>').focus();
				errors.ser = true;
			}
			$('#billing_state').focus();
			return;
		}else
		if(billing_phone == ''){
			$('.billing_s_error,.billing_b_error,.billing_l_error,.billing_f_error').remove();
			if(errors.per === false){
				$('#billing_phone').after('<p class="billing_p_error" style="color:red">Please enter your phone number.</p>').focus();
				errors.per = true;
			}
			$('#billing_phone').focus();
			return;
		}else
		if(billing_email == ''){
			$('.billing_p_error,.billing_s_error,.billing_b_error,.billing_l_error,.billing_f_error').remove();
			if(errors.mer === false){
				$('#billing_email').after('<p class="billing_email_error" style="color:red">Please enter Email Address.</p>').focus();
				errors.mer = true;
			}
			$('#billing_email').focus();
			return;
		}					
		
		$( '#order_methods, #order_review' ).block({ message: null, overlayCSS: { background: '#fff url() no-repeat center', backgroundSize:'16px 16px', opacity: 0.6 } });

		$('.billing_email_error,.billing_p_error,.billing_s_error,.billing_b_error,.billing_l_error,.billing_f_error').remove();
		$(this).attr('value','Wait...');
		jQuery.ajax({
			type : "post",
			url : ajaxscript,
			data : {
				action				: "get_stronghold_payment_sources", 
				billing_email 		: billing_email,
				billing_first_name 	: billing_first_name,
				billing_last_name 	: billing_last_name,
				billing_state 		: billing_state,
				billing_phone 		: billing_phone,
				billing_birthdate 	: billing_birthdate,
				nonce : nonce
			},
			success: function(response) {
				$( '.blockOverlay,.blockUI' ).remove();
				$("#payment_sources_list").html(response);
				addTipToCheckout(0,0);
			}
		});
		
	});

	$(document.body).on('change','.stronghold_payment_sources input[name="payment_source"]:checked',function(){
		payment_source = $(this).val();
		
		if(payment_source != ''){
			$(document.body).find('.stronghold-tips').show();
		}
	});
	
	$(document.body).on('click','.stronghold_tip',function(){
		var previousValue = $(this).attr('previousValue');
		var name = $(this).attr('name');

		if (previousValue == 'checked'){
			$(this).removeAttr('checked');
			$(this).attr('previousValue', false);
		}else{
			$("input[name="+name+"]:radio").attr('previousValue', false);
			$(this).attr('previousValue', 'checked');
		}
	});
		
	$(document.body).on('click','#add_stronghold_custom_tip',function(){
		var btntext = $(this).text();						
		var CusTipValue = $('#stronghold_custom_tip_price').val();
		var newsession = 0;
		
		if(btntext === "Add" || btntext === "Update"){
			if(CusTipValue > 0 || CusTipValue != ''){
				$(this).text('Remove');
				addTipToCheckout(CusTipValue,1);
			}else{
				$(this).text('Add');
				addTipToCheckout(0,1);
			}
		}else{
			$('#stronghold_custom_tip_price').val('');
			$(this).text('Add');
			addTipToCheckout(0,1);
		}
	});
	
	$(document.body).on('click','#stronghold_custom_tip',function(){
		var btntext = $(this).text();
		var newsession;		
		
		if(btntext === "Enter Custom Amount"){
			newsession = 1;
			$('.stronghold-tip-options').hide();
			$('.stronghold-tip-options').find('input[type="radio"]').removeAttr('checked').attr('previousValue', false);
			$('.stronghold_custom_tip').show();
		}else{
			newsession = 0;
			$('.stronghold_custom_tip').find('input[type="number"]').val('');
			$('.stronghold_custom_tip').hide();
			$('.stronghold-tip-options').show();
		}
		addTipToCheckout(0,newsession);
		$(this).text(function(i, text){							
			return text === "Enter Custom Amount" ? "Return to Tips List" : "Enter Custom Amount";
		});
	});
	
	$(document.body).on('click','.stronghold-tip-options input[type="radio"]',function(){						
		var checked = $(this).attr('previousvalue');
		if(checked != 'checked'){
			addTipToCheckout(0,0);
		}else{
			addTipToCheckout($(this).val(),0);
		}
	});
	
	function addTipToCheckout(amount,session){
		$( '#order_methods, #order_review' ).block({ message: null, overlayCSS: { background: '#fff url() no-repeat center', backgroundSize:'16px 16px', opacity: 0.6 } });
		
		show_payment = amount;
		
		jQuery.ajax({
			type : "post",
			url : ajaxscript,
			data : {
				action: "add_stronghold_payment_tip", 
				amount: amount,
				session: session,
				payment_source: payment_source,
				nonce : nonce
			},
			success: function(response) {
				updateChcekout();				
			}
		});
	}
	
	$(document.body).on('click','.unlink-payment-source',function(){
		if (confirm('Are you sure you want to remove this Payment Source?')) {
			var payment_source = $(this).attr('data-payment-id');
			
			$( '#order_methods, #order_review' ).block({ message: null, overlayCSS: { background: '#fff url() no-repeat center', backgroundSize:'16px 16px', opacity: 0.6 } });
		
			jQuery.ajax({
				type : "post",
				url : ajaxscript,
				data : {
					action: "remove_payment_source", 
					payment_source: payment_source,
					nonce : nonce
				},
				success: function(response) {
					updateChcekout();				
				}
			});
		}
	});
	
});