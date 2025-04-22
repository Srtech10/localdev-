const { registerPaymentMethod } = window.wc.wcBlocksRegistry;
const stronghold = window.wc.wcSettings.getSetting( 'WC_Stronghold_Payment_Gateway_data', {} );
const stronghold_label = window.wp.htmlEntities.decodeEntities( stronghold.title )
	|| window.wp.i18n.__( 'Stronghold Checkout', '' );
const stronghold_content = () => {
	return window.wp.htmlEntities.decodeEntities( stronghold.description || '' );
};
const Stronghold_arr = {
	name: 'WC_Stronghold_Payment_Gateway',
	label: stronghold_label,
	content: Object( window.wp.element.createElement )( stronghold_content, null ),
	edit: Object( window.wp.element.createElement )( stronghold_content, null ),
	canMakePayment: () => true,
	ariaLabel: stronghold_label,
	supports: {
		features: stronghold.supports,
	},
};
window.wc.wcBlocksRegistry.registerPaymentMethod( Stronghold_arr );