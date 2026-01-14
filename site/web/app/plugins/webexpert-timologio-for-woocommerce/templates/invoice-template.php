<head>
  <style>

      body{
        font-size:12px;
      }

      .bold {
        font-weight:bold;
      }

      table {
        margin-top:40px;
        width:100%;
        border-collapse: collapse;
      }

      table td {
        border:1px solid #ccc;
        border-right:none;
        border-left:none;
        padding:8px;
      }

      table tr{
        border:1px solid #ccc;
      }

      table thead tr th {
        background-color:#ccc;
      }

      .center-row th , .center-row td {
        text-align:center;
      }

      .left-row th , .left-row td {
        text-align:left !important;
      }

  </style>
</head>

<div style="width:100%">

<div style="width:40%;float:left">
    %logo%
</div>

<div style="width:60%;float:left;text-align:right">

  <div class='bold'>
    <?php echo get_option("webexpert_timologio_for_woocommerce_invoice_shop_name"); ?>
  </div>
  <div class='bold'>
    <?php echo get_option('woocommerce_store_address'); ?>, <?php echo get_option('woocommerce_store_city'); ?> <?php echo get_option('woocommerce_store_postcode'); ?>
  </div>
  <?php if(strlen(strval(get_option('webexpert_timologio_for_woocommerce_invoice_vat_number'))) > 0 ) { ?>
    <div class='bold'>
      <?php _e('VAT number' , 'webexpert-timologio-for-woocommerce') ?>: <?php echo get_option('webexpert_timologio_for_woocommerce_invoice_vat_number'); ?>
    </div>
  <?php }  elseif( strlen(strval(get_option('woocommerce_store_vat_id'))) > 0 ) {  ?>
  <div class='bold'>
    <?php _e('VAT number' , 'webexpert-timologio-for-woocommerce') ?>: <?php echo get_option('woocommerce_store_vat_id'); ?>
  </div>
  <?php } ?>
  <?php if(strlen(strval(get_option('webexpert_timologio_for_woocommerce_invoice_tax_office'))) > 0 ) { ?>
    <div class='bold'>
      <?php _e('Tax Office' , 'webexpert-timologio-for-woocommerce') ?>: <?php echo get_option('webexpert_timologio_for_woocommerce_invoice_tax_office'); ?>
    </div>
  <?php } ?>
  <?php if(strlen(strval(get_option('webexpert_timologio_for_woocommerce_invoice_email_address'))) > 0) { ?>
    <div class='bold'>
      <?php _e('Email' , 'webexpert-timologio-for-woocommerce') ?>: <?php echo get_option('webexpert_timologio_for_woocommerce_invoice_email_address') ?>
    </div>
  <?php } ?>
  <?php if(strlen(strval(get_option('webexpert_timologio_for_woocommerce_invoice_phone_number'))) > 0) { ?>
    <div class='bold'>
      <?php _e('Phone Number' , 'webexpert-timologio-for-woocommerce') ?>: <?php echo get_option('webexpert_timologio_for_woocommerce_invoice_phone_number') ?>
    </div>
  <?php } ?>

</div>



<div style='width:100%'>

<table >
    <thead>
         <tr class='center-row'>
           <th><?php _e('INVOICE TYPE' , 'webexpert-timologio-for-woocommerce') ?></th>
           <th><?php _e('CODE', 'webexpert-timologio-for-woocommerce') ?></th>
           <th><?php _e('DATE', 'webexpert-timologio-for-woocommerce') ?></th>
           <th><?php _e('TIME', 'webexpert-timologio-for-woocommerce') ?></th>
         </tr>
    </thead>
    <tbody>
          <tr class='center-row'>
            <td>%invoice_type%</td>
            <td>%invoice_id%</td>
            <td>%order_date%</td>
            <td>%order_time%</td>
          </tr>

    </tbody>
</table>

</div>

<div style='width:100%'>

  <div style='width:50%;float:left'>
    <table >
        <thead>
             <tr class='center-row'>
               <th style="width:40%"><?php _e('CUSTOMER INFO' , 'webexpert-timologio-for-woocommerce') ?>:</th>
               <th style="width:80%"></th>
             </tr>
        </thead>
        <tbody>
              <tr class='center-row'>
                <td style="width:20%" class="bold"><?php _e('FULL NAME' , 'webexpert-timologio-for-woocommerce') ?>:</td>
                <td style='width:80%'>%billing_first_name% %billing_last_name%</td>
              </tr>

              <tr class='center-row'>
                <td style="width:20%" class="bold"><?php _e('EMAIL' , 'webexpert-timologio-for-woocommerce') ?>:</td>
                <td style='width:80%'>%billing_email%</td>
              </tr>

              <tr class='center-row'>
                <td style="width:20%" class="bold"><?php _e('PHONE' , 'webexpert-timologio-for-woocommerce') ?>:</td>
                <td style='width:80%'>%billing_phone%</td>
              </tr>

              <tr class='center-row'>
                <td style="width:20%" class="bold"><?php _e('ORDER DATE' , 'webexpert-timologio-for-woocommerce') ?>:</td>
                <td style='width:80%'>%order_date%</td>
              </tr>

              <?php if(!empty($data['company_name'])) { ?>
                <tr class='center-row'>
                  <td style="width:20%" class="bold"><?php _e('COMPANY NAME' , 'webexpert-timologio-for-woocommerce') ?>:</td>
                  <td style='width:80%'><?php echo $data['company_name'] ?></td>
                </tr>
              <?php } ?>

              <?php if(!empty($data['vat_number'])) { ?>
                <tr class='center-row'>
                  <td style="width:20%" class="bold"><?php _e('VAT NUMBER' , 'webexpert-timologio-for-woocommerce') ?>:</td>
                  <td style='width:80%'><?php echo $data['vat_number'] ?></td>
                </tr>
              <?php } ?>

              <?php if(!empty($data['activity'])) { ?>
                <tr class='center-row'>
                  <td style="width:20%" class="bold"><?php _e('ACTIVITY' , 'webexpert-timologio-for-woocommerce') ?>:</td>
                  <td style='width:80%'><?php echo $data['activity'] ?></td>
                </tr>
              <?php } ?>

              <?php if(!empty($data['tax_office'])) { ?>
                <tr class='center-row'>
                  <td style="width:20%" class="bold"><?php _e('TAX OFFICE' , 'webexpert-timologio-for-woocommerce') ?>:</td>
                  <td style='width:80%'><?php echo $data['tax_office'] ?></td>
                </tr>
              <?php } ?>

        </tbody>
    </table>
  </div>

  <div style='padding-left:5%;width:45%;float:left'>
    <table >
        <thead>
             <tr class='center-row'>
               <th style="width:40%"><?php _e('ADDRESS' , 'webexpert-timologio-for-woocommerce') ?></th>
               <th style="width:80%"></th>
             </tr>
        </thead>
        <tbody>
              <tr class='center-row'>
                <td style="width:20%" class="bold"><?php _e('STREET' , 'webexpert-timologio-for-woocommerce') ?>:</td>
                <td style='width:80%'>%billing_address%</td>
              </tr>
              <tr class='center-row'>
                <td style="width:20%" class="bold"><?php _e('CITY' , 'webexpert-timologio-for-woocommerce') ?>:</td>
                <td style='width:80%'>%billing_city%</td>
              </tr>
              <tr class='center-row'>
                <td style="width:20%" class="bold"><?php _e('POST CODE' , 'webexpert-timologio-for-woocommerce') ?>:</td>
                <td style='width:80%'>%billing_postcode%</td>
              </tr>
        </tbody>
    </table>
  </div>

</div>


<div style='width:100%'>
  <table >
      <thead>
           <tr class='left-row'>
             <th width="10%"><?php _e('Νο.' , 'webexpert-timologio-for-woocommerce') ?></th>
             <th width="30%"><?php _e('PRODUCT' , 'webexpert-timologio-for-woocommerce') ?></th>
             <th width="15%" style='text-align:center'><?php _e('NET PRICE' , 'webexpert-timologio-for-woocommerce') ?></th>
             <th width="15%" style='text-align:center'><?php _e('QTY' , 'webexpert-timologio-for-woocommerce') ?></th>
             <th width="15%" style='text-align:center'><?php _e('DISCOUNT' , 'webexpert-timologio-for-woocommerce') ?></th>
             <th width="15%" style='text-align:center'><?php _e('TOTAL' , 'webexpert-timologio-for-woocommerce') ?></th>
             <th width="15%" style='text-align:center'><?php _e('VAT' , 'webexpert-timologio-for-woocommerce') ?></th>
           </tr>
      </thead>
      <tbody>
          %order_table%
      </tbody>
  </table>
</div>


<div style='width:100%'>
  <table>
      <thead>
           <tr class='center-row'>
             <th><?php _e('PAYMENT METHOD' , 'webexpert-timologio-for-woocommerce') ?></th>
             <th><?php _e('TOTAL NET PRICE', 'webexpert-timologio-for-woocommerce') ?></th>
             <th><?php _e('TOTAL VAT PRICE', 'webexpert-timologio-for-woocommerce') ?></th>
             <th><?php _e('TOTAL', 'webexpert-timologio-for-woocommerce') ?></th>
           </tr>
      </thead>
      <tbody>
            <tr class='center-row'>
              <td>%payment_method%</td>
              <td>%total_net_price%</td>
              <td>%total_tax_price%</td>
              <td>%total_price%</td>
            </tr>

      </tbody>
  </table>

  <div style='margin-top:30px'>
      %qr_string%
  </div>
</div>

</div>
