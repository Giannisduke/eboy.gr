<?php

namespace PixelYourSite;

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

$type = "edd";

?>

<div class="wrap" id="pys">
    <h1><?php _e( 'PixelYourSite Pro', 'pys' ); ?></h1>
    <div class="pys_stat">
        <div class="row">
            <div class="col">
                <h2 class="section-title">EDD Reports (beta)</h2>
            </div>
        </div>

        <?php include __DIR__."/../../modules/statistic/views/html-report.php"?>

    </div>
</div>