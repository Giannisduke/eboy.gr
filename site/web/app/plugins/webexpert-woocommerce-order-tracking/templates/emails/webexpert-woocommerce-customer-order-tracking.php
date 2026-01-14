<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/*
 * @hooked WC_Emails::email_header() Output the email header
 */
do_action( 'woocommerce_email_header', $email_heading, $email ); ?>

    <p><?php printf( esc_html__( 'Hi %s,', 'webexpert-woocommerce-order-tracking' ), esc_html( $order->get_billing_first_name() ) ); ?></p>
    <p><?php printf( esc_html__( 'We are happy to inform you that your order #%s has been shipped via %s with voucher code %s and should be with you shortly.', 'webexpert-woocommerce-order-tracking' ), "<strong>".$order->get_id()."</strong>", "<strong>".$shipping_company."</strong>", "<strong>".$tracking_number."</strong>" ); ?></p>
    <p><?php printf( esc_html__( 'If you want to track the shipping process of your order, please follow the link below:', 'webexpert-woocommerce-order-tracking' )); ?></p>
    <p style="text-align: center">
        <a target="_blank" href="<?php echo $tracking_number_url?>" style="display: inline-block;padding: 10px 20px;font-size: 14px;text-transform: uppercase;background: <?php echo get_option('woocommerce_email_base_color');?>;color: #fff;text-decoration:none"><?php esc_html_e( 'Track and trace', 'webexpert-woocommerce-order-tracking' ); ?></a>
    </p>

    <p><?php printf(esc_html__( 'If you have any questions, send us an email at %s', 'webexpert-woocommerce-order-tracking' ),$customer_support_email); ?></p>

    <p><?php esc_html_e( 'Thanks for shopping with us.', 'webexpert-woocommerce-order-tracking' ); ?></p>
<?php

/*
 * @hooked WC_Emails::email_footer() Output the email footer
 */
do_action( 'woocommerce_email_footer', $email );