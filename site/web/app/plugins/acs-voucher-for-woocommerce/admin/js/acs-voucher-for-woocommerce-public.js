(function( $ ) {
	'use strict';
	$(document).ready(function(){
		$(document).on('blur paste','#billing_address_1,#billing_postcode,#billing_city',function(){
			if ($("#billing_address_1").is('empty') || $("#billing_postcode").is('empty') || $("#billing_city").is('empty'))
				return;

			jQuery.ajax({
				type : "post",
				url : ajax_object.ajax_url,
				data : {
					action: "webexpert_acs_validate_address",
					billing_address_1 : $("#billing_address_1").val(),
					billing_postcode : $("#billing_postcode").val(),
					billing_city : $("#billing_city").val(),
				},
				success: function(response) {
					if(!response) {
						return;
					}

					if (response.Resolved_Area) {
						$("#billing_city").val(response.Resolved_Area);
					}

					if (response.Resolved_Zip) {
						$("#billing_postcode").val(response.Resolved_Zip);
					}
				}
			});
		});
	});

})( jQuery );
