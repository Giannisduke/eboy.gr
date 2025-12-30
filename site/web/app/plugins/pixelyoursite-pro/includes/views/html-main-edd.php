<?php

namespace PixelYourSite;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

use PixelYourSite\Ads\Helpers as AdsHelpers;

?>

<h2 class="section-title">EasyDigitalDownloads Settings</h2>

<!-- Enable EDD -->
<div class="card card-static">
    <div class="card-body">
        <div  class="row">
            <div class="col">
                <?php PYS()->render_switcher_input( 'edd_advance_purchase_fb_enabled' ); ?>
                <h4 class="switcher-label">Facebook auto-renewals purchase tracking</h4>
            </div>
        </div>
        <div  class="row">
            <div class="col">
                <?php PYS()->render_switcher_input( 'edd_advance_purchase_ga_enabled' ); ?>
                <h4 class="switcher-label">Google Analytics auto-renewals purchase tracking</h4>
            </div>
        </div>
		<?php if ( Tiktok()->enabled() ) : ?>
            <div  class="row">
                <div class="col">
					<?php Tiktok()->render_switcher_input( 'edd_advance_purchase_enabled' ); ?>
                    <h4 class="switcher-label">TikTok Advanced Purchase Tracking</h4>
                </div>
            </div>
		<?php endif; ?>
		<?php if ( Pinterest()->enabled() ) : ?>
            <div  class="row">
                <div class="col">
					<?php Pinterest()->render_switcher_input( 'edd_advance_purchase_enabled' ); ?>
                    <h4 class="switcher-label">Pinterest Advanced Purchase Tracking</h4>
                </div>
            </div>
		<?php endif; ?>
        <p class="small">
            The plugin will send a Purchase event to Meta and Google using API when auto-renewals take place or when a new order is placed by an admin on the backend. Meta Conversion API token and GA4 Measurement Protocol secret are required.
        </p>
        <div  class="row">
            <div class="col">
                <?php PYS()->render_switcher_input( 'edd_track_refunds_GA' ); ?>
                <h4 class="switcher-label">Track refunds on Goolge Analytics</h4>
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

        <div class="row">
            <div class="col">
                <?php PYS()->render_switcher_input( 'edd_enabled_save_data_to_orders' ); ?>
                <h4 class="switcher-label">Enable Easy Digital Downloads Reports</h4>
                <small class="form-text">Save the <i>landing page, UTMs, client's browser's time, day, and month, the number of orders, lifetime value, and average order</i>.
                    You can view this data when you open an order, or on the <a href="<?=admin_url("admin.php?page=pixelyoursite_edd_reports")?>">Easy Digital Downloads Reports</a> page</small>
            </div>
        </div>
        <div class="row mt-2">
            <div class="col">
                <?php PYS()->render_switcher_input( 'edd_enabled_display_data_to_orders' ); ?>
                <h4 class="switcher-label">Display the tracking data on the order's page</h4>
                <small class="form-check">Show the <i>landing page, traffic source,</i> and <i>UTMs</i> on the order's edit page.</small>
            </div>
        </div>
        <div class="row">
            <div class="col">
                <?php PYS()->render_switcher_input( 'edd_enabled_save_data_to_user' ); ?>
                <h4 class="switcher-label">Display data to the user's profile</h4>
                <small class="form-text">Display <i>the number of orders, lifetime value, and average order</i>.</small>
            </div>
        </div>
        <?php if(isEddRecurringActive()) :?>
            <div class="row">
                <div class="col">
                    <?php PYS()->render_switcher_input( 'edd_enabled_purchase_recurring' ); ?>
                    <h4 class="switcher-label">Renewal Tracking</h4>
                    <small class="form-text">Send the Purchase event to Facebook and Google using API for automatic renewals.</small>
                </div>
            </div>
        <?php endif; ?>
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
                            <?php PYS()->render_radio_input( 'edd_purchase_new_customer_guest', 'yes',
                                'Send it for guest checkout' ); ?>
                            <?php PYS()->render_radio_input( 'edd_purchase_new_customer_guest', 'no',
                                'Don\'t send it for guest checkout' ); ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <hr>
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
                <p>Enhanced Conversions for Google Ads with PixelYourSite (9:14) - <a href="https://www.youtube.com/watch?v=-bN5D_HJyuA" target="_blank">watch now</a></p>
                <p>Track Facebook (META) Ads results with Google Analytics 4 (GA4) using UTMs (10:13) - <a href="https://www.youtube.com/watch?v=v3TfmX5H1Ts" target="_blank">watch now</a></p>
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
                <label>Prefix: </label><?php PYS()->render_text_input("edd_order_id_prefix","Prefix"); ?>
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
<div class="card">
    <div class="card-header">
        When to fire the add to cart event<?php cardCollapseBtn(); ?>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col">
                <div class="custom-controls-stacked">
                   <?php PYS()->render_checkbox_input( 'edd_add_to_cart_on_button_click', 'On Add To Cart button clicks' ); ?>
                   <?php PYS()->render_checkbox_input( 'edd_add_to_cart_on_checkout_page', 'On Checkout Page' ); ?>
               </div>
           </div>
       </div>
   </div>
</div>
<h2 class="section-title">ID Settings</h2>
<?php if ( Facebook()->enabled() ) : ?>

