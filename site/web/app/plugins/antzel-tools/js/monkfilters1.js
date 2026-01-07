jQuery( function($) {
	const container =  $('.monkallfilters');
	const myboxes =  $('.monkallfilters .wd-pf-checkboxes');
	const showMoreBtn1 =  $('.monkfiltershowmore');
	const showMoreBtn = showMoreBtn1[0];
	//console.log(showMoreBtn1);
	// Hide mybox items > 4 and show 'Show More' button
	if (myboxes.length > 4) {
	  for (let i = 4; i < myboxes.length; i++) {
		myboxes[i].style.display = 'none';
	  }
	  showMoreBtn.style.display = 'flex';
	}

	// Add click event listener to 'Show More' button
	showMoreBtn.addEventListener('click', () => {
	  for (let i = 4; i < myboxes.length; i++) {
		myboxes[i].style.display = 'inline-block';
	  }
	  showMoreBtn.style.display = 'none';
	});
	// hide prosfores button on archive filters if no products found on page
	if ($('.products.elements-grid .product-grid-item').length == 0) {
		//$('#wd-640a1f453f8ff').addClass('monkhideall');
		$('.monkallfilters').addClass('monkhideall');
		$('.wd-shop-active-filters').addClass('monkhideall');
	}

});