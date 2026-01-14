jQuery(function($){
	$(document).ready(function(){
        $('.webexpert_google_merchant_product_data_feed select').select2({width: '350px'});
        $('#google_categories_map').select2();
	    $('.we-google-merchant-smart-switch').on('click',function(e){
	        e.preventDefault();

	        if ($(this).attr('data-action')==="include") {
                $(this).attr('data-action',"exclude");
                $(this).text($(this).data('lang-exclude'));
                $(this).parent().parent().next().find('input[type="checkbox"]').prop('checked', false);
            }else {
                $(this).attr('data-action',"include");
                $(this).text($(this).data('lang-include'));
                $(this).parent().parent().next().find('input[type="checkbox"]').prop('checked', true);
            }
        })
    });
 });