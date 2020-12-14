/*! WP ULike - v4.4.3
 *  https://wpulike.com
 *  TechnoWich 2020;
 */


/* ================== assets/js/src/wordpress-ulike-notifications.js =================== */


/* 'WordpressUlikeNotifications' plugin : https://github.com/alimir/wp-ulike */
(function ($, window, document, undefined) {
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
  // The actual plugin constructor
  function Plugin(element, options) {
    this.element = element;
    this.$element = $(element);
    this.settings = $.extend({}, defaults, options);
    this._defaults = defaults;
    this._name = pluginName;
    this.init();
  }

  // Avoid Plugin.prototype conflicts
  $.extend(Plugin.prototype, {
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
      this.$messageElement = $("<div/>")
        .addClass(
          this.settings.messageElement + " wpulike-" + this.settings.messageType
        )
        .text(this.settings.messageText);
    },

    /**
     * Create notification container
     */
    _container: function () {
      // Make notification container if not exist
      if (!$("." + this.settings.notifContainer).length) {
        this.$element.append(
          $("<div/>").addClass(this.settings.notifContainer)
        );
      }
      this.$notifContainer = this.$element.find(
        "." + this.settings.notifContainer
      );
    },

    /**
     * Append notice
     */
    _append: function () {
      // Append Notification
      this.$notifContainer
        .append(this.$messageElement)
        .trigger("WordpressUlikeNotificationAppend");
    },

    /**
     * Disappear notice
     */
    _remove: function () {
      var self = this;
      // Remove Message On Click
      this.$messageElement.click(function () {
        $(this)
          .fadeOut(300, function () {
            $(this).remove();
            if (!$("." + self.settings.messageElement).length) {
              self.$notifContainer.remove();
            }
          })
          .trigger("WordpressUlikeRemoveNotification");
      });
      // Remove Message With Timeout
      if( self.settings.timeout ){
        setTimeout(function () {
          self.$messageElement
            .fadeOut(300, function () {
              $(this).remove();
              if (!$("." + self.settings.messageElement).length) {
                self.$notifContainer.remove();
              }
            })
            .trigger("WordpressUlikeRemoveNotification");
        }, self.settings.timeout);
      }

    }
  });

  // A really lightweight plugin wrapper around the constructor,
  // preventing against multiple instantiations
  $.fn[pluginName] = function (options) {
    return this.each(function () {
      new Plugin(this, options);
    });
  };
})(jQuery, window, document);


/* ================== assets/js/src/wordpress-ulike.js =================== */


