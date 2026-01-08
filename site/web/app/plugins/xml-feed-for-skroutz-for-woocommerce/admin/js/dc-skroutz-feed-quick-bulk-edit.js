(function($) {
	'use strict';
	// fill quick edit input, on quick edit box opening
	$(".ptitle").on("focus", function(e) {
		let editRow = $(e.target).closest(".quick-edit-row"),
			id = parseInt(editRow.attr("id").replace("edit-", "")),
			SkroutzAvailVal = $(`#post-${id}`).find('.column-dicha_skroutz_feed_custom_availability span.hidden').text(),
			SkroutzAvailInput = editRow.find('select[name="dicha_skroutz_feed_custom_availability"]');

		if (SkroutzAvailVal && !SkroutzAvailInput.val()) {
			SkroutzAvailInput.val(SkroutzAvailVal);
		}
	});
})(jQuery);