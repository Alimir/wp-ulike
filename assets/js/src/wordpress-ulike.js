(function (window, document, undefined) {
  "use strict";

  // Create the defaults once
  var pluginName = "WordpressUlike",
    defaults = {
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
    },
    attributesMap = {
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
  function getDataAttribute(element, name) {
    // Try dataset first (converts kebab-case to camelCase)
    var camelName = name.replace(/-([a-z])/g, function (g) {
      return g[1].toUpperCase();
    });
    if (element.dataset && element.dataset[camelName] !== undefined) {
      return element.dataset[camelName];
    }
    // Fallback to getAttribute
    var value = element.getAttribute("data-" + name);
    if (value === null) {
      return undefined;
    }
    // Try to parse as boolean or number
    if (value === "true") return true;
    if (value === "false") return false;
    if (value === "" || value === "null") return null;
    if (!isNaN(value) && value !== "") return Number(value);
    return value;
  }

  // Helper function to trigger custom events
  function triggerEvent(element, eventName, detail) {
    var event = new CustomEvent(eventName, {
      bubbles: true,
      cancelable: true,
      detail: detail || {}
    });
    element.dispatchEvent(event);
  }

  // Helper function to handle multiple elements
  function forEachElement(elements, callback) {
    if (!elements) return;
    if (elements.length === undefined) {
      // Single element
      callback(elements, 0);
    } else {
      // NodeList or Array
      Array.prototype.forEach.call(elements, callback);
    }
  }

  // Helper function to get siblings
  function getSiblings(element, selector) {
    var siblings = [];
    var parent = element.parentNode;
    if (!parent) return siblings;
    var children = parent.children;
    for (var i = 0; i < children.length; i++) {
      if (children[i] !== element) {
        if (!selector || children[i].matches(selector)) {
          siblings.push(children[i]);
        }
      }
    }
    return siblings;
  }

  // The actual plugin constructor
  function Plugin(element, options) {
    this.element = element;
    this.settings = Object.assign({}, defaults, options);
    this._defaults = defaults;
    this._name = pluginName;

    // Create main selectors
    this.buttonElement = this.element.querySelector(this.settings.buttonSelector);

    // read attributes
    if (this.buttonElement) {
      for (var attrName in attributesMap) {
        var value = getDataAttribute(this.buttonElement, attrName);
        if (value !== undefined) {
          this.settings[attributesMap[attrName]] = value;
        }
      }
    }

    // General element
    this.generalElement = this.element.querySelector(this.settings.generalSelector);

    // Create counter element
    if (this.generalElement) {
      this.counterElement = this.generalElement.querySelectorAll(
        this.settings.counterSelector
      );
    } else {
      this.counterElement = [];
    }

    // Append dom counter element
    if (this.counterElement.length) {
      var self = this;
      forEachElement(this.counterElement, function (element) {
        var counterValue = getDataAttribute(element, "ulike-counter-value");
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
    init: function () {
      // Call _ajaxify function on click button
      if (this.buttonElement) {
        this.buttonElement.addEventListener("click", this._initLike.bind(this));
      }
      // Call likers box generator (one-time event)
      if (this.generalElement) {
        var self = this;
        var mouseenterHandler = function (event) {
          self._updateLikers(event);
          self.generalElement.removeEventListener("mouseenter", mouseenterHandler);
        };
        this.generalElement.addEventListener("mouseenter", mouseenterHandler);
      }
    },

    /**
     * global AJAX callback
     */
    _ajax: function (args, callback) {
      // Do Ajax & update default value
      var formData = new FormData();
      for (var key in args) {
        if (args.hasOwnProperty(key)) {
          formData.append(key, args[key]);
        }
      }

      fetch(wp_ulike_params.ajax_url, {
        method: "POST",
        body: formData,
      })
        .then(function (response) {
          return response.json();
        })
        .then(callback)
        .catch(function (error) {
          console.error("WP Ulike AJAX error:", error);
        });
    },

    /**
     * init ulike core process
     */
    _initLike: function (event) {
      // Prevents further propagation of the current event in the capturing and bubbling phases
      event.stopPropagation();
      // Update element if there's more than one button
      this._maybeUpdateElements(event);
      // Check for same buttons elements
      this._updateSameButtons();
      // Check for same likers elements
      this._updateSameLikers();
      // Disable button
      if (this.buttonElement) {
        if (Array.isArray(this.buttonElement) || this.buttonElement.length !== undefined) {
          forEachElement(this.buttonElement, function (btn) {
            btn.disabled = true;
          });
        } else {
          this.buttonElement.disabled = true;
        }
      }
      // Manipulations
      triggerEvent(document, "WordpressUlikeLoading", { element: this.element });
      // Add progress class
      if (this.generalElement) {
        if (Array.isArray(this.generalElement) || this.generalElement.length !== undefined) {
          forEachElement(this.generalElement, function (el) {
            el.classList.add("wp_ulike_is_loading");
          });
        } else {
          this.generalElement.classList.add("wp_ulike_is_loading");
        }
      }
      // Start AJAX process
      var self = this;
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
        function (response) {
          //remove progress class
          if (self.generalElement) {
            if (Array.isArray(self.generalElement) || self.generalElement.length !== undefined) {
              forEachElement(self.generalElement, function (el) {
                el.classList.remove("wp_ulike_is_loading");
              });
            } else {
              self.generalElement.classList.remove("wp_ulike_is_loading");
            }
          }
          // Make changes
          if (response.success) {
            self._updateMarkup(response);
            // Append html data
            self._appendChild();
          } else if (response.data && response.data.hasToast) {
            self._sendNotification("error", response.data.message);
          }
          // Re-enable button
          if (self.buttonElement) {
            if (Array.isArray(self.buttonElement) || self.buttonElement.length !== undefined) {
              forEachElement(self.buttonElement, function (btn) {
                btn.disabled = false;
              });
            } else {
              self.buttonElement.disabled = false;
            }
          }
          // Add new trigger when process finished
          triggerEvent(document, "WordpressUlikeUpdated", { element: self.element });
        }
      );
    },

    _maybeUpdateElements: function (event) {
      this.buttonElement = event.currentTarget;
      this.generalElement = this.buttonElement.closest(
        this.settings.generalSelector
      );
      if (this.generalElement) {
        this.counterElement = this.generalElement.querySelectorAll(
          this.settings.counterSelector
        );
      } else {
        this.counterElement = [];
      }
      this.settings.factor = getDataAttribute(this.buttonElement, "ulike-factor");
    },

    /**
     * append child
     */
    _appendChild: function () {
      if (this.settings.append !== "" && this.buttonElement) {
        var appendedElement = document.querySelector(this.settings.append);
        if (appendedElement && this.buttonElement) {
          var button = Array.isArray(this.buttonElement) || this.buttonElement.length !== undefined
            ? this.buttonElement[0]
            : this.buttonElement;
          if (button) {
            button.appendChild(appendedElement);
            if (this.settings.appendTimeout) {
              var self = this;
              setTimeout(function () {
                if (appendedElement.parentNode) {
                  appendedElement.remove();
                }
              }, this.settings.appendTimeout);
            }
          }
        }
      }
    },

    /**
     * update button markup and calling some actions
     */
    _updateMarkup: function (response) {
      // Set sibling general elements
      this._setSbilingElement();
      // Set sibling button elements
      this._setSbilingButtons();
      // Update general element class names
      this._updateGeneralClassNames(response.data.status);
      // If data exist
      if (response.data.data !== null) {
        // Update counter + check refresh likers box
        if (response.data.status != 5) {
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

    _updateGeneralClassNames: function (status) {
      // Our base status class names
      var classNameObj = {
        start: "wp_ulike_is_not_liked",
        active: "wp_ulike_is_liked",
        deactive: "wp_ulike_is_unliked",
        disable: "wp_ulike_click_is_disabled",
      };

      var generalEl = Array.isArray(this.generalElement) || this.generalElement.length !== undefined
        ? this.generalElement[0]
        : this.generalElement;

      // Remove status from sibling element
      if (this.siblingElement && this.siblingElement.length) {
        forEachElement(this.siblingElement, function (el) {
          el.classList.remove(classNameObj.active, classNameObj.deactive);
        });
      }

      if (!generalEl) return;

      switch (status) {
        case 1:
          generalEl.classList.add(classNameObj.active);
          generalEl.classList.remove(classNameObj.start);
          var firstChild = generalEl.firstElementChild;
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
          if (this.siblingElement && this.siblingElement.length) {
            forEachElement(this.siblingElement, function (el) {
              el.classList.add(classNameObj.disable);
            });
          }
          break;
      }
    },

    _arrayToString: function (data) {
      return data.join(" ");
    },

    _setSbilingElement: function () {
      var generalEl = Array.isArray(this.generalElement) || this.generalElement.length !== undefined
        ? this.generalElement[0]
        : this.generalElement;
      if (generalEl) {
        this.siblingElement = getSiblings(generalEl);
      } else {
        this.siblingElement = [];
      }
    },

    _setSbilingButtons: function () {
      var buttonEl = Array.isArray(this.buttonElement) || this.buttonElement.length !== undefined
        ? this.buttonElement[0]
        : this.buttonElement;
      if (buttonEl) {
        this.siblingButton = getSiblings(buttonEl, this.settings.buttonSelector);
      } else {
        this.siblingButton = [];
      }
    },

    __updateCounter: function (counterValue) {
      // Update counter element
      var self = this;
      forEachElement(this.counterElement, function (element) {
        element.setAttribute("data-ulike-counter-value", counterValue);
        element.innerHTML = counterValue;
      });

      var buttonEl = Array.isArray(self.buttonElement) || self.buttonElement.length !== undefined
        ? self.buttonElement[0]
        : self.buttonElement;
      triggerEvent(document, "WordpressUlikeCounterUpdated", { buttonElement: buttonEl });
    },

    /**
     * init & update likers box
     */
    _updateLikers: function (event) {
      // Make a request to generate or refresh the likers box
      if (this.settings.displayLikers) {
        // return on these conditions
        if (
          this.settings.likersTemplate == "popover" &&
          getDataAttribute(this.element, "ulike-tooltip")
        ) {
          return;
        } else if (
          this.settings.likersTemplate == "default" &&
          this.likersElement
        ) {
          return;
        }

        // Show tooltip with loading spinner immediately for popover style
        if (this.settings.likersTemplate == "popover") {
          if (typeof WordpressUlikeTooltipPlugin !== "undefined") {
            var tooltipId =
              this.settings.type.toLowerCase() + "-" + this.settings.ID;
            var tooltipInstance =
              window.WordpressUlikeTooltip &&
              window.WordpressUlikeTooltip.getInstanceById
                ? window.WordpressUlikeTooltip.getInstanceById(tooltipId)
                : null;

            if (!tooltipInstance) {
              // Create new tooltip instance
              new WordpressUlikeTooltipPlugin(this.element, {
                id: tooltipId,
                title: "",
                position: "top",
                child: this.settings.generalSelector,
                theme: "white",
                size: "tiny",
                trigger: "hover",
              });
            }

            // Show loading spinner immediately (use setTimeout to ensure instance is registered)
            setTimeout(function () {
              var instance =
                window.WordpressUlikeTooltip &&
                window.WordpressUlikeTooltip.getInstanceById
                  ? window.WordpressUlikeTooltip.getInstanceById(tooltipId)
                  : null;
              if (instance && instance.showLoading) {
                instance.showLoading();
              }
            }, 10);
          }
        }

        // Add progress status class
        var generalEl = Array.isArray(this.generalElement) || this.generalElement.length !== undefined
          ? this.generalElement[0]
          : this.generalElement;
        if (generalEl) {
          generalEl.classList.add("wp_ulike_is_getting_likers_list");
        }
        // Start ajax process
        var self = this;
        this._ajax(
          {
            action: "wp_ulike_get_likers",
            id: this.settings.ID,
            nonce: this.settings.nonce,
            type: this.settings.type,
            displayLikers: this.settings.displayLikers,
            likersTemplate: this.settings.likersTemplate,
          },
          function (response) {
            // Remove progress status class
            if (generalEl) {
              generalEl.classList.remove("wp_ulike_is_getting_likers_list");
            }
            // Change markup
            if (response.success) {
              self._updateLikersMarkup(response.data);
            }
          }
        );

        if (event) {
          event.stopImmediatePropagation();
        }
        return false;
      }
    },

    /**
     * Update likers markup
     */
    _updateLikersMarkup: function (data) {
      if (this.settings.likersTemplate == "popover") {
        this.likersElement = this.element;
        var tooltipId =
          this.settings.type.toLowerCase() + "-" + this.settings.ID;
        var tooltipInstance =
          window.WordpressUlikeTooltip &&
          window.WordpressUlikeTooltip.getInstanceById
            ? window.WordpressUlikeTooltip.getInstanceById(tooltipId)
            : null;

        // Check if we have content or if it's empty
        var hasContent = data.template && data.template.trim().length > 0;

        if (hasContent) {
          // Update existing tooltip content
          if (tooltipInstance && tooltipInstance.updateContent) {
            tooltipInstance.updateContent(data.template);
          } else if (typeof WordpressUlikeTooltipPlugin !== "undefined") {
            // Create new if doesn't exist
            new WordpressUlikeTooltipPlugin(this.element, {
              id: tooltipId,
              title: data.template,
              position: "top",
              child: this.settings.generalSelector,
              theme: "white",
              size: "tiny",
              trigger: "hover",
            });
          }
        } else {
          // No content - hide tooltip if it exists
          if (tooltipInstance && tooltipInstance.hide) {
            tooltipInstance.hide();
          }
        }
      } else {
        // If the likers container is not exist, we've to add it.
        if (!this.likersElement) {
          var tempDiv = document.createElement("div");
          tempDiv.innerHTML = data.template;
          var newElement = tempDiv.firstElementChild;
          if (newElement) {
            this.element.appendChild(newElement);
            this.likersElement = newElement;
          }
        }
        // Modify likers box innerHTML
        if (this.likersElement) {
          if (data.template) {
            this.likersElement.style.display = "";
            this.likersElement.innerHTML = data.template;
          } else {
            this.likersElement.style.display = "none";
            this.likersElement.innerHTML = "";
          }
        }
      }

      triggerEvent(document, "WordpressUlikeLikersMarkupUpdated", {
        likersElement: this.likersElement,
        likersTemplate: this.settings.likersTemplate,
        template: data.template,
      });
    },

    /**
     * Update the elements of same buttons at the same time
     */
    _updateSameButtons: function () {
      // Get buttons with same unique class names
      var factorMethod =
        typeof this.settings.factor !== "undefined" && this.settings.factor
          ? "_" + this.settings.factor
          : "";
      var selector =
        ".wp_" +
        this.settings.type.toLowerCase() +
        factorMethod +
        "_btn_" +
        this.settings.ID;
      this.sameButtons = document.querySelectorAll(selector);
      // Update general elements
      if (this.sameButtons.length > 1) {
        this.buttonElement = this.sameButtons;
        // Get general elements for all buttons
        var generalElements = [];
        forEachElement(this.sameButtons, function (btn) {
          var genEl = btn.closest(this.settings.generalSelector);
          if (genEl) {
            generalElements.push(genEl);
          }
        }.bind(this));
        this.generalElement = generalElements.length === 1 ? generalElements[0] : generalElements;
        // Get counter elements
        var counterElements = [];
        forEachElement(generalElements, function (genEl) {
          var counters = genEl.querySelectorAll(this.settings.counterSelector);
          forEachElement(counters, function (counter) {
            counterElements.push(counter);
          });
        }.bind(this));
        this.counterElement = counterElements;
      }
    },

    /**
     * Update the elements of same likers at the same time
     */
    _updateSameLikers: function () {
      var selector =
        ".wp_" +
        this.settings.type.toLowerCase() +
        "_likers_" +
        this.settings.ID;
      this.sameLikers = document.querySelectorAll(selector);
      // Update general elements
      if (this.sameLikers.length > 1) {
        this.likersElement = this.sameLikers;
      }
    },

    /**
     * Get likers wrapper element
     */
    _getLikersElement: function () {
      return this.likersElement;
    },

    /**
     * Control actions
     */
    _updateButton: function (btnText, status) {
      var buttonEl = Array.isArray(this.buttonElement) || this.buttonElement.length !== undefined
        ? this.buttonElement[0]
        : this.buttonElement;

      if (!buttonEl) return;

      if (buttonEl.classList.contains("wp_ulike_put_image")) {
        if (status == 4) {
          buttonEl.classList.add("image-unlike", "wp_ulike_btn_is_active");
        } else {
          buttonEl.classList.toggle("image-unlike");
          buttonEl.classList.toggle("wp_ulike_btn_is_active");
        }
        if (this.siblingElement && this.siblingElement.length) {
          forEachElement(this.siblingElement, function (sibling) {
            var siblingBtn = sibling.querySelector(this.settings.buttonSelector);
            if (siblingBtn) {
              siblingBtn.classList.remove("image-unlike", "wp_ulike_btn_is_active");
            }
          }.bind(this));
        }
        if (this.siblingButton && this.siblingButton.length) {
          forEachElement(this.siblingButton, function (siblingBtn) {
            siblingBtn.classList.remove("image-unlike", "wp_ulike_btn_is_active");
          });
        }
      } else if (
        buttonEl.classList.contains("wp_ulike_put_text") &&
        btnText !== null
      ) {
        var span = buttonEl.querySelector("span");
        if (span) {
          span.innerHTML = btnText;
        }
      }
    },

    /**
     * Send notification by 'WordpressUlikeNotifications' plugin
     */
    _sendNotification: function (messageType, messageText) {
      // Display Notification
      if (typeof WordpressUlikeNotifications !== "undefined") {
        new WordpressUlikeNotifications(document.body, {
          messageType: messageType,
          messageText: messageText,
        });
      }
    },
  };

  // Expose plugin to window for global access
  window[pluginName] = Plugin;
})(window, document);
