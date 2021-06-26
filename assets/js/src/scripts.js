(function ($) {
  // on document ready
  $(function () {
    $(".wpulike").WordpressUlike();
  });

  // Init ulike buttons
  $(".wpulike").WordpressUlike();

  // Update elements on ajax loaded
  $(document).ajaxComplete(function () {
    // init WordpressUlike
    $(".wpulike").WordpressUlike();
  });
})(jQuery);
