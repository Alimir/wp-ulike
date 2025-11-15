/* 'WordpressUlikeNotifications' plugin : https://github.com/alimir/wp-ulike */
(function (window, document, undefined) {
  "use strict";

  // Create the defaults once
  var pluginName = "WordpressUlikeNotifications",
    defaults = {
      messageType: "success",
      messageText: "Hello World!",
      timeout: 8000,
      messageElement: "wpulike-message",
      notifContainer: "wpulike-notification"
    };

  /**
   * Helper function to fade out an element
   */
  function fadeOut(element, duration, callback) {
    // Ensure element has initial opacity
    if (window.getComputedStyle(element).opacity === "") {
      element.style.opacity = "1";
    }
    // Set transition and fade out
    element.style.transition = "opacity " + duration + "ms";
    // Use requestAnimationFrame to ensure smooth transition
    requestAnimationFrame(function () {
      element.style.opacity = "0";
      setTimeout(function () {
        if (callback) {
          callback();
        }
      }, duration);
    });
  }

  /**
   * Helper function to dispatch custom events
   */
  function triggerEvent(element, eventName, detail) {
    var event = new CustomEvent(eventName, {
      bubbles: true,
      cancelable: true,
      detail: detail || {}
    });
    element.dispatchEvent(event);
  }

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
    init: function () {
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
    _message: function () {
      this.messageElement = document.createElement("div");
      this.messageElement.className =
        this.settings.messageElement + " wpulike-" + this.settings.messageType;
      this.messageElement.textContent = this.settings.messageText;
    },

    /**
     * Create notification container
     */
    _container: function () {
      // Make notification container if not exist
      var existingContainer = this.element.querySelector(
        "." + this.settings.notifContainer
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
    _append: function () {
      // Append Notification
      this.notifContainer.appendChild(this.messageElement);
      triggerEvent(this.notifContainer, "WordpressUlikeNotificationAppend");
    },

    /**
     * Disappear notice
     */
    _remove: function () {
      var self = this;
      // Remove Message On Click
      this.messageElement.addEventListener("click", function () {
        fadeOut(this, 300, function () {
          this.remove();
          var remainingMessages = self.element.querySelectorAll(
            "." + self.settings.messageElement
          );
          if (remainingMessages.length === 0) {
            self.notifContainer.remove();
          }
          triggerEvent(self.element, "WordpressUlikeRemoveNotification");
        }.bind(this));
      });

      // Remove Message With Timeout
      if (self.settings.timeout) {
        setTimeout(function () {
          fadeOut(self.messageElement, 300, function () {
            self.messageElement.remove();
            var remainingMessages = self.element.querySelectorAll(
              "." + self.settings.messageElement
            );
            if (remainingMessages.length === 0) {
              self.notifContainer.remove();
            }
            triggerEvent(self.element, "WordpressUlikeRemoveNotification");
          });
        }, self.settings.timeout);
      }
    }
  };

  // Expose plugin to window for global access
  window[pluginName] = Plugin;

  // jQuery compatibility layer (if jQuery is available)
  if (typeof jQuery !== "undefined" && jQuery.fn) {
    jQuery.fn[pluginName] = function (options) {
      return this.each(function () {
        new Plugin(this, options);
      });
    };
  }
})(window, document);
