(function (window, document) {
  "use strict";

  // Helper function to initialize ulike on elements
  const initUlike = (elements) => {
    if (!elements) return;

    // Handle single element or NodeList
    const elementArray = elements.length !== undefined
      ? Array.from(elements)
      : [elements];

    elementArray.forEach((element) => {
      if (element && typeof WordpressUlike !== "undefined") {
        // Check if already initialized (using data attribute as marker)
        if (!element.hasAttribute("data-ulike-initialized")) {
          new WordpressUlike(element);
          element.setAttribute("data-ulike-initialized", "true");
        }
      }
    });
  };

  // Init ulike buttons on page load
  const wpulikeElements = document.querySelectorAll(".wpulike");
  initUlike(wpulikeElements);

  /**
   * Detecting div of certain class has been added to DOM
   */
  const WordpressUlikeOnElementInserted = (
    containerSelector,
    elementSelector,
    callback
  ) => {
    const onMutationsObserved = (mutations) => {
      mutations.forEach((mutation) => {
        if (mutation.addedNodes.length) {
          mutation.addedNodes.forEach((node) => {
            // Check if the added node itself matches
            if (node.nodeType === 1 && node.matches && node.matches(elementSelector)) {
              callback(node);
            }
            // Check for children that match
            if (node.nodeType === 1 && node.querySelectorAll) {
              const elements = node.querySelectorAll(elementSelector);
              elements.forEach((el) => callback(el));
            }
          });
        }
      });
    };

    const target = document.querySelector(containerSelector);
    if (!target) return;

    const config = {
      childList: true,
      subtree: true,
    };
    const MutationObserver =
      window.MutationObserver || window.WebKitMutationObserver;

    if (MutationObserver) {
      const observer = new MutationObserver(onMutationsObserved);
      observer.observe(target, config);
    }
  };

  // On wp ulike element added
  WordpressUlikeOnElementInserted("body", ".wpulike", (element) => {
    initUlike(element);
  });
})(window, document);
