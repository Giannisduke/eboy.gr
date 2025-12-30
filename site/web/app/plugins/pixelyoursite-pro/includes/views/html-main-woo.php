<?php

namespace PixelYourSite;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

use PixelYourSite\Facebook\Helpers;
use PixelYourSite\Ads\Helpers as AdsHelpers;

?>

<h2 class="section-title">WooCommerce Settings</h2>

<!-- Enable WooCommerce -->
<div class="card card-static">
    <div class="card-body">
        <div  class="row">
            <div class="col">
                <?php PYS()->render_switcher_input( 'woo_advance_purchase_fb_enabled' ); ?>
                <h4 class="switcher-label">Facebook Advanced Purchase Tracking</h4>
            </div>
        </div>
        <div  class="row">
            <div class="col">
                <?php PYS()->render_switcher_input( 'woo_advance_purchase_ga_enabled' ); ?>
                <h4 class="switcher-label">Google Analytics Advanced Purchase Tracking</h4>
            </div>
        </div>
		<?php if ( Tiktok()->enabled() ) : ?>
            <div  class="row">
                <div class="col">
                    <?php Tiktok()->render_switcher_input( 'woo_advance_purchase_enabled' ); ?>
                    <h4 class="switcher-label">TikTok Advanced Purchase Tracking</h4>
                </div>
            </div>
        <?php endif; ?>
		<?php if ( Pinterest()->enabled() ) : ?>
            <div  class="row">
                <div class="col">
					<?php Pinterest()->render_switcher_input( 'woo_advance_purchase_enabled' ); ?>
                    <h4 class="switcher-label">Pinterest Advanced Purchase Tracking</h4>
                </div>
            </div>
		<?php endif; ?>
        <p class="small">
            If the default Purchase event doesn't fire when an order is placed by the client, a Purchase event will be sent to Meta and Google using API when the order status is changed to "Completed". Meta Conversion API token and GA4 Measurement Protocol secret are required.
        </p>
        <div  class="row">
            <div class="col">
                <?php PYS()->render_switcher_input( 'woo_track_refunds_GA' ); ?>
                <h4 class="switcher-label">Track refunds on Google Analytics</h4>
            </div>
        </div>
        <p class="small">
            A "Refund" event will be sent to Google via the API when the order status changes to "Refund". GA4 measurement protocol secret required.
        </p>
    </div>
</div>
<div class="card card-static">
    <div class="card-header">
        General
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col">
                <p>Fire e-commerce related events. Meta, TikTok, Google Ads, Bing (paid add-on), and Pinterest (paid add-on) events are Dynamic Ads Ready. Monetization data is sent to Google Analytics.</p>
            </div>
        </div>

        <div class="row mt-2">
            <div class="col">
                <?php PYS()->render_switcher_input( 'woo_enabled_save_data_to_orders' ); ?>
                <h4 class="switcher-label">Enable WooCommerce Reports</h4>
                <small class="form-check">Save the <i>landing page, UTMs, client's browser's time, day, and month, the number of orders, lifetime value, and average order</i>.
                    You can view this data when you open an order, or on the WooCommerce <a href="<?=admin_url("admin.php?page=pixelyoursite_woo_reports")?>">Reports page</a></small>
            </div>
        </div>
        <div class="row mt-2">
            <div class="col">
                <?php PYS()->render_switcher_input('woo_add_enrich_to_admin_email'); ?>
                <h4 class="switcher-label">Send reports data to the New Order email</h4>
                <small>You will see the landing page, UTMs, client's browser's time, day, and month, the number of orders, lifetime value, and average order in your WooCommerce's default "New Order" email.
                    Your clients will NOT get this info.</small>
            </div>
        </div>


        <div class="row mt-2">
            <div class="col">
                <?php PYS()->render_switcher_input( 'woo_enabled_display_data_to_orders' ); ?>
                <h4 class="switcher-label">Display the tracking data on the order's page</h4>
                <small class="form-check">Show the <i>landing page, traffic source,</i> and <i>UTMs</i> on the order's edit page.</small>
            </div>
        </div>
        <div class="row mt-2">
            <div class="col">
                <?php PYS()->render_switcher_input( 'woo_enabled_save_data_to_user' ); ?>
                <h4 class="switcher-label">Display orders data on the user's profile page</h4>
                <small class="form-check">Display <i>the number of orders, lifetime value, and average order</i>.</small>
            </div>
        </div>
        <div class="row mt-2">
            <div class="col">
                <?php PYS()->render_switcher_input( 'woo_enabled_show_tracking_type' ); ?>
                <h4 class="switcher-label">Show tracking type</h4>
                <small class="form-check">Show the tracking type in the orders table and on the on the order's page.</small>
            </div>
        </div>
        <hr>
        <div class="row mt-3">
            <div class="col-11">
                <label class="label-inline">New customer parameter</label>
            </div>
        </div>
        <div class="row">
            <div class="col">
                <small>The new_customer parameter is added to the purchase event for our Google native tags and for GTM. It's use by Google for new customer acquisition. We always send it with true or false values for logged-in users. We will use these options for guest checkout.</small>
                <div>
                    <div class="collapse-inner">
                        <div class="custom-controls-stacked">
                            <?php PYS()->render_radio_input( 'woo_purchase_new_customer_guest', 'yes',
                                'Send it for guest checkout' ); ?>
                            <?php PYS()->render_radio_input( 'woo_purchase_new_customer_guest', 'no',
                                'Don\'t send it for guest checkout' ); ?>

                        </div>
                    </div>
                </div>
            </div>
        </div>
        <hr>

        <div class="row mt-2">
            <div class="col">
                <label class="mb-2">If the Purchase event doesn't work correctly, add your Checkout page(s) ID(s) here:</label>
                <?php PYS()->render_tags_select_input("woo_checkout_page_ids"); ?>
                <small class="form-check">Don't add the Checkout page IDs if you use Stripe or Klarna because conflicts are possible.</small>
            </div>
        </div>


 </div>
</div>
<!-- video -->
<div class="card card-static">
    <div class="card-header">
        Recommended Videos:
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col">
                <p>WooCommerce AddToCart Event FIX (4:46 min) - <a href="https://www.youtube.com/watch?v=oZoAu8a0PNg" target="_blank">watch now</a></p>
                <p>Analyse your WooCommerce data with ChatGPT (12:06) - <a href="https://www.youtube.com/watch?v=FjGJYAdZEKc" target="_blank">watch video</a></p>
                <p>Enhanced Conversions for Google Ads with PixelYourSite (9:14) - <a href="https://www.youtube.com/watch?v=-bN5D_HJyuA" target="_blank">watch now</a></p>
                <p>Google Analytic 4 (GA4) & WooCommerce: Transaction Reports (6:51) - <a href="https://www.youtube.com/watch?v=zLtXHbp_DDU" target="_blank">watch now</a></p>
                <p>Google Analytics 4 (GA4) FUNNELS for WooCommerce (6:13)  - <a href="https://www.youtube.com/watch?v=c6L1XMYzuMM" target="_blank">watch now</a></p>
                <p>Same Facebook (Meta) pixel or Google tag on multiple WooCommerce websites? (4:43) - <a href="https://www.youtube.com/watch?v=3Ugwlq1EVO4" target="_blank">watch now</a></p>
                <p>WooCommerce First-Party Reports: Track UTMs, Traffic Source, Landing Page (13:15) - <a href="https://www.youtube.com/watch?v=4VpVf9llfkU" target="_blank">watch video</a></p>
                <p>Find out your ads PROFIT - Meta, Google, TikTok, Pinterest, etc (5:48) - <a href="https://www.youtube.com/watch?v=ydqyp-iW9Ko" target="_blank">watch video</a></p>
                <p>How to track WooCommerce BRANDS on Google Analytics 4 (GA4) (4:05) - <a href="https://www.youtube.com/watch?v=7B8uU3p_mjw" target="_blank">watch now</a></p>
                <p>How to track WooCommerce VARIABLE products on Google Analytics 4 (GA4) (4:21) - <a href="https://www.youtube.com/watch?v=LZtw6HxbFRg" target="_blank">watch now</a></p>
                <p>WooCommerce LISTS tracking on GA4 (6:49) - <a href="https://www.youtube.com/watch?v=8CKu2krVpyA" target="_blank">watch now</a></p>

            </div>
        </div>
    </div>
</div>
<!--  Brand -->
<div class="card ">
    <div class="card-header">
        Brand tracking for Google Analytics<?php cardCollapseBtn(); ?>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-11 form-inline">
                <?php PYS()->render_switcher_input( 'enable_woo_brand' ); ?>
                <h4 class="switcher-label">Enable Brand tracking</h4>
                <div class="col-11 form-inline" style="margin-bottom: 20px; margin-top: 20px;">
                    <label>Brand taxonomy</label>
                    <?php PYS()->render_group_select_brand_taxonomy( 'woo_brand_taxonomy',
                        PYS()->get_object_taxonomies_for_brand(), false ); ?>
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col">
                <div class="custom-controls-stacked">
                    <input type="hidden" name="pys[core][woo_brand_taxonomy_plugin][]" value="">
                    <?php PYS()->render_checkbox_input_array_brand( 'woo_brand_taxonomy_plugin', 'Try to auto-detect the "brand" field', 'autodetect'); ?>
                    <?php if ( is_plugin_active( PYS_BRAND_PYS_PCF ) ){?>
                        <?php PYS()->render_checkbox_input_array_brand( 'woo_brand_taxonomy_plugin', 'Product Catalog Feed for WooCommerce plugin detected, use it when possible', 'PYS_BRAND_PYS_PCF'); ?>
                    <?php }?>
                    <?php if ( is_plugin_active( PYS_BRAND_YWBA ) ){?>
                        <?php PYS()->render_checkbox_input_array_brand( 'woo_brand_taxonomy_plugin', 'YITH WooCommerce Brands Add-on plugin detected, use it when possible', 'PYS_BRAND_YWBA'); ?>
                    <?php }?>
                    <?php if ( is_plugin_active( PYS_BRAND_PEWB ) ){?>
                        <?php PYS()->render_checkbox_input_array_brand( 'woo_brand_taxonomy_plugin', 'Perfect WooCommerce Brands. Use it when possible', 'PYS_BRAND_PEWB'); ?>
                    <?php }?>
                    <?php if ( is_plugin_active( PYS_BRAND_PRWB ) ){?>
                        <?php PYS()->render_checkbox_input_array_brand( 'woo_brand_taxonomy_plugin', 'Premmerce WooCommerce Brands. Use it when possible', 'PYS_BRAND_PRWB'); ?>
                    <?php }?>
                    <?php if ( is_plugin_active( PYS_BRAND_PBFW ) || is_plugin_active(PYS_BRAND_WB)){?>
                        <?php PYS()->render_checkbox_input_array_brand( 'woo_brand_taxonomy_plugin', 'Product Brands For WooCommerce. Use it when possible', 'PYS_BRAND_PBFW'); ?>
                    <?php }?>
                </div>
            </div>
        </div>
    </div>
</div>

