/* 'WordpressUlike' plugin : https://github.com/alimir/wp-ulike */
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
      likeStatus: 0 /* Values : 0 (Is not logged-in), 1 (Is not liked), 2 (Is liked), 3 (Is unliked), 4 (Already liked) */,
      append: '',
      appendTimeout: 2000,
      displayLikers: false,
      factor: '',
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
      "ulike-display-likers": "displayLikers",
      "ulike-append-timeout": "appendTimeout",
      "ulike-status": "likeStatus",
      "ulike-factor": "factor"
    };

  // The actual plugin constructor
  function Plugin(element, options) {
    this.element = element;
    this.$element = $(element);
    this.settings = $.extend({}, defaults, options);
    this._defaults = defaults;
    this._name = pluginName;

    this._refreshTheLikers = false;

    // Create main selectors
    this.buttonElement = this.$element.find(this.settings.buttonSelector);
    this.generalElement = this.$element.find(this.settings.generalSelector);
    this.counterElement = this.generalElement.find(
      this.settings.counterSelector
    );

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
      //Call _ajaxify function on click button
      this.buttonElement.click(this._initLike.bind(this));
      //Call _ajaxify function on click button
      this.buttonElement.hover(this._updateLikers.bind(this));
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
      // check for same buttons
      this._updateSameButtons();
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
          status: this.settings.likeStatus,
          type: this.settings.type
        },
        function (response) {
          //remove progress class
          this.generalElement.removeClass("wp_ulike_is_loading");
          // Make changes
          if (response.success) {
            this._updateMarkup(response);
            // Append html data
            this._appendChild();
          } else {
            this._sendNotification("error", response.data);
          }
          // Re-enable button
          this.buttonElement.prop("disabled", false);
          // Add new trigger when process finished
          $document.trigger("WordpressUlikeUpdated", this.element);
        }.bind(this)
      );
    },

    _maybeUpdateElements: function(event) {
      this.buttonElement  = $(event.currentTarget);
      this.generalElement = this.buttonElement.closest(this.settings.generalSelector);
      this.counterElement = this.generalElement.find(
        this.settings.counterSelector
      );
      this.settings.factor = this.buttonElement.data('ulike-factor');
      this.settings.likeStatus = this.buttonElement.data('ulike-status');
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
      //check likeStatus
      switch (this.settings.likeStatus) {
        case 1 /* Change the status of 'is not liked' to 'liked' */:
          this.buttonElement.data("ulike-status", 4);
          this.settings.likeStatus = 4;
          this.generalElement
            .addClass("wp_ulike_is_liked")
            .removeClass("wp_ulike_is_not_liked");
          this.generalElement
            .children()
            .first()
            .addClass("wp_ulike_click_is_disabled");
            this.__updateCounter(response.data.data);
          this._controlActions(
            "success",
            response.data.message,
            response.data.btnText,
            4
          );
          this._refreshTheLikers = true;
          break;
        case 2 /* Change the status of 'liked' to 'unliked' */:
          this.buttonElement.data("ulike-status", 3);
          this.settings.likeStatus = 3;
          this.generalElement
            .addClass("wp_ulike_is_unliked")
            .removeClass("wp_ulike_is_liked");
          this.__updateCounter(response.data.data);
          this._controlActions(
            "error",
            response.data.message,
            response.data.btnText,
            3
          );
          this._refreshTheLikers = true;
          break;
        case 3 /* Change the status of 'unliked' to 'liked' */:
          this.buttonElement.data("ulike-status", 2);
          this.settings.likeStatus = 2;
          this.generalElement
            .addClass("wp_ulike_is_liked")
            .removeClass("wp_ulike_is_unliked");
          this.__updateCounter(response.data.data);
          this._controlActions(
            "success",
            response.data.message,
            response.data.btnText,
            2
          );
          this._refreshTheLikers = true;
          break;
        case 4 /* Just print the log-in warning message */:
          this._controlActions(
            "info",
            response.data.message,
            response.data.btnText,
            4
          );
          this.generalElement
            .children()
            .first()
            .addClass("wp_ulike_click_is_disabled");
          break;
        default:
          /* Just print the permission failed message */
          this._controlActions(
            "warning",
            response.data.message,
            response.data.btnText,
            0
          );
      }

      // Refresh likers box on data update
      if (this._refreshTheLikers) {
        this._updateLikers();
      }
    },

    __updateCounter: function( counterValue ){
      if( typeof counterValue !== "object" ){
        this.counterElement.text(counterValue);
      } else {
        var $siblingElement = this.generalElement.siblings();
        if( this.settings.factor === 'down' ){
          this.counterElement.text(counterValue.down);
          if($siblingElement.length){
            $siblingElement.find(this.settings.counterSelector).text(counterValue.up);
          }
        } else {
          this.counterElement.text(counterValue.up);
          if($siblingElement.length){
            $siblingElement.find(this.settings.counterSelector).text(counterValue.down);
          }
        }
      }


      $document.trigger("WordpressUlikeCounterUpdated", [this.buttonElement, this.settings.likeStatus]);
      // // $document.on( "WordpressUlikeCounterUpdated", function( event, param1, param2 ) {
      // //   console.log( param1 );
      // // });
    },

    /**
     * init & update likers box
     */
    _updateLikers: function () {
      // Get likers box container element
      this.likersElement = this._getLikersElement();
      // Make a request to generate or refresh the likers box
      if (this.settings.displayLikers && (!this.likersElement.length || this._refreshTheLikers)) {
        // Add progress status class
        this.generalElement.addClass("wp_ulike_is_getting_likers_list");
        // Start ajax process
        this._ajax(
          {
            action: "wp_ulike_get_likers",
            id: this.settings.ID,
            nonce: this.settings.nonce,
            type: this.settings.type,
            refresh: this._refreshTheLikers ? 1 : 0
          },
          function (response) {
            // Remove progress status class
            this.generalElement.removeClass("wp_ulike_is_getting_likers_list");
            // Change markup
            if (response.success) {
              // If the likers container is not exist, we've to add it.
              if (!this.likersElement.length) {
                this.likersElement = $("<div>", {
                  class: response.data.class
                }).appendTo(this.$element);
              }
              // Modify likers box innerHTML
              if (response.data.template) {
                this.likersElement.show().html(response.data.template);
              } else {
                this.likersElement.hide();
              }
            }
            this._refreshTheLikers = false;
          }.bind(this)
        );
      }
    },

    /**
     * Update the elements of same buttons at the same time
     */
    _updateSameButtons: function () {
      // Get buttons with same unique class names
      this.sameButtons = $document.find(
        ".wp_" + this.settings.type.toLowerCase() + this.settings.factor + "_" + this.settings.ID
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
     * Get likers wrapper element
     */
    _getLikersElement: function () {
      if (this.generalElement.length > 1) {
        return this.generalElement.next(this.settings.likersSelector);
      } else {
        return this.$element.find(this.settings.likersSelector);
      }
    },

    /**
     * Control actions
     */
    _controlActions: function (messageType, messageText, btnText, likeStatus) {
      //check the button types
      if (this.buttonElement.hasClass("wp_ulike_put_image")) {
        if (likeStatus === 3 || likeStatus === 2) {
          this.buttonElement.toggleClass("image-unlike");
        }
      } else if (this.buttonElement.hasClass("wp_ulike_put_text")) {
        this.buttonElement.find("span").html(btnText);
      }

      // Display Notifications
      this._sendNotification(messageType, messageText);
    },

    /**
     * Send notification by 'WordpressUlikeNotifications' plugin
     */
    _sendNotification: function (messageType, messageText) {
      //Check notifications active mode
      if (wp_ulike_params.notifications !== "1") {
        return;
      }
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
