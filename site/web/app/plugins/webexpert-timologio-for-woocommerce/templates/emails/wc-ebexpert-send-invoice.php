<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

do_action( 'woocommerce_email_header', $email_heading, $email ); ?>

<?php
if ($additional_content) {
	echo wpautop($additional_content);
}else { ?>
    <p><?php printf( __( 'We have attached your invoice for order #%d.', 'webexpert-timologio-for-woocommerce' ), $order->get_order_number() ); ?></p>
<?php }

do_action( 'woocommerce_email_order_details', $order, $sent_to_admin, $plain_text, $email );
do_action( 'woocommerce_email_order_meta', $order, $sent_to_admin, $plain_text, $email );
do_action( 'woocommerce_email_customer_details', $order, $sent_to_admin, $plain_text, $email );
do_action( 'woocommerce_email_footer', $email );