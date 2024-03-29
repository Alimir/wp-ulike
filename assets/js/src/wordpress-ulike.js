(function ($, window, document, undefined) {
  "use strict";

  // Create the defaults once
  var pluginName = "WordpressUlike",
    $window = $(window),
    $document = $(document),
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

  // The actual plugin constructor
  function Plugin(element, options) {
    this.element = element;
    this.$element = $(element);
    this.settings = $.extend({}, defaults, options);
    this._defaults = defaults;
    this._name = pluginName;

    // Create main selectors
    this.buttonElement = this.$element.find(this.settings.buttonSelector);

    // read attributes
    for (var attrName in attributesMap) {
      var value = this.buttonElement.data(attrName);
      if (value !== undefined) {
        this.settings[attributesMap[attrName]] = value;
      }
    }

    // General element
    this.generalElement = this.$element.find(this.settings.generalSelector);

    // Create counter element
    this.counterElement = this.generalElement.find(
      this.settings.counterSelector
    );

    // Append dom counter element
    if (this.counterElement.length) {
      this.counterElement.each(
        function (index, element) {
          if (typeof $(element).data("ulike-counter-value") !== "undefined") {
            $(element).html($(element).data("ulike-counter-value"));
          }
        }.bind(this)
      );
    }
    // Get likers box container element
    this.likersElement = this.$element.find(this.settings.likersSelector);

    this.init();
  }

  // Avoid Plugin.prototype conflicts
  $.extend(Plugin.prototype, {
    init: function () {
      // Call _ajaxify function on click button
      this.buttonElement.on("click", this._initLike.bind(this));
      // Call likers box generator
      this.generalElement.one("mouseenter", this._updateLikers.bind(this));
    },

    /**
     * global AJAX callback
     */
    _ajax: function (args, callback) {
      // Do Ajax & update default value
      $.ajax({
        url: wp_ulike_params.ajax_url,
        type: "POST",
        dataType: "json",
        data: args,
      }).done(callback);
    },

    /**
     * init ulike core process
     */
    _initLike: function (event) {
      // Prevents further propagation of the current event in the capturing and bubbling phases
      event.stopPropagation();
      // Update element if there's more thab one button
      this._maybeUpdateElements(event);
      // Check for same buttons elements
      this._updateSameButtons();
      // Check for same likers elements
      this._updateSameLikers();
      // Disable button
      this.buttonElement.prop("disabled", true);
      // Manipulations
      $document.trigger("WordpressUlikeLoading", this.element);
      // Add progress class
      this.generalElement.addClass("wp_ulike_is_loading");
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
        function (response) {
          //remove progress class
          this.generalElement.removeClass("wp_ulike_is_loading");
          // Make changes
          if (response.success) {
            this._updateMarkup(response);
            // Append html data
            this._appendChild();
          } else if (response.data.hasToast) {
            this._sendNotification("error", response.data.message);
          }
          // Re-enable button
          this.buttonElement.prop("disabled", false);
          // Add new trigger when process finished
          $document.trigger("WordpressUlikeUpdated", this.element);
        }.bind(this)
      );
    },

    _maybeUpdateElements: function (event) {
      this.buttonElement = $(event.currentTarget);
      this.generalElement = this.buttonElement.closest(
        this.settings.generalSelector
      );
      this.counterElement = this.generalElement.find(
        this.settings.counterSelector
      );
      this.settings.factor = this.buttonElement.data("ulike-factor");
    },

    /**
     * append child
     */
    _appendChild: function () {
      if (this.settings.append !== "") {
        var $appendedElement = $(this.settings.append);
        this.buttonElement.append($appendedElement);
        if (this.settings.appendTimeout) {
          setTimeout(function () {
            $appendedElement.detach();
          }, this.settings.appendTimeout);
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

      // Remove status from sibling element
      if (this.siblingElement.length) {
        this.siblingElement.removeClass(
          this._arrayToString([classNameObj.active, classNameObj.deactive])
        );
      }

      switch (status) {
        case 1:
          this.generalElement
            .addClass(classNameObj.active)
            .removeClass(classNameObj.start);
          this.generalElement.children().first().addClass(classNameObj.disable);
          break;

        case 2:
          this.generalElement
            .addClass(classNameObj.deactive)
            .removeClass(classNameObj.active);
          break;

        case 3:
          this.generalElement
            .addClass(classNameObj.active)
            .removeClass(classNameObj.deactive);
          break;

        case 0:
        case 5:
          this.generalElement.addClass(classNameObj.disable);
          if (this.siblingElement.length) {
            this.siblingElement.addClass(classNameObj.disable);
          }
          break;
      }
    },

    _arrayToString: function (data) {
      return data.join(" ");
    },

    _setSbilingElement: function () {
      this.siblingElement = this.generalElement.siblings();
    },

    _setSbilingButtons: function () {
      this.siblingButton = this.buttonElement.siblings(
        this.settings.buttonSelector
      );
    },

    __updateCounter: function (counterValue) {
      // Update counter element
      this.counterElement
        .attr("data-ulike-counter-value", counterValue)
        .html(counterValue);

      $document.trigger("WordpressUlikeCounterUpdated", [this.buttonElement]);
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
          this.$element.data("ulike-tooltip")
        ) {
          return;
        } else if (
          this.settings.likersTemplate == "default" &&
          this.likersElement.length
        ) {
          return;
        }
        // Add progress status class
        this.generalElement.addClass("wp_ulike_is_getting_likers_list");
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
          function (response) {
            // Remove progress status class
            this.generalElement.removeClass("wp_ulike_is_getting_likers_list");
            // Change markup
            if (response.success) {
              this._updateLikersMarkup(response.data);
            }
          }.bind(this)
        );

        event.stopImmediatePropagation();
        return false;
      }
    },

    /**
     * Update likers markup
     */
    _updateLikersMarkup: function (data) {
      if (this.settings.likersTemplate == "popover") {
        this.likersElement = this.$element;
        if (data.template) {
          this.likersElement.WordpressUlikeTooltip({
            id: this.settings.type.toLowerCase() + "-" + this.settings.ID,
            title: data.template,
            position: "top",
            child: this.settings.generalSelector,
            theme: "white",
            size: "tiny",
            trigger: "hover",
          });
        }
      } else {
        // If the likers container is not exist, we've to add it.
        if (!this.likersElement.length) {
          this.likersElement = $(data.template).appendTo(this.$element);
        }
        // Modify likers box innerHTML
        if (data.template) {
          this.likersElement.show().html(data.template);
        } else {
          this.likersElement.hide().empty();
        }
      }

      $document.trigger("WordpressUlikeLikersMarkupUpdated", [
        this.likersElement,
        this.settings.likersTemplate,
        data.template,
      ]);
    },

    /**
     * Update the elements of same buttons at the same time
     */
    _updateSameButtons: function () {
      // Get buttons with same unique class names
      var factorMethod =
        typeof this.settings.factor !== "undefined"
          ? "_" + this.settings.factor
          : "";
      this.sameButtons = $document.find(
        ".wp_" +
          this.settings.type.toLowerCase() +
          factorMethod +
          "_btn_" +
          this.settings.ID
      );
      // Update general elements
      if (this.sameButtons.length > 1) {
        this.buttonElement = this.sameButtons;
        this.generalElement = this.buttonElement.closest(
          this.settings.generalSelector
        );
        this.counterElement = this.generalElement.find(
          this.settings.counterSelector
        );
      }
    },

    /**
     * Update the elements of same buttons at the same time
     */
    _updateSameLikers: function () {
      this.sameLikers = $document.find(
        ".wp_" +
          this.settings.type.toLowerCase() +
          "_likers_" +
          this.settings.ID
      );
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
      if (this.buttonElement.hasClass("wp_ulike_put_image")) {
        if (status == 4) {
          this.buttonElement.addClass("image-unlike wp_ulike_btn_is_active");
        } else {
          this.buttonElement.toggleClass("image-unlike wp_ulike_btn_is_active");
        }
        if (this.siblingElement.length) {
          this.siblingElement
            .find(this.settings.buttonSelector)
            .removeClass("image-unlike wp_ulike_btn_is_active");
        }
        if (this.siblingButton.length) {
          this.siblingButton.removeClass("image-unlike wp_ulike_btn_is_active");
        }
      } else if (
        this.buttonElement.hasClass("wp_ulike_put_text") &&
        btnText !== null
      ) {
        this.buttonElement.find("span").html(btnText);
      }
    },

    /**
     * Send notification by 'WordpressUlikeNotifications' plugin
     */
    _sendNotification: function (messageType, messageText) {
      // Display Notification
      $(document.body).WordpressUlikeNotifications({
        messageType: messageType,
        messageText: messageText,
      });
    },
  });

  // A really lightweight plugin wrapper around the constructor,
  // preventing against multiple instantiations
  $.fn[pluginName] = function (options) {
    return this.each(function () {
      if (!$.data(this, "plugin_" + pluginName)) {
        $.data(this, "plugin_" + pluginName, new Plugin(this, options));
      }
    });
  };
})(jQuery, window, document);
