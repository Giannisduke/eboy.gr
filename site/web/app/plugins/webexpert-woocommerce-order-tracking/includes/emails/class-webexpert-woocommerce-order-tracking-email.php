<?php
if (!defined('ABSPATH')) {
    exit;
}
if (!class_exists('WC_Email')) {
    return;
}

class Webexpert_woocommerce_order_tracking_email extends WC_Email {

    public function __construct() {
        $this->id = 'customer_tracking_number';
        $this->customer_email = true;
        $this->title = __('Order Tracking Voucher', 'webexpert-woocommerce-order-tracking');
        $this->description = __('Order Tracking emails are sent to customers when an order is marked as completed and indicate that their orders have been shipped.', 'webexpert-woocommerce-order-tracking');
        $this->template_html = 'emails/webexpert-woocommerce-customer-order-tracking.php';
        $this->template_plain = 'emails/plain/webexpert-woocommerce-customer-order-tracking.php';
        $this->template_base = CUSTOM_WC_EMAIL_PATH_FOR_ORDER_TRACKING . 'templates/';
        $this->placeholders = array(
            '{site_title}'   => $this->get_blogname(),
            '{order_date}'   => '',
            '{order_number}' => '',
            '{order_id}' => ''
        );

        if (apply_filters('webexpert_woocommerce_order_tracking_disable_oncomplete',true)) {
            add_action('woocommerce_order_status_completed_notification', array($this, 'trigger'), 10, 2);
        }
        add_action('send_tracking_number', array($this, 'trigger'), 10, 2);

        parent::__construct();
    }

    public function trigger($order_id, $order = false) {
        if ($order_id && !is_a($order, 'WC_Order')) {
            $order = wc_get_order($order_id);
        }
        if (is_a($order, 'WC_Order')) {
            // order meta is cached, so we need a refresh
            $order=wc_get_order($order->get_id());

            $this->object = $order;
            $this->recipient = $this->object->get_billing_email();
            $this->placeholders['{order_date}'] = wc_format_datetime($this->object->get_date_created());
            $this->placeholders['{order_number}'] = $this->object->get_order_number();
        }

        if ($this->is_enabled() && $this->get_recipient() && $this->get_tracking_number()) {
            $this->send($this->get_recipient(), $this->get_subject(), $this->get_content(), $this->get_headers(), $this->get_attachments());

            if (class_exists("Webexpert_Woocommerce_Sms") && $this->get_option('enable_webexpert_woocommerce_sms_integration') == "yes") {
                $message = $this->get_option('webexpert_woocommerce_sms_message', $this->get_default_sms_message());
                $message=str_replace('{order_number}', $this->object->get_order_number(), $message);
                $message=str_replace('{shipping_company}', $this->get_shipping_company(), $message);
                $message=str_replace('{tracking_number}', $this->get_tracking_number(), $message);
                $message=str_replace('{order_id}', $order_id, $message);

                webexpert_woocommerce_sms_send_order_message($order, $message);
            }
        }
    }

    public function get_default_subject() {
        return __('Your {site_title} order tracking voucher', 'webexpert-woocommerce-order-tracking');
    }

    public function get_default_tracking_number_url() {
        return __('https://www.shipping.com/track/{tracking_number}', 'webexpert-woocommerce-order-tracking');
    }

    public function get_default_sms_message() {
        return __('Your order #{order_number} has been shipped via {shipping_company} with voucher code {tracking_number}.', 'webexpert-woocommerce-order-tracking');
    }

    public function get_default_shipping_company_name() {
        return __('Shipping company name', 'webexpert-woocommerce-order-tracking');
    }

    public function get_tracking_number_url() {
	    $order = $this->object;
        $tracking_number = $this->get_tracking_number();
        $tracking_number_url = $this->format_string(apply_filters('webexpert_woocommerce_order_tracking_custom_shipping_tracking_url',$this->get_option('shipping_tracking_url', $this->get_default_tracking_number_url()),$order->get_id()));
        return str_replace('{order_id}', $order->get_id(), str_replace('{tracking_number}', $tracking_number, $tracking_number_url));
    }

    public function get_shipping_company() {
        $order = $this->object;
        return apply_filters('webexpert_woocommerce_order_tracking_custom_shipping_company_name',$this->get_option('shipping_company_name', $this->get_default_shipping_company_name()),$order->get_id());
    }

    public function get_customer_support_mail() {
	    return apply_filters('webexpert_woocommerce_order_tracking_custom_support_email',$this->get_option('feedback_email', get_option('admin_email')));
    }

    public function get_tracking_number() {
        $order = $this->object;
        return $order->get_meta(apply_filters('webexpert_woocommerce_order_tracking_custom_tracking_field','_shipping_tracking_number'));
    }

    public function get_default_heading() {
        return __('Thank you for your order', 'webexpert-woocommerce-order-tracking');
    }

