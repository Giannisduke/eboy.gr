<?php 
    if(array_key_exists('tbigr_hidden', $_POST) && $_POST['tbigr_hidden'] == 'Y') {
        if (array_key_exists('tbigr_hide', $_POST)){
            $tbigr_hide = $_POST['tbigr_hide'];
        }else{
            $tbigr_hide = '0';            
        }
        if (array_key_exists('tbigr_unicid', $_POST)){
            $tbigr_unicid = $_POST['tbigr_unicid'];
        }else{
            $tbigr_unicid = '';            
        }
        if (array_key_exists('credittbigr_store_id', $_POST)){
            $credittbigr_store_id = $_POST['credittbigr_store_id'];
        }else{
            $credittbigr_store_id = '';            
        }
        if (array_key_exists('credittbigr_username', $_POST)){
            $credittbigr_username = $_POST['credittbigr_username'];
        }else{
            $credittbigr_username = '';            
        }
        if (array_key_exists('credittbigr_password', $_POST)){
            $credittbigr_password = $_POST['credittbigr_password'];
        }else{
            $credittbigr_password = '';            
        }
        if (array_key_exists('credittbigr_iris_iban', $_POST)){
            $credittbigr_iris_iban = $_POST['credittbigr_iris_iban'];
        }else{
            $credittbigr_iris_iban = '';            
        }
        if (array_key_exists('credittbigr_iris_key', $_POST)){
            $credittbigr_iris_key = $_POST['credittbigr_iris_key'];
        }else{
            $credittbigr_iris_key = '';            
        }
        update_option('tbigr_hide', $tbigr_hide);
        update_option('tbigr_unicid', $tbigr_unicid);
        update_option('credittbigr_store_id', $credittbigr_store_id);
        update_option('credittbigr_username', $credittbigr_username);
        update_option('credittbigr_password', $credittbigr_password);
        update_option('credittbigr_iris_iban', $credittbigr_iris_iban);
        update_option('credittbigr_iris_key', $credittbigr_iris_key);
        ?>
        <div class="updated"><p><strong><?php _e('Settings saved successfully.', 'tbicreditgr'); ?></strong></p></div>
        <?php
    } else {
        $tbigr_hide = get_option('tbigr_hide') === '' ? '0' : get_option('tbigr_hide');
        $tbigr_unicid = get_option('tbigr_unicid');
        $credittbigr_store_id = get_option('credittbigr_store_id');
        $credittbigr_username = get_option('credittbigr_username');
        $credittbigr_password = get_option('credittbigr_password');
        $credittbigr_iris_iban = get_option('credittbigr_iris_iban');
        $credittbigr_iris_key = get_option('credittbigr_iris_key');
    }
?>
<div class="wrap">
    <?php    echo "<h2>" . __('tbi bank - all the module settings', 'tbicreditgr') . "</h2>"; ?>
    <form name="tbigr_form" method="post" enctype="multipart/form-data" action="<?php echo str_replace( '%7E', '~', $_SERVER['REQUEST_URI']); ?>">
        <input type="hidden" name="tbigr_hidden" value="Y">
        
        <?php echo "<h4>" . __("System settings", "tbicreditgr") . "</h4>"; ?>
        <table cellspacing="4" cellpadding="4" border="0" width="900px">
            <tr>
                <td width="300px" style="vertical-align:top;">
                    <?php _e('Hide/Show module info', 'tbicreditgr'); ?>
                </td>
                <td width="600px;" style="vertical-align:top;">
                    <select name="tbigr_hide" class="mt-currency-form-control" style="width:300px;">
                        <option value="1" <?php if ($tbigr_hide == 1){echo "selected";} ?>><?php _e('Hide', 'tbicreditgr'); ?></option>
                        <option value="0" <?php if ($tbigr_hide == 0){echo "selected";} ?>><?php _e('Show', 'tbicreditgr'); ?></option>
                    </select><br />
                    <span style="font-size:80%;"><?php _e('Hide/Show module info pane on pages.', 'tbicreditgr'); ?></span>
                </td>
            </tr>
          <tr>
            <td width="300px" style="vertical-align:top;border-bottom:1px solid #cbd5e1;">
              <?php _e('Unique shop identifier', 'tbicreditgr'); ?>
            </td>
            <td width="600px;" style="vertical-align:top;border-bottom:1px solid #cbd5e1;">
              <input type="text" name="tbigr_unicid" value="<?php echo $tbigr_unicid; ?>" size="36" style="width:300px;"><br />
              <span style="font-size:80%;"><?php _e('Unique shop identifier in the tbi bank GR system.', 'tbicreditgr'); ?></span>
            </td>
          </tr>
          <tr>
            <td width="300px" style="vertical-align:top;">
              <?php _e('Store ID for eCommerce tbi bank system', 'tbicreditgr'); ?>
            </td>
            <td width="600px;" style="vertical-align:top;">
              <input type="text" name="credittbigr_store_id" value="<?php echo $credittbigr_store_id; ?>" size="36" style="width:300px;"><br />
              <span style="font-size:80%;"><?php _e('Store ID for eCommerce tbi bank system. Required for system authentication.', 'tbicreditgr'); ?></span>
            </td>
          </tr>
          <tr>
            <td width="300px" style="vertical-align:top;">
              <?php _e('Username for eCommerce tbi bank system', 'tbicreditgr'); ?>
            </td>
            <td width="600px;" style="vertical-align:top;">
              <input type="text" name="credittbigr_username" value="<?php echo $credittbigr_username; ?>" size="36" style="width:300px;"><br />
              <span style="font-size:80%;"><?php _e('Username for eCommerce tbi bank system. Required for system authentication.', 'tbicreditgr'); ?></span>
            </td>
          </tr>
          <tr>
            <td width="300px" style="vertical-align:top;border-bottom:1px solid #cbd5e1;">
              <?php _e('Password for eCommerce tbi bank system', 'tbicreditgr'); ?>
            </td>
            <td width="600px;" style="vertical-align:top;border-bottom:1px solid #cbd5e1;">
              <input type="text" name="credittbigr_password" value="<?php echo $credittbigr_password; ?>" size="36" style="width:300px;"><br />
              <span style="font-size:80%;"><?php _e('Password for eCommerce tbi bank system. Required for system authentication.', 'tbicreditgr'); ?></span>
            </td>
          </tr>
          <tr>
            <td width="300px" style="vertical-align:top;">
              <?php _e('IRIS IBAN', 'tbicreditgr'); ?>
            </td>
            <td width="600px;" style="vertical-align:top;">
              <input type="text" name="credittbigr_iris_iban" value="<?php echo $credittbigr_iris_iban; ?>" size="36" style="width:300px;"><br />
              <span style="font-size:80%;"><?php _e('IRIS IBAN.', 'tbicreditgr'); ?></span>
            </td>
          </tr>
          <tr>
            <td width="300px" style="vertical-align:top;">
              <?php _e('IRIS Key', 'tbicreditgr'); ?>
            </td>
            <td width="600px;" style="vertical-align:top;">
              <input type="text" name="credittbigr_iris_key" value="<?php echo $credittbigr_iris_key; ?>" size="36" style="width:300px;"><br />
              <span style="font-size:80%;"><?php _e('IRIS Key.', 'tbicreditgr'); ?></span>
            </td>
          </tr>
        </table>
        <hr />
        <p class="submit">
        <input type="submit" name="Submit" value="<?php _e('Save changes', 'tbicreditgr'); ?>" />
        </p>
    </form>
</div>