<!-- Facebook ID -->
<div class="card">
    <div class="card-header">
        Facebook ID setting<?php cardCollapseBtn(); ?>
    </div>
    <div class="card-body">
        <div class="row mb-3">
            <div class="col col-offset-left form-inline">
                <label>content_id</label>
                <?php Facebook()->render_select_input( 'edd_content_id',
                    array(
                        'download_id' => 'Download ID',
                        'download_sku' => 'Download SKU',
                    )
                ); ?>
            </div>
        </div>
        <div class="row mb-3">
            <div class="col col-offset-left form-inline">
                <label>content_id prefix</label><?php Facebook()->render_text_input( 'edd_content_id_prefix', '(optional)' ); ?>
            </div>
        </div>
        <div class="row">
            <div class="col col-offset-left form-inline">
                <label>content_id suffix</label><?php Facebook()->render_text_input( 'edd_content_id_suffix', '(optional)' ); ?>
            </div>
        </div>
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
                <div class="col col-offset-left form-inline">
                    <label>ecomm_prodid</label>
                    <?php GATags()->render_select_input( 'edd_content_id',
                        array(
                            'download_id' => 'Download ID',
                            'download_sku'   => 'Download SKU',
                        )
                    ); ?>
                </div>
            </div>
            <div class="row mb-3">
                <div class="col col-offset-left form-inline">
                    <label>ecomm_prodid prefix</label><?php GATags()->render_text_input( 'edd_content_id_prefix', '(optional)' ); ?>
                </div>
            </div>
            <div class="row">
                <div class="col col-offset-left form-inline">
                    <label>ecomm_prodid suffix</label><?php GATags()->render_text_input( 'edd_content_id_suffix', '(optional)' ); ?>
                </div>
            </div>
        </div>
    </div>
<?php endif; ?>

<?php if ( Pinterest()->enabled() ) : ?>

    <div class="card" id="pys-section-ga-id">
        <div class="card-header">
            Pinterest Tag ID setting<?php cardCollapseBtn(); ?>
        </div>
        <div class="card-body">

            <div class="row mb-3">
                <div class="col col-offset-left form-inline">
                    <label>ID</label>
                    <?php Pinterest()->render_select_input( 'edd_content_id',
                        array(
                            'download_id' => 'Download ID',
                            'download_sku'   => 'Download SKU',
                        )
                    ); ?>
                </div>
            </div>
            <div class="row mb-3">
                <div class="col-11 col-offset-left form-inline">
                    <label>ID prefix</label><?php Pinterest()->render_text_input( 'edd_content_id_prefix',
                        '(optional)' ); ?>
                </div>
            </div>
            <div class="row">
                <div class="col-11 col-offset-left form-inline">
                    <label>ID suffix</label><?php Pinterest()->render_text_input( 'edd_content_id_suffix',
                        '(optional)' ); ?>
                </div>
            </div>
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
            Bing ID setting<?php cardCollapseBtn(); ?>
        </div>
        <div class="card-body">
            <div class="row mb-3">
                <div class="col col-offset-left form-inline">
                    <label>ID</label>
                    <?php Bing()->render_select_input( 'edd_content_id',
                        array(
                            'download_id' => 'Download ID',
                            'download_sku'   => 'Download SKU',
                        )
                    ); ?>
                </div>
            </div>
            <div class="row mb-3">
                <div class="col-11 col-offset-left form-inline">
                    <label>ID prefix</label><?php Bing()->render_text_input( 'edd_content_id_prefix',
                        '(optional)' ); ?>
                </div>
            </div>
            <div class="row">
                <div class="col-11 col-offset-left form-inline">
                    <label>ID suffix</label><?php Bing()->render_text_input( 'edd_content_id_suffix',
                        '(optional)' ); ?>
                </div>
            </div>
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
                <div class="col col-offset-left form-inline">
                    <label>ecomm_prodid</label>
                    <?php GTM()->render_select_input( 'edd_content_id',
                        array(
                            'download_id' => 'Download ID',
                            'download_sku'   => 'Download SKU',
                        )
                    ); ?>
                </div>
            </div>
            <div class="row mb-3">
                <div class="col col-offset-left form-inline">
                    <label>ecomm_prodid prefix</label><?php GTM()->render_text_input( 'edd_content_id_prefix', '(optional)' ); ?>
                </div>
            </div>
            <div class="row">
                <div class="col col-offset-left form-inline">
                    <label>ecomm_prodid suffix</label><?php GTM()->render_text_input( 'edd_content_id_suffix', '(optional)' ); ?>
                </div>
            </div>
        </div>
    </div>
<?php endif; ?>

<hr>

<!-- Google Dynamic Remarketing Vertical -->
<?php if ( GA()->enabled() || Ads()->enabled() ) : ?>

<div class="card ">
    <div class="card-header">
        Google Dynamic Remarketing Vertical <?php cardCollapseBtn(); ?>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-11">
                <div class="custom-controls-stacked">
                  <?php PYS()->render_radio_input( 'google_retargeting_logic', 'ecomm',
                  'Use Retail Vertical  (select this if you have access to Google Merchant)' ); ?>
                  <?php PYS()->render_radio_input( 'google_retargeting_logic', 'dynx',
                  'Use Custom Vertical (select this if Google Merchant is not available for your country)' ); ?>
              </div>
          </div>
          <div class="col-1">
           <?php renderPopoverButton( 'google_dynamic_remarketing_vertical' ); ?>
       </div>
   </div>
</div>
</div>

<?php endif; ?>

