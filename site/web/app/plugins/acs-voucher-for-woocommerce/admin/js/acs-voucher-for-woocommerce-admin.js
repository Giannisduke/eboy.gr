(function( $ ) {
	'use strict';
	$(document).ready(function(){
		//ACS Order Tracking
		// ------------------------------------------

		$(document).on('click', '.webexpert-acs-account-delete', function(e){
			e.preventDefault();
			$(this).next().find(`input`).each(function(){
				$(this).val('');
			});
			$(this).parent().css('display', 'none');
		});

		$(document).on('click', '.webexpert-add-extra-acs-acc', function(e){
			e.preventDefault();
			let element = $(".webexpert-acs-account-table").first();
			let clone = element.clone();
			clone.insertAfter($('.webexpert-acs-account-table').last()).hide().fadeIn(500);
			clone.find(`input`).each(function() {
				$(this).val('');
			});
			clone.find('.account_id').remove();
			clone.prepend(`<span class="webexpert-acs-account-delete">&times;</span>`);
		})

		if (jQuery('#webexpert_acs_disable_on_payments').length)
			jQuery('#webexpert_acs_disable_on_payments').select2();

		if (jQuery('#webexpert_acs_disable_on_shipping').length)
			jQuery('#webexpert_acs_disable_on_shipping').select2();

		jQuery( function($) {
			var from = $('input[name="mishaDateFrom"]'),
				to = $('input[name="mishaDateTo"]');

			$( 'input[name="mishaDateFrom"], input[name="mishaDateTo"]' ).datepicker( {dateFormat : "dd-mm-yy"} );
			from.on( 'change', function() {
				to.datepicker( 'option', 'minDate', from.val() );
			});

			to.on( 'change', function() {
				from.datepicker( 'option', 'maxDate', to.val() );
			});
		});

		$('#reset-job').on('click',function (e) {
			e.preventDefault();
			var $this=$(this);
			if (confirm($this.data('confirm'))) {
				$.ajax({
					type : "post",
					dataType : "json",
					url : webexpert_ajax_object.ajax_url,
					data : {action: "acs_reset_voucher", order_id : $this.data('order')},
					success: function () {
						location.reload();
					}
				});
			}
		});

		$('#acs_pickup_date').datepicker({
			dateFormat : 'yy-mm-dd'
		});

		$('#acs_special_cases').on('change',function(e){
			if(jQuery.inArray("INS", $(this).val()) !== -1) {
				jQuery('#acs_insurance_amount_container').show();
			}else {
				jQuery('#acs_insurance_amount_container').hide();
				$('input[name="acs_insurance_amount"]').val(0);
			}
		});

		$('#acs_create_voucher').on("click",function (e) {
			var $this=$(this);
			e.preventDefault();
			$this.addClass('disabled').addClass('is-active');
			jQuery.ajax({
				type : "post",
				dataType : "json",
				url : webexpert_ajax_object.ajax_url,
				data : {action: "acs_create_voucher", 'acs_account' : $("#acs_voucher_account").val() , order_id : $this.data('order'),parcels: $('#acs_parcels').val(),services: $('#acs_special_cases').val(), cod: $('input[name="acs_cod"]').val(), comments: $('textarea[name="acs_comments"]').val(),weight: $('input[name="acs_weight"]').val(),pickup_date: $('input[name="acs_pickup_date"]').val(),insurance_amount: $('input[name="acs_insurance_amount"]').val()},
				success: function(response) {
					$this.removeClass('disabled').removeClass('is-active');
					console.log(response);
					if(response === "success") {
						alert($this.data('success'));
						location.reload();
					}
					else {
						alert(response);
					}
				}
			})
		});

		$('#acs_print_voucher').on("click",function (e) {
			e.preventDefault();
			var $this=$(this);
			$this.addClass('disabled').addClass('is-active');
			jQuery.ajax({
				type : "post",
				dataType : "json",
				url : webexpert_ajax_object.ajax_url,
				data : {action: "acs_print_voucher", print_type: $('input[name="acs_print_voucher_type"]:checked').val(), order_id :$this.data('order')},
				success: function(response) {
					console.log(response);
					if (response.success) {
						window.open(response.data, "_blank");
					}else {
						window.alert(response.data)
					}
					$this.removeClass('disabled').removeClass('is-active');
				}
			})
		});

		$('.acs_print_voucher_type1').on("click",function (e) {
			e.preventDefault();
			var $this=$(this);
			$this.addClass('disabled').addClass('is-active');
			jQuery.ajax({
				type : "post",
				dataType : "json",
				url : webexpert_ajax_object.ajax_url,
				data : {action: "acs_print_voucher", print_type: $this.data('type'),order_id : $this.data('order')},
				success: function(response) {
					console.log(response);
					if (response.success) {
						window.open(response.data, "_blank");
					}else {
						window.alert(response.data)
					}
					$this.removeClass('disabled').removeClass('is-active');
				}
			})
		});

		$('.print-voucher-debug').on("submit",function (e) {
			e.preventDefault();
			var $this=$(this);
			$this.find('button').addClass('disabled').addClass('is-active');
			if ($this.find('#webexpert_print_voucher_acs').val().length === 0) {
				$this.find('button').removeClass('disabled').removeClass('is-active');
				return;
			}
			jQuery.ajax({
				type : "post",
				dataType : "json",
				url : webexpert_ajax_object.ajax_url,
				data : $this.serialize(),
				success: function(response) {
					$this.find('button').removeClass('disabled').removeClass('is-active');
					if (response.success) {
						window.open(response.data, "_blank");
					}else {
						window.alert(response.data)
					}
				}
			})
		});

		$('.acs-cod-beneficiary-info').on("submit",function (e) {
			e.preventDefault();
			var $this=$(this);
			$this.find('button').addClass('disabled').addClass('is-active');
			jQuery.ajax({
				type : "post",
				dataType : "json",
				url : webexpert_ajax_object.ajax_url,
				data : $this.serialize(),
				success: function(response) {
					$this.find('button').removeClass('disabled').removeClass('is-active');
					$('table.cod_beneficiary_container').empty()
					if (response.cod_beneficiary_info) {
						if(response.cod_beneficiary_info.length>0) {
							$('table.cod_beneficiary_container').append("<tr><td><strong>POD</td><td><strong>Receiver</strong></td><td><strong>Delivery_Date</strong></td><td><strong>COD</strong></td></tr>");
							$.each(response.cod_beneficiary_info, function (index, list) {
								var d = new Date(list.Parcel_Delivery_Date);
								$('table.cod_beneficiary_container').append("<tr><td>"+list.POD+"</td><td>"+list.Parcel_Receiver+"</td><td>"+d.toLocaleDateString()+"</td><td>"+list.Parcel_COD_Amount+"€</td></tr>");
							});
						}else {
							alert($this.data('success'));
						}
					}else {
						alert(response);
					}
				}
			})
		});

		$('.acs-find-pickup-lists').on("submit",function (e) {
			e.preventDefault();
			var $this=$(this);
			$this.find('button').addClass('disabled').addClass('is-active');
			jQuery.ajax({
				type : "post",
				dataType : "json",
				url : webexpert_ajax_object.ajax_url,
				data : $this.serialize(),
				success: function(response) {
					console.log(response);
					$this.find('button').removeClass('disabled').removeClass('is-active');
					$('ul.pickup_list_result_container').empty()
					if (response.lists) {
						if(response.lists.length>0) {
							$.each(response.lists, function (index, list) {
								$('ul.pickup_list_result_container').append(
									"<li><span class='dashicons dashicons-media-document'></span> " +
									"<a class='acs-found-pickup-list-print' data-list='" + list.massnumber + "' href='#print_list'>" +
									list.list +
									"</a></li>"
								);
							});
						}else {
							alert($this.data('success'));
						}
					}else {
						alert(response);
					}
				}
			})
		});

		jQuery(document).on('click', '.acs-found-pickup-list-print', function (e) {
			e.preventDefault();
			const pickupList = jQuery(this).data('list');
			jQuery.ajax({
				url: ajaxurl, // WordPress global AJAX URL
				method: 'POST',
				dataType: 'json',
				data: {
					action: 'acs_print_pickup_list',
					pickupList: pickupList
				},
				success: function (response) {
					if (response.url) {
						window.open(response.url, '_blank');
					} else {
						alert('⚠️ No file URL returned or something went wrong.');
						console.error(response);
					}
				},
				error: function (xhr, status, error) {
					alert('An error occurred while generating the pickup list PDF.');
				}
			});
		});

		$('.acs-cancel-voucher-debug').on("submit",function (e) {
			e.preventDefault();
			var $this=$(this);
			$this.find('button').addClass('disabled').addClass('is-active');
			if ($this.find('#webexpert_cancel_voucher_acs').val().length === 0) {
				$this.find('button').removeClass('disabled').removeClass('is-active');
				return;
			}
			jQuery.ajax({
				type : "post",
				dataType : "json",
				url : webexpert_ajax_object.ajax_url,
				data : $this.serialize(),
				success: function(response) {
					$this.find('button').removeClass('disabled').removeClass('is-active');
					if(response === "success") {
						alert($this.data('success'));
						location.reload();
					}
					else {
						alert(response);
					}
				}
			})
		});

		$('.acs_cancel_voucher').on("click",function (e) {
			e.preventDefault();
			var $this=$(this);
			$this.addClass('disabled').addClass('is-active');
			jQuery.ajax({
				type : "post",
				dataType : "json",
				url : webexpert_ajax_object.ajax_url,
				data : {action: "acs_cancel_voucher", order_id : $this.data('order')},
				success: function(response) {
					$this.removeClass('disabled').removeClass('is-active');
					if(response === "success") {
						alert($this.data('success'));
						location.reload();
					}
					else {
						alert(response);
					}
				}
			})
		});

		$('.acs_close_pending_voucher').on("click",function (e) {
			e.preventDefault();
			var $this=$(this);
			$this.addClass('disabled').addClass('is-active');
			jQuery.ajax({
				type : "post",
				dataType : "json",
				url : webexpert_ajax_object.ajax_url,
				data : {action: "acs_close_voucher"},
				success: function(response) {
					$this.removeClass('disabled').removeClass('is-active');
					if (response.url) {
						window.open(response.url, "_blank");
						location.reload();
					}else {
						if(response === "success") {
							alert($this.data('success'));
						}
						else {
							alert(response);
						}
					}
				}
			})
		})
	});

})( jQuery );
