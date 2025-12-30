<?php
/** add admin menu */
function tbigr_admin_actions() {
    add_options_page(__('tbi bank - Module settings', 'tbicreditgr'), __('tbi bank settings', 'tbicreditgr'), 'manage_options', "tbigr-options", "tbigr_admin_options");
}