<!-- Event Value -->
<div class="card">
    <div class="card-header">
        Value Settings <?php cardCollapseBtn(); ?>
    </div>
    <div class="card-body">
        <div class="row mb-3">
            <div class="col">
                <div class="custom-controls-stacked">
                   <?php PYS()->render_radio_input( 'edd_event_value', 'price', 'Use EasyDigitalDownloads price settings' ); ?>
                   <?php PYS()->render_radio_input( 'edd_event_value', 'custom', 'Customize Tax' ); ?>
               </div>
           </div>
       </div>
       <div class="row mb-3 edd-event-value-option" style="display: none;">
        <div class="col col-offset-left form-inline">
            <?php PYS()->render_select_input( 'edd_tax_option',
               array(
                  'included' => 'Include Tax',
                  'excluded' => 'Exclude Tax',
              )
          ); ?>
      </div>
  </div>
  <div class="row">
    <div class="col">
        <h4 class="label">Lifetime Customer Value</h4>
        <?php PYS()->render_multi_select_input( 'edd_ltv_order_statuses', edd_get_payment_statuses() ); ?>
    </div>
</div>
</div>
</div>

<hr>
<h2 class="section-title">Recommended events</h2>
<!-- Purchase -->
<div class="card">
    <div class="card-header has_switch">
        <?php PYS()->render_switcher_input( 'edd_purchase_enabled' ); ?> Track Purchases <?php cardCollapseBtn(); ?>
    </div>
    <div class="card-body">
        <?php PYS()->renderValueOptionsBlock('edd_purchase', false);?>
        <hr>
        <div class="row mb-1">
            <div class="col-11">
               <?php PYS()->render_checkbox_input( 'edd_purchase_on_transaction', 'Fire the event on transaction only' ); ?>
            </div>
            <div class="col-1">
             <?php renderPopoverButton( 'edd_purchase_on_transaction' ); ?>
            </div>
        </div>
        <div class="row mb-3">
            <div class="col-12">
                <?php PYS()->render_checkbox_input( 'edd_purchase_not_fire_for_zero', "Don't fire the event for 0 value transactions" ); ?>
            </div>
        </div>
        <div class="row mb-3">
            <div class="col-12">
                <?php PYS()->render_checkbox_input( 'edd_purchase_not_fire_for_zero_items', "Don't fire the event when the number of items is 0" ); ?>
            </div>
        </div>

     <?php if ( Facebook()->enabled() ) : ?>
     <div class="row mb-1">
        <div class="col">
            <?php Facebook()->render_switcher_input( 'edd_purchase_enabled' ); ?>
            <h4 class="switcher-label">Enable the Purchase event on Facebook (required for DPA)</h4>
        </div>
    </div>
<?php endif; ?>

    <?php if ( Pinterest()->enabled() ) : ?>
<div class="row">
    <div class="col">
        <?php Pinterest()->render_switcher_input( 'edd_checkout_enabled' ); ?>
        <h4 class="switcher-label">Enable the Checkout event on Pinterest</h4>
        <?php Pinterest()->renderAddonNotice(); ?>
    </div>
</div>
<?php endif; ?>


    <?php if ( GA()->enabled() ) : ?>
<div class="row mb-1">
    <div class="col">
        <?php GA()->render_switcher_input( 'edd_purchase_enabled' ); ?>
        <h4 class="switcher-label">Enable the purchase event on Google Analytics</h4>
    </div>
</div>

<?php endif; ?>

    <?php if ( Ads()->enabled() ) : ?>
<div class="row">
    <div class="col">
        <?php Ads()->render_switcher_input( 'edd_purchase_enabled' ); ?>
        <h4 class="switcher-label">Enable the purchase event on Google Ads</h4>
    </div>
</div>
<?php AdsHelpers\renderConversionLabelInputs( 'edd_purchase' ); ?>
        <div class="row mb-2">
            <div class="col col-offset-left">
                <label class="label-inline">EDD Purchase event, Google Ads lables:</label>
                <div class="custom-controls-stacked">
                    <?php Ads()->render_radio_input( 'edd_purchase_conversion_track', 'conversion', 'Fire a conversion event along with the default Purchase event' ); ?>
                    <?php Ads()->render_radio_input( 'edd_purchase_conversion_track', 'current_event',
                        'Add the conversion label to the Purchase event' ); ?>
                </div>
            </div>
        </div>
<?php endif; ?>

    <?php if ( Bing()->enabled() ) : ?>
<div class="row">
    <div class="col-11">
        <?php Bing()->render_switcher_input( 'edd_purchase_enabled' ); ?>
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
                <?php Tiktok()->render_switcher_input( 'edd_purchase_enabled' ); ?>
                <h4 class="switcher-label">Enable the PlaceAnOrder event on TikTok</h4>
            </div>
        </div>
        <div class="row">
            <div class="col-11">
                <?php Tiktok()->render_switcher_input( 'edd_complete_payment_enabled' ); ?>
                <h4 class="switcher-label">Enable the CompletePayment event on TikTok</h4>
            </div>
        </div>
    <?php endif; ?>
        <hr class="mb-3 mt-3">
        <?php if ( GTM()->enabled() ) : ?>
            <div class="row mb-1">
                <div class="col">
                    <?php GTM()->render_switcher_input( 'edd_purchase_enabled' ); ?>
                    <h4 class="switcher-label">Enable the purchase event on GTM dataLayer</h4>
                </div>
            </div>
        <?php endif; ?>
        <hr class="mb-3 mt-3">
<div class="row mt-3">
    <div class="col">
        <p class="mb-0">*This event will be fired on the order-received, the default Easy Digital Downloads
            "thank you page". If you use PayPal, make sure that auto-return is ON. If you want to use "custom
            thank you pages", you must configure them with our
            <a href="https://www.pixelyoursite.com/super-pack" target="_blank">Super Pack</a>.
        </p>
    </div>
