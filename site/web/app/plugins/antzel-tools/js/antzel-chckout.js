jQuery(document).ready(function($) {
	$('a.woocommerce-terms-and-conditions-link').on('click touchend', function(event) {
		event.preventDefault();
		window.location.href = $(this).attr('href');
		return false;
	});
});