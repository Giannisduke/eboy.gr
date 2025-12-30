<?php
    /** do output buffer */
    function tbigr_do_output_buffer() {
        ob_start();
    }
    
    /** load admin menu */
    function tbigr_admin_options() {
        include('tbigr_import_admin.php');
    }
    
    /** add origins */
    function tbigr_add_allowed_origins( $origins ) {
        $origins[] = TBIGR_LIVEURL;
        return $origins;
    }
    
    /** add text domain */
    function tbigr_load_textdomain() {
        $locale = apply_filters( 'plugin_locale', determine_locale(), 'tbicreditgr' );
        $mofile = 'tbicreditgr' . '-' . $locale . '.mo';
        load_textdomain( 'tbicreditgr', TBIGR_PLUGIN_DIR . '/languages/' . $mofile );
    }
    
    /** add order column tbigr status */
    function tbigr_add_order_column_status( $columns ) {
        $tbigr_status_columns = ( is_array( $columns ) ) ? $columns : array();
        unset( $tbigr_status_columns[ 'order_actions' ] );
        $tbigr_status_columns['tbigr_status_columnt'] = __('tbi bank Status', 'tbicreditgr');
        $tbigr_status_columns[ 'order_actions' ] = $columns[ 'order_actions' ];
        return $tbigr_status_columns;
    }
    
    /** add order column tbigr status values */
    function tbigr_add_order_column_status_values( $column ) {
        global $post;
        $data = get_post_meta( $post->ID );
        if ( $column == 'tbigr_status_columnt' ) {
            $tbigr_status = '';
            if (file_exists(TBIGR_PLUGIN_DIR . '/keys/tbigrorders.json')){
                $tbigr_orderdata = file_get_contents(TBIGR_PLUGIN_DIR . '/keys/tbigrorders.json');
                $tbigr_orderdata_all = json_decode($tbigr_orderdata, true);
                foreach ($tbigr_orderdata_all as $key => $value){
                    if ($tbigr_orderdata_all[$key]['order_id'] == $post->ID){
                        $tbigr_status = $tbigr_orderdata_all[$key]['order_status'];
                    }
                }
            }
            echo ( $tbigr_status );
        }
    }
    
    function TBIGR_PMT($rate, $nper, $pv, $fv=0, $type = 0) {
        return (-$fv - $pv * pow(1 + $rate, $nper)) / (1 + $rate * $type) / ((pow(1 + $rate, $nper) - 1) / $rate);
    }
    
    /** payment gateway tbi gr **/
    function tbigr_init_tbigr_gateway_class() {
        class WC_Gateway_tbigr_Gateway extends WC_Payment_Gateway {
            public $domain;
			public $instructions;
			public $order_status;
            
            public function __construct() {
                $this->id = 'tbigr';
                $this->icon = apply_filters('woocommerce_custom_gateway_icon', '');
                $this->has_fields = false;
                $this->method_title = __( 'Pay width tbi bank', 'tbicreditgr' );
                $this->method_description = __( 'You pay for the merchandise with tbi bank', 'tbicreditgr' );
                // Load the settings.
                $this->init_form_fields();
                $this->init_settings();
                // Define user set variables
                $this->title = $this->get_option( 'title' );
                $this->description = $this->get_option( 'description' );
                $this->instructions = $this->get_option( 'instructions', $this->description );
                $this->order_status = $this->get_option( 'order_status', 'completed' );
                // Actions
                add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
                add_action( 'woocommerce_thankyou_tbigr', array( $this, 'thankyou_tbigr_page' ) );
                // Customer Emails
                add_action( 'woocommerce_email_before_order_table', array( $this, 'email_tbigr_instructions' ), 10, 3 );
            }
            
            public function TBIGR_PMT($rate, $nper, $pv, $fv=0, $type = 0) {
                return (-$fv - $pv * pow(1 + $rate, $nper)) / (1 + $rate * $type) / ((pow(1 + $rate, $nper) - 1) / $rate);
            }
    
            public function init_form_fields() {
                $this->form_fields = array(
                'enabled' => array(
                'title'   => __( 'Enable/Disable', 'tbicreditgr' ),
                'type'    => 'checkbox',
                'label'   => __( 'Enable tbi bank', 'tbicreditgr' ),
                'default' => 'yes'
                ),
                'title' => array(
                'title'       => __( 'Title', 'tbicreditgr' ),
                'type'        => 'text',
                'description' => __( 'tbi bank (3-60 installments without card & 100% online)', 'tbicreditgr' ),
                'default'     => __( 'tbi bank (3-60 installments without card & 100% online)', 'tbicreditgr' ),
                'desc_tip'    => true,
                ),
                'order_status' => array(
                'title'       => __( 'Order Status', 'tbicreditgr' ),
                'type'        => 'select',
                'class'       => 'wc-enhanced-select',
                'description' => __( 'Choose whether status you wish after checkout.', 'tbicreditgr' ),
                'default'     => 'wc-pending',
                'desc_tip'    => true,
                'options'     => wc_get_order_statuses()
                ),
                'description' => array(
                'title'       => __( 'Description', 'tbicreditgr' ),
                'type'        => 'textarea',
                'description' => __( 'Buy now and pay later.', 'tbicreditgr' ),
                'default'     => __('Buy now and pay later.', 'tbicreditgr'),
                'desc_tip'    => true,
                ),
                'instructions' => array(
                'title'       => __( 'Instructions', 'tbicreditgr' ),
                'type'        => 'textarea',
                'description' => __( 'Buy now and pay later.', 'tbicreditgr' ),
                'default'     => '',
                'desc_tip'    => true,
                ),
                );
            }
            
            /**
            * Check if the gateway is available for use.
            *
            * @return bool
            */
            public function is_available() {
                if ( 'yes' !== $this->enabled ){
                    return false;
                }
                
                if ( WC()->cart && 0 < $this->get_order_total() && 0 < $this->max_amount && $this->max_amount < $this->get_order_total() ) {
                    return false;
                }
                
                $tbigr_unicid = (string)get_option("tbigr_unicid");
                $tbigr_ch = curl_init();
                curl_setopt($tbigr_ch, CURLOPT_SSL_VERIFYPEER, false);
                curl_setopt($tbigr_ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($tbigr_ch, CURLOPT_URL, TBIGR_LIVEURL . '/function/getparameters.php?cid='.$tbigr_unicid);
                $paramstbigr = json_decode(curl_exec($tbigr_ch), true);
                curl_close($tbigr_ch);
                
                $tbigr_minstojnost = floatval($paramstbigr['tbi_minstojnost']);
                $tbigr_maxstojnost = floatval($paramstbigr['tbi_maxstojnost']);
                $tbigr_status_tbi = $paramstbigr['tbi_status'];
                if (
                    WC()->cart && 
                    (
                        $tbigr_status_tbi == "No" || 
                        $this->get_order_total() < $tbigr_minstojnost || 
                        $this->get_order_total() > $tbigr_maxstojnost
                    )
                ){
                    return false;
                }
                
                $is_available = false;
                if (WC()->cart){
                    $items = WC()->cart->get_cart();
                    foreach($items as $item) {
                        $_product = wc_get_product( $item['product_id']); 
                        if (($_product->get_stock_quantity() === NULL) || ($_product->get_stock_quantity() !== 0) || $_product->backorders_allowed()){
                            $is_available = true;
                        }else{
                            return false;
                            break;
                        }
                    } 
                }
                
                return $is_available;
            }
            
            public function thankyou_tbigr_page() {
                if ( $this->instructions )
                echo wpautop( wptexturize( $this->instructions ) );
            }
            
            public function email_tbigr_instructions( $order, $sent_to_admin, $plain_text = false ) {
                if ( $this->instructions && ! $sent_to_admin && 'tbigr' === $order->get_payment_method() && $order->has_status( 'on-hold' ) ) {
                    echo wpautop( wptexturize( $this->instructions ) ) . PHP_EOL;
                }
            }
            
            public function payment_fields(){
                if ( $description = $this->get_description() ) {
                    echo wpautop( wptexturize( $description ) );
                }
                $tbigr_unicid = (string)get_option("tbigr_unicid");
                $tbigr_ch = curl_init();
                curl_setopt($tbigr_ch, CURLOPT_SSL_VERIFYPEER, false);
                curl_setopt($tbigr_ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($tbigr_ch, CURLOPT_URL, TBIGR_LIVEURL . '/function/getparameters.php?cid='.$tbigr_unicid);
                $paramstbigr = json_decode(curl_exec($tbigr_ch), true);
                curl_close($tbigr_ch);
                $tbigr__minprice = $paramstbigr['tbi_minstojnost'];
                global $woocommerce;
                $tbigr__price = $woocommerce->cart->total;
                if ($tbigr__price >= $tbigr__minprice){
                    $tbigr_is_visible = true;
                }else{
                    $tbigr_is_visible = false;
                }
                $tbigr_tbi_custom_button_status = $paramstbigr['tbi_custom_button_status'];
                $tbigr_vnoska = $paramstbigr['tbi_vnoska'];
                if ($paramstbigr['tbi_backurl'] != ''){
                    if (preg_match("#https?://#", $paramstbigr['tbi_backurl']) === 0) {
                        $tbigr_backurl = 'http://'.$paramstbigr['tbi_backurl'];
                    }else{
                        $tbigr_backurl = $paramstbigr['tbi_backurl'];
                    }
                }else{
                    $tbigr_backurl = '';
                }
                $tbi_divider = floatval($paramstbigr['tbi_divider']);
                if ($tbigr__price < $tbi_divider){
                    $tbi_rate = floatval($paramstbigr['tbi_rate']);
                    $tbi_commission = floatval($paramstbigr['tbi_commission']);
                    $tbi_insurance = floatval($paramstbigr['tbi_insurance']);
                    $tbi_months = intval($paramstbigr['tbi_months']);            
                }else{
                    $tbi_rate = floatval($paramstbigr['tbi_rate2']);
                    $tbi_commission = floatval($paramstbigr['tbi_commission2']);
                    $tbi_insurance = floatval($paramstbigr['tbi_insurance2']);
                    $tbi_months = intval($paramstbigr['tbi_months2']);            
                }
                
                if ($tbi_rate == 0){
                    $tbi_rate = 1;
                }
                
                $tbigr_mesecna = $this->TBIGR_PMT(($tbi_rate / 100) / 12, $tbi_months,  - ($tbigr__price + $tbi_commission) * (1 + $tbi_insurance * $tbi_months));
                
                $tbigr_tbi_btn_theme = $paramstbigr['tbi_btn_theme'];
                $tbi_btn_color = '#e55a00;';
                if ($paramstbigr['tbi_btn_theme'] == 'tbi'){
                    $tbi_btn_color = '#e55a00;';
                }
                if ($paramstbigr['tbi_btn_theme'] == 'tbi2'){
                    $tbi_btn_color = '#00368a;';
                }
                if ($paramstbigr['tbi_btn_theme'] == 'tbi3'){
                    $tbi_btn_color = '#2b7953;';
                }
                if ($paramstbigr['tbi_btn_theme'] == 'tbi4'){
                    $tbi_btn_color = '#848789;';
                }
                if ($tbigr_is_visible) {
                    if ($tbigr_tbi_custom_button_status == 'Yes') {
                        if ($tbigr_vnoska == 'Yes'){
                            echo '<table border="0" cellpadding="0" cellspacing="0">';
                            echo '<tr>';
                            echo '<td style="float:left;padding-right:5px;padding-bottom:5px;">';
                            if ($tbigr_backurl == ''){
                                echo "<img id=\"btn_tbigr\" style=\"min-width:155px;min-height:55px;\" src=\"".TBIGR_LIVEURL."/calculators/assets/img/custom_buttons/" . $tbigr_unicid . ".png\" title=\"Credit module tbi bank " . TBIGR_MOD_VERSION . "\" alt=\"Credit module tbi bank " . TBIGR_MOD_VERSION . "\" onmouseover=\"this.src='".TBIGR_LIVEURL."/calculators/assets/img/custom_buttons/" . $tbigr_unicid . "_hover.png'\" onmouseout=\"this.src='".TBIGR_LIVEURL."/calculators/assets/img/custom_buttons/" . $tbigr_unicid . ".png'\">";
                            }else{
                                echo "<a href=\"" . $tbigr_backurl . "\" target=\"_blank\" title=\"Go to tbi bank page\"><img id=\"btn_tbigr\" style=\"cursor:pointer;min-width:155px;min-height:55px;\" src=\"".TBIGR_LIVEURL."/calculators/assets/img/custom_buttons/" . $tbigr_unicid . ".png\" title=\"Credit module tbi bank " . TBIGR_MOD_VERSION . "\" alt=\"Credit module tbi bank " . TBIGR_MOD_VERSION . "\" onmouseover=\"this.src='".TBIGR_LIVEURL."/calculators/assets/img/custom_buttons/" . $tbigr_unicid . "_hover.png'\" onmouseout=\"this.src='".TBIGR_LIVEURL."/calculators/assets/img/custom_buttons/" . $tbigr_unicid . ".png'\"></a>";
                            }
                            echo '</td>';
                            echo '</tr>';
                            echo '<tr>';
                            echo '<td style="vertical-align:bottom;">';
                            echo '<p style="color:' . $tbi_btn_color . 'font-size:16pt;font-weight:bold;">' . number_format($tbigr_mesecna, 2, '.', '') . ' ' . __('EUR', 'tbicreditgr') . ' x ' . $tbi_months . ' ' . __('months', 'tbicreditgr') . '</p>';
                            echo '</td>';
                            echo '</tr>';
                            echo '</table>';
                        }else{
                            echo "<a href=\"" . $tbigr_backurl . "\" target=\"_blank\" style=\"float:left;\" title=\"Go to tbi bank page\"><img id=\"btn_tbigr\" style=\"cursor:pointer;min-width:155px;min-height:55px;\" src=\"".TBIGR_LIVEURL."/calculators/assets/img/custom_buttons/" . $tbigr_unicid . ".png\" title=\"Credit module tbi bank " . TBIGR_MOD_VERSION . "\" alt=\"Credit module tbi bank " . TBIGR_MOD_VERSION . "\" onmouseover=\"this.src='".TBIGR_LIVEURL."/calculators/assets/img/custom_buttons/" . $tbigr_unicid . "_hover.png'\" onmouseout=\"this.src='".TBIGR_LIVEURL."/calculators/assets/img/custom_buttons/" . $tbigr_unicid . ".png'\"></a>";
                            echo "<br /><br />";
                        }
                    }else{
                        if ($tbigr_vnoska == 'Yes'){
                            echo '<table border="0" cellpadding="0" cellspacing="0">';
                            echo '<tr>';
                            echo '<td style="float:left;padding-right:5px;padding-bottom:5px;">';
                            if ($tbigr_backurl == ''){
                                echo "<img id=\"btn_tbigr\" style=\"min-width:155px;min-height:55px;\" src=\"".TBIGR_LIVEURL."/calculators/assets/img/buttons/" . $tbigr_tbi_btn_theme . ".png\" title=\"Credit module tbi bank " . TBIGR_MOD_VERSION . "\" alt=\"Credit module tbi bank " . TBIGR_MOD_VERSION . "\" onmouseover=\"this.src='".TBIGR_LIVEURL."/calculators/assets/img/buttons/" . $tbigr_tbi_btn_theme . "-hover.png'\" onmouseout=\"this.src='".TBIGR_LIVEURL."/calculators/assets/img/buttons/" . $tbigr_tbi_btn_theme . ".png'\">";
                            }else{
                                echo "<a href=\"" . $tbigr_backurl . "\" target=\"_blank\" title=\"Go to tbi bank page\"><img id=\"btn_tbigr\" style=\"cursor:pointer;min-width:155px;min-height:55px;\" src=\"".TBIGR_LIVEURL."/calculators/assets/img/buttons/" . $tbigr_tbi_btn_theme . ".png\" title=\"Credit module tbi bank " . TBIGR_MOD_VERSION . "\" alt=\"Credit module tbi bank " . TBIGR_MOD_VERSION . "\" onmouseover=\"this.src='".TBIGR_LIVEURL."/calculators/assets/img/buttons/" . $tbigr_tbi_btn_theme . "-hover.png'\" onmouseout=\"this.src='".TBIGR_LIVEURL."/calculators/assets/img/buttons/" . $tbigr_tbi_btn_theme . ".png'\"></a>";
                            }
                            echo '</td>';
                            echo '</tr>';
                            echo '<tr>';
                            echo '<td style="vertical-align:bottom;">';
                            echo '<p style="color:' . $tbi_btn_color . 'font-size:16pt;font-weight:bold;">' . number_format($tbigr_mesecna, 2, '.', '') . ' ' . __('EUR', 'tbicreditgr') . ' x ' . $tbi_months . ' ' . __('months', 'tbicreditgr') . '</p>';
                            echo '</td>';
                            echo '</tr>';
                            echo '</table>';
                        }else{
                            echo "<a href=\"" . $tbigr_backurl . "\" target=\"_blank\" style=\"float:left;\" title=\"Go to tbi bank page\"><img id=\"btn_tbigr\" style=\"padding-bottom: 5px;cursor:pointer;width:155px;\" src=\"".TBIGR_LIVEURL."/calculators/assets/img/buttons/" . $tbigr_tbi_btn_theme . ".png\" title=\"Credit module tbi bank " . TBIGR_MOD_VERSION . "\" alt=\"Credit module tbi bank " . TBIGR_MOD_VERSION . "\" onmouseover=\"this.src='".TBIGR_LIVEURL."/calculators/assets/img/buttons/" . $tbigr_tbi_btn_theme . "-hover.png'\" onmouseout=\"this.src='".TBIGR_LIVEURL."/calculators/assets/img/buttons/" . $tbigr_tbi_btn_theme . ".png'\"></a>";
                            echo "<br />";
                        }
                    }
                }
            }
            
            public function process_payment( $order_id ) {
                $order = wc_get_order( $order_id );
                $status = 'wc-' === substr( $this->order_status, 0, 3 ) ? substr( $this->order_status, 3 ) : $this->order_status;
                // Set order status
                $order->update_status( $status, __( 'tbi bank. ', 'tbicreditgr' ) );
                
                $tbigr_unicid = (string)get_option("tbigr_unicid");
                $tbigr_store_id = (string)get_option('credittbigr_store_id');
                $tbigr_username = (string)get_option('credittbigr_username');
                $tbigr_password = (string)get_option('credittbigr_password');
                $tbigr_ch = curl_init();
                curl_setopt($tbigr_ch, CURLOPT_SSL_VERIFYPEER, false);
                curl_setopt($tbigr_ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($tbigr_ch, CURLOPT_URL, TBIGR_LIVEURL . '/function/getparameters.php?cid='.$tbigr_unicid);
                $paramstbigr = json_decode(curl_exec($tbigr_ch), true);
                curl_close($tbigr_ch);
                
                global $woocommerce;
                $tbigr__price = 0;
                $tbigr__minprice = $paramstbigr['tbi_minstojnost'];
                $tbigr__price = $woocommerce->cart->total;
                
                global $current_user;
                get_currentuserinfo();
                $tbigr_products = '';
                $tbigr_products_q = '';
                $tbigr_products_n = '';
                $ident = 0;
                $tbigr_api_items = array();
                foreach($woocommerce->cart->get_cart() as $cart_item){
                    if(!empty($cart_item)){
                        $tbigr_items[$ident]['name'] = $cart_item['data']->get_title();
                        $tbigr_api_items[$ident]['name'] = $cart_item['data']->get_title();
                        $tbigr_api_items[$ident]['description'] = '';
                        $tbigr_quantity = $cart_item['quantity'];
                        $tbigr_items[$ident]['qty'] = "$tbigr_quantity";
                        $tbigr_api_items[$ident]['qty'] = "$tbigr_quantity";
                        $tbigr_price = (float)$cart_item['line_total'] / (float)$cart_item['quantity'];
                        $tbigr_items[$ident]['price'] = "$tbigr_price";
                        $tbigr_api_items[$ident]['price'] = "$tbigr_price";
                        $terms = get_the_terms( $cart_item['product_id'], 'product_cat' );
                        foreach ($terms as $term){
                            $tbigr_category = $term->term_id;
                        }
                        $tbigr_items[$ident]['category'] = "$tbigr_category";
                        $tbigr_product_id = $cart_item['product_id'];
                        $tbigr_items[$ident]['sku'] = "$tbigr_product_id";
                        $tbigr_api_items[$ident]['sku'] = "$tbigr_product_id";
                        $tbigr_api_items[$ident]['category'] = "$tbigr_category";
                        $tbigr_image = wp_get_attachment_image_src( get_post_thumbnail_id( $cart_item['product_id'] ), 'single-post-thumbnail' );
                        $tbigr_imagePath = isset($tbigr_image[0]) ? $tbigr_image[0] : '';
                        $tbigr_items[$ident]['ImageLink'] = "$tbigr_imagePath";
                        $tbigr_api_items[$ident]['ImageLink'] = "$tbigr_imagePath";
                        $ident++;
                    }
                }
                
                global $current_user;
                get_currentuserinfo();
                $tbigr_image = wp_get_attachment_image_src( get_post_thumbnail_id( $loop->post->ID ), 'single-post-thumbnail' );
                
                $tbigr_url = TBIGR_LIVEURL . "/function/status.php";
                $tbigr_fname = isset($current_user->user_firstname) ? $current_user->user_firstname : tbigr_wordpress_get_params( 'billing_first_name', '' );
                $tbigr_lname = isset($current_user->user_firstname) ? $current_user->user_lastname : tbigr_wordpress_get_params( 'billing_last_name', '' );
                $tbigr_cnp = '';
                $tbigr_email = isset($current_user->user_email) ? $current_user->user_email : tbigr_wordpress_get_params( 'billing_email', '' );
                $tbigr_person_type = '';
                $tbigr_net_income = '';
                $tbigr_instalments = '';
                $tbigr_quantity = tbigr_wordpress_get_params( 'tbigr_newq', 1 );
                $tbigr_product_category = '';
                $tbigr_imagePath = isset($tbigr_image[0]) ? $tbigr_image[0] : '';
                $tbigr_customer = new WC_Customer( $current_user->ID );
                $tbigr_phone = ($tbigr_customer->get_billing_phone() !== '') ? $tbigr_customer->get_billing_phone() : tbigr_wordpress_get_params( 'billing_phone', '' );
                $tbigr_billing_address = ($tbigr_customer->get_billing_address() !== '') ? $tbigr_customer->get_billing_address() : tbigr_wordpress_get_params( 'billing_address_1', '' ) . ' ' . tbigr_wordpress_get_params( 'billing_address_2', '' );
                $tbigr_billing_city = ($tbigr_customer->get_billing_city() !== '') ? $tbigr_customer->get_billing_city() : tbigr_wordpress_get_params( 'billing_city', '' );
                $tbigr_billing_county = ($tbigr_customer->get_billing_state() !== '') ? $tbigr_customer->get_billing_state() : tbigr_wordpress_get_params( 'billing_state', '' );
                $tbigr_shipping_address = ($tbigr_customer->get_shipping_address() !== '') ? $tbigr_customer->get_shipping_address() : tbigr_wordpress_get_params( 'billing_address_1', '' ) . ' ' . tbigr_wordpress_get_params( 'billing_address_2', '' );
                $tbigr_shipping_city = ($tbigr_customer->get_shipping_city() !== '') ? $tbigr_customer->get_shipping_city() : tbigr_wordpress_get_params( 'billing_city', '' );
                $tbigr_shipping_county = ($tbigr_customer->get_shipping_state() !== '') ? $tbigr_customer->get_shipping_state() : tbigr_wordpress_get_params( 'billing_state', '' );
                
                // Create tbigr order i data base
                $tbigr_add_ch = curl_init();
                curl_setopt($tbigr_add_ch, CURLOPT_SSL_VERIFYPEER, false);
                curl_setopt($tbigr_add_ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($tbigr_add_ch, CURLOPT_URL, TBIGR_LIVEURL . '/function/addorders.php?cid='.$tbigr_unicid);
                curl_setopt($tbigr_add_ch, CURLOPT_POST, 1);
                $tbigr_post = array(
                    'store_id'      => $tbigr_store_id,
                    'order_id'      =>  $order_id,
                    'type'          =>  'TBI',
                    'back_ref'      =>  $tbigr_url,
                    'order_total'   =>  $tbigr__price,
                    'username'        => $tbigr_username,
                    'password'        => $tbigr_password,
                    'customer'      =>  array(
                        'fname'         => $tbigr_fname,
                        'lname'         => $tbigr_lname,
                        'cnp'           => $tbigr_cnp, 
                        'email'         => $tbigr_email,
                        'phone'         => $tbigr_phone,
                        'billing_address'      => $tbigr_billing_address,
                        'billing_city'          => $tbigr_billing_city,
                        'billing_county'        => $tbigr_billing_county,
                        'shipping_address'      => $tbigr_shipping_address,
                        'shipping_city'          => $tbigr_shipping_city,
                        'shipping_county'        => $tbigr_shipping_county,
                        'person_type'   => $tbigr_person_type,
                        'net_income'    => $tbigr_net_income,
                        'instalments'    => $tbigr_instalments
                    ),
                    'items' => $tbigr_items
                );
                curl_setopt($tbigr_add_ch, CURLOPT_POSTFIELDS, http_build_query($tbigr_post));
                $paramstbigradd=json_decode(curl_exec($tbigr_add_ch), true);
                curl_close($tbigr_add_ch);                
                // Create tbigr order i data base
                
                if (isset($paramstbigradd['status']) && ($paramstbigradd['status'] == 'Yes')){
                    // save to tbiorders file
                    $tbigr_tempcontent = file_get_contents(TBIGR_PLUGIN_DIR . '/keys/tbigrorders.json');
                    if ($tbigr_tempcontent != false){
                        $tbigr_orders = json_decode($tbigr_tempcontent);
                        // test over 1000
                        if (is_array($tbigr_orders) && (count($tbigr_orders) >= 1000)){
                            array_shift($tbigr_orders);
                        }
                        $tbigr_order_current = array(
                            "order_id" => $order_id,
                            "order_status" => "Draft"
                        );
                        $key = array_search($order_id, array_map(
                            function($o) {
                                return $o->order_id;
                            }, 
                            $tbigr_orders));
                        if ($key === false){
                            array_push($tbigr_orders, $tbigr_order_current);
                        }
                        $jsondata = json_encode($tbigr_orders);
                        file_put_contents(TBIGR_PLUGIN_DIR . '/keys/tbigrorders.json', $jsondata);
                    }
                    
                    // send to softinteligens
                    $tbigr_post = array(
                        'firstname'          => $tbigr_fname,
                        'lastname'          => $tbigr_lname,
                        'surname'              => '',
                        'pin'               => $tbigr_cnp,
                        'origin'            => 'POS_Online',
                        'resellercode'        => $tbigr_store_id,
                        'email'             => $tbigr_email,
                        'phone'             => $tbigr_phone,
                        'billingaddress'    => array(
                            'country'            => '',
                            'county'            => $tbigr_billing_county,
                            'city'                  => $tbigr_billing_city,
                            'streetname'          => $tbigr_billing_address,
                            'streetno'            => '',
                            'buildingno'        => '',
                            'entranceno'        => '',
                            'floorno'            => '',
                            'apartmentno'        => '',
                            'postalcode'        => ''
                        ),
                        'deliveryaddress'    => array(
                            'country'            => '',
                            'county'            => $tbigr_shipping_county,
                            'city'                  => $tbigr_shipping_city,
                            'streetname'          => $tbigr_shipping_address,
                            'streetno'            => '',
                            'buildingno'        => '',
                            'entranceno'        => '',
                            'floorno'            => '',
                            'apartmentno'        => '',
                            'postalcode'        => ''
                        ),
                        'orderid'           => $paramstbigradd['newid'],
                        'bankingproductcode'=> '',
                        'promo'                => '0',
                        'installments'        => $tbigr_instalments,
                        'ordertotal'        => $tbigr__price,
                        'installmentamount'    => '',
                        'items'             => $tbigr_api_items,
                        'siteurl'            => TBIGR_LIVEURL,
                        'statusurl'            => $tbigr_url
                    );
                    if ($paramstbigr['tbi_testenv'] == 1){
                        $tbigr_ocp = '1f0a04a6ad7a4dac80c664706fdcb93c';
                        $tbigr_envurl = $paramstbigr['tbi_testurl'];
                    }else{
                        $tbigr_ocp = 'd2304c1fe5de43d8837d081192a9a39b';
                        $tbigr_envurl = $paramstbigr['tbi_liveurl'];
                    }
                    
                    $tbi_pause_txt = $paramstbigr['tbi_pause_txt'];
                    
                    $tbigr_plaintext = json_encode($tbigr_post, JSON_UNESCAPED_UNICODE);
                    $curl_token = curl_init();
                    curl_setopt_array($curl_token, array(
                        CURLOPT_URL             => $tbigr_envurl . '/User/authorize',
                        CURLOPT_RETURNTRANSFER     => true,
                        CURLOPT_ENCODING         => '',
                        CURLOPT_MAXREDIRS         => 4,
                        CURLOPT_TIMEOUT         => 0,
                        CURLOPT_FOLLOWLOCATION     => true,
                        CURLOPT_HTTP_VERSION     => CURL_HTTP_VERSION_1_1,
                        CURLOPT_CUSTOMREQUEST     => 'POST',
                        CURLOPT_POSTFIELDS         =>'{"username":"' . $tbigr_username . '","password":"' . $tbigr_password . '"}',
                        CURLOPT_HTTPHEADER         => array(
                            'Content-Type: application/json',
                            'Ocp-Apim-Subscription-Key: ' . $tbigr_ocp,
                            'Ocp-Apim-Trace: true'
                        ),
                    ));
                    $response_token = curl_exec($curl_token);
                    curl_close($curl_token);
                    $tbigr_token = json_decode($response_token);
                    
                    if (!empty($tbigr_token->token)){
                        $curl_application = curl_init();
                        curl_setopt_array($curl_application, array(
                            CURLOPT_URL => $tbigr_envurl . '/Application',
                            CURLOPT_RETURNTRANSFER => true,
                            CURLOPT_ENCODING => '',
                            CURLOPT_MAXREDIRS => 4,
                            CURLOPT_TIMEOUT => 0,
                            CURLOPT_FOLLOWLOCATION => true,
                            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                            CURLOPT_CUSTOMREQUEST => 'POST',
                            CURLOPT_POSTFIELDS => $tbigr_plaintext,
                            CURLOPT_HTTPHEADER => array(
                                'Content-Type: application/json',
                                'Ocp-Apim-Subscription-Key: ' . $tbigr_ocp,
                                'Ocp-Apim-Trace: true',
                                'AuthorizationToken: ' . $tbigr_token->token
                            ),
                        ));
                        $response_application = curl_exec($curl_application);
                        $curl_tbigr_error = curl_error($curl_application);
                        curl_close($curl_application);
                        $tbigr_result = json_decode($response_application);
                        
                        if ($curl_tbigr_error){
                            wc_add_notice(__( 'Communication error. You cannot create an order in tbi bank ftos. Please try again later.', 'tbicreditgr' ), 'error');
                            return NULL;
                        }else{
                            if ($tbigr_result->ErrorCode == null){
                                $tbigr_url = $tbigr_result->Url;
                                WC()->cart->empty_cart();
                                return array(
                                    'result'    => 'success',
                                    'redirect'  => esc_url_raw($tbigr_url)
                                );
                            }else{
                                wc_add_notice(__( 'You cannot create an order in tbi bank ftos. Please try again later.', 'tbicreditgr' ), 'error');
                                return NULL;
                            }
                        }
                    }else{
                        wc_add_notice(__( 'Communication error. You cannot create an order in tbi bank ftos. Please try again later.', 'tbicreditgr' ), 'error');
                        return NULL;
                    }
                }
            }
            
        }
        
    }
    
    /** payment gateway tbi iris **/
    function tbigr_init_tbiiris_gateway_class() {
        class WC_Gateway_tbiiris_Gateway extends WC_Payment_Gateway {
            public $domain;
			public $instructions;
			public $order_status;
            
            public function __construct() {
                $this->id = 'tbiiris';
                $this->icon = apply_filters('woocommerce_custom_gateway_icon', '');
                $this->has_fields = false;
                $this->method_title = __( 'Pay width IRIS Pay', 'tbicreditgr' );
                $this->method_description = __( 'You pay for the merchandise with IRIS Pay', 'tbicreditgr' );
                // Load the settings.
                $this->init_form_fields();
                $this->init_settings();
                // Define user set variables
                $this->title = $this->get_option( 'title' );
                $this->description = $this->get_option( 'description' );
                $this->instructions = $this->get_option( 'instructions', $this->description );
                $this->order_status = $this->get_option( 'order_status', 'completed' );
                // Actions
                add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
                add_action( 'woocommerce_thankyou_tbiiris', array( $this, 'thankyou_tbiiris_page' ) );
                // Customer Emails
                add_action( 'woocommerce_email_before_order_table', array( $this, 'email_tbiiris_instructions' ), 10, 3 );
            }
            
            public function TBIIRIS_PMT($rate, $nper, $pv, $fv=0, $type = 0) {
                return (-$fv - $pv * pow(1 + $rate, $nper)) / (1 + $rate * $type) / ((pow(1 + $rate, $nper) - 1) / $rate);
            }
    
            public function init_form_fields() {
                $this->form_fields = array(
                'enabled' => array(
                'title'   => __( 'Enable/Disable', 'tbicreditgr' ),
                'type'    => 'checkbox',
                'label'   => __( 'Enable IRIS Pay', 'tbicreditgr' ),
                'default' => 'yes'
                ),
                'title' => array(
                'title'       => __( 'Title', 'tbicreditgr' ),
                'type'        => 'text',
                'description' => __( 'This controls the title which the user sees during checkout.', 'tbicreditgr' ),
                'default'     => __( 'IRIS Pay', 'tbicreditgr' ),
                'desc_tip'    => true,
                ),
                'order_status' => array(
                'title'       => __( 'Order Status', 'tbicreditgr' ),
                'type'        => 'select',
                'class'       => 'wc-enhanced-select',
                'description' => __( 'Choose whether status you wish after checkout.', 'tbicreditgr' ),
                'default'     => 'wc-pending',
                'desc_tip'    => true,
                'options'     => wc_get_order_statuses()
                ),
                'description' => array(
                'title'       => __( 'Description', 'tbicreditgr' ),
                'type'        => 'textarea',
                'description' => __( 'Payment method description that the customer will see on your checkout.', 'tbicreditgr' ),
                'default'     => __('You pay for the merchandise with IRIS Pay', 'tbicreditgr'),
                'desc_tip'    => true,
                ),
                'instructions' => array(
                'title'       => __( 'Instructions', 'tbicreditgr' ),
                'type'        => 'textarea',
                'description' => __( 'Instructions that will be added to the thank you page and emails.', 'tbicreditgr' ),
                'default'     => '',
                'desc_tip'    => true,
                ),
                );
            }
            
            /**
            * Check if the gateway is available for use.
            *
            * @return bool
            */
            public function is_available() {
                if ( 'yes' !== $this->enabled ){
                    return false;
                }
                
                if ( WC()->cart && 0 < $this->get_order_total() && 0 < $this->max_amount && $this->max_amount < $this->get_order_total() ) {
                    return false;
                }
                
                $tbigr_unicid = (string)get_option("tbigr_unicid");
                $tbigr_ch = curl_init();
                curl_setopt($tbigr_ch, CURLOPT_SSL_VERIFYPEER, false);
                curl_setopt($tbigr_ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($tbigr_ch, CURLOPT_URL, TBIGR_LIVEURL . '/function/getparameters.php?cid='.$tbigr_unicid);
                $paramstbigr = json_decode(curl_exec($tbigr_ch), true);
                curl_close($tbigr_ch);
        
                $tbigr_status_iris = $paramstbigr['iris_status'];
                if (WC()->cart && $tbigr_status_iris == "No") {
                    return false;
                }
                
                $is_available = false;
                if (WC()->cart){
                    $items = WC()->cart->get_cart();
                    foreach($items as $item) {
                        $_product = wc_get_product( $item['product_id']); 
                        if (($_product->get_stock_quantity() === NULL) || ($_product->get_stock_quantity() !== 0) || $_product->backorders_allowed()){
                            $is_available = true;
                        }else{
                            return false;
                            break;
                        }
                    } 
                }
                
                return $is_available;
            }
            
            public function thankyou_tbiiris_page() {
                if ( $this->instructions )
                echo wpautop( wptexturize( $this->instructions ) );
            }
            
            public function email_tbiiris_instructions( $order, $sent_to_admin, $plain_text = false ) {
                if ( $this->instructions && ! $sent_to_admin && 'tbiiris' === $order->get_payment_method() && $order->has_status( 'on-hold' ) ) {
                    echo wpautop( wptexturize( $this->instructions ) ) . PHP_EOL;
                }
            }
            
            public function payment_fields(){
                if ( $description = $this->get_description() ) {
                    echo wpautop( wptexturize( $description ) );
                }
                $tbigr_unicid = (string)get_option("tbigr_unicid");
                $tbigr_ch = curl_init();
                curl_setopt($tbigr_ch, CURLOPT_SSL_VERIFYPEER, false);
                curl_setopt($tbigr_ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($tbigr_ch, CURLOPT_URL, TBIGR_LIVEURL . '/function/getparameters.php?cid='.$tbigr_unicid);
                $paramstbigr = json_decode(curl_exec($tbigr_ch), true);
                curl_close($tbigr_ch);
                global $woocommerce;
                $tbigr_iris_custom_button_status = $paramstbigr['iris_custom_button_status'];
                if ($paramstbigr['iris_backurl'] != ''){
                    if (preg_match("#https?://#", $paramstbigr['iris_backurl']) === 0) {
                        $iris_backurl = 'http://'.$paramstbigr['iris_backurl'];
                    }else{
                        $iris_backurl = $paramstbigr['iris_backurl'];
                    }
                }else{
                    $iris_backurl = '';
                }
                
                $tbigr_tbi_btn_theme = $paramstbigr['tbi_btn_theme'];
                
                if ($tbigr_iris_custom_button_status == 'Yes') {
                    echo "<a href=\"" . $iris_backurl . "\" target=\"_blank\" style=\"float:left;\" title=\"Go to IRIS Pay page\"><img id=\"btn_tbigr\" style=\"cursor:pointer;min-width:155px;min-height:55px;\" src=\"".TBIGR_LIVEURL."/calculators/assets/img/custom_buttons_iris/" . $tbigr_unicid . ".png\" title=\"Credit module IRIS Pay " . TBIGR_MOD_VERSION . "\" alt=\"Credit module IRIS Pay " . TBIGR_MOD_VERSION . "\" onmouseover=\"this.src='".TBIGR_LIVEURL."/calculators/assets/img/custom_buttons_iris/" . $tbigr_unicid . "_hover.png'\" onmouseout=\"this.src='".TBIGR_LIVEURL."/calculators/assets/img/custom_buttons_iris/" . $tbigr_unicid . ".png'\"></a>";
                }else{
                    echo "<a href=\"" . $iris_backurl . "\" target=\"_blank\" style=\"float:left;\" title=\"Go to IRIS Pay page\"><img id=\"btn_tbigr\" style=\"padding-bottom: 5px;cursor:pointer;width:155px;\" src=\"".TBIGR_LIVEURL."/calculators/assets/img/buttons/" . $tbigr_tbi_btn_theme . ".png\" title=\"Credit module IRIS Pay " . TBIGR_MOD_VERSION . "\" alt=\"Credit module IRIS Pay " . TBIGR_MOD_VERSION . "\" onmouseover=\"this.src='".TBIGR_LIVEURL."/calculators/assets/img/buttons/" . $tbigr_tbi_btn_theme . "-hover.png'\" onmouseout=\"this.src='".TBIGR_LIVEURL."/calculators/assets/img/buttons/" . $tbigr_tbi_btn_theme . ".png'\"></a>";
                }                
            }
            
            public function process_payment( $order_id ) {
                $order = wc_get_order( $order_id );
                $status = 'wc-' === substr( $this->order_status, 0, 3 ) ? substr( $this->order_status, 3 ) : $this->order_status;
                // Set order status
                $order->update_status( $status, __( 'IRIS Pay. ', 'tbicreditgr' ) );
                
                $tbigr_unicid = (string)get_option("tbigr_unicid");
                $tbigr_store_id = (string)get_option('credittbigr_store_id');
                $tbigr_password = (string)get_option('credittbigr_password');
                $tbigr_iris_iban = (string)get_option('credittbigr_iris_iban');
                $tbigr_iris_user = (string)get_option('credittbigr_iris_user');
                $tbigr_ch = curl_init();
                curl_setopt($tbigr_ch, CURLOPT_SSL_VERIFYPEER, false);
                curl_setopt($tbigr_ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($tbigr_ch, CURLOPT_URL, TBIGR_LIVEURL . '/function/getparameters.php?cid='.$tbigr_unicid);
                $paramstbigr = json_decode(curl_exec($tbigr_ch), true);
                curl_close($tbigr_ch);
                
                global $woocommerce;
                $tbigr__price = 0;
                $tbigr__minprice = $paramstbigr['tbi_minstojnost'];
                $tbigr__price = $woocommerce->cart->total;
                
                global $current_user;
                get_currentuserinfo();
                $tbigr_products = '';
                $tbigr_products_q = '';
                $tbigr_products_n = '';
                $ident = 0;
                $tbigr_api_items = array();
                foreach($woocommerce->cart->get_cart() as $cart_item){
                    if(!empty($cart_item)){
                        $tbigr_items[$ident]['name'] = $cart_item['data']->get_title();
                        $tbigr_api_items[$ident]['name'] = $cart_item['data']->get_title();
                        $tbigr_api_items[$ident]['description'] = '';
                        $tbigr_quantity = $cart_item['quantity'];
                        $tbigr_items[$ident]['qty'] = "$tbigr_quantity";
                        $tbigr_api_items[$ident]['qty'] = "$tbigr_quantity";
                        $tbigr_price = (float)$cart_item['line_total'] / (float)$cart_item['quantity'];
                        $tbigr_items[$ident]['price'] = "$tbigr_price";
                        $tbigr_api_items[$ident]['price'] = "$tbigr_price";
                        $terms = get_the_terms( $cart_item['product_id'], 'product_cat' );
                        foreach ($terms as $term){
                            $tbigr_category = $term->term_id;
                        }
                        $tbigr_items[$ident]['category'] = "$tbigr_category";
                        $tbigr_product_id = $cart_item['product_id'];
                        $tbigr_items[$ident]['sku'] = "$tbigr_product_id";
                        $tbigr_api_items[$ident]['sku'] = "$tbigr_product_id";
                        $tbigr_api_items[$ident]['category'] = "$tbigr_category";
                        $tbigr_image = wp_get_attachment_image_src( get_post_thumbnail_id( $cart_item['product_id'] ), 'single-post-thumbnail' );
                        $tbigr_imagePath = isset($tbigr_image[0]) ? $tbigr_image[0] : '';
                        $tbigr_items[$ident]['ImageLink'] = "$tbigr_imagePath";
                        $tbigr_api_items[$ident]['ImageLink'] = "$tbigr_imagePath";
                        $ident++;
                    }
                }
                
                global $current_user;
                get_currentuserinfo();
                $tbigr_image = wp_get_attachment_image_src( get_post_thumbnail_id( $loop->post->ID ), 'single-post-thumbnail' );
                
                $tbigr_url = TBIGR_LIVEURL . "/function/status.php";
                $tbigr_fname = isset($current_user->user_firstname) ? $current_user->user_firstname : tbigr_wordpress_get_params( 'billing_first_name', '' );
                $tbigr_lname = isset($current_user->user_firstname) ? $current_user->user_lastname : tbigr_wordpress_get_params( 'billing_last_name', '' );
                $tbigr_cnp = '';
                $tbigr_email = isset($current_user->user_email) ? $current_user->user_email : tbigr_wordpress_get_params( 'billing_email', '' );
                $tbigr_person_type = '';
                $tbigr_net_income = '';
                $tbigr_instalments = '';
                $tbigr_quantity = tbigr_wordpress_get_params( 'tbigr_newq', 1 );
                $tbigr_product_category = '';
                $tbigr_imagePath = isset($tbigr_image[0]) ? $tbigr_image[0] : '';
                $tbigr_customer = new WC_Customer( $current_user->ID );
                $tbigr_phone = ($tbigr_customer->get_billing_phone() !== '') ? $tbigr_customer->get_billing_phone() : tbigr_wordpress_get_params( 'billing_phone', '' );
                $tbigr_billing_address = ($tbigr_customer->get_billing_address() !== '') ? $tbigr_customer->get_billing_address() : tbigr_wordpress_get_params( 'billing_address_1', '' ) . ' ' . tbigr_wordpress_get_params( 'billing_address_2', '' );
                $tbigr_billing_city = ($tbigr_customer->get_billing_city() !== '') ? $tbigr_customer->get_billing_city() : tbigr_wordpress_get_params( 'billing_city', '' );
                $tbigr_billing_county = ($tbigr_customer->get_billing_state() !== '') ? $tbigr_customer->get_billing_state() : tbigr_wordpress_get_params( 'billing_state', '' );
                $tbigr_shipping_address = ($tbigr_customer->get_shipping_address() !== '') ? $tbigr_customer->get_shipping_address() : tbigr_wordpress_get_params( 'billing_address_1', '' ) . ' ' . tbigr_wordpress_get_params( 'billing_address_2', '' );
                $tbigr_shipping_city = ($tbigr_customer->get_shipping_city() !== '') ? $tbigr_customer->get_shipping_city() : tbigr_wordpress_get_params( 'billing_city', '' );
                $tbigr_shipping_county = ($tbigr_customer->get_shipping_state() !== '') ? $tbigr_customer->get_shipping_state() : tbigr_wordpress_get_params( 'billing_state', '' );
                
                // Create tbigr order i data base
                $tbigr_add_ch = curl_init();
                curl_setopt($tbigr_add_ch, CURLOPT_SSL_VERIFYPEER, false);
                curl_setopt($tbigr_add_ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($tbigr_add_ch, CURLOPT_URL, TBIGR_LIVEURL . '/function/addorders.php?cid='.$tbigr_unicid);
                curl_setopt($tbigr_add_ch, CURLOPT_POST, 1);
                $tbigr_post = array(
                    'store_id'      => $tbigr_store_id,
                    'order_id'      =>  $order_id,
                    'type'          =>  'IRIS',
                    'back_ref'      =>  $tbigr_url,
                    'order_total'   =>  $tbigr__price,
                    'username'        => $tbigr_username,
                    'password'        => $tbigr_password,
                    'customer'      =>  array(
                        'fname'         => $tbigr_fname,
                        'lname'         => $tbigr_lname,
                        'cnp'           => $tbigr_cnp, 
                        'email'         => $tbigr_email,
                        'phone'         => $tbigr_phone,
                        'billing_address'      => $tbigr_billing_address,
                        'billing_city'          => $tbigr_billing_city,
                        'billing_county'        => $tbigr_billing_county,
                        'shipping_address'      => $tbigr_shipping_address,
                        'shipping_city'          => $tbigr_shipping_city,
                        'shipping_county'        => $tbigr_shipping_county,
                        'person_type'   => $tbigr_person_type,
                        'net_income'    => $tbigr_net_income,
                        'instalments'    => $tbigr_instalments
                    ),
                    'items' => $tbigr_items
                );
                curl_setopt($tbigr_add_ch, CURLOPT_POSTFIELDS, http_build_query($tbigr_post));
                $paramstbigradd=json_decode(curl_exec($tbigr_add_ch), true);
                curl_close($tbigr_add_ch);                
                // Create tbigr order i data base
                
                if (isset($paramstbigradd['status']) && ($paramstbigradd['status'] == 'Yes')){
                    // save to tbiorders file
                    $tbigr_tempcontent = file_get_contents(TBIGR_PLUGIN_DIR . '/keys/tbigrorders.json');
                    if ($tbigr_tempcontent != false){
                        $tbigr_orders = json_decode($tbigr_tempcontent);
                        // test over 1000
                        if (is_array($tbigr_orders) && (count($tbigr_orders) >= 1000)){
                            array_shift($tbigr_orders);
                        }
                        $tbigr_order_current = array(
                            "order_id" => $order_id,
                            "order_status" => "Draft"
                        );
                        $key = array_search($order_id, array_map(
                            function($o) {
                                return $o->order_id;
                            }, 
                            $tbigr_orders));
                        if ($key === false){
                            array_push($tbigr_orders, $tbigr_order_current);
                        }
                        $jsondata = json_encode($tbigr_orders);
                        file_put_contents(TBIGR_PLUGIN_DIR . '/keys/tbigrorders.json', $jsondata);
                    }
                    
                    // send to IRIS
                    $tbigriris_post = array(
                        'currency'          => 'EUR',
                        'description'          => 'TBI IRIS Payment',
                        'hookUrl'              => $tbigr_url . '?order=' . $paramstbigradd['newid'],
                        'name'               => '',
                        'redirectUrl'        => wc_get_checkout_url(),
                        'sum'                => $tbigr__price,
                        'toIban'             => $tbigr_iris_iban
                    );
                    
                    if ($paramstbigr['iris_testenv'] == 1){
                        $tbigriris_envurl = $paramstbigr['iris_testurl'];
                    }else{
                        $tbigriris_envurl = $paramstbigr['iris_liveurl'];
                    }
                    
                    $tbi_pause_txt = $paramstbigr['tbi_pause_txt'];
                    
                    $tbigriris_plaintext = json_encode($tbigriris_post, JSON_UNESCAPED_UNICODE);
                    
                    $tbigr_iris_key = (string)get_option('credittbigr_iris_key');
                    
                    $curl_application = curl_init();
                        curl_setopt_array($curl_application, array(
                        CURLOPT_URL => $tbigriris_envurl . '/' . $tbigr_iris_key,
                        CURLOPT_RETURNTRANSFER => true,
                        CURLOPT_ENCODING => '',
                        CURLOPT_MAXREDIRS => 4,
                        CURLOPT_TIMEOUT => 0,
                        CURLOPT_FOLLOWLOCATION => true,
                        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                        CURLOPT_CUSTOMREQUEST => 'POST',
                        CURLOPT_POSTFIELDS => $tbigriris_plaintext,
                        CURLOPT_HTTPHEADER => array(
                            'Content-Type: application/json'
                        ),
                    ));
                    $response_application = curl_exec($curl_application);
                    curl_close($curl_application);
                    $tbigriris_result = json_decode($response_application);
                    if ($tbigriris_result->paymentLink != null){
                        $tbigriris_url = $tbigriris_result->paymentLink;
                        WC()->cart->empty_cart();
                        return array(
                            'result'    => 'success',
                            'redirect'  => esc_url_raw($tbigriris_url)
                        );
                    }else{
                        wc_add_notice(__( 'You cannot create an order in IRIS. Please try again later.', 'tbicreditgr' ), 'error');
                        return NULL;
                    }
                }
            }            
        }        
    }
    
    /** payment gateway **/
    function add_tbigr_gateway_class( $methods ) {
        $methods[] = 'WC_Gateway_tbigr_Gateway';
        $methods[] = 'WC_Gateway_tbiiris_Gateway';
        return $methods;
    }
    
    /** vizualize credit button */
    function tbigr_credit_button() {
        $tbigr_hide = intval(get_option('tbigr_hide'));
        if ($tbigr_hide === 0){
            global $product;
            $tbigr__price = 0;
            $tbigr_unicid = (string)get_option("tbigr_unicid");
            $tbigr_ch = curl_init();
            curl_setopt($tbigr_ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($tbigr_ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($tbigr_ch, CURLOPT_URL, TBIGR_LIVEURL . '/function/getparameters.php?cid='.$tbigr_unicid);
            $paramstbigr = json_decode(curl_exec($tbigr_ch), true);
            curl_close($tbigr_ch);
            
            if (!empty($paramstbigr)){
                $tbigr__minprice = $paramstbigr['tbi_minstojnost'];
                $tbigr_tbi_btn_theme = $paramstbigr['tbi_btn_theme'];
                $tbigr__price = $product->get_price();
                $tbigr_tbi_custom_button_status = $paramstbigr['tbi_custom_button_status'];
                
                $tbi_btn_color = '#e55a00;';
                if ($paramstbigr['tbi_btn_theme'] == 'tbi'){
                    $tbi_btn_color = '#e55a00;';
                }
                if ($paramstbigr['tbi_btn_theme'] == 'tbi2'){
                    $tbi_btn_color = '#00368a;';
                }
                if ($paramstbigr['tbi_btn_theme'] == 'tbi3'){
                    $tbi_btn_color = '#2b7953;';
                }
                if ($paramstbigr['tbi_btn_theme'] == 'tbi4'){
                    $tbi_btn_color = '#848789;';
                }
                
                if ($tbigr__price == 0){
                    $tbigr_is_empty = true;
                }else{
                    $tbigr_is_empty = false;
                }
                
                if ($paramstbigr['tbi_status'] == 'Yes'){
                    $tbigr_is_active = true;
                }else{
                    $tbigr_is_active = false;
                }
                
                if ($tbigr__price >= $tbigr__minprice){
                    $tbigr_is_visible = true;
                }else{
                    $tbigr_is_visible = false;
                }
                $tbigr_zaiavka1tbi_text = $paramstbigr['tbi_zaglavie'];
                $tbigr_zaiavka2tbi_text = $paramstbigr['tbi_opisanie'];
                $tbigr_zaiavka3tbi_text = $paramstbigr['tbi_product'];
                $tbigr_vnoska = $paramstbigr['tbi_vnoska'];
                if ($paramstbigr['tbi_backurl'] != ''){
                    if (preg_match("#https?://#", $paramstbigr['tbi_backurl']) === 0) {
                        $tbigr_backurl = 'http://'.$paramstbigr['tbi_backurl'];
                    }else{
                        $tbigr_backurl = $paramstbigr['tbi_backurl'];
                    }
                }else{
                    $tbigr_backurl = '';
                }
                
                $tbi_divider = floatval($paramstbigr['tbi_divider']);
                if ($tbigr__price < $tbi_divider){
                    $tbi_rate = floatval($paramstbigr['tbi_rate']);
                    $tbi_commission = floatval($paramstbigr['tbi_commission']);
                    $tbi_insurance = floatval($paramstbigr['tbi_insurance']);
                    $tbi_months = intval($paramstbigr['tbi_months']);            
                }else{
                    $tbi_rate = floatval($paramstbigr['tbi_rate2']);
                    $tbi_commission = floatval($paramstbigr['tbi_commission2']);
                    $tbi_insurance = floatval($paramstbigr['tbi_insurance2']);
                    $tbi_months = intval($paramstbigr['tbi_months2']);            
                }
                        
                if ($tbi_rate == 0){
                    $tbi_rate = 1;
                }
                
                $tbigr_mesecna = tbigr_PMT(($tbi_rate / 100) / 12, $tbi_months,  - ($tbigr__price + $tbi_commission) * (1 + $tbi_insurance * $tbi_months));
                
                $tbigr_btnvisible = $paramstbigr['tbi_btnvisible'];
                if ($tbigr_btnvisible == 'Yes'){
                    if (!$tbigr_is_empty) {
                        if ($tbigr_is_active) {
                            if (($tbigr_zaiavka1tbi_text != '') || ($tbigr_zaiavka2tbi_text != '') || ($tbigr_zaiavka3tbi_text != '')) {
                                echo "<br /><span style=\"font-size:22px;font-weight:bold;\">$tbigr_zaiavka1tbi_text</span> <span style=\"font-size:18px;\">$tbigr_zaiavka2tbi_text</span> $tbigr_zaiavka3tbi_text";
                            }
                            if ($tbigr_is_visible) {
                                if ($tbigr_tbi_custom_button_status == 'Yes') {
                                    if ($tbigr_vnoska == 'Yes'){
                                        echo '<table border="0" style="max-width:400px;">';
                                        echo '<tr>';
                                        echo '<td style="padding-right:5px;padding-bottom:5px;">';
                                        if ($tbigr_backurl == ''){
                                            echo "<img id=\"btn_tbigr\" style=\"padding-bottom: 5px;\" src=\"".TBIGR_LIVEURL."/calculators/assets/img/custom_buttons/" . $tbigr_unicid . ".png\" title=\"Credit module tbi bank " . TBIGR_MOD_VERSION . "\" alt=\"Credit module tbi bank " . TBIGR_MOD_VERSION . "\" onmouseover=\"this.src='".TBIGR_LIVEURL."/calculators/assets/img/custom_buttons/" . $tbigr_unicid . "_hover.png'\" onmouseout=\"this.src='".TBIGR_LIVEURL."/calculators/assets/img/custom_buttons/" . $tbigr_unicid . ".png'\">";
                                        }else{
                                            echo "<a href=\"" . $tbigr_backurl . "\" target=\"_blank\" title=\"Go to tbi bank page\"><img id=\"btn_tbigr\" style=\"padding-bottom: 5px;cursor:pointer;\" src=\"".TBIGR_LIVEURL."/calculators/assets/img/custom_buttons/" . $tbigr_unicid . ".png\" title=\"Credit module tbi bank " . TBIGR_MOD_VERSION . "\" alt=\"Credit module tbi bank " . TBIGR_MOD_VERSION . "\" onmouseover=\"this.src='".TBIGR_LIVEURL."/calculators/assets/img/custom_buttons/" . $tbigr_unicid . "_hover.png'\" onmouseout=\"this.src='".TBIGR_LIVEURL."/calculators/assets/img/custom_buttons/" . $tbigr_unicid . ".png'\"></a>";
                                        }
                                        echo '</td>';
                                        echo '</tr>';
                                        echo '<tr>';
                                        echo '<td style="vertical-align:bottom;">';
                                        echo '<p style="color:' . $tbi_btn_color . 'font-size:16pt;font-weight:bold;">' . number_format($tbigr_mesecna, 2, '.', '') . ' ' . __('EUR', 'tbicreditgr') . ' x ' . $tbi_months . ' ' . __('months', 'tbicreditgr') . '</p>';
                                        echo '</td>';
                                        echo '</tr>';
                                        echo '</table>';
                                    }else{
                                        echo "<a href=\"" . $tbigr_backurl . "\" target=\"_blank\" title=\"Go to tbi bank page\"><img id=\"btn_tbigr\" style=\"padding-bottom: 5px;cursor:pointer;\" src=\"".TBIGR_LIVEURL."/calculators/assets/img/custom_buttons/" . $tbigr_unicid . ".png\" title=\"Credit module tbi bank " . TBIGR_MOD_VERSION . "\" alt=\"Credit module tbi bank " . TBIGR_MOD_VERSION . "\" onmouseover=\"this.src='".TBIGR_LIVEURL."/calculators/assets/img/custom_buttons/" . $tbigr_unicid . "_hover.png'\" onmouseout=\"this.src='".TBIGR_LIVEURL."/calculators/assets/img/custom_buttons/" . $tbigr_unicid . ".png'\"></a>";
                                    }
                                    }else{
                                    if ($tbigr_vnoska == 'Yes'){
                                        echo '<table border="0" style="max-width:400px;">';
                                        echo '<tr>';
                                        echo '<td style="padding-right:5px;padding-bottom:5px;">';
                                        if ($tbigr_backurl == ''){
                                            echo "<img id=\"btn_tbigr\" style=\"padding-bottom: 5px;\" src=\"".TBIGR_LIVEURL."/calculators/assets/img/buttons/" . $tbigr_tbi_btn_theme . ".png\" title=\"Credit module tbi bank " . TBIGR_MOD_VERSION . "\" alt=\"Credit module tbi bank " . TBIGR_MOD_VERSION . "\" onmouseover=\"this.src='".TBIGR_LIVEURL."/calculators/assets/img/buttons/" . $tbigr_tbi_btn_theme . "-hover.png'\" onmouseout=\"this.src='".TBIGR_LIVEURL."/calculators/assets/img/buttons/" . $tbigr_tbi_btn_theme . ".png'\">";
                                        }else{
                                            echo "<a href=\"" . $tbigr_backurl . "\" target=\"_blank\" title=\"Go to tbi bank page\"><img id=\"btn_tbigr\" style=\"padding-bottom: 5px;cursor:pointer;\" src=\"".TBIGR_LIVEURL."/calculators/assets/img/buttons/" . $tbigr_tbi_btn_theme . ".png\" title=\"Credit module tbi bank " . TBIGR_MOD_VERSION . "\" alt=\"Credit module tbi bank " . TBIGR_MOD_VERSION . "\" onmouseover=\"this.src='".TBIGR_LIVEURL."/calculators/assets/img/buttons/" . $tbigr_tbi_btn_theme . "-hover.png'\" onmouseout=\"this.src='".TBIGR_LIVEURL."/calculators/assets/img/buttons/" . $tbigr_tbi_btn_theme . ".png'\"></a>";
                                        }
                                        echo '</td>';
                                        echo '</tr>';
                                        echo '<tr>';
                                        echo '<td style="vertical-align:bottom;">';
                                        echo '<p style="color:' . $tbi_btn_color . 'font-size:16pt;font-weight:bold;">' . number_format($tbigr_mesecna, 2, '.', '') . ' ' . __('EUR', 'tbicreditgr') . ' x ' . $tbi_months . ' ' . __('months', 'tbicreditgr') . '</p>';
                                        echo '</td>';
                                        echo '</tr>';
                                        echo '</table>';
                                    }else{
                                        echo "<a href=\"" . $tbigr_backurl . "\" target=\"_blank\" title=\"Go to tbi bank page\"><img id=\"btn_tbigr\" style=\"padding-bottom: 5px;cursor:pointer;\" src=\"".TBIGR_LIVEURL."/calculators/assets/img/buttons/" . $tbigr_tbi_btn_theme . ".png\" title=\"Credit module tbi bank " . TBIGR_MOD_VERSION . "\" alt=\"Credit module tbi bank " . TBIGR_MOD_VERSION . "\" onmouseover=\"this.src='".TBIGR_LIVEURL."/calculators/assets/img/buttons/" . $tbigr_tbi_btn_theme . "-hover.png'\" onmouseout=\"this.src='".TBIGR_LIVEURL."/calculators/assets/img/buttons/" . $tbigr_tbi_btn_theme . ".png'\"></a>";
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }
    }
    
    function tbigr_add_query_vars_filter( $vars ){
        $vars[] = "product";
        $vars[] = "products_price";
        $vars[] = "vnoski";
        $vars[] = "redoven";
        return $vars;
    }
    
    function tbigr_wordpress_get_params($param = null,$null_return = null){
        if ($param){
            $value = (!empty($_POST[$param]) ? trim(esc_sql($_POST[$param])) : (!empty($_GET[$param]) ? trim(esc_sql($_GET[$param])) : $null_return ));
            return $value;
            } else {
            $params = array();
            foreach ($_POST as $key => $param) {
                $params[trim(esc_sql($key))] = (!empty($_POST[$key]) ? trim(esc_sql($_POST[$key])) :  $null_return );
            }
            foreach ($_GET as $key => $param) {
                $key = trim(esc_sql($key));
                if (!isset($params[$key])) { // if there is no key or it's a null value
                    $params[trim(esc_sql($key))] = (!empty($_GET[$key]) ? trim(esc_sql($_GET[$key])) : $null_return );
                }
            }
            return $params;
        }
    }    
    
    function tbigr_add_meta() {
        //register css-s
        wp_enqueue_style( 'tbigr_style', plugin_dir_url( __FILE__ ) . '../css/tbi_style.css', array(), TBIGR_MOD_VERSION, 'all');
        wp_enqueue_script( 'tbigr_credit', plugin_dir_url( __FILE__ ) . '../js/tbicredit.js', false, TBIGR_MOD_VERSION);
    }
    
    function tbigr_reklama() {
        $tbigr_hide = intval(get_option('tbigr_hide'));
        if ($tbigr_hide === 0){
            $o = '';
            if ( is_front_page() ) {    
                $tbigr_unicid = (string)get_option("tbigr_unicid");
                $tbigr_ch = curl_init();
                curl_setopt($tbigr_ch, CURLOPT_SSL_VERIFYPEER, false);
                curl_setopt($tbigr_ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($tbigr_ch, CURLOPT_URL, TBIGR_LIVEURL . '/function/getparameters.php?cid='.$tbigr_unicid);
                $paramstbigr=json_decode(curl_exec($tbigr_ch), true);
                curl_close($tbigr_ch);
                if (!empty($paramstbigr['tbi_container_status']) && $paramstbigr['tbi_container_status'] == 'Yes' && !empty($paramstbigr['tbi_status']) && $paramstbigr['tbi_status'] == 'Yes'){
                    $o .= '<div class="tbigr_float" onclick="tbigrChangeContainer();">';
                    $o .= '<img src="'.TBIGR_LIVEURL.'/dist/img/tbi_logo.png" class="tbigr-my-float">';
                    $o .= '</div>';
                    $o .= '<div class="tbigr-label-container">';
                    $o .= '<i class="fa fa-play fa-rotate-180 tbigr-label-arrow"></i>';
                    $o .= '<div class="tbigr-label-text">';
                    $o .= '<div style="padding-bottom:5px;"></div>';
                    $o .= '<img src="'.TBIGR_LIVEURL.'/calculators/assets/img/tbim' . $paramstbigr['tbi_container_reklama'] . '.png">';
                    $o .= '<div style="font-size:16px;padding-top:3px;">' . $paramstbigr['tbi_container_txt1'] . '</div>';
                    $o .= '<p style="font-size:14px;">' . $paramstbigr['tbi_container_txt2'] . '</p>';
                    $o .= '<div class="tbi-label-text-a"><a href="'.TBIGR_LIVEURL.'/calculators/assets/img/Procedura%20Online%20TBI%20Bank-2017.pdf" target="_blank" alt="' . __('CREDIT ONLINE INFORMATION WITH TBI BANK', 'tbicreditgr') . '">' . __('CREDIT ONLINE INFORMATION WITH TBI BANK!', 'tbicreditgr') . '</a></div>';
                    $o .= '</div>';
                    $o .= '</div>';
                }
            }
            echo $o;
        }
    } 
    
    add_action( 'wp_ajax_tbigr_updateorder', 'tbigr_updateorder' );
    add_action( 'wp_ajax_nopriv_tbigr_updateorder', 'tbigr_updateorder' );
    
    function tbigr_updateorder() {
        $json = array();
        $json['success'] = 'unsuccess';
        
        $tbigr_unicid = (string)get_option("tbigr_unicid");
        
        if (isset($_REQUEST['order_id'])) {
            $tbigr_order_id = $_REQUEST['order_id'];
        } else {
            $tbigr_order_id = '';
        }
        
        if (isset($_REQUEST['status'])) {
            $tbigr_status = $_REQUEST['status'];
        } else {
            $tbigr_status = '';
        }
        
        if (isset($_REQUEST['calculator_id'])) {
            $tbigr_calculator_id = $_REQUEST['calculator_id'];
        } else {
            $tbigr_calculator_id = '';
        }
        
        if (($tbigr_calculator_id != '') && ($tbigr_unicid == $tbigr_calculator_id)){
            if (file_exists(TBIGR_PLUGIN_DIR . '/keys/tbigrorders.json')) {
                $orderdata = file_get_contents(TBIGR_PLUGIN_DIR . '/keys/tbigrorders.json');
                $tbigr_orderdata_all = json_decode($orderdata, true);
                foreach ($tbigr_orderdata_all as $key => $value){
                    if ($tbigr_orderdata_all[$key]['order_id'] == $tbigr_order_id){
                        $tbigr_orderdata_all[$key]['order_status'] = $tbigr_status;
                    }
                }
                $jsondata = json_encode($tbigr_orderdata_all);
                file_put_contents(TBIGR_PLUGIN_DIR . '/keys/tbigrorders.json', $jsondata);
                $json['success'] = 'success';
            }
        }
        
        $json['tbigr_order_id'] = $tbigr_order_id;
        $json['tbigr_status'] = $tbigr_status;
        $json['tbigr_calculator_id'] = $tbigr_calculator_id;
        
        echo (json_encode($json));
        die();
    }