<?php

namespace PixelYourSite;

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

/**
 * @var String $visitModel
 * @var String $time
 * @var String $timeStart
 * @var String $timeEnd
 * @var String $perPage
 * @var String $type // edd or woo
 */
?>

<div class="row stat_data ">
    <div class="col">
        <div class="loading text-center">
            <span class="spinner .is-active"></span>Loading ...
        </div>
        <div class="global_data" data-type="<?=$type?>">
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

            <div class="pys_stat_time_custom mt-2" style="display: <?=$timeStart?"flex":"none"?>">
                <?php
                    $calendarDateStart = $timeStart ? date_format(date_create($timeStart),'m/d/Y') : $timeStart;
                    $calendarDateEnd = $timeEnd ? date_format(date_create($timeEnd),'m/d/Y') : $timeEnd;
                ?>
                <input type="text" class="datepicker datepicker_start mr-2" placeholder="From" value="<?=$calendarDateStart?>"/>
                <input type="text" class="datepicker datepicker_end mr-2" placeholder="To" value="<?=$calendarDateEnd?>"/>
                <button class="btn btn-primary load">Load</button>
            </div>

            <select class="pys_visit_model mt-2" id="select_visit_model">
                <option value="first_visit" <?=selected("first_visit",$visitModel)?>>First Visit</option>
                <option value="last_visit" <?=selected("last_visit",$visitModel)?>>Last Visit</option>
            </select>
            <div class="infoBlock"></div>
            <canvas id="pys_stat_graphics" class="mt-3" width="400" height="100"></canvas>
            <ul class="total mt-3">

            </ul>
            <div class="d-flex justify-content-between mt-3">
                <div>
                    <select class="per_page_selector">
                        <option value="5" <?=selected($perPage,5)?>>5</option>
                        <option value="25" <?=selected($perPage,25)?>>25</option>
                        <option value="50" <?=selected($perPage,50)?>>50</option>
                        <option value="75" <?=selected($perPage,75)?>>75</option>
                        <option value="100" <?=selected($perPage,100)?>>100</option>
                    </select>
                    <i class="fa fa-refresh ml-2 reload_table"> </i>
                </div>

                <form class="report_form" method="post" enctype="multipart/form-data">
                    <input type="hidden" name="cog"/>
                    <input type="hidden" name="label"/>
                    <input type="hidden" name="model"/>
                    <input type="hidden" name="start_date"/>
                    <input type="hidden" name="end_date"/>
                    <input type="hidden" name="type"/>
                    <input type="hidden" name="filter_type"/>
                    <input type="hidden" name="export_type"/>
                    <input type="hidden" name="export_csw" value="<?=$type?>_report"/>

                    <div class="export-current-data-csv export-data-csv">
                        <button class="btn btn-primary report">Export the current data set</button>
                    </div>
                    <div class="export-all-data-csv export-data-csv">
                        <div class="change-data-export">
                            <h4 class="label">Select additional fields to be exported</h4>
                            <label class="custom-control custom-checkbox">
                                <input type="checkbox" value="fn" class="custom-control-input" name="change-data[]">
                                <span class="custom-control-indicator"></span>
                                <span class="custom-control-description">fn</span>
                            </label>
                            <label class="custom-control custom-checkbox">
                                <input type="checkbox" value="ln" class="custom-control-input" name="change-data[]">
                                <span class="custom-control-indicator"></span>
                                <span class="custom-control-description">ln</span>
                            </label>
                            <label class="custom-control custom-checkbox">
                                <input type="checkbox" value="email" class="custom-control-input" name="change-data[]">
                                <span class="custom-control-indicator"></span>
                                <span class="custom-control-description">email</span>
                            </label>
                            <label class="custom-control custom-checkbox">
                                <input type="checkbox" value="phone" class="custom-control-input" name="change-data[]">
                                <span class="custom-control-indicator"></span>
                                <span class="custom-control-description">phone</span>
                            </label>
                        </div>
                        <div class="change_visit_model">
                            <select class="pys_visit_model mt-2" name="gpt_visit_model">
                                <option value="first_visit" selected>First Visit</option>
                                <option value="last_visit">Last Visit</option>
                                <option value="all">All</option>
                            </select>
                        </div>
                        <button class="btn btn-primary all_report">Export ChatGPT file</button>
                        <p>Learn how to use it: <a href="https://www.pixelyoursite.com/pixelyoursite-and-chatgpt?utm_source=pys_pro&utm_medium=pro_plugin_chatgpt_help_link" target="_blank">watch video here</a></p>
                    </div>

                </form>
            </div>



            <table class="pys_stat_info mt-3 table " data-filter_type="">
                <thead>
                <tr>
                    <th class="title"></th>
                    <th class="num_sale sortable"   data-order="order"  data-sort="desc">
                        Orders:<i class="fa fa-sort"></i>
                    </th>
                    <th class="sortable"            data-order="gross_sale" data-sort="desc">
                        Gross sales:<i class="fa fa-sort"></i>
                    </th>
                    <th class="active sortable"     data-order="net_sale"  data-sort="desc">
                        Net sales:<i class="fa fa-sort-desc"></i>
                    </th>
                    <th class="sortable"            data-order="total_sale"  data-sort="desc">
                        Total sales:<i class="fa fa-sort"></i>
                    </th>
                </tr>
                </thead>
                <tbody>

                </tbody>
            </table>
            <ul class="pagination">

            </ul>
        </div>
    </div>
</div>
