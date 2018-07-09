(function( $ ) {

  	$('.wp_ulike_delete').click(function(e) {
		e.preventDefault();
		var parent = $(this).closest('tr');
		var value  = $(this).data('id');
		var table  = $(this).data('table');
		var nonce  = $(this).data('nonce');
		var r      = confirm(wp_ulike_admin.logs_notif);
		if (r === true) {
			jQuery.ajax({
			  type:'POST',
			  url: ajaxurl,
			  data:{
				action:'ulikelogs',
				id    : value,
				nonce : nonce,
				table : table
			  },
			  beforeSend:function(){
				parent.css("background-color","#fff59d");
			  },
			  success: function( response ) {
			  	if( response.success ) {
			  		parent.fadeOut(300);
			  	} else {
			  		parent.css("background-color","#ef9a9a");
			  	}
			  }
			});
		}
	});

	// Charts stack array to save data
	var chartsData = [];

    $.fn.WpUlikeAjaxStats = function( method, value ){
			// local var
			var theResponse = null;
			// returnValue     = $.isPlainObject( value ) ? $.parseJSON( value ) : value;

	        // jQuery ajax
	        $.ajax({
	            type      :'POST',
	            dataType  : 'json',
	            url       : ajaxurl,
	            async     : false,
	            data      :{
					action: 'wp_ulike_ajax_stats',
					method: method,
					value : value,
					nonce : wp_ulike_admin.nonce_field
	            },
	            success   : function( response ){
	                if( response.success ) {
	                    theResponse = JSON.parse( response.data );
	                } else {
	                	theResponse = 0;
	                }
	            }
	        });
	        // Return the response text
	        return theResponse;
    };

    $.fn.WpUlikeLinearCharts = function( index, value ){
    	// Get chart element
		var $this      = $(this),
		sumStack       = 0,
		callback       = $this.data( 'callback' ),
		dataArgs       = $this.data( 'args' ),
		$ajaxElement   = $(this).closest( ".wp-ulike-is-loading" ),
		$canvasElement = $this.find( "canvas" ),
		theResponse    = $.fn.WpUlikeAjaxStats( callback, dataArgs );

		// If any error occurred, then continue
	  	if( !theResponse || theResponse.length === 0 ){
	  		$this.closest('.wp-ulike-summary-charts').remove();
	    	return; //this is equivalent of 'continue' for jQuery loop
		}

		// Create object of canvas
		var drawChart            = $canvasElement[0].getContext("2d");
		// Push data into datasets options
		theResponse.options['data'] = theResponse.data;

		new Chart(drawChart, {
		    // The type of chart we want to create
		    type: 'line',
		    // The data for our dataset
		    data: {
				labels  : theResponse.label,
				datasets: [
					theResponse.options
				]
			},
			options: {
				animation: false
			}
		});

		// Get the sum of total likes
		theResponse.data.forEach(function( num ){
			sumStack += parseFloat( num ) || 0;
		});
		// Upgrade chartsData array
		chartsData.push({
			type      : value,
			sum       : sumStack,
			label     : theResponse.options.label,
			background: theResponse.options.backgroundColor
		});

		// Remove loading spinner
		if( $ajaxElement.length ){
			$ajaxElement.removeClass( 'wp-ulike-is-loading' );
		}
    };

    $.fn.WpUlikeLogsCount = function(){
    	// Variables
		var callback = $(this).data( 'callback' ),
		dataArgs     = $(this).data( 'args' ),
		$ajaxElement = $(this).closest( ".wp-ulike-is-loading" ),
		theResponse  = $.fn.WpUlikeAjaxStats( callback, dataArgs );

		// Insert ajax data
		$(this).find('.wp-ulike-var').html( theResponse );

		// Remove loading spinner
		if( $ajaxElement.length ){
			$ajaxElement.removeClass( 'wp-ulike-is-loading' );
		}
    };

    $.fn.WpUlikePieCharts = function(){
		var $this      = $(this),
		$canvasElement = $this.find( "canvas" ),
		$ajaxElement   = $this.closest( ".wp-ulike-is-loading" );

		if( chartsData != null ) {

			var piechart  = $canvasElement[0].getContext("2d"),
			pieData       = [],
			pieBackground = [],
			pieLabels     = [];

			chartsData.forEach(function( value, key ){
				pieData.push( value.sum );
				pieBackground.push( value.background );
				pieLabels.push( value.label );
			});

			new Chart( piechart, {
			    // The type of chart we want to create
			    type: 'pie',
			    // The data for our dataset
			    data: {
				    datasets: [{
						data           : pieData,
						backgroundColor: pieBackground,
				    }],
				    // These labels appear in the legend and in the tooltips when hovering different arcs
					labels: pieLabels
				}
			});
			
		} else {
			$this.closest('.wp-ulike-percent-charts').remove();
		}

		// Remove loading spinner
		if( $ajaxElement.length ){
			$ajaxElement.removeClass( 'wp-ulike-is-loading' );
		}	

    };

    $.fn.WpUlikeStats = function(){

		$('.wp-ulike-ajax-get-var').each(function(){
			$(this).WpUlikeLogsCount();		
		});

		$('.wp-ulike-ajax-get-chart').each(function(){
			$(this).WpUlikeLinearCharts();		
		});

		$('.wp-ulike-draw-chart').WpUlikePieCharts();

		var $mapElement = $('#wp-ulike-maps');

		if( typeof $mapElement !== 'undefined' ){

			var world_map_data = $mapElement.WpUlikeAjaxStats( 'data_map', '' );

		  	if( world_map_data !== false ){
				$mapElement.vectorMap({
					map              : 'world_en',
					backgroundColor  : '#333333',
					color            : '#ffffff',
					hoverOpacity     : 0.7,
					selectedColor    : '#666666',
					enableZoom       : true,
					showTooltip      : true,
					values           : world_map_data,
					scaleColors      : ['#C8EEFF', '#006491'],
					normalizeFunction: 'polynomial',
					onLabelShow      : function (event, label, code) {
						if(world_map_data[code] > 0) {
							label.append(': '+world_map_data[code]+' Users');
						}
					}
				});
			}

			$mapElement.closest('.inside').removeClass( "wp-ulike-is-loading" );

		}
    };

    $(function(){

    	if( wp_ulike_admin.hook_address === 'wp-ulike_page_wp-ulike-statistics' ){
    		$(document).WpUlikeStats();
    	}

    });

})( jQuery );