</div>
</div>
</div>

<!-- InitiateCheckout -->
<div class="card">
    <div class="card-header has_switch">
        <?php PYS()->render_switcher_input( 'edd_initiate_checkout_enabled' ); ?>Track the Checkout Page <?php cardCollapseBtn(); ?>
    </div>
    <div class="card-body">
        <?php PYS()->renderValueOptionsBlock('edd_initiate_checkout', false);?>
        <hr>
    <?php if ( Facebook()->enabled() ) : ?>
       <div class="row">
        <div class="col">
            <?php Facebook()->render_switcher_input( 'edd_initiate_checkout_enabled' ); ?>
            <h4 class="switcher-label">Enable the InitiateCheckout event on Facebook</h4>
        </div>
    </div>
<?php endif; ?>
	    <?php if ( GA()->enabled() ) : ?>
            <div class="row mb-1">
                <div class="col">
				    <?php GA()->render_switcher_input( 'edd_initiate_checkout_enabled' ); ?>
                    <h4 class="switcher-label">Enable the begin_checkout event on Google Analytics</h4>
                </div>
            </div>

	    <?php endif; ?>
	    <?php if ( Ads()->enabled() ) : ?>
            <div class="row mb-1">
                <div class="col">
				    <?php Ads()->render_switcher_input( 'edd_initiate_checkout_enabled' ); ?>
                    <h4 class="switcher-label">Enable the begin_checkout event on Google Ads</h4>
                </div>
            </div>
		    <?php AdsHelpers\renderConversionLabelInputs( 'edd_initiate_checkout' ); ?>
            <div class="row mb-2">
                <div class="col col-offset-left">
                    <label class="label-inline">EDD begin_checkout event, Google Ads lables:</label>
                    <div class="custom-controls-stacked">
					    <?php Ads()->render_radio_input( 'edd_initiate_checkout_conversion_track', 'conversion', 'Fire a conversion event along with the default begin_checkout event' ); ?>
					    <?php Ads()->render_radio_input( 'edd_initiate_checkout_conversion_track', 'current_event',
						    'Add the conversion label to the begin_checkout event' ); ?>
                    </div>
                </div>
            </div>
	    <?php endif; ?>
    <?php if ( Pinterest()->enabled() ) : ?>
<div class="row">
    <div class="col">
        <?php Pinterest()->render_switcher_input( 'edd_initiate_checkout_enabled' ); ?>
        <h4 class="switcher-label">Enable the InitiateCheckout on Pinterest</h4>
        <?php Pinterest()->renderAddonNotice(); ?>
    </div>
</div>
<?php endif; ?>

    <?php if ( Bing()->enabled() ) : ?>
<div class="row">
    <div class="col">
        <?php Bing()->render_switcher_input( 'edd_initiate_checkout_enabled' ); ?>
        <h4 class="switcher-label">Enable the InitiateCheckout on Bing</h4>
        <?php Bing()->renderAddonNotice(); ?>
    </div>
</div>
<?php endif; ?>

    <?php if ( Tiktok()->enabled() ) : ?>
            <div class="row">
                <div class="col">
                    <?php Tiktok()->render_switcher_input( 'edd_initiate_checkout_enabled' ); ?>
                    <h4 class="switcher-label">Enable the InitiateCheckout on TikTok</h4>
                </div>
            </div>
        <?php endif; ?>
        <hr class="mb-3 mt-3">
        <?php if ( GTM()->enabled() ) : ?>
            <div class="row mb-1">
                <div class="col">
                    <?php GTM()->render_switcher_input( 'edd_initiate_checkout_enabled' ); ?>
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
        <?php PYS()->render_switcher_input( 'edd_add_to_cart_enabled' ); ?>Track add to cart <?php cardCollapseBtn(); ?>
    </div>
    <div class="card-body">
        <?php PYS()->renderValueOptionsBlock('edd_add_to_cart', false);?>
        <hr>
       <?php if ( Facebook()->enabled() ) : ?>
       <div class="row">
        <div class="col">
            <?php Facebook()->render_switcher_input( 'edd_add_to_cart_enabled' ); ?>
            <h4 class="switcher-label">Enable the AddToCart event on Facebook (required for DPA)</h4>
        </div>
    </div>
