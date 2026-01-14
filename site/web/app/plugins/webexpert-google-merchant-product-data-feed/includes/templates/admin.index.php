<?php
global $wpdb, $blog_id;
$attribute_taxonomies =  wc_get_attribute_taxonomies();
$attributes_listed=array_column($attribute_taxonomies,'attribute_name');
$attributes_listed=array_map(function($val) { return "pa_".$val;} , $attributes_listed);
$selected_cron=get_option('we_google_merchant_product_data_feed_cron_schedule',[]);
$attributes_listed=array_merge($attributes_listed,['product_type','product_visibility','product_cat','product_tag','product_shipping_class']);
$custom_taxonomies = get_object_taxonomies('product', 'objects');
foreach ($custom_taxonomies as $k=>$taxonomy) {
	if (in_array($taxonomy->name,$attributes_listed)|| in_array("pa_".$taxonomy->name,$attributes_listed)) {
		unset($custom_taxonomies[$k]);
	}
}
$selected_manufacturer=get_option('we_google_merchant_product_data_feed_brand',[]);
$selected_colour=get_option('we_google_merchant_product_data_feed_colour',[]);
$selected_size=get_option('we_google_merchant_product_data_feed_size',[]);
?>
<div class="wrap webexpert_google_merchant_product_data_feed">
	<?php if (get_transient('webexpert-google-merchant-product-data-feed-errors')) { ?>
        <div class="notice notice-error is-dismissible">
            <p>
				<?php
				echo get_transient('webexpert-google-merchant-product-data-feed-errors');
				delete_transient('webexpert-google-merchant-product-data-feed-errors');
				?>
            </p>
        </div>
	<?php } else if (get_transient('webexpert-google-merchant-product-data-feed-success')) { ?>
        <div class="notice notice-success is-dismissible">
            <p>
				<?php
				echo get_transient('webexpert-google-merchant-product-data-feed-success');
				delete_transient('webexpert-google-merchant-product-data-feed-success');
				?>
            </p>
        </div>
	<?php } ?>
    <h1><?php _e('WooCommerce Google Merchant Product Data Feed',$this->plugin_slug);?></h1>
    <form action="<?php echo esc_url( admin_url('admin-post.php') ); ?>" method="post">
        <p><?php _e('Last update',$this->plugin_slug);?>: <strong><?php echo $this->we_google_merchant_product_data_feed_lastrun() ?></strong></p>
		<?php if ($this->we_google_merchant_product_data_feed_lastrun()!="Never") { ?>
            <p><?php _e('Submit the following URL to Google Merchant.',$this->plugin_slug);?></p>
            <code><?php
				$filename="google_merchant.xml";
				if (defined('WP_ALLOW_MULTISITE') && isset($blog_id))
					$filename="google_merchant_$blog_id.xml";
				echo WE_GOOGLE_MERCHANT_DATA_FEED_PLUGIN_URL.'/'.$filename;?></code>
            <p>
                <?php if (file_exists(WE_GOOGLE_MERCHANT_DATA_FEED_PLUGIN_PATH.'/'.$filename)) : ?>
                <a target="_blank" href="<?php echo WE_GOOGLE_MERCHANT_DATA_FEED_PLUGIN_URL.'/'.$filename ;?>"><?php _e('View XML',$this->plugin_slug);?></a>
                <?php else : ?>
	                <em><?php _e('Error: XML file does not exist',$this->plugin_slug);?></em>
                <?php endif; ?>
            </p>
            <p><strong><?php _e('Statistics',$this->plugin_slug);?></strong><br>
				<?php _e('Percentage completed',$this->plugin_slug);?>: <?php echo get_option('webexpert_google_merchant_product_data_feed_stats_percentage','0');?>&percnt;<br>
				<?php _e('Simple products',$this->plugin_slug);?>: <?php echo get_option('webexpert_google_merchant_product_data_feed_stats_simple_products','-');?><br>
				<?php _e('Separate variations',$this->plugin_slug);?>: <?php echo get_option('webexpert_google_merchant_product_data_feed_stats_variations','-');?><br>
            </p>
		<?php } ?>
		<?php submit_button(__('Update feed',$this->plugin_slug)); ?>
        <input type="hidden" name="action" value="we_run_xml_google_merchant">
    </form>
    <form action="<?php echo esc_url( admin_url('admin-post.php') ); ?>" method="post">
        <h2 class="title"><?php _e('Settings',$this->plugin_slug);?></h2>
        <table class="form-table">
            <tr valign="top">
                <th scope="row"><label for="we_google_merchant_product_data_feed_cron_schedule"><?php _e('Cron schedule',$this->plugin_slug);?></label></th>
                <td>
                    <select id="we_google_merchant_product_data_feed_cron_schedule" name="we_google_merchant_product_data_feed_cron_schedule">
                        <option value=""><?php _e('Choose',$this->plugin_slug);?></option>
                        <option value="disabled" <?php echo (($selected_cron=="disabled") ? 'selected' : '' );?>><?php _e('Disabled',$this->plugin_slug);?></option>
                        <option value="hourly" <?php echo (($selected_cron=="hourly") ? 'selected' : '' );?>><?php _e('Hourly',$this->plugin_slug);?></option>
                        <option value="twicedaily" <?php echo (($selected_cron=="twicedaily") ? 'selected' : '' );?>><?php _e('Twice Daily',$this->plugin_slug);?></option>
                        <option value="daily" <?php echo (($selected_cron=="daily") ? 'selected' : '' );?>><?php _e('Daily',$this->plugin_slug);?></option>
                    </select>
                </td>
            </tr>
            <tr valign="top">
                <th scope="row"><label for="we_google_merchant_product_data_feed_brand"><?php _e('Brand',$this->plugin_slug);?></label></th>
                <td>
                    <select id="we_google_merchant_product_data_feed_brand" name="we_google_merchant_product_data_feed_brand[]" multiple>
                        <optgroup label="<?php _e('Choose attributes',$this->plugin_slug);?>">
					        <?php foreach ($attribute_taxonomies as $atr) { ?>
                                <option value="<?php echo $atr->attribute_name;?>" <?php echo ((is_string($selected_manufacturer) && $selected_manufacturer==$atr->attribute_name) || (is_array($selected_manufacturer) && in_array($atr->attribute_name,$selected_manufacturer)) ? 'selected' : '' );?> ><?php echo $atr->attribute_label; ?></option>
					        <?php } ?>
                        </optgroup>
                        <optgroup label="<?php _e('or taxonomies',$this->plugin_slug);?>">
					        <?php foreach ($custom_taxonomies as $atr) { ?>
                                <option value="<?php echo $atr->name;?>"  <?php echo ((is_string($selected_manufacturer) && $selected_manufacturer==$atr->name) || (is_array($selected_manufacturer) && in_array($atr->name,$selected_manufacturer)) ? 'selected' : '' );?> ><?php echo $atr->label; ?></option>
					        <?php } ?>
                        </optgroup>
                    </select>
                </td>
            </tr>
            <tr valign="top">
                <th scope="row"><label for="we_google_merchant_product_data_feed_colour"><?php _e('Color',$this->plugin_slug);?></label></th>
                <td>
                    <select id="we_google_merchant_product_data_feed_colour" name="we_google_merchant_product_data_feed_colour[]" multiple>
                        <optgroup label="<?php _e('Choose attributes',$this->plugin_slug);?>">
					        <?php foreach ($attribute_taxonomies as $atr) { ?>
                                <option value="<?php echo $atr->attribute_name;?>" <?php echo ((is_string($selected_colour) && $selected_colour==$atr->attribute_name) || (is_array($selected_colour) && in_array($atr->attribute_name,$selected_colour)) ? 'selected' : '' );?> ><?php echo $atr->attribute_label; ?></option>
					        <?php } ?>
                        </optgroup>
                        <optgroup label="<?php _e('or taxonomies',$this->plugin_slug);?>">
					        <?php foreach ($custom_taxonomies as $atr) { ?>
                                <option value="<?php echo $atr->name;?>" <?php echo ((is_string($selected_colour) && $selected_colour==$atr->name) || (is_array($selected_colour) && in_array($atr->name,$selected_colour)) ? 'selected' : '' );?> ><?php echo $atr->label; ?></option>
					        <?php } ?>
                        </optgroup>
                    </select>
                </td>
            </tr>
            <tr valign="top">
                <th scope="row"><label for="we_google_merchant_product_data_feed_size"><?php _e('Size',$this->plugin_slug);?></label></th>
                <td>
                    <select id="we_google_merchant_product_data_feed_size" name="we_google_merchant_product_data_feed_size[]" multiple>
                        <optgroup label="<?php _e('Choose attributes',$this->plugin_slug);?>">
					        <?php foreach ($attribute_taxonomies as $atr) { ?>
                                <option value="<?php echo $atr->attribute_name;?>" <?php echo ((is_string($selected_size) && $selected_size==$atr->attribute_name) || (is_array($selected_size) && in_array($atr->attribute_name,$selected_size)) ? 'selected' : '' );?> ><?php echo $atr->attribute_label; ?></option>
					        <?php } ?>
                        </optgroup>
                        <optgroup label="<?php _e('or taxonomies',$this->plugin_slug);?>">
					        <?php foreach ($custom_taxonomies as $atr) { ?>
                                <option value="<?php echo $atr->name;?>" <?php echo ((is_string($selected_size) && $selected_size==$atr->name) || (is_array($selected_size) && in_array($atr->name,$selected_size)) ? 'selected' : '' );?> ><?php echo $atr->label; ?></option>
					        <?php } ?>
                        </optgroup>
                    </select>
                </td>
            </tr>
            <tr valign="top">
                <th scope="row"><?php _e('Description',$this->plugin_slug);?></th>
                <td>
                    <fieldset><legend class="screen-reader-text"><span><?php _e('Description',$this->plugin_slug);?></span></legend>
                        <label> <input type="radio" name="we_google_merchant_product_data_feed_desc_field" value="short" <?php echo get_option('we_google_merchant_product_data_feed_desc_field','short')=='short' ? 'checked' : '';?>> <span><?php _e('Short description',$this->plugin_slug);?></span> </label><br>
                        <label> <input type="radio" name="we_google_merchant_product_data_feed_desc_field" value="long" <?php echo get_option('we_google_merchant_product_data_feed_desc_field','short')=='long' ? 'checked' : '';?>> <span><?php _e('Description',$this->plugin_slug);?></span> </label>
                    </fieldset>
                </td>
            </tr>
            <tr valign="top">
                <th scope="row"><label for="we_google_merchant_product_data_feed_hide_out_of_stock"><?php _e('Out of stock visibility',$this->plugin_slug);?></label></th>
                <td>
                    <fieldset>
                        <legend class="screen-reader-text"><span><?php _e('Hide out of stock items from XML feed',$this->plugin_slug);?></span></legend>
                        <label for="we_google_merchant_product_data_feed_hide_out_of_stock">
                            <input name="we_google_merchant_product_data_feed_hide_out_of_stock" id="we_google_merchant_product_data_feed_hide_out_of_stock" type="checkbox" class="" value="1" <?php echo checked( get_option('we_google_merchant_product_data_feed_hide_out_of_stock',1),1 );?>>
	                        <?php _e('Hide out of stock items from XML feed',$this->plugin_slug);?></label>
                    </fieldset>
                </td>
            </tr>
            <tr valign="top">
                <th scope="row">
                    <label for="we_google_merchant_product_data_feed_default_category"><?php _e('Default Google product category',$this->plugin_slug);?></label>
                </th>
                <td>
                    <?php
                    $file=file_get_contents(plugin_dir_path( __DIR__ )."/categories/taxonomy-with-ids.en-US.txt");
                    $lines = explode("\n", $file);;
                    $value= get_option('we_google_merchant_product_data_feed_default_category','');
                    ?>
                    <select id="we_google_merchant_product_data_feed_default_category" name="we_google_merchant_product_data_feed_default_category">
                        <option value=""><?php _e('(None)',$this->plugin_slug);?></option>
				        <?php foreach ($lines as $line) :
					        $split = explode(" - ",$line); ?>
                            <option value="<?php echo $split[0];?>" <?php selected( $value, $split[0] );?>><?php echo $split[1];?></option>
				        <?php endforeach; ?>
                    </select>
                    <p class="description"><?php _e('Google Merchant category mapping', $this->plugin_slug); ?></p>
                </td>
            </tr>
            <tr valign="top">
                <th scope="row"><label for="we_google_merchant_product_data_feed_categories_not_to_list"><a data-action="<?php echo (get_option('we_google_merchant_product_data_feed_categories_not_to_list_invert',0) ? 'include' : 'exclude'); ?>" data-lang-exclude="<?php _e('Exclude',$this->plugin_slug) ?>" data-lang-include="<?php _e('Include',$this->plugin_slug) ?>" class="we-google-merchant-smart-switch"><?php _e((get_option('we_google_merchant_product_data_feed_categories_not_to_list_invert', 0) ? 'Include' : 'Exclude'),$this->plugin_slug) ?></a> <?php _e(' products from these categories',$this->plugin_slug);?></label></th>
                <td>
					<?php $selected_terms=get_option('we_google_merchant_product_data_feed_categories_not_to_list',[]);
					if (empty($selected_terms))
						$selected_terms=[];
					?>
                    <select id="we_google_merchant_product_data_feed_categories_not_to_list" name="we_google_merchant_product_data_feed_categories_not_to_list[]" multiple="multiple">
						<?php
						$terms=get_terms( 'product_cat', array( 'get' => 'all' ));
						foreach ($terms as $term) {
							echo "<option value='$term->term_id' ".(in_array($term->term_id,$selected_terms) ? 'selected="selected"' : '').">$term->name</option>";
						}
						?>
                    </select>
                    <input type="checkbox" id="we_google_merchant_product_data_feed_categories_not_to_list_invert" class="we-google-merchant-smart-switch-checkbox" name="we_google_merchant_product_data_feed_categories_not_to_list_invert" value="1" <?php echo (get_option('we_google_merchant_product_data_feed_categories_not_to_list_invert',0) ? 'checked="checked"' : ''); ?>>
                </td>
            </tr>
            <tr valign="top">
                <th scope="row"><label for="we_google_merchant_product_data_feed_tags_not_to_list"><a data-action="<?php echo (get_option('we_google_merchant_product_data_feed_tags_not_to_list_invert',0) ? 'include' : 'exclude'); ?>" data-lang-exclude="<?php _e('Exclude',$this->plugin_slug) ?>" data-lang-include="<?php _e('Include',$this->plugin_slug) ?>" class="we-google-merchant-smart-switch"><?php _e((get_option('we_google_merchant_product_data_feed_tags_not_to_list_invert', 0) ? 'Include' : 'Exclude'),$this->plugin_slug) ?></a> <?php _e(' products with these tags',$this->plugin_slug);?></label></th>
                <td>
					<?php $selected_terms=get_option('we_google_merchant_product_data_feed_tags_not_to_list',[]);
					if (empty($selected_terms))
						$selected_terms=[];
					?>
                    <select id="we_google_merchant_product_data_feed_tags_not_to_list" name="we_google_merchant_product_data_feed_tags_not_to_list[]" multiple="multiple">
						<?php
						$terms=get_terms( 'product_tag', array( 'get' => 'all' ));
						foreach ($terms as $term) {
							echo "<option value='$term->term_id' ".(in_array($term->term_id,$selected_terms) ? 'selected="selected"' : '').">$term->name</option>";
						}
						?>
                    </select>
                    <input type="checkbox" id="we_google_merchant_product_data_feed_tags_not_to_list_invert" class="we-google-merchant-smart-switch-checkbox" name="we_google_merchant_product_data_feed_tags_not_to_list_invert" value="1" <?php echo (get_option('we_google_merchant_product_data_feed_tags_not_to_list_invert',0) ? 'checked="checked"' : ''); ?>>
                </td>
            </tr>
            <tr valign="top">
                <th scope="row"><label for="we_google_merchant_product_data_feed_attributes_not_to_list"><a data-action="<?php echo (get_option('we_google_merchant_product_data_feed_attributes_not_to_list_invert',0) ? 'include' : 'exclude'); ?>" data-lang-exclude="<?php _e('Exclude',$this->plugin_slug) ?>" data-lang-include="<?php _e('Include',$this->plugin_slug) ?>" class="we-google-merchant-smart-switch"><?php _e((get_option('we_google_merchant_product_data_feed_attributes_not_to_list_invert', 0) ? 'Include' : 'Exclude'),$this->plugin_slug) ?></a> <?php _e(' products with these attributes',$this->plugin_slug);?></label>
                </th>
                <td>
					<?php $selected_terms=get_option('we_google_merchant_product_data_feed_attributes_not_to_list',[]);
					if (empty($selected_terms))
						$selected_terms=[];
					?>
                    <select id="we_google_merchant_product_data_feed_attributes_not_to_list" name="we_google_merchant_product_data_feed_attributes_not_to_list[]" multiple="multiple">
						<?php
						$attribute_taxonomies = wc_get_attribute_taxonomies();;
						foreach ($attribute_taxonomies as $taxonomy) {
							$terms=get_terms( 'pa_'.$taxonomy->attribute_name, array( 'get' => 'all' ));
							foreach ($terms as $term) {
								echo "<option value='".$term->term_id."__".$term->taxonomy."' ".(in_array($term->term_id,$selected_terms) ? 'selected="selected"' : '').">$taxonomy->attribute_label: $term->name</option>";
							}
						}
						?>
                    </select>
                    <input type="checkbox" id="we_google_merchant_product_data_feed_attributes_not_to_list_invert" class="we-google-merchant-smart-switch-checkbox" name="we_google_merchant_product_data_feed_attributes_not_to_list_invert" value="1" <?php echo (get_option('we_google_merchant_product_data_feed_attributes_not_to_list_invert',0) ? 'checked="checked"' : ''); ?>>
                </td>
            </tr>
        </table>
        <h2 class="title"><?php _e('Overrides',$this->plugin_slug);?></h2>
        <table class="form-table">
            <tr valign="top">
                <th scope="row"><label for="we_google_merchant_product_data_feed_custom_id"><?php _e('Custom Product ID field',$this->plugin_slug);?></label></th>
                <td><input type="text" name="we_google_merchant_product_data_feed_custom_id" value="<?php echo esc_attr( get_option('we_google_merchant_product_data_feed_custom_id') ); ?>" /></td>
            </tr>
            <tr valign="top">
                <th scope="row"><label for="we_google_merchant_product_data_feed_custom_mpn"><?php _e('Custom Product MPN field',$this->plugin_slug);?></label></th>
                <td><input type="text" name="we_google_merchant_product_data_feed_custom_mpn" value="<?php echo esc_attr( get_option('we_google_merchant_product_data_feed_custom_mpn') ); ?>" /></td>
            </tr>
            <tr valign="top">
                <th scope="row"><label for="we_google_merchant_product_data_feed_custom_gtin"><?php _e('Custom Product GTIN field',$this->plugin_slug);?></label></th>
                <td><input type="text" name="we_google_merchant_product_data_feed_custom_gtin" value="<?php echo esc_attr( get_option('we_google_merchant_product_data_feed_custom_gtin') ); ?>" /></td>
            </tr>
            <tr valign="top">
                <th scope="row"><label for="we_google_merchant_product_data_feed_custom_weight"><?php _e('Custom Product weight field',$this->plugin_slug);?></label></th>
                <td><input type="text" name="we_google_merchant_product_data_feed_custom_weight" value="<?php echo esc_attr( get_option('we_google_merchant_product_data_feed_custom_weight') ); ?>" /></td>
            </tr>
            <tr valign="top">
                <th scope="row"><label for="we_google_merchant_product_data_feed_max_page_size"><?php _e('Max Page Size',$this->plugin_slug);?></label></th>
                <td><input type="number" name="we_google_merchant_product_data_feed_max_page_size" value="<?php echo esc_attr( get_option('we_google_merchant_product_data_feed_max_page_size',100) ); ?>" /></td>
            </tr>
        </table>
        <h2 class="title"><?php _e('License',$this->plugin_slug);?></h2>
        <table class="form-table">
            <tr valign="top">
                <th scope="row">Email</th>
                <td><input type="text" name="we_google_merchant_product_data_feed_email" value="<?php echo esc_attr( get_option('we_google_merchant_product_data_feed_email') ); ?>" /></td>
            </tr>

            <tr valign="top">
                <th scope="row">License Key</th>
                <td><input type="text" name="we_google_merchant_product_data_feed_license_key" value="<?php echo esc_attr( get_option('we_google_merchant_product_data_feed_license_key') ); ?>" /></td>
            </tr>
        </table>
		<?php submit_button(__('Save settings',$this->plugin_slug)); ?>
        <input type="hidden" name="" value="<?php echo $_SERVER['HTTP_HOST'];?>">
        <input type="hidden" name="action" value="we_save_settings_google_merchant">
    </form>
</div>