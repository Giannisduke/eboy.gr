<?php
/**
 * Customer completed order email (plain text)
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/emails/plain/customer-completed-order.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see     https://docs.woocommerce.com/document/template-structure/
 * @package WooCommerce/Templates/Emails/Plain
 * @version 3.5.0
 */

if (!defined('ABSPATH')) {
	exit;
}

echo '= ' . esc_html($email_heading) . " =\n\n";

/* translators: %s: Customer first name */
echo sprintf(esc_html__('Hi %s,', 'webexpert-woocommerce-order-tracking'), esc_html($order->get_billing_first_name())) . "\n\n";

echo sprintf(esc_html__('We are happy to inform you that your order #%s has been shipped via %s with voucher code %s and should be with you shortly.', 'webexpert-woocommerce-order-tracking'), $order->get_id(), $shipping_company, $tracking_number) . "\n\n";
echo sprintf(esc_html__('If you want to track the shipping process of your order, please follow the link below:', 'webexpert-woocommerce-order-tracking')) . "\n";
echo sprintf(esc_html__($tracking_number_url)) . "\n\n";
echo sprintf(esc_html__('If you have any questions, send us an email at %s', 'webexpert-woocommerce-order-tracking'), $customer_support_email) . "\n\n";

echo esc_html__('Thanks for shopping with us.', 'webexpert-woocommerce-order-tracking') . "\n\n";

echo "\n=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=\n\n";

echo esc_html(apply_filters('woocommerce_email_footer_text', get_option('woocommerce_email_footer_text'))); // phpcs:ignore WordPress.XSS.EscapeOutput.OutputNotEscaped
