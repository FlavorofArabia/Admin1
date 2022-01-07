/*global $, alert*/
/*Table Content
========================
    # Header Top Menu
    # Menu Toggle 
    # Magnific Popup    
    # page Overlay
*/

$(document).ready(function () {
    "use strict";
    
    $("[data-countdown]").each(function () {
        var $this = $(this),
            finalDate = $(this).data('countdown');
        $this.countdown(finalDate, function (event) {
            $this.html(event.strftime('<div class="box-time-date day"><div class="box-time-date-inner"><span class="time-num">%-D</span>يوم</div></div> <div class="box-time-date hour"><div class="box-time-date-inner"><span class="time-num">%-H</span> ساعة</div></div> <div class="box-time-date minutes"><div class="box-time-date-inner"><span class="time-num">%-M</span>دقيقة</div></div></div>'));
        });
    });
    
});


$(document).ready(function () {
    
    "use strict";
	
	$("<div class='mobilemenu-overlay'></div>").appendTo("body");
	
	$('#gx-sidemenu').gxSideMenu({
        mode: isMobile.any() ? 'normal' : 'normal', // normal | tiny
        interval: 300, // animations' interval
        direction: 'left', // left | right
        openOnHover: false, // true | false
        clickTrigger: true, // true | false
        followURLs: true, // true | false
        trigger: ".mobile-menu-icon, .mobilemenu-overlay", // class or id of trigger element
        startFrom: 280, // start pixel from corner on hover trigger
        startClosed: false, // menu opens on document load
        scrolling: false, // menu scrollable (iScroll plugin needed!)
        urlBase: '/sidemenu/', // document base URL
        backText: 'القائمة الرئيسية', // Back button text
        onOpen: function() { }, // Open callback
        onClose: function() { } // Close callback
    });
	
	$(".mobile-menu-icon").click(function (e){
        e.preventDefault();
        		
		$(".mobilemenu-overlay").addClass('in');
        
    }); 
	  
    $(".overlay-drop.cart-icon").click(function (e){
        e.preventDefault();
        		
		$(".side-cart").addClass('in');
        
    }); 
	
	$(".search-icon").click(function (e){
        e.preventDefault();
        		
		$(".main-search").addClass('in');
        
    }); 
	$(".user-icon").click(function (e){
        e.preventDefault();
        		
		$(".user-profile").addClass('in');
        
    }); 
	
	
    $(".overlay-drop").click(function (e){
        e.preventDefault();
        		
		$(".page-overlay, #menu-btn").addClass('in');
		
        
    });  
	
	
    $(".page-overlay, #menu-btn, .mobilemenu-overlay").click(function (e) {
        e.preventDefault();
        
        $(".page-overlay, #menu-btn, .side-cart, .main-search, .user-profile, .mobilemenu-overlay").removeClass('in');
    });     
 
    
});


/*Menu Toggle
============================*/
$(document).ready(function () {
    
    "use strict";
    
    function menuContToggle() {
        
        var menuItem = $(".menu-cont > li > a");
        
        if (($(window).width()) < 992) {
            
            $(menuItem).next().css("display", "none");
            
            $(menuItem).click(function (e) {
                
                e.preventDefault();
                
                $(this).next().slideToggle(500);
                
            });
        } else {
            
            $(menuItem).next().css("display", "block");
            
        }
        
    }
    
    menuContToggle();
    
    $(window).on('resize', function () {
        
        menuContToggle();
        
	});
    
    /*$(".menu-toggle").click(function () {
        
        $(".mega-menu").toggleClass("menu-open");
        
    });*/
    
});

/*Magnific Popup
============================*/
$(document).ready(function () {
    
    "use strict";
    
    $('.popup-text').magnificPopup({
        
        removalDelay: 500,
        
        closeBtnInside: true,
        
        callbacks: {
            
            beforeOpen: function () {
                
                this.st.mainClass = this.st.el.attr('data-effect');
                
            }
            
        },
        
        midClick: true
    });
    
});

/*page Overlay
============================*/
$(document).ready(function () {
    
    "use strict";
    
    $("<div class='page-overlay'></div>").appendTo("body");
	$("<div class='pageCart-overlay'></div>").appendTo("body");
    
    
    $(".vertical-menu").hover(function () {

        $(".menu-cont, .page-overlay").toggleClass('in');
		$(".cart-wish-item.dropdown").toggleClass('zindexdown');
		
        
    });
	
    $(".cart-wish-item.dropdown").hover(function () {
        
        $(".pageCart-overlay").toggleClass('in');
        
    });
    
});


/*Select-Item
========================*/
$(document).ready(function () {
    "use strict";
    $(".select-active").click(function (e) {
        e.preventDefault();
        $(this).next().slideToggle(500);
        $(this).toggleClass("active");
    });
});




/*Select Menu
=========================*/
$('#discuss , .select-ui, .select-box').selectmenu();

