(function( $ ) {
    'use strict';
    $(document).ready(function(){
        jQuery( ".datepicker" ).datepicker({
            dateFormat : "dd/mm/yy"
        });

        $(document).on('click', '.webexpert-voucher-tracking', function(e){
            e.preventDefault();
            var $this=jQuery(this);
            jQuery.ajax({
                type : "post",
                dataType : "json",
                url : webexpert_ajax_object.ajax_url,
                data : {action: "webexpert_order_tracking_thickbox", 'order_id' : $this.data('order'), vendor : $this.data('vendor'), voucher : $this.data('voucher'), title : $this.data('title')},
                success: function(response) {
                    $this.removeClass('disabled').removeClass('is-active');
                    jQuery("#webexpert-voucher-tracking-" + response.order_id).html('').html(response.html);
                    tb_show('', "#TB_inline?height=400&amp;width=600&amp;inlineId=webexpert-voucher-tracking-" + response.order_id);
                }
            })
        });

        $('.webexpert-woocommerce-export-orders').on("submit",function (e) {
            e.preventDefault();
            var $this=$(this);
            $this.find('button').addClass('disabled').addClass('is-active');
            jQuery.ajax({
                type : "post",
                url : webexpert_ajax_object.ajax_url,
                data : $this.serialize(),
                success: function(data) {
                    $this.find('button').removeClass('disabled').removeClass('is-active');
                    if (data.error) {
                        alert(data.error);
                        return;
                    }

                    var downloadLink = document.createElement("a");
                    data = data.substring(0, data.length-1);
                    var fileData = [data];
                    var blobObject = new Blob(fileData,{
                        type: "text/csv;charset=utf-8;"
                    });

                    var url = URL.createObjectURL(blobObject);
                    downloadLink.href = url;
                    downloadLink.download = "orders.csv";
                    document.body.appendChild(downloadLink);
                    downloadLink.click();
                    document.body.removeChild(downloadLink);
                }
            })
        });
    });
})( jQuery );
function WebExpertopenInNewTab(url) {
    window.open(url, '_blank').focus();
    return false;
}