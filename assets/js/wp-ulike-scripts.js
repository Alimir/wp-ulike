/**
 * WP ULike Plugin 2.2
 *
 * http://wordpress.org/plugins/wp-ulike/
 * https://github.com/Alimir/wp-ulike
 *
 */
jQuery(document).ready(function($) {
	//add button class in buddypress ulike button.
	$('.activity-content .wpulike .counter a').addClass("button");
	
	//start WP ULike process
	$(document).on('click', '.wp_ulike_btn',function(e) {
		var type 	= $(this).data('ulike-type');
		var status 	= $(this).data('ulike-status');
		var id 		= $(this).data('ulike-id');
		var p_class = $(e.target).parent();
		
		if (id != '') {
			//start AJAX
			jQuery.ajax({
			  type:'POST',
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
				if(status == 1){
				  p_class.html("<a data-ulike-id='"+id+"' data-ulike-type='"+type+"' data-ulike-status='2' class='wp_ulike_btn text button'>" + ulike_obj.text_after_like + "</a><span class='count-box'>"+data+"</span>");
				}
				if(status == 2){
				  if(ulike_obj.return_initial_after_unlike == 1){
					if(ulike_obj.button_type == 'image'){
					  p_class.html("<a data-ulike-id='"+id+"' data-ulike-type='"+type+"' data-ulike-status='1' class='wp_ulike_btn image button'></a><span class='count-box'>"+data+"</span>");
					} else {
					  p_class.html("<a data-ulike-id='"+id+"' data-ulike-type='"+type+"' data-ulike-status='1' class='wp_ulike_btn text button'>" + ulike_obj.button_text + "</a><span class='count-box'>"+data+"</span>");
					}
				  }
				  else{
					p_class.html("<a data-ulike-id='"+id+"' data-ulike-type='"+type+"' data-ulike-status='"+status+"' class='wp_ulike_btn text button'>" + ulike_obj.text_after_unlike + "</a><span class='count-box'>"+data+"</span>");
				  }
				}
				if(status == 3){
				  p_class.html("<a class='image button' title='You Liked This'></a><span class='count-box'>"+data+"</span>");
				}
				if(status == 4){
				  if(ulike_obj.button_type == 'image'){
					p_class.html("<a class='image button' title='You Liked This'></a><span class='count-box'>"+data+"</span>");	
				  }
				  else if(ulike_obj.button_type == 'text'){
					p_class.html("<a class='text button' title='You Liked This'>" + ulike_obj.button_text + "</a><span class='count-box'>"+data+"</span>");	
				  }
				}
			  }
			});
			//End Ajax
		}
	});
});