<!--  Transaction ID -->
<div class="card ">
    <div class="card-header">
        Transaction ID<?php cardCollapseBtn(); ?>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-5 form-inline">
                <label>Prefix: </label><?php PYS()->render_text_input("woo_order_id_prefix","Prefix"); ?>
            </div>
        </div>
        <div class="row mt-3">
            <div class="col">
                <p>Consider adding a prefix for transactions IDs if you use the same tags on multiple websites.</p>
            </div>
        </div>
    </div>
</div>
<!-- AddToCart -->
<div class="card ">
    <div class="card-header">
        When to fire the add to cart event <?php cardCollapseBtn(); ?>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col">
                <div class="custom-controls-stacked">
                    <?php PYS()->render_checkbox_input( 'woo_add_to_cart_on_button_click', 'On Add To Cart button clicks' ); ?>
                    <?php PYS()->render_checkbox_input( 'woo_add_to_cart_on_cart_page', 'On the Cart Page' ); ?>
                    <?php PYS()->render_checkbox_input( 'woo_add_to_cart_on_checkout_page', 'On Checkout Page' ); ?>
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col  form-inline">
                <label>Change this if the AddToCart event doesn't fire</label>
                <?php PYS()->render_select_input( 'woo_add_to_cart_catch_method',
                    array('add_cart_hook'=>"WooCommerce hooks",'add_cart_js'=>"Button's classes",) ); ?>
            </div>
        </div>
    </div>
</div>
<!-- Event Value -->
<div class="card ">
    <div class="card-header">
        Value Settings <?php cardCollapseBtn(); ?>
    </div>
    <div class="card-body">
        <div class="row mb-3">
            <div class="col">
                <div class="custom-controls-stacked">
                    <?php PYS()->render_radio_input( 'woo_event_value', 'price', 'Use WooCommerce price settings' ); ?>
                    <?php PYS()->render_radio_input( 'woo_event_value', 'custom', 'Customize Tax and Shipping' ); ?>
                </div>
            </div>
        </div>
        <div class="row mb-3 woo-event-value-option" style="display: none;">
            <div class="col col-offset-left form-inline">
                <?php PYS()->render_select_input( 'woo_tax_option',
                    array(
                        'included' => 'Include Tax',
                        'excluded' => 'Exclude Tax',
                    )
                ); ?>
                <label>and</label>
                <?php PYS()->render_select_input( 'woo_shipping_option',
                    array(
                        'included' => 'Include Shipping',
                        'excluded' => 'Exclude Shipping',
                    )
                ); ?>
                <label>and</label>
                <?php PYS()->render_select_input( 'woo_fees_option',
                    array(
                        'included' => 'Include Fees',
                        'excluded' => 'Exclude Fees',
                    )
                ); ?>
            </div>
        </div>

        <div class="row">
            <div class="col">
                <h4 class="label">Lifetime Customer Value</h4>
                <?php PYS()->render_multi_select_input( 'woo_ltv_order_statuses', wc_get_order_statuses() ); ?>
            </div>
        </div>
    </div>
</div>

<h2 class="section-title">ID Settings</h2>

<!-- Facebook for WooCommerce -->
<?php if ( Facebook()->enabled() && Helpers\isFacebookForWooCommerceActive() ) : ?>

<!-- @todo: add notice output -->
<!-- @todo: add show/hide facebook content id section JS -->
<div class="card card-static">
    <div class="card-header">
        Facebook for WooCommerce Integration
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col">
                <p><strong>It looks like you're using both PixelYourSite and Facebook for WooCommerce Extension. Good, because
                they can do a great job together!</strong></p>
                <p>Facebook for WooCommerce Extension is a useful free tool that lets you import your products to a Facebook
                    shop and adds a very basic Meta Pixel (formerly Facebook Pixel) on your site. PixelYourSite is a dedicated plugin that
                supercharges your Meta Pixel (formerly Facebook Pixel) with extremely useful features.</p>
                <p>We made it possible to use both plugins together. You just have to decide what ID to use for your events.</p>
            </div>
        </div>
        <div class="row mb-3">
            <div class="col">
                <div class="custom-controls-stacked">
                    <?php Facebook()->render_radio_input( 'woo_content_id_logic', 'facebook_for_woocommerce', 'Use Facebook for WooCommerce extension content_id logic' ); ?>
                    <?php Facebook()->render_radio_input( 'woo_content_id_logic', 'default', 'Use PixelYourSite content_id logic' ); ?>
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col">
                <p><em>* If you plan to use the product catalog created by Facebook for WooCommerce Extension, use the
                    Facebook for WooCommerce Extension ID. If you plan to use older product catalogs, or new ones created
                with other plugins, it's better to keep the default PixelYourSite settings.</em></p>
            </div>
        </div>
    </div>
</div>

<?php endif; ?>



<?php if ( Facebook()->enabled() ) : ?>

<?php
    $facebook_id_visibility = Helpers\isDefaultWooContentIdLogic() ? 'block' : 'none';
    $isExpand = Helpers\isFacebookForWooCommerceActive();
    ?>

<div class="card" id="pys-section-facebook-id" style="display: <?=$facebook_id_visibility ?>;">
    <div class="card-header">
        Facebook ID setting <?php cardCollapseBtn(); ?>
    </div>
    <div class="card-body <?=$isExpand ? 'show' : ''?>" style="display: <?=$isExpand ? 'block' : 'none'?>">
        <div class="row mb-3">
            <div class="col">
                <?php Facebook()->render_switcher_input( 'woo_variable_as_simple' ); ?>
                <h4 class="switcher-label">Treat variable products like simple products</h4>
                <p class="mt-3">If you enable this option, the main ID will be used instead of the variation ID. Turn this option ON when your Product Catalog doesn't include the variants for variable
                products.</p>
            </div>
        </div>
        <div class="row mb-3">
            <div class="col">
                <?php Facebook()->render_switcher_input( 'woo_variable_data_select_product' ); ?>
                <h4 class="switcher-label">For product pages, track the variation data when a variation is selected</h4>
            </div>
        </div>
        <div class="row mb-3">
            <div class="col col-offset-left form-inline">
                <label>content_id</label>
                <?php Facebook()->render_select_input( 'woo_content_id',
                    array(
                        'product_id' => 'Product ID',
                        'product_sku'   => 'Product SKU',
                    )
                ); ?>
            </div>
        </div>
        <div class="row mb-3">
            <div class="col col-offset-left form-inline">
                <label>content_id prefix</label><?php Facebook()->render_text_input( 'woo_content_id_prefix', '(optional)' ); ?>
            </div>
        </div>
        <div class="row">
            <div class="col col-offset-left form-inline">
                <label>content_id suffix</label><?php Facebook()->render_text_input( 'woo_content_id_suffix', '(optional)' ); ?>
            </div>
        </div>
        <?php if (isWPMLActive()) : ?>
            <div class="row mb-3 mt-3">
                <div class="col">
                    <p class="mb-3"> WPML Detected! Select your ID logic.</p>
			        <?php Facebook()->render_switcher_input( 'woo_wpml_unified_id' ); ?>
                    <h4 class="switcher-label">WPML Unified ID logic</h4>
                    <p class="mt-3"> If you use localized feeds, enable the unified ID logic for the tag and we will use the native product ID for each translationed item.</p>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>
<?php endif; ?>

<?php if ( GATags()->enabled() ) : ?>

    <div class="card" id="pys-section-ga-id">
        <div class="card-header">
            Google Tags ID settings <?php cardCollapseBtn(); ?>
        </div>
        <div class="card-body">
            <div class="row mb-3">
                <div class="col">
                    <?php GATags()->render_switcher_input( 'woo_variable_as_simple' ); ?>
                    <h4 class="switcher-label">Treat variable products like simple products</h4>
                    <p class="mt-3">If you enable this option, the main ID will be used instead of the variation ID. Turn this option ON when your Merchant Catalog doesn't include the variants for variable products.</p>
                </div>
            </div>
            <div class="row mb-3">
                <div class="col">
                    <?php GATags()->render_switcher_input( 'woo_variable_data_select_product' ); ?>
                    <h4 class="switcher-label">For product pages, track the variation data when a variation is selected</h4>
                </div>
            </div>
            <div class="row mb-3">
                <div class="col-12">
                    <?php GATags()->render_checkbox_input( 'woo_variations_use_parent_name', "When tracking variations, use the parent name" ); ?>
                </div>
            </div>
            <div class="row mb-3">
                <div class="col col-offset-left form-inline">
                    <label>id</label>
                    <?php GATags()->render_select_input( 'woo_content_id',
                        array(
                            'product_id' => 'Product ID',
                            'product_sku'   => 'Product SKU',
                        )
                    ); ?>
                </div>
            </div>
            <div class="row mb-3">
                <div class="col col-offset-left form-inline">
                    <label>id prefix</label><?php GATags()->render_text_input( 'woo_content_id_prefix', '(optional)' ); ?>
                </div>
            </div>
            <div class="row">
                <div class="col col-offset-left form-inline">
                    <label>id suffix</label><?php GATags()->render_text_input( 'woo_content_id_suffix', '(optional)' ); ?>
                </div>
            </div>
	        <?php if (isWPMLActive()) : ?>
                <div class="row mb-3 mt-3">
                    <div class="col">
                        <p class="mb-3"> WPML Detected! Select your ID logic.</p>
				        <?php GATags()->render_switcher_input( 'woo_wpml_unified_id' ); ?>
                        <h4 class="switcher-label">WPML Unified ID logic</h4>
                        <p class="mt-3"> If you use localized feeds, enable the unified ID logic for the tag and we will use the native product ID for each translationed item.</p>
                    </div>
                </div>
	        <?php endif; ?>
        </div>
    </div>
<?php endif; ?>


<?php if ( Tiktok()->enabled() ) : ?>

    <div class="card" id="pys-section-facebook-id" style="display: <?=$facebook_id_visibility ?>;">
        <div class="card-header">
            TikTok ID setting <?php cardCollapseBtn(); ?>
        </div>
        <div class="card-body <?=$isExpand ? 'show' : ''?>" style="display: <?=$isExpand ? 'block' : 'none'?>">
            <div class="row mb-3">
                <div class="col">
                    <?php Tiktok()->render_switcher_input( 'woo_variable_as_simple' ); ?>
                    <h4 class="switcher-label">Treat variable products like simple products</h4>
                    <p class="mt-3">If you enable this option, the main ID will be used instead of the variation ID. Turn this option ON when your Product Catalog doesn't include the variants for variable
                        products. </p>
                </div>
            </div>
            <div class="row mb-3">
                <div class="col">
                    <?php Tiktok()->render_switcher_input( 'woo_variable_data_select_product' ); ?>
                    <h4 class="switcher-label">For product pages, track the variation data when a variation is selected</h4>
                </div>
            </div>
            <div class="row mb-3">
                <div class="col col-offset-left form-inline">
                    <label>content_id</label>
                    <?php Tiktok()->render_select_input( 'woo_content_id',
                        array(
                            'product_id' => 'Product ID',
                            'product_sku'   => 'Product SKU',
                        )
                    ); ?>
                </div>
            </div>
            <div class="row mb-3">
                <div class="col col-offset-left form-inline">
                    <label>content_id prefix</label><?php Tiktok()->render_text_input( 'woo_content_id_prefix', '(optional)' ); ?>
                </div>
            </div>
            <div class="row">
                <div class="col col-offset-left form-inline">
                    <label>content_id suffix</label><?php Tiktok()->render_text_input( 'woo_content_id_suffix', '(optional)' ); ?>
                </div>
            </div>
	        <?php if (isWPMLActive()) : ?>
                <div class="row mb-3 mt-3">
                    <div class="col">
                        <p class="mb-3"> WPML Detected! Select your ID logic.</p>
				        <?php Tiktok()->render_switcher_input( 'woo_wpml_unified_id' ); ?>
                        <h4 class="switcher-label">WPML Unified ID logic</h4>
                        <p class="mt-3"> If you use localized feeds, enable the unified ID logic for the tag and we will use the native product ID for each translationed item.</p>
                    </div>
                </div>
	        <?php endif; ?>
        </div>
    </div>
