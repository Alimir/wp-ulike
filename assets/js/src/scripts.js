(function (window, document) {
  "use strict";

  // Helper function to initialize ulike on elements
  function initUlike(elements) {
    if (!elements) return;

    // Handle single element or NodeList
    var elementArray = elements.length !== undefined
      ? Array.prototype.slice.call(elements)
      : [elements];

    elementArray.forEach(function (element) {
      if (element && typeof WordpressUlike !== "undefined") {
        // Check if already initialized (using data attribute as marker)
        if (!element.hasAttribute("data-ulike-initialized")) {
          new WordpressUlike(element);
          element.setAttribute("data-ulike-initialized", "true");
        }
      } else if (element && typeof jQuery !== "undefined" && jQuery.fn && jQuery.fn.WordpressUlike) {
        // Fallback to jQuery if available
        jQuery(element).WordpressUlike();
      }
    });
  }

  // Init ulike buttons on page load
  var wpulikeElements = document.querySelectorAll(".wpulike");
  initUlike(wpulikeElements);

  /**
   * Detecting div of certain class has been added to DOM
   */
  function WordpressUlikeOnElementInserted(
    containerSelector,
    elementSelector,
    callback
  ) {
    var onMutationsObserved = function (mutations) {
      mutations.forEach(function (mutation) {
        if (mutation.addedNodes.length) {
          mutation.addedNodes.forEach(function (node) {
            // Check if the added node itself matches
            if (node.nodeType === 1 && node.matches && node.matches(elementSelector)) {
              callback(node);
            }
            // Check for children that match
            if (node.nodeType === 1 && node.querySelectorAll) {
              var elements = node.querySelectorAll(elementSelector);
              for (var i = 0, len = elements.length; i < len; i++) {
                callback(elements[i]);
              }
            }
          });
        }
      });
    };

    var target = document.querySelector(containerSelector);
    if (!target) return;

    var config = {
      childList: true,
      subtree: true,
    };
    var MutationObserver =
      window.MutationObserver || window.WebKitMutationObserver;

    if (MutationObserver) {
      var observer = new MutationObserver(onMutationsObserved);
      observer.observe(target, config);
    }
  }
  // On wp ulike element added
  WordpressUlikeOnElementInserted("body", ".wpulike", function (element) {
    initUlike(element);
  });
})(window, document);