/*Owl Carousel
=============================*/
$(document).ready(function () {
    "use strict";
    $(".widgets_carousel").owlCarousel({
        items : 1,
        itemsDesktopSmall : [979, 1],
        itemsDesktop : [1199, 1],
        itemsTablet: [767, 2],/*767px*/
        itemsMobile : [480, 1],/*498px*/
        navigation : true,
        pagination : false,
        autoPlay : true,
        navigationText: ["<i class='fa fa-chevron-left'></i>", "<i class='fa fa-chevron-right'></i>"]
    });
    
    $(".three_carousel").owlCarousel({
        items : 4,
        itemsDesktopSmall : [979, 2],
        itemsDesktop : [1199, 2],
        itemsTablet: [749, 2],/*767px*/
        itemsMobile : [500, 1],/*517px*/
        navigation : true,
        pagination : false,
        autoPlay : true,
        navigationText: ["<i class='fa fa-chevron-left'></i>", "<i class='fa fa-chevron-right'></i>"]
    });
    
    $(".cart-slider").owlCarousel({
        items : 2,
        itemsDesktopSmall : [979, 2],
        itemsDesktop : [1199, 2],
        itemsTablet: [749, 2],/*767px*/
        itemsMobile : [500, 1],/*517px*/
        navigation : true,
        pagination : false,
        autoPlay : true,
        navigationText: ["<i class='fa fa-angle-left'></i>", "<i class='fa fa-angle-right'></i>"]
    });
});




/* Product Rate
==================================*/

$(document).ready(function () {
    
    'use strict';
    
    $('.hover-rate').each(function () {
        
        var i = $(this).children('.active:last').index();

        $(this).on('mouseenter mouseleave', 'li', function () {

            $(this).add($(this).siblings()).removeClass('active');

            $(this).add($(this).prevAll()).addClass('active');

        }).on('mouseleave', function () {
            $(this).children('li').removeClass('active').eq(i).addClass('active').prevAll().addClass('active');
        });

    });
    
});


/*Tab Panel
===============================*/

$(document).ready(function () {
    
    "use strict";
    
    $(".tab").each(function () {
            
        $(".tab-panel").not(":first").hide();

        $("li", this).removeClass("active");

        $("li:first-child", this).addClass("active");

        $(".tab-panel:first-child").show();

        $("li", this).click(function (t) {

            var i = $("a", this).attr("href");

            $(this).siblings().removeClass("active");

            $(this).addClass("active");

            $(i).siblings().hide();

            $(i).fadeIn(400);

            t.preventDefault();
        });
    });
});

/*Add Review Scroll
==============================*/

$(document).ready(function () {

    "use strict";
    
    $('a[href="#reviews"].add-review').click(function (e) {
        
        e.preventDefault();
        
        $('.tab a[href="#reviews"]').trigger('click');
        
        $('body, html').animate({
            scrollTop: $("#reviews").offset().top
        }, 1000);

    });
    
});

/* Show Product Zoom
==============================*/
function thumblist() {
    "use strict";
    if ($('#show-product-gal').length) {
        $('#show-product-gal').carouFredSel({
            prev  : '.thumblist-box .prev',
            next  : '.thumblist-box .next',
            width : '100%',
            auto  : false,
            swipe : {
                onMouse : false,
                onTouch : true
            }
        });
    }
}
function zoom() {
    "use strict";
    if ($.fn.elevateZoom) {
        var image = $('.general-img').find('img');
        image.removeData('elevateZoom');
        $('.zoomContainer').remove();
        image.elevateZoom({
            gallery            : 'show-product-gal',
            cursor             : 'crosshair',
            galleryActiveClass : 'active',
            zoomWindowWidth    : 0,
            zoomWindowHeight   : 0,
            borderSize         : 0,
            borderColor        : 'none',
            easing             : true,
            zoomWindowFadeIn   : 100,
            zoomWindowFadeOut  : 100,
            lensFadeIn         : 100,
            lensFadeOut        : 100,
            zoomType		   : 'inner'
        });
    }
}

$(document).ready(function () {
    
    'use strict';
    
    zoom();
    
    thumblist();
    
    $(window).on('resize', function () {
        
        zoom();
        
        thumblist();
        
	});
    
});



/* Product Number Counter 
====================================*/
$(document).ready(function () {
    'use strict';
    
    $('body').on('click','.number-up', function () {
        let element = $(this).closest('.cat-number').find('input[type="text"]');
        let value = parseInt(element.val());
        let productId = element.attr('data-productId');
        let cartId = element.attr('data-cartId');
        let dataAction = element.attr('data-action');
        $(this).closest('.cat-number').find('input[type="text"]').val(parseFloat(value) + 1).attr('value', value + 1);
        if (dataAction == 'add'){
            cart.add(productId,value + 1);
        } else {
            cart.update(cartId,value + 1);
        }
        return false;
    });
    
    $('body').on('click','.number-down', function () {

        let element = $(this).closest('.cat-number').find('input[type="text"]');
        let value = parseInt(element.val());
        let productId = element.attr('data-productId');
        let cartId = element.attr('data-cartId');
        let dataAction = element.attr('data-action');
        if (value > 1) {
            $(this).closest('.cat-number').find('input[type="text"]').val(parseFloat(value) - 1).attr('value', value - 1);

            if (dataAction == 'add'){
                cart.add(productId,value-1);
            } else {
                cart.update(cartId,value-1);
            }
        }
        return false;
    });
    
    
    $('.cat-number').find('input[type="text"]').on('keyup', function () {
        $(this).attr('value', $(this).val());
    });

    $(".pageCart-overlay.in").css({'position':'static'})
});