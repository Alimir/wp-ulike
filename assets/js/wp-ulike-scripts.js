/**
 * WP ULike Plugin 2.4.2
 *
 * http://wordpress.org/plugins/wp-ulike/
 * https://github.com/Alimir/wp-ulike
 *
 */
jQuery(document).ready(function($) {
	//start WP ULike process
	$(document).on('click', '.wp_ulike_btn',function(e) {
		var type 	= $(this).data('ulike-type');
		var status 	= $(this).data('ulike-status');
		var id 		= $(this).data('ulike-id');
		var uclass 	= $(this).data('ulike-class');
		var p_class = $(e.target).closest( "a" ).parent();
		
		if(ulike_obj.notifications == 1) {
			var liked 	= ulike_obj.like_notice;
			var unliked = ulike_obj.unlike_notice;
			toastr.options = {
			  "closeButton": false,
			  "debug": false,
			  "newestOnTop": false,
			  "progressBar": false,
			  "positionClass": "toast-bottom-right",
			  "preventDuplicates": false,
			  "showDuration": "300",
			  "hideDuration": "1000",
			  "timeOut": "5000",
			  "extendedTimeOut": "1000",
			  "showEasing": "swing",
			  "hideEasing": "linear",
			  "showMethod": "fadeIn",
			  "hideMethod": "fadeOut"
			}		
		}
		
		if (id != '') {
			//start AJAX
			jQuery.ajax({
			  type:'POST',
			  cache: false,
			  url: ulike_obj.ajaxurl,
			  data:{
				action:'wp_ulike_process',
				id: id,
				type: type
			  },
			  beforeSend:function(){
				p_class.html('<a class="loading"></a><span class="count-box">...</span>');
			  },			  
			  success: function(data) {
				var vardata = jQuery( data ).find( 'response_data' ).text();
				if(status == 1){
				  if(ulike_obj.button_type == 'image'){
				    p_class.html("<a data-ulike-id='"+id+"' data-ulike-type='"+type+"' data-ulike-status='2' class='wp_ulike_btn image-unlike'></a><span class='count-box'>"+vardata+"</span>");
				  } else {
				    p_class.html("<a data-ulike-id='"+id+"' data-ulike-type='"+type+"' data-ulike-status='2' class='wp_ulike_btn text'>" + ulike_obj.button_text_u + "</a><span class='count-box'>"+vardata+"</span>");
				  }
				  if( typeof liked !== 'undefined' && liked != ''){
					toastr.success(liked)
				  }	  
				}
				if(status == 2){
				  if(ulike_obj.button_type == 'image'){
				    p_class.html("<a data-ulike-id='"+id+"' data-ulike-type='"+type+"' data-ulike-status='1' class='wp_ulike_btn image'></a><span class='count-box'>"+vardata+"</span>");
				  } else {
				    p_class.html("<a data-ulike-id='"+id+"' data-ulike-type='"+type+"' data-ulike-status='1' class='wp_ulike_btn text'>" + ulike_obj.button_text + "</a><span class='count-box'>"+vardata+"</span>");
				  }
				  if( typeof unliked !== 'undefined' && unliked != ''){
					toastr.error(unliked)
				  }	  					
				}
				if(status == 3){
				  if(ulike_obj.button_type == 'image'){
				    p_class.html("<a class='image-unlike user-tooltip' title='Already Voted'></a><span class='count-box'>"+vardata+"</span>");
				  } else {
				    p_class.html("<a class='text user-tooltip' title='Already Voted'>" + ulike_obj.button_text_u + "</a><span class='count-box'>"+vardata+"</span>");
				  }
				  if( typeof liked !== 'undefined' && liked != ''){
					toastr.success(liked)
				  }
				}
				if(status == 4){
				  if(ulike_obj.button_type == 'image'){
					p_class.html("<a class='image' title='You Liked This'></a><span class='count-box'>"+vardata+"</span>");	
				  }
				  else{
					p_class.html("<a class='text' title='You Liked This'>" + ulike_obj.button_text + "</a><span class='count-box'>"+vardata+"</span>");	
				  }
				  if( typeof liked !== 'undefined' && liked != ''){
					toastr.success(liked)
				  }					
				}
			  }
			});
			//End Ajax
			e.preventDefault();
		}
	});
});