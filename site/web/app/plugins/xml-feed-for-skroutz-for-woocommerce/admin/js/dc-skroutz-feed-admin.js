(function($) {
	'use strict';
	$(document).ready(function() {
		$('.xml-feed-for-skroutz-for-woocommerce-settings select.select-woo-input').selectWoo({width: '300px'});

		const minutesInput = $('.dicha-skroutz-cron-minute-input-wrapper');

		$('#dicha_skroutz_feed_cron_hour').on('change', function() {
			$(this).val() < 1 ? minutesInput.hide() : minutesInput.show();
		});

		// remove successful run param from url if exists
		const url = new URL(window.location.href);
		const searchParams = new URLSearchParams(url.search);

		if (searchParams.has('feed_success')) {
			searchParams.delete('feed_success');
			window.history.replaceState(null, '', `${url.origin}${url.pathname}?${searchParams.toString()}`);
		}
	});
})(jQuery);