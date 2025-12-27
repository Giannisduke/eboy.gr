jQuery(document).ready(function($) {
  $(".owl-carousel").owlCarousel({
    items:3,
    loop:true,
    margin:5,
    autoplay:true,
    autoWidth: true,
    autoplayTimeout: 1000, // time between slides in ms
    autoplaySpeed: 1000,   // speed of the slide animation
    smartSpeed: 1000, 
    autoplayHoverPause:true
});

});

