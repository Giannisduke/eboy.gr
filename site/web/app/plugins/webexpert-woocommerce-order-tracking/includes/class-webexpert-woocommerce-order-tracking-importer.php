<?php
if ( !defined('WP_LOAD_IMPORTERS') )
	return;

require_once ABSPATH . 'wp-admin/includes/import.php';
require_once plugin_dir_path(__FILE__)  . "vendor/autoload.php";

if ( !class_exists( 'WP_Importer' ) ) {
	$class_wp_importer = ABSPATH . 'wp-admin/includes/class-wp-importer.php';
	if ( file_exists( $class_wp_importer ) )
		require_once $class_wp_importer;
}

if ( class_exists( 'WP_Importer' ) ) {
	class Webexpert_woocommerce_order_tracking_importer extends WP_Importer {

		var $posts = array ();
		var $file;
        var $count_shipping_vouchers=0;
        var $count_status_changes=0;

		function header() {
			echo '<div class="wrap">';
			echo '<h2>'.__('Shipping number importer', 'webexpert-woocommerce-order-tracking').'</h2>';
		}

		function footer() {
			echo '</div>';
		}

		function unhtmlentities($string) { // From php.net for < 4.3 compat
			$trans_tbl = get_html_translation_table(HTML_ENTITIES);
			$trans_tbl = array_flip($trans_tbl);
			return strtr($string, $trans_tbl);
		}

		function greet() {
			?>
			<div class="narrow">
				<p><?php _e('This importer allows you to import Shipping Numbers to your store via a csv file and optionally update order status.', 'webexpert-woocommerce-order-tracking'); ?></p>
                <p><?php _e('CSV columns are', 'webexpert-woocommerce-order-tracking');?>: <code>order_id,shipping_number,order_status,carrier</code></p>

                <p><code>order_id</code> <?php _e('required', 'webexpert-woocommerce-order-tracking');?> - <?php _e('Order ID');?><br>
                    <code>shipping_number</code> <?php _e('required', 'webexpert-woocommerce-order-tracking');?> - <?php _e('Tracking number', 'webexpert-woocommerce-order-tracking');?><br>
                    <code>order_status</code> <?php _e('optional', 'webexpert-woocommerce-order-tracking');?> - <?php _e('Desired order status.', 'webexpert-woocommerce-order-tracking');?> - <?php _e('Accepts:', 'webexpert-woocommerce-order-tracking');?>  <em><?php echo implode(",",array_keys(wc_get_order_statuses()));?></em><br>
                    <code>carrier</code> <?php _e('optional', 'webexpert-woocommerce-order-tracking');?><br>
                </p>

				<form enctype="multipart/form-data" method="post" action="<?php echo admin_url('admin.php?import=Webexpert_woocommerce_order_tracking_importer&amp;step=1');?>"><p>
						<label for="upload"><?php _e("Choose the CSV file from your computer:"."<br>", 'webexpert-woocommerce-order-tracking'); ?></label>
						<input type="file" id="upload" name="import" size="25" accept=".csv, application/vnd.openxmlformats-officedocument.spreadsheetml.sheet, application/vnd.ms-excel" />
						<input type="hidden" name="action" value="save" />
						<?php submit_button('Import');?>
						<?php wp_nonce_field('Webexpert_order_tracking-import'); ?>
				</form>
			</div>
			<?php
		}

		function import_codes() {
			global $wpdb;
			$csv = new ParseCsv\Csv();
			$csv->auto($this->file);

			$allowed_statuses=array_keys(wc_get_order_statuses());

			foreach ($csv->data as $data) {
			    if (isset($data['order_id'])) {
			        $order=wc_get_order($data['order_id']);
			        if ($order) {

			            if (!empty($data['shipping_number'])) {
				            $order->update_meta_data('_shipping_tracking_number', $data['shipping_number']);
				            $order->save();
				            $this->count_shipping_vouchers++;
			            }

                        if (!empty($data['carrier'])) {
	                        $order->update_meta_data('_webexpert_order_tracking_carrier',$data['carrier']);
	                        $order->save();
                        }

			            if (!empty($data['order_status'])) {
			                if (in_array($data['order_status'],$allowed_statuses) || in_array("wc-{$data['order_status']}",$allowed_statuses)) {
				                if ($order->get_status()!=$data['order_status']) {
					                $order->set_status($data['order_status']);
					                $order->save();
					                $this->count_status_changes++;
				                }
			                }
			            }
			        }
			    }
			}
		}

		function import() {
			$file = wp_import_handle_upload();
			if ( isset($file['error']) ) {
				echo $file['error'];
				return;
			}

			$this->file = $file['file'];
			$this->import_codes();
			wp_import_cleanup($file['id']);
			do_action('import_done', 'Webexpert_woocommerce_order_tracking_importer');

			echo '<h3>';
			printf(__('All done. <a href="%s">Have fun!</a>', 'webexpert-woocommerce-order-tracking'),admin_url("edit.php?post_type=shop_order"));
			echo '</h3>';

			echo __('Shipping tracking numbers added: '.$this->count_shipping_vouchers, 'webexpert-woocommerce-order-tracking');
			echo "<br>";
			echo __('Order statuses updated: '.$this->count_status_changes, 'webexpert-woocommerce-order-tracking');
		}

		function dispatch() {
			if (empty ($_GET['step']))
				$step = 0;
			else
				$step = (int) $_GET['step'];

			$this->header();

			switch ($step) {
				case 0 :
					$this->greet();
					break;
				case 1 :
					check_admin_referer('Webexpert_order_tracking-import');
					$result = $this->import();
					if ( is_wp_error( $result ) )
						echo $result->get_error_message();
					break;
			}

			$this->footer();
		}

	}
}

$woocommerce_order_tracking_importer = new Webexpert_woocommerce_order_tracking_importer();
register_importer('Webexpert_woocommerce_order_tracking_importer', __('Web Expert shipping number importer', 'webexpert-woocommerce-order-tracking'), __('Imports Shipping Numbers to your store via a csv file and optionally update order status.', 'webexpert-woocommerce-order-tracking'), array ($woocommerce_order_tracking_importer, 'dispatch'));