// Licence: http://jqueryfordesigners.com/jquery-infinite-carousel/
$.fn.infiniteCarousel = function () {

    return this.each(function () {
        var $wrapper = $('> div', this).css('overflow', 'hidden'),
            $slider = $wrapper.find('> ul'),
            $items = $slider.find('> li'),
            $single = $items.filter(':first'),

            // TODO: custumise this values!!! 
			speedy = 500,		// SET: animation speed
			scrollafter = 7500,	// SET: scroll after ... ms
            singleWidth = 540,	// SET: width of your images. $single.outerWidth() doesn't work correct :-(
            visible = 1, 		// SET: visible images. Or: Math.ceil($wrapper.innerWidth() / singleWidth), // note: doesn't include padding or border
            currentPage = 1,	// SET: start page.
            pages = Math.ceil($items.length / visible);
 
		// 1. Pad so that 'visible' number will always be seen, otherwise create empty items
        if (($items.length % visible) != 0) {
            $slider.append(repeat('<li class="empty" />', visible - ($items.length % visible)));
            $items = $slider.find('> li');
        }
		
        // 2. Top and tail the list with 'visible' number of items, top has the last section, and tail has the first
        $items.filter(':first').before($items.slice(- visible).clone().addClass('cloned'));
        $items.filter(':last').after($items.slice(0, visible).clone().addClass('cloned'));
        $items = $slider.find('> li'); // reselect

        // 3. Set the left position to the first 'real' item. Doesn't work correct :-(
        //$wrapper.scrollLeft(singleWidth * visible);

        // 4. paging function
        function gotoPage(page) {
            var dir = page < currentPage ? -1 : 1,
                n = Math.abs(currentPage - page),
                left = singleWidth * dir * visible * n;
			
            $wrapper.filter(':not(:animated)').animate({
                scrollLeft : '+=' + left
            }, speedy, function () {
                if (page == 0) {
                    $wrapper.scrollLeft(singleWidth * visible * pages);
                    page = pages;
                } else if (page > pages) {
                    $wrapper.scrollLeft(singleWidth * visible);
                    // reset back to start position
                    page = 1;
                } 
 
                currentPage = page;
            });
            
            return false;
        }
		
		gotoPage(0);	// keine Ahnung warum das sein muss
		setInterval(function() {
			gotoPage(currentPage - 1);
		}, scrollafter);
    });  
};
 
$(document).ready(function () {
  $('.infiniteCarousel').infiniteCarousel();
});