<?php endif; ?>

<?php if ( Pinterest()->enabled() ) : ?>

    <div class="card" id="pys-section-pinterest-id">
        <div class="card-header">
            Pinterest Tag ID setting <?php cardCollapseBtn(); ?>
        </div>
        <div class="card-body">
            <div class="row mb-3">
                <div class="col">
                    <?php Pinterest()->render_switcher_input( 'woo_variable_as_simple' ); ?>
                    <h4 class="switcher-label">Treat variable products like simple products</h4>
                    <p class="mt-3">If you enable this option, the main ID will be used instead of the variation ID. Turn this option ON when your Product Catalog doesn't include the variants for variable products.</p>
                </div>
            </div>
            <div class="row mb-3">
                <div class="col">
                    <?php Pinterest()->render_switcher_input( 'woo_variable_data_select_product' ); ?>
                    <h4 class="switcher-label">For product pages, track the variation data when a variation is selected</h4>
                </div>
            </div>
            <div class="row mb-3">
                <div class="col col-offset-left form-inline">
                    <label>ID</label>
                    <?php Pinterest()->render_select_input( 'woo_content_id',
                        array(
                            'product_id' => 'Product ID',
                            'product_sku'   => 'Product SKU',
                        )
                    ); ?>
                </div>
            </div>
            <div class="row mb-3">
                <div class="col col-offset-left form-inline">
                    <label>ID prefix</label><?php Pinterest()->render_text_input( 'woo_content_id_prefix', '(optional)' ); ?>
                </div>
            </div>
            <div class="row">
                <div class="col col-offset-left form-inline">
                    <label>ID suffix</label><?php Pinterest()->render_text_input( 'woo_content_id_suffix', '(optional)' ); ?>
                </div>
            </div>
	        <?php if (isWPMLActive() && Pinterest()->getOption('woo_wpml_unified_id') !== NULL) : ?>
                <div class="row mb-3 mt-3">
                    <div class="col">
                        <p class="mb-3"> WPML Detected! Select your ID logic.</p>
				        <?php Pinterest()->render_switcher_input( 'woo_wpml_unified_id' ); ?>
                        <h4 class="switcher-label">WPML Unified ID logic</h4>
                        <p class="mt-3"> If you use localized feeds, enable the unified ID logic for the tag and we will use the native product ID for each translationed item.</p>
                    </div>
                </div>
	        <?php endif; ?>

        </div>
    </div>
<?php else: ?>
<div class="card card-static" id="pys-section-ga-id">
    <div class="card-header">
        Pinterest Tag ID setting
        <a class="pys_external_link" href="https://www.pixelyoursite.com/pinterest-tag?utm_source=pys-free-plugin&utm_medium=pinterest-badge&utm_campaign=requiere-free-add-on" target="_blank">Requires paid add-on <i class="fa fa-external-link"></i></a>
    </div>
</div>
<?php endif; ?>

<!-- @todo: update UI -->
<!-- @todo: hide for dummy Bing -->
<?php if ( Bing()->enabled() ) : ?>
    <div class="card">
        <div class="card-header">
            Bing Tag ID setting <?php cardCollapseBtn(); ?>
        </div>
        <div class="card-body">

            <div class="row mb-3">
                <div class="col">
                    <?php Bing()->render_switcher_input( 'woo_variable_as_simple' ); ?>
                    <h4 class="switcher-label">Treat variable products like simple products</h4>
                    <p class="mt-3">If you enable this option, the main ID will be used instead of the variation ID. Turn this option ON when your Product Catalog doesn't include the variants for variable products.</p>
                </div>
            </div>
            <div class="row mb-3">
                <div class="col">
                    <?php Bing()->render_switcher_input( 'woo_variable_data_select_product' ); ?>
                    <h4 class="switcher-label">For product pages, track the variation data when a variation is selected</h4>
                </div>
            </div>
            <div class="row mb-3">
                <div class="col col-offset-left form-inline">
                    <label>ID</label>
                    <?php Bing()->render_select_input( 'woo_content_id',
                        array(
                            'product_id' => 'Product ID',
                            'product_sku'   => 'Product SKU',
                        )
                    ); ?>
                </div>
            </div>
            <div class="row mb-3">
                <div class="col col-offset-left form-inline">
                    <label>ID prefix</label><?php Bing()->render_text_input( 'woo_content_id_prefix', '(optional)' ); ?>
                </div>
            </div>
            <div class="row">
                <div class="col col-offset-left form-inline">
                    <label>ID suffix</label><?php Bing()->render_text_input( 'woo_content_id_suffix', '(optional)' ); ?>
                </div>
            </div>
	        <?php if (isWPMLActive() && Bing()->getOption('woo_wpml_unified_id') !== NULL) : ?>
                <div class="row mb-3 mt-3">
                    <div class="col">
                        <p class="mb-3"> WPML Detected! Select your ID logic.</p>
				        <?php Bing()->render_switcher_input( 'woo_wpml_unified_id' ); ?>
                        <h4 class="switcher-label">WPML Unified ID logic</h4>
                        <p class="mt-3"> If you use localized feeds, enable the unified ID logic for the tag and we will use the native product ID for each translationed item.</p>
                    </div>
                </div>
	        <?php endif; ?>
        </div>
    </div>
<?php else : ?>
<div class="card card-static">
    <div class="card-header">
        Bing Tag ID setting
        <a class="pys_external_link" href="https://www.pixelyoursite.com/bing-tag?utm_source=pixelyoursite-free-plugin&utm_medium=plugin&utm_campaign=free-plugin-bing" target="_blank">Requires paid add-on <i class="fa fa-external-link"></i></a>
    </div>
</div>
<?php endif; ?>
<hr>
<?php if ( GTM()->enabled() ) : ?>

    <div class="card" id="pys-section-gtm-id">
        <div class="card-header">
            GTM tag settings <?php cardCollapseBtn(); ?>
        </div>
        <div class="card-body">
            <div class="row mb-3">
                <div class="col">
                    <?php GTM()->render_switcher_input( 'woo_variable_as_simple' ); ?>
                    <h4 class="switcher-label">Treat variable products like simple products</h4>
                    <p class="mt-3">If you enable this option, the main ID will be used instead of the variation ID. Turn this option ON when your Merchant Catalog doesn't include the variants for variable products.</p>
                </div>
            </div>
            <div class="row mb-3">
                <div class="col">
                    <?php GTM()->render_switcher_input( 'woo_variable_data_select_product' ); ?>
                    <h4 class="switcher-label">For product pages, track the variation data when a variation is selected</h4>
                </div>
            </div>
            <div class="row mb-3">
                <div class="col-12">
                    <?php GTM()->render_checkbox_input( 'woo_variations_use_parent_name', "When tracking variations, use the parent name" ); ?>
                </div>
            </div>
            <div class="row mb-3">
                <div class="col col-offset-left form-inline">
                    <label>id</label>
                    <?php GTM()->render_select_input( 'woo_content_id',
                        array(
                            'product_id' => 'Product ID',
                            'product_sku'   => 'Product SKU',
                        )
                    ); ?>
                </div>
            </div>
            <div class="row mb-3">
                <div class="col col-offset-left form-inline">
                    <label>id prefix</label><?php GTM()->render_text_input( 'woo_content_id_prefix', '(optional)' ); ?>
                </div>
            </div>
            <div class="row">
                <div class="col col-offset-left form-inline">
                    <label>id suffix</label><?php GTM()->render_text_input( 'woo_content_id_suffix', '(optional)' ); ?>
                </div>
            </div>
	        <?php if (isWPMLActive()) : ?>
                <div class="row mb-3 mt-3">
                    <div class="col">
                        <p class="mb-3"> WPML Detected! Select your ID logic.</p>
				        <?php GTM()->render_switcher_input( 'woo_wpml_unified_id' ); ?>
                        <h4 class="switcher-label">WPML Unified ID logic</h4>
                        <p class="mt-3"> If you use localized feeds, enable the unified ID logic for the tag and we will use the native product ID for each translationed item.</p>
                    </div>
                </div>
	        <?php endif; ?>
        </div>
    </div>
<?php endif; ?>
<hr>
<!-- Google Dynamic Remarketing Vertical -->
<?php if ( GA()->enabled() || Ads()->enabled() ) : ?>

<div class="card ">
    <div class="card-header">
        Google Dynamic Remarketing Vertical<?php cardCollapseBtn(); ?>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-11">
                <div class="custom-controls-stacked">
                   <?php PYS()->render_radio_input( 'google_retargeting_logic', 'ecomm', 'Use Retail Vertical  (select this if you have access to Google Merchant)' ); ?>
                   <?php PYS()->render_radio_input( 'google_retargeting_logic', 'dynx', 'Use Custom Vertical (select this if Google Merchant is not available for your country)' ); ?>
               </div>
           </div>
           <div class="col-1">
             <?php renderPopoverButton( 'google_dynamic_remarketing_vertical' ); ?>
         </div>
     </div>
 </div>
</div>

<?php endif; ?>

<h2 class="section-title">Recommended events</h2>

