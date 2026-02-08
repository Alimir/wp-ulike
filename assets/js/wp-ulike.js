/*! WP ULike - v5.0.1
 *  https://wpulike.com
 *  TechnoWich 2026;
 */


/* ================== assets/js/src/tooltip.js =================== */


/**
 * WP ULike Tooltip Plugin
 * 
 * @fileoverview Lightweight tooltip solution with dynamic content loading
 * @requires ES7 (ES2016) compatible browser
 * @author WP ULike Team
 * @see https://github.com/alimir/wp-ulike
 */
(function (window, document, undefined) {
  "use strict";

  // Store tooltip instances
  const tooltipInstances = new WeakMap();
  const tooltipInstancesById = {}; // For easier access by ID
  const activeTooltips = [];

  // Default options
  const defaults = {
    id: Date.now(),
    title: "",
    trigger: "hover",
    position: "top",
    class: "",
    theme: "light",
    size: "small",
    singleton: true,
    close_on_outside_click: true,
  };

  // Constants
  const SPACING = 13;
  const SHOW_DELAY = 100;
  const HIDE_DELAY = 100;

  // Helper: Get element position (viewport-relative for fixed positioning)
  const getOffset = (element) => {
    const rect = element.getBoundingClientRect();
    return {
      top: rect.top,
      left: rect.left,
      width: rect.width,
      height: rect.height,
    };
  };

  // Helper: Create loading spinner HTML
  const createSpinnerHTML = () => {
    return '<div class="ulf-loading-spinner"><div class="ulf-spinner-circle"></div><div class="ulf-spinner-circle"></div><div class="ulf-spinner-circle"></div></div>';
  };

  // Helper: Create tooltip element
  const createTooltipElement = (content, className, isLoading) => {
    const tooltip = document.createElement("div");
    tooltip.className = `ulf-tooltip ${className || ""}`;
    tooltip.setAttribute("role", "tooltip");

    // Ensure content is never empty to prevent glitch
    const contentHTML = isLoading ? createSpinnerHTML() : (content || "&nbsp;");
    tooltip.innerHTML = `<div class="ulf-arrow"></div><div class="ulf-content">${contentHTML}</div>`;
    return tooltip;
  };

  // Helper: Position tooltip
  const positionTooltip = (tooltip, reference, placement) => {
    // Force a reflow to ensure tooltip has proper dimensions
    void tooltip.offsetHeight;

    const refRect = getOffset(reference);
    const tooltipRect = tooltip.getBoundingClientRect();
    const arrow = tooltip.querySelector(".ulf-arrow");
    const viewport = {
      width: window.innerWidth,
      height: window.innerHeight,
    };

    const positions = {
      top: {
        top: refRect.top - tooltipRect.height - SPACING,
        left: refRect.left + refRect.width / 2 - tooltipRect.width / 2,
        arrow: "bottom",
      },
      bottom: {
        top: refRect.top + refRect.height + SPACING,
        left: refRect.left + refRect.width / 2 - tooltipRect.width / 2,
        arrow: "top",
      },
      left: {
        top: refRect.top + refRect.height / 2 - tooltipRect.height / 2,
        left: refRect.left - tooltipRect.width - SPACING,
        arrow: "right",
      },
      right: {
        top: refRect.top + refRect.height / 2 - tooltipRect.height / 2,
        left: refRect.left + refRect.width + SPACING,
        arrow: "left",
      },
    };

    const pos = positions[placement] || positions.top;

    // Keep tooltip in viewport
    if (pos.left < 10) pos.left = 10;
    if (pos.left + tooltipRect.width > viewport.width - 10) {
      pos.left = viewport.width - tooltipRect.width - 10;
    }
    if (pos.top < 10) pos.top = 10;
    if (pos.top + tooltipRect.height > viewport.height - 10) {
      pos.top = viewport.height - tooltipRect.height - 10;
    }

    // Use fixed positioning (viewport-relative) for consistent positioning
    tooltip.style.position = "fixed";
    tooltip.style.left = `${pos.left}px`;
    tooltip.style.top = `${pos.top}px`;

    if (arrow) {
      arrow.className = `ulf-arrow ulf-arrow-${pos.arrow}`;
    }

    // Mark as positioned to show arrow
    tooltip.setAttribute("data-positioned", "true");
  };

  // Helper: Convert array-like to array
  const arrayFrom = Array.from || ((arr) => Array.prototype.slice.call(arr));

  // Main plugin
  function WordpressUlikeTooltipPlugin(element, options) {
    // Handle multiple elements
    if (element.length !== undefined && element.length > 1) {
      arrayFrom(element).forEach((el) => {
        new WordpressUlikeTooltipPlugin(el, options);
      });
      return element;
    }

    if (!element) return false;

    // Merge options
    options = Object.assign({}, defaults, options || {});

    // Get title from attribute or hidden content element
    if (!options.title) {
      // Check for hidden content element (for dynamic content like likers)
      const hiddenContent = element.querySelector('[data-tooltip-content]');
      if (hiddenContent) {
        const tooltipState = hiddenContent.getAttribute('data-tooltip-state');
        if (tooltipState === 'ready') {
          options.title = hiddenContent.innerHTML.trim();
        }
      }

      // Fallback to title attribute
      if (!options.title) {
        const titleAttr = element.getAttribute("title");
        if (titleAttr) {
          options.title = titleAttr;
          element.removeAttribute("title");
        }
      }
    }

    // Destroy existing
    const existing = tooltipInstances.get(element);
    if (existing) {
      existing.destroy();
    }

    let tooltip = null;
    let showTimeout = null;
    let hideTimeout = null;
    let isLoading = false;
    let scrollHandler = null;
    let scrollHandlerOptions = null;
    let outsideHandler = null; // Store for cleanup
    let isHovering = false; // Track if user is currently hovering

    const show = (showLoading) => {
      // If showing loading, always show even if tooltip exists
      if (tooltip && tooltip.parentNode && !showLoading) return;

      // Hide others if singleton
      if (options.singleton !== false) {
        activeTooltips.forEach((t) => {
          if (t && t.hide && t.element !== element) t.hide();
        });
      }

      // Create or update tooltip
      let className = `ulf-${options.theme || "light"}-theme ulf-${options.size || "small"}`;
      if (options.class) className += ` ${options.class}`;

      // Get reference element for positioning
      let reference = element;
      if (options.child) {
        const childEl = element.querySelector(options.child);
        if (childEl) reference = childEl;
      }

      if (!tooltip || !tooltip.parentNode || showLoading) {
        if (tooltip && tooltip.parentNode) {
          tooltip.remove();
        }
        isLoading = showLoading === true;
        tooltip = createTooltipElement(
          options.title || "",
          className,
          isLoading
        );
        document.body.appendChild(tooltip);
        // Position after a brief delay to ensure dimensions are calculated
        requestAnimationFrame(() => {
          if (tooltip && tooltip.parentNode) {
            positionTooltip(tooltip, reference, options.position || "top");
          }
        });
      } else {
        // Update existing tooltip content
        const contentEl = tooltip.querySelector(".ulf-content");
        if (contentEl) {
          isLoading = showLoading === true;
          contentEl.innerHTML = isLoading
            ? createSpinnerHTML()
            : (options.title || "&nbsp;");
        }
        // Reposition after content update (with delay for dimension calculation)
        requestAnimationFrame(() => {
          if (tooltip && tooltip.parentNode) {
            positionTooltip(tooltip, reference, options.position || "top");
          }
        });
      }

      // Add hover handlers to tooltip if trigger is hover
      if (options.trigger === "hover" || !options.trigger) {
        tooltip.addEventListener("mouseenter", () => {
          clearTimeout(hideTimeout);
        });
        tooltip.addEventListener("mouseleave", handleHide);
      }

      // Add scroll listener to hide tooltip on scroll (standard behavior)
      // This prevents tooltip from appearing to "move" when scrolling
      // Most tooltip libraries (Tippy.js, Popper.js) hide tooltips on scroll
      if (!scrollHandler) {
        scrollHandler = () => {
          if (tooltip && tooltip.parentNode) {
            hide();
          }
        };
        // Use capture phase and passive for better performance
        scrollHandlerOptions = { capture: true, passive: true };
        window.addEventListener("scroll", scrollHandler, scrollHandlerOptions);
      }

      // Add to active
      const isInActive = activeTooltips.some((t) => t.element === element);
      if (!isInActive) {
        activeTooltips.push({ element, hide });
      }

      // Set ID for accessibility
      const id = `ulp-dom-${options.id}`;
      tooltip.setAttribute("id", id);
      element.setAttribute("aria-describedby", id);

      // Trigger event
      const event = new CustomEvent("ulf-show", {
        bubbles: true,
        detail: { tooltip },
      });
      element.dispatchEvent(event);
    };

    // Helper: Get or create hidden content element
    const getTooltipContentElement = () => {
      let hiddenContent = element.querySelector('[data-tooltip-content]');
      if (!hiddenContent) {
        hiddenContent = document.createElement("div");
        hiddenContent.setAttribute('data-tooltip-content', '');
        hiddenContent.style.display = 'none';
        element.appendChild(hiddenContent);
      }
      return hiddenContent;
    };

    // Helper: Set tooltip state
    const setTooltipState = (state) => {
      const hiddenContent = getTooltipContentElement();
      hiddenContent.setAttribute('data-tooltip-state', state);
    };

    // Helper: Get tooltip state
    const getTooltipState = () => {
      const hiddenContent = element.querySelector('[data-tooltip-content]');
      return hiddenContent ? hiddenContent.getAttribute('data-tooltip-state') : null;
    };

    const updateContent = (content) => {
      // Update options.title to keep it in sync
      options.title = content || "";

      // Update hidden content element (for dynamic content)
      const hiddenContent = getTooltipContentElement();
      hiddenContent.innerHTML = content || "";

      // Set state: 'ready' if has content, 'empty' if no content
      const hasContent = content && content.trim().length > 0;
      setTooltipState(hasContent ? 'ready' : 'empty');

      // If content is empty, hide tooltip immediately (don't show empty tooltip)
      if (!hasContent) {
        if (tooltip && tooltip.parentNode) {
          hide();
        }
        return;
      }

      // If tooltip is visible, update it immediately
      if (tooltip && tooltip.parentNode) {
        const contentEl = tooltip.querySelector(".ulf-content");
        if (contentEl) {
          contentEl.innerHTML = content;
          isLoading = false;
          // Reposition after content update
          const reference = options.child ? (element.querySelector(options.child) || element) : element;
          requestAnimationFrame(() => {
            if (tooltip && tooltip.parentNode) {
              positionTooltip(tooltip, reference, options.position || "top");
            }
          });
        }
      } else if (isHovering) {
        // Tooltip not visible but user is hovering - show it
        show(false);
      }
    };

    const hide = () => {
      if (!tooltip || !tooltip.parentNode) return;

      // Remove scroll listener when hiding
      if (scrollHandler && scrollHandlerOptions) {
        window.removeEventListener("scroll", scrollHandler, scrollHandlerOptions);
        scrollHandler = null;
        scrollHandlerOptions = null;
      }

      tooltip.remove();
      tooltip = null;
      isLoading = false;

      // Remove from active
      const index = activeTooltips.findIndex((t) => t.element === element);
      if (index > -1) {
        activeTooltips.splice(index, 1);
      }

      // Remove aria
      element.removeAttribute("aria-describedby");

      // Trigger event
      const event = new CustomEvent("ulf-hide", { bubbles: true });
      element.dispatchEvent(event);
    };

    // Helper: Get cached content from hidden element
    const getCachedContent = () => {
      const hiddenContent = element.querySelector('[data-tooltip-content]');
      return hiddenContent ? hiddenContent.innerHTML.trim() : '';
    };

    // Event handlers
    const handleShow = () => {
      clearTimeout(hideTimeout);
      isHovering = true;

      const tooltipState = getTooltipState();

      // Handle different states
      if (tooltipState === 'empty') return; // Don't show empty tooltip

      if (tooltipState === 'ready') {
        const cachedContent = getCachedContent();
        if (cachedContent) {
          options.title = cachedContent;
          showTimeout = setTimeout(show, SHOW_DELAY);
        } else {
          setTooltipState('empty'); // Content disappeared - mark as empty
        }
        return;
      }

      if (tooltipState === 'loading') {
        show(true);
        return;
      }

      // Not initialized - request data
      if (!tooltipState || tooltipState === '') {
        if (instance.requestData && instance.requestData()) {
          show(true);
        } else {
          setTooltipState('loading');
          show(true);
        }
        return;
      }

      // Fallback for static content
      if (options.showLoadingImmediately) {
        show(true);
      } else {
        showTimeout = setTimeout(show, SHOW_DELAY);
      }
    };

    const handleHide = () => {
      clearTimeout(showTimeout);
      isHovering = false;
      hideTimeout = setTimeout(hide, HIDE_DELAY);
    };

    // Setup events based on trigger
    if (options.trigger === "hover" || !options.trigger) {
      element.addEventListener("mouseenter", handleShow);
      element.addEventListener("mouseleave", handleHide);
    } else if (options.trigger === "click") {
      element.addEventListener("click", (e) => {
        e.preventDefault();
        if (tooltip && tooltip.parentNode) {
          hide();
        } else {
          show();
        }
      });
    }

    // Click outside handler
    if (options.close_on_outside_click !== false) {
      outsideHandler = (e) => {
        if (
          tooltip &&
          tooltip.parentNode &&
          !tooltip.contains(e.target) &&
          !element.contains(e.target)
        ) {
          hide();
        }
      };
      document.addEventListener("mousedown", outsideHandler);
    }

    // Store instance
    const instance = {
      show,
      showLoading: () => show(true),
      updateContent,
      hide,
      destroy: () => {
        hide();
        element.removeEventListener("mouseenter", handleShow);
        element.removeEventListener("mouseleave", handleHide);
        if (instance.contentUpdateHandler) {
          element.removeEventListener("tooltip-content-updated", instance.contentUpdateHandler);
        }
        if (scrollHandler && scrollHandlerOptions) {
          window.removeEventListener("scroll", scrollHandler, scrollHandlerOptions);
          scrollHandler = null;
          scrollHandlerOptions = null;
        }
        if (outsideHandler) {
          document.removeEventListener("mousedown", outsideHandler);
          outsideHandler = null;
        }
        if (showTimeout) {
          clearTimeout(showTimeout);
          showTimeout = null;
        }
        if (hideTimeout) {
          clearTimeout(hideTimeout);
          hideTimeout = null;
        }
        tooltipInstances.delete(element);
        if (options.id) delete tooltipInstancesById[options.id];
      },
    };

    tooltipInstances.set(element, instance);
    if (options.id) {
      tooltipInstancesById[options.id] = instance;
    }

    // Expose helper methods for external use
    instance.setLoadingState = () => {
      setTooltipState('loading');
    };

    // If tooltip is created with hover trigger, check state immediately
    // This handles the case when tooltip is created while user is already hovering
    if (!options.trigger || options.trigger === "hover") {
      setTimeout(() => {
        const currentState = getTooltipState();
        if (!currentState || currentState === '') {
          handleShow(); // Will request data
        } else if (currentState === 'loading') {
          show(true);
        } else if (currentState === 'ready') {
          const cachedContent = getCachedContent();
          if (cachedContent) {
            options.title = cachedContent;
            showTimeout = setTimeout(show, SHOW_DELAY);
          }
        }
      }, 0);
    }

    // Request data from external source (e.g., AJAX)
    // This method checks state and triggers event or calls dataFetcher callback if provided
    instance.requestData = () => {
      const currentState = getTooltipState();

      // If already loaded (ready or empty), don't request again
      if (currentState === 'ready' || currentState === 'empty') {
        return false; // Data already available
      }

      // If already loading, don't request again
      if (currentState === 'loading') {
        return false; // Already requesting
      }

      // Set loading state
      setTooltipState('loading');

      // If dataFetcher callback is provided, use it directly
      if (typeof options.dataFetcher === 'function') {
        options.dataFetcher(element, options.id);
        return true;
      }

      // Fallback: trigger event for external handler (backward compatibility)
      setTimeout(() => {
        const event = new CustomEvent("tooltip-request-data", {
          bubbles: true,
          detail: { element, tooltipId: options.id }
        });
        element.dispatchEvent(event);
        document.dispatchEvent(event);
      }, 0);

      return true;
    };

    // Listen for content updates via custom event (optional, for external updates)
    const contentUpdateHandler = (e) => {
      const detail = e.detail || {};
      if (detail.element === element || (detail.target && element.contains(detail.target))) {
        updateContent(detail.content || "");
      }
    };
    element.addEventListener("tooltip-content-updated", contentUpdateHandler);
    instance.contentUpdateHandler = contentUpdateHandler;

    return element;
  }

  // Expose
  window.WordpressUlikeTooltipPlugin = WordpressUlikeTooltipPlugin;
  window.WordpressUlikeTooltip = {
    visible: activeTooltips,
    defaults,
    getInstanceById: (id) => tooltipInstancesById[id],
    getInstanceByElement: (element) => tooltipInstances.get(element),
  };

  // Expose as jQuery plugin for backward compatibility (if jQuery is available)
  // This allows users' existing jQuery code to continue working
  // Example: $('.element').WordpressUlikeTooltip({...})
  if (typeof jQuery !== 'undefined' && jQuery && jQuery.fn) {
    jQuery.fn.WordpressUlikeTooltip = function (options) {
      return this.each(function () {
        new WordpressUlikeTooltipPlugin(this, options);
      });
    };
  }
})(window, document);


