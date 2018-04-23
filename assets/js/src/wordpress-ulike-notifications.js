/* 'WordpressUlikeNotifications' plugin : https://github.com/alimir/wp-ulike */
;(function ( $, window, document, undefined ) {

    "use strict";

    // Create the defaults once
    var pluginName = "WordpressUlikeNotifications",
        defaults  = {
            messageType   : 'success', 
            messageText   : 'Hello World!', 
            messageElement: 'wpulike-message', 
            notifContainer: 'wpulike-notification'
        };
    // The actual plugin constructor
    function Plugin ( element, options ) {
        this.element   = element;
        this.$element  = $(element);
        this.settings  = $.extend( {}, defaults, options );
        this._defaults = defaults;
        this._name     = pluginName;
        this.init();
    }

    // Avoid Plugin.prototype conflicts
    $.extend(Plugin.prototype, {
        init: function () {
            // Crate Message Wrapper
            this._message();
            // Create Notification Container
            this._container();
            // Append Notification
            this._append();
            // Remove Notification
            this._remove();
        },

        _message: function(){
            this.$messageElement = $('<div/>').addClass( this.settings.messageElement + ' wpulike-' + this.settings.messageType ).text( this.settings.messageText );
        },

        _container: function(){
            // Make notification container if not exist
            if( !$( '.' + this.settings.notifContainer ).length ) {
                this.$element.append( $('<div/>').addClass( this.settings.notifContainer ) );
            }
            this.$notifContainer = this.$element.find( '.' + this.settings.notifContainer );
        },

        _append: function(){
            // Append Notification
            this.$notifContainer.append( this.$messageElement ).trigger('WordpressUlikeNotificationAppend');
        },

        _remove: function(){
            var self = this;
            // Remove Message On Click
            this.$messageElement.click( function(){
                $(this).fadeOut(300, function(){
                    $(this).remove();
                    if( !$( '.' + self.settings.messageElement ).length ) {
                        self.$notifContainer.remove();
                    }
                }).trigger('WordpressUlikeRemoveNotification');
            });
            // Remove Message With Timeout
            setTimeout(function() {
                self.$messageElement.fadeOut(300, function(){
                    $(this).remove();
                    if( !$( '.' + self.settings.messageElement ).length ) {
                        self.$notifContainer.remove();
                    }
                }).trigger('WordpressUlikeRemoveNotification');   
            }, 8000 );               
        }

    });

    // A really lightweight plugin wrapper around the constructor,
    // preventing against multiple instantiations
    $.fn[ pluginName ] = function ( options ) {
        return this.each(function() {
            new Plugin( this, options );
        });
    };

})( jQuery, window, document );