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
    // Try dataset first (converts kebab-case to camelCase)
    const camelName = name.replace(/-([a-z])/g, (g) => g[1].toUpperCase());
    if (element.dataset && element.dataset[camelName] !== undefined) {
      return element.dataset[camelName];
    }
    // Fallback to getAttribute
    const value = element.getAttribute(`data-${name}`);
    if (value === null) {
      return undefined;
    }
    // Try to parse as boolean or number
    if (value === "true") return true;
    if (value === "false") return false;
    if (value === "" || value === "null") return null;
    if (!isNaN(value) && value !== "") return Number(value);
    return value;
  };

  // Helper function to trigger custom events
  const triggerEvent = (element, eventName, detail) => {
    const event = new CustomEvent(eventName, {
      bubbles: true,
      cancelable: true,
      detail: detail || {}
    });
    element.dispatchEvent(event);
  };

  // Helper function to handle multiple elements
  const forEachElement = (elements, callback) => {
    if (!elements) return;
    if (elements.length === undefined) {
      // Single element
      callback(elements, 0);
    } else {
      // NodeList or Array
      Array.from(elements).forEach(callback);
    }
  };

  // Helper function to get siblings
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

  // Helper to get single element from array/NodeList
  const getSingleElement = (elements) => {
    return Array.isArray(elements) || elements.length !== undefined
      ? elements[0]
      : elements;
  };

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
      for (const attrName in attributesMap) {
        if (attributesMap.hasOwnProperty(attrName)) {
          const value = getDataAttribute(this.buttonElement, attrName);
          if (value !== undefined) {
            this.settings[attributesMap[attrName]] = value;
          }
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
      // Call _ajaxify function on click button
      if (this.buttonElement) {
        this.buttonElement.addEventListener("click", this._initLike.bind(this));
      }
      // Call likers box generator (one-time event)
      if (this.generalElement) {
        const mouseenterHandler = (event) => {
          this._updateLikers(event);
          this.generalElement.removeEventListener("mouseenter", mouseenterHandler);
        };
        this.generalElement.addEventListener("mouseenter", mouseenterHandler);
      }
    },

    /**
     * global AJAX callback
     */
    _ajax(args, callback) {
      // Do Ajax & update default value
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
        forEachElement(this.buttonElement, (btn) => {
          btn.disabled = true;
        });
      }
      // Manipulations
      triggerEvent(document, "WordpressUlikeLoading", { element: this.element });
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
          triggerEvent(document, "WordpressUlikeUpdated", { element: this.element });
        }
      );
    },

    _maybeUpdateElements(event) {
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
    _appendChild() {
      if (this.settings.append !== "" && this.buttonElement) {
        const appendedElement = document.querySelector(this.settings.append);
        if (appendedElement && this.buttonElement) {
          const button = getSingleElement(this.buttonElement);
          if (button) {
            button.appendChild(appendedElement);
            if (this.settings.appendTimeout) {
              setTimeout(() => {
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
      // Our base status class names
      const classNameObj = {
        start: "wp_ulike_is_not_liked",
        active: "wp_ulike_is_liked",
        deactive: "wp_ulike_is_unliked",
        disable: "wp_ulike_click_is_disabled",
      };

      const generalEl = getSingleElement(this.generalElement);

      // Remove status from sibling element
      if (this.siblingElement && this.siblingElement.length) {
        forEachElement(this.siblingElement, (el) => {
          el.classList.remove(classNameObj.active, classNameObj.deactive);
        });
      }

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
          if (this.siblingElement && this.siblingElement.length) {
            forEachElement(this.siblingElement, (el) => {
              el.classList.add(classNameObj.disable);
            });
          }
          break;
      }
    },

    _arrayToString(data) {
      return data.join(" ");
    },

    _setSbilingElement() {
      const generalEl = getSingleElement(this.generalElement);
      if (generalEl) {
        this.siblingElement = getSiblings(generalEl);
      } else {
        this.siblingElement = [];
      }
    },

    _setSbilingButtons() {
      const buttonEl = getSingleElement(this.buttonElement);
      if (buttonEl) {
        this.siblingButton = getSiblings(buttonEl, this.settings.buttonSelector);
      } else {
        this.siblingButton = [];
      }
    },

    __updateCounter(counterValue) {
      // Update counter element
      forEachElement(this.counterElement, (element) => {
        element.setAttribute("data-ulike-counter-value", counterValue);
        element.innerHTML = counterValue;
      });

      const buttonEl = getSingleElement(this.buttonElement);
      triggerEvent(document, "WordpressUlikeCounterUpdated", { buttonElement: buttonEl });
    },

    /**
     * init & update likers box
     */
    _updateLikers(event) {
      // Make a request to generate or refresh the likers box
      if (this.settings.displayLikers) {
        // return on these conditions
        if (
          this.settings.likersTemplate === "popover" &&
          getDataAttribute(this.element, "ulike-tooltip")
        ) {
          return;
        } else if (
          this.settings.likersTemplate === "default" &&
          this.likersElement
        ) {
          return;
        }

        // Show tooltip with loading spinner immediately for popover style
        if (this.settings.likersTemplate === "popover") {
          if (typeof WordpressUlikeTooltipPlugin !== "undefined") {
            const tooltipId = `${this.settings.type.toLowerCase()}-${this.settings.ID}`;
            const tooltipInstance =
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
            setTimeout(() => {
              const instance =
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
        const generalEl = getSingleElement(this.generalElement);
        if (generalEl) {
          generalEl.classList.add("wp_ulike_is_getting_likers_list");
        }
        // Start ajax process
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
            // Remove progress status class
            if (generalEl) {
              generalEl.classList.remove("wp_ulike_is_getting_likers_list");
            }
            // Change markup
            if (response.success) {
              this._updateLikersMarkup(response.data);
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
    _updateLikersMarkup(data) {
      if (this.settings.likersTemplate === "popover") {
        this.likersElement = this.element;
        const tooltipId = `${this.settings.type.toLowerCase()}-${this.settings.ID}`;
        const tooltipInstance =
          window.WordpressUlikeTooltip &&
          window.WordpressUlikeTooltip.getInstanceById
            ? window.WordpressUlikeTooltip.getInstanceById(tooltipId)
            : null;

        // Check if we have content or if it's empty
        const hasContent = data.template && data.template.trim().length > 0;

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
          const tempDiv = document.createElement("div");
          tempDiv.innerHTML = data.template;
          const newElement = tempDiv.firstElementChild;
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
    _updateSameButtons() {
      // Get buttons with same unique class names
      const factorMethod =
        typeof this.settings.factor !== "undefined" && this.settings.factor
          ? `_${this.settings.factor}`
          : "";
      const selector = `.wp_${this.settings.type.toLowerCase()}${factorMethod}_btn_${this.settings.ID}`;
      this.sameButtons = document.querySelectorAll(selector);
      // Update general elements
      if (this.sameButtons.length > 1) {
        this.buttonElement = this.sameButtons;
        // Get general elements for all buttons
        const generalElements = [];
        forEachElement(this.sameButtons, (btn) => {
          const genEl = btn.closest(this.settings.generalSelector);
          if (genEl) {
            generalElements.push(genEl);
          }
        });
        this.generalElement = generalElements.length === 1 ? generalElements[0] : generalElements;
        // Get counter elements
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
     * Control actions
     */
    _updateButton(btnText, status) {
      const buttonEl = getSingleElement(this.buttonElement);

      if (!buttonEl) return;

      if (buttonEl.classList.contains("wp_ulike_put_image")) {
        if (status === 4) {
          buttonEl.classList.add("image-unlike", "wp_ulike_btn_is_active");
        } else {
          buttonEl.classList.toggle("image-unlike");
          buttonEl.classList.toggle("wp_ulike_btn_is_active");
        }
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
      } else if (
        buttonEl.classList.contains("wp_ulike_put_text") &&
        btnText !== null
      ) {
        const span = buttonEl.querySelector("span");
        if (span) {
          span.innerHTML = btnText;
        }
      }
    },

    /**
     * Send notification by 'WordpressUlikeNotifications' plugin
     */
    _sendNotification(messageType, messageText) {
      // Display Notification
      if (typeof WordpressUlikeNotifications !== "undefined") {
        new WordpressUlikeNotifications(document.body, {
          messageType,
          messageText,
        });
      }
    },
  };

  // Expose plugin to window for global access
  window[pluginName] = Plugin;
})(window, document);