    public function get_content_html() {
        return wc_get_template_html(
            $this->template_html, array(
            'order'               => $this->object,
            'email_heading'       => $this->get_heading(),
            'sent_to_admin'       => false,
            'plain_text'          => false,
            'email'               => $this,
            'shipping_company'    => $this->get_shipping_company(),
            'tracking_number_url' => $this->get_tracking_number_url(),
            'tracking_number'     => $this->get_tracking_number(),
            'customer_support_email'     => $this->get_customer_support_mail(),
        ), '', $this->template_base
        );
    }

    public function get_content_plain() {
        return wc_get_template_html(
            $this->template_plain, array(
            'order'               => $this->object,
            'email_heading'       => $this->get_heading(),
            'sent_to_admin'       => false,
            'plain_text'          => true,
            'email'               => $this,
            'shipping_company'    => $this->get_shipping_company(),
            'tracking_number_url' => $this->get_tracking_number_url(),
            'tracking_number'     => $this->get_tracking_number(),
            'customer_support_email'     => $this->get_customer_support_mail(),
        ), '', $this->template_base
        );
    }

    public function init_form_fields() {
        $fields = array(
            'enabled'               => array(
                'title'   => __('Enable/Disable', 'webexpert-woocommerce-order-tracking'),
                'type'    => 'checkbox',
                'label'   => __('Enable this email notification', 'webexpert-woocommerce-order-tracking'),
                'default' => 'yes',
            ),
            'subject'               => array(
                'title'       => __('Subject', 'webexpert-woocommerce-order-tracking'),
                'type'        => 'text',
                'desc_tip'    => true,
                'description' => sprintf(__('Available placeholders: %s', 'webexpert-woocommerce-order-tracking'), '<code>{site_title}, {order_date}, {order_number}</code>'),
                'placeholder' => $this->get_default_subject(),
                'default'     => '',
            ),
            'heading'               => array(
                'title'       => __('Email heading', 'webexpert-woocommerce-order-tracking'),
                'type'        => 'text',
                'desc_tip'    => true,
                'description' => sprintf(__('Available placeholders: %s', 'webexpert-woocommerce-order-tracking'), '<code>{site_title}, {order_date}, {order_number}</code>'),
                'placeholder' => $this->get_default_heading(),
                'default'     => '',
            ),
            'email_type'            => array(
                'title'       => __('Email type', 'webexpert-woocommerce-order-tracking'),
                'type'        => 'select',
                'description' => __('Choose which format of email to send.', 'webexpert-woocommerce-order-tracking'),
                'default'     => 'html',
                'class'       => 'email_type wc-enhanced-select',
                'options'     => $this->get_email_type_options(),
                'desc_tip'    => true,
            ),
            'shipping_company_name' => array(
                'title'       => __('Shipping company name', 'webexpert-woocommerce-order-tracking'),
                'type'        => 'text',
                'desc_tip'    => true,
                'description' => __('The name of the shipping company', 'webexpert-woocommerce-order-tracking'),
                'placeholder' => $this->get_default_shipping_company_name(),
                'default'     => '',
            ),
            'shipping_tracking_url' => array(
                'title'       => __('Shipping tracking URL', 'webexpert-woocommerce-order-tracking'),
                'type'        => 'text',
                'desc_tip'    => true,
                'description' => __('The dynamic URL of order tracking with {tracking_number} and {order_id} variable', 'webexpert-woocommerce-order-tracking'),
                'placeholder' => $this->get_default_tracking_number_url(),
                'default'     => '',
            ),
            'feedback_email' => array(
	            'title'       => __('Custom support email address', 'webexpert-woocommerce-order-tracking'),
	            'type'        => 'text',
	            'desc_tip'    => true,
	            'description' => __('The email address printed on the shipping message', 'webexpert-woocommerce-order-tracking'),
	            'default'     => get_option('admin_email'),
            ),
            'disable_order_completed_email' => array(
	            'title'       => __('Disable order complete email', 'webexpert-woocommerce-order-tracking'),
	            'type'        => 'checkbox',
	            'desc_tip'    => true,
	            'description' => __('Disable WooCommerce default Order Complete email', 'webexpert-woocommerce-order-tracking'),
	            'default'     => 'yes',
            )
        );

        if (class_exists("Webexpert_Woocommerce_Sms")) {
            $fields['enable_webexpert_woocommerce_sms_integration'] = array(
                'title'       => __('Enable SMS integration', 'webexpert-woocommerce-order-tracking'),
                'type'        => 'checkbox',
                'desc_tip'    => true,
                'description' => __('Send SMS when user has filled in cellphone via Web Expert Woocommerce SMS', 'webexpert-woocommerce-order-tracking'),
                'default'     => 'yes',
            );
            $fields['webexpert_woocommerce_sms_message'] = array(
                'title'       => __('SMS message', 'webexpert-woocommerce-order-tracking'),
                'type'        => 'text',
                'desc_tip'    => true,
                'description' => __('SMS message sent to customer. Use variables {order_number}, {shipping_company}, {tracking_number}', 'webexpert-woocommerce-order-tracking'),
                'placeholder' => $this->get_default_sms_message(),
                'default'     => '',
            );
        }

        $this->form_fields = $fields;
    }
}