/*! WP ULike - v3.2
 *  https://wpulike.com
 *  Alimir 2018;
 */


/* ================== assets/js/src/wordpress-ulike-notifications.js =================== */


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


/* ================== assets/js/src/wordpress-ulike.js =================== */


/* 'WordpressUlike' plugin : https://github.com/alimir/wp-ulike */
;(function ( $, window, document, undefined ) {

    "use strict";

    // Create the defaults once
    var pluginName = "WordpressUlike",
        $window   = $(window),
        $document = $(document),
        defaults  = {
            ID             : 0, /*  Auto ID value by element type */
            nonce          : 0, /*  Get nonce token */
            type           : '', /* Values : likeThis (Posts),likeThisComment, likeThisActivity, likeThisTopic */
            likeStatus     : 0, /* Values : 0 (Is not logged-in), 1 (Is not liked), 2 (Is liked), 3 (Is unliked), 4 (Already liked) */
            counterSelector: wp_ulike_params.counter_selector, /* You can change this value by add filter on 'wp_ulike_counter_selector' */
            generalSelector: wp_ulike_params.general_selector, /* You can change this value by add filter on 'wp_ulike_general_selector' */
            buttonSelector : wp_ulike_params.button_selector /* You can change this value by add filter on 'wp_ulike_button_selector' */
        },
        attributesMap = {
            'ulike-id'    : 'ID',
            'ulike-nonce' : 'nonce',
            'ulike-type'  : 'type',
            'ulike-status': 'likeStatus'
        };

    // The actual plugin constructor
    function Plugin ( element, options ) {
        this.element = element;
        this.$element = $(element);
        this.settings = $.extend( {}, defaults, options );
        this._defaults = defaults;
        this._name = pluginName;
        
        // Create main selectors
        this.buttonElement  = this.$element.find(this.settings.buttonSelector);
        this.generalElement = this.$element.find(this.settings.generalSelector);
        this.counterElement = this.generalElement.find( this.settings.counterSelector );

        // read attributes
        for ( var attrName in attributesMap ) {
            var value = this.buttonElement.data( attrName );
            if ( value !== undefined ) {
                this.settings[attributesMap[attrName]] = value;
            }
        }
        this.init();
    }

    // Avoid Plugin.prototype conflicts
    $.extend(Plugin.prototype, {
        init: function () {
            //Call _ajaxify function on click button
            this.buttonElement.click( this._ajaxify.bind(this) );
        },
        
        _ajaxify: function(){
            $.ajax({
                type:'POST',
                cache: false,
                dataType: 'json',
                url: wp_ulike_params.ajax_url,
                data:{
                    action: 'wp_ulike_process',
                    id: this.settings.ID,
                    nonce: this.settings.nonce,
                    status: this.settings.likeStatus,
                    type: this.settings.type
                },
                beforeSend:function(){
                    $document.trigger('WordpressUlikeLoading');
                    this.generalElement.addClass( 'wp_ulike_is_loading' );
                }.bind(this),
                success: function( response ){
                    //remove loading class
                    this.generalElement.removeClass( 'wp_ulike_is_loading' );                    
                    if( response.success ) {
                        this._update( response );
                    } else {
                        this._notif( 'error', response.data );
                    }
                    
                    $document.trigger('WordpressUlikeUpdated');
                }.bind(this)
            });
        },
        
        _update: function( response ){
            //check likeStatus
            switch(this.settings.likeStatus) {
                case 1: /* Change the status of 'is not liked' to 'liked' */
                    this.buttonElement.attr('data-ulike-status', 4);
                    this.settings.likeStatus = 4;                   
                    this.generalElement.addClass( 'wp_ulike_is_liked' ).removeClass( 'wp_ulike_is_not_liked' );
                    this.generalElement.children().first().addClass( 'wp_ulike_click_is_disabled' );
                    this.counterElement.text( response.data.data );
                    this._actions( 'success', response.data.message, response.data.btnText, 4 );
                    break;
                case 2: /* Change the status of 'liked' to 'unliked' */
                    this.buttonElement.attr( 'data-ulike-status', 3 );
                    this.settings.likeStatus = 3;
                    this.generalElement.addClass( 'wp_ulike_is_unliked' ).removeClass('wp_ulike_is_liked');
                    this.counterElement.text( response.data.data );
                    this._actions( 'error', response.data.message, response.data.btnText, 3 );
                    break;
                case 3: /* Change the status of 'unliked' to 'liked' */
                    this.buttonElement.attr('data-ulike-status', 2);
                    this.settings.likeStatus = 2;
                    this.generalElement.addClass('wp_ulike_is_liked').removeClass('wp_ulike_is_unliked');
                    this.counterElement.text( response.data.data );
                    this._actions( 'success', response.data.message, response.data.btnText, 2 );
                    break;                  
                case 4: /* Just print the log-in warning message */
                    this._actions( 'info', response.data.message, response.data.btnText, 4 );
                    this.generalElement.children().first().addClass( 'wp_ulike_click_is_disabled' );
                    break;
                default: /* Just print the permission faild message */
                    this._actions( 'warning', response.data.message, response.data.btnText, 0 );
            }
        },
        
        _actions: function( messageType, messageText, btnText, likeStatus ){
            //check the button types
            if( wp_ulike_params.button_type === 'image' ) {
                if( likeStatus === 3 || likeStatus === 2){
                    this.buttonElement.toggleClass('image-unlike');
                }
            } else if( wp_ulike_params.button_type === 'text' ) {
                this.buttonElement.find('span').html(btnText);
            }
            
            // Display Notifications
            this._notif( messageType, messageText );
        },

        _notif: function( messageType, messageText ){
            //Check notifications active mode
            if(wp_ulike_params.notifications !== '1') return;
            // Display Notification
            $(document.body).WordpressUlikeNotifications({
                messageType   : messageType, 
                messageText   : messageText
            });
        }

    });

    // A really lightweight plugin wrapper around the constructor,
    // preventing against multiple instantiations
    $.fn[ pluginName ] = function ( options ) {
        return this.each(function() {
            if ( !$.data( this, "plugin_" + pluginName ) ) {
                $.data( this, "plugin_" + pluginName, new Plugin( this, options ) );
            }
        });
    };

})( jQuery, window, document );


/* ================== assets/js/src/scripts.js =================== */


/* Run :) */
;(function($){

    // on document ready
    $(function(){
        // Upgrading 'WordpressUlike' datasheets when new DOM has been inserted
        $(this).bind('DOMNodeInserted', function(e) {
            $(".wpulike").WordpressUlike();
        });
    });
    
    // init WordpressUlike
    $(".wpulike").WordpressUlike();

    // removes "empty" paragraphs
    $('p').filter(function () { return this.innerHTML == "" }).remove();

})( jQuery );