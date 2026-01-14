(function( $ ) {
	'use strict';
    document.addEventListener('DOMContentLoaded', function() {
        if (jQuery('#_billing_invoice').length)
            jQuery('#_billing_invoice').select2();


        var invoice = $('#_billing_invoice');

        function checkTimologioFieldsVisibility() {
            if (invoice.val() === 'y') {
                $('.invoice-only').slideDown('fast');
                $('.invoice-only .optional').hide();
            } else {
                $('.invoice-only').slideUp('fast');
                $('.invoice-only .optional').show();
            }
        }

        $(document).on('change',invoice,checkTimologioFieldsVisibility);

		function getParameterByName(name, url = window.location.href) {
			name = name.replace(/[\[\]]/g, '\\$&');
			var regex = new RegExp('[?&]' + name + '(=([^&#]*)|&|#|$)'),
				results = regex.exec(url);
			if (!results) return null;
			if (!results[2]) return '';
			return decodeURIComponent(results[2].replace(/\+/g, ' '));
		}

		$(document).on('blur paste','#_billing_vat_id',function(){
			var $this=$(this);
			jQuery( 'body' ).trigger( 'update_checkout' );
			jQuery.ajax({
				type : "post",
				url : webexpert_ajax_object.ajax_url,
				data : {
					action: "webexpert_timologio_for_wc_aade_fill",
					vat_id : $this.val()
				},
				success: function(response) {
					var tax_office_select_field = $('#_billing_tax_office');
					var billing_company=jQuery('#_billing_company');
					var billing_address_1=jQuery('#_billing_address_1');

					if (response.commer_title) {
						billing_company.val(response.commer_title);
					}

					if (response.onomasia) {
						billing_company.val(response.onomasia);
					}

					if (response.doy_descr && tax_office_select_field.length>0) {
						if (tax_office_select_field.prop('type') === 'text') {
							tax_office_select_field.val(response.doy_descr);
						}else {
							tax_office_select_field.val(response.doy).trigger('change');
						}
					}

					if (response.postal_address) {
						billing_address_1.val(response.postal_address);
					}

					if (response.postal_address_no) {
						billing_address_1.val(billing_address_1.val()+" "+response.postal_address_no);
					}

					if (response.postal_area_description) {
						$('#_billing_city').val(response.postal_area_description);
					}

					if (response.postal_zip_code) {
						$('#_billing_postcode').val(response.postal_zip_code);
					}

					if (response.activities && response.activities[0]) {
						$('#_billing_activity').val(response.activities[0]);
					}
				}
			});
		});

        checkTimologioFieldsVisibility();
				let webexpert_generating_invoice = false;
				$(document).on('click' , '.webexpert-timologio-for-woocommerce-generate-invoice', function(e) {
						e.preventDefault();
						let link = $(this);
						let action = getParameterByName('action' , link.attr('href'));
						let orderId = getParameterByName('o' , link.attr('href'));

						let ajaxLoader = $("#webexpert-timologio-for-woocommerce-ajax-loader").val();
						link.html(`<img src="${ajaxLoader}" width="16" height="16" />`);

						if(webexpert_generating_invoice){
							return;
						}
						webexpert_generating_invoice = true;

						$.ajax({
							url: webexpert_ajax_object.ajax_url,
							method:'POST',
							data: {
								action: "webexpert_timologio_for_woocommerce_generate_invoice",
								type: action,
								order_id : orderId
							},
							success: function(response) {
								$.ajax({
									url: window.location.href,
									success: function(resp) {
										window.open(response.url , '_blank');
										let updatedHtml = $(resp).find('.webexpert-timologio-for-woocommerce-generating-box').html();
										$('.webexpert-timologio-for-woocommerce-generating-box').html(updatedHtml);
									},
									complete:function() {
										webexpert_generating_invoice = false;
									},
								});
							},
							error:function(response){
								alert(response.responseJSON.error);
							}
						});


				});


				$(document).on('change', '#webexpert_timologio_for_woocommerce_finalization_invoice', function(e) {
						 let orderId = $(this).data('order-id');
						 let result = $(this).is(':checked') ? 'yes' : 'no';

						 let data = {
							 action : 'webexpert_timologio_for_wc_finalize_order_invoice',
							 result,
							 orderId,
						 };

						 $.ajax({ url: webexpert_ajax_object.ajax_url, method:'POST', data: data ,	 success:function(response) {} });

				});

				$(document).on('click', '#webexpert-timologio-for-woocommerce-custom-invoice-button', function(e){
						$("#webexpert-timologio-for-woocommerce-custom-invoice-file").click();
				});

				$(document).on('click', '#webexpert_timologio_for_woocommerce_check_aade_connection', function(e){
						e.preventDefault();

						let aade_vat = $("#woocommerce_store_vat_id").val().trim();
						let aade_username = $("#webexpert_timologio_for_woocommerce_aade_username").val().trim();
						let aade_password = $("#webexpert_timologio_for_woocommerce_aade_password").val().trim();

						$.ajax({
								url:webexpert_ajax_object.ajax_url,
								method:'POST',
								data:{
									action: 'webexpert_timologio_for_wc_check_aade_connection',
									aade_vat,
									aade_username,
									aade_password
								},
								success: function(response) {
									alert(response.msg);
								}
						});

				});

				$(document).on('change', '#webexpert-timologio-for-woocommerce-custom-invoice-file', function(e){
							 let fileExtension = ['pdf', 'txt', 'docx', 'doc'];
							 let button = $("#webexpert-timologio-for-woocommerce-custom-invoice-button");
							 let ajaxLoader = $("#webexpert-timologio-for-woocommerce-ajax-loader").val();
							 let orderId = $(this).data('order-id');

							 if ($.inArray($(this).val().split('.').pop().toLowerCase(), fileExtension) == -1) {
									 alert("Only formats are allowed : "+fileExtension.join(', '));
									 return;
							 }

							 button.html(`<img src="${ajaxLoader}" height="16" width="16" />`);
							 let formData = new FormData();
							 formData.append('invoice_file', this.files[0]);
							 formData.append('action' , 'webexpert_timologio_for_wc_finalize_order_invoice_upload');
							 formData.append('orderId', orderId);
								$.ajax({
								    url: webexpert_ajax_object.ajax_url,
								    type: 'POST',
								    data: formData,
								    contentType: false,
								    processData: false,
								    success: function(response){
												$.ajax({
														url: window.location.href,
														success: function(resp) {
																let updatedHtml = $(resp).find('.webexpert-timologio-for-woocommerce-generating-box-2').html();
																$('.webexpert-timologio-for-woocommerce-generating-box-2').html(updatedHtml);
														}
												});
								    },
										error: function(response) {
											  console.log(response);
												console.log(response.responseJSON.errors.error);
										}
								});
				});

				$(document).on('click' , "#webexpert_timologio_for_wc_delete_uploaded_file", function(e){
							let orderid = $(this).data('order-id');
							let ajaxLoader = $("#webexpert-timologio-for-woocommerce-ajax-loader").val();
							$(this).html(`<img src="${ajaxLoader}" height="16" width="16" />`);

							if(confirm('Are you sure?')) {
									$.ajax({
											url:webexpert_ajax_object.ajax_url,
											method:'POST',
											data:{
												orderId : orderid,
												action: 'webexpert_timologio_for_wc_order_invoice_upload_delete'
											},
											success:function(response) {
													$.ajax({
															url: window.location.href,
															success: function(resp) {
																	let updatedHtml = $(resp).find('.webexpert-timologio-for-woocommerce-generating-box-2').html();
																	$('.webexpert-timologio-for-woocommerce-generating-box-2').html(updatedHtml);
															}
													});
											}
									});
							}
				});

				if($('.webexpert_timologio_for_woocommerce_vat_exempt_categories').length) {
					$('.webexpert_timologio_for_woocommerce_vat_exempt_categories').select2({
							width: '350px'
					});
				}

				if($('.webexpert_timologio_for_woocommerce_vat_exempt_tax_class').length) {
					$('.webexpert_timologio_for_woocommerce_vat_exempt_tax_class').select2({
							width: '350px'
					});
				}
    });
})( jQuery );