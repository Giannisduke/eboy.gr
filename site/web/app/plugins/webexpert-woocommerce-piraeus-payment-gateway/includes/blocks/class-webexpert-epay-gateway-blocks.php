<?php
use Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType;

final class WebExpert_Epay_Payments_Blocks_Support extends AbstractPaymentMethodType {
	/**
	 * The gateway instance.
	 *
	 * @var WC_Gateway_Dummy
	 */
	private $gateway;

	/**
	 * Payment method name/id/slug.
	 *
	 * @var string
	 */
	protected $name = 'piraeusbank_gateway';

	/**
	 * Initializes the payment method type.
	 */
	public function initialize() {
		$this->settings = get_option( 'woocommerce_piraeusbank_gateway_settings', [] );
		$gateways       = WC()->payment_gateways->payment_gateways();
		$this->gateway  = $gateways[ $this->name ];
	}

	/**
	 * Returns if this payment method should be active. If false, the scripts will not be enqueued.
	 *
	 * @return boolean
	 */
	public function is_active() {
		return $this->gateway->is_available();
	}

	/**
	 * Returns an array of scripts/handles to be registered for this payment method.
	 *
	 * @return array
	 */
	public function get_payment_method_script_handles() {
		$script_path       = '/assets/js/frontend/blocks.js';
		$script_asset_path = WebExpert_Epay_Payments::plugin_abspath() . 'assets/js/frontend/blocks.asset.php';
		$script_asset      = file_exists( $script_asset_path )
			? require( $script_asset_path )
			: array(
				'dependencies' => array(),
				'version'      => '1.2.0'
			);
		$script_url        = WebExpert_Epay_Payments::plugin_url() . $script_path;

		wp_register_script(
			'webexpert-woocommerce-piraeus-payment-blocks',
			$script_url,
			$script_asset[ 'dependencies' ],
			$script_asset[ 'version' ],
			true
		);

		if ( function_exists( 'wp_set_script_translations' ) ) {
			wp_set_script_translations( 'webexpert-woocommerce-piraeus-payment-blocks', 'webexpert-woocommerce-piraeus-payment-gateway', WebExpert_Epay_Payments::plugin_abspath() . 'languages/' );
		}

		return [ 'webexpert-woocommerce-piraeus-payment-blocks' ];
	}

	/**
	 * Returns an array of key=>value pairs of data made available to the payment methods script.
	 *
	 * @return array
	 */
	public function get_payment_method_data() {
		if (!is_admin() && is_checkout()) {
			if (is_wc_endpoint_url('order-pay')) {
				$order_id = get_query_var('order-pay');
				$order = wc_get_order($order_id);
				$cart_total = $order->get_total();
			} else {
				$cart_total = WC()->cart->get_total('');
			}
			$this->gateway->calculate_installments($cart_total);
		}

		$settings = [
			'id'          => $this->gateway->id,
			'title'       => $this->get_setting( 'title' ),
			'description' => $this->get_setting( 'description' ),
			'icon'        => $this->gateway->icon,
			'supports'    => array_filter( $this->gateway->supports, [ $this->gateway, 'supports' ] ),
		];

		if ($this->gateway->get_option('pb_installments') > 1) {
			$installments=[];
			foreach ($this->gateway->installments_range as $num) {
				$installments[$num]=sprintf( esc_html( _n( 'No installments', '%d installments from %s per month', $num, 'webexpert-woocommerce-piraeus-payment-gateway'  ) ), $num, html_entity_decode( strip_tags(wc_price(round($cart_total/$num,2)))));
			}
			$settings['installments']=$installments;
		}

		return $settings;
	}
}