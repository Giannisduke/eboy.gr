<?php
namespace PixelYourSite;

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}
/**
 * @var String $type // edd or woo
 */
$statistic = $type == "woo" ? PysStatistic()->wooStatistic : PysStatistic()->eddStatistic;
$allStatus = $type == "woo" ? wc_get_order_statuses() : edd_get_payment_statuses();
?>

<div class="row mt-2">
    <div class="col">
        <h2 class="section-title">Settings</h2>
    </div>
</div>

<div class="row">
    <div class="col">
        <h4 class="label">Active orders status:</h4>

        <select class="form-control pys-pysselect2"
                data-placeholder="Select Order status"
                id="<?=$type?>_stat_order_statuses"  style="width: 100%;"
                multiple>

            <?php
            $selected = $statistic::getSelectedOrderStatus();
            foreach ( $allStatus as $option_key => $option_value ) : ?>
                <option value="<?php echo esc_attr( $option_key ); ?>"
                    <?php selected( in_array( $option_key, $selected ) ); ?>
                >
                    <?php echo esc_attr( $option_value ); ?>
                </option>
            <?php endforeach; ?>

        </select>

    </div>
</div>
<div class="row justify-content-center mt-3">
    <div class="col-4">
        <button class="btn btn-block btn-sm btn-save btn-save-<?=$type?>-stat">Save Settings</button>
    </div>
</div>
