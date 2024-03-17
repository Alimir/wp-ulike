/*! WP ULike - v4.7.0
 *  https://wpulike.com
 *  TechnoWich 2024;
 */


/* ================== assets/js/src/tooltip.js =================== */


; (function ($) {

    $.fn.WordpressUlikeTooltip = function (options) {

        //Instantiate WordpressUlikeTooltip once per dom element
        if (this.length > 1) {
            this.each(function () {
                $(this).WordpressUlikeTooltip(options);
            });
            return this;
        }

        //if there's nothing being passed
        if (typeof this === 'undefined' || this.length !== 1) {
            return false;
        }

        const dom_wrapped = $(this);

        //get list of options
        options = $.extend({}, $.WordpressUlikeTooltip.defaults, options, dom_wrapped.data());

        //get title attribute
        let title = dom_wrapped.attr('title');

        //if exists, override defaults
        if (typeof title !== 'undefined' && title.length) {
            options.title = title;
        }

        //add theme class
        options.class += ' ulf-' + options.theme + '-theme';
        //add size class
        options.class += ' ulf-' + options.size;

        //lowercase and trim whatever trigger is provided to try to make it more forgiving (this means "Hover " works just as well as "hover")
        options.trigger = options.trigger.toLowerCase().trim();

        let helper = {
            dom: this,
            dom_wrapped: dom_wrapped,
            position_debug: options.position_debug,
            trigger: options.trigger,
            id: options.id,
            title: options.title,
            content: options.title,
            child_class: options.child,
            theme: options.theme,
            class: options.class,
            position: options.position,
            close_on_outside_click: options.close_on_outside_click,
            singleton: options.singleton,
            dataAttr: 'ulike-tooltip',
            //create tooltip html
            createTooltipHTML: function () {
                return `<div class='ulf-tooltip ${helper.class}' role='tooltip'><div class='ulf-arrow'></div><div class='ulf-content'>${helper.content}</div></div>`;
            },
            //disable existing options/handlers
            destroy: function () {
                //only if it's actually tied to this element
                const existing = helper.dom_wrapped.data(helper.dataAttr);
                if (typeof existing !== 'undefined' && existing !== null) {
                    existing.dom_wrapped.off('touchstart mouseenter', existing.show);
                    existing.dom_wrapped.off('click', existing.preventDefaultHandler);

                    //attach resize handler to reposition tooltip
                    $(window).off('resize', existing.onResize);

                    //if currently shown, hide it
                    existing.isVisible() && existing.hide();

                    //detach from dom
                    existing.dom_wrapped.data(existing.dataAttr, null);
                }
            },
            //initialize the plugin on this element
            initialize: function () {
                //attach on handler to show tooltip
                //use touchstart and mousedown just like if you click outside the tooltip to close it
                //this way it blocks the hide if you click the button a second time to close the tooltip
                helper.dom_wrapped.on('touchstart mouseenter', helper.show);
                helper.dom_wrapped.on('click', helper.preventDefaultHandler);
                // helper.dom_wrapped.on('touchend mouseleave', helper.hide);


                if (!$.WordpressUlikeTooltip.body_click_initialized) {
                    $(document).on('touchstart mousedown', helper.onClickOutside);
                    $.WordpressUlikeTooltip.bodyClickInitialized = true;
                }

                //attach to dom for easy access later
                helper.dom_wrapped.data(helper.dataAttr, helper);

                // WP ULike Actions
                $(document).on('WordpressUlikeLikersMarkupUpdated', function (e, el, type, temp) {
                    if (type == 'popover') {
                        if (temp.length) {
                            helper.show();
                        } else {
                            let existing = el.data(helper.dataAttr);
                            if (typeof existing !== 'undefined' && existing !== null) {
                                existing.destroy();
                            }
                        }
                    }
                });

                //return dom for chaining of event handlers and such
                return helper.dom;
            },
            //on click of element, prevent default
            preventDefaultHandler: function (e) {
                e.preventDefault();
                return false;
            },
            //shows the tooltip
            show: function (trigger_event) {
                //if already visible, don't show
                if (helper.isVisible()) {
                    return false;
                }

                if (helper.singleton) {
                    helper.hideAllVisible();
                }

                //cache reference to the body
                const body = $('body');

                //get string from function
                if (typeof trigger_event === 'undefined' || trigger_event) {
                    if (typeof helper.title === 'function') helper.content = helper.title(helper.dom_wrapped, helper);
                }
                //add the tooltip to the dom
                body.append(helper.createTooltipHTML());
                //cache tooltip
                helper.tooltip = $('.ulf-tooltip:last');
                //position it
                helper.positionTooltip();
                //attach resize handler to reposition tooltip
                $(window).on('resize', helper.onResize);
                //give the tooltip an id so we can set accessibility props
                const id = 'ulp-dom-' + helper.id;
                helper.tooltip.attr('id', id);
                helper.dom.attr('aria-describedby', id);
                //add to open array
                $.WordpressUlikeTooltip.visible.push(helper);
                //trigger event on show and pass the tooltip
                if (typeof trigger_event === 'undefined' || trigger_event) {
                    helper.dom.trigger('ulf-show', [helper.tooltip, helper.hide]);
                }
                //if the trigger element is modified, reposition tooltip (hides if no longer exists or invisible)
                //if tooltip is modified, trigger reposition
                //this is admittedly inefficient, but it's only listening when the tooltip is open
                // Create a MutationObserver instance to replace the deprecated DOMSubtreeModified event
                helper.observer = new MutationObserver(function(mutations) {
                    // Call the positionTooltip method on DOM modifications
                    helper.positionTooltip();
                });

                // Configuration for the observer to listen to DOM modifications
                const config = { attributes: true, childList: true, subtree: true };

                // Start observing the body for DOM modifications
                helper.observer.observe(document.body, config);
            },
            //is this tooltip visible
            isVisible: function () {
                return $.inArray(helper, $.WordpressUlikeTooltip.visible) > -1;
            },
            //hide all visible tooltips
            hideAllVisible: function () {
                $.each($.WordpressUlikeTooltip.visible, function (index, WordpressUlikeTooltip) {
                    //if it's not a focus/hoverfocus tooltip with focus currently, hide it
                    if (!WordpressUlikeTooltip.dom_wrapped.hasClass('ulf-focused')) {
                        WordpressUlikeTooltip.hide();
                    }
                });
                return this;
            },
            //hides the tooltip for this element
            hide: function (trigger_event) {
                // Disconnect the MutationObserver to stop listening for DOM modifications
                if (helper.observer) {
                    helper.observer.disconnect();
                    helper.observer = null;
                }
                //remove scroll handler to reposition tooltip
                $(window).off('resize', helper.onResize);
                //remove accessbility props
                helper.dom.attr('aria-describedby', null);
                //remove from dom
                if (helper.tooltip && helper.tooltip.length) {
                    helper.tooltip.remove();
                }
                //trigger hide event
                if (typeof trigger_event === 'undefined' || trigger_event) {
                    helper.dom.trigger('ulf-hide');
                }
                //hide on click if not click
                if (helper.trigger !== 'click') {
                    helper.dom_wrapped.off('touchstart mousedown', helper.hide);
                }
                //remove from open array
                var index = $.inArray(helper, $.WordpressUlikeTooltip.visible);
                $.WordpressUlikeTooltip.visible.splice(index, 1);

                return helper.dom;
            },
            //on body resized
            onResize: function () {
                //hiding and showing the tooltip will update it's position
                helper.hide(false);
                helper.show(false);
            },
            //on click outside of the tooltip
            onClickOutside: function (e) {
                const target = $(e.target);
                if (!target.hasClass('ulf-tooltip') && !target.parents('.ulf-tooltip:first').length) {
                    $.each($.WordpressUlikeTooltip.visible, function (index, WordpressUlikeTooltip) {
                        if (typeof WordpressUlikeTooltip !== 'undefined') {
                            //if close on click AND target is NOT the trigger element OR it is the trigger element,
                            // but the trigger is not focus/hoverfocus (since on click focus is granted in those cases and the tooltip should be displayed)
                            if (WordpressUlikeTooltip.close_on_outside_click && (target !== WordpressUlikeTooltip.dom_wrapped || (WordpressUlikeTooltip.trigger !== 'focus' && WordpressUlikeTooltip.trigger !== 'hoverfocus'))) {
                                WordpressUlikeTooltip.hide();
                            }
                        }
                    });
                }
            },
            //position tooltip based on where the clicked element is
            positionTooltip: function () {

                helper.positionDebug('-- Start positioning --');

                //if no longer exists or is no longer visible
                if (!helper.dom_wrapped.length || !helper.dom_wrapped.is(":visible")) {
                    helper.positionDebug('Elem no longer exists. Removing tooltip');

                    helper.hide(true);
                }

                //cache reference to arrow
                let arrow = helper.tooltip.find('.ulf-arrow');

                //first try to fit it with the preferred position
                let [arrow_dir, elem_width, tooltip_width, tooltip_height, left, top] = helper.calculateSafePosition(helper.position);

                //if still couldn't fit, switch to auto
                if (typeof left === 'undefined' && helper.position !== 'auto') {
                    helper.positionDebug('Couldn\'t fit preferred position');
                    [arrow_dir, elem_width, tooltip_width, tooltip_height, left, top] = helper.calculateSafePosition('auto');
                }

                //fallback to centered (modal style)
                if (typeof left === 'undefined') {
                    helper.positionDebug('Doesn\'t appear to fit. Displaying centered');
                    helper.tooltip.addClass('ulf-centered').css({
                        'top': '50%',
                        'left': '50%',
                        'margin-left': -(tooltip_width / 2),
                        'margin-top': -(tooltip_height / 2)
                    });
                    if (arrow && arrow.length) {
                        arrow.remove();
                    }
                    helper.positionDebug('-- Done positioning --');
                    return;
                }

                //position the tooltip
                helper.positionDebug({ 'Setting Position': { 'Left': left, 'Top': top } });
                helper.tooltip.css('left', left);
                helper.tooltip.css('top', top);

                //arrow won't point at it if hugging side
                if (elem_width < 60) {
                    helper.positionDebug('Element is less than ' + elem_width + 'px. Setting arrow to hug the side tighter');
                    arrow_dir += ' ulf-arrow-super-hug';
                }

                //set the arrow location
                arrow.addClass('ulf-arrow-' + arrow_dir);

                helper.positionDebug('-- Done positioning --');

                return helper;
            },
            //detects where it will fit and returns the positioning info
            calculateSafePosition: function (position) {
                //cache reference to arrow
                let arrow = helper.tooltip.find('.ulf-arrow');

                //get position + size of clicked element
                let elem_position = helper.dom_wrapped.offset();
                let elem_height = helper.dom_wrapped.outerHeight();
                let elem_width = helper.dom_wrapped.outerWidth();

                //get tooltip dimensions
                let tooltip_width = helper.tooltip.outerWidth();
                let tooltip_height = helper.tooltip.outerHeight();

                //get window dimensions
                let window_width = document.querySelector('body').offsetWidth;
                let window_height = document.querySelector('body').offsetHeight;

                //get arrow size so we can pad
                let arrow_height = arrow.is(":visible") ? arrow.outerHeight() : 0;
                let arrow_width = arrow.is(":visible") ? arrow.outerWidth() : 0;

                //see where it fits in relation to the clicked element
                let fits = {};
                fits.below = (window_height - (tooltip_height + elem_height + elem_position.top)) > 5;
                fits.above = (elem_position.top - tooltip_height) > 5;
                fits.vertical_half = (elem_position.top + (elem_width / 2) - (tooltip_height / 2)) > 5;
                fits.right = (window_width - (tooltip_width + elem_width + elem_position.left)) > 5;
                fits.right_half = (window_width - elem_position.left - (elem_width / 2) - (tooltip_width / 2)) > 5;
                fits.right_full = (window_width - elem_position.left - tooltip_width) > 5;
                fits.left = (elem_position.left - tooltip_width) > 5;
                fits.left_half = (elem_position.left + (elem_width / 2) - (tooltip_width / 2)) > 5;
                fits.left_full = (elem_position.left - tooltip_width) > 5;

                //in debug mode, display all details
                helper.positionDebug({
                    'Clicked Element': { 'Left': elem_position.left, 'Top': elem_position.top },
                });
                helper.positionDebug({
                    'Element Dimensions': { 'Height': elem_height, 'Width': elem_width },
                    'Tooltip Dimensions': { 'Height': tooltip_height, 'Width': tooltip_width },
                    'Window Dimensions': { 'Height': window_height, 'Width': window_width },
                    'Arrow Dimensions': { 'Height': arrow_height, 'Width': arrow_width },
                });
                helper.positionDebug(fits);

                //vars we need for positioning
                let arrow_dir, left, top;

                if ((position === 'auto' || position === 'bottom') && fits.below && fits.left_half && fits.right_half) {
                    helper.positionDebug('Displaying below, centered');
                    arrow_dir = 'top';
                    left = elem_position.left - (tooltip_width / 2) + (elem_width / 2);
                    top = elem_position.top + elem_height + (arrow_height / 2);
                }
                else if ((position === 'auto' || position === 'top') && fits.above && fits.left_half && fits.right_half) {
                    helper.positionDebug('Displaying above, centered');
                    arrow_dir = 'bottom';
                    if (helper.child_class) {
                        let $child_element = helper.dom_wrapped.find(helper.child_class).first();
                        left = $child_element.offset().left - (tooltip_width / 2) + ($child_element.width() / 2);
                    } else {
                        left = elem_position.left - (tooltip_width / 2) + (elem_width / 2);
                    }
                    top = elem_position.top - tooltip_height - (arrow_height / 2);
                }
                else if ((position === 'auto' || position === 'left') && fits.left && fits.vertical_half) {
                    helper.positionDebug('Displaying left, centered');
                    arrow_dir = 'right';
                    left = elem_position.left - tooltip_width - (arrow_width / 2);
                    top = elem_position.top + (elem_height / 2) - (tooltip_height / 2);
                }
                else if ((position === 'auto' || position === 'right') && fits.right && fits.vertical_half) {
                    helper.positionDebug('Displaying right, centered');
                    arrow_dir = 'left';
                    left = elem_position.left + elem_width + (arrow_width / 2);
                    top = elem_position.top + (elem_height / 2) - (tooltip_height / 2);
                }
                else if ((position === 'auto' || position === 'bottom') && fits.below && fits.right_full) {
                    helper.positionDebug('Displaying below, to the right');
                    arrow_dir = 'top ulf-arrow-hug-left';
                    left = elem_position.left;
                    top = elem_position.top + elem_height + (arrow_height / 2);
                }
                else if ((position === 'auto' || position === 'bottom') && fits.below && fits.left_full) {
                    helper.positionDebug('Displaying below, to the left');
                    arrow_dir = 'top ulf-arrow-hug-right';
                    left = elem_position.left + elem_width - tooltip_width;
                    top = elem_position.top + elem_height + (arrow_height / 2);
                }
                else if ((position === 'auto' || position === 'top') && fits.above && fits.right_full) {
                    helper.positionDebug('Displaying above, to the right');
                    arrow_dir = 'bottom ulf-arrow-hug-left';
                    left = elem_position.left;
                    top = elem_position.top - tooltip_height - (arrow_height / 2);
                }
                else if ((position === 'auto' || position === 'top') && fits.above && fits.left_full) {
                    helper.positionDebug('Displaying above, to the left');
                    arrow_dir = 'bottom ulf-arrow-hug-right';
                    left = elem_position.left + elem_width - tooltip_width;
                    top = elem_position.top - tooltip_height - (arrow_height / 2);
                }

                return [arrow_dir, elem_width, tooltip_width, tooltip_height, left, top];
            },
            //if position_debug is enabled, let's console.log the details
            positionDebug: function (msg) {
                if (!helper.position_debug) {
                    return false;
                }

                return typeof msg === 'object' ? console.table(msg) : console.log(`Position: ${msg}`);
            }
        };

        helper.destroy();

        return helper.initialize();
    };

    $.WordpressUlikeTooltip = {};
    $.WordpressUlikeTooltip.visible = [];
    $.WordpressUlikeTooltip.body_click_initialized = false;
    $.WordpressUlikeTooltip.defaults = {
        id: Date.now(),
        title: '',
        trigger: 'hoverfocus',
        position: 'auto',
        class: '',
        theme: 'black',
        size: 'small',
        singleton: true,
        close_on_outside_click: true,
    }

})(jQuery);


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
      this.$messageElement.on('click', function () {
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
      if (self.settings.timeout) {
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


/* ================== assets/js/src/scripts.js =================== */


(function ($) {
  // Init ulike buttons
  $(".wpulike").WordpressUlike();

  /**
   * jquery detecting div of certain class has been added to DOM
   */
  function WordpressUlikeOnElementInserted(
    containerSelector,
    elementSelector,
    callback
  ) {
    var onMutationsObserved = function (mutations) {
      mutations.forEach(function (mutation) {
        if (mutation.addedNodes.length) {
          var elements = $(mutation.addedNodes).find(elementSelector);
          for (var i = 0, len = elements.length; i < len; i++) {
            callback(elements[i]);
          }
        }
      });
    };

    var target = $(containerSelector)[0];
    var config = {
      childList: true,
      subtree: true,
    };
    var MutationObserver =
      window.MutationObserver || window.WebKitMutationObserver;
    var observer = new MutationObserver(onMutationsObserved);
    observer.observe(target, config);
  }

  // On wp ulike element added
  WordpressUlikeOnElementInserted("body", ".wpulike", function (element) {
    $(element).WordpressUlike();
  });
})(jQuery);