<?php
use Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType;

final class WC_stronghold_Blocks extends AbstractPaymentMethodType {

	private $gateway;
	protected $name = 'WC_Stronghold_Payment_Gateway';

	public function initialize() {
		$this->settings = get_option( 'woocommerce_{$this->name}_settings', [] );
		$this->gateway = new WC_Stronghold_Payment_Gateway();
		
	}

	public function is_active() {
		return true;
	}

	public function get_payment_method_script_handles() {
		wp_register_script(
			'wc-WC_Stronghold_Payment_Gateway-blocks-integration',
			plugins_url( 'block/checkout.js', __FILE__ ),
			[
				'wc-blocks-registry',
				'wc-settings',
				'wp-element',
				'wp-html-entities',
				'wp-i18n',
			],
			false,
			true
		);	
	
		wp_register_script( 'stronghold_js', plugins_url( '/js/stronghold.js', __FILE__ ), array( 'jquery' ) );
		wp_register_script( 'stronghold', 'https://api.strongholdpay.com/v2/js', array( 'jquery' ) );
		wp_enqueue_style( 'stronghold_css', plugins_url( '/css/stronghold.css', __FILE__ ) );

		// in most payment processors you have to use PUBLIC KEY to obtain a token
		wp_localize_script( 'stronghold_js', 'stronghold_params', array(
			'ajaxurl' 			=> $this->gateway->ajaxscript,
			'publishable_key' 	=> $this->gateway->publishable_key,
			'intergration_id' 	=> $this->gateway->intergration_id,
			'apitype' 			=> $this->gateway->apitype,
			'nonce' 				=> wp_create_nonce( "process_reservation_nonce" )
		) ); 
		return array( 'wc-WC_Stronghold_Payment_Gateway-blocks-integration',
					'stronghold_js',
					'stronghold',
		);
		
	}

	public function get_payment_method_data() {
		return array(
			'title' 			=> $this->gateway->title,
			'description' 	=> $this->gateway->description,
			'supports'  		=> $this->gateway->supports,
		);
	}

}
