<?php
namespace PixelYourSite;

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}
?>
<div class="deleting_form">
    <div class="row mt-2">
        <div class="col">
            <h2 class="section-title">Clearing statistics</h2>
        </div>
    </div>
    <form method="post" id="dataDeletionForm">
        <div class="row">
            <div class="col">
                <h4 class="label">Select period for data deletion:</h4>

                <select name="delete_time" class="pys_stat_delete_time mt-2">
                    <option value="all" <?=selected($this->delete_time,"all")?>>All</option>
                    <option value="yesterday" <?=selected($this->delete_time,"yesterday")?>>Yesterday</option>
                    <option value="today" <?=selected($this->delete_time,"today")?>>Today</option>
                    <option value="7" <?=selected($this->delete_time,"7")?>>Last 7 days</option>
                    <option value="30" <?=selected($this->delete_time,"30")?>>Last 30 days</option>
                    <option value="current_month" <?=selected($this->delete_time,"current_month")?>>Current month</option>
                    <option value="last_month" <?=selected($this->delete_time,"last_month")?>>Last month</option>
                    <option value="year_to_date" <?=selected($this->delete_time,"year_to_date")?>>Year to date</option>
                    <option value="last_year" <?=selected($this->delete_time,"last_year")?>>Last year</option>
                    <option value="custom" <?=selected($this->delete_time,"custom")?>>Custom dates</option>
                </select>
                <div class="pys_stat_delete_time_custom mt-2" style="display: none;" >
                    <input type="text" name="delete_time_start" class="datepicker datepicker_start mr-2" placeholder="From">
                    <input type="text" name="delete_time_end" class="datepicker datepicker_end mr-2" placeholder="To">
                </div>
            </div>
        </div>

        <input type="hidden" name="type" value="<?= $type;?>">

        <div class="row justify-content-center mt-3">
            <div class="col-4">
                <button type="submit" value="delete_statistic" class="btn btn-block btn-sm btn-danger btn-delete-stat"><?= __('Delete the data', 'pixelyoursite');?></button>
            </div>
        </div>
    </form>
</div>


