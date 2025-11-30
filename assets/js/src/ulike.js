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
        forEachElement(this.buttonElement, (button) => {
          if (button) {
            button.addEventListener("click", this._initLike.bind(this));
          }
        });
      }
      // Call likers box generator (one-time event)
      const firstGeneralEl = this.generalElement.length > 0 ? this.generalElement[0] : null;
      if (firstGeneralEl) {
        const mouseenterHandler = (event) => {
          this._updateLikers(event);
          firstGeneralEl.removeEventListener("mouseenter", mouseenterHandler);
        };
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
            setTimeout(() => {
              appendedElements.forEach((el) => {
                if (el && el.parentNode) {
                  el.remove();
                }
              });
            }, this.settings.appendTimeout);
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
     */
    _fetchLikersData() {
      if (!this.settings.displayLikers) {
        this._isFetchingLikers = false;
        return;
      }

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
                  if (this._isFetchingLikers) {
                    return;
                  }
                  this._isFetchingLikers = true;
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