<?php endif; ?>

    <?php if ( Pinterest()->enabled() ) : ?>
    <div class="row">
        <div class="col">
            <?php Pinterest()->render_switcher_input( 'edd_add_to_cart_enabled' ); ?>
            <h4 class="switcher-label">Enable the AddToCart event on Pinterest</h4>
            <?php Pinterest()->renderAddonNotice(); ?>
        </div>
    </div>
    <?php endif; ?>


    <?php if ( GA()->enabled() ) : ?>
    <div class="row mb-1">
        <div class="col">
            <?php GA()->render_switcher_input( 'edd_add_to_cart_enabled' ); ?>
            <h4 class="switcher-label">Enable the add_to_cart event on Google Analytics</h4>
        </div>
    </div>

    <?php endif; ?>

    <?php if ( Ads()->enabled() ) : ?>
    <div class="row">
        <div class="col">
            <?php Ads()->render_switcher_input( 'edd_add_to_cart_enabled' ); ?>
            <h4 class="switcher-label">Enable the add_to_cart event on Google Ads</h4>
        </div>
    </div>
    <?php AdsHelpers\renderConversionLabelInputs( 'edd_add_to_cart' ); ?>
    <div class="row mb-2">
        <div class="col col-offset-left">
            <label class="label-inline">EDD add_to_cart event, Google Ads lables:</label>
            <div class="custom-controls-stacked">
                <?php Ads()->render_radio_input( 'edd_add_to_cart_conversion_track', 'conversion', 'Fire a conversion event along with the default add_to_cart event' ); ?>
                <?php Ads()->render_radio_input( 'edd_add_to_cart_conversion_track', 'current_event',
                    'Add the conversion label to the add_to_cart event' ); ?>
            </div>
        </div>
    </div>
    <?php endif; ?>
    <?php if ( Bing()->enabled() ) : ?>
    <div class="row">
        <div class="col">
            <?php Bing()->render_switcher_input( 'edd_add_to_cart_enabled' ); ?>
            <h4 class="switcher-label">Enable the AddToCart event on Bing</h4>
            <?php Bing()->renderAddonNotice(); ?>
        </div>
    </div>
    <?php endif; ?>

    <?php if ( Tiktok()->enabled() ) : ?>
            <div class="row">
                <div class="col">
                    <?php Tiktok()->render_switcher_input( 'edd_add_to_cart_enabled' ); ?>
                    <h4 class="switcher-label">Enable the AddToCart event on TikTok</h4>
                </div>
            </div>
        <?php endif; ?>
        <hr class="mb-3 mt-3">
        <?php if ( GTM()->enabled() ) : ?>
            <div class="row mb-1">
                <div class="col">
                    <?php GTM()->render_switcher_input( 'edd_add_to_cart_enabled' ); ?>
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
        <?php PYS()->render_switcher_input( 'edd_view_content_enabled' ); ?>Track product pages <?php cardCollapseBtn(); ?>
    </div>
    <div class="card-body">
        <?php PYS()->renderValueOptionsBlock('edd_view_content', false);?>
        <hr>
       <?php if ( Facebook()->enabled() ) : ?>
       <div class="row">
        <div class="col">
            <?php Facebook()->render_switcher_input( 'edd_view_content_enabled' ); ?>
            <h4 class="switcher-label">Enable the ViewContent on Facebook (required for DPA)</h4>
        </div>
    </div>
<?php endif; ?>

<?php if ( Pinterest()->enabled() ) : ?>
<div class="row">
    <div class="col">
        <?php Pinterest()->render_switcher_input( 'edd_page_visit_enabled' ); ?>
        <h4 class="switcher-label">Enable the PageVisit event on Pinterest</h4>
        <?php Pinterest()->renderAddonNotice(); ?>
    </div>
</div>
<?php endif; ?>

<div class="row my-3">
    <div class="col col-offset-left form-inline">
        <label>Delay</label>
        <?php PYS()->render_number_input( 'edd_view_content_delay' ); ?>
        <label>seconds</label>
    </div>
</div>

<?php if ( GA()->enabled() ) : ?>
<div class="row mb-1">
    <div class="col">
        <?php GA()->render_switcher_input( 'edd_view_content_enabled' ); ?>
        <h4 class="switcher-label">Enable the view_item event on Google Analytics</h4>
    </div>
</div>

<?php endif; ?>

<?php if ( Ads()->enabled() ) : ?>
<div class="row">
    <div class="col">
        <?php Ads()->render_switcher_input( 'edd_view_content_enabled' ); ?>
        <h4 class="switcher-label">Enable the view_item event on Google Ads</h4>
    </div>
</div>
<?php AdsHelpers\renderConversionLabelInputs( 'edd_view_content' ); ?>
    <div class="row mb-2">
        <div class="col col-offset-left">
            <label class="label-inline">EDD view_item event, Google Ads lables:</label>
            <div class="custom-controls-stacked">
				<?php Ads()->render_radio_input( 'edd_view_content_conversion_track', 'conversion', 'Fire a conversion event along with the default view_item event' ); ?>
				<?php Ads()->render_radio_input( 'edd_view_content_conversion_track', 'current_event',
					'Add the conversion label to the view_item event' ); ?>
            </div>
        </div>
    </div>
<?php endif; ?>
    <?php if ( Tiktok()->enabled() ) : ?>
        <div class="row">
            <div class="col">
                <?php Tiktok()->render_switcher_input( 'edd_view_content_enabled' ); ?>
                <h4 class="switcher-label">Enable the ViewContent event on Tiktok</h4>
            </div>
        </div>
    <?php endif; ?>
<?php if ( Bing()->enabled() ) : ?>
<div class="row">
    <div class="col">
        <?php Bing()->render_switcher_input( 'edd_view_content_enabled' ); ?>
        <h4 class="switcher-label">Enable the PageVisit event on Bing</h4>
        <?php Bing()->renderAddonNotice(); ?>
    </div>
</div>
<?php endif; ?>
        <hr class="mb-3 mt-3">
        <?php if ( GTM()->enabled() ) : ?>
            <div class="row mb-1">
                <div class="col">
                    <?php GTM()->render_switcher_input( 'edd_view_content_enabled' ); ?>
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
        <?php PYS()->render_switcher_input( 'edd_view_category_enabled' ); ?>Track product category pages <?php cardCollapseBtn(); ?>
    </div>
    <div class="card-body">

       <?php if ( Facebook()->enabled() ) : ?>
       <div class="row">
        <div class="col">
            <?php Facebook()->render_switcher_input( 'edd_view_category_enabled' ); ?>
            <h4 class="switcher-label">Enable the ViewCategory event on Facebook Analytics (used for DPA)</h4>
        </div>
    </div>
<?php endif; ?>

