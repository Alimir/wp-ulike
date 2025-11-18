/* 'WordpressUlikeNotifications' plugin : https://github.com/alimir/wp-ulike */
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
  const fadeOut = (element, callback) => {
    if (!element) return;

    // Use requestAnimationFrame to sync with CSS transition
    requestAnimationFrame(() => {
      element.classList.add(defaults.fadeOutClass);

      // Remove element after transition completes
      setTimeout(() => {
        if (callback) {
          callback();
        }
      }, FADE_OUT_DURATION);
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

      // Clear timeout if still pending
      if (this.timeoutId) {
        clearTimeout(this.timeoutId);
        this.timeoutId = null;
      }

      // Remove message with fade out
      fadeOut(this.messageElement, () => {
        this._cleanup();
      });
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
