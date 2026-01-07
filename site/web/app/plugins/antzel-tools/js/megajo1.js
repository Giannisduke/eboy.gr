jQuery( function($) {
$("#mega-menu-wrap-main-menu #mega-menu-main-menu > li.mega-menu-megamenu > ul.mega-sub-menu li.mega-menu-column > ul.mega-sub-menu > li.mega-menu-item > a.mega-menu-link").each(function(){
//$("#mega-menu-wrap-main-menu #mega-menu-main-menu > li.mega-menu-megamenu > ul.mega-sub-menu li.mega-menu-column").each(function(){
  if($(this).parent().children("ul").length){
    $(this).addClass("jomenuca");
  }
  //$(this).addClass("jomenuca");
});
});
// Play/pause button on slider
jQuery( document ).ready(function($) {
    var playButton  = '.play-button';
    var pauseButton = '.pause-button';
    var current     = '.current';
    var sliderDiv   = '#rev_slider_7_1_wrapper';
   
    $(pauseButton).hide();
    $(playButton).hide();
       
        jQuery(current).show();
 
        jQuery(pauseButton).click(function() {
            jQuery(this).hide().removeClass( "current" );
            jQuery(playButton).show().addClass( "current" );
        });
        jQuery(playButton).click(function() {
            jQuery(this).hide().removeClass( "current" );
            jQuery(pauseButton).show().addClass( "current" );
        });
});
jQuery( document ).ready(function($) {
  $(window).resize(function(){
	var windowWidth = window.innerWidth;
	console.log(windowWidth);
	console.log(this.outerWidth);
	$('ul.mega-sub-menu').css({
		position: 'absolute',
		left: ((windowWidth / 2) - (this.outerWidth / 2) )
    });	
  });
  // call `resize` to center elements
  $(window).resize();
});