<!-- Purchase -->
<div class="card">
    <div class="card-header has_switch">
        <?php PYS()->render_switcher_input( 'woo_purchase_enabled' ); ?>Track Purchases <?php cardCollapseBtn(); ?>
    </div>
    <div class="card-body">
	    <?php PYS()->renderValueOptionsBlock('woo_purchase', false);?>
        <hr>
        <div class="row mb-1">
            <div class="col-11">
               <?php PYS()->render_checkbox_input( 'woo_purchase_on_transaction', 'Fire the event on transaction only' ); ?>
           </div>
           <div class="col-1">
              <?php renderPopoverButton( 'woo_purchase_on_transaction' ); ?>
          </div>
        </div>
        <div class="row mb-3">
            <div class="col-12">
                <?php PYS()->render_checkbox_input( 'woo_purchase_not_fire_for_zero', "Don't fire the event for 0 value transactions" ); ?>
            </div>
        </div>
        <div class="row mb-3">
            <div class="col-12">
                <?php PYS()->render_checkbox_input( 'woo_purchase_not_fire_for_zero_items', "Don't fire the event when the number of items is 0" ); ?>
            </div>
        </div>
        <div class="row mb-1">
            <div class="col">
                <label>Fire the Purchase Event for the following order status:</label>
                <div class="custom-controls-stacked mb-2">
                    <?php
                        $statuses = wc_get_order_statuses();
                        foreach ( $statuses as $status => $status_name) {
                            PYS()->render_checkbox_input_revert_array( 'woo_order_purchase_disabled_status', esc_html( $status_name ),esc_attr( $status ));
                        }
                    ?>
                </div>
                <label>The Purchase event fires when the client makes a transaction on your website. It won't fire on when the order status is modified afterwards.</label>
            </div>
        </div>

        <?php if ( Facebook()->enabled() ) : ?>
            <div class="row mb-1">
                <div class="col">
                    <?php Facebook()->render_switcher_input( 'woo_purchase_enabled' ); ?>
                    <h4 class="switcher-label">Enable the Purchase event on Facebook (required for DPA)</h4>
                </div>
            </div>
        <?php endif; ?>

        <?php if ( Pinterest()->enabled() ) : ?>
            <div class="row">
                <div class="col">
                    <?php Pinterest()->render_switcher_input( 'woo_purchase_enabled' ); ?>
                    <h4 class="switcher-label">Enable the Checkout event on Pinterest</h4>
                    <?php Pinterest()->renderAddonNotice(); ?>
                </div>
            </div>
        <?php endif; ?>



    <?php if ( GA()->enabled() ) : ?>
    <div class="row mb-1">
        <div class="col">
            <?php GA()->render_switcher_input( 'woo_purchase_enabled' ); ?>
            <h4 class="switcher-label">Enable the purchase event on Google Analytics</h4>
        </div>
    </div>
    <?php endif; ?>

    <?php if ( Ads()->enabled() ) : ?>
    <div class="row">
        <div class="col">
            <?php Ads()->render_switcher_input( 'woo_purchase_enabled' ); ?>
            <h4 class="switcher-label">Enable the purchase event on Google Ads</h4>
        </div>
    </div>
    <?php AdsHelpers\renderConversionLabelInputs( 'woo_purchase' ); ?>
    <div class="row mb-2">
        <div class="col col-offset-left">
            <label class="label-inline">WooCommerce Purchase event, Google Ads labels:</label>
            <div class="custom-controls-stacked">
                <?php Ads()->render_radio_input( 'woo_purchase_conversion_track', 'conversion', 'Fire a conversion event along with the default Purchase event' ); ?>
                <?php Ads()->render_radio_input( 'woo_purchase_conversion_track', 'current_event',
                    'Add the conversion label to the Purchase event' ); ?>
            </div>
        </div>
    </div>
    <div class="row mb-2">
        <div class="col col-offset-left">
            <?php Ads()->render_checkbox_input( 'woo_purchase_new_customer',
                'Send the new_customer parameter' ); ?>
        </div>
    </div>
    <hr>

    <?php endif; ?>

    <?php if ( Bing()->enabled() ) : ?>
    <div class="row">
        <div class="col-11">
            <?php Bing()->render_switcher_input( 'woo_purchase_enabled' ); ?>
            <h4 class="switcher-label">Enable the Purchase event on Bing</h4>
            <?php Bing()->renderAddonNotice(); ?>
        </div>
        <div class="col-1">
            <?php renderPopoverButton( 'woo_bing_enable_purchase' ); ?>
        </div>
    </div>
    <?php endif; ?>

    <?php if ( Tiktok()->enabled() ) : ?>
        <div class="row">
            <div class="col-11">
                <?php Tiktok()->render_switcher_input( 'woo_purchase_enabled' ); ?>
                <h4 class="switcher-label">Enable the PlaceAnOrder event on TikTok</h4>
            </div>
        </div>

        <div class="row">
            <div class="col-11">
                <?php Tiktok()->render_switcher_input( 'woo_compete_payment_enabled' ); ?>
                <h4 class="switcher-label">Enable the CompletePayment event on TikTok</h4>
            </div>
        </div>
    <?php endif; ?>
        <hr class="mb-3 mt-3">
	    <?php if ( GTM()->enabled() ) : ?>
            <div class="row mb-1">
                <div class="col">
				    <?php GTM()->render_switcher_input( 'woo_purchase_enabled' ); ?>
                    <h4 class="switcher-label">Enable the purchase event on GTM dataLayer</h4>
                </div>
            </div>
            <div class="row mt-3 mb-2">
                <div class="col col-offset-left">
                    <?php GTM()->render_checkbox_input( 'woo_purchase_new_customer',
                        'Send the new_customer parameter' ); ?>
                </div>
            </div>
	    <?php endif; ?>
        <hr class="mb-3 mt-3">
        <div class="row mt-3">
            <div class="col">
                <p class="mb-0">*This event will be fired on the order-received, the default WooCommerce "thank you
                    page". If you use PayPal, make sure that auto-return is ON. If you want to use "custom thank you
                    pages", you must configure them with our <a href="https://www.pixelyoursite.com/super-pack"
                    target="_blank">Super Pack</a>.</p>
            </div>
        </div>
    </div>
</div>
<!-- InitiateCheckout -->
<div class="card">
    <div class="card-header has_switch">
        <?php PYS()->render_switcher_input( 'woo_initiate_checkout_enabled' ); ?>Track the Checkout Page <?php cardCollapseBtn(); ?>
    </div>
    <div class="card-body">
	    <?php PYS()->renderValueOptionsBlock('woo_initiate_checkout');?>
        <hr>
        <?php if ( Facebook()->enabled() ) : ?>
            <div class="row">
                <div class="col">
                    <?php Facebook()->render_switcher_input( 'woo_initiate_checkout_enabled' ); ?>
                    <h4 class="switcher-label">Enable the InitiateCheckout event on Facebook</h4>
                </div>
            </div>
        <?php endif; ?>
        <?php if ( Ads()->enabled() ) : ?>
            <div class="row">
                <div class="col">
                    <?php Ads()->render_switcher_input( 'woo_initiate_checkout_enabled' ); ?>
                    <h4 class="switcher-label">Enable the begin_checkout event on Google Ads</h4>
                </div>
            </div>
	        <?php AdsHelpers\renderConversionLabelInputs( 'woo_initiate_checkout' ); ?>
            <div class="row mb-2">
                <div class="col col-offset-left">
                    <label class="label-inline">WooCommerce begin_checkout event, Google Ads lables:</label>
                    <div class="custom-controls-stacked">
				        <?php Ads()->render_radio_input( 'woo_initiate_checkout_conversion_track', 'conversion', 'Fire a conversion event along with the default begin_checkout event' ); ?>
				        <?php Ads()->render_radio_input( 'woo_initiate_checkout_conversion_track', 'current_event',
					        'Add the conversion label to the begin_checkout event' ); ?>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <?php if ( Pinterest()->enabled() ) : ?>
            <div class="row">
                <div class="col">
                    <?php Pinterest()->render_switcher_input( 'woo_initiate_checkout_enabled' ); ?>
                    <h4 class="switcher-label">Enable the InitiateCheckout on Pinterest</h4>
                    <?php Pinterest()->renderAddonNotice(); ?>
                </div>
            </div>
        <?php endif; ?>

        <?php if ( Bing()->enabled() ) : ?>
            <div class="row">
                <div class="col">
                    <?php Bing()->render_switcher_input( 'woo_initiate_checkout_enabled' ); ?>
                    <h4 class="switcher-label">Enable the InitiateCheckout on Bing</h4>
                    <?php Bing()->renderAddonNotice(); ?>
                </div>
            </div>
        <?php endif; ?>

        <?php if ( Tiktok()->enabled() ) : ?>
            <div class="row">
                <div class="col">
                    <?php Tiktok()->render_switcher_input( 'woo_initiate_checkout_enabled' ); ?>
                    <h4 class="switcher-label">Enable the InitiateCheckout on TikTok</h4>
                </div>
            </div>
        <?php endif; ?>

        <hr class="mb-3 mt-3">
	    <?php if ( GTM()->enabled() ) : ?>
            <div class="row mb-1">
                <div class="col">
				    <?php GTM()->render_switcher_input( 'woo_initiate_checkout_enabled' ); ?>
                    <h4 class="switcher-label">Enable the begin_checkout event on GTM dataLayer</h4>
                </div>
            </div>
	    <?php endif; ?>
        <hr class="mb-3 mt-3">
    </div>
</div>
<!-- AddToCart -->
<div class="card">
    <div class="card-header has_switch">
        <?php PYS()->render_switcher_input( 'woo_add_to_cart_enabled' ); ?>Track add to cart <?php cardCollapseBtn(); ?>
    </div>
    <div class="card-body">
	    <?php PYS()->renderValueOptionsBlock('woo_add_to_cart');?>
        <hr>
        <?php if ( Facebook()->enabled() ) : ?>
            <div class="row">
                <div class="col">
                    <?php Facebook()->render_switcher_input( 'woo_add_to_cart_enabled' ); ?>
                    <h4 class="switcher-label">Enable the AddToCart event on Facebook (required for DPA)</h4>
                </div>
            </div>
        <?php endif; ?>

        <?php if ( Pinterest()->enabled() ) : ?>
            <div class="row">
                <div class="col">
                    <?php Pinterest()->render_switcher_input( 'woo_add_to_cart_enabled' ); ?>
                    <h4 class="switcher-label">Enable the AddToCart event on Pinterest</h4>
                    <?php Pinterest()->renderAddonNotice(); ?>
                </div>
            </div>
        <?php endif; ?>

        <?php if ( GA()->enabled() ) : ?>
            <div class="row mb-1">
                <div class="col">
                    <?php GA()->render_switcher_input( 'woo_add_to_cart_enabled' ); ?>
                    <h4 class="switcher-label">Enable the add_to_cart event on Google Analytics</h4>
                </div>
            </div>
        <?php endif; ?>
        <?php if ( Ads()->enabled() ) : ?>
            <div class="row">
                <div class="col">
                    <?php Ads()->render_switcher_input( 'woo_add_to_cart_enabled' ); ?>
                    <h4 class="switcher-label">Enable the add_to_cart event on Google Ads</h4>
                </div>
            </div>
            <?php AdsHelpers\renderConversionLabelInputs( 'woo_add_to_cart' ); ?>
            <div class="row mb-2">
                <div class="col col-offset-left">
                    <label class="label-inline">WooCommerce add_to_cart event, Google Ads lables:</label>
                    <div class="custom-controls-stacked">
				        <?php Ads()->render_radio_input( 'woo_add_to_cart_conversion_track', 'conversion', 'Fire a conversion event along with the default add_to_cart event' ); ?>
				        <?php Ads()->render_radio_input( 'woo_add_to_cart_conversion_track', 'current_event',
					        'Add the conversion label to the add_to_cart event' ); ?>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <?php if ( Bing()->enabled() ) : ?>
            <div class="row">
                <div class="col">
                    <?php Bing()->render_switcher_input( 'woo_add_to_cart_enabled' ); ?>
                    <h4 class="switcher-label">Enable the AddToCart event on Bing</h4>
                    <?php Bing()->renderAddonNotice(); ?>
                </div>
            </div>
        <?php endif; ?>

        <?php if ( Tiktok()->enabled() ) : ?>
            <div class="row">
                <div class="col">
                    <?php Tiktok()->render_switcher_input( 'woo_add_to_cart_enabled' ); ?>
                    <h4 class="switcher-label">Enable the AddToCart event on TikTok</h4>
                </div>
            </div>
        <?php endif; ?>
        <hr class="mb-3 mt-3">
	    <?php if ( GTM()->enabled() ) : ?>
            <div class="row mb-1">
                <div class="col">
				    <?php GTM()->render_switcher_input( 'woo_add_to_cart_enabled' ); ?>
                    <h4 class="switcher-label">Enable the add_to_cart event on GTM dataLayer</h4>
                </div>
            </div>
	    <?php endif; ?>
        <hr class="mb-3 mt-3">
    </div>
