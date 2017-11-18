(function( $ ) {

  	$('.wp_ulike_delete').click(function(e) {
		e.preventDefault();
		var parent = $(this).closest('tr');  
		var value=$(this).data('id');
		var table=$(this).data('table');
		var r = confirm(wp_ulike_logs.message);
		if (r === true) {
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

	if ( typeof wp_ulike_statistics === "undefined" ) return;

	var posts_dataset_var 		= JSON.parse(wp_ulike_statistics.posts_dataset);
	var comments_dataset_var 	= JSON.parse(wp_ulike_statistics.comments_dataset);
	var activities_dataset_var 	= JSON.parse(wp_ulike_statistics.activities_dataset);
	var topics_dataset_var 		= JSON.parse(wp_ulike_statistics.topics_dataset);
	var world_map_data 			= JSON.parse(wp_ulike_statistics.data_map);
	var activities_dataset_sum  = 0;
	var topics_dataset_sum  	= 0;
	var comments_dataset_sum 	= 0;
	var posts_dataset_sum  		= 0;
	//posts dataset
	if(posts_dataset_var  !== null){
		for (var postNum = 0; postNum < posts_dataset_var.length; postNum++) {
			posts_dataset_sum += parseInt(posts_dataset_var[postNum]);
		}
		var posts_date = {
			labels : JSON.parse(wp_ulike_statistics.posts_date_labels),
			datasets : [
				{
					label: "Liked Posts",
					data : posts_dataset_var,
					backgroundColor: "rgba(66, 165, 245,0.8)",
					borderColor: "rgba(21, 101, 192,1)",
					pointBackgroundColor: "rgba(255,255,255,1)",
					borderWidth: 1
				}
			]
		};
	}

	//comments dataset
	if(comments_dataset_var  !== null){
		for (var commentNum = 0; commentNum < comments_dataset_var.length; commentNum++) {
			comments_dataset_sum += parseInt(comments_dataset_var[commentNum]);
		}
		var comments_date = {
			labels : JSON.parse(wp_ulike_statistics.comments_date_labels),
			datasets : [
				{
					label: "Liked Comments",
					data : comments_dataset_var,
					backgroundColor : "rgba(255, 202, 40,0.8)",
					borderColor : "rgba(255, 143, 0,1)",
					pointBackgroundColor: "rgba(255,255,255,1)",
					borderWidth: 1
					
				}
			]
		};
	}


	//activities dataset
	if(activities_dataset_var  !== null){
		for (var activityNum = 0; activityNum < activities_dataset_var.length; activityNum++) {
			activities_dataset_sum += parseInt(activities_dataset_var[activityNum]);
		}
		var activities_date = {
			labels : JSON.parse(wp_ulike_statistics.activities_date_labels),
			datasets : [
				{
					label: "Liked Activities",
					data : activities_dataset_var,
					backgroundColor: "rgba(239, 83, 80,0.8)",
					borderColor: "rgba(198, 40, 40,1)",
					pointBackgroundColor: "rgba(255,255,255,1)",
					borderWidth: 1
				}
			]
		};
	}

	//Topics dataset
	if(topics_dataset_var  !== null){
		for (var topicNum = 0; topicNum < topics_dataset_var.length; topicNum++) {
			topics_dataset_sum += parseInt(topics_dataset_var[topicNum]);
		}
		var topics_date = {
			labels : JSON.parse(wp_ulike_statistics.topics_date_labels),
			datasets : [
				{
					label: "Liked Topics",
					data : topics_dataset_var,
					backgroundColor: "rgba(102, 187, 106,0.8)",
					borderColor: "rgba(27, 94, 32,1)",
					pointBackgroundColor: "rgba(255,255,255,1)",
					borderWidth: 1
				}
			]
		};
	}

	var pieData = {
	    datasets: [{
	        data: [
	        	posts_dataset_sum,
	        	comments_dataset_sum,
	        	activities_dataset_sum,
	        	topics_dataset_sum
	        ],
            backgroundColor: [
                "#42a5f5",
                "#ffca28",
                "#F7464A",
                "#66bb6a",
            ],	        
	    }],

	    // These labels appear in the legend and in the tooltips when hovering different arcs
	    labels: [
	        'Posts',
	        'Comments',
	        'Activities',
	        'Topics',
	    ]		
	};


	var postsChart 		= document.getElementById('chart1');
	var commentsChart 	= document.getElementById('chart2');
	var activitiesChart	= document.getElementById('chart3');
	var topicsChart		= document.getElementById('chart4');
	var allocationChart = document.getElementById('piechart');
	
	if ( postsChart !== null ) {
		if( posts_dataset_var  !== null ){
			var ctx1 = postsChart.getContext("2d");
			postsChart = new Chart(ctx1, {
			    // The type of chart we want to create
			    type: 'line',
			    // The data for our dataset
			    data: posts_date
			});			
		}else{
			document.getElementById("posts_likes_stats").getElementsByClassName("main")[0].innerHTML = "No Data Found!";		
		}
	}
	
	if ( commentsChart !== null ) {
		if( comments_dataset_var  !== null ){
			var ctx2 = commentsChart.getContext("2d");
			commentsChart = new Chart(ctx2, {
			    // The type of chart we want to create
			    type: 'line',
			    // The data for our dataset
			    data: comments_date
			});			
		}else{
			document.getElementById("comments_likes_stats").getElementsByClassName("main")[0].innerHTML = "No Data Found!";		
		}
	}
	
	if ( activitiesChart !== null ) {
		if( activities_dataset_var  !== null ){
			var ctx3 = activitiesChart.getContext("2d");
			activitiesChart = new Chart(ctx3, {
			    // The type of chart we want to create
			    type: 'line',
			    // The data for our dataset
			    data: activities_date
			});				
		}else{
			document.getElementById("activities_likes_stats").getElementsByClassName("main")[0].innerHTML = "No Data Found!";		
		}
	}
	
	if ( topicsChart !== null ) {
		if( topics_dataset_var  !== null ){
			var ctx4 = topicsChart.getContext("2d");
			topicsChart = new Chart(ctx4, {
			    // The type of chart we want to create
			    type: 'line',
			    // The data for our dataset
			    data: topics_date
			});				
		}else{
			document.getElementById("topics_likes_stats").getElementsByClassName("main")[0].innerHTML = "No Data Found!";		
		}
	}
	
	if( allocationChart !== null ) {
		if( activities_dataset_var  !== null || topics_dataset_var  !== null || comments_dataset_var  || null && posts_dataset_var  || null ){
			var ctx5 = allocationChart.getContext("2d");
			allocationChart = new Chart(ctx5, {
			    // The type of chart we want to create
			    type: 'pie',
			    // The data for our dataset
			    data: pieData
			});				
		}else{
			document.getElementById("piechart_stats").getElementsByClassName("main")[0].innerHTML = "No Data Found!";		
		}
	}	

	jQuery('#vmap').vectorMap({
	    map 				: 'world_en',
	    backgroundColor 	: '#333333',
	    color 				: '#ffffff',
	    hoverOpacity 		: 0.7,
	    selectedColor 		: '#666666',
	    enableZoom 			: true,
	    showTooltip  		: true,
	    values  			: world_map_data,
	    scaleColors 		: ['#C8EEFF', '#006491'],
	    normalizeFunction	: 'polynomial',
		onLabelShow 		: function (event, label, code) {
			if(world_map_data[code] > 0) {
				label.append(': '+world_map_data[code]+' Users'); 
			}
		}			
	});	

	postboxes.save_state = function(){
		return;
	};
	postboxes.save_order = function(){
		return;
	};
	postboxes.add_postbox_toggles();
	
})( jQuery );