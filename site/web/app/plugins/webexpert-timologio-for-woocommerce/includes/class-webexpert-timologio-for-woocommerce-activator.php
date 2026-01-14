<?php
class Webexpert_Timologio_For_Woocommerce_Activator {
	public static function activate() {

		if(empty(get_option("webexpert_timologio_for_woocommerce_invoice_serial")))
			update_option("webexpert_timologio_for_woocommerce_invoice_serial" , "Τ");

		if(empty(get_option("webexpert_timologio_for_woocommerce_receipt_serial")))
			update_option("webexpert_timologio_for_woocommerce_receipt_serial" , "Α");

		if(empty(get_option("webexpert_timologio_for_woocommerce_credit_serial")))
		update_option("webexpert_timologio_for_woocommerce_credit_serial" , "ΠΤ");



		if(empty(get_option("webexpert_timologio_for_woocommerce_cancel_serial")))
			update_option("webexpert_timologio_for_woocommerce_cancel_serial" , "ΑΤ");

        if(empty(get_option("webexpert_timologio_for_woocommerce_cancel_receipt_serial")))
            update_option("webexpert_timologio_for_woocommerce_cancel_receipt_serial" , "ΑΑ");

        if(empty(get_option("webexpert_timologio_for_woocommerce_return_receipt_serial")))
            update_option("webexpert_timologio_for_woocommerce_return_receipt_serial" , "ΑΕ");

		if(empty(get_option("webexpert_timologio_for_woocommerce_invoice_starting")))
		update_option("webexpert_timologio_for_woocommerce_invoice_starting" , 1);

		if(empty(get_option("webexpert_timologio_for_woocommerce_receipt_starting")))
			update_option("webexpert_timologio_for_woocommerce_receipt_starting" , 1);

        if(empty(get_option("webexpert_timologio_for_woocommerce_cancel_receipt_starting")))
            update_option("webexpert_timologio_for_woocommerce_cancel_receipt_starting" , 1);

        if(empty(get_option("webexpert_timologio_for_woocommerce_receipt_return_starting")))
            update_option("webexpert_timologio_for_woocommerce_receipt_return_starting" , 1);

		if(empty(get_option("webexpert_timologio_for_woocommerce_credit_starting")))
			update_option("webexpert_timologio_for_woocommerce_credit_starting" , 1);

		if(empty(get_option("webexpert_timologio_for_woocommerce_cancel_starting")))
			update_option("webexpert_timologio_for_woocommerce_cancel_starting" , 1);

		if(empty(get_option("webexpert_timologio_for_woocommerce_invoice_qr_digit_seperation")))
				update_option("webexpert_timologio_for_woocommerce_invoice_qr_digit_seperation" , ",");

		if(empty(get_option("webexpert_timologio_for_woocommerce_invoice_qr_delimiter")))
			  update_option("webexpert_timologio_for_woocommerce_invoice_qr_delimiter" , ";");

		if(empty(get_option("webexpert_timologio_for_woocommerce_invoice_qr_thousands_seperation")))
				update_option("webexpert_timologio_for_woocommerce_invoice_qr_thousands_seperation" , ".");


	}
}
