<?php
class Webexpert_Timologio_For_Woocommerce_Deactivator {
	public static function deactivate() {
		$post = get_page_by_path('webexpert-timologio-for-woocommerce-box-generate');
			if($post){
				wp_delete_post($post->ID , true);
			}
	}
}
