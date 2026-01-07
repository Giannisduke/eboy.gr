jQuery(document).ready(function ($) {
	$("#mega-menu-wrap-main-menu #mega-menu-main-menu > li.mega-menu-tabbed > ul.mega-sub-menu > li.mega-menu-item ").on({
    mouseenter: function () {
        //console.log("checked2");
		if($(this).children("ul").length == 0)
	  {
		$("#mega-menu-wrap-main-menu #mega-menu-main-menu > li.mega-menu-tabbed > ul.mega-sub-menu > li.mega-menu-item.mega-menu-item-has-children.mega-toggle-on > ul.mega-sub-menu").hide();
	  }
    },
    mouseleave: function () {
        //stuff to do on mouse leave
    }
});
});