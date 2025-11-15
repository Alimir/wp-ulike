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
    notifContainer: "wpulike-notification"
  };

  /**
   * Helper function to fade out an element
   */
  const fadeOut = (element, duration, callback) => {
    // Ensure element has initial opacity
    const computedStyle = window.getComputedStyle(element);
    if (!computedStyle.opacity || computedStyle.opacity === "") {
      element.style.opacity = "1";
    }
    // Set transition and fade out
    element.style.transition = `opacity ${duration}ms`;
    // Use requestAnimationFrame to ensure smooth transition
    requestAnimationFrame(() => {
      element.style.opacity = "0";
      setTimeout(() => {
        if (callback) {
          callback();
        }
      }, duration);
    });
  };

  /**
   * Helper function to dispatch custom events
   */
  const triggerEvent = (element, eventName, detail) => {
    const event = new CustomEvent(eventName, {
      bubbles: true,
      cancelable: true,
      detail: detail || {}
    });
    element.dispatchEvent(event);
  };

  // The actual plugin constructor
  function Plugin(element, options) {
    this.element = element;
    this.settings = Object.assign({}, defaults, options);
    this._defaults = defaults;
    this._name = pluginName;
    this.init();
  }

  // Plugin prototype methods
  Plugin.prototype = {
    init() {
      // Create Message Wrapper
      this._message();
      // Create Notification Container
      this._container();
      // Append Notification
      this._append();
      // Remove Notification
      this._remove();
    },

    /**
     * Create Message Wrapper
     */
    _message() {
      this.messageElement = document.createElement("div");
      this.messageElement.className =
        `${this.settings.messageElement} wpulike-${this.settings.messageType}`;
      this.messageElement.textContent = this.settings.messageText;
    },

    /**
     * Create notification container
     */
    _container() {
      // Make notification container if not exist
      const existingContainer = this.element.querySelector(
        `.${this.settings.notifContainer}`
      );
      if (!existingContainer) {
        this.notifContainer = document.createElement("div");
        this.notifContainer.className = this.settings.notifContainer;
        this.element.appendChild(this.notifContainer);
      } else {
        this.notifContainer = existingContainer;
      }
    },

    /**
     * Append notice
     */
    _append() {
      // Append Notification
      this.notifContainer.appendChild(this.messageElement);
      triggerEvent(this.notifContainer, "WordpressUlikeNotificationAppend");
    },

    /**
     * Disappear notice
     */
    _remove() {
      const self = this;
      const removeMessage = (messageEl) => {
        fadeOut(messageEl, 300, () => {
          messageEl.remove();
          const remainingMessages = self.element.querySelectorAll(
            `.${self.settings.messageElement}`
          );
          if (remainingMessages.length === 0) {
            self.notifContainer.remove();
          }
          triggerEvent(self.element, "WordpressUlikeRemoveNotification");
        });
      };

      // Remove Message On Click
      this.messageElement.addEventListener("click", function () {
        removeMessage(this);
      });

      // Remove Message With Timeout
      if (self.settings.timeout) {
        setTimeout(() => {
          removeMessage(self.messageElement);
        }, self.settings.timeout);
      }
    }
  };

  // Expose plugin to window for global access
  window[pluginName] = Plugin;
})(window, document);
