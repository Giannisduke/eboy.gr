<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

if ( ! class_exists( 'WC_Email' ) ) {
	return;
}

/**
 * Class WC_Customer_Cancel_Order
 */
class WC_Webexpert_Send_Invoice extends WC_Email {

	/**
	 * Create an instance of the class.
	 *
	 * @access public
	 * @return void
	 */
	function __construct() {

		$this->id          = 'wc_webexpert_send_invoice';
		$this->title       = __( 'Send an invoice to the customer', 'webexpert-timologio-for-woocommerce' );
		$this->description = __( 'Send the invoice to your customer', 'webexpert-timologio-for-woocommerce' );

		$this->customer_email = true;
		$this->heading     = __( 'Your invoice', 'webexpert-timologio-for-woocommerce' );

		$this->subject     = sprintf( _x( '[%s] Your Invoice ', 'default email subject for invoice emails sent to the customer', 'webexpert-timologio-for-woocommerce' ), get_bloginfo('name') );

		$this->template_html  = 'emails/wc-ebexpert-send-invoice.php';
		$this->template_plain = 'emails/plain/wc-ebexpert-send-invoice.php';
		$this->template_base  = CUSTOM_WC_EMAIL_PATH . 'templates/';


		//add_action( 'woocommerce_order_status_pending_to_cancelled_notification', array( $this, 'trigger' ) );
		//add_action( 'woocommerce_order_status_on-hold_to_cancelled_notification', array( $this, 'trigger' ) );

		parent::__construct();
	}



	 function trigger( $order_id ) {
		$this->object = wc_get_order( $order_id );

		if ( version_compare( '3.0.0', WC()->version, '>' ) ) {
			$order_email = $this->object->billing_email;
		} else {
			$order_email = $this->object->get_billing_email();
		}

		$this->recipient = $order_email;


		if ( ! $this->is_enabled() || ! $this->get_recipient() ) {
			return;
		}


		$this->send( $this->get_recipient(), $this->get_subject(), $this->get_content(), $this->get_headers(), $this->get_attachments() );
	}

	public function get_content_html() {
		return wc_get_template_html( $this->template_html, array(
			'order'         => $this->object,
			'email_heading' => $this->get_heading(),
			'additional_content' => $this->get_additional_content(),
			'sent_to_admin' => false,
			'plain_text'    => false,
			'email'			=> $this
		), '', $this->template_base );
	}


	public function get_content_plain() {
		return wc_get_template_html( $this->template_plain, array(
			'order'         => $this->object,
			'email_heading' => $this->get_heading(),
			'additional_content' => $this->get_additional_content(),
			'sent_to_admin' => false,
			'plain_text'    => true,
			'email'			=> $this
		), '', $this->template_base );
	}

}