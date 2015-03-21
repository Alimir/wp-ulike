jQuery(document).ready(function($) {
  $('.wp_ulike_delete').click(function(e) {
	e.preventDefault();
	var parent = $(this).closest('tr');  
	var value=$(this).data('id');
	var table=$(this).data('table');
	var r = confirm(wp_ulike_logs.message);
		if (r == true) {
			jQuery.ajax({
			  type:'POST',
			  url: wp_ulike_logs.ajaxurl,
			  data:{
				action:'ulikelogs',
				id: value,
				table: table
			  },
			  beforeSend:function(){
				parent.css("background-color","yellow");
			  },			  
			  success: function(data) {
				parent.fadeOut(300);
			  }
			});
		}	
	});
});