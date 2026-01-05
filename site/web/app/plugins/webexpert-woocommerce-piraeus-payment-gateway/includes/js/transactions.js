(function( $ ) {
    'use strict';
    jQuery( document ).ready(function() {
        function json2table(json, classes) {
            var cols = Object.keys(json[0]);
            var headerRow = '';
            var bodyRows = '';
            classes = classes || '';
            function capitalizeFirstLetter(string) {
                return string.charAt(0).toUpperCase() + string.slice(1);
            }
            cols.map(function(col) {
                headerRow += capitalizeFirstLetter(col) + '\t';
            });
            json.map(function(row) {
                bodyRows += '\n';
                cols.map(function(colName) {
                    bodyRows += row[colName] + '\t';
                })
            });
            return classes + headerRow + bodyRows;
        }
        jQuery('#installments_validator').on('click',function(e){
            e.preventDefault();
            var data='{' + $('#woocommerce_piraeusbank_gateway_pb_installments_options_array').val() + '}';
            try {
                JSON.parse(data);
                alert("Έγκυρη μορφή");
            } catch (e) {
                if (e instanceof SyntaxError) {
                    alert("Μη έγκυρη μορφή");
                } else {
                    alert("Μη έγκυρη μορφή");
                }
            }
        });

        $('#woocommerce_piraeusbank_gateway_pb_transactions_log').attr('readonly', 'readonly');
        $('#woocommerce_piraeusbank_gateway_pb_transactions_log').attr('disabled', 'disabled');
        jQuery.ajax({
            type : "post",
            dataType : "json",
            url : ajax_object.ajax_url,
            data : {action: "webexpert_piraeus_transactions"},
            success: function(response) {
                if(response.type == "success") {
                    if( $.isArray(response.data) && response.data.length ) {
                    jQuery("#woocommerce_piraeusbank_gateway_pb_transactions_log").val(json2table(response.data));
                        $('#woocommerce_piraeusbank_gateway_pb_transactions_log').attr('readonly', 'readonly');
                        $('#woocommerce_piraeusbank_gateway_pb_transactions_log').attr('disabled', 'disabled');
                    }
                }
            }
        });
    });
})( jQuery );