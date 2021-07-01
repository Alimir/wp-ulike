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

  // Init on buddypress activity stream
  $('#buddypress').on('bp_ajax_request', '[data-bp-list="activity"]', function () {
    $(".wpulike").WordpressUlike();
  });

})(jQuery);
