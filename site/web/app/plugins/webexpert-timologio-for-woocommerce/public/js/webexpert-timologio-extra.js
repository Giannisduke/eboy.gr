(function( $ ) {
    'use strict';

    document.addEventListener('DOMContentLoaded', function() {
        $(".woocommerce-checkout input[type=text] , .woocommerce-checkout textarea").each(function(){
            $(this).val($(this).val().normalize('NFD').replace(/[\u0300-\u036f]/g, "").toUpperCase());
        });
    });

    $(document).on('input', ".woocommerce-checkout input[type=text] , .woocommerce-checkout textarea" , function(){
        $(this).val($(this).val().normalize('NFD').replace(/[\u0300-\u036f]/g, "").toUpperCase());
    });

})( jQuery );