</div>
<!-- ViewContent -->
<div class="card">
    <div class="card-header has_switch">
        <?php PYS()->render_switcher_input( 'woo_view_content_enabled' ); ?>Track product pages <?php cardCollapseBtn(); ?>
    </div>
    <div class="card-body">
	    <?php PYS()->renderValueOptionsBlock('woo_view_content');?>
        <hr>
        <?php if ( Facebook()->enabled() ) : ?>
            <div class="row">
                <div class="col">
                    <?php Facebook()->render_switcher_input( 'woo_view_content_enabled' ); ?>
                    <h4 class="switcher-label">Enable the ViewContent on Facebook (required for DPA)</h4>
                </div>
            </div>
        <?php endif; ?>

        <?php if ( Pinterest()->enabled() ) : ?>
            <div class="row">
                <div class="col">
                    <?php Pinterest()->render_switcher_input( 'woo_view_content_enabled' ); ?>
                    <h4 class="switcher-label">Enable the PageVisit event on Pinterest</h4>
                    <?php Pinterest()->renderAddonNotice(); ?>
                </div>
            </div>
        <?php endif; ?>

        <div class="row my-3">
            <div class="col col-offset-left form-inline">
                <label>Delay</label>
                <?php PYS()->render_number_input( 'woo_view_content_delay' ); ?>
                <label>seconds</label>
            </div>
        </div>

        <?php if ( GA()->enabled() ) : ?>
            <div class="row mb-1">
                <div class="col">
                    <?php GA()->render_switcher_input( 'woo_view_content_enabled' ); ?>
                    <h4 class="switcher-label">Enable the view_item event on Google Analytics</h4>
                </div>
            </div>
        <?php endif; ?>
        <?php if ( Ads()->enabled() ) : ?>
            <div class="row">
                <div class="col">
                    <?php Ads()->render_switcher_input( 'woo_view_content_enabled' ); ?>
                    <h4 class="switcher-label">Enable the view_item event on Google Ads</h4>
                </div>
            </div>
            <?php AdsHelpers\renderConversionLabelInputs( 'woo_view_content' ); ?>
            <div class="row mb-2">
                <div class="col col-offset-left">
                    <label class="label-inline">WooCommerce view_item event, Google Ads lables:</label>
                    <div class="custom-controls-stacked">
				        <?php Ads()->render_radio_input( 'woo_view_content_conversion_track', 'conversion', 'Fire a conversion event along with the default view_item event' ); ?>
				        <?php Ads()->render_radio_input( 'woo_view_content_conversion_track', 'current_event',
					        'Add the conversion label to the view_item event' ); ?>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <?php if ( Bing()->enabled() ) : ?>
            <div class="row">
                <div class="col">
                    <?php Bing()->render_switcher_input( 'woo_view_content_enabled' ); ?>
                    <h4 class="switcher-label">Enable the PageVisit event on Bing</h4>
                    <?php Bing()->renderAddonNotice(); ?>
                </div>
            </div>
        <?php endif; ?>

        <?php if ( Tiktok()->enabled() ) : ?>
            <div class="row">
                <div class="col">
                    <?php Tiktok()->render_switcher_input( 'woo_view_content_enabled' ); ?>
                    <h4 class="switcher-label">Enable the ViewContent event on TikTok</h4>
                </div>
            </div>
        <?php endif; ?>
        <hr class="mb-3 mt-3">
	    <?php if ( GTM()->enabled() ) : ?>
            <div class="row mb-1">
                <div class="col">
				    <?php GTM()->render_switcher_input( 'woo_view_content_enabled' ); ?>
                    <h4 class="switcher-label">Enable the view_item event on GTM dataLayer</h4>
                </div>
            </div>
	    <?php endif; ?>
        <hr class="mb-3 mt-3">
    </div>
</div>
<!-- ViewCategory -->
<div class="card">
    <div class="card-header has_switch">
        <?php PYS()->render_switcher_input( 'woo_view_category_enabled' ); ?>Track product category pages <?php cardCollapseBtn(); ?>
    </div>
    <div class="card-body">
        <?php if ( Facebook()->enabled() ) : ?>
            <div class="row">
                <div class="col">
                    <?php Facebook()->render_switcher_input( 'woo_view_category_enabled' ); ?>
                    <h4 class="switcher-label">Enable the ViewCategory event on Facebook Analytics (used for DPA)</h4>
                </div>
            </div>
        <?php endif; ?>



        <?php if ( Ads()->enabled() ) : ?>
            <div class="row">
                <div class="col">
                    <?php Ads()->render_switcher_input( 'woo_view_category_enabled' ); ?>
                    <h4 class="switcher-label">Enable the view_item_list event on Google Ads</h4>
                </div>
            </div>
            <?php AdsHelpers\renderConversionLabelInputs( 'woo_view_category' ); ?>
            <div class="row mb-2">
                <div class="col col-offset-left">
                    <label class="label-inline">WooCommerce view_item_list event, Google Ads lables:</label>
                    <div class="custom-controls-stacked">
				        <?php Ads()->render_radio_input( 'woo_view_category_conversion_track', 'conversion', 'Fire a conversion event along with the default view_item_list event' ); ?>
				        <?php Ads()->render_radio_input( 'woo_view_category_conversion_track', 'current_event',
					        'Add the conversion label to the view_item_list event' ); ?>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <?php if ( Pinterest()->enabled() ) : ?>
            <div class="row">
                <div class="col">
                    <?php Pinterest()->render_switcher_input( 'woo_view_category_enabled' ); ?>
                    <h4 class="switcher-label">Enable the ViewCategory event on Pinterest</h4>
                    <?php Pinterest()->renderAddonNotice(); ?>
                </div>
            </div>
        <?php endif; ?>

        <?php if ( Bing()->enabled() ) : ?>
            <div class="row">
                <div class="col">
                    <?php Bing()->render_switcher_input( 'woo_view_category_enabled' ); ?>
                    <h4 class="switcher-label">Enable the ViewCategory event on Bing</h4>
                    <?php Bing()->renderAddonNotice(); ?>
                </div>
            </div>
        <?php endif; ?>

    </div>
</div>
<!-- ViewCart -->
<div class="card">
    <div class="card-header has_switch">
        <?php PYS()->render_switcher_input( 'woo_view_cart_enabled' ); ?>Track cart pages <?php cardCollapseBtn(); ?>
    </div>
    <div class="card-body">

        <?php if ( GA()->enabled() ) : ?>
            <div class="row mb-1">
                <div class="col">
                    <?php GA()->render_switcher_input( 'woo_view_cart_enabled' ); ?>
                    <h4 class="switcher-label">Enable the view_cart event on Google Analytics</h4>
                </div>
            </div>
        <?php endif; ?>

    </div>
</div>
<!-- Track product list performance -->
<div class="card">
    <div class="card-header has_switch">
        <?php PYS()->render_switcher_input( 'woo_view_item_list_enabled' ); ?>Track product list performance on Google Analytics<?php cardCollapseBtn(); ?>
    </div>
    <div class="card-body">
        <?php if ( GA()->enabled() ) : ?>


            <div class="row mb-2">
                <div class="col col-offset-left">
                    <h3>Lists:</h3>
                    <?php GA()->render_checkbox_input( 'woo_enable_list_shop', 'Shop page' ); ?>
                </div>
            </div>
            <div class="row mb-2">
                <div class="col col-offset-left">
                    <?php GA()->render_checkbox_input( 'woo_enable_list_related', 'Related product' ); ?>
                </div>
            </div>
            <div class="row mb-2">
                <div class="col col-offset-left">
                    <?php GA()->render_checkbox_input( 'woo_enable_list_category', 'Category' ); ?>
                </div>
            </div>
            <div class="row mb-2">
                <div class="col col-offset-left">
                    <?php GA()->render_checkbox_input( 'woo_enable_list_tags', 'Tags' ); ?>
                </div>
            </div>
            <div class="row mb-2">
                <div class="col col-offset-left">
                    <?php GA()->render_checkbox_input( 'woo_enable_list_shortcodes', 'Shortcodes' ); ?>
                </div>
            </div>
            <hr>
            <div class="row mb-1">
                <div class="col">
                    <?php GA()->render_switcher_input( 'woo_view_item_list_track_name' ); ?>
                    <h4 class="switcher-label">Track names for lists</h4>
                </div>
            </div>
            <small>When checked, we send the data like we don now. Example:<br>
                Category - iPhones
            </small>
            <hr>
            <div class="row mb-1">
                <div class="col">
                    <?php GA()->render_switcher_input( 'woo_view_item_list_enabled' ); ?>
                    <h4 class="switcher-label">Enable the view_item_list event on Google Analytics(categories, related products, search, shortcodes)</h4>
                </div>
            </div>
            <div class="row mb-1">
                <div class="col">
                    <?php GA()->render_switcher_input( 'woo_select_content_enabled' ); ?>
                    <h4 class="switcher-label">Enable the select_item event on Google Analytics(when a product is clicked on categories, related products, search, shortcodes)</h4>
                </div>
            </div>
            <hr>
            <small>What parameters we add to the items table in for e-commerce events (item_list_id, item_list_name)</small>
            <div class="row mb-1">
                <div class="col col-offset-left">
                    <?php GA()->render_switcher_input( 'woo_track_item_list_name' ); ?>
                    <h4 class="switcher-label">Enable item_list_name</h4>
                </div>
            </div>
            <div class="row mb-1">
                <div class="col col-offset-left">
                    <?php GA()->render_switcher_input( 'woo_track_item_list_id' ); ?>
                    <h4 class="switcher-label">Enable item_list_id</h4>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>
