/*!
 * Radio image select
 **/
(function($) {
    // Register jQuery plugin.
    $.fn.radioImageSelect = function(options) {
        // Default var for options.
        var defaults = {
                // Img class.
                imgItemClass: 'radio-img-item',
                // Img Checked class.
                imgItemCheckedClass: 'item-checked',
                // Is need hide label connected?
                hideLabel: true
            },

            /**
             * Method firing when need to update classes.
             */
            syncClassChecked = function(img) {
                var radioName = img.prev('input[type="radio"]').attr('name');

                $('input[name="' + radioName + '"]').each(function() {
                    // Define img by radio name.
                    var myImg = $(this).next('img');

                    // Add / Remove Checked class.
                    if ($(this).prop('checked')) {
                        myImg.addClass(options.imgItemCheckedClass);
                    } else {
                        myImg.removeClass(options.imgItemCheckedClass);
                    }
                });
            };

        // Parse args..
        options = $.extend(defaults, options);

        // Start jQuery loop on elements..
        return this.each(function() {
            $(this)
                // First all we are need to hide the radio input.
                .hide()
                // And add new img element by data-image source.
                .after('<img src="' + $(this).data('image') + '" alt="radio image" />');

            // Define the new img element.
            var img = $(this).next('img');
            // Add item class.
            img.addClass(options.imgItemClass);

            // Check if need to hide label connected.
            if (options.hideLabel) {
                $('label[for=' + $(this).attr('id') + ']').hide();
            }

            // When we are created the img and radio get checked, we need add checked class.
            if ($(this).prop('checked')) {
                img.addClass(options.imgItemCheckedClass);
            }

            // Create click event on img element.
            img.on('click', function(e) {
                $(this)
                    // Prev to current radio input.
                    .prev('input[type="radio"]')
                    // Set checked attr.
                    .prop('checked', true)
                    // Run change event for radio element.
                    .trigger('change');

                // Firing the sync classes.
                syncClassChecked($(this));
            });
        });
    }
})(jQuery);