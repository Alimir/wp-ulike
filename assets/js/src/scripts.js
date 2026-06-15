/**
 * WP ULike Scripts - Initialization
 *
 * @fileoverview Auto-initializes WP ULike plugin on page load and dynamic content
 * @requires ES7 (ES2016) compatible browser
 * @author WP ULike Team
 * @see https://github.com/alimir/wp-ulike
 */
(function (window, document) {
  "use strict";

  const arrayFrom = (arrayLike) => {
    if (Array.from) {
      return Array.from(arrayLike);
    }
    return Array.prototype.slice.call(arrayLike);
  };

  const initUlike = (elements) => {
    if (!elements || typeof WordpressUlike === "undefined") {
      return;
    }

    const elementArray = elements.length !== undefined ? arrayFrom(elements) : [elements];

    elementArray.forEach((element) => {
      if (element && !element.hasAttribute("data-ulike-initialized")) {
        new WordpressUlike(element);
        element.setAttribute("data-ulike-initialized", "true");
      }
    });
  };

  const pendingElements = new Set();
  let mutationFrameScheduled = false;

  const flushPendingInits = () => {
    mutationFrameScheduled = false;

    if (!pendingElements.size) {
      return;
    }

    const batch = arrayFrom(pendingElements);
    pendingElements.clear();
    initUlike(batch);
  };

  const queueInit = (element) => {
    if (!element || element.nodeType !== 1 || !element.matches(".wpulike")) {
      return;
    }

    pendingElements.add(element);

    if (mutationFrameScheduled) {
      return;
    }

    mutationFrameScheduled = true;
    window.requestAnimationFrame(flushPendingInits);
  };

  const collectMatchingNodes = (node, elementSelector, callback) => {
    if (node.nodeType !== 1) {
      return;
    }

    if (node.matches && node.matches(elementSelector)) {
      callback(node);
    }

    if (node.querySelectorAll) {
      arrayFrom(node.querySelectorAll(elementSelector)).forEach(callback);
    }
  };

  const WordpressUlikeOnElementInserted = (containerSelector, elementSelector, callback) => {
    const onMutationsObserved = (mutations) => {
      mutations.forEach((mutation) => {
        if (!mutation.addedNodes.length) {
          return;
        }

        mutation.addedNodes.forEach((node) => {
          collectMatchingNodes(node, elementSelector, callback);
        });
      });
    };

    const target = document.querySelector(containerSelector);
    if (!target) {
      return null;
    }

    const MutationObserver = window.MutationObserver || window.WebKitMutationObserver;
    if (!MutationObserver) {
      return null;
    }

    const observer = new MutationObserver(onMutationsObserved);
    observer.observe(target, {
      childList: true,
      subtree: true,
    });

    return observer;
  };

  initUlike(document.querySelectorAll(".wpulike"));

  // Observe body so custom load-more, AJAX, and page-builder injections keep working.
  WordpressUlikeOnElementInserted("body", ".wpulike", queueInit);
})(window, document);
