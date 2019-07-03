/* Run :) */
(function($) {
  // on document ready
  $(function() {
    // Upgrading 'WordpressUlike' datasheets when new DOM has been inserted
    $(this).bind("DOMNodeInserted", function(e) {
      $(".wpulike").WordpressUlike();
    });
  });

  // init WordpressUlike
  $(".wpulike").WordpressUlike();
})(jQuery);
