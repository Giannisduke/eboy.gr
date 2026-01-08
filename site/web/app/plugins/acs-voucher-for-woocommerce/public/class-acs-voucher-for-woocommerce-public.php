<?php

/**
 * The public-facing functionality of the plugin.
 *
 * @link       f43
 * @since      1.0.0
 *
 * @package    Acs_Voucher_For_Woocommerce
 * @subpackage Acs_Voucher_For_Woocommerce/public
 */

/**
 * The public-facing functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the public-facing stylesheet and JavaScript.
 *
 * @package    Acs_Voucher_For_Woocommerce
 * @subpackage Acs_Voucher_For_Woocommerce/public
 * @author     Web Expert <info@webexpert.gr>
 */
class Acs_Voucher_For_Woocommerce_Public {

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
	 * @param      string    $plugin_name       The name of the plugin.
	 * @param      string    $version    The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {
		$this->plugin_name = $plugin_name;
		$this->version = $version;
	}

	public function enqueue_styles() {
		wp_register_style($this->plugin_name, plugin_dir_url( __FILE__ ) . 'css/acs-voucher-for-woocommerce-public.css', array(), $this->version, 'all' );
	}

	public function enqueue_scripts() {
		wp_register_script($this->plugin_name, plugin_dir_url( __FILE__ ) . 'js/acs-voucher-for-woocommerce-public.js', array( 'jquery' ), $this->version, true);
		wp_localize_script($this->plugin_name, 'ajax_object', array('ajax_url' => admin_url('admin-ajax.php')));
	}

	function webexpert_acs_track_form() {
		wp_enqueue_style($this->plugin_name);
		wp_enqueue_script( $this->plugin_name);
		?>
		<div class="webexpert_acs_tracking_container">
			<?php
			$code = '';
			if(isset($_GET['order_id']) && !empty($_GET['order_id'])) {
				$code = sanitize_text_field($_GET['order_id']);
			}
			?>
			<?php if (empty($code)) { ?>
				<form id='webexpert-acs-track-order-form' action='' method='get' class='newsletter-form form-row'>
					<p>
						<label for='order_id'><?php echo __( 'Order ID', $this->plugin_name ) ?> </label>
						<input placeholder='<?php echo  __('Order ID', $this->plugin_name ) ?>' type='text' name='order_id' id='order_id' value='' />
					</p>
					<p>
						<label for="email_or_phone"> <?php echo __('E-mail or Telephone',  $this->plugin_name); ?> </label>
						<input name="email_or_phone"  type="text" id="email_or_phone" placeholder='<?php echo __('E-mail address', $this->plugin_name );?>' value='' />
					</p>
					<button data-failed="<?php _e('Order ID is not valid',$this->plugin_name);?>" type='submit'><?php echo  __( 'Submit', $this->plugin_name ); ?> </button>
				</form>
			<?php } ?>
			<div class="track-results-acs">
				<?php if(!empty($code)){
					$order = wc_get_order($code);
					if($order){
						$status = $order->get_status();
						$status_full = wc_get_order_status_name( $status );
						echo '<h3>'.__( 'Order Status', $this->plugin_name ).'</h3>
<ul>
<li><span class="Status">'.__( 'Order ID', $this->plugin_name ).'</span><span class="Shop">'.$code.'</span></li>
<li><span class="Status">'.__( 'Date', $this->plugin_name ).'</span><span class="Shop">'.wc_format_datetime($order->get_date_created(),get_option( 'date_format' )." ".get_option( 'time_format' )).'</span></li>
<li><span class="Status">'.__( 'Status', $this->plugin_name ).'</span><span class="Shop">'.$status_full.'</span>
</li>
</ul>';
						$str = do_shortcode('[webexpert_acs_track_checkpoints order_id="'.$code.'"]');
						if(!empty($str) && $str!='-')
							echo '<h3>'.__( 'Shipping Status', $this->plugin_name ).'</h3>'.$str;
					}else {
						echo '<div class="woocommerce-error mt-5 mb-0">'.__( 'Wrong order ID or e-mail address', $this->plugin_name ).'</div>';
					}
				}?>
			</div>
		</div>
		<?php
	}

	public function webexpert_get_acs_order_html() {
			$code = sanitize_text_field($_POST['code']);
			$email_or_phone = sanitize_text_field($_POST['email_or_phone']);
			$return = array();
			$return['error'] = 1;

			if(!empty($code)) {
			 $order = wc_get_order($code);
			 if($order && ($order->get_billing_email() == $email_or_phone || apply_filters('webexpert_acs_for_woocommerce_custom_phone',$order->get_billing_phone(),$order) == $email_or_phone ) ){
			     $status = $order->get_status();
			     $status_full = wc_get_order_status_name( $status );
			     $return['html'] = '<h3>'.__( 'Order Status', $this->plugin_name ).'</h3>
			<ul>
			<li><span class="Status">'. __( 'Order ID', $this->plugin_name ).'</span><span class="Shop">'.$code.'</span></li>
			<li><span class="Status">'.__( 'Date', $this->plugin_name ).'</span><span class="Shop">'.wc_format_datetime($order->get_date_created(),get_option( 'date_format' )." ".get_option( 'time_format' )).'</span></li>
			<li><span class="Status">'.__( 'Status', $this->plugin_name ).'</span><span class="Shop">'.$status_full.'</span></li>
			</ul>';
			     $str =  do_shortcode('[webexpert_acs_track_checkpoints order_id="'.$code.'"]');
			     if(!empty($str) && $str!='-')
			         $return['html'] .= '<h3>'. __( 'Shipping Status', 'webexpert-basic-theme' ).'</h3>'.$str;
			     $return['error'] = 0;
			 }
			}
			wp_send_json($return);
			die();
	}

	private function check_for_inacessible_order($orderId) {
		$order = wc_get_order($orderId);

		if(!$order) {
			return;
		}

		$postcode = empty($order->get_shipping_postcode()) ? $order->get_billing_postcode() : $order->get_shipping_postcode();

		$acs=new ACS_Voucher_For_Woocommerce();
        $acs_admin=new ACS_Voucher_For_Woocommerce_Admin($acs->get_plugin_name(),$acs->get_version());

		$result=$acs_admin->webexpert_acs_validate_zip($postcode);
        if ($result->Inaccessible_Area_Kind=="ΔΠ") {
            $order->update_meta_data('_webexpert_acs_inaccessible', "yes");
            $order->save();
        }
	}


	public function webexpert_acs_woocommerce_thankyou($orderId) {
        if (get_option('webexpert_acs_enable_inaccessible_check','0')=='1')
		    $this->check_for_inacessible_order($orderId);
	}

	public function webexpert_acs_payment_complete($orderId) {
        if (get_option('webexpert_acs_enable_inaccessible_check','0')=='1')
		    $this->check_for_inacessible_order($orderId);
	}
}