/* ================== assets/js/src/notifications.js =================== */


/**
 * WP ULike Notifications Plugin
 * 
 * @fileoverview Toast notification system for user feedback
 * @requires ES7 (ES2016) compatible browser
 * @author WP ULike Team
 * @see https://github.com/alimir/wp-ulike
 */
(function (window, document, undefined) {
  "use strict";

  // Create the defaults once
  const pluginName = "WordpressUlikeNotifications";
  const defaults = {
    messageType: "success",
    messageText: "Hello World!",
    timeout: 8000,
    messageElement: "wpulike-message",
    notifContainer: "wpulike-notification",
    fadeOutClass: "wpulike-message-fadeout"
  };

  // Constants
  const FADE_OUT_DURATION = 300; // Match CSS transition duration

  // Cache container instances to avoid repeated DOM queries
  const containerCache = new WeakMap();

  /**
   * Helper function to dispatch custom events
   * Optimized: avoid creating empty objects
   */
  const triggerEvent = (element, eventName, detail) => {
    if (!element) return;
    const event = new CustomEvent(eventName, {
      bubbles: true,
      cancelable: true,
      detail: detail || null
    });
    element.dispatchEvent(event);
  };

  /**
   * Helper function to fade out an element using CSS class
   * No inline styles - all handled by CSS
   * Optimized: use requestAnimationFrame for better timing
   */
  const fadeOut = (element, callback, instance) => {
    if (!element) return;

    // Use requestAnimationFrame to sync with CSS transition
    requestAnimationFrame(() => {
      element.classList.add(defaults.fadeOutClass);

      // Remove element after transition completes
      const timeoutId = setTimeout(() => {
        if (instance) instance.fadeTimeoutId = null;
        if (callback) {
          callback();
        }
      }, FADE_OUT_DURATION);
      if (instance) instance.fadeTimeoutId = timeoutId;
    });
  };

  /**
   * Helper to get or create notification container
   * Optimized: cache container lookup using WeakMap
   */
  const getOrCreateContainer = (parentElement, containerClass) => {
    // Check cache first
    let container = containerCache.get(parentElement);
    if (container && container.parentNode) {
      return container;
    }

    // Query DOM only if not cached
    container = parentElement.querySelector(`.${containerClass}`);
    if (!container) {
      container = document.createElement("div");
      container.className = containerClass;
      parentElement.appendChild(container);
    }

    // Cache the container
    containerCache.set(parentElement, container);
    return container;
  };

  // The actual plugin constructor
  function Plugin(element, options) {
    if (!element) {
      console.warn("WordpressUlikeNotifications: element is required");
      return;
    }

    this.element = element;
    this.settings = Object.assign({}, defaults, options);
    this._defaults = defaults;
    this._name = pluginName;
    this.timeoutId = null;
    this.fadeTimeoutId = null; // Track fade timeout
    this.isRemoving = false;
    // Cache className to avoid template literal on each access
    this._messageClassName = null;

    this.init();
  }

  // Plugin prototype methods
  Plugin.prototype = {
    init() {
      // Create Message Wrapper
      this._createMessage();
      // Get or Create Notification Container
      this._getContainer();
      // Append Notification
      this._append();
      // Setup removal handlers
      this._setupRemoval();
    },

    /**
     * Create Message Wrapper
     * Optimized: cache className string
     */
    _createMessage() {
      this.messageElement = document.createElement("div");

      // Cache className to avoid template literal recreation
      if (!this._messageClassName) {
        this._messageClassName = `${this.settings.messageElement} wpulike-${this.settings.messageType}`;
      }
      this.messageElement.className = this._messageClassName;

      this.messageElement.textContent = this.settings.messageText;
      this.messageElement.setAttribute("role", "alert");
      this.messageElement.setAttribute("aria-live", "polite");
    },

    /**
     * Get or create notification container
     */
    _getContainer() {
      this.notifContainer = getOrCreateContainer(
        this.element,
        this.settings.notifContainer
      );
    },

    /**
     * Append notice to container
     * Optimized: batch DOM operations
     */
    _append() {
      if (!this.notifContainer || !this.messageElement) return;

      // Single DOM operation
      this.notifContainer.appendChild(this.messageElement);

      // Trigger event after DOM update
      requestAnimationFrame(() => {
        triggerEvent(this.notifContainer, "WordpressUlikeNotificationAppend", {
          messageElement: this.messageElement
        });
      });
    },

    /**
     * Setup removal handlers (click and timeout)
     * Optimized: use arrow function to avoid binding
     */
    _setupRemoval() {
      if (!this.messageElement) return;

      // Remove Message On Click - use arrow function for better performance
      this.messageElement.addEventListener("click", () => {
        this.remove();
      }, { once: true, passive: true }); // passive for better scroll performance

      // Remove Message With Timeout
      if (this.settings.timeout && this.settings.timeout > 0) {
        this.timeoutId = setTimeout(() => {
          this.remove();
        }, this.settings.timeout);
      }
    },

    /**
     * Remove message with fade out animation
     * Optimized to prevent multiple calls
     */
    remove() {
      if (this.isRemoving || !this.messageElement) return;
      this.isRemoving = true;

      // Clear timeouts if still pending
      if (this.timeoutId) {
        clearTimeout(this.timeoutId);
        this.timeoutId = null;
      }
      if (this.fadeTimeoutId) {
        clearTimeout(this.fadeTimeoutId);
        this.fadeTimeoutId = null;
      }

      // Remove message with fade out
      fadeOut(this.messageElement, () => {
        this._cleanup();
      }, this);
    },

    /**
     * Cleanup after removal
     * Optimized: batch DOM operations
     */
    _cleanup() {
      if (!this.messageElement) return;

      const messageEl = this.messageElement;
      const container = this.notifContainer;

      // Remove element from DOM
      if (messageEl.parentNode) {
        messageEl.remove();
      }

      // Check if container is empty and remove it
      if (container && container.children.length === 0) {
        if (container.parentNode) {
          container.remove();
          // Clear cache when container is removed
          containerCache.delete(this.element);
        }
      }

      // Trigger removal event
      triggerEvent(this.element, "WordpressUlikeRemoveNotification", {
        messageElement: messageEl
      });

      // Cleanup references
      this.messageElement = null;
      this.notifContainer = null;
      this.isRemoving = false;
      this._messageClassName = null;
    }
  };

  // Expose plugin to window for global access
  window[pluginName] = Plugin;

  // Expose as jQuery plugin for backward compatibility (if jQuery is available)
  // This allows users' existing jQuery code to continue working
  // Example: $(document.body).WordpressUlikeNotifications({...})
  if (typeof jQuery !== 'undefined' && jQuery && jQuery.fn) {
    jQuery.fn[pluginName] = function (options) {
      return this.each(function () {
        new Plugin(this, options);
      });
    };
  }
})(window, document);