<hr>
<!-- Track product list performance -->
<div class="card">
    <div class="card-header has_switch">
        <?php PYS()->render_switcher_input( 'woo_view_item_list_enabled' ); ?>Track product list performance on GTM dataLayer<?php cardCollapseBtn(); ?>
    </div>
    <div class="card-body">
        <?php if ( GTM()->enabled() ) : ?>


            <div class="row mb-2">
                <div class="col col-offset-left">
                    <h3>Lists:</h3>
                    <?php GTM()->render_checkbox_input( 'woo_enable_list_shop', 'Shop page' ); ?>
                </div>
            </div>
            <div class="row mb-2">
                <div class="col col-offset-left">
                    <?php GTM()->render_checkbox_input( 'woo_enable_list_related', 'Related product' ); ?>
                </div>
            </div>
            <div class="row mb-2">
                <div class="col col-offset-left">
                    <?php GTM()->render_checkbox_input( 'woo_enable_list_category', 'Category' ); ?>
                </div>
            </div>
            <div class="row mb-2">
                <div class="col col-offset-left">
                    <?php GTM()->render_checkbox_input( 'woo_enable_list_tags', 'Tags' ); ?>
                </div>
            </div>
            <div class="row mb-2">
                <div class="col col-offset-left">
                    <?php GTM()->render_checkbox_input( 'woo_enable_list_shortcodes', 'Shortcodes' ); ?>
                </div>
            </div>
            <hr>
            <div class="row mb-1">
                <div class="col">
                    <?php GTM()->render_switcher_input( 'woo_view_item_list_track_name' ); ?>
                    <h4 class="switcher-label">Track names for lists</h4>
                </div>
            </div>
            <small>When checked, we send the data like we don now. Example:<br>
                Category - iPhones
            </small>
            <hr>
            <div class="row mb-1">
                <div class="col">
                    <?php GTM()->render_switcher_input( 'woo_view_item_list_enabled' ); ?>
                    <h4 class="switcher-label">Enable the view_item_list event on GTM dataLayer(categories, related products, search, shortcodes)</h4>
                </div>
            </div>
            <div class="row mb-1">
                <div class="col">
                    <?php GTM()->render_switcher_input( 'woo_select_content_enabled' ); ?>
                    <h4 class="switcher-label">Enable the select_item event on GTM dataLayer(when a product is clicked on categories, related products, search, shortcodes)</h4>
                </div>
            </div>
            <hr>
            <small>What parameters we add to the items table in for e-commerce events (item_list_id, item_list_name)</small>
            <div class="row mb-1">
                <div class="col col-offset-left">
                    <?php GTM()->render_switcher_input( 'woo_track_item_list_name' ); ?>
                    <h4 class="switcher-label">Enable item_list_name</h4>
                </div>
            </div>
            <div class="row mb-1">
                <div class="col col-offset-left">
                    <?php GTM()->render_switcher_input( 'woo_track_item_list_id' ); ?>
                    <h4 class="switcher-label">Enable item_list_id</h4>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<h2 class="section-title">Advanced Marketing Events</h2>

<!-- FrequentShopper -->
<div class="card">
    <div class="card-header has_switch">
        <?php PYS()->render_switcher_input( 'woo_frequent_shopper_enabled' ); ?> FrequentShopper Event <?php cardCollapseBtn(); ?>
    </div>
    <div class="card-body">

        <?php if ( Facebook()->enabled() ) : ?>
            <div class="row">
                <div class="col">
                    <?php Facebook()->render_switcher_input( 'woo_frequent_shopper_enabled' ); ?>
                    <h4 class="switcher-label">Send the event to Facebook</h4>
                </div>
            </div>
        <?php endif; ?>

		<?php if ( GA()->enabled() ) : ?>
            <div class="row">
                <div class="col">
					<?php GA()->render_switcher_input( 'woo_frequent_shopper_enabled' ); ?>
                    <h4 class="switcher-label">Send the event to Google Analytics</h4>
                </div>
            </div>
        <?php endif; ?>

		<?php if ( Ads()->enabled() ) : ?>
            <div class="row">
                <div class="col">
					<?php Ads()->render_switcher_input( 'woo_frequent_shopper_enabled' ); ?>
                    <h4 class="switcher-label">Enable on Google Ads</h4>
                </div>
            </div>
			<?php AdsHelpers\renderConversionLabelInputs( 'woo_frequent_shopper' ); ?>
		<?php endif; ?>

        <?php if ( Pinterest()->enabled() ) : ?>
            <div class="row">
                <div class="col">
                    <?php Pinterest()->render_switcher_input( 'woo_frequent_shopper_enabled' ); ?>
                    <h4 class="switcher-label">Enable on Pinterest</h4>
                    <?php Pinterest()->renderAddonNotice(); ?>
                </div>
            </div>
        <?php endif; ?>

        <?php if ( Bing()->enabled() ) : ?>
            <div class="row">
                <div class="col">
                    <?php Bing()->render_switcher_input( 'woo_frequent_shopper_enabled' ); ?>
                    <h4 class="switcher-label">Enable on Bing</h4>
                    <?php Bing()->renderAddonNotice(); ?>
                </div>
            </div>
        <?php endif; ?>

		<?php if ( Tiktok()->enabled() ) : ?>
            <div class="row">
                <div class="col">
					<?php Tiktok()->render_switcher_input( 'woo_frequent_shopper_enabled' ); ?>
                    <h4 class="switcher-label">Enable on TikTok</h4>
                </div>
            </div>
		<?php endif; ?>

        <hr class="mb-3 mt-3">
	    <?php if ( GTM()->enabled() ) : ?>
            <div class="row mb-1">
                <div class="col">
				    <?php GTM()->render_switcher_input( 'woo_frequent_shopper_enabled' ); ?>
                    <h4 class="switcher-label">Send the event to GTM dataLayer</h4>
                </div>
            </div>
	    <?php endif; ?>
        <hr class="mb-3 mt-3">
        <div class="row mt-3">
            <div class="col col-offset-left form-inline">
                <label>Fire this event when the client has at least </label>
                <?php PYS()->render_number_input( 'woo_frequent_shopper_transactions' ); ?>
                <label>transactions</label>
            </div>
        </div>
    </div>
</div>
<!-- VipClient -->
<div class="card">
    <div class="card-header has_switch">
        <?php PYS()->render_switcher_input( 'woo_vip_client_enabled' ); ?>VIPClient Event <?php cardCollapseBtn(); ?>
    </div>
    <div class="card-body">

        <?php if ( Facebook()->enabled() ) : ?>
            <div class="row">
                <div class="col">
                    <?php Facebook()->render_switcher_input( 'woo_vip_client_enabled' ); ?>
                    <h4 class="switcher-label">Send the event to Facebook</h4>
                </div>
            </div>
        <?php endif; ?>

		<?php if ( GA()->enabled() ) : ?>
            <div class="row">
                <div class="col">
					<?php GA()->render_switcher_input( 'woo_vip_client_enabled' ); ?>
                    <h4 class="switcher-label">Send the event to Google Analytics</h4>
                </div>
            </div>
		<?php endif; ?>

		<?php if ( Ads()->enabled() ) : ?>
            <div class="row">
                <div class="col">
					<?php Ads()->render_switcher_input( 'woo_vip_client_enabled' ); ?>
                    <h4 class="switcher-label">Enable on Google Ads</h4>
                </div>
            </div>
			<?php AdsHelpers\renderConversionLabelInputs( 'woo_vip_client' ); ?>
		<?php endif; ?>

        <?php if ( Pinterest()->enabled() ) : ?>
            <div class="row">
                <div class="col">
                    <?php Pinterest()->render_switcher_input( 'woo_vip_client_enabled' ); ?>
                    <h4 class="switcher-label">Enable on Pinterest</h4>
                    <?php Pinterest()->renderAddonNotice(); ?>
                </div>
            </div>
        <?php endif; ?>

        <?php if ( Bing()->enabled() ) : ?>
            <div class="row">
                <div class="col">
                    <?php Bing()->render_switcher_input( 'woo_vip_client_enabled' ); ?>
                    <h4 class="switcher-label">Enable on Bing</h4>
                    <?php Bing()->renderAddonNotice(); ?>
                </div>
            </div>
        <?php endif; ?>

		<?php if ( Tiktok()->enabled() ) : ?>
            <div class="row">
                <div class="col">
					<?php Tiktok()->render_switcher_input( 'woo_vip_client_enabled' ); ?>
                    <h4 class="switcher-label">Enable on TikTok</h4>
                </div>
            </div>
		<?php endif; ?>
        <hr class="mb-3 mt-3">
	    <?php if ( GTM()->enabled() ) : ?>
            <div class="row mb-1">
                <div class="col">
				    <?php GTM()->render_switcher_input( 'woo_vip_client_enabled' ); ?>
                    <h4 class="switcher-label">Send the event to GTM dataLayer</h4>
                </div>
            </div>
	    <?php endif; ?>
        <hr class="mb-3 mt-3">
        <div class="row mt-3">
            <div class="col col-offset-left form-inline">
                <label>Fire this event when the client has at least</label>
                <?php PYS()->render_number_input( 'woo_vip_client_transactions' ); ?>
                <label>transactions and average order is at least</label>
                <?php PYS()->render_number_input( 'woo_vip_client_average_value' ); ?>
            </div>
        </div>
    </div>
</div>
<!-- BigWhale -->
<div class="card">
    <div class="card-header has_switch">
        <?php PYS()->render_switcher_input( 'woo_big_whale_enabled' ); ?>BigWhale Event <?php cardCollapseBtn(); ?>
    </div>
    <div class="card-body">

        <?php if ( Facebook()->enabled() ) : ?>
            <div class="row">
                <div class="col">
                    <?php Facebook()->render_switcher_input( 'woo_big_whale_enabled' ); ?>
                    <h4 class="switcher-label">Send the event to Facebook</h4>
                </div>
            </div>
        <?php endif; ?>

		<?php if ( GA()->enabled() ) : ?>
            <div class="row mb-1">
                <div class="col">
					<?php GA()->render_switcher_input( 'woo_big_whale_enabled' ); ?>
                    <h4 class="switcher-label">Send the event to Google Analytics</h4>
                </div>
            </div>
		<?php endif; ?>

		<?php if ( Ads()->enabled() ) : ?>
            <div class="row">
                <div class="col">
					<?php Ads()->render_switcher_input( 'woo_big_whale_enabled' ); ?>
                    <h4 class="switcher-label">Enable on Google Ads</h4>
                </div>
            </div>
			<?php AdsHelpers\renderConversionLabelInputs( 'woo_big_whale' ); ?>
		<?php endif; ?>

        <?php if ( Pinterest()->enabled() ) : ?>
            <div class="row">
                <div class="col">
                    <?php Pinterest()->render_switcher_input( 'woo_big_whale_enabled' ); ?>
                    <h4 class="switcher-label">Enable on Pinterest</h4>
                    <?php Pinterest()->renderAddonNotice(); ?>
                </div>
            </div>
        <?php endif; ?>

        <?php if ( Bing()->enabled() ) : ?>
            <div class="row">
                <div class="col">
                    <?php Bing()->render_switcher_input( 'woo_big_whale_enabled' ); ?>
                    <h4 class="switcher-label">Enable on Bing</h4>
                    <?php Bing()->renderAddonNotice(); ?>
                </div>
            </div>
        <?php endif; ?>

		<?php if ( Tiktok()->enabled() ) : ?>
            <div class="row">
                <div class="col">
					<?php Tiktok()->render_switcher_input( 'woo_big_whale_enabled' ); ?>
                    <h4 class="switcher-label">Enable on TikTok</h4>
                </div>
            </div>
		<?php endif; ?>
        <hr class="mb-3 mt-3">
	    <?php if ( GTM()->enabled() ) : ?>
            <div class="row mb-1">
                <div class="col">
				    <?php GTM()->render_switcher_input( 'woo_big_whale_enabled' ); ?>
                    <h4 class="switcher-label">Send the event to GTM dataLayer</h4>
                </div>
            </div>
	    <?php endif; ?>
        <hr class="mb-3 mt-3">
        <div class="row mt-3">
            <div class="col col-offset-left form-inline">
                <label>Fire this event when the client has LTV at least</label>
                <?php PYS()->render_number_input( 'woo_big_whale_ltv' ); ?>
            </div>
        </div>
    </div>
