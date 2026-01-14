(function( $ ) {
    'use strict';

    document.addEventListener('DOMContentLoaded', function() {
        var invoice_select_field = $('#billing_invoice');
        var invoice_value='n';
        if (invoice_select_field.length>0) {
            if (typeof selectWoo !== 'undefined') {
                if (jQuery('.repositioning_fix').length>0) {
                    invoice_select_field.selectWoo({ minimumResultsForSearch: Infinity, dropdownParent: invoice_select_field.parent() });
                }else {
                    invoice_select_field.selectWoo({ minimumResultsForSearch: Infinity });
                }
            }

            invoice_value = invoice_select_field.val();
        }else {
            invoice_value = jQuery('input[type=radio][name=billing_invoice]').val();
        }

        var tax_office_select_field = $('#billing_tax_office');
        if (tax_office_select_field.length>0) {
            if (typeof selectWoo !== 'undefined') {
                tax_office_select_field.selectWoo({ minimumResultsForSearch: Infinity });
            }
        }
        function checkTimologioFieldsVisibility() {
            if (invoice_value === 'y') {
                $('.invoice-only').slideDown('fast');
                $('.invoice-only .optional').hide();
                $('.invoice-only label').each(function (index, value) {
                    if ($(this).find('.required').length===0) {
                        $(this).append(" <abbr class=\"required\" title=\"required\">*</abbr>");
                    }
                });
                if ($('#billing_country').val()!=='GR') {
                    $('.invoice-only label[for="billing_tax_office"]').find('.required').remove();
                }
            } else {
                $('.invoice-only').slideUp('fast');
                $('.invoice-only .optional').show();
                $('.invoice-only label').each(function (index, value) {
                    $(this).find('.required').remove();
                });
            }
        }

        $(document).on('change','#billing_country',function(){
            var billing_tax_office = $('.invoice-only label[for="billing_tax_office"]');
            if (invoice_value === 'y') {
                if ($(this).val()!=='GR') {
                    billing_tax_office.find('.required').remove();
                }else {
                    if (billing_tax_office.find('.required').length===0) {
                        billing_tax_office.append(" <abbr class=\"required\" title=\"required\">*</abbr>");
                    }
                }
            }
        });

        $(document).on('change','#billing_invoice',function(){
            invoice_value = jQuery(this).val();
            checkTimologioFieldsVisibility();
            jQuery.ajax({
                url : webexpert_ajax_object.ajax_url,
                type: 'POST',
                data: {
                    'action': 'webexpert_timologio_for_wc_invoice_value',
                    'invoice_option': invoice_value
                }
            });
            jQuery( 'body' ).trigger( 'update_checkout' );
        });

        $(document).on('change','input[type=radio][name=billing_invoice]',function(){
            invoice_value = this.value;
            checkTimologioFieldsVisibility();
            jQuery.ajax({
                url : webexpert_ajax_object.ajax_url,
                type: 'POST',
                data: {
                    'action': 'webexpert_timologio_for_wc_invoice_value',
                    'invoice_option': invoice_value
                }
            });
            jQuery( 'body' ).trigger( 'update_checkout' );
        });

        $(document).on('change', '#billing_39a', function() {
            jQuery( 'body' ).trigger( 'update_checkout' );
        });

        var billing_39a_field=$('#billing_39a_field');
        if (billing_39a_field.length > 0) {
            var customHtml = billing_39a_field.find('label').attr('data-custom-description');
            billing_39a_field.append(customHtml);
        }

        $(document).on('blur paste','#billing_vat_id',function(){
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
                    var billing_company=jQuery('#billing_company');
                    var billing_address_1=jQuery('#billing_address_1');

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
                        $('#billing_city').val(response.postal_area_description);
                    }

                    if (response.postal_zip_code) {
                        $('#billing_postcode').val(response.postal_zip_code);
                        jQuery( 'body' ).trigger( 'update_checkout' );
                    }

                    if (response.activities && response.activities[0]) {
                        $('#billing_activity').val(response.activities[0]);
                    }
                }
            });
        });

        $(document).on('blur paste','#billing_company,#billing_activity,#billing_vat_id,#billing_tax_office',function(){
            if (invoice_value === 'y') {
                if( !this.value ) {
                    $(this).parents(".form-row").addClass('woocommerce-invalid');
                    $(this).parents(".form-row").addClass('woocommerce-invalid-required-field');
                    $(this).parents(".form-row").removeClass('woocommerce-validated');
                }else {
                    $(this).parents(".form-row").removeClass('woocommerce-invalid');
                    $(this).parents(".form-row").removeClass('woocommerce-invalid-required-field');
                    $(this).parents(".form-row").addClass('woocommerce-validated');
                }
            }
        });

        checkTimologioFieldsVisibility();
    });

})( jQuery );