(function ($, window, document, undefined) {
  "use strict";

  // Create the defaults once
  var pluginName = "WordpressUlike",
    $window = $(window),
    $document = $(document),
    defaults = {
      ID: 0 /*  Auto ID value by element type */,
      nonce: 0 /*  Get nonce token */,
      type:
        "" /* Values : likeThis (Posts),likeThisComment, likeThisActivity, likeThisTopic */,
      append: '',
      appendTimeout: 2000,
      displayLikers: false,
      disablePophover: true,
      isTotal: false,
      factor: '',
      template: '',
      counterSelector: ".count-box",
      generalSelector: ".wp_ulike_general_class",
      buttonSelector: ".wp_ulike_btn",
      likersSelector: ".wp_ulike_likers_wrapper"
    },
    attributesMap = {
      "ulike-id": "ID",
      "ulike-nonce": "nonce",
      "ulike-type": "type",
      "ulike-append": "append",
      "ulike-is-total": "isTotal",
      "ulike-display-likers": "displayLikers",
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
    this.generalElement = this.$element.find(this.settings.generalSelector);
    this.counterElement = this.generalElement.find(
      this.settings.counterSelector
    );

    // Get likers box container element
    this.likersElement = this.$element.find(this.settings.likersSelector);

    // read attributes
    for (var attrName in attributesMap) {
      var value = this.buttonElement.data(attrName);
      if (value !== undefined) {
        this.settings[attributesMap[attrName]] = value;
      }
    }
    this.init();
  }

  // Avoid Plugin.prototype conflicts
  $.extend(Plugin.prototype, {
    init: function () {
      // Call _ajaxify function on click button
      this.buttonElement.click(this._initLike.bind(this));
      // Call likers box generator
      this.generalElement.one("mouseenter", this._updateLikers.bind(this));
      // Fix PopHover Appearance
      // if( !this.settings.disablePophover && this.settings.displayLikers ){
      //   var self = this;
      //   this.generalElement.hover(
      //     function() {
      //       self.$element.addClass( "wp_ulike_display_pophover" );
      //     }, function() {
      //       self.$element.removeClass( "wp_ulike_display_pophover" );
      //     }
      //   );
      // }
    },

    /**
     * global AJAX callback
     */
    _ajax: function (args, callback) {
      // Do Ajax & update default value
      $.ajax({
        url: wp_ulike_params.ajax_url,
        type: "POST",
        cache: false,
        dataType: "json",
        data: args
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
          disablePophover: this.settings.disablePophover
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
      this.generalElement = this.buttonElement.closest(this.settings.generalSelector);
      this.counterElement = this.generalElement.find(
        this.settings.counterSelector
      );
      this.settings.factor = this.buttonElement.data('ulike-factor');
    },

    /**
     * append child
     */
    _appendChild: function () {
      if (this.settings.append !== '') {
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
        if (response.data.status < 5) {
          this.__updateCounter(response.data.data);
          // Refresh likers box on data update
          if (this.settings.displayLikers && typeof response.data.likers !== "undefined") {
            this._updateLikersMarkup(response.data.likers);
          }
        }
        // Update button status
        this._updateButton(response.data.btnText, response.data.status);
      }
      // Display Notifications
      if (response.data.hasToast) {
        this._sendNotification(response.data.messageType, response.data.message);
      }
    },

    _updateGeneralClassNames: function (status) {
      // Our base status class names
      var classNameObj = {
        start: "wp_ulike_is_not_liked",
        active: "wp_ulike_is_liked",
        deactive: "wp_ulike_is_unliked",
        disable: "wp_ulike_click_is_disabled"
      };

      // Remove status from sibling element
      if (this.siblingElement.length) {
        this.siblingElement.removeClass(this._arrayToString([
          classNameObj.active,
          classNameObj.deactive
        ]));
      }

      switch (status) {
        case 1:
          this.generalElement
            .addClass(classNameObj.active)
            .removeClass(classNameObj.start);
          this.generalElement
            .children()
            .first()
            .addClass(classNameObj.disable);
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

        default:
          this.generalElement.addClass(classNameObj.disable);
          if (this.siblingElement.length) {
            this.siblingElement.addClass(classNameObj.disable);
          }
          break;
      }
    },

    _arrayToString: function (data) {
      return data.join(' ');
    },

    _setSbilingElement: function () {
      this.siblingElement = this.generalElement.siblings();
    },

    _setSbilingButtons: function () {
      this.siblingButton = this.buttonElement.siblings(this.settings.buttonSelector);
    },

    __updateCounter: function (counterValue) {
      if (typeof counterValue !== "object") {
        this.counterElement.text(counterValue);
      } else {
        if (this.settings.isTotal && typeof counterValue.sub !== "undefined") {
          this.counterElement.text(counterValue.sub);
        } else {
          if (this.settings.factor === 'down') {
            this.counterElement.text(counterValue.down);
            if (this.siblingElement.length) {
              this.siblingElement.find(this.settings.counterSelector).text(counterValue.up);
            }
          } else {
            this.counterElement.text(counterValue.up);
            if (this.siblingElement.length) {
              this.siblingElement.find(this.settings.counterSelector).text(counterValue.down);
            }
          }
        }
      }

      $document.trigger("WordpressUlikeCounterUpdated", [this.buttonElement]);
    },

    /**
     * init & update likers box
     */
    _updateLikers: function () {
      // Make a request to generate or refresh the likers box
      if (this.settings.displayLikers && !this.likersElement.length) {
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
            disablePophover: this.settings.disablePophover
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
      }
    },

    /**
     * Update likers markup
     */
    _updateLikersMarkup: function (data) {
      // If the likers container is not exist, we've to add it.
      if (!this.likersElement.length) {
        this.likersElement = $("<div>", {
          class: data.class
        }).appendTo(this.$element);
      }
      // Modify likers box innerHTML
      if (data.template) {
        this.likersElement.show().html(data.template);
      } else {
        this.likersElement.hide();
      }
    },

    /**
     * Update the elements of same buttons at the same time
     */
    _updateSameButtons: function () {
      // Get buttons with same unique class names
      var factorMethod = typeof this.settings.factor !== "undefined" ? '_' + this.settings.factor : '';
      this.sameButtons = $document.find(
        ".wp_" + this.settings.type.toLowerCase() + factorMethod + "_btn_" + this.settings.ID
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
        ".wp_" + this.settings.type.toLowerCase() + "_likers_" + this.settings.ID
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
    _updateButton: function (btnText, likeStatus) {
      if (this.buttonElement.hasClass("wp_ulike_put_image")) {
        this.buttonElement.toggleClass("image-unlike wp_ulike_btn_is_active");
        if (this.siblingElement.length) {
          this.siblingElement.find(this.settings.buttonSelector).removeClass("image-unlike wp_ulike_btn_is_active");
        }
        if (this.siblingButton.length) {
          this.siblingButton.removeClass("image-unlike wp_ulike_btn_is_active");
        }
      } else if (this.buttonElement.hasClass("wp_ulike_put_text") && btnText !== null) {
        if (typeof btnText !== "object") {
          this.buttonElement.find("span").html(btnText);
        } else {
          if (this.settings.factor === 'down') {
            this.buttonElement.find("span").html(btnText.down);
            if (this.siblingElement.length) {
              this.siblingElement.find(this.settings.buttonSelector).find("span").html(btnText.up);
            }
          } else {
            this.buttonElement.find("span").html(btnText.up);
            if (this.siblingElement.length) {
              this.siblingElement.find(this.settings.buttonSelector).find("span").html(btnText.down);
            }
          }
        }
      }
    },

    /**
     * Send notification by 'WordpressUlikeNotifications' plugin
     */
    _sendNotification: function (messageType, messageText) {
      // Display Notification
      $(document.body).WordpressUlikeNotifications({
        messageType: messageType,
        messageText: messageText
      });
    }
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


/* ================== assets/js/src/scripts.js =================== */


/* Run :) */
(function ($) {
  // on document ready
  $(function () {
    // Upgrading 'WordpressUlike' datasheets when new DOM has been inserted
    $(this).bind("DOMNodeInserted", function (e) {
      $(".wpulike").WordpressUlike();
    });
  });

  // init WordpressUlike
  $(".wpulike").WordpressUlike();
})(jQuery);