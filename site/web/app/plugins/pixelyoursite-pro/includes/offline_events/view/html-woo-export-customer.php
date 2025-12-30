<?php
namespace PixelYourSite;

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}
?>

<div class="card customer_export_card">
    <div class="card-header">
        Google Customer Export file <?php cardCollapseBtn(); ?>
    </div>
    <div class="card-body">
        <div class="row mb-4">
            <div class="col form-inline">

                <label style="margin-bottom: 10px;margin-right: 5px">Order status:</label>
                <?php
                $allStatus = wc_get_order_statuses();
                foreach ($allStatus as $key => $label) :
                    $checked = "";
                    if($key == "wc-completed") {
                        $checked = "checked";
                    } ?>
                    <label style="margin-bottom: 5px;margin-right: 5px">
                        <input style="margin-right: 5px" type="checkbox" <?=$checked?> class="order_status" value="<?=$key?>" name="customer_order_status[]">
                        <?=$label?></label>

                <?php  endforeach; ?>
            </div>
        </div>

        <div class="row mt-3 mb-3">
            <div class="col form-inline">
                <label>Select</label>
                <?php PYS()->render_text_input("woo_last_export_customer_date", '', false, true); ?>

                <select class="form-control-sm" id="woo_export_customer">
                    <option value="export_last_time" selected="selected">Export from last time</option>
                    <option value="export_by_date">Export by dates</option>
                    <option value="export_all">Export from all orders</option>
                </select>
                <div id="pys_customer_export_datepickers" class="form-inline" style="display: none">
                    <label for="from">From</label>
                    <input type="text" class="pys_datepickers_from" name="from">
                    <label for="to">to</label>
                    <input type="text" class="pys_datepickers_to" name="to">
                </div>
            </div>
        </div>
        <div class="row mt-3 mb-3">
            <div class="col">
                <label class="custom-control custom-checkbox">
                    <input type="checkbox" id="use_crypto" name="use_crypto" value="1" checked class="custom-control-input">
                    <span class="custom-control-indicator"></span>
                    <span class="custom-control-description">Use SHA-256 data encoding</span>
                </label>

            </div>
        </div>
        <div class="row mt-4">
            <div class="col-3">
                <input type="hidden" id="customer_generate_export_wpnonce" value="<?=wp_create_nonce("customer_generate_export_wpnonce")?>"/>
                <a href="#" target="_blank" class="btn btn-sm btn-block btn-primary" id="customer_generate_all_data"><?php _e('Export all the data', 'pys'); ?></a>
            </div>
            <div id="customer_generate_export_loading" class="col-3" style="display:none; padding-top:5px">
                <img src="<?php echo admin_url('/images/wpspin_light.gif'); ?>" class="waiting" />
                <span class="current ml-4">0</span>/<span class="max">0</span>
            </div>
        </div>
        <div class="row mt-4">
            <div class="col">

                <ul class="export_links">
                    <li class="export_links_title"><b>Exports:</b></li>
                    <?php
                    $offlineEvents = new OfflineEvents();
                    $files = glob(trailingslashit(PYS_PATH) . 'tmp_customers/*csv');
                    usort( // sort by filemtime
                        $files,
                        function($file1, $file2) {
                            return filemtime($file1) > filemtime($file2) ? -1 : 1;
                        }
                    );
                    $sortedFiles = [];
                    for ($i = 0; $i < count($files); $i++) {
                        if ($i < 3) {
                            $sortedFiles[] = $files[$i];
                        } else {
                            unlink($files[$i]);
                        }
                    }

                    foreach ($sortedFiles as $file) {
                        $fileName = basename($file, ".csv");
                        $parts = explode("-", $fileName);

                        $created = str_replace("_", "/", $parts[0]);
                        $type = $parts[1];
                        $name = "<li data-name='$fileName'>Created on $created<b> Export ";
                        $fileUrl = $offlineEvents->getCustomerFileUrl($fileName);
                        if ($type == "export_all") {
                            $name .= "All orders";
                        } else {
                            $start = str_replace("_", "/", $parts[2]);
                            $end = str_replace("_", "/", $parts[3]);
                            $name .= "from $start to $end";
                        }
                        $name .= "</b> - <a href='" . $fileUrl . "' download>download CSV</a></li>";
                        echo $name;
                    }
                    ?>
                </ul>
            </div>
        </div>

    </div>
</div>