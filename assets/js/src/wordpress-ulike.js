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
                    this._update( response );
                    $document.trigger('WordpressUlikeUpdated');
                }.bind(this)
            });
        },
        
        _update: function( response ){
            //remove loading class
            this.generalElement.removeClass( 'wp_ulike_is_loading' );
            //check likeStatus
            switch(this.settings.likeStatus) {
                case 1: /* Change the status of 'is not liked' to 'liked' */
                    this.buttonElement.attr('data-ulike-status', 4);
                    this.settings.likeStatus = 4;                   
                    this.generalElement.addClass( 'wp_ulike_is_liked' ).removeClass( 'wp_ulike_is_not_liked' );
                    this.generalElement.children().first().addClass( 'wp_ulike_click_is_disabled' );
                    this.counterElement.text( response.data );
                    this._actions( 'success', response.message, response.btnText, 4 );
                    break;
                case 2: /* Change the status of 'liked' to 'unliked' */
                    this.buttonElement.attr( 'data-ulike-status', 3 );
                    this.settings.likeStatus = 3;
                    this.generalElement.addClass( 'wp_ulike_is_unliked' ).removeClass('wp_ulike_is_liked');
                    this.counterElement.text( response.data );
                    this._actions( 'error', response.message, response.btnText, 3 );
                    break;
                case 3: /* Change the status of 'unliked' to 'liked' */
                    this.buttonElement.attr('data-ulike-status', 2);
                    this.settings.likeStatus = 2;
                    this.generalElement.addClass('wp_ulike_is_liked').removeClass('wp_ulike_is_unliked');
                    this.counterElement.text( response.data );
                    this._actions( 'success', response.message, response.btnText, 2 );
                    break;                  
                case 4: /* Just print the log-in warning message */
                    this._actions( 'info', response.message, response.btnText, 4 );
                    this.generalElement.children().first().addClass( 'wp_ulike_click_is_disabled' );
                    break;
                default: /* Just print the permission faild message */
                    this._actions( 'warning', response.message, response.btnText, 0 );
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
            //Check notifications active mode
            if(wp_ulike_params.notifications !== '1') return;
            //Set 'toastr' options
            toastr.options = {
              closeButton       : false,
              debug             : false,
              newestOnTop       : false,
              progressBar       : false,
              positionClass     : 'toast-bottom-right',
              preventDuplicates : false,
              showDuration      : 300,
              hideDuration      : 2000,
              timeOut           : 5000,
              extendedTimeOut   : 1000,
              showEasing        : 'swing',
              hideEasing        : 'linear',
              showMethod        : 'fadeIn',
              hideMethod        : 'fadeOut'
            }
            //Toast my notification
            toastr[messageType]( messageText );         
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