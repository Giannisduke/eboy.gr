<?php

/**
 * The admin-specific functionality of the plugin.
 *
 * @link       https://www.webexpert.gr/
 * @since      1.0.0
 *
 * @package    Acs_Voucher_For_Woocommerce
 * @subpackage Acs_Voucher_For_Woocommerce/admin
 */

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    Acs_Voucher_For_Woocommerce
 * @subpackage Acs_Voucher_For_Woocommerce/admin
 * @author     Web Expert <info@webexpert.gr>
 */

class Acs_Voucher_For_Woocommerce_Admin {

	/**
	 * The ID of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $plugin_name    The ID of this plugin.
	 */
	private $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $version    The current version of this plugin.
	 */
	private $version;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @param      string    $plugin_name       The name of this plugin.
	 * @param      string    $version    The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {

		$this->plugin_name = $plugin_name;
		$this->version = $version;

	}

	/**
	 * Register the stylesheets for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_styles() {

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in Acs_Voucher_For_Woocommerce_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Acs_Voucher_For_Woocommerce_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */
		if( ! wp_style_is( 'select2', 'registered' ) ) {
			wp_register_style( 'select2', WC()->plugin_url() . '/assets/css/select2.css', null, $this->version );
		}
		wp_enqueue_style('jquery-ui', '//code.jquery.com/ui/1.11.4/themes/smoothness/jquery-ui.min.css');
		wp_enqueue_style( 'select2' );
		wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'css/acs-voucher-for-woocommerce-admin.css', array(), $this->version, 'all' );
	}

	/**
	 * Register the JavaScript for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts() {

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in Acs_Voucher_For_Woocommerce_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Acs_Voucher_For_Woocommerce_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		wp_enqueue_script('jquery-ui-datepicker');
		wp_enqueue_script('select2');
		wp_register_script($this->plugin_name, plugin_dir_url(__FILE__) . 'js/acs-voucher-for-woocommerce-admin.js', array('jquery','select2','jquery-ui-datepicker'), $this->version, false);
		wp_enqueue_script($this->plugin_name);
		wp_localize_script($this->plugin_name, 'webexpert_ajax_object', array('ajax_url' => admin_url('admin-ajax.php')));
	}

	public function jobs_ctp() {
		$labels = array(
			'name'               => _x( 'Jobs', 'post type general name', $this->plugin_name ),
			'singular_name'      => _x( 'Job', 'post type singular name', $this->plugin_name ),
			'menu_name'          => _x( 'Jobs', 'admin menu', $this->plugin_name ),
			'name_admin_bar'     => _x( 'Jobs', 'add new on admin bar', $this->plugin_name ),
			'add_new'            => _x( 'Add New', 'book', $this->plugin_name ),
			'add_new_item'       => __( 'Add New Job', $this->plugin_name ),
			'new_item'           => __( 'New Job', $this->plugin_name ),
			'edit_item'          => __( 'Edit Job', $this->plugin_name ),
			'view_item'          => __( 'View Job', $this->plugin_name ),
			'all_items'          => __( 'All Jobs', $this->plugin_name ),
			'search_items'       => __( 'Search Jobs', $this->plugin_name ),
			'parent_item_colon'  => __( 'Parent Jobs:', $this->plugin_name ),
			'not_found'          => __( 'No jobs found.', $this->plugin_name ),
			'not_found_in_trash' => __( 'No jobs found in Trash.', $this->plugin_name )
		);

		$names = [
			'name'     => 'we_voucher_job',
			'singular' => __('Job',$this->plugin_name),
			'plural'   => __('Jobs',$this->plugin_name)
		];
		$jobs = new PostTypes\PostType($names,[],$labels);

		$jobs->options(
			[
				'has_archive'  => false,
				'show_ui'      => true,
				'public'       => false,
				'exclude_from_search' => true,
				'show_in_nav_menus'=>false,
				'supports'     => ['title'],
				'map_meta_cap' => true,
				'capabilities' => array(
					'create_posts' => false,
				)
			]
		);

		$jobs->columns()->add(
			[
				'my_title'  => __('Title',$this->plugin_name),
				'status'  => __('Job Status',$this->plugin_name),
				'carrier'  => __('Carrier',$this->plugin_name),
				'delivery'  => __('Track & Trace',$this->plugin_name),
				'print'   => __('Print',$this->plugin_name),
				'actions' => __('Actions',$this->plugin_name),
			]
		);

		$jobs->columns()->hide( 'title' );
		$jobs->columns()->hide( 'date' );

		$jobs->columns()->populate('my_title',function ($column, $post_id) {
			$provider=get_post_meta($post_id,'we_voucher_job_provider',true);
			if ($provider=="acs") {
				$accountString = "";
				$account = get_post_meta($post_id, 'we_acs_account', true);
				if (strlen(strval($account)) > 0) {
					$account = strval(intval($account) + 1);
				}
				$accountUserId = get_post_meta($post_id, 'we_acs_account_user_id', true);
				$accountString .= $account;
				if ($accountUserId) {
					$accountString = $accountString . " - " . $accountUserId;
				}
				if ($accountString) {
					$accountString = __("By", $this->plugin_name) . " " . $accountString;
				}

				$order_id = get_post_meta($post_id, 'order_id', true);
				echo "<strong>
            <a href='" . get_edit_post_link($order_id) . "'>
                <div> " . get_the_title($post_id) . " </div>
            </a>

              <div> " . $accountString . "</div>

            </strong>";
			}
		});

		$jobs->columns()->sortable( [
			'my_title'  => [ 'title', false ],
		] );

		$jobs->columns()->populate(
			'carrier', function ($column, $post_id) {
			$provider=get_post_meta($post_id,'we_voucher_job_provider',true);
			if ($provider=="acs") {
				echo __('ACS Courier',$this->plugin_name);
			}
		}
		);

		$jobs->columns()->populate(
			'delivery', function ($column, $post_id) {
			$provider=get_post_meta($post_id,'we_voucher_job_provider',true);
			$order_id = get_post_meta($post_id, 'order_id', true);
			$status = get_post_meta($post_id, 'webexpert_voucher_job_status', true);
			$order=wc_get_order($order_id);
			if ($provider=="acs" && $status!='we-voucher-cancelled' && $order) {
				echo ($order->get_meta('voucher_delivery_status') ?? '-');
			}
		}
		);

		$jobs->columns()->populate(
			'status', function ($column, $post_id) {
			$provider=get_post_meta($post_id,'we_voucher_job_provider',true);
			if ($provider=="acs") {
				$this->get_job_status_formatted($post_id);
			}
		}
		);

		$jobs->columns()->populate(
			'print', function ($column, $post_id) {
			$provider=get_post_meta($post_id,'we_voucher_job_provider',true);
			if ($provider=="acs") {
				$order_id = get_post_meta($post_id, 'order_id', true);
				?>
                <button data-order="<?php echo $order_id; ?>" type="button" class="button acs_print_voucher_type1 has-spinner" id="acs_print_voucher_type_1" data-type="2" <?php echo(empty(get_post_meta($post_id, 'voucher_id', true)) ? 'disabled' : ''); ?>><?php _e('Flyer',$this->plugin_name);?> <span class="we_spinner"></span> </button>
                <button data-order="<?php echo $order_id; ?>" type="button" class="button acs_print_voucher_type1 has-spinner" id="acs_print_voucher_type_2" data-type="1" <?php echo(empty(get_post_meta($post_id, 'voucher_id', true)) ? 'disabled' : ''); ?>><?php _e('Sticker',$this->plugin_name);?> <span class="we_spinner"></span></button>
				<?php
			}
		});

		$jobs->columns()->populate(
			'actions', function ($column, $post_id) {
			$provider=get_post_meta($post_id,'we_voucher_job_provider',true);
			if ($provider=="acs") {
				$status = get_post_meta($post_id, 'webexpert_voucher_job_status', true);
				$order_id = get_post_meta($post_id, 'order_id', true);
				?>
                <button data-order="<?php echo $order_id; ?>" type="button" class="button acs_cancel_voucher has-spinner" data-success="<?php _e('Voucher has been cancelled', $this->plugin_name); ?>" <?php echo($status == 'we-voucher-cancelled' || $status == 'we-voucher-closed' ? 'disabled' : '') ?>><?php _e('Cancel', $this->plugin_name); ?> <span class="we_spinner"></span></button>
				<?php
			}
		});

		$jobs->icon('dashicons-tag');
		$jobs->register();
	}

	public function webexpert_action_row($actions, $post) {
		//check for your post type
		if ($post->post_type =="we_voucher_job"){
			return [];
		}
		return $actions;
	}

	public function get_job_status($post_id) {
		$status = get_post_meta($post_id, 'webexpert_voucher_job_status', true);
		switch ($status) {
			case'we-voucher-open':
				return __('Opened', $this->plugin_name);
				break;
			case 'we-voucher-cancelled':
				return __('Cancelled', $this->plugin_name);
				break;
			case 'we-voucher-closed':
				return __('Closed', $this->plugin_name);
				break;
			default:
				return __('Not initialized', $this->plugin_name);
				break;
		}
	}

	public function get_job_status_formatted($post_id) {
		$status = $this->get_job_status($post_id);
		$status_class = get_post_meta($post_id, 'webexpert_voucher_job_status', true);
		echo "<span class='webexpert-job-status " . strtolower($status_class) . "'>$status</span>";
	}

	public function jobs_metabox() {
		$screen = wc_get_container()->get( \Automattic\WooCommerce\Internal\DataStores\Orders\CustomOrdersTableController::class )->custom_orders_table_usage_is_enabled()
			? wc_get_page_screen_id( 'shop-order' )
			: 'shop_order';

		add_meta_box(
			'voucher-options-acs',
			__('ACS Voucher', $this->plugin_name),
			[$this, 'voucher_metabox_content'],
			$screen, 'side', 'core'
		);
	}

	public function acs_cod_beneficiary_info() {
		$date = sanitize_text_field($_POST['webexpert_acs_cod_date_pickup']);
		$service_url = 'https://webservices.acscourier.net/ACSRestServices/api/ACSAutoRest';

		$acsAccount = get_option('webexpert_default_acs_account');

		$body = array(
			'ACSAlias' => 'ACS_COD_Beneficiary_Info',
			'ACSInputParameters' => [
				'Company_ID' => get_option('webexpert_acs_company_id')[$acsAccount ],
				'Company_Password' => get_option('webexpert_acs_company_password')[$acsAccount ],
				'User_ID' => get_option('webexpert_acs_user_id')[$acsAccount ],
				'User_Password' => get_option('webexpert_acs_user_password')[$acsAccount ],
				"User_locals"=> 'GR',
				'COD_Payment_Date'=>date_i18n('Y-m-d',strtotime(str_replace('/','-',$date)))
			]
		);

		if (get_option('webexpert_acs_debug',null)=='1') {
			file_put_contents(wp_upload_dir()['basedir'].'/wc-logs/acs-voucher-for-woocommerce.log',PHP_EOL."=== ACS Voucher for WooCommerce ===".PHP_EOL,FILE_APPEND);
			file_put_contents(wp_upload_dir()['basedir'].'/wc-logs/acs-voucher-for-woocommerce.log',"=== COD Beneficiary info Request ===".PHP_EOL,FILE_APPEND);
			file_put_contents(wp_upload_dir()['basedir'].'/wc-logs/acs-voucher-for-woocommerce.log',json_encode($body,JSON_UNESCAPED_UNICODE).PHP_EOL,FILE_APPEND);
		}

		$request = wp_remote_post($service_url,[
			'data_format' => 'body',
			'method'      => 'POST',
			'body'    => json_encode($body),
			'headers' => array(
				"Content-Type"=> "application/json",
				"ACSApiKey"=>get_option('webexpert_acs_apikey')[$acsAccount]
			),
		]);

		if (wp_remote_retrieve_response_code($request) != 200) {
			return wp_remote_retrieve_response_code($request)." - ".wp_remote_retrieve_response_message($request);
		}

		if (is_wp_error($request)) {
			return $request->get_error_message();
		}

		$response = wp_remote_retrieve_body( $request );
		if (get_option('webexpert_acs_debug',null)=='1') {
			file_put_contents(wp_upload_dir()['basedir'].'/wc-logs/acs-voucher-for-woocommerce.log',PHP_EOL."=== ACS Voucher for WooCommerce ===".PHP_EOL,FILE_APPEND);
			file_put_contents(wp_upload_dir()['basedir'].'/wc-logs/acs-voucher-for-woocommerce.log',"=== COD Beneficiary info Response ===".PHP_EOL,FILE_APPEND);
			file_put_contents(wp_upload_dir()['basedir'].'/wc-logs/acs-voucher-for-woocommerce.log',$response.PHP_EOL,FILE_APPEND);
		}
		$obj = json_decode($response);

		if ($obj->ACSExecution_HasError) {
			return ($obj->ACSExecutionErrorMessage);
		}

		$cod_beneficiary_info=[];
		foreach ($obj->ACSOutputResponce->ACSTableOutput->Table_Data as $table_data) {
			$cod_beneficiary_info[]=$table_data;
		}

		wp_send_json(['cod_beneficiary_info'=>$cod_beneficiary_info,], 200);
	}

	public function voucher_metabox_content($post) {
		$order = ( $post instanceof WP_Post ) ? wc_get_order( $post->ID ) : $post;
		if ($order) {
			$job_id = !empty($order->get_meta('we_voucher_job_id')) ? $order->get_meta('we_voucher_job_id') : null;
			$vendor=get_post_meta($job_id, 'we_voucher_job_provider', true);
			$lock_me= ($vendor && $vendor!=="acs") ? 'disabled' : '';

			$total_weight = 0;
			$dimension_unit = get_option('woocommerce_dimension_unit');

			foreach ($order->get_items() as $item) {
				$product_variation_id = $item['variation_id'];
				if ($product_variation_id) {
					$product = wc_get_product($item['variation_id']);
				} else {
					$product = wc_get_product($item['product_id']);
				}
				if ($product) {
					$volumetric_weight=0.0;
					if(get_option('webexpert_acs_disable_dimensions_volumetric','0')!="1" && $product->get_length() && $product->get_width() && $product->get_height()) {
						$length = (wc_get_dimension(str_replace(",", ".", $product->get_length()), 'cm', $dimension_unit));
						$width = (wc_get_dimension(str_replace(",", ".", $product->get_width()), 'cm', $dimension_unit));
						$height = (wc_get_dimension(str_replace(",", ".", $product->get_height()), 'cm', $dimension_unit));
						$volumetric_weight = ($length*$width*$height) / 5000 * $item->get_quantity();
					}

					$weight=0.0;
					if($product->get_weight()) {
						$weight = floatval(str_replace(",", ".", $product->get_weight()) * $item->get_quantity());
					}

					if ($volumetric_weight>$weight) {
						$total_weight+=$volumetric_weight;
					}else {
						$total_weight+=$weight;
					}
				}
			}
			$total_weight=apply_filters('webexpert_acs_custom_order_total_weight',$total_weight,$order);
			?>
            <div class="webexpert-field-group">
                <div class="webexpert-field">
					<?php _e("Voucher", $this->plugin_name); ?>: <?php echo($job_id && $vendor=="acs" ? get_post_meta($job_id, 'voucher_id', true) : '-'); ?><br>
                </div>
				<?php
				if ($vendor=="acs") {
					$sub_vouchers = get_post_meta($job_id, 'sub_voucher_id', true);
					if ($sub_vouchers) {
						echo '<div class="webexpert-field">';
						echo __("Sub Voucher", $this->plugin_name) . ":" . implode("<br> " . __("Sub Voucher", $this->plugin_name) . ": ", $sub_vouchers);
						echo '</div>';
					}
				}
				?>
                <div class="webexpert-field">
					<?php _e('Status',$this->plugin_name);?> <?php $vendor=="acs" ? $this->get_job_status_formatted($job_id) : _e('-'); ?>
                </div>
                <div class="webexpert-field">
		            <?php _e('Track & Trace',$this->plugin_name);?>:
		            <?php
		            if ( $vendor == "acs" && is_string( $order->get_meta( 'voucher_delivery_status' ) ) ) {
			            echo( $order->get_meta( 'voucher_delivery_status' ) ? $order->get_meta( 'voucher_delivery_status' ) : '-' );
		            } else if ( $vendor == "acs" && is_object( $order->get_meta( 'voucher_delivery_status' ) ) ) {
			            echo $order->get_meta( 'voucher_delivery_status' )->web_status_title;
		            }
		            ?>
                </div>
            </div>
            <div class="webexpert-field-group">
                <div class="webexpert-field">
                    <div class="webexpert-field-label">
                        <input  <?php echo $lock_me;?>  type="hidden" name="acs_order_id_for_voucher" id="acs_order_id_for_voucher" value="<?php echo $order->get_id(); ?>">
                        <label for="acs_parcels"><?php echo __('Parcels', $this->plugin_name); ?></label>
                    </div>
                    <div class="webexpert-field-input">
                        <input  <?php echo $lock_me;?>  id="acs_parcels" value="<?php echo($order->get_meta('acs_parcels') ? $order->get_meta('acs_parcels') : 1); ?>" type="number" name="acs_parcels">
                    </div>
                </div>
                <div class="webexpert-field">
                    <div class="webexpert-field-label">
                        <label for="acs_weight"><?php echo __('Weight (in kg)', $this->plugin_name); ?></label>
                    </div>
                    <div class="webexpert-field-input">
						<?php
						$field_weight=get_option('webexpert_acs_default_weight');
						if($order->get_meta('acs_weight')>0) {
							$field_weight=$order->get_meta('acs_weight');
						}else {
							$weight = wc_get_weight($total_weight,'kg');
							if ($weight>0) {
								$field_weight = $weight;
							}
						}
						?>
                        <input  <?php echo $lock_me;?>  id="acs_weight" value="<?php echo $field_weight; ?>" type="text" name="acs_weight">
                    </div>
                </div>
                <div class="webexpert-field">
                    <div class="webexpert-field-label">
                        <label for="acs_pickup_date"><?php echo __('Pickup Date', $this->plugin_name); ?></label>
                    </div>
                    <div class="webexpert-field-input">
                        <input  <?php echo $lock_me;?>  id="acs_pickup_date" value="<?php echo($order->get_meta( 'acs_pickup_date') ? $order->get_meta('acs_pickup_date') : date_i18n('Y-m-d')); ?>" type="text" name="acs_pickup_date">
                    </div>
                </div>
				<?php if ($order->get_payment_method() == "cod") : ?>
                    <div class="webexpert-field">
                        <div class="webexpert-field-label">
                            <label for="acs_cod"><?php echo __('Cash on delivery price', $this->plugin_name); ?></label>
                        </div>
                        <div class="webexpert-field-input">
                            <input  <?php echo $lock_me;?>  id="acs_cod" type="text" placeholder="0.0" value="<?php echo($order->get_meta('acs_cod') ? number_format(floatval(str_replace(",",".",$order->get_meta('acs_cod'))), 2) : $order->get_total()); ?>" name="acs_cod">
                        </div>
                    </div>
				<?php else: ?>
                    <input type="hidden" name="acs_cod" value="0">
				<?php endif; ?>
                <div class="webexpert-field">
                    <div class="webexpert-field-label">
                        <label for="acs_special_cases"><?php echo __('Services', $this->plugin_name); ?></label>
                    </div>
                    <div class="webexpert-field-input">
						<?php $services = $order->get_meta( 'acs_special_cases') ? $order->get_meta( 'acs_special_cases') : []; ?>
						<?php $services = apply_filters('webexpert_acs_custom_services_actions',$services,$order); ?>
                        <select  <?php echo $lock_me;?>  id="acs_special_cases" name="acs_special_cases" multiple>
                            <option value="INS" <?php echo in_array('INS', $services) ? 'selected' : ''; ?>><?php _e('Insurance', $this->plugin_name); ?></option>
                            <option value="SAT" <?php echo in_array('SAT', $services) ? 'selected' : ''; ?>><?php _e('Saturday Delivery', $this->plugin_name); ?></option>
                            <option value="COD" <?php echo in_array('COD', $services) || $order->get_payment_method() == "cod" ? 'selected' : ''; ?>><?php _e('COD', $this->plugin_name); ?></option>
                            <option <?php if($order->get_meta('_webexpert_acs_inaccessible') == "yes") { echo 'selected'; } ?> value="REM" <?php echo in_array('REM', $services) ? 'selected' : ''; ?>><?php _e('Inaccessible Area', $this->plugin_name); ?></option>
                            <option value="REC" <?php echo in_array('REC', $services) ? 'selected' : ''; ?>><?php _e('Reception Delivery', $this->plugin_name); ?></option>
                            <option value="RDO" <?php echo in_array('RDO', $services) ? 'selected' : ''; ?>><?php _e('Return Shipment Service', $this->plugin_name); ?></option>
                            <option value="CEC" <?php echo in_array('CEC', $services) ? 'selected' : ''; ?>><?php _e('Cyprus Economy', $this->plugin_name); ?></option>
                            <option value="P2P" <?php echo in_array('P2P', $services) ? 'selected' : ''; ?>><?php _e('Point to Point (Cyprus)', $this->plugin_name); ?></option>
                            <option value="D2P" <?php echo in_array('D2P', $services) ? 'selected' : ''; ?>><?php _e('Door to Point (Cyprus)', $this->plugin_name); ?></option>
                            <option value="P2D" <?php echo in_array('P2D', $services) ? 'selected' : ''; ?>><?php _e('Point to Door (Cyprus)', $this->plugin_name); ?></option>
                        </select>
                    </div>
                </div>
                <div class="webexpert-field" id="acs_insurance_amount_container" style="<?php echo ($order->get_meta( 'acs_insurance_amount') && $order->get_meta('acs_insurance_amount')>0 ? 'display:block;' : ''); ?>">
                    <div class="webexpert-field-label">
                        <label for="acs_insurance_amount"><?php echo __('Insurance Amount', $this->plugin_name); ?></label>
                    </div>
                    <div class="webexpert-field-input">
                        <input  <?php echo $lock_me;?>  id="acs_insurance_amount" value="<?php echo($order->get_meta( 'acs_insurance_amount') ? $order->get_meta( 'acs_insurance_amount') : ''); ?>" type="text" name="acs_insurance_amount">
                    </div>
                </div>
                <div class="webexpert-field">
                    <div class="webexpert-field-label">
                        <label for="acs_comments"><?php _e('Comments', $this->plugin_name); ?></label>
                    </div>
                    <div class="webexpert-field-input">
                        <textarea  <?php echo $lock_me;?>  name="acs_comments" id="acs_comments" cols="22" rows="5"><?php echo !empty($order->get_meta( 'acs_comments')) ? $order->get_meta( 'acs_comments') : $order->get_customer_note(); ?></textarea>
                    </div>
                </div>

                <div class="webexpert-field">
                    <div class="webexpert-field-label"><label for=""> <?php _e("Select your account" , $this->plugin_name) ?>  </label></div>
					<?php
					$existingAccounts = 0;
					foreach(get_option('webexpert_acs_sender') as $index => $opt) {
						if(strlen(trim($opt)) > 0) {
							$existingAccounts++;
						}
					}
					?>
                    <div class="webexpert-field-input">
                        <select id="acs_voucher_account" name="acs_voucher_account">
							<?php
							for($i=0;$i<$existingAccounts;$i++) {
								$selected=$order->get_meta('we_acs_account');
								if (empty($selected)) {
									$selected=get_option('webexpert_default_acs_account',0);
								}
								?>
                                <option <?php selected($selected,$i); ?> value="<?php echo $i ?>"><?php echo "(". ($i+1) . ") - "  . get_option('webexpert_acs_user_id')[$i] ?></option>
							<?php } ?>
                        </select>
                    </div>
                </div>
                <p>
                    <button <?php echo $lock_me;?>  data-order="<?php echo $order->get_id(); ?>" type="button" class="button has-spinner" id="acs_create_voucher" data-error="<?php _e('There was an error issuing the voucher.',$this->plugin_name);?>" data-success="<?php _e('Vouchers have been created!',$this->plugin_name);?>" <?php echo($job_id !== null ? 'disabled' : ''); ?>><?php _e('Create voucher',
							$this->plugin_name); ?> <span class="we_spinner"></span> </button>
            </div>


            <div class="webexpert-field-group">
                <input type="hidden" id="voucher_type_Sticker" value="<?php __('Sticker', $this->plugin_name) ?>">
                <div class="webexpert-field">
                    <div class="webexpert-field-label">
                        <label><input  <?php echo $lock_me;?> type="radio" name="acs_print_voucher_type" value="2" <?php echo (get_option('webexpert_acs_default_print_size','0')=="2" ? 'checked' : '');  ?>><?php echo __('Flyer',$this->plugin_name); ?></label>
                    </div>
                    <div class="webexpert-field-label">
                        <label><input <?php echo $lock_me;?> type="radio" name="acs_print_voucher_type" value="1" <?php echo (get_option('webexpert_acs_default_print_size','0')=="1" ? 'checked' : '');  ?>><?php echo __('Sticker',$this->plugin_name); ?></label>
                    </div>
                    <div class="webexpert-field-label">
                        <button <?php echo $lock_me;?> data-order="<?php echo $order->get_id(); ?>" type="button" class="button has-spinner" id="acs_print_voucher" <?php echo($job_id === null ? 'disabled' : '') ?>>
							<?php _e('Print voucher', $this->plugin_name); ?> <span class="we_spinner"></span></button>
                    </div>
                </div>
            </div>

            <div class="webexpert-field-group">
                <div class="webexpert-field">
                    <button <?php echo $lock_me;?> data-order="<?php echo $order->get_id(); ?>" type="button" class="button has-spinner acs_cancel_voucher" data-success="<?php _e('Voucher has been cancelled',$this->plugin_name);?>" id="acs_cancel_voucher" <?php echo($job_id === null ? 'disabled' : '') ?>>
						<?php _e('Cancel voucher', $this->plugin_name); ?> <span class="we_spinner"></span></button><br>
                    <small><a style="display:inline-block;margin-top:6px" href="#reset" data-order="<?php echo $order->get_id(); ?>" id="reset-job" data-confirm="<?php _e('Are you sure you want to reset voucher process for this order?',$this->plugin_name);?>"><?php _e('Reset job',$this->plugin_name);?></a></small>
                </div>
            </div>

			<?php
		}
	}

	function acs_reset_voucher() {
		$order_id=sanitize_text_field($_POST['order_id']);
		$order=wc_get_order($order_id);
		if ($order) {
			$order->delete_meta_data( '_webexpert_order_tracking_carrier');
			$order->delete_meta_data( 'we_voucher_job_id');
			$order->delete_meta_data( 'acs_parcels');
			$order->delete_meta_data( 'acs_cod');
			$order->delete_meta_data( 'acs_weight');
			$order->delete_meta_data( 'acs_pickup_date');
			$order->delete_meta_data( 'acs_insurance_amount');
			$order->delete_meta_data( 'acs_comments');
			$order->delete_meta_data( 'acs_special_cases');
			$order->delete_meta_data( '_shipping_tracking_number');
			$order->delete_meta_data( 'voucher_delivery_status');
			$order->save();
		}

		wp_send_json(['success'=>1]);
	}

	function form($post_id) {
		global $typenow;

		if('we_voucher_job' !== $post_id){
			return;
		}

		$order_id = get_post_meta($post_id, 'order_id', true);
		?>
        <button data-order="<?php echo $order_id; ?>" type="button" name="close_pending_voucher" data-success="<?php _e('All pending vouchers are closed',$this->plugin_name);?>" class="button acs_close_pending_voucher has-spinner">
			<?php _e('Issue pickup list (ACS Courier)', $this->plugin_name); ?> <span class="we_spinner"></span> </button>
		<?php
		if ($typenow == 'we_voucher_job') {

			$from = (isset($_GET['mishaDateFrom']) && $_GET['mishaDateFrom']) ? $_GET['mishaDateFrom'] : '';
			$to = (isset($_GET['mishaDateTo']) && $_GET['mishaDateTo']) ? $_GET['mishaDateTo'] : '';
			echo '<label for="mishaDateFrom" style="margin-left:20px">'.__('Filter jobs:',$this->plugin_name).'</label>
            <input type="text" name="mishaDateFrom" id="mishaDateFrom" placeholder="'.__('Date From',$this->plugin_name).'" value="' . esc_attr($from) . '"  autocomplete="off"><input autocomplete="off" type="text" name="mishaDateTo" placeholder="'.__('Date To',$this->plugin_name).'" value="' . esc_attr($to) . '" />';
		}
	}

	function filterquery($admin_query) {
		global $pagenow;

		if (
			is_admin()
			&& $admin_query->is_main_query()
			&& in_array($pagenow, array('edit.php', 'upload.php'))
			&& (!empty($_GET['mishaDateFrom']) || !empty($_GET['mishaDateTo']))
		) {

			$admin_query->set(
				'date_query',
				array(
					'after'     => sanitize_text_field($_GET['mishaDateFrom']),
					'before'    => sanitize_text_field($_GET['mishaDateTo']),
					'inclusive' => true,
					'column'    => 'post_date'
				)
			);
		}

		return $admin_query;
	}

	function remove_date_drop() {
		$screen = get_current_screen();

		if ('we_voucher_job' == $screen->post_type) {
			add_filter('months_dropdown_results', '__return_empty_array');
		}
	}

	function register_my_bulk_actions($bulk_actions) {
		unset($bulk_actions['edit']);
		unset($bulk_actions['trash']);
		$bulk_actions['print_jobs_acs'] = __('Print jobs (ACS Courier)',$this->plugin_name);
		$bulk_actions['cancel_jobs_acs'] = __('Cancel jobs (ACS Courier)',$this->plugin_name);
		$bulk_actions['trash'] = __('Delete jobs',$this->plugin_name);
		return $bulk_actions;
	}

	function webexpert_acs_courier_bulk_action_notices() {
		if ( ! empty( $_REQUEST['acs_jobs_cancelled'] ) ) {
			echo '<div id="message" class="updated notice notice-success is-dismissible">
			<p>'.__('Selected jobs were cancelled',$this->plugin_name).'</p>
		</div>';
		}
		else if ( !empty ( $_REQUEST['webexpert_not_all_acs_accounts_same'] ) ) {
			echo '<div class="notice notice-error is-dismissible">
		        <p> ' . __("You should select jobs that have been created by the same ACS account", $this->plugin_name) . ' </p>
		        </div>
		    ';
		}
	}

    function register_my_bulk_actions_handler($redirect, $doaction, $post_ids) {
        $redirect = remove_query_arg( array( 'elta_jobs_cancelled','acs_jobs_cancelled','geniki_taxydromiki_jobs_cancelled','geniki_taxydromiki_jobs_print' ), $redirect );
        if ($doaction == 'print_jobs_acs') {
            $print_type=get_option('webexpert_acs_default_print_size','1');
            $vouchers_to_print=[];
            $job_accounts = [];
            foreach ($post_ids as $job_id) {
                $voucher_id = get_post_meta($job_id, 'voucher_id', true) ? get_post_meta($job_id, 'voucher_id', true) : null;
                if ($voucher_id) {
                    if( strlen(strval(get_post_meta($job_id , 'we_acs_account' , true))) > 0 ) {
                        $job_accounts[] = strval(get_post_meta($job_id , 'we_acs_account' , true));
                    }
                    $vouchers_to_print[] = $voucher_id;
                }
            }

            if(count( array_unique( $job_accounts ) ) != 1) {
                return add_query_arg('webexpert_not_all_acs_accounts_same', count($post_ids), $redirect);
            }

            $defaultAcsAccount = reset($job_accounts) ?? get_option('webexpert_default_acs_account');
            $service_url = 'https://webservices.acscourier.net/ACSRestServices/api/ACSAutoRest';
            $body = array(
                'ACSAlias' => 'ACS_Get_Printvoucher_URL',
                'ACSInputParameters' => [
                    'Company_ID' => get_option('webexpert_acs_company_id')[$defaultAcsAccount],
                    'Company_Password' => get_option('webexpert_acs_company_password')[$defaultAcsAccount],
                    'User_ID' => get_option('webexpert_acs_user_id')[$defaultAcsAccount],
                    'User_Password' => get_option('webexpert_acs_user_password')[$defaultAcsAccount],
                    'Voucher_No'=>implode(",",$vouchers_to_print),
                    'Language'=>'GR',
                    'Print_Type'=>$print_type,
                    'Start_Position'=>1,
                    'Voucher_mode'=>null
                ]
            );

            if (get_option('webexpert_acs_debug',null)=='1') {
                $logger = wc_get_logger();
                $logger->info( wc_print_r( json_encode($body,JSON_UNESCAPED_UNICODE), true ), array( 'source' => 'asc-for-woocommerce' ) );
            }

            $request = wp_remote_post($service_url,[
                'data_format' => 'body',
                'method'      => 'POST',
                'body'    => json_encode($body),
                'headers' => array(
                        "Content-Type"=> "application/json",
                        "ACSApiKey"=>get_option('webexpert_acs_apikey')[$defaultAcsAccount]
                ),
            ]);

            $response = wp_remote_retrieve_body( $request );

            if (get_option('webexpert_acs_debug',null)=='1') {
                $logger = wc_get_logger();
                $logger->info( wc_print_r( $response, true ), array( 'source' => 'asc-for-woocommerce' ) );
            }
            $response_obj = json_decode($response);
            if (isset($response_obj->ACSOutputResponce->ACSValueOutput[0]->PrintVoucher_URL) && !empty($response_obj->ACSOutputResponce->ACSValueOutput[0]->PrintVoucher_URL)) {
                $voucher_url = $response_obj->ACSOutputResponce->ACSValueOutput[0]->PrintVoucher_URL;
                wp_redirect($voucher_url);
                exit;
            }
        }

        if ($doaction == 'cancel_jobs_acs') {
            foreach ($post_ids as $job_id) {
                $vendor=get_post_meta($job_id, 'we_voucher_job_provider', true);
                if ($vendor == "acs") {
                    $order_id = get_post_meta($job_id, 'order_id', true) ? get_post_meta($job_id, 'order_id', true) : null;
                    update_post_meta($job_id, 'webexpert_voucher_job_status', 'we-voucher-cancelled');
                    delete_post_meta($job_id, 'voucher_id');
                    delete_post_meta($job_id, 'sub_voucher_id');
                    delete_post_meta($job_id, 'acs_company_id');
                    delete_post_meta($job_id, 'acs_company_password');
                    delete_post_meta($job_id, 'acs_user_id');
                    delete_post_meta($job_id, 'acs_user_password');
                    delete_post_meta($job_id, 'acs_sender');
                    $order=wc_get_order($order_id);
                    if ($order) {
                        $order->delete_meta_data( '_webexpert_order_tracking_carrier');
                        $order->delete_meta_data( 'we_voucher_job_id');
                        $order->delete_meta_data( 'acs_parcels');
                        $order->delete_meta_data( 'acs_cod');
                        $order->delete_meta_data( 'acs_weight');
                        $order->delete_meta_data( 'acs_pickup_date');
                        $order->delete_meta_data( 'acs_insurance_amount');
                        $order->delete_meta_data( 'acs_comments');
                        $order->delete_meta_data( 'acs_special_cases');
                        $order->delete_meta_data( '_shipping_tracking_number');
                        $order->delete_meta_data( 'voucher_delivery_status');
                        $order->save();
                    }
                }
                $redirect = add_query_arg('acs_jobs_cancelled', count($post_ids), $redirect);
            }
        }

        return $redirect;
    }

	function acs_print_voucher() {
		$order_id = isset($_POST['order_id']) ? sanitize_text_field($_POST['order_id']) : null;
		$order=wc_get_order($order_id);
		if ($order) {
			$print_type = isset($_POST['print_type']) ? sanitize_text_field($_POST['print_type']) : '1';
			$job_id = !empty($order->get_meta('we_voucher_job_id')) ? $order->get_meta('we_voucher_job_id') : null;

			if ($job_id) {
				$voucher_id = get_post_meta($job_id, 'voucher_id', true) ? get_post_meta($job_id, 'voucher_id', true) : null;

				if ($voucher_id) {
					$acsAccount = get_post_meta($job_id , 'we_acs_account', true) ? get_post_meta($job_id, 'we_acs_account', true) : get_option('webexpert_default_acs_account');

					try {
						$qStr = "";
						$qStrNew = "";
						$sub_vouchers = get_post_meta($job_id, 'sub_voucher_id', true);
						if ($sub_vouchers) {
							foreach ($sub_vouchers as $sub_voucher) {
								$qStr .= "|$sub_voucher";
								$qStrNew .= ",$sub_voucher";
							}
						}

						if (apply_filters('webexpert_acs_old_print_way',false)) {
							wp_send_json("https://acs-eud2.acscourier.net/Eshops/GetVoucher.aspx?MainID=" . urlencode(get_option('webexpert_acs_company_id')[$acsAccount]) . "&MainPass=" . urlencode(get_option('webexpert_acs_company_password')[$acsAccount]) . "&UserID=" . urlencode(get_option('webexpert_acs_user_id')[$acsAccount]) . "&UserPass=" . urlencode(get_option('webexpert_acs_user_password')[$acsAccount]) . "&voucherno=" . $voucher_id . "$qStr&PrintType={$print_type}&StartFromNumber=1", 200);
						}else {
							$service_url = 'https://webservices.acscourier.net/ACSRestServices/api/ACSAutoRest';
							$body = array(
								'ACSAlias' => 'ACS_Print_Voucher',
								'ACSInputParameters' => [
									'Company_ID' => get_option('webexpert_acs_company_id')[$acsAccount],
									'Company_Password' => get_option('webexpert_acs_company_password')[$acsAccount],
									'User_ID' => get_option('webexpert_acs_user_id')[$acsAccount],
									'User_Password' => get_option('webexpert_acs_user_password')[$acsAccount],
									'Voucher_No'=>$voucher_id.$qStrNew,
									'Print_Type'=>$print_type,
									'Start_Position'=>1
								]
							);

							if (get_option('webexpert_acs_debug',null)=='1') {
								file_put_contents(wp_upload_dir()['basedir'].'/wc-logs/acs-voucher-for-woocommerce.log',PHP_EOL."=== ACS Voucher for WooCommerce ===".PHP_EOL,FILE_APPEND);
								file_put_contents(wp_upload_dir()['basedir'].'/wc-logs/acs-voucher-for-woocommerce.log',"=== Print Voucher request (#{$order->get_id()}) ===".PHP_EOL,FILE_APPEND);
								file_put_contents(wp_upload_dir()['basedir'].'/wc-logs/acs-voucher-for-woocommerce.log',json_encode($body,JSON_UNESCAPED_UNICODE).PHP_EOL,FILE_APPEND);
							}

							$request = wp_remote_post($service_url,[
								'data_format' => 'body',
								'method'      => 'POST',
								'body'    => json_encode($body),
								'headers' => array(
									"Content-Type"=> "application/json",
									"ACSApiKey"=>get_option('webexpert_acs_apikey')[$acsAccount]
								),
							]);

							$response = wp_remote_retrieve_body( $request );

							if (get_option('webexpert_acs_debug',null)=='1') {
								file_put_contents(wp_upload_dir()['basedir'].'/wc-logs/acs-voucher-for-woocommerce.log',PHP_EOL."=== ACS Voucher for WooCommerce ===".PHP_EOL,FILE_APPEND);
								file_put_contents(wp_upload_dir()['basedir'].'/wc-logs/acs-voucher-for-woocommerce.log',"=== Print Voucher response (#{$order->get_id()}) ===".PHP_EOL,FILE_APPEND);
								file_put_contents(wp_upload_dir()['basedir'].'/wc-logs/acs-voucher-for-woocommerce.log',$response.PHP_EOL,FILE_APPEND);
							}

							$obj = json_decode($response);
							if ($obj->ACSExecution_HasError) {
								wp_send_json_error($obj->ACSExecutionErrorMessage, 200);
								return false;
							}
							$file=wp_upload_bits("$voucher_id.pdf", null, base64_decode($obj->ACSOutputResponce->ACSValueOutput[0]->ACSObjectOutput));
							wp_send_json_success($file['url']);
						}
						exit;
					} catch (Exception $fault) {
						wp_send_json_error($fault);
					}
				}else {
					wp_send_json_error(__("Voucher number could not be found!",$this->plugin_name));
				}
			}else {
				wp_send_json_error(__("Job could not be found!",$this->plugin_name));
			}
		}
	}

	function acs_cancel_voucher() {
		$order_id = isset($_POST['order_id']) ? sanitize_text_field($_POST['order_id']) : null;
		$order=wc_get_order($order_id);
		if ($order) {
			$job_id = !empty($order->get_meta('we_voucher_job_id')) ? $order->get_meta('we_voucher_job_id') : null;
			$voucher_id = get_post_meta($job_id, 'voucher_id', true) ? get_post_meta($job_id, 'voucher_id', true) : null;

			if ($voucher_id) {
				$service_url = 'https://webservices.acscourier.net/ACSRestServices/api/ACSAutoRest';
				$acsAccount = get_post_meta($job_id, 'we_acs_account', true) ? get_post_meta($job_id, 'we_acs_account', true) : get_option('webexpert_default_acs_account');
				$body = array(
					'ACSAlias' => 'ACS_Delete_Voucher',
					'ACSInputParameters' => [
						'Company_ID' => get_option('webexpert_acs_company_id')[$acsAccount],
						'Company_Password' => get_option('webexpert_acs_company_password')[$acsAccount],
						'User_ID' => get_option('webexpert_acs_user_id')[$acsAccount],
						'User_Password' => get_option('webexpert_acs_user_password')[$acsAccount],
						'Voucher_No'=>$voucher_id
					]
				);

				if (get_option('webexpert_acs_debug',null)=='1') {
					file_put_contents(wp_upload_dir()['basedir'].'/wc-logs/acs-voucher-for-woocommerce.log',PHP_EOL."=== ACS Voucher for WooCommerce ===".PHP_EOL,FILE_APPEND);
					file_put_contents(wp_upload_dir()['basedir'].'/wc-logs/acs-voucher-for-woocommerce.log',"=== Delete Voucher request (#{$order->get_id()}) ===".PHP_EOL,FILE_APPEND);
					file_put_contents(wp_upload_dir()['basedir'].'/wc-logs/acs-voucher-for-woocommerce.log',json_encode($body,JSON_UNESCAPED_UNICODE).PHP_EOL,FILE_APPEND);
				}

				$request = wp_remote_post($service_url,[
					'data_format' => 'body',
					'method'      => 'POST',
					'body'    => json_encode($body),
					'headers' => array(
						"Content-Type"=> "application/json",
						"ACSApiKey"=>get_option('webexpert_acs_apikey')[$acsAccount]
					),
				]);

				if (wp_remote_retrieve_response_code($request) != 200) {
					return wp_remote_retrieve_response_code($request)." - ".wp_remote_retrieve_response_message($request);
				}

				if (is_wp_error($request)) {
					return $request->get_error_message();
				}

				$response = wp_remote_retrieve_body( $request );

				if (get_option('webexpert_acs_debug',null)=='1') {
					file_put_contents(wp_upload_dir()['basedir'].'/wc-logs/acs-voucher-for-woocommerce.log',PHP_EOL."=== ACS Voucher for WooCommerce ===".PHP_EOL,FILE_APPEND);
					file_put_contents(wp_upload_dir()['basedir'].'/wc-logs/acs-voucher-for-woocommerce.log',"=== Delete Voucher response (#{$order->get_id()}) ===".PHP_EOL,FILE_APPEND);
					file_put_contents(wp_upload_dir()['basedir'].'/wc-logs/acs-voucher-for-woocommerce.log',$response.PHP_EOL,FILE_APPEND);
				}

				$obj = json_decode($response);

				if ($obj->ACSExecution_HasError) {
					wp_send_json($obj->ACSExecutionErrorMessage, 200);
					return false;
				}

				update_post_meta($job_id, 'webexpert_voucher_job_status', 'we-voucher-cancelled');
				delete_post_meta($job_id, 'voucher_id');
				delete_post_meta($job_id, 'sub_voucher_id');
				$order->delete_meta_data( '_webexpert_order_tracking_carrier');
				$order->delete_meta_data('we_voucher_job_id');
				$order->delete_meta_data( 'we_voucher_job_id');
				$order->delete_meta_data( 'acs_parcels');
				$order->delete_meta_data( 'acs_cod');
				$order->delete_meta_data( 'acs_weight');
				$order->delete_meta_data( 'acs_pickup_date');
				$order->delete_meta_data( 'acs_insurance_amount');
				$order->delete_meta_data( 'acs_comments');
				$order->delete_meta_data( 'acs_special_cases');
				$order->delete_meta_data( '_shipping_tracking_number');
				$order->delete_meta_data( 'voucher_delivery_status');
				$order->save();
				wp_send_json('success',200);
			}
		}
	}

	function webexpert_cancel_voucher_acs() {
		$voucher_id = sanitize_text_field($_POST['webexpert_cancel_voucher_acs']);
		if ($voucher_id) {

			//Get the job post type
			$jobs = get_posts([
				'post_type'  => 'we_voucher_job',
				'meta_query' => array(
					array(
						'key' => 'voucher_id',
						'value' => $voucher_id,
						'compare' => '=',
					)
				)
			]);

			foreach ($jobs as $job) {
				$order_id = get_post_meta($job->ID, 'order_id', true) ? get_post_meta($job->ID, 'order_id', true) : null;
				$order=wc_get_order($order_id);
				if ($order) {
					$acsAccount = !empty(get_post_meta($job->ID , 'we_acs_account' , true)) ? get_post_meta($job->ID , 'we_acs_account' , true) : get_option('webexpert_default_acs_account');

					$service_url = 'https://webservices.acscourier.net/ACSRestServices/api/ACSAutoRest';
					$body = array(
						'ACSAlias' => 'ACS_Delete_Voucher',
						'ACSInputParameters' => [
							'Company_ID' => get_option('webexpert_acs_company_id')[$acsAccount],
							'Company_Password' => get_option('webexpert_acs_company_password')[$acsAccount],
							'User_ID' => get_option('webexpert_acs_user_id')[$acsAccount],
							'User_Password' => get_option('webexpert_acs_user_password')[$acsAccount],
							'Voucher_No'=>$voucher_id
						]
					);

					if (get_option('webexpert_acs_debug',null)=='1') {
						file_put_contents(wp_upload_dir()['basedir'].'/wc-logs/acs-voucher-for-woocommerce.log',PHP_EOL."=== ACS Voucher for WooCommerce ===".PHP_EOL,FILE_APPEND);
						file_put_contents(wp_upload_dir()['basedir'].'/wc-logs/acs-voucher-for-woocommerce.log',"=== Delete Voucher Request (#{$order->get_id()}) ===".PHP_EOL,FILE_APPEND);
						file_put_contents(wp_upload_dir()['basedir'].'/wc-logs/acs-voucher-for-woocommerce.log',json_encode($body,JSON_UNESCAPED_UNICODE).PHP_EOL,FILE_APPEND);
					}

					$request = wp_remote_post($service_url,[
						'data_format' => 'body',
						'method'      => 'POST',
						'body'    => json_encode($body),
						'headers' => array(
							"Content-Type"=> "application/json",
							"ACSApiKey"=>get_option('webexpert_acs_apikey')[$acsAccount]
						),
					]);

					if (wp_remote_retrieve_response_code($request) != 200) {
						return wp_remote_retrieve_response_code($request)." - ".wp_remote_retrieve_response_message($request);
					}

					if (is_wp_error($request)) {
						return $request->get_error_message();
					}

					$response = wp_remote_retrieve_body( $request );

					if (get_option('webexpert_acs_debug',null)=='1') {
						file_put_contents(wp_upload_dir()['basedir'].'/wc-logs/acs-voucher-for-woocommerce.log',PHP_EOL."=== ACS Voucher for WooCommerce ===".PHP_EOL,FILE_APPEND);
						file_put_contents(wp_upload_dir()['basedir'].'/wc-logs/acs-voucher-for-woocommerce.log',"=== Delete Voucher response (#{$order->get_id()}) ===".PHP_EOL,FILE_APPEND);
						file_put_contents(wp_upload_dir()['basedir'].'/wc-logs/acs-voucher-for-woocommerce.log',$response.PHP_EOL,FILE_APPEND);
					}
					$obj = json_decode($response);

					if ($obj->ACSExecution_HasError) {
						wp_send_json($obj->ACSExecutionErrorMessage, 200);
						return;
					}

					update_post_meta($job->ID, 'webexpert_voucher_job_status', 'we-voucher-cancelled');
					delete_post_meta($job->ID, 'voucher_id');
					delete_post_meta($job->ID, 'sub_voucher_id');
					$order->delete_meta_data( '_webexpert_order_tracking_carrier');
					$order->delete_meta_data( 'we_voucher_job_id');
					$order->delete_meta_data( 'acs_parcels');
					$order->delete_meta_data( 'acs_cod');
					$order->delete_meta_data( 'acs_weight');
					$order->delete_meta_data( 'acs_pickup_date');
					$order->delete_meta_data( 'acs_insurance_amount');
					$order->delete_meta_data( 'acs_comments');
					$order->delete_meta_data( 'acs_special_cases');
					$order->delete_meta_data( '_shipping_tracking_number');
					$order->delete_meta_data( 'voucher_delivery_status');
					$order->save();
				}
				wp_send_json('success', 200);
			}
		}
	}

	function acs_courier_voucher_auto_issue($order_id) {
		$order=wc_get_order($order_id);
		if ($order) {
			$job_id = !empty($order->get_meta('we_voucher_job_id')) ? $order->get_meta('we_voucher_job_id') : null;
			$voucher_no = get_post_meta($job_id, 'voucher_id', true) ? get_post_meta($job_id, 'voucher_id', true) : null;
			if (get_option('webexpert_acs_auto_issue_upon_complete',null)=='1' && empty($voucher_no)) {
				$disable_on_gateways=[];
				if (!empty(get_option('webexpert_acs_disable_on_payments'))) {
					$disable_on_gateways=get_option('webexpert_acs_disable_on_payments',[]);
				}

				$disable_on_shipping=[];
				if (!empty(get_option('webexpert_acs_disable_on_shipping'))) {
					$disable_on_shipping=get_option('webexpert_acs_disable_on_shipping',[]);
				}

				if (in_array($order->get_payment_method(),$disable_on_gateways)) {
					return false;
				}

				$shipping=$order->get_items( 'shipping' );
				foreach ($shipping as $s) {
					$shipping="{$s->get_method_id()}:{$s->get_instance_id()}";
				}

				if (in_array($shipping,$disable_on_shipping)) {
					return false;
				}

				if (apply_filters('webexpert_acs_disable_on_custom_hook',false,$order)) {
					return false;
				}

				$total_weight = 0;
				foreach( $order->get_items() as $item_id => $product_item ){
					$product = $product_item->get_product();
					if (is_numeric($product->get_weight())) {
						$total_weight+=$product->get_weight();
					}
				}

				$total_weight=wc_get_weight($total_weight,'kg');
				$parcels = $order->get_meta('acs_parcels') ? $order->get_meta( 'acs_parcels') : 1;
				$services = $order->get_meta( 'acs_special_cases') ? $order->get_meta( 'acs_special_cases', true) : [];
				$comments = $order->get_meta( 'acs_comments') ? $order->get_meta( 'acs_comments') : apply_filters('webexpert_acs_voucher_customer_note',$order->get_customer_note());
				$comments = apply_filters('webexpert_acs_voucher_custom_comments',$comments,$order_id);
				$weight = $total_weight>0 ? $total_weight : get_option('webexpert_acs_default_weight');
				$pickup_date = $order->get_meta( 'acs_pickup_date') ? $order->get_meta('acs_pickup_date') : date_i18n('Y-m-d');

				if( $order->get_meta('_webexpert_acs_inaccessible') == "yes" && !in_array('REM', $services) ) {
					$services[] = 'REM';
				}

				$cod='0';
				if ($order->get_payment_method() == "cod") :
					$cod = $order->get_meta($order_id, 'acs_cod', true) ? number_format(floatval(str_replace(",",".",$order->get_meta('acs_cod'))), 2) : $order->get_total();
					if (!in_array('COD', $services, true)) {
						array_push($services, 'COD');
					}
				endif;
				$this->acs_create_voucher_process(['order_id'=>$order_id,'cod'=>$cod,'comments'=>$comments,'weight'=>$weight,'services'=>$services,'parcels'=>$parcels,'pickup_date'=>$pickup_date]);
			}
		}
	}

	function webexpert_acs_find_pickup_lists() {
		$date = sanitize_text_field($_POST['webexpert_acs_date_pickup']);
		$service_url = 'https://webservices.acscourier.net/ACSRestServices/api/ACSAutoRest';

		$acsAccount = get_option('webexpert_default_acs_account');

		$body = array(
			'ACSAlias' => 'ACS_Get_Pickup_Lists',
			'ACSInputParameters' => [
				'Company_ID' => get_option('webexpert_acs_company_id')[$acsAccount ],
				'Company_Password' => get_option('webexpert_acs_company_password')[$acsAccount ],
				'User_ID' => get_option('webexpert_acs_user_id')[$acsAccount ],
				'User_Password' => get_option('webexpert_acs_user_password')[$acsAccount ],
				'Pickup_Date'=>date_i18n('Y-m-d',strtotime(str_replace('/','-',$date)))
			]
		);

		if (get_option('webexpert_acs_debug',null)=='1') {
			file_put_contents(wp_upload_dir()['basedir'].'/wc-logs/acs-voucher-for-woocommerce.log',PHP_EOL."=== ACS Voucher for WooCommerce ===".PHP_EOL,FILE_APPEND);
			file_put_contents(wp_upload_dir()['basedir'].'/wc-logs/acs-voucher-for-woocommerce.log',"=== Get Pickup Lists Request ===".PHP_EOL,FILE_APPEND);
			file_put_contents(wp_upload_dir()['basedir'].'/wc-logs/acs-voucher-for-woocommerce.log',json_encode($body,JSON_UNESCAPED_UNICODE).PHP_EOL,FILE_APPEND);
		}

		$request = wp_remote_post($service_url,[
			'data_format' => 'body',
			'method'      => 'POST',
			'body'    => json_encode($body),
			'headers' => array(
				"Content-Type"=> "application/json",
				"ACSApiKey"=>get_option('webexpert_acs_apikey')[$acsAccount]
			),
		]);

		if (wp_remote_retrieve_response_code($request) != 200) {
			return wp_remote_retrieve_response_code($request)." - ".wp_remote_retrieve_response_message($request);
		}

		if (is_wp_error($request)) {
			return $request->get_error_message();
		}

		$response = wp_remote_retrieve_body( $request );
		if (get_option('webexpert_acs_debug',null)=='1') {
			file_put_contents(wp_upload_dir()['basedir'].'/wc-logs/acs-voucher-for-woocommerce.log',PHP_EOL."=== ACS Voucher for WooCommerce ===".PHP_EOL,FILE_APPEND);
			file_put_contents(wp_upload_dir()['basedir'].'/wc-logs/acs-voucher-for-woocommerce.log',"=== Get Pickup Lists Response ===".PHP_EOL,FILE_APPEND);
			file_put_contents(wp_upload_dir()['basedir'].'/wc-logs/acs-voucher-for-woocommerce.log',$response.PHP_EOL,FILE_APPEND);
		}
		$obj = json_decode($response);

		if (!empty($obj->ACSOutputResponce->ACSValueOutput[0]->Error_Message)) {
			if ($obj->ACSOutputResponce->ACSValueOutput[0]->Unprinted_Found>=1) {
				$error_msg=$obj->ACSOutputResponce->ACSValueOutput[0]->Error_Message.": [ ";
				foreach ($obj->ACSOutputResponce->ACSTableOutput->Table_Data as $table_data) {
					$error_msg.="{$table_data->Unprinted_Vouchers} ";
				}
				$error_msg.="]";
				wp_send_json($error_msg,200);
			}else {
				wp_send_json($obj->ACSOutputResponce->ACSValueOutput[0]->Error_Message,200);
			}
			return;
		}

		$lists=[];
        foreach ($obj->ACSOutputResponce->ACSTableOutput->Table_Data as $table_data) {
            array_push($lists,['list'=>$table_data->PickupList_No,'massnumber'=>$table_data->PickupList_No,'url'=>"https://acs-eud2.acscourier.net/Eshops/getlist.aspx?MainID=" . urlencode(get_option('webexpert_acs_company_id')[$acsAccount]) . "&MainPass=" . urlencode(get_option('webexpert_acs_company_password')[$acsAccount]) . "&UserID=" . urlencode(get_option('webexpert_acs_user_id')[$acsAccount]) . "&UserPass=" . urlencode(get_option('webexpert_acs_user_password')[$acsAccount]) .
            "&MassNumber=$table_data->PickupList_No&DateParal=".date_i18n('Y-m-d',strtotime(str_replace('/','-',$date)))]);
        }

		wp_send_json(['lists'=>$lists,], 200);
	}

	function webexpert_print_voucher_acs() {
		$voucher_id = sanitize_text_field($_POST['webexpert_print_voucher_acs']);
		$print_type = sanitize_text_field($_POST['acs_print_voucher_type']);
		if ($voucher_id) {

			$query = new WP_Query([
				'post_type'  => 'we_voucher_job',
				'meta_query' => array(
					array(
						'key' => 'voucher_id',
						'value' => $voucher_id,
						'compare' => '=',
					)
				)
			]);

			$posts = $query->get_posts();
			$jobItem = count($posts) ? $posts[0] : null;
			$jobItem = $jobItem ? get_post($jobItem) : null;
			$acsAccount = $jobItem ? get_post_meta($jobItem->ID , 'we_acs_account' , true) : get_option('webexpert_default_acs_account');

			try {
				$qStr = "";
				if (apply_filters('webexpert_acs_old_print_way',false)) {
					wp_send_json( "https://acs-eud2.acscourier.net/Eshops/GetVoucher.aspx?MainID=" . urlencode( get_option( 'webexpert_acs_company_id' )[ $acsAccount ] ) . "&MainPass=" . urlencode( get_option( 'webexpert_acs_company_password' )[ $acsAccount ] ) . "&UserID=" . urlencode( get_option( 'webexpert_acs_user_id' )[ $acsAccount ] ) . "&UserPass=" . urlencode( get_option( 'webexpert_acs_user_password' )[ $acsAccount ] ) . "&voucherno=" . $voucher_id . "$qStr&PrintType={$print_type}&StartFromNumber=1", 200 );
				}else {
					$service_url = 'https://webservices.acscourier.net/ACSRestServices/api/ACSAutoRest';
					$body = array(
						'ACSAlias' => 'ACS_Print_Voucher',
						'ACSInputParameters' => [
							'Company_ID' => get_option('webexpert_acs_company_id')[$acsAccount],
							'Company_Password' => get_option('webexpert_acs_company_password')[$acsAccount],
							'User_ID' => get_option('webexpert_acs_user_id')[$acsAccount],
							'User_Password' => get_option('webexpert_acs_user_password')[$acsAccount],
							'Voucher_No'=>$voucher_id,
							'Print_Type'=>$print_type,
							'Start_Position'=>1
						]
					);

					if (get_option('webexpert_acs_debug',null)=='1') {
						file_put_contents(wp_upload_dir()['basedir'].'/wc-logs/acs-voucher-for-woocommerce.log',PHP_EOL."=== ACS Voucher for WooCommerce ===".PHP_EOL,FILE_APPEND);
						file_put_contents(wp_upload_dir()['basedir'].'/wc-logs/acs-voucher-for-woocommerce.log',"=== Print Voucher request (#$voucher_id}) ===".PHP_EOL,FILE_APPEND);
						file_put_contents(wp_upload_dir()['basedir'].'/wc-logs/acs-voucher-for-woocommerce.log',json_encode($body,JSON_UNESCAPED_UNICODE).PHP_EOL,FILE_APPEND);
					}

					$request = wp_remote_post($service_url,[
						'data_format' => 'body',
						'method'      => 'POST',
						'body'    => json_encode($body),
						'headers' => array(
							"Content-Type"=> "application/json",
							"ACSApiKey"=>get_option('webexpert_acs_apikey')[$acsAccount]
						),
					]);

					$response = wp_remote_retrieve_body( $request );

					if (get_option('webexpert_acs_debug',null)=='1') {
						file_put_contents(wp_upload_dir()['basedir'].'/wc-logs/acs-voucher-for-woocommerce.log',PHP_EOL."=== ACS Voucher for WooCommerce ===".PHP_EOL,FILE_APPEND);
						file_put_contents(wp_upload_dir()['basedir'].'/wc-logs/acs-voucher-for-woocommerce.log',"=== Print Voucher response (#{$voucher_id}) ===".PHP_EOL,FILE_APPEND);
						file_put_contents(wp_upload_dir()['basedir'].'/wc-logs/acs-voucher-for-woocommerce.log',$response.PHP_EOL,FILE_APPEND);
					}

					$obj = json_decode($response);

					if ($obj->ACSExecution_HasError) {
						wp_send_json_error($obj->ACSExecutionErrorMessage, 200);
						return false;
					}
					$file=wp_upload_bits("$voucher_id.pdf", null, base64_decode($obj->ACSOutputResponce->ACSValueOutput[0]->ACSObjectOutput));
					wp_send_json_success($file['url']);
				}
				exit;

			} catch (Exception $fault) {
				wp_send_json_error($fault);
			}
		}
	}

	function acs_close_voucher() {
		$acsAccount = get_option('webexpert_default_acs_account');
		$service_url = 'https://webservices.acscourier.net/ACSRestServices/api/ACSAutoRest';
		$body = array(
			'ACSAlias' => 'ACS_Issue_Pickup_List',
			'ACSInputParameters' => [
				'Company_ID' => get_option('webexpert_acs_company_id')[$acsAccount],
				'Company_Password' => get_option('webexpert_acs_company_password')[$acsAccount],
				'User_ID' => get_option('webexpert_acs_user_id')[$acsAccount],
				'User_Password' => get_option('webexpert_acs_user_password')[$acsAccount],
				'Pickup_Date'=>date_i18n('Y-m-d')
			]
		);

		if (get_option('webexpert_acs_debug',null)=='1') {
			file_put_contents(wp_upload_dir()['basedir'].'/wc-logs/acs-voucher-for-woocommerce.log',PHP_EOL."=== ACS Voucher for WooCommerce ===".PHP_EOL,FILE_APPEND);
			file_put_contents(wp_upload_dir()['basedir'].'/wc-logs/acs-voucher-for-woocommerce.log',"=== Issue Pickup Lists Request ===".PHP_EOL,FILE_APPEND);
			file_put_contents(wp_upload_dir()['basedir'].'/wc-logs/acs-voucher-for-woocommerce.log',json_encode($body,JSON_UNESCAPED_UNICODE).PHP_EOL,FILE_APPEND);
		}

		$request = wp_remote_post($service_url,[
			'data_format' => 'body',
			'method'      => 'POST',
			'body'    => json_encode($body),
			'headers' => array(
				"Content-Type"=> "application/json",
				"ACSApiKey"=>get_option('webexpert_acs_apikey')[$acsAccount]
			),
		]);

		if (wp_remote_retrieve_response_code($request) != 200) {
			return wp_remote_retrieve_response_code($request)." - ".wp_remote_retrieve_response_message($request);
		}

		if (is_wp_error($request)) {
			return $request->get_error_message();
		}

		$response = wp_remote_retrieve_body( $request );
		if (get_option('webexpert_acs_debug',null)=='1') {
			file_put_contents(wp_upload_dir()['basedir'].'/wc-logs/acs-voucher-for-woocommerce.log',PHP_EOL."=== ACS Voucher for WooCommerce ===".PHP_EOL,FILE_APPEND);
			file_put_contents(wp_upload_dir()['basedir'].'/wc-logs/acs-voucher-for-woocommerce.log',"=== Issue Pickup Lists Response ===".PHP_EOL,FILE_APPEND);
			file_put_contents(wp_upload_dir()['basedir'].'/wc-logs/acs-voucher-for-woocommerce.log',$response.PHP_EOL,FILE_APPEND);
		}
		$obj = json_decode($response);

		if (!empty($obj->ACSOutputResponce->ACSValueOutput[0]->Error_Message)) {
			if ($obj->ACSOutputResponce->ACSValueOutput[0]->Unprinted_Found>=1) {
				$error_msg=$obj->ACSOutputResponce->ACSValueOutput[0]->Error_Message.": [ ";
				foreach ($obj->ACSOutputResponce->ACSTableOutput->Table_Data as $table_data) {
					$error_msg.="{$table_data->Unprinted_Vouchers} ";
				}
				$error_msg.="]";
				wp_send_json($error_msg,200);
			}else {
				wp_send_json($obj->ACSOutputResponce->ACSValueOutput[0]->Error_Message,200);
			}
			return;
		}

		$pickupList=null;
		if (!empty($obj->ACSOutputResponce->ACSValueOutput[0]->PickupList_No))
			$pickupList=$obj->ACSOutputResponce->ACSValueOutput[0]->PickupList_No;

		$posts = new WP_Query(
			array(
				'post_type'  => 'we_voucher_job',
				'meta_query' => array(
					'relation' => 'AND',
					array(
						'key' => 'we_voucher_job_provider',
						'value' => 'acs',
					),
					array(
						'key' => 'webexpert_voucher_job_status',
						'value' => 'we-voucher-open',
					),
				)
			)
		);

		$posts = $posts->get_posts();

		foreach ($posts as $post) {
			update_post_meta($post->ID, 'webexpert_voucher_job_status', 'we-voucher-closed');
		}

		if ($pickupList) {
			if (apply_filters('webexpert_acs_old_print_way',false)) {
				$url="https://acs-eud2.acscourier.net/Eshops/getlist.aspx?MainID=" . urlencode(get_option('webexpert_acs_company_id')[$acsAccount]) . "&MainPass=" . urlencode(get_option('webexpert_acs_company_password')[$acsAccount]) . "&UserID=" . urlencode(get_option('webexpert_acs_user_id')[$acsAccount]) . "&UserPass=" . urlencode(get_option('webexpert_acs_user_password')[$acsAccount]) . "&MassNumber=$pickupList&DateParal=".date_i18n('Y-m-d');
				wp_send_json(['url'=>$url], 200);
			}else {
				$service_url = 'https://webservices.acscourier.net/ACSRestServices/api/ACSAutoRest';
				$body = array(
					'ACSAlias' => 'ACS_Print_Pickup_List',
					'ACSInputParameters' => [
						'Company_ID' => get_option('webexpert_acs_company_id')[$acsAccount],
						'Company_Password' => get_option('webexpert_acs_company_password')[$acsAccount],
						'User_ID' => get_option('webexpert_acs_user_id')[$acsAccount],
						'User_Password' => get_option('webexpert_acs_user_password')[$acsAccount],
						'Language'=>'GR',
						'Mass_Number'=>$pickupList,
						'Pickup_Date'=>date_i18n('Y-m-d'),
					]
				);

				if (get_option('webexpert_acs_debug',null)=='1') {
					file_put_contents(wp_upload_dir()['basedir'].'/wc-logs/acs-voucher-for-woocommerce.log',PHP_EOL."=== ACS Voucher for WooCommerce ===".PHP_EOL,FILE_APPEND);
					file_put_contents(wp_upload_dir()['basedir'].'/wc-logs/acs-voucher-for-woocommerce.log',"=== Print Pickup List Voucher request ===".PHP_EOL,FILE_APPEND);
					file_put_contents(wp_upload_dir()['basedir'].'/wc-logs/acs-voucher-for-woocommerce.log',json_encode($body,JSON_UNESCAPED_UNICODE).PHP_EOL,FILE_APPEND);
				}

				$request = wp_remote_post($service_url,[
					'data_format' => 'body',
					'method'      => 'POST',
					'body'    => json_encode($body),
					'headers' => array(
						"Content-Type"=> "application/json",
						"ACSApiKey"=>get_option('webexpert_acs_apikey')[$acsAccount]
					),
				]);

				$response = wp_remote_retrieve_body( $request );

				if (get_option('webexpert_acs_debug',null)=='1') {
					file_put_contents(wp_upload_dir()['basedir'].'/wc-logs/acs-voucher-for-woocommerce.log',PHP_EOL."=== ACS Voucher for WooCommerce ===".PHP_EOL,FILE_APPEND);
					file_put_contents(wp_upload_dir()['basedir'].'/wc-logs/acs-voucher-for-woocommerce.log',"=== Print Pickup List response ===".PHP_EOL,FILE_APPEND);
					file_put_contents(wp_upload_dir()['basedir'].'/wc-logs/acs-voucher-for-woocommerce.log',$response.PHP_EOL,FILE_APPEND);
				}

				$obj = json_decode($response);

				if ($obj->ACSExecution_HasError) {
					wp_send_json($obj->ACSExecutionErrorMessage, 200);
					return false;
				}
				$file=wp_upload_bits("$pickupList.pdf", null, base64_decode($obj->ACSOutputResponce->ACSValueOutput[0]->ACSObjectOutput->PDFData));
				wp_send_json(['url'=>$file['url']], 200);
			}
		}
		wp_send_json('success', 200);
	}

    function acs_print_pickup_list() {
        $pickupList=$_POST['pickupList'];
        if ($pickupList) {
            // TODO - needs more accounts
            $acsAccount = get_option('webexpert_default_acs_account');
            $service_url = 'https://webservices.acscourier.net/ACSRestServices/api/ACSAutoRest';
            $body = array(
                'ACSAlias' => 'ACS_Print_Pickup_List',
                'ACSInputParameters' => [
                    'Company_ID' => get_option('webexpert_acs_company_id')[$acsAccount],
                    'Company_Password' => get_option('webexpert_acs_company_password')[$acsAccount],
                    'User_ID' => get_option('webexpert_acs_user_id')[$acsAccount],
                    'User_Password' => get_option('webexpert_acs_user_password')[$acsAccount],
                    'Language'=>'GR',
                    'Mass_Number'=>$pickupList,
                    'Pickup_Date'=>date_i18n('Y-m-d'),
                ]
            );

            if (get_option('webexpert_acs_debug',null)=='1') {
                $logger = wc_get_logger();
                $logger->info( wc_print_r( json_encode($body,JSON_UNESCAPED_UNICODE), true ), array( 'source' => 'asc-for-woocommerce' ) );
            }

            $request = wp_remote_post($service_url,[
                    'data_format' => 'body',
                    'method'      => 'POST',
                    'body'    => json_encode($body),
                    'headers' => array(
                            "Content-Type"=> "application/json",
                            "ACSApiKey"=>get_option('webexpert_acs_apikey')[$acsAccount]
                    ),
            ]);

            $response = wp_remote_retrieve_body( $request );

            if (get_option('webexpert_acs_debug',null)=='1') {
                $logger = wc_get_logger();
                $logger->info( wc_print_r( $response, true ), array( 'source' => 'asc-for-woocommerce' ) );
            }

            $obj = json_decode($response);

            if ($obj->ACSExecution_HasError) {
                wp_send_json($obj->ACSExecutionErrorMessage, 200);
                return false;
            }
            $file=wp_upload_bits("$pickupList.pdf", null, base64_decode($obj->ACSOutputResponce->ACSValueOutput[0]->ACSObjectOutput->PDFData));
            wp_send_json(['url'=>$file['url']], 200);
        }
    }

	function acs_create_voucher() {
		$order_id = isset($_POST['order_id']) ? sanitize_text_field($_POST['order_id']) : null;
		$order=wc_get_order($order_id);
		$cod = isset($_POST['cod']) ? sanitize_text_field($_POST['cod']) : 0.0;
		$comments = isset($_POST['comments']) ? sanitize_text_field($_POST['comments']) : apply_filters('webexpert_acs_voucher_customer_note',$order->get_customer_note());;
		$acs_account = isset($_POST['acs_account']) ? sanitize_text_field($_POST['acs_account']) : -1;
		$weight = isset($_POST['weight']) ? sanitize_text_field($_POST['weight']) : get_option('webexpert_acs_default_weight');;
		$services = isset($_POST['services']) ? (array)$_POST['services'] : [];

		$parcels = isset($_POST['parcels']) ? sanitize_text_field($_POST['parcels']) : 1;
		$pickup_date = isset($_POST['pickup_date']) ? sanitize_text_field($_POST['pickup_date']) : date_i18n('Y-m-d');
		$insurance_amount = isset($_POST['insurance_amount']) ? sanitize_text_field($_POST['insurance_amount']) : null;
		wp_send_json($this->acs_create_voucher_process(['order_id'=>$order_id, 'acs_account' => $acs_account, 'cod'=>$cod,'comments'=>$comments,'weight'=>$weight,'services'=>$services,'parcels'=>$parcels,'pickup_date'=>$pickup_date, 'insurance_amount'=>$insurance_amount]));
	}

	function acs_create_voucher_process($args) {
		$order_id = isset($args['order_id']) ? sanitize_text_field($args['order_id']) : null;
		$order=wc_get_order($order_id);
		$acs_account = isset($args['acs_account']) ? $args['acs_account'] : sanitize_text_field( get_option('webexpert_default_acs_account') );
		$cod = isset($args['cod']) ? sanitize_text_field($args['cod']) : 0.0;
		$comments = isset($args['comments']) ? sanitize_text_field($args['comments']) : apply_filters('webexpert_acs_voucher_customer_note',$order->get_customer_note());;
		$comments = apply_filters('webexpert_acs_voucher_custom_comments',$comments,$order_id);
		$weight = apply_filters('webexpert_acs_for_woocommerce_custom_weight',isset($args['weight']) ? sanitize_text_field($args['weight']) : get_option('webexpert_acs_default_weight'));
		$services = isset($args['services']) ? (array)$args['services'] : [];
		$parcels = isset($args['parcels']) ? sanitize_text_field($args['parcels']) : 1;
		$pickup_date = isset($args['pickup_date']) ? sanitize_text_field($args['pickup_date']) : date_i18n('Y-m-d');
		$insurance_amount = isset($args['insurance_amount']) ? sanitize_text_field($args['insurance_amount']) : null;
		try {
			$first_name = $order->get_shipping_first_name() ? $order->get_shipping_first_name() : $order->get_billing_first_name();
			$last_name = $order->get_shipping_last_name() ? $order->get_shipping_last_name() : $order->get_billing_last_name();
			$service_url = 'https://webservices.acscourier.net/ACSRestServices/api/ACSAutoRest';

			$shipping_address=$order->get_shipping_address_1()." ".$order->get_shipping_address_2();
			$billing_address=$order->get_billing_address_1()." ".$order->get_billing_address_2();

			$phone = $order->get_shipping_phone() ? $order->get_shipping_phone() : $order->get_billing_phone();
			$billing_cellphone = !empty($order->get_meta('billing_cellphone')) ? $order->get_meta('billing_cellphone') : $order->get_billing_phone();
			$cellphone = !empty($order->get_meta('shipping_phone')) ? $order->get_meta('shipping_phone') : $billing_cellphone;

			$body = array(
				'ACSAlias' => 'ACS_Create_Voucher',
				'ACSInputParameters' => [
					'Company_ID' => get_option('webexpert_acs_company_id')[$acs_account],
					'Company_Password' => get_option('webexpert_acs_company_password')[$acs_account],
					'User_ID' => get_option('webexpert_acs_user_id')[$acs_account],
					'User_Password' => get_option('webexpert_acs_user_password')[$acs_account],
					'Pickup_Date' => $pickup_date,
					'Sender' => get_option('webexpert_acs_sender')[$acs_account],
					'Recipient_Name' => "{$first_name} {$last_name}",
					'Recipient_Address' => mb_substr(!empty($shipping_address) ? $shipping_address : $billing_address,0,42),
					'Recipient_Address_Number' => null,
					'Recipient_Zipcode' => $order->get_shipping_postcode() ? $order->get_shipping_postcode() : $order->get_billing_postcode(),
					'Recipient_Region' => $order->get_shipping_city() ? $order->get_shipping_city() : $order->get_billing_city(),
					'Recipient_Phone' => apply_filters('webexpert_acs_voucher_custom_phone_field',preg_replace("/[^0-9]/", "",$phone),$order),
					'Recipient_Cell_phone' => apply_filters('webexpert_acs_voucher_custom_cellphone_field',preg_replace("/[^0-9]/", "",$cellphone),$order),
					'Recipient_Country' => $order->get_shipping_country() ? $order->get_shipping_country() : $order->get_billing_country(),
					'Acs_Station_Branch_Destination' => 1,
					'Billing_Code' => get_option('webexpert_acs_billing_code')[$acs_account],
					'Charge_Type' => 2,
					'Item_Quantity' => $parcels,
					'Weight' => floatval(str_replace(",",".",$weight)),
					'Delivery_Notes'=>$comments,
					'Recipient_Email'=>$order->get_billing_email()
				]
			);

			if ($cod) {
				$body ['ACSInputParameters'] ['Cod_Ammount' ] = floatval(str_replace(",",".",$cod));
				$body ['ACSInputParameters'] ['Cod_Payment_Way' ] = 0;
				if (!in_array('COD', $services)) {
					array_push($services,'COD');
				}
			}

			if ($insurance_amount && $insurance_amount>0) {
				$body ['ACSInputParameters'] ['Insurance_Ammount' ] = $insurance_amount;
				if (!in_array('INS', $services)) {
					array_push($services,'INS');
				}
			}

			if (!empty($services)) {
				$body ['ACSInputParameters'] ['Acs_Delivery_Products' ] = implode(',',$services);
			}

			if (get_option('webexpert_acs_debug',null)=='1') {
				file_put_contents(wp_upload_dir()['basedir'].'/wc-logs/acs-voucher-for-woocommerce.log',PHP_EOL."=== ACS Voucher for WooCommerce ===".PHP_EOL,FILE_APPEND);
				file_put_contents(wp_upload_dir()['basedir'].'/wc-logs/acs-voucher-for-woocommerce.log',"=== Create Voucher Request (#{$order->get_id()}) ===".PHP_EOL,FILE_APPEND);
				file_put_contents(wp_upload_dir()['basedir'].'/wc-logs/acs-voucher-for-woocommerce.log',json_encode($body,JSON_UNESCAPED_UNICODE).PHP_EOL,FILE_APPEND);
			}

			$request = wp_remote_post($service_url,[
				'data_format' => 'body',
				'method'      => 'POST',
				'body'    => json_encode($body),
				'headers' => array(
					"Content-Type"=> "application/json",
					"ACSApiKey"=>get_option('webexpert_acs_apikey')[$acs_account]
				),
			]);

			if (wp_remote_retrieve_response_code($request) != 200) {
				return wp_remote_retrieve_response_code($request)." - ".wp_remote_retrieve_response_message($request);
			}

			if (is_wp_error($request)) {
				return $request->get_error_message();
			}

			$response = wp_remote_retrieve_body( $request );

			if (get_option('webexpert_acs_debug',null)=='1') {
				file_put_contents(wp_upload_dir()['basedir'].'/wc-logs/acs-voucher-for-woocommerce.log',PHP_EOL."=== ACS Voucher for WooCommerce ===".PHP_EOL,FILE_APPEND);
				file_put_contents(wp_upload_dir()['basedir'].'/wc-logs/acs-voucher-for-woocommerce.log',"=== Create Voucher Response (#{$order->get_id()}) ===".PHP_EOL,FILE_APPEND);
				file_put_contents(wp_upload_dir()['basedir'].'/wc-logs/acs-voucher-for-woocommerce.log',$response.PHP_EOL,FILE_APPEND);
			}
			$obj = json_decode($response);

			if (!empty($obj->ACSOutputResponce->ACSValueOutput[0]->Error_Message)) {
				return $obj->ACSOutputResponce->ACSValueOutput[0]->Error_Message;
			}

			if (!empty($obj->ACSExecution_HasError)) {
				return $obj->ACSExecutionErrorMessage;
			}

			$voucherNo = $obj->ACSOutputResponce->ACSValueOutput[0]->Voucher_No;

			$post_id = wp_insert_post(
				[
					'post_title'  => __("Job for order",$this->plugin_name)." #{$order->get_id()}",
					'post_status' => "publish",
					'post_type'   => "we_voucher_job"
				]
			);

			if ($post_id === 0 || is_wp_error($post_id)) {
				return "Error creating Job cpt";
			}

			$order->update_meta_data('we_voucher_job_id', $post_id);
			update_post_meta($post_id, 'we_voucher_job_provider', 'acs');
			if($acs_account != -1) {
				$order->update_meta_data( 'we_acs_account', $acs_account);
				update_post_meta($post_id, 'we_acs_account', $acs_account);
				if( isset( get_option('webexpert_acs_user_id')[$acs_account] ) )
					update_post_meta($post_id, 'we_acs_account_user_id', sanitize_text_field(  get_option('webexpert_acs_user_id')[$acs_account] ) );
			}

			$order->update_meta_data( '_webexpert_order_tracking_carrier', 'acs');
			$order->update_meta_data( 'acs_parcels', $parcels);
			$order->update_meta_data( 'acs_cod', $cod);
			$order->update_meta_data( 'acs_weight', $weight);
			$order->update_meta_data( 'acs_pickup_date',$pickup_date);
			$order->update_meta_data( 'acs_insurance_amount',$insurance_amount);
			$order->update_meta_data( 'acs_comments', $comments);
			$order->update_meta_data( 'acs_special_cases', $services);
			$order->update_meta_data( '_shipping_tracking_number', $voucherNo);
			$order->save();
			update_post_meta($post_id, 'voucher_id', $voucherNo);
			update_post_meta($post_id, 'order_id', $order->get_id());
			update_post_meta($post_id, 'webexpert_voucher_job_status', 'we-voucher-open');
			do_action('webexpert_acs_voucher_created',$order,$post_id);
			return 'success';

		} catch (Exception $fault) {
			wp_send_json($fault);
		}
	}

	function run_hourly_event() {
		$args = array(
			'date_created' => '>' . ( time() - (10 * DAY_IN_SECONDS )),
			'status' => 'completed',
			'limit' => -1,
			'meta_key'     => 'we_voucher_job_id',
		);
		$orders = wc_get_orders( $args );

		foreach ($orders as $order) {
			$job_id = !empty($order->get_meta('we_voucher_job_id')) ? $order->get_meta('we_voucher_job_id') : null;
			$vendor=get_post_meta($job_id, 'we_voucher_job_provider', true);
			if ($vendor=='acs') {
				if (!empty($order->get_meta('voucher_delivery_status')) && stripos($order->get_meta('voucher_delivery_status'), "παρεδόθη") !== false) {
					continue;
				}
				$track=$this->webexpert_acs_track($order->get_id(),null,true);
				if (isset($track)) {
					$order->update_meta_data('voucher_delivery_status', $track);
				} else {
					$order->update_meta_data('voucher_delivery_status', '-');
				}
				$order->save();
			}
		}
	}

	function run_daily_event() {
		try {
			$service_url = 'https://webservices.acscourier.net/ACSRestServices/api/ACSAutoRest';
			$body = array(
				'ACSAlias' => 'ACS_Issue_Pickup_List',
				'ACSInputParameters' => [
					'Company_ID' => get_option('webexpert_acs_company_id')[get_option('webexpert_default_acs_account')],
					'Company_Password' => get_option('webexpert_acs_company_password')[get_option('webexpert_default_acs_account')],
					'User_ID' => get_option('webexpert_acs_user_id')[get_option('webexpert_default_acs_account')],
					'User_Password' => get_option('webexpert_acs_user_password')[get_option('webexpert_default_acs_account')],
					'Pickup_Date' => date_i18n('Y-m-d'),
				]
			);

			if (get_option('webexpert_acs_debug',null)=='1') {
				file_put_contents(wp_upload_dir()['basedir'].'/wc-logs/acs-voucher-for-woocommerce.log',PHP_EOL."=== ACS Voucher for WooCommerce ===".PHP_EOL,FILE_APPEND);
				file_put_contents(wp_upload_dir()['basedir'].'/wc-logs/acs-voucher-for-woocommerce.log',"=== Issue Pickup Lists Request ===".PHP_EOL,FILE_APPEND);
				file_put_contents(wp_upload_dir()['basedir'].'/wc-logs/acs-voucher-for-woocommerce.log',json_encode($body,JSON_UNESCAPED_UNICODE).PHP_EOL,FILE_APPEND);
			}

			$request = wp_remote_post($service_url,[
				'data_format' => 'body',
				'method'      => 'POST',
				'body'    => json_encode($body),
				'headers' => array(
					"Content-Type"=> "application/json",
					"ACSApiKey"=>get_option('webexpert_acs_apikey')[get_option('webexpert_default_acs_account')]
				),
			]);

			if (wp_remote_retrieve_response_code($request) != 200) {
				return wp_remote_retrieve_response_code($request)." - ".wp_remote_retrieve_response_message($request);
			}

			if (is_wp_error($request)) {
				return $request->get_error_message();
			}

			$response = wp_remote_retrieve_body( $request );

			if (get_option('webexpert_acs_debug',null)=='1') {
				file_put_contents(wp_upload_dir()['basedir'].'/wc-logs/acs-voucher-for-woocommerce.log',PHP_EOL."=== ACS Voucher for WooCommerce ===".PHP_EOL,FILE_APPEND);
				file_put_contents(wp_upload_dir()['basedir'].'/wc-logs/acs-voucher-for-woocommerce.log',"=== Issue Pickup Lists Response ===".PHP_EOL,FILE_APPEND);
				file_put_contents(wp_upload_dir()['basedir'].'/wc-logs/acs-voucher-for-woocommerce.log',$response.PHP_EOL,FILE_APPEND);
			}
			$obj = json_decode($response);

			if (!empty($obj->ACSOutputResponce->ACSValueOutput[0]->Error_Message)) {
				wp_send_json("Error issuing pickup list", 503);
				return;
			}

			$posts = new WP_Query(
				array(
					'post_type'  => 'we_voucher_job',
					'meta_key'   => "webexpert_voucher_job_status",
					'meta_value' => "we-voucher-open",
					'date_query' => array(
						array(
							'year' => date('y'),
							'month' => date('m'),
							'day' => date('d'),
						),
					),
				)
			);

			$jobs = $posts->get_posts();

			foreach ($jobs as $job) {
				$vendor=get_post_meta($job->ID, 'we_voucher_job_provider', true);
				if ($vendor=="acs") {
					update_post_meta($job->ID, 'webexpert_voucher_job_status', 'we-voucher-closed');
				}
			}

			return true;
		} catch (Exception $fault) {
			wp_send_json($fault, 503);
		}
	}

	function webexpert_acs_track($order_id,$voucher_no,$summary=false) {
		$arr=[];
		if ($order_id) {
			$order = wc_get_order($order_id);
			if ($order) {
				$job_id = !empty($order->get_meta('we_voucher_job_id')) ? $order->get_meta('we_voucher_job_id') : null;
				$voucher_no = get_post_meta($job_id, 'voucher_id', true) ? get_post_meta($job_id, 'voucher_id', true) : null;

				if (empty($voucher_no)) {
					$voucher_no = apply_filters('webexpert_acs_custom_voucher_no', $order->get_meta('_shipping_tracking_number'), $order_id);
				}

				if ($voucher_no) {
					$query = new WP_Query([
						'post_type' => 'we_voucher_job',
						'fields' => 'ids',
						'meta_query' => array(
							array(
								'key' => 'voucher_id',
								'value' => $voucher_no,
								'compare' => '=',
							)
						)
					]);

					$jobs = $query->get_posts();
					$acsAccount = get_option('webexpert_default_acs_account');
					foreach ($jobs as $job_id) {
						$acsAccount = get_post_meta($job_id, 'we_acs_account', true) ? get_post_meta($job_id, 'we_acs_account', true) : $acsAccount;
					}
					if ($summary) {
						$body = array(
							'ACSAlias' => 'ACS_Trackingsummary',
							'ACSInputParameters' => [
								'Company_ID' => get_option('webexpert_acs_company_id')[$acsAccount],
								'Company_Password' => get_option('webexpert_acs_company_password')[$acsAccount],
								'User_ID' => get_option('webexpert_acs_user_id')[$acsAccount],
								'User_Password' => get_option('webexpert_acs_user_password')[$acsAccount],
								'Voucher_No' => $voucher_no,
							]
						);
					} else {
						$body = array(
							'ACSAlias' => 'ACS_TrackingDetails',
							'ACSInputParameters' => [
								'Company_ID' => get_option('webexpert_acs_company_id')[$acsAccount],
								'Company_Password' => get_option('webexpert_acs_company_password')[$acsAccount],
								'User_ID' => get_option('webexpert_acs_user_id')[$acsAccount],
								'User_Password' => get_option('webexpert_acs_user_password')[$acsAccount],
								'Voucher_No' => $voucher_no,
							]
						);
					}

					if (get_option('webexpert_acs_debug',null)=='1') {
						file_put_contents(wp_upload_dir()['basedir'].'/wc-logs/acs-voucher-for-woocommerce.log',PHP_EOL."=== ACS Voucher for WooCommerce ===".PHP_EOL,FILE_APPEND);
						file_put_contents(wp_upload_dir()['basedir'].'/wc-logs/acs-voucher-for-woocommerce.log',"=== Tracking Request ===".PHP_EOL,FILE_APPEND);
						file_put_contents(wp_upload_dir()['basedir'].'/wc-logs/acs-voucher-for-woocommerce.log',json_encode($body,JSON_UNESCAPED_UNICODE).PHP_EOL,FILE_APPEND);
					}

					$service_url = 'https://webservices.acscourier.net/ACSRestServices/api/ACSAutoRest';
					$request = wp_remote_post($service_url, [
						'data_format' => 'body',
						'method' => 'POST',
						'body' => json_encode($body),
						'headers' => array(
							"Content-Type" => "application/json",
							"ACSApiKey" => get_option('webexpert_acs_apikey')[$acsAccount]
						),
					]);

					if (wp_remote_retrieve_response_code($request) != 200) {
						return wp_remote_retrieve_response_code($request) . " - " . wp_remote_retrieve_response_message($request);
					}

					if (is_wp_error($request)) {
						return $request->get_error_message();
					}

					$response = wp_remote_retrieve_body($request);

					if (get_option('webexpert_acs_debug',null)=='1') {
						file_put_contents(wp_upload_dir()['basedir'].'/wc-logs/acs-voucher-for-woocommerce.log',PHP_EOL."=== ACS Voucher for WooCommerce ===".PHP_EOL,FILE_APPEND);
						file_put_contents(wp_upload_dir()['basedir'].'/wc-logs/acs-voucher-for-woocommerce.log',"=== Tracking Response ===".PHP_EOL,FILE_APPEND);
						file_put_contents(wp_upload_dir()['basedir'].'/wc-logs/acs-voucher-for-woocommerce.log',$response.PHP_EOL,FILE_APPEND);
					}
					$obj = json_decode($response);

					if (!empty($obj->ACSOutputResponce->ACSValueOutput[0]->Error_Message)) {
						return $obj->ACSOutputResponce->ACSValueOutput[0]->Error_Message;
					}

					if (!empty($obj->ACSExecution_HasError) && $obj->ACSExecution_HasError == true) {
						return $obj->ACSExecutionErrorMessage;
					}

					if (!empty($obj->ACSOutputResponce->ACSValueOutput[0]->Error_Message)) {
						return $obj->ACSOutputResponce->ACSValueOutput[0]->Error_Message;
					}

					if ($summary) {
						if (!empty($obj->ACSOutputResponce->ACSTableOutput->Table_Data[0])) {
							return $obj->ACSOutputResponce->ACSTableOutput->Table_Data[0]->delivery_info;
						}
					}

					foreach ($obj->ACSOutputResponce->ACSTableOutput->Table_Data as $table_data) {
						array_push($arr, $table_data);
					}

					return $arr;
				}
			}
		}
		return [];
	}

	function webexpert_acs_track_checkpoints($atts) {
		$a = shortcode_atts( array(
			'order_id' => null,
			'voucher_no' => null,
		), $atts );

		$buffer="-";
		$track=$this->webexpert_acs_track($a['order_id'],$a['voucher_no'],false);
		if (is_array($track) && sizeof($track)>0) {
			$buffer="<ul>";
			foreach ($track as $checkpoint) {
				$buffer.="<li><span class='Status'>{$checkpoint->checkpoint_action}</span> <span class='StatusDate'>".date_i18n('d/m/Y H:i:s',strtotime($checkpoint->checkpoint_date_time))."</span> <span class='Shop'>{$checkpoint->checkpoint_location}</span></li>";
			}
			$buffer.="</ul>";
		}

		$track_summary=$this->webexpert_acs_track($a['order_id'],$a['voucher_no'],true);
		if (isset($track_summary)) {
			$order=wc_get_order($a['order_id']);
			if ($order) {
				$order->update_meta_data($a['order_id'],'voucher_delivery_status',$track_summary);
				$order->save();
			}
		}
		return $buffer;
	}

	function webexpert_acs_track_status($atts) {
		$a = shortcode_atts( array(
			'order_id' => null,
			'voucher_no' => null,
		), $atts );

		$track=$this->webexpert_acs_track($a['order_id'],$a['voucher_no'],true);

		if (isset($track)) {
			$order=wc_get_order($a['order_id']);
			if ($order) {
				$order->update_meta_data( 'voucher_delivery_status', $track);
				$order->save();
			}
			return $track;
		}

		return '-';
	}

	function acs_courier_shipping_company_name($shipping_company_name,$order_id) {
		$order=wc_get_order($order_id);
		if ($order) {
			$job_id = !empty($order->get_meta('we_voucher_job_id')) ? $order->get_meta('we_voucher_job_id') : null;
			$vendor=get_post_meta($job_id, 'we_voucher_job_provider', true);
			if (apply_filters('webexpert_custom_vendor_field',$vendor,$order) == "acs") {
				return apply_filters('webexpert_acs_order_tracking_change_title',__('ACS Courier',$this->plugin_name));
			}
		}
		return $shipping_company_name;
	}

	function acs_courier_shipping_tracking_url($shipping_tracking_url,$order_id) {
		$order=wc_get_order($order_id);
		if ($order) {
			$job_id = !empty($order->get_meta('we_voucher_job_id')) ? $order->get_meta('we_voucher_job_id') : null;
			$vendor=get_post_meta($job_id, 'we_voucher_job_provider', true);
			if (apply_filters('webexpert_custom_vendor_field',$vendor,$order) == "acs") {
				return apply_filters('webexpert_acs_order_tracking_change_url','https://www.acscourier.net/el/myacs/anafores-apostolwn/anazitisi-apostolwn/?trackingNumber={tracking_number}',$order);
			}
		}
		return $shipping_tracking_url;
	}

	function webexpert_add_edit_order_my_account_orders_actions( $actions, $order ) {
		if ( $order->has_status( 'completed' ) ) {
			$job_id = !empty($order->get_meta('we_voucher_job_id')) ? $order->get_meta('we_voucher_job_id') : null;
			$vendor=get_post_meta($job_id, 'we_voucher_job_provider', true);
			$voucher_no = get_post_meta($job_id, 'voucher_id', true) ? get_post_meta($job_id, 'voucher_id', true) : null;
			if (empty($voucher_no)) {
				$voucher_no=apply_filters('webexpert_acs_custom_voucher_no',$order->get_meta('_shipping_tracking_number'),$order->get_id());
			}
			if (apply_filters('webexpert_custom_vendor_field',$vendor,$order) == "acs") {
				$actions['tracking'] = array(
					'url'  => apply_filters('webexpert_acs_order_tracking_change_url', 'https://www.acscourier.net/el/myacs/anafores-apostolwn/anazitisi-apostolwn/?trackingNumber='.$voucher_no,$order),
					'name' => __('Tracking', $this->plugin_name)
				);
			}
		}
		return $actions;
	}

	function webexpert_acs_license_admin_notices() {
		if (empty(get_option('webexpert_acs_gateway_license_key')) || empty(get_option('webexpert_acs_gateway_email'))) {
			?>
            <div class="notice notice-error">
                <p><?php _e('Please activate <strong>Web Expert ACS Voucher for WooCommerce</strong> to enable all it\'s features and automatic updates.', 'acs-voucher-for-woocommerce'); ?></p>
            </div>
			<?php
		}
		if (get_option('webexpert_acs_valid_license',false)===false) {
			?>
            <div class="notice notice-error">
                <p><?php _e('The license for <strong>Web Expert ACS Voucher for WooCommerce</strong> is invalid. Please fill in a valid license key.', 'acs-voucher-for-woocommerce'); ?></p>
            </div>
			<?php
		}
	}

	function acs_plugin_action_links($links, $file)
	{
		static $this_plugin;
		if (!$this_plugin) {
			$this_plugin = ( dirname(plugin_basename(__FILE__), 2) . '/' . $this->plugin_name . '.php' );
		}
		if ($file == $this_plugin) {
			$settings_link = '<a href="' . admin_url("admin.php?page=webexpert-acs-voucher").'">'.__('Settings').'</a>';
			$support_link = '<a target="_blank" href="https://support.webexpert.gr">'.__('Support').'</a>';
			array_unshift($links, $settings_link, $support_link);
		}
		return $links;
	}

	function action_after_account_orders_js() {
		$action_slug = 'tracking';
		?>
        <script>
            jQuery(function($){
                $('a.<?php echo $action_slug; ?>').each( function(){
                    $(this).attr('target','_blank');
                })
            });
        </script>
		<?php
	}

	public function webexpert_acs_validate_address_ajax() {
		$billing_address_1=sanitize_text_field($_POST['billing_address_1']);
		$billing_postcode=sanitize_text_field($_POST['billing_postcode']);
		$billing_city=sanitize_text_field($_POST['billing_city']);

		$response=$this->webexpert_acs_validate_address($billing_address_1,$billing_postcode,$billing_city);

		wp_send_json($response);
	}

	public function webexpert_acs_validate_zip($zip) {
		$service_url = 'https://webservices.acscourier.net/ACSRestServices/api/ACSAutoRest';
		$acsAccount = get_option('webexpert_default_acs_account');

		$body = array(
			'ACSAlias' => 'ACS_Area_Find_By_Zip_Code',
			'ACSInputParameters' => [
				'Company_ID' => get_option('webexpert_acs_company_id')[$acsAccount],
				'Company_Password' => get_option('webexpert_acs_company_password')[$acsAccount],
				'User_ID' => get_option('webexpert_acs_user_id')[$acsAccount],
				'User_Password' => get_option('webexpert_acs_user_password')[$acsAccount],
				'Zip_Code'=>str_replace(" ","",$zip)
			]
		);

		if (get_option('webexpert_acs_debug',null)=='1') {
			file_put_contents(wp_upload_dir()['basedir'].'/wc-logs/acs-voucher-for-woocommerce.log',PHP_EOL."=== ACS Voucher for WooCommerce ===".PHP_EOL,FILE_APPEND);
			file_put_contents(wp_upload_dir()['basedir'].'/wc-logs/acs-voucher-for-woocommerce.log',"=== Validate Zip Request ===".PHP_EOL,FILE_APPEND);
			file_put_contents(wp_upload_dir()['basedir'].'/wc-logs/acs-voucher-for-woocommerce.log',json_encode($body,JSON_UNESCAPED_UNICODE).PHP_EOL,FILE_APPEND);
		}

		$request = wp_remote_post($service_url,[
			'data_format' => 'body',
			'method'      => 'POST',
			'body'    => json_encode($body),
			'headers' => array(
				"Content-Type"=> "application/json",
				"ACSApiKey"=> get_option('webexpert_acs_apikey')[$acsAccount]
			),
		]);

		if (wp_remote_retrieve_response_code($request) != 200) {
			return wp_remote_retrieve_response_code($request)." - ".wp_remote_retrieve_response_message($request);
		}

		if (is_wp_error($request)) {
			return $request->get_error_message();
		}

		$response = wp_remote_retrieve_body( $request );
		if (get_option('webexpert_acs_debug',null)=='1') {
			file_put_contents(wp_upload_dir()['basedir'].'/wc-logs/acs-voucher-for-woocommerce.log',PHP_EOL."=== ACS Voucher for WooCommerce ===".PHP_EOL,FILE_APPEND);
			file_put_contents(wp_upload_dir()['basedir'].'/wc-logs/acs-voucher-for-woocommerce.log',"=== Validate Zip Response ===".PHP_EOL,FILE_APPEND);
			file_put_contents(wp_upload_dir()['basedir'].'/wc-logs/acs-voucher-for-woocommerce.log',$response.PHP_EOL,FILE_APPEND);
		}
		$obj = json_decode($response);

		if ($obj->ACSExecution_HasError) {
			return ($obj->ACSExecutionErrorMessage);
		}

		foreach ($obj->ACSOutputResponce->ACSTableOutput->Table_Data as $table_data) {
			return $table_data;
		}

		return $obj;
	}

	public function webexpert_acs_validate_address($address_1,$postcode,$city) {
		$service_url = 'https://webservices.acscourier.net/ACSRestServices/api/ACSAutoRest';

		$acsAccount = get_option('webexpert_default_acs_account');

		$body = array(
			'ACSAlias' => 'ACS_Address_Validation',
			'ACSInputParameters' => [
				'Company_ID' => get_option('webexpert_acs_company_id')[$acsAccount],
				'Company_Password' => get_option('webexpert_acs_company_password')[$acsAccount],
				'User_ID' => get_option('webexpert_acs_user_id')[$acsAccount],
				'User_Password' => get_option('webexpert_acs_user_password')[$acsAccount],
				'Address'=>"$address_1 $postcode $city"
			]
		);

		$request = wp_remote_post($service_url,[
			'data_format' => 'body',
			'method'      => 'POST',
			'body'    => json_encode($body),
			'headers' => array(
				"Content-Type"=> "application/json",
				"ACSApiKey"=>get_option('webexpert_acs_apikey')[$acsAccount]
			),
		]);

		if (wp_remote_retrieve_response_code($request) != 200) {
			return wp_remote_retrieve_response_code($request)." - ".wp_remote_retrieve_response_message($request);
		}

		if (is_wp_error($request)) {
			return $request->get_error_message();
		}

		$response = wp_remote_retrieve_body( $request );
		$obj = json_decode($response);

		if ($obj->ACSExecution_HasError) {
			return ($obj->ACSExecutionErrorMessage);
		}

		foreach ($obj->ACSOutputResponce->ACSValueOutput as $ACSValueOutput) {
			foreach ($ACSValueOutput->ACSObjectOutput as $ACSObjectOutput) {
				return $ACSObjectOutput;
			}
		}
	}

	public function enqueue_public_scripts() {
		if (get_option('webexpert_acs_address_validation','n')=='y') {
			wp_register_script($this->plugin_name."_addr_validate", plugin_dir_url(__FILE__) . 'js/acs-voucher-for-woocommerce-public.js', array('jquery'), $this->version, false);
			if (is_checkout()) {
				wp_enqueue_script($this->plugin_name."_addr_validate");
				wp_localize_script( $this->plugin_name."_addr_validate", 'ajax_object', array( 'ajax_url' => admin_url( 'admin-ajax.php' ) ) );
			}
		}
	}

	function webexpert_acs_delivered_list( $column, $the_order ) {
		if ( ! is_a( $the_order, 'WC_Order' ) ) {
			global $post;
			$the_order = wc_get_order( $post->ID );
		}

		$job_id = !empty($the_order->get_meta('we_voucher_job_id')) ? $the_order->get_meta('we_voucher_job_id') : null;
		$vendor = get_post_meta($job_id, 'we_voucher_job_provider', true);

		if ($vendor == "acs" && !empty($the_order->get_meta('voucher_delivery_status')) && stripos($the_order->get_meta('voucher_delivery_status'), "παρεδόθη") !== false && $column == 'order_number') {
			echo "<div class='webexpert-delivered-icon'>" . wc_help_tip(__('Delivered', $this->plugin_name), false) . "</div>";
		}
	}
}