</div>
<div class="card">
    <div class="card-header has_switch">
        <?php PYS()->render_switcher_input( 'woo_FirstTimeBuyer_enabled' ); ?>FirstTimeBuyer Event <?php cardCollapseBtn(); ?>
    </div>
    <div class="card-body">

        <?php if ( Facebook()->enabled() ) : ?>
            <div class="row">
                <div class="col">
                    <?php Facebook()->render_switcher_input( 'woo_FirstTimeBuyer_enabled' ); ?>
                    <h4 class="switcher-label">Send the event to Facebook</h4>
                </div>
            </div>
        <?php endif; ?>

		<?php if ( GA()->enabled() ) : ?>
            <div class="row">
                <div class="col">
					<?php GA()->render_switcher_input( 'woo_FirstTimeBuyer_enabled' ); ?>
                    <h4 class="switcher-label">Send the event to Google Analytics</h4>
                </div>
            </div>
		<?php endif; ?>

		<?php if ( Ads()->enabled() ) : ?>
            <div class="row">
                <div class="col">
					<?php Ads()->render_switcher_input( 'woo_FirstTimeBuyer_enabled' ); ?>
                    <h4 class="switcher-label">Enable on Google Ads</h4>
                </div>
            </div>
			<?php AdsHelpers\renderConversionLabelInputs( 'woo_FirstTimeBuyer' ); ?>
		<?php endif; ?>

        <?php if ( Pinterest()->enabled() ) : ?>
            <div class="row">
                <div class="col">
                    <?php Pinterest()->render_switcher_input( 'woo_FirstTimeBuyer_enabled' ); ?>
                    <h4 class="switcher-label">Send the event to Pinterest</h4>
                    <?php Pinterest()->renderAddonNotice(); ?>
                </div>
            </div>
        <?php endif; ?>

        <?php if ( Bing()->enabled() ) : ?>
            <div class="row">
                <div class="col">
                    <?php Bing()->render_switcher_input( 'woo_FirstTimeBuyer_enabled' ); ?>
                    <h4 class="switcher-label">Send the event to Bing</h4>
                    <?php Bing()->renderAddonNotice(); ?>
                </div>
            </div>
        <?php endif; ?>

		<?php if ( Tiktok()->enabled() ) : ?>
            <div class="row">
                <div class="col">
					<?php Tiktok()->render_switcher_input( 'woo_FirstTimeBuyer_enabled' ); ?>
                    <h4 class="switcher-label">Send the event to TikTok</h4>
                </div>
            </div>
		<?php endif; ?>

        <hr class="mb-3 mt-3">
	    <?php if ( GTM()->enabled() ) : ?>
            <div class="row mb-1">
                <div class="col">
				    <?php GTM()->render_switcher_input( 'woo_FirstTimeBuyer_enabled' ); ?>
                    <h4 class="switcher-label">Send the event to GTM dataLayer</h4>
                </div>
            </div>
	    <?php endif; ?>
        <hr class="mb-3 mt-3">
    </div>
</div>
<div class="card">
    <div class="card-header has_switch">
        <?php PYS()->render_switcher_input( 'woo_ReturningCustomer_enabled' ); ?>ReturningCustomer Event <?php cardCollapseBtn(); ?>
    </div>
    <div class="card-body">

        <?php if ( Facebook()->enabled() ) : ?>
            <div class="row">
                <div class="col">
                    <?php Facebook()->render_switcher_input( 'woo_ReturningCustomer_enabled' ); ?>
                    <h4 class="switcher-label">Send the event to Facebook</h4>
                </div>
            </div>
        <?php endif; ?>

		<?php if ( GA()->enabled() ) : ?>
            <div class="row">
                <div class="col">
					<?php GA()->render_switcher_input( 'woo_ReturningCustomer_enabled' ); ?>
                    <h4 class="switcher-label">Send the event to Google Analytics</h4>
                </div>
            </div>
		<?php endif; ?>

		<?php if ( Ads()->enabled() ) : ?>
            <div class="row">
                <div class="col">
					<?php Ads()->render_switcher_input( 'woo_ReturningCustomer_enabled' ); ?>
                    <h4 class="switcher-label">Enable on Google Ads</h4>
                </div>
            </div>
			<?php AdsHelpers\renderConversionLabelInputs( 'woo_ReturningCustomer' ); ?>
		<?php endif; ?>

        <?php if ( Pinterest()->enabled() ) : ?>
            <div class="row">
                <div class="col">
                    <?php Pinterest()->render_switcher_input( 'woo_ReturningCustomer_enabled' ); ?>
                    <h4 class="switcher-label">Send the event to Pinterest</h4>
                    <?php Pinterest()->renderAddonNotice(); ?>
                </div>
            </div>
        <?php endif; ?>

        <?php if ( Bing()->enabled() ) : ?>
            <div class="row">
                <div class="col">
                    <?php Bing()->render_switcher_input( 'woo_ReturningCustomer_enabled' ); ?>
                    <h4 class="switcher-label">Send the event to Bing</h4>
                    <?php Bing()->renderAddonNotice(); ?>
                </div>
            </div>
        <?php endif; ?>

		<?php if ( Tiktok()->enabled() ) : ?>
            <div class="row">
                <div class="col">
					<?php Tiktok()->render_switcher_input( 'woo_ReturningCustomer_enabled' ); ?>
                    <h4 class="switcher-label">Send the event to TikTok</h4>
                </div>
            </div>
		<?php endif; ?>

        <hr class="mb-3 mt-3">
	    <?php if ( GTM()->enabled() ) : ?>
            <div class="row mb-1">
                <div class="col">
				    <?php GTM()->render_switcher_input( 'woo_ReturningCustomer_enabled' ); ?>
                    <h4 class="switcher-label">Send the event to GTM dataLayer</h4>
                </div>
            </div>
	    <?php endif; ?>
        <hr class="mb-3 mt-3">
    </div>
</div>
<h2 class="section-title">Extra events</h2>

<!-- Checkout Behavior on Google Analytics -->
<?php if ( GA()->enabled() ) : ?>
    <div class="card checkout_steps">
        <div class="card-header has_switch">
            <?php PYS()->render_switcher_input( 'woo_checkout_steps_enabled' ); ?> Track Checkout Behavior on Google Analytics <?php cardCollapseBtn(); ?>
        </div>
        <div class="card-body">

            <div class="row mb-1 woo_initiate_checkout_enabled">
                <div class="pr-0"><div class="step pt-2">STEP 1:</div></div>
                <div class="pl-0">
                    <?php GA()->render_switcher_input( 'woo_initiate_checkout_enabled' ); ?>
                    <h4 class="switcher-label">Enable the begin_checkout </h4>
                </div>
	            <?php renderPopoverButton( 'woo_initiate_checkout_event_value_1' ); ?>
            </div>

            <div class="row mb-1" style="display: none">
                <div class="col">
                    <?php GA()->render_switcher_input( 'woo_initiate_set_checkout_option_enabled' ); ?>
                    <h4 class="switcher-label">Enable the set_checkout_option  </h4>
                </div>
            </div>
            <div class="row mb-1 woo_initiate_checkout_progress_f_enabled checkout_progress" >
                <div class="pr-0"><div class="step pt-2"></div></div>
                <div class="pl-0">
                    <?php GA()->render_switcher_input( 'woo_initiate_checkout_progress_f_enabled'); ?>
                    <h4 class="switcher-label">Enable checkout_progress when the First Name is added   </h4>
                </div>
            </div>
            <div class="row mb-1 woo_initiate_checkout_progress_l_enabled checkout_progress">
                <div class="pr-0"><div class="step pt-2"></div></div>
                <div class="pl-0">
                    <?php GA()->render_switcher_input( 'woo_initiate_checkout_progress_l_enabled' ); ?>
                    <h4 class="switcher-label">Enable checkout_progress when the Last Name is added   </h4>
                </div>
            </div>
            <div class="row mb-1 woo_initiate_checkout_progress_e_enabled checkout_progress">
                <div class="pr-0"><div class="step pt-2"></div></div>
                <div class="pl-0">
                    <?php GA()->render_switcher_input( 'woo_initiate_checkout_progress_e_enabled' ); ?>
                    <h4 class="switcher-label">Enable checkout_progress when the Email is added   </h4>
                </div>
            </div>
            <div class="row mb-1 woo_initiate_checkout_progress_o_enabled checkout_progress">
                <div class="pr-0"><div class="step pt-2"></div></div>
                <div class="pl-0">
                    <?php GA()->render_switcher_input( 'woo_initiate_checkout_progress_o_enabled' ); ?>
                    <h4 class="switcher-label">Enable checkout_progress when is Place Order is clicked </h4>
                </div>
            </div>



        </div>
    </div>
<?php endif; ?>
<!-- RemoveFromCart -->
<div class="card">
    <div class="card-header has_switch">
        <?php PYS()->render_switcher_input( 'woo_remove_from_cart_enabled' ); ?>Track remove from cart <?php cardCollapseBtn(); ?>
    </div>
    <div class="card-body">

     <?php if ( Facebook()->enabled() ) : ?>
     <div class="row">
        <div class="col">
            <?php Facebook()->render_switcher_input( 'woo_remove_from_cart_enabled' ); ?>
            <h4 class="switcher-label">Enable the RemoveFromCart event on Facebook</h4>
        </div>
    </div>
<?php endif; ?>

<?php if ( GA()->enabled() ) : ?>
<div class="row mb-1">
    <div class="col">
        <?php GA()->render_switcher_input( 'woo_remove_from_cart_enabled' ); ?>
        <h4 class="switcher-label">Enable the remove_from_cart event on Google Analytics</h4>
    </div>
</div>
<?php endif; ?>


<?php if ( Pinterest()->enabled() ) : ?>
<div class="row">
    <div class="col">
        <?php Pinterest()->render_switcher_input( 'woo_remove_from_cart_enabled' ); ?>
        <h4 class="switcher-label">Enable the RemoveFromCart event on Pinterest</h4>
        <?php Pinterest()->renderAddonNotice(); ?>
    </div>
</div>
<?php endif; ?>

</div>
</div>
<!-- Affiliate -->
<div class="card">
    <div class="card-header has_switch">
        <?php PYS()->render_switcher_input('woo_affiliate_enabled'); ?> Track WooCommerce affiliate button clicks <?php cardCollapseBtn(); ?>
    </div>
    <div class="card-body">
	    <?php PYS()->renderValueOptionsBlock('woo_affiliate');?>
        <hr>
        <?php if ( Facebook()->enabled() ) : ?>
            <div class="row">
                <div class="col">
                    <?php Facebook()->render_switcher_input( 'woo_affiliate_enabled' ); ?>
                    <h4 class="switcher-label">Send the event to Facebook</h4>
                </div>
            </div>
        <?php endif; ?>

        <?php if ( Pinterest()->enabled() ) : ?>
            <div class="row">
                <div class="col">
                    <?php Pinterest()->render_switcher_input( 'woo_affiliate_enabled' ); ?>
                    <h4 class="switcher-label">Enable on Pinterest</h4>
                    <?php Pinterest()->renderAddonNotice(); ?>
                </div>
            </div>
        <?php endif; ?>

        <div class="row my-3">
            <div class="col col-offset-left form-inline">
                <label>Event Type:</label><?php PYS()->render_select_input( 'woo_affiliate_event_type',
                    array(
                        'ViewContent'          => 'ViewContent',
                        'AddToCart'            => 'AddToCart',
                        'AddToWishlist'        => 'AddToWishlist',
                        'InitiateCheckout'     => 'InitiateCheckout',
                        'AddPaymentInfo'       => 'AddPaymentInfo',
                        'Purchase'             => 'Purchase',
                        'Lead'                 => 'Lead',
                        'CompleteRegistration' => 'CompleteRegistration',
                        'disabled'             => '',
                        'custom'               => 'Custom',
                    ), false, 'pys_core_woo_affiliate_custom_event_type', 'custom' ); ?>
                <?php PYS()->render_text_input( 'woo_affiliate_custom_event_type', 'Enter name', false, true ); ?>
            </div>
        </div>

        <?php if ( GA()->enabled() ) : ?>
            <div class="row mb-1">
                <div class="col">
                    <?php GA()->render_switcher_input( 'woo_affiliate_enabled' ); ?>
                    <h4 class="switcher-label">Send the event to Google Analytics</h4>
                </div>
            </div>
        <?php endif; ?>

        <?php if ( Ads()->enabled() ) : ?>
            <div class="row">
                <div class="col">
                    <?php Ads()->render_switcher_input( 'woo_affiliate_enabled' ); ?>
                    <h4 class="switcher-label">Send the event to Google Ads</h4>
                </div>
            </div>
        <?php endif; ?>

    </div>
</div>
<!-- PayPal -->
<div class="card">
    <div class="card-header has_switch">
        <?php PYS()->render_switcher_input('woo_paypal_enabled'); ?>Track WooCommerce PayPal Standard clicks <?php cardCollapseBtn(); ?>
    </div>
    <div class="card-body">
	    <?php PYS()->renderValueOptionsBlock('woo_paypal');?>
        <hr>
        <?php if ( Facebook()->enabled() ) : ?>
            <div class="row">
                <div class="col">
                    <?php Facebook()->render_switcher_input( 'woo_paypal_enabled' ); ?>
                    <h4 class="switcher-label">Send the event to Facebook</h4>
                </div>
            </div>
        <?php endif; ?>

        <?php if ( Pinterest()->enabled() ) : ?>
            <div class="row">
                <div class="col">
                    <?php Pinterest()->render_switcher_input( 'woo_paypal_enabled' ); ?>
                    <h4 class="switcher-label">Enable on Pinterest</h4>
                    <?php Pinterest()->renderAddonNotice(); ?>
                </div>
            </div>
        <?php endif; ?>

        <div class="row my-3">
            <div class="col col-offset-left form-inline">
                <label>Event Type:</label><?php PYS()->render_select_input( 'woo_paypal_event_type',
                    array(
                        'ViewContent'          => 'ViewContent',
                        'AddToCart'            => 'AddToCart',
                        'AddToWishlist'        => 'AddToWishlist',
                        'InitiateCheckout'     => 'InitiateCheckout',
                        'AddPaymentInfo'       => 'AddPaymentInfo',
                        'Purchase'             => 'Purchase',
                        'Lead'                 => 'Lead',
                        'CompleteRegistration' => 'CompleteRegistration',
                        'disabled'             => '',
                        'custom'               => 'Custom',
                    ), false, 'pys_core_woo_paypal_custom_event_type', 'custom' ); ?>
                <?php PYS()->render_text_input( 'woo_paypal_custom_event_type', 'Enter name', false, true ); ?>
            </div>
        </div>

        <?php if ( GA()->enabled() ) : ?>
            <div class="row mb-1">
                <div class="col">
                    <?php GA()->render_switcher_input( 'woo_paypal_enabled' ); ?>
                    <h4 class="switcher-label">Send the event to Google Analytics</h4>
                </div>
            </div>
        <?php endif; ?>


    </div>

</div>
<!-- Track CompleteRegistration -->
<div class="card">
    <div class="card-header has_switch">
        <?php PYS()->render_switcher_input('woo_complete_registration_enabled'); ?> Track CompleteRegistration for the Meta Pixel (formerly Facebook Pixel)<?php cardCollapseBtn(); ?>
    </div>
    <div class="card-body">
        <?php if ( Facebook()->enabled() ) : ?>

            <?php Facebook()->renderValueOptionsBlock('woo_complete_registration', false);?>
            <hr>
            <div class="row mb-1">
                <div class="col">
                    <?php Facebook()->render_checkbox_input( 'woo_complete_registration_fire_every_time',
                        "Fire this event every time a transaction takes place"); ?>
                </div>
            </div>

            <div class="row mb-1">
                <div class="col">
                    <?php Facebook()->render_switcher_input( 'woo_complete_registration_send_from_server'); ?>
                    <h4 class="switcher-label">Send this from your server only. It won't be visible on your browser.</h4>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<h2 class="section-title">WooCommerce Parameters</h2>

<!-- About  Events -->
<div class="card card-static">
    <div class="card-header">
        About WooCommerce Events Parameters
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col">
                <p>All events get the following Global Parameters for all the tags: <i>page_title, post_type, post_id,
                        landing_page, event_URL, user_role, plugin, event_time (pro),
                        event_day (pro), event_month (pro), traffic_source (pro), UTMs (pro).</i>
                </p>
                <br><br>

                <p>The Meta Pixel (formerly Facebook Pixel) events are Dynamic Ads ready.</p>
                <p>The Google Analytics events track Monetization data (GA4).</p>
                <p>The Google Ads events have the required data for Dynamic Remarketing
                    (<a href = "https://support.google.com/google-ads/answer/7305793" target="_blank">official help</a>).
                </p>
                <p>The Pinterest events have the required data for Dynamic Remarketing.</p>

                <br><br>
                <p>The Purchase event will have the following extra-parameters:
                    <i>category_name, num_items, tags, total (pro), transactions_count (pro), tax (pro),
                        predicted_ltv (pro), average_order (pro), coupon_used (pro), coupon_code (pro), shipping (pro),
                        shipping_cost (pro), fee (pro).</i>
                </p>

            </div>
        </div>
    </div>
</div>

<!-- Control the WooCommerce Parameters -->
<div class="card">
    <div class="card-header">
        Control the WooCommerce Parameters <?php cardCollapseBtn(); ?>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col">
                You can use these parameters to create audiences, custom conversions, or goals. We recommend keeping them active. If you get privacy warnings about some of these parameters, you can turn them OFF.
                <hr>
            </div>
        </div>
        <div class="row">
            <div class="col">
                <?php PYS()->render_switcher_input( 'enable_woo_category_name_param' ); ?>
                <h4 class="switcher-label">category_name</h4>
                <hr>
            </div>
        </div>
        <div class="row">
            <div class="col">
                <?php PYS()->render_switcher_input( 'enable_woo_num_items_param' ); ?>
                <h4 class="switcher-label">num_items</h4>
                <hr>
            </div>
        </div>
        <div class="row">
            <div class="col">
                <?php PYS()->render_switcher_input( 'enable_woo_tags_param' ); ?>
                <h4 class="switcher-label">tags</h4>
                <hr>
            </div>
        </div>
        <div class="row">
            <div class="col">
                <?php PYS()->render_switcher_input( 'enable_woo_fees_param' ); ?>
                <h4 class="switcher-label">fees</h4>
                <hr>
            </div>
        </div>
        <div class="row">
            <div class="col">
                <?php PYS()->render_switcher_input( 'enable_woo_total_param' ); ?>
                <h4 class="switcher-label">total (PRO)</h4>
                <hr>
            </div>
        </div>


        <div class="row">
            <div class="col">
                <?php PYS()->render_switcher_input( 'enable_woo_transactions_count_param' ); ?>
                <h4 class="switcher-label">transactions_count (PRO)</h4>
                <hr>
            </div>
        </div>

        <div class="row">
            <div class="col">
                <?php PYS()->render_switcher_input( 'enable_woo_tax_param' ); ?>
                <h4 class="switcher-label">tax (PRO)</h4>
                <hr>
            </div>
        </div>

        <div class="row">
            <div class="col">
                <?php PYS()->render_switcher_input( 'enable_woo_predicted_ltv_param' ); ?>
                <h4 class="switcher-label">predicted_ltv (PRO)</h4>
                <hr>
            </div>
        </div>

        <div class="row">
            <div class="col">
                <?php PYS()->render_switcher_input( 'enable_woo_average_order_param' ); ?>
                <h4 class="switcher-label">average_order (PRO)</h4>
                <hr>
            </div>
        </div>

        <div class="row">
            <div class="col">
                <?php PYS()->render_switcher_input( 'enable_woo_coupon_used_param' ); ?>
                <h4 class="switcher-label">coupon_used (PRO)</h4>
                <hr>
            </div>
        </div>
        <div class="row">
            <div class="col">
                <?php PYS()->render_switcher_input( 'enable_woo_coupon_name_param' ); ?>
                <h4 class="switcher-label">coupon_name (PRO)</h4>
                <hr>
            </div>
        </div>

        <div class="row">
            <div class="col">
                <?php PYS()->render_switcher_input( 'enable_woo_shipping_param' ); ?>
                <h4 class="switcher-label">shipping (PRO)</h4>
                <hr>
            </div>
        </div>

        <div class="row">
            <div class="col">
                <?php PYS()->render_switcher_input( 'enable_woo_shipping_cost_param' ); ?>
                <h4 class="switcher-label">shipping_cost (PRO)</h4>
                <hr>
            </div>
        </div>



        <div class="row">
            <div class="col">
                <?php PYS()->renderDummySwitcher( true ); ?>
                <h4 class="switcher-label">content_ids (mandatory for DPA)</h4>
                <hr>
            </div>
        </div>

        <div class="row">
            <div class="col">
                <?php PYS()->renderDummySwitcher( true ); ?>
                <h4 class="switcher-label">content_type (mandatory for DPA)</h4>
                <hr>
            </div>
        </div>

        <div class="row">
            <div class="col">
                <?php PYS()->renderDummySwitcher( true ); ?>
                <h4 class="switcher-label">value (mandatory for purchase, you have more options on event level)</h4>
                <hr>
            </div>
        </div>
    </div>
</div>
<?php include PYS_PATH.'/includes/offline_events/view/html-woo-export-customer.php'; ?>

<?php include PYS_PATH.'/includes/offline_events/view/html-woo-export.php'; ?>

<div class="row justify-content-center">
	<div class="col-4">
		<button class="btn btn-block btn-sm btn-save">Save Settings</button>
	</div>
</div>
