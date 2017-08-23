/* Run :) */
(function( $ ) {
    // init WordpressUlike
    $(".wpulike").WordpressUlike();
    // Upgrading 'WordpressUlike' datasheets when new DOM has been inserted
    $(document).ready(function(){
        $(this).bind('DOMNodeInserted', function(e) {
            $(".wpulike").WordpressUlike();
        });         
    }); 
    // removes "empty" paragraphs
    $('p').filter(function () { return this.innerHTML == "" }).remove();
})( jQuery );