/* ================== assets/js/src/ulike.js =================== */


/**
 * WP ULike - Main Plugin
 * 
 * @fileoverview Core like/unlike functionality with AJAX support
 * @requires ES7 (ES2016) compatible browser
 * @author WP ULike Team
 * @see https://github.com/alimir/wp-ulike
 */
(function (window, document, undefined) {
  "use strict";

  // Create the defaults once
  const pluginName = "WordpressUlike";
  const defaults = {
    ID: 0,
    nonce: 0,
    type: "",
    append: "",
    appendTimeout: 2000,
    displayLikers: false,
    likersTemplate: "default",
    disablePophover: true,
    isTotal: false,
    factor: "",
    template: "",
    counterSelector: ".count-box",
    generalSelector: ".wp_ulike_general_class",
    buttonSelector: ".wp_ulike_btn",
    likersSelector: ".wp_ulike_likers_wrapper",
  };
  const attributesMap = {
    "ulike-id": "ID",
    "ulike-nonce": "nonce",
    "ulike-type": "type",
    "ulike-append": "append",
    "ulike-is-total": "isTotal",
    "ulike-display-likers": "displayLikers",
    "ulike-likers-style": "likersTemplate",
    "ulike-disable-pophover": "disablePophover",
    "ulike-append-timeout": "appendTimeout",
    "ulike-factor": "factor",
    "ulike-template": "template",
  };

  // Helper function to get data attribute value
  const getDataAttribute = (element, name) => {
    const camelName = name.replace(/-([a-z])/g, (g) => g[1].toUpperCase());
    if (element.dataset && element.dataset[camelName] !== undefined) {
      return element.dataset[camelName];
    }
    const value = element.getAttribute(`data-${name}`);
    if (value === null) {
      return undefined;
    }
    if (value === "true") return true;
    if (value === "false") return false;
    if (value === "" || value === "null") return null;
    if (!isNaN(value) && value !== "") return Number(value);
    return value;
  };

  // Helper function to trigger custom events (works with both jQuery and vanilla JS)
  const triggerEvent = (element, eventName, data) => {
    const event = new CustomEvent(eventName, {
      bubbles: true,
      cancelable: true,
      detail: data
    });
    element.dispatchEvent(event);

    if (typeof jQuery !== 'undefined' && jQuery && jQuery.fn && jQuery.fn.on) {
      const $element = jQuery(element);
      $element.trigger(eventName, data);
    }
  };

  // Safe Array.from polyfill for older browsers
  const arrayFrom = (arrayLike) => {
    if (Array.from) {
      return Array.from(arrayLike);
    }
    return Array.prototype.slice.call(arrayLike);
  };

  // Helper function to handle multiple elements (like jQuery collection)
  const forEachElement = (elements, callback) => {
    if (!elements) return;
    if (elements.length === undefined) {
      callback(elements, 0);
    } else {
      arrayFrom(elements).forEach(callback);
    }
  };

  // Helper to get siblings (like jQuery .siblings())
  const getSiblings = (element, selector) => {
    const siblings = [];
    const parent = element.parentNode;
    if (!parent) return siblings;
    const children = parent.children;
    for (let i = 0; i < children.length; i++) {
      if (children[i] !== element) {
        if (!selector || children[i].matches(selector)) {
          siblings.push(children[i]);
        }
      }
    }
    return siblings;
  };

  // Helper to get all siblings from multiple elements (like jQuery collection.siblings())
  const getAllSiblings = (elements, selector) => {
    const allSiblings = [];
    const seen = new Set();
    forEachElement(elements, (el) => {
      const siblings = getSiblings(el, selector);
      siblings.forEach((sibling) => {
        if (!seen.has(sibling)) {
          seen.add(sibling);
          allSiblings.push(sibling);
        }
      });
    });
    return allSiblings;
  };

  // Helper to get single element from array/NodeList
  const getSingleElement = (elements) => {
    return Array.isArray(elements) || elements.length !== undefined
      ? elements[0]
      : elements;
  };

  // Helper to normalize boolean values in settings
  const normalizeBooleanValues = (settings, defaults) => {
    for (const key in defaults) {
      if (typeof defaults[key] === 'boolean' && settings[key] != null) {
        settings[key] = settings[key] != 0 && settings[key] !== "0" && settings[key] !== false;
      }
    }
  };

  // The actual plugin constructor
  function Plugin(element, options) {
    this.element = element;
    this.settings = Object.assign({}, defaults, options);
    // Normalize boolean values automatically
    normalizeBooleanValues(this.settings, defaults);
    this._defaults = defaults;
    this._name = pluginName;
    // Store handlers and timeouts for cleanup
    this._boundHandlers = [];
    this._timeouts = [];
    // Initialize fetching flag
    this._isFetchingLikers = false;

    // Create main selectors (like jQuery .find())
    this.buttonElement = this.element.querySelectorAll(this.settings.buttonSelector);

    // read attributes from first button
    const firstButton = this.buttonElement.length > 0 ? this.buttonElement[0] : null;
    if (firstButton) {
      for (const attrName in attributesMap) {
        if (attributesMap.hasOwnProperty(attrName)) {
          const value = getDataAttribute(firstButton, attrName);
          if (value !== undefined) {
            this.settings[attributesMap[attrName]] = value;
          }
        }
      }
      // Normalize boolean values after reading attributes
      normalizeBooleanValues(this.settings, defaults);
    }

    // General element (like jQuery .find())
    this.generalElement = this.element.querySelectorAll(this.settings.generalSelector);

    // Create counter element (like jQuery .find() on collection)
    this.counterElement = [];
    if (this.generalElement.length > 0) {
      forEachElement(this.generalElement, (generalEl) => {
        const counters = generalEl.querySelectorAll(this.settings.counterSelector);
        forEachElement(counters, (counter) => {
          this.counterElement.push(counter);
        });
      });
    }

    // Append dom counter element
    if (this.counterElement.length > 0) {
      forEachElement(this.counterElement, (element) => {
        const counterValue = getDataAttribute(element, "ulike-counter-value");
        if (counterValue !== undefined) {
          element.innerHTML = counterValue;
        }
      });
    }
    // Get likers box container element
    this.likersElement = this.element.querySelector(this.settings.likersSelector);

    this.init();
  }

  // Plugin prototype methods
  Plugin.prototype = {
    init() {
      // Attach click listeners to ALL buttons
      if (this.buttonElement && this.buttonElement.length > 0) {
        const boundHandler = this._initLike.bind(this);
        this._boundHandlers.push({ element: this.buttonElement, event: 'click', handler: boundHandler });
        forEachElement(this.buttonElement, (button) => {
          if (button) {
            button.addEventListener("click", boundHandler);
          }
        });
      }
      // Call likers box generator (one-time event)
      const firstGeneralEl = this.generalElement.length > 0 ? this.generalElement[0] : null;
      if (firstGeneralEl) {
        const mouseenterHandler = (event) => {
          this._updateLikers(event);
          firstGeneralEl.removeEventListener("mouseenter", mouseenterHandler);
          // Remove from tracking since it removes itself
          const index = this._boundHandlers.findIndex(h => h.handler === mouseenterHandler);
          if (index > -1) this._boundHandlers.splice(index, 1);
        };
        this._boundHandlers.push({ element: firstGeneralEl, event: 'mouseenter', handler: mouseenterHandler });
        firstGeneralEl.addEventListener("mouseenter", mouseenterHandler);
      }
    },

    /**
     * global AJAX callback
     */
    _ajax(args, callback) {
      const formData = new FormData();
      for (const key in args) {
        if (args.hasOwnProperty(key)) {
          formData.append(key, args[key]);
        }
      }

      fetch(wp_ulike_params.ajax_url, {
        method: "POST",
        body: formData,
      })
        .then((response) => response.json())
        .then(callback)
        .catch((error) => {
          console.error("WP Ulike AJAX error:", error);
        });
    },

    /**
     * init ulike core process
     */
    _initLike(event) {
      event.stopPropagation();
      // Update element if there's more than one button
      this._maybeUpdateElements(event);
      // Check for same buttons elements
      this._updateSameButtons();
      // Check for same likers elements
      this._updateSameLikers();
      // Disable button
      if (this.buttonElement) {
        forEachElement(this.buttonElement, (btn) => {
          btn.disabled = true;
        });
      }
      // Manipulations
      triggerEvent(document, "WordpressUlikeLoading", this.element);
      // Add progress class
      if (this.generalElement) {
        forEachElement(this.generalElement, (el) => {
          el.classList.add("wp_ulike_is_loading");
        });
      }
      // Start AJAX process
      this._ajax(
        {
          action: "wp_ulike_process",
          id: this.settings.ID,
          nonce: this.settings.nonce,
          factor: this.settings.factor,
          type: this.settings.type,
          template: this.settings.template,
          displayLikers: this.settings.displayLikers,
          likersTemplate: this.settings.likersTemplate,
        },
        (response) => {
          //remove progress class
          if (this.generalElement) {
            forEachElement(this.generalElement, (el) => {
              el.classList.remove("wp_ulike_is_loading");
            });
          }
          // Make changes
          if (response.success) {
            this._updateMarkup(response);
            // Append html data
            this._appendChild();
          } else if (response.data && response.data.hasToast) {
            this._sendNotification("error", response.data.message);
          }
          // Re-enable button
          if (this.buttonElement) {
            forEachElement(this.buttonElement, (btn) => {
              btn.disabled = false;
            });
          }
          // Add new trigger when process finished
          triggerEvent(document, "WordpressUlikeUpdated", this.element);
        }
      );
    },

    _maybeUpdateElements(event) {
      this.buttonElement = event.currentTarget;
      this.generalElement = this.buttonElement.closest(this.settings.generalSelector);
      if (this.generalElement) {
        this.counterElement = this.generalElement.querySelectorAll(this.settings.counterSelector);
      } else {
        this.counterElement = [];
      }
      this.settings.factor = getDataAttribute(this.buttonElement, "ulike-factor");
    },

    /**
     * append child
     */
    _appendChild() {
      if (this.settings.append !== "" && this.buttonElement) {
        let sourceElements = [];

        // Check if append is HTML content (starts with <) or a CSS selector
        if (this.settings.append.trim().startsWith('<')) {
          // Parse HTML content
          const tempDiv = document.createElement("div");
          tempDiv.innerHTML = this.settings.append;
          // Collect all children by removing them from tempDiv
          while (tempDiv.firstChild) {
            sourceElements.push(tempDiv.removeChild(tempDiv.firstChild));
          }
        } else {
          // Try to use as CSS selector
          const appendedElement = document.querySelector(this.settings.append);
          if (appendedElement) {
            sourceElements.push(appendedElement);
          }
        }

        if (sourceElements.length > 0) {
          const appendedElements = [];
          forEachElement(this.buttonElement, (button) => {
            if (button) {
              sourceElements.forEach((sourceElement) => {
                const clonedElement = sourceElement.cloneNode(true);
                button.appendChild(clonedElement);
                appendedElements.push(clonedElement);
              });
            }
          });

          if (this.settings.appendTimeout && appendedElements.length > 0) {
            const timeoutId = setTimeout(() => {
              appendedElements.forEach((el) => {
                if (el && el.parentNode) {
                  el.remove();
                }
              });
            }, this.settings.appendTimeout);
            this._timeouts.push(timeoutId);
          }
        }
      }
    },

    /**
     * update button markup and calling some actions
     */
    _updateMarkup(response) {
      // Set sibling general elements
      this._setSbilingElement();
      // Set sibling button elements
      this._setSbilingButtons();
      // Update general element class names
      this._updateGeneralClassNames(response.data.status);
      // If data exist
      if (response.data.data !== null) {
        // Update counter + check refresh likers box
        if (response.data.status !== 5) {
          this.__updateCounter(response.data.data);
          // Refresh likers box on data update
          if (
            this.settings.displayLikers &&
            typeof response.data.likers !== "undefined"
          ) {
            this._updateLikersMarkup(response.data.likers);
          }
        }
        // Update button status
        this._updateButton(response.data.btnText, response.data.status);
      }
      // Display Notifications
      if (response.data.hasToast) {
        this._sendNotification(
          response.data.messageType,
          response.data.message
        );
      }
    },

    _updateGeneralClassNames(status) {
      const classNameObj = {
        start: "wp_ulike_is_not_liked",
        active: "wp_ulike_is_liked",
        deactive: "wp_ulike_is_unliked",
        disable: "wp_ulike_click_is_disabled",
      };

      // Remove status from sibling element
      if (this.siblingElement && this.siblingElement.length) {
        forEachElement(this.siblingElement, (el) => {
          el.classList.remove(classNameObj.active, classNameObj.deactive);
        });
      }

      // Update general element(s)
      forEachElement(this.generalElement, (generalEl) => {
        if (!generalEl) return;

        switch (status) {
          case 1:
            generalEl.classList.add(classNameObj.active);
            generalEl.classList.remove(classNameObj.start);
            const firstChild = generalEl.firstElementChild;
            if (firstChild) {
              firstChild.classList.add(classNameObj.disable);
            }
            break;

          case 2:
            generalEl.classList.add(classNameObj.deactive);
            generalEl.classList.remove(classNameObj.active);
            break;

          case 3:
            generalEl.classList.add(classNameObj.active);
            generalEl.classList.remove(classNameObj.deactive);
            break;

          case 0:
          case 5:
            generalEl.classList.add(classNameObj.disable);
            break;
        }
      });

      // Handle sibling disable for case 0 and 5
      if ((status === 0 || status === 5) && this.siblingElement && this.siblingElement.length) {
        forEachElement(this.siblingElement, (el) => {
          el.classList.add(classNameObj.disable);
        });
      }
    },

    _arrayToString(data) {
      return data.join(" ");
    },

    _setSbilingElement() {
      // Like jQuery: this.generalElement.siblings()
      // When generalElement is a collection, get siblings of ALL elements
      if (this.generalElement.length !== undefined && this.generalElement.length > 1) {
        this.siblingElement = getAllSiblings(this.generalElement);
      } else {
        const singleEl = getSingleElement(this.generalElement);
        this.siblingElement = singleEl ? getSiblings(singleEl) : [];
      }
    },

    _setSbilingButtons() {
      // Like jQuery: this.buttonElement.siblings(selector)
      // When buttonElement is a collection, get siblings of ALL elements
      if (this.buttonElement.length !== undefined && this.buttonElement.length > 1) {
        this.siblingButton = getAllSiblings(this.buttonElement, this.settings.buttonSelector);
      } else {
        const singleEl = getSingleElement(this.buttonElement);
        this.siblingButton = singleEl ? getSiblings(singleEl, this.settings.buttonSelector) : [];
      }
    },

    /**
     * Simple counter update (no up/down or isTotal handling)
     */
    __updateCounter(counterValue) {
      // Update counter element
      forEachElement(this.counterElement, (element) => {
        element.setAttribute("data-ulike-counter-value", counterValue);
        element.innerHTML = counterValue;
      });

      const buttonEl = getSingleElement(this.buttonElement);
      triggerEvent(document, "WordpressUlikeCounterUpdated", [buttonEl]);
    },

    /**
     * Fetch likers data via AJAX
     * Prevents duplicate requests
     */
    _fetchLikersData() {
      if (!this.settings.displayLikers) {
        this._isFetchingLikers = false;
        return;
      }

      // Prevent duplicate requests
      if (this._isFetchingLikers) {
        return;
      }

      this._isFetchingLikers = true;

      const generalEl = getSingleElement(this.generalElement);
      if (generalEl) {
        generalEl.classList.add("wp_ulike_is_getting_likers_list");
      }

      this._ajax(
        {
          action: "wp_ulike_get_likers",
          id: this.settings.ID,
          nonce: this.settings.nonce,
          type: this.settings.type,
          displayLikers: this.settings.displayLikers,
          likersTemplate: this.settings.likersTemplate,
        },
        (response) => {
          if (generalEl) {
            generalEl.classList.remove("wp_ulike_is_getting_likers_list");
          }
          this._isFetchingLikers = false;
          if (response.success) {
            this._updateLikersMarkup(response.data);
          } else {
            this._updateLikersMarkup("");
          }
        }
      );
    },

    /**
     * Get all sibling wrapper elements that should have tooltips
     */
    _getAllTooltipElements() {
      const factorMethod =
        typeof this.settings.factor !== "undefined" && this.settings.factor
          ? `_${this.settings.factor}`
          : "";
      const buttonSelector = `.wp_${this.settings.type.toLowerCase()}${factorMethod}_btn_${this.settings.ID}`;
      const allSameButtons = document.querySelectorAll(buttonSelector);

      const wrapperElements = [];
      forEachElement(allSameButtons, (btn) => {
        const wrapper = btn.closest('.wpulike');
        if (wrapper && !wrapperElements.includes(wrapper)) {
          wrapperElements.push(wrapper);
        }
      });

      return wrapperElements.length > 0 ? wrapperElements : [this.element];
    },

    /**
     * init & update likers box
     */
    _updateLikers(event) {
      if (this.settings.displayLikers) {
        // return on these conditions
        if (
          this.settings.likersTemplate === "popover" &&
          getDataAttribute(this.element, "ulike-tooltip")
        ) {
          return;
        } else if (
          this.settings.likersTemplate === "default" &&
          this.likersElement &&
          (this.likersElement.length === undefined || this.likersElement.length > 0)
        ) {
          return;
        }

        // Handle popover tooltips
        if (this.settings.likersTemplate === "popover") {
          if (typeof WordpressUlikeTooltipPlugin !== "undefined") {
            const tooltipId = `${this.settings.type.toLowerCase()}-${this.settings.ID}`;

            // Create tooltip only for current element (not all siblings) to ensure correct hover behavior
            const currentInstance = window.WordpressUlikeTooltip && window.WordpressUlikeTooltip.getInstanceByElement
              ? window.WordpressUlikeTooltip.getInstanceByElement(this.element)
              : null;

            if (!currentInstance) {
              new WordpressUlikeTooltipPlugin(this.element, {
                id: tooltipId,
                position: "top",
                child: this.settings.generalSelector,
                theme: "white",
                size: "tiny",
                trigger: "hover",
                dataFetcher: (element, tooltipId) => {
                  // Don't set flag here - let _fetchLikersData handle it
                  // This prevents the flag from blocking the AJAX request
                  this._fetchLikersData();
                }
              });
            }
          }
        } else {
          // For default template, fetch data directly
          this._fetchLikersData();
        }

        if (event) {
          event.stopImmediatePropagation();
        }
        return false;
      }
    },

    /**
     * Update likers markup
     */
    _updateLikersMarkup(data) {
      if (this.settings.likersTemplate === "popover") {
        this.likersElement = this.element;
        const tooltipId = `${this.settings.type.toLowerCase()}-${this.settings.ID}`;

        const template = data && typeof data === 'object' ? data.template : data;
        const templateContent = template || "";

        const allTooltipElements = this._getAllTooltipElements();

        // Update content for all siblings (existing instances and pre-populate for future instances)
        forEachElement(allTooltipElements, (wrapperEl) => {
          // Update existing tooltip instances via events
          const updateEvent = new CustomEvent("tooltip-content-updated", {
            bubbles: true,
            detail: {
              element: wrapperEl,
              content: templateContent
            }
          });
          wrapperEl.dispatchEvent(updateEvent);
          document.dispatchEvent(updateEvent);

          // Pre-populate content for siblings that don't have tooltip instances yet
          // This ensures when they're hovered, content is already available
          let hiddenContent = wrapperEl.querySelector('[data-tooltip-content]');
          if (!hiddenContent) {
            hiddenContent = document.createElement("div");
            hiddenContent.setAttribute('data-tooltip-content', '');
            hiddenContent.setAttribute('data-tooltip-state', 'ready');
            hiddenContent.style.display = 'none';
            wrapperEl.appendChild(hiddenContent);
          }
          hiddenContent.innerHTML = templateContent;
          hiddenContent.setAttribute('data-tooltip-state', 'ready');
        });
      } else {
        // Handle both single element and NodeList/array (from _updateSameLikers)
        const hasLikersElement = this.likersElement &&
          (this.likersElement.length === undefined
            ? true
            : this.likersElement.length > 0);

        if (!hasLikersElement && data && data.template) {
          // If the likers container doesn't exist, create it
          const tempDiv = document.createElement("div");
          tempDiv.innerHTML = data.template;
          const newElement = tempDiv.firstElementChild;
          if (newElement) {
            this.element.appendChild(newElement);
            this.likersElement = newElement;
          }
        }

        // Update all likers elements (handles both single element and NodeList)
        if (this.likersElement) {
          const elementsToUpdate = this.likersElement.length !== undefined
            ? arrayFrom(this.likersElement)
            : [this.likersElement];

          // Handle data as object with template property, or as string/empty
          const template = (data && typeof data === 'object' && data.template)
            ? data.template
            : (typeof data === 'string' ? data : '');

          forEachElement(elementsToUpdate, (likersEl) => {
            if (!likersEl) return;
            if (template) {
              likersEl.style.display = "";
              likersEl.innerHTML = template;
            } else {
              likersEl.style.display = "none";
              likersEl.innerHTML = "";
            }
          });
        }
      }

      const template = data && typeof data === 'object' ? data.template : data;
      triggerEvent(document, "WordpressUlikeLikersMarkupUpdated", [
        this.likersElement,
        this.settings.likersTemplate,
        template
      ]);
    },

    /**
     * Update the elements of same buttons at the same time
     */
    _updateSameButtons() {
      // Get buttons with same unique class names
      const factorMethod =
        typeof this.settings.factor !== "undefined" && this.settings.factor
          ? `_${this.settings.factor}`
          : "";
      const selector = `.wp_${this.settings.type.toLowerCase()}${factorMethod}_btn_${this.settings.ID}`;
      this.sameButtons = document.querySelectorAll(selector);
      // Update general elements (only when there are multiple same buttons)
      if (this.sameButtons.length > 1) {
        this.buttonElement = this.sameButtons;
        // Get general elements for all buttons (like jQuery .closest() on collection)
        const generalElements = [];
        forEachElement(this.sameButtons, (btn) => {
          const genEl = btn.closest(this.settings.generalSelector);
          if (genEl) {
            generalElements.push(genEl);
          }
        });
        this.generalElement = generalElements.length === 1 ? generalElements[0] : generalElements;
        // Get counter elements from all general elements (like jQuery .find() on collection)
        const counterElements = [];
        forEachElement(generalElements, (genEl) => {
          const counters = genEl.querySelectorAll(this.settings.counterSelector);
          forEachElement(counters, (counter) => {
            counterElements.push(counter);
          });
        });
        this.counterElement = counterElements;
      }
    },

    /**
     * Update the elements of same likers at the same time
     */
    _updateSameLikers() {
      const selector = `.wp_${this.settings.type.toLowerCase()}_likers_${this.settings.ID}`;
      this.sameLikers = document.querySelectorAll(selector);
      // Update general elements
      if (this.sameLikers.length > 1) {
        this.likersElement = this.sameLikers;
      }
    },

    /**
     * Get likers wrapper element
     */
    _getLikersElement() {
      return this.likersElement;
    },

    /**
     * Control actions (simple version - no up/down factor handling)
     */
    _updateButton(btnText, status) {
      forEachElement(this.buttonElement, (buttonEl) => {
        if (!buttonEl) return;

        if (buttonEl.classList.contains("wp_ulike_put_image")) {
          if (status === 4) {
            buttonEl.classList.add("image-unlike", "wp_ulike_btn_is_active");
          } else {
            buttonEl.classList.toggle("image-unlike");
            buttonEl.classList.toggle("wp_ulike_btn_is_active");
          }
        } else if (
          buttonEl.classList.contains("wp_ulike_put_text") &&
          btnText !== null
        ) {
          const span = buttonEl.querySelector("span");
          if (span) {
            span.innerHTML = btnText;
          }
        }
      });

      // Update sibling buttons (remove active state from siblings)
      if (this.siblingElement && this.siblingElement.length) {
        forEachElement(this.siblingElement, (sibling) => {
          const siblingBtn = sibling.querySelector(this.settings.buttonSelector);
          if (siblingBtn) {
            siblingBtn.classList.remove("image-unlike", "wp_ulike_btn_is_active");
          }
        });
      }
      if (this.siblingButton && this.siblingButton.length) {
        forEachElement(this.siblingButton, (siblingBtn) => {
          siblingBtn.classList.remove("image-unlike", "wp_ulike_btn_is_active");
        });
      }
    },

    /**
     * Send notification by 'WordpressUlikeNotifications' plugin
     */
    _sendNotification(messageType, messageText) {
      if (typeof WordpressUlikeNotifications !== "undefined") {
        new WordpressUlikeNotifications(document.body, {
          messageType,
          messageText,
        });
      }
    },

    /**
     * Cleanup method to prevent memory leaks
     */
    destroy() {
      // Remove all event listeners
      this._boundHandlers.forEach(({ element, event, handler }) => {
        if (element && element.length !== undefined) {
          forEachElement(element, (el) => {
            if (el) el.removeEventListener(event, handler);
          });
        } else if (element) {
          element.removeEventListener(event, handler);
        }
      });
      this._boundHandlers = [];

      // Clear all timeouts
      this._timeouts.forEach((timeoutId) => {
        clearTimeout(timeoutId);
      });
      this._timeouts = [];

      // Reset flags
      this._isFetchingLikers = false;
    },
  };

  // Expose plugin to window for global access
  window[pluginName] = Plugin;

  // Expose as jQuery plugin for backward compatibility
  if (typeof jQuery !== 'undefined' && jQuery && jQuery.fn) {
    jQuery.fn[pluginName] = function (options) {
      return this.each(function () {
        if (!this.hasAttribute || !this.hasAttribute("data-ulike-initialized")) {
          new Plugin(this, options);
          if (this.setAttribute) {
            this.setAttribute("data-ulike-initialized", "true");
          }
        }
      });
    };
  }
})(window, document);


/* ================== assets/js/src/scripts.js =================== */


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

  // Safe Array.from polyfill for older browsers (if needed)
  const arrayFrom = (arrayLike) => {
    if (Array.from) {
      return Array.from(arrayLike);
    }
    // Fallback for very old browsers
    return Array.prototype.slice.call(arrayLike);
  };

  // Helper function to initialize ulike on elements
  const initUlike = (elements) => {
    if (!elements) return;

    // Handle single element or NodeList
    const elementArray = elements.length !== undefined
      ? arrayFrom(elements)
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
              arrayFrom(elements).forEach((el) => callback(el));
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