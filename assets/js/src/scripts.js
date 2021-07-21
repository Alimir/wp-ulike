(function ($) {
  // Init ulike buttons
  $(".wpulike").WordpressUlike();

  /**
   * jquery detecting div of certain class has been added to DOM
   */
  function WordpressUlikeOnElementInserted(
    containerSelector,
    elementSelector,
    callback
  ) {
    var onMutationsObserved = function (mutations) {
      mutations.forEach(function (mutation) {
        if (mutation.addedNodes.length) {
          var elements = $(mutation.addedNodes).find(elementSelector);
          for (var i = 0, len = elements.length; i < len; i++) {
            callback(elements[i]);
          }
        }
      });
    };

    var target = $(containerSelector)[0];
    var config = {
      childList: true,
      subtree: true,
    };
    var MutationObserver =
      window.MutationObserver || window.WebKitMutationObserver;
    var observer = new MutationObserver(onMutationsObserved);
    observer.observe(target, config);
  }

  // On wp ulike element added
  WordpressUlikeOnElementInserted("body", ".wpulike", function (element) {
    $(element).WordpressUlike();
  });
})(jQuery);