<?php if ( GA()->enabled() ) : ?>
<div class="row mb-1">
    <div class="col">
        <?php GA()->render_switcher_input( 'edd_view_category_enabled' ); ?>
        <h4 class="switcher-label">Enable the view_item_list event on Google Analytics</h4>
    </div>
</div>

<?php endif; ?>

<?php if ( Ads()->enabled() ) : ?>
<div class="row">
    <div class="col">
        <?php Ads()->render_switcher_input( 'edd_view_category_enabled' ); ?>
        <h4 class="switcher-label">Enable the view_item_list event on Google Ads</h4>
    </div>
</div>
<?php AdsHelpers\renderConversionLabelInputs( 'edd_view_category' ); ?>
    <div class="row mb-2">
        <div class="col col-offset-left">
            <label class="label-inline">EDD view_item_list event, Google Ads lables:</label>
            <div class="custom-controls-stacked">
				<?php Ads()->render_radio_input( 'edd_view_category_conversion_track', 'conversion', 'Fire a conversion event along with the default view_item_list event' ); ?>
				<?php Ads()->render_radio_input( 'edd_view_category_conversion_track', 'current_event',
					'Add the conversion label to the view_item_list event' ); ?>
            </div>
        </div>
    </div>
<?php endif; ?>
<?php if ( Pinterest()->enabled() ) : ?>
<div class="row">
    <div class="col">
        <?php Pinterest()->render_switcher_input( 'edd_view_category_enabled' ); ?>
        <h4 class="switcher-label">Enable the ViewCategory event on Pinterest</h4>
        <?php Pinterest()->renderAddonNotice(); ?>
    </div>
</div>
<?php endif; ?>

<?php if ( Bing()->enabled() ) : ?>
<div class="row">
    <div class="col">
        <?php Bing()->render_switcher_input( 'edd_view_category_enabled' ); ?>
        <h4 class="switcher-label">Enable the ViewCategory event on Bing</h4>
        <?php Bing()->renderAddonNotice(); ?>
    </div>
</div>
<?php endif; ?>
        <hr class="mb-3 mt-3">
        <?php if ( GTM()->enabled() ) : ?>
            <div class="row mb-1">
                <div class="col">
                    <?php GTM()->render_switcher_input( 'edd_view_category_enabled' ); ?>
                    <h4 class="switcher-label">Enable the ViewCategory event on GTM dataLayer</h4>
                </div>
            </div>
        <?php endif; ?>
        <hr class="mb-3 mt-3">
</div>
</div>

<h2 class="section-title">Advanced Marketing Events</h2>

<!-- FrequentShopper -->
<div class="card">
    <div class="card-header has_switch">
        <?php PYS()->render_switcher_input( 'edd_frequent_shopper_enabled' ); ?>FrequentShopper Event <?php cardCollapseBtn(); ?>
    </div>
    <div class="card-body">

        <?php if ( Facebook()->enabled() ) : ?>
            <div class="row">
                <div class="col">
                    <?php Facebook()->render_switcher_input( 'edd_frequent_shopper_enabled' ); ?>
                    <h4 class="switcher-label">Send the event to Facebook</h4>
                </div>
            </div>
        <?php endif; ?>

        <?php if ( GA()->enabled() ) : ?>
            <div class="row mb-1">
                <div class="col">
                    <?php GA()->render_switcher_input( 'edd_frequent_shopper_enabled' ); ?>
                    <h4 class="switcher-label">Send the event to Google Analytics</h4>
                </div>
            </div>
        <?php endif; ?>

		<?php if ( Ads()->enabled() ) : ?>
            <div class="row">
                <div class="col">
					<?php Ads()->render_switcher_input( 'edd_frequent_shopper_enabled' ); ?>
                    <h4 class="switcher-label">Enable on Google Ads</h4>
                </div>
            </div>
			<?php AdsHelpers\renderConversionLabelInputs( 'edd_frequent_shopper' ); ?>
		<?php endif; ?>

        <?php if ( Pinterest()->enabled() ) : ?>
            <div class="row">
                <div class="col">
                    <?php Pinterest()->render_switcher_input( 'edd_frequent_shopper_enabled' ); ?>
                    <h4 class="switcher-label">Enable on Pinterest</h4>
                    <?php Pinterest()->renderAddonNotice(); ?>
                </div>
            </div>
        <?php endif; ?>

        <?php if ( Bing()->enabled() ) : ?>
            <div class="row">
                <div class="col">
                    <?php Bing()->render_switcher_input( 'edd_frequent_shopper_enabled' ); ?>
                    <h4 class="switcher-label">Enable on Bing</h4>
                    <?php Bing()->renderAddonNotice(); ?>
                </div>
            </div>
        <?php endif; ?>

		<?php if ( Tiktok()->enabled() ) : ?>
            <div class="row">
                <div class="col">
					<?php Tiktok()->render_switcher_input( 'edd_frequent_shopper_enabled' ); ?>
                    <h4 class="switcher-label">Enable on TikTok</h4>
                </div>
            </div>
		<?php endif; ?>
        <hr class="mb-3 mt-3">
        <?php if ( GTM()->enabled() ) : ?>
            <div class="row mb-1">
                <div class="col">
                    <?php GTM()->render_switcher_input( 'edd_frequent_shopper_enabled' ); ?>
                    <h4 class="switcher-label">Enable on GTM dataLayer</h4>
                </div>
            </div>
        <?php endif; ?>
        <hr class="mb-3 mt-3">
        <div class="row mt-3">
            <div class="col col-offset-left form-inline">
                <label>Fire this event when the client has at least </label>
                <?php PYS()->render_number_input( 'edd_frequent_shopper_transactions' ); ?>
                <label>transactions</label>
            </div>
        </div>
    </div>
