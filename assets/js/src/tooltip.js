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

        //if toggle on hover, no backdrop
        if (options.trigger !== 'click') {
            options.backdrop = false;
        }

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
            backdrop: options.backdrop,
            position: options.position,
            close_on_outside_click: options.close_on_outside_click,
            singleton: options.singleton,
            dataAttr: 'ulike-tooltip',
            //create tooltip html
            createTooltipHTML: function () {
                return `<div class='ulf-tooltip ${helper.class}' role='tooltip'><div class='ulf-arrow'></div><div class='ulf-content'>${helper.content}</div></div>`;
            },
            //creates backdrop html if necessary
            createBackdropHTML: function () {
                return helper.backdrop ? `<div class='ulf-backdrop ulf-${helper.backdrop}-backdrop'></div>` : false;
            },
            //disable existing options/handlers
            destroy: function () {
                //only if it's actually tied to this element
                const existing = helper.dom_wrapped.data(helper.dataAttr);
                if (typeof existing !== 'undefined' && existing !== null) {
                    if (existing.trigger === 'click') {
                        //disable handler
                        existing.dom_wrapped.off('touchstart mousedown', existing.toggleTooltipHandler);
                        existing.dom_wrapped.off('click', existing.preventDefaultHandler);
                    }
                    else if (existing.trigger === 'focus') {
                        existing.dom_wrapped.off('touchstart focus', existing.show);
                        existing.dom_wrapped.off('touchend blur', existing.hide);
                    }
                    else if (existing.trigger === 'hover') {
                        existing.dom_wrapped.off('touchstart mouseenter', existing.show);
                        existing.dom_wrapped.off('click', existing.preventDefaultHandler);
                    }
                    else if (existing.trigger === 'hoverfocus') {
                        existing.dom_wrapped.off('focus', existing.hoverfocusFocusShow);
                        existing.dom_wrapped.off('blur', existing.hoverfocusBlur);

                        existing.dom_wrapped.off('touchstart mouseenter', existing.show);
                        existing.dom_wrapped.off('touchend mouseleave', existing.hoverfocusHide);
                    }
                    else if (existing.trigger === 'hoverclick') {
                        existing.dom_wrapped.off('touchstart click', existing.toggleTooltipHandler);
                        existing.dom_wrapped.off('mouseenter', existing.show);
                    }

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
                if (helper.trigger === 'click') {
                    helper.dom_wrapped.on('touchstart mousedown', helper.toggleTooltipHandler);
                    helper.dom_wrapped.on('click', helper.preventDefaultHandler);
                }
                else if (helper.trigger === 'focus') {
                    helper.dom_wrapped.on('touchstart focus', helper.show);
                    helper.dom_wrapped.on('touchend blur', helper.hide);
                }
                else if (helper.trigger === 'hover') {
                    helper.dom_wrapped.on('touchstart mouseenter', helper.show);
                    helper.dom_wrapped.on('click', helper.preventDefaultHandler);
                    // helper.dom_wrapped.on('touchend mouseleave', helper.hide);
                }
                else if (helper.trigger === 'hoverfocus') {
                    helper.dom_wrapped.on('focus', helper.hoverfocusFocusShow);
                    helper.dom_wrapped.on('blur', helper.hoverfocusBlur);

                    helper.dom_wrapped.on('touchstart mouseenter', helper.show);
                    // helper.dom_wrapped.on('touchend mouseleave', helper.hoverfocusHide);
                    helper.dom_wrapped.on('click', helper.preventDefaultHandler);
                }
                else if (helper.trigger === 'hoverclick') {
                    helper.dom_wrapped.on('touchstart click', helper.toggleTooltipHandler);
                    helper.dom_wrapped.on('mouseenter', helper.show);
                    helper.dom_wrapped.on('touchend', helper.hoverfocusHide);
                }

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
            //add class when focused for hoverfocus - this way mouseleave can detect if focused or not
            //document.activeElement isn't working to detect focus
            hoverfocusFocusShow: function () {
                helper.dom_wrapped.addClass('ulf-focused');
                helper.show();
            },
            //remove class on blur for hoverfocus
            hoverfocusBlur: function () {
                if (helper.dom_wrapped && helper.dom_wrapped.length) {
                    helper.dom_wrapped.removeClass('ulf-focused');
                }
                helper.hide();
            },
            //prevent hiding a tooltip on mouseleave if the element is still focused
            hoverfocusHide: function () {
                //don't hide if focused. Hide on blur instead.
                if (helper.dom_wrapped.hasClass('ulf-focused')) {
                    return false;
                }
                helper.hide();
            },
            //on click of element, prevent default
            preventDefaultHandler: function (e) {
                e.preventDefault();
                return false;
            },
            //toggle tooltip visibility (used for click event)
            toggleTooltipHandler: function (e) {
                e.preventDefault();
                helper.isVisible() && helper.hide() || helper.show();
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

                //blurred won't work like the standard separate div backdrop
                //it has to be applied directly to the dom we're blurring
                if (helper.backdrop === 'blurred') {
                    body.addClass('ulf-blurred-body');
                }
                //if regular backdrop, append the div
                else if (helper.backdrop) {
                    body.append(helper.createBackdropHTML());
                }
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
                $('body').on('DOMSubtreeModified', helper.positionTooltip);
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
                //remove the dom modification handler
                $('body').off('DOMSubtreeModified', helper.positionTooltip);
                //remove scroll handler to reposition tooltip
                $(window).off('resize', helper.onResize);
                //remove accessbility props
                helper.dom.attr('aria-describedby', null);
                //remove from dom
                if (helper.tooltip && helper.tooltip.length) {
                    helper.tooltip.remove();
                }
                //remove blurring to body
                if (helper.backdrop === 'blurred') {
                    $('body').removeClass('ulf-blurred-body');
                }
                //remove backdrop
                else if (helper.backdrop) {
                    $('.ulf-backdrop').remove();
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
        backdrop: false,
        singleton: true,
        close_on_outside_click: true,
    }

})(jQuery);