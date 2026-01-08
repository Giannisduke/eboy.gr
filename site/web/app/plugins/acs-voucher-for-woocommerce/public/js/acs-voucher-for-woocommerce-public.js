(function( $ ) {
	'use strict';
	$(document).ready(function(){
		var $track_results=$('.track-results-acs');
	    $(document).on('submit', '#webexpert-acs-track-order-form', function (e) {
	        e.preventDefault();
	        var $btn = $(this).find(`[type="submit"]`);
	        $btn.prop('disabled', true).find('i').remove();
	        var $val = $('input[name="order_id"]').val();
			let orderEmailOrPhone = $(`input[name="email_or_phone"]`).val();
	        if( $val === '' ){
	            $track_results.hide().html();
				alert($btn.data('failed'));
	            $btn.prop('disabled', false).find('i').remove();
	        }else{
	            var data = {action: 'webexpert_get_acs_order_html', code:$val, email_or_phone:orderEmailOrPhone };
	            jQuery.post(ajax_object.ajax_url, data, function (response) {
	                if(response.error === 0) {
	                    $track_results.hide().html(response.html);
	                    $track_results.fadeIn()
	                }else{
	                    $track_results.hide().html();
						alert($btn.data('failed'));
	                    $btn.prop('disabled', false).find('i').remove();
	                }
	            });
	        }
	    });
	});
})( jQuery );