</div>

<!-- VipClient -->
<div class="card">
    <div class="card-header has_switch">
        <?php PYS()->render_switcher_input( 'edd_vip_client_enabled' ); ?>VIPClient Event <?php cardCollapseBtn(); ?>
    </div>
    <div class="card-body">

        <?php if ( Facebook()->enabled() ) : ?>
            <div class="row">
                <div class="col">
                    <?php Facebook()->render_switcher_input( 'edd_vip_client_enabled' ); ?>
                    <h4 class="switcher-label">Send the event to Facebook</h4>
                </div>
            </div>
        <?php endif; ?>

        <?php if ( GA()->enabled() ) : ?>
            <div class="row mb-1">
                <div class="col">
                    <?php GA()->render_switcher_input( 'edd_vip_client_enabled' ); ?>
                    <h4 class="switcher-label">Send the event to Google Analytics</h4>
                </div>
            </div>
        <?php endif; ?>

		<?php if ( Ads()->enabled() ) : ?>
            <div class="row">
                <div class="col">
					<?php Ads()->render_switcher_input( 'edd_vip_client_enabled' ); ?>
                    <h4 class="switcher-label">Enable on Google Ads</h4>
                </div>
            </div>
			<?php AdsHelpers\renderConversionLabelInputs( 'edd_vip_client' ); ?>
		<?php endif; ?>

        <?php if ( Pinterest()->enabled() ) : ?>
            <div class="row">
                <div class="col">
                    <?php Pinterest()->render_switcher_input( 'edd_vip_client_enabled' ); ?>
                    <h4 class="switcher-label">Enable on Pinterest</h4>
                    <?php Pinterest()->renderAddonNotice(); ?>
                </div>
            </div>
        <?php endif; ?>

        <?php if ( Bing()->enabled() ) : ?>
            <div class="row">
                <div class="col">
                    <?php Bing()->render_switcher_input( 'edd_vip_client_enabled' ); ?>
                    <h4 class="switcher-label">Enable on Bing</h4>
                    <?php Bing()->renderAddonNotice(); ?>
                </div>
            </div>
        <?php endif; ?>

		<?php if ( Tiktok()->enabled() ) : ?>
            <div class="row">
                <div class="col">
					<?php Tiktok()->render_switcher_input( 'edd_vip_client_enabled' ); ?>
                    <h4 class="switcher-label">Enable on TikTok</h4>
                </div>
            </div>
		<?php endif; ?>
        <hr class="mb-3 mt-3">
        <?php if ( GTM()->enabled() ) : ?>
            <div class="row mb-1">
                <div class="col">
                    <?php GTM()->render_switcher_input( 'edd_vip_client_enabled' ); ?>
                    <h4 class="switcher-label">Enable on GTM dataLayer</h4>
                </div>
            </div>
        <?php endif; ?>
        <hr class="mb-3 mt-3">
        <div class="row mt-3">
            <div class="col col-offset-left form-inline">
                <label>Fire this event when the client has at least</label>
                <?php PYS()->render_number_input( 'edd_vip_client_transactions' ); ?>
                <label>transactions and average order is at least</label>
                <?php PYS()->render_number_input( 'edd_vip_client_average_value' ); ?>
            </div>
        </div>
    </div>
</div>

<!-- BigWhale -->
<div class="card">
    <div class="card-header has_switch">
        <?php PYS()->render_switcher_input( 'edd_big_whale_enabled' ); ?> BigWhale Event <?php cardCollapseBtn(); ?>
    </div>
    <div class="card-body">

        <?php if ( Facebook()->enabled() ) : ?>
            <div class="row">
                <div class="col">
                    <?php Facebook()->render_switcher_input( 'edd_big_whale_enabled' ); ?>
                    <h4 class="switcher-label">Send the event to Facebook</h4>
                </div>
            </div>
        <?php endif; ?>

        <?php if ( GA()->enabled() ) : ?>
            <div class="row mb-1">
                <div class="col">
                    <?php GA()->render_switcher_input( 'edd_big_whale_enabled' ); ?>
                    <h4 class="switcher-label">Send the event to Google Analytics</h4>
                </div>
            </div>
        <?php endif; ?>

		<?php if ( Ads()->enabled() ) : ?>
            <div class="row">
                <div class="col">
					<?php Ads()->render_switcher_input( 'edd_big_whale_enabled' ); ?>
                    <h4 class="switcher-label">Enable on Google Ads</h4>
                </div>
            </div>
			<?php AdsHelpers\renderConversionLabelInputs( 'edd_big_whale' ); ?>
		<?php endif; ?>

        <?php if ( Pinterest()->enabled() ) : ?>
            <div class="row">
                <div class="col">
                    <?php Pinterest()->render_switcher_input( 'edd_big_whale_enabled' ); ?>
                    <h4 class="switcher-label">Enable on Pinterest</h4>
                    <?php Pinterest()->renderAddonNotice(); ?>
                </div>
            </div>
        <?php endif; ?>

        <?php if ( Bing()->enabled() ) : ?>
            <div class="row">
                <div class="col">
                    <?php Bing()->render_switcher_input( 'edd_big_whale_enabled' ); ?>
                    <h4 class="switcher-label">Enable on Bing</h4>
                    <?php Bing()->renderAddonNotice(); ?>
                </div>
            </div>
        <?php endif; ?>

		<?php if ( Tiktok()->enabled() ) : ?>
            <div class="row">
                <div class="col">
					<?php Tiktok()->render_switcher_input( 'edd_big_whale_enabled' ); ?>
                    <h4 class="switcher-label">Enable on TikTok</h4>
                </div>
            </div>
		<?php endif; ?>
        <hr class="mb-3 mt-3">
        <?php if ( GTM()->enabled() ) : ?>
            <div class="row mb-1">
                <div class="col">
                    <?php GTM()->render_switcher_input( 'edd_big_whale_enabled' ); ?>
                    <h4 class="switcher-label">Enable on GTM dataLayer</h4>
                </div>
            </div>
        <?php endif; ?>
        <hr class="mb-3 mt-3">
        <div class="row mt-3">
            <div class="col col-offset-left form-inline">
                <label>Fire this event when the client has LTV at least</label>
                <?php PYS()->render_number_input( 'edd_big_whale_ltv' ); ?>
            </div>
        </div>
    </div>
</div>

<hr>

<!-- RemoveFromCart -->
<div class="card">
    <div class="card-header has_switch">
        <?php PYS()->render_switcher_input( 'edd_remove_from_cart_enabled' ); ?>Track remove from cart <?php cardCollapseBtn(); ?>
    </div>
    <div class="card-body">

        <?php if ( Facebook()->enabled() ) : ?>
            <div class="row">
                <div class="col">
                    <?php Facebook()->render_switcher_input( 'edd_remove_from_cart_enabled' ); ?>
                    <h4 class="switcher-label">Enable the RemoveFromCart event on Facebook</h4>
                </div>
            </div>
        <?php endif; ?>

        <?php if ( GA()->enabled() ) : ?>
            <div class="row mb-1">
                <div class="col">
                    <?php GA()->render_switcher_input( 'edd_remove_from_cart_enabled' ); ?>
                    <h4 class="switcher-label">Enable the remove_from_cart event on Google Analytics</h4>
                </div>
            </div>

        <?php endif; ?>



        <?php if ( Pinterest()->enabled() ) : ?>
            <div class="row">
                <div class="col">
                    <?php Pinterest()->render_switcher_input( 'edd_remove_from_cart_enabled' ); ?>
                    <h4 class="switcher-label">Enable the RemoveFromCart event on Pinterest</h4>
                    <?php Pinterest()->renderAddonNotice(); ?>
                </div>
            </div>
        <?php endif; ?>
        <hr class="mb-3 mt-3">
        <?php if ( GTM()->enabled() ) : ?>
            <div class="row mb-1">
                <div class="col">
                    <?php GTM()->render_switcher_input( 'edd_remove_from_cart_enabled' ); ?>
                    <h4 class="switcher-label">Enable the RemoveFromCart event on GTM dataLayer</h4>
                </div>
            </div>
        <?php endif; ?>
        <hr class="mb-3 mt-3">
    </div>
</div>

<h2 class="section-title">EDD Parameters</h2>
<!-- About EDD Events Parameters -->
<div class="card card-static">
    <div class="card-header">
        About EDD Events Parameters
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col">
                <p>All events get the following parameters for all the tags: <i>page_title, post_type, post_id, landing_page, event_URL, user_role, plugin, event_time (pro), event_day (pro), event_month (pro), traffic_source (pro), UTMs (pro).</i></p>
                <p>The Purchase event will have the following extra-parameters: <i>category_name, num_items, tags, total (pro), transactions_count (pro), tax (pro), predicted_ltv (pro), average_order (pro), coupon_used (pro), coupon_code (pro), shipping (pro), shipping_cost (pro).</i></p>
                <p>The Meta Pixel (formerly Facebook Pixel) events are Dynamic Ads ready.</p>
                <p>The Google Analytics events track Monetization data (GA4).</p>
                <p>The Google Ads events have the required data for Dynamic Remarketing (<a href = "https://support.google.com/google-ads/answer/7305793" target="_blank">official help</a>). </p>
                <p>The Pinterest events have the required data for Dynamic Remarketing.</p>
            </div>
        </div>
    </div>
</div>

<!-- Control the EDD Parameters -->
<div class="card">
    <div class="card-header">
        Control the EDD Parameters <?php cardCollapseBtn(); ?>
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
                <?php PYS()->render_switcher_input( 'enable_edd_category_name_param' ); ?>
                <h4 class="switcher-label">category_name</h4>
                <hr>
            </div>
        </div>
        <div class="row">
            <div class="col">
                <?php PYS()->render_switcher_input( 'enable_edd_num_items_param' ); ?>
                <h4 class="switcher-label">num_items</h4>
                <hr>
            </div>
        </div>
        <div class="row">
            <div class="col">
                <?php PYS()->render_switcher_input( 'enable_edd_tags_param' ); ?>
                <h4 class="switcher-label">tags</h4>
                <hr>
            </div>
        </div>

        <div class="row">
            <div class="col">
                <?php PYS()->render_switcher_input( 'enable_edd_total_param' ); ?>
                <h4 class="switcher-label">total</h4>
                <hr>
            </div>
        </div>

        <div class="row">
            <div class="col">
                <?php PYS()->render_switcher_input( 'enable_edd_tax_param' ); ?>
                <h4 class="switcher-label">tax</h4>
                <hr>
            </div>
        </div>
        <div class="row">
            <div class="col">
                <?php PYS()->render_switcher_input( 'enable_edd_coupon_param' ); ?>
                <h4 class="switcher-label">coupon</h4>
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

<hr>
<div class="row justify-content-center">
    <div class="col-4">
        <button class="btn btn-block btn-sm btn-save">Save Settings</button>
    </div>
</div>
