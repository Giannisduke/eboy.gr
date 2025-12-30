<?php

namespace PixelYourSite;

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

/**
 * @var String $activeFilter
 * @var String $filterId
 * @var String $visitModel
 * @var String $time
 * @var String $timeStart
 * @var String $timeEnd
 * @var String $title
 * @var String $perPage
 * @var String $type // edd or woo
 */
?>

<div class="row stat_data ">
    <div class="col">
        <div class="loading text-center">
            <span class="spinner .is-active"></span>Loading ...
        </div>
        <div class="single_data" data-filter="<?=$activeFilter?>" data-model="<?=$visitModel?>" data-filter_id="<?=$filterId?>" data-type="<?=$type?>">
            <div class="col text-center infoBlock">
                <h3 class="text-center" style="margin-top:40px">Install the WooCommerce Cost of Goods plugin and get access to the Cost & Profit reports.</h3>
                <a href="https://www.pixelyoursite.com/plugins/woocommerce-cost-of-goods?utm_source=plugin&utm_medium=plugin&utm_campaign=cost-profit-reports&utm_content=cost-profit-reports&utm_term=cost-profit-reports" target="_blank" class="btn btn-save" style="margin-top:30px">Get the plugin</a>
            </div>
            <ul class="total">

            </ul>

            <select class="pys_stat_time mt-2">
                <option value="yesterday" <?=selected($time,"yesterday")?>>Yesterday</option>
                <option value="today" <?=selected($time,"today")?>>Today</option>
                <option value="7" <?=selected($time,"7")?>>Last 7 days</option>
                <option value="30" <?=selected($time,"30")?>>Last 30 days</option>
                <option value="current_month" <?=selected($time,"current_month")?>>Current month</option>
                <option value="last_month" <?=selected($time,"last_month")?>>Last month</option>
                <option value="year_to_date" <?=selected($time,"year_to_date")?>>Year to date</option>
                <option value="last_year" <?=selected($time,"last_year")?>>Last year</option>
                <option value="custom" <?=selected($time,"custom")?>>Custom dates</option>
            </select>

            <div class="pys_stat_time_custom mt-2">
                <?php
                $calendarDateStart = $timeStart ? date_format(date_create($timeStart),'m/d/Y') : $timeStart;
                $calendarDateEnd = $timeEnd ? date_format(date_create($timeEnd),'m/d/Y') : $timeEnd;
                ?>
                <input type="text" class="datepicker datepicker_start mr-2" placeholder="From" value="<?=$calendarDateStart?>"/>
                <input type="text" class="datepicker datepicker_end mr-2" placeholder="To" value="<?=$calendarDateEnd?>"/>
                <button class="btn btn-primary load">Load</button>
            </div>

            <div class="d-flex mt-3 mb-3">
                <span class="single_filter mr-3 "><?=$title?></span>
                <button class="btn single_back">< Back</button>
            </div>
            <canvas id="pys_stat_single_graphics" width="400" height="100"></canvas>
            <div class="d-flex justify-content-between mt-3">
                <div>
                    <select class="per_page_selector">
                        <option value="10" <?=selected($perPage,10)?>>10</option>
                        <option value="25" <?=selected($perPage,25)?>>25</option>
                        <option value="50" <?=selected($perPage,50)?>>50</option>
                        <option value="75" <?=selected($perPage,75)?>>75</option>
                        <option value="100" <?=selected($perPage,100)?>>100</option>
                    </select>
                    <i class="fa fa-refresh ml-2 reload_table"> </i>
                </div>
                <form class="report_form"  method="post" enctype="multipart/form-data">
                    <input type="hidden" name="cog"/>
                    <input type="hidden" name="start_date"/>
                    <input type="hidden" name="end_date"/>
                    <input type="hidden" name="filter_id"/>
                    <input type="hidden" name="type"/>
                    <input type="hidden" name="model"/>
                    <input type="hidden" name="single_table_type"/>
                    <input type="hidden" name="filter_type"/>
                    <input type="hidden" name="export_csw" value="<?=$type?>_single_report"/>
                    <button class="btn btn-primary report" >Download</button>
                </form>
            </div>
            <ul class="total mt-3">

            </ul>
            <div class="btn-group order_buttons mt-3" role="group" aria-label="Basic example">
                <button type="button" class="btn btn-primary" data-slug="dates">Date</button>
                <button type="button" class="btn btn-secondary" data-slug="orders">Order ID</button>
                <button type="button" class="btn btn-secondary" data-slug="products">Products</button>
            </div>

            <table class="pys_stat_single_info mt-3 table">
            </table>
            <ul class="pys_stat_single_info_pagination pagination"></ul>
        </div>
    </div>
</div>
