	var posts_dataset_var = JSON.parse(wp_ulike_statistics.posts_dataset);
	var comments_dataset_var = JSON.parse(wp_ulike_statistics.comments_dataset);
	var activities_dataset_var = JSON.parse(wp_ulike_statistics.activities_dataset);
	var topics_dataset_var = JSON.parse(wp_ulike_statistics.topics_dataset);
	var world_map_data = JSON.parse(wp_ulike_statistics.data_map);
	var activities_dataset_sum = topics_dataset_sum = comments_dataset_sum = posts_dataset_sum = 0;
	
	//posts dataset
	if(posts_dataset_var  !== null){
	for (var i = 0; i < posts_dataset_var.length; i++) {
		posts_dataset_sum += parseInt(posts_dataset_var[i]);
	}
	var posts_date = {
		labels : JSON.parse(wp_ulike_statistics.posts_date_labels),
		datasets : [
			{
				label: "Posts Likes Stats",
				fillColor: "rgba(151,187,205,0.2)",
				strokeColor: "rgba(151,187,205,1)",
				pointColor: "rgba(151,187,205,1)",
				pointStrokeColor : "#fff",
				pointHighlightFill : "#fff",
				pointHighlightStroke : "rgba(220,220,220,1)",
				data : posts_dataset_var
			}
		]
	}
	}
	
	//comments dataset
	if(comments_dataset_var  !== null){
	for (var i = 0; i < comments_dataset_var.length; i++) {
		comments_dataset_sum += parseInt(comments_dataset_var[i]);
	}
	var comments_date = {
		labels : JSON.parse(wp_ulike_statistics.comments_date_labels),
		datasets : [
			{
				label: "Comments Likes Stats",
				fillColor : "rgba(253,180,92,0.2)",
				strokeColor: "rgba(255,200,112,1)",
				pointColor : "rgba(255,200,112,1)",
				pointStrokeColor : "#fff",
				pointHighlightFill : "#fff",
				pointHighlightStroke : "rgba(255,200,112,1)",
				data : comments_dataset_var
			}
		]
	}
	}
	
	
	//activities dataset
	if(activities_dataset_var  !== null){
	for (var i = 0; i < activities_dataset_var.length; i++) {
		activities_dataset_sum += parseInt(activities_dataset_var[i]);
	}
	var activities_date = {
		labels : JSON.parse(wp_ulike_statistics.activities_date_labels),
		datasets : [
			{
				label: "Activities Likes Stats",
				fillColor: "rgba(231,79,64,0.2)",
				strokeColor: "rgba(247,70,74,1)",
				pointColor: "rgba(247,70,74,1)",
				pointStrokeColor : "#fff",
				pointHighlightFill : "#fff",
				pointHighlightStroke : "rgba(247,70,74,1)",
				data : activities_dataset_var
			}
		]
	}
	}
	
	//Topics dataset
	if(topics_dataset_var  !== null){
	for (var i = 0; i < topics_dataset_var.length; i++) {
		topics_dataset_sum += parseInt(topics_dataset_var[i]);
	}
	var topics_date = {
		labels : JSON.parse(wp_ulike_statistics.topics_date_labels),
		datasets : [
			{
				label: "Topics Likes Stats",
				fillColor: "rgba(141,199,112,0.2)",
				strokeColor: "rgba(102,153,102,1)",
				pointColor: "rgba(102,153,102,1)",
				pointStrokeColor : "#fff",
				pointHighlightFill : "#fff",
				pointHighlightStroke : "rgba(102,153,102,1)",
				data : topics_dataset_var
			}
		]
	}
	}
	
	var pieData = [
			{
				value: posts_dataset_sum,
				color:"#5cc6fd",
				highlight: "#7dd1fd",
				label: "Posts"
			},
			{
				value: comments_dataset_sum,
				color: "#FDB45C",
				highlight: "#FFC870",
				label: "Comment"
			},
			{
				value: activities_dataset_sum,
				color: "#F7464A",
				highlight: "#FF5A5E",
				label: "Activities"
			},
			{
				value: topics_dataset_sum,
				color: "#8DC770",
				highlight: "#696",
				label: "Topics"
			}
		];

	(function(){
		var chart1 		= document.getElementById('chart1');
		var chart2 		= document.getElementById('chart2');
		var chart3		= document.getElementById('chart3');
		var chart4		= document.getElementById('chart4');
		var piechart 	= document.getElementById('piechart');
		
		if (chart1 != null) {
			if(posts_dataset_var  !== null){
				var ctx1 = chart1.getContext("2d");
				new Chart(ctx1).Line(posts_date, {
					responsive: true
				});
			}else{
				document.getElementById("posts_likes_stats").getElementsByClassName("main")[0].innerHTML = "No Data Found!";		
			}
		}
		
		if (chart2 != null) {
			if(comments_dataset_var  !== null){
				var ctx2 = chart2.getContext("2d");
				new Chart(ctx2).Line(comments_date, {
					responsive: true
				});
			}else{
				document.getElementById("comments_likes_stats").getElementsByClassName("main")[0].innerHTML = "No Data Found!";		
			}
		}
		
		if (chart3 != null) {
			if(activities_dataset_var  !== null){
				var ctx3 = chart3.getContext("2d");
				new Chart(ctx3).Line(activities_date, {
					responsive: true
				});
			}else{
				document.getElementById("activities_likes_stats").getElementsByClassName("main")[0].innerHTML = "No Data Found!";		
			}
		}
		
		if (chart4 != null) {
			if(topics_dataset_var  !== null){
				var ctx3 = chart4.getContext("2d");
				new Chart(ctx3).Line(topics_date, {
					responsive: true
				});
			}else{
				document.getElementById("topics_likes_stats").getElementsByClassName("main")[0].innerHTML = "No Data Found!";		
			}
		}
		
		if (piechart != null) {
			if(activities_dataset_var  !== null || topics_dataset_var  !== null || comments_dataset_var  || null && posts_dataset_var  || null){
				var ctx4 = piechart.getContext("2d");
				new Chart(ctx4).Pie(pieData, {
					responsive: true
				});
			}else{
				document.getElementById("piechart_stats").getElementsByClassName("main")[0].innerHTML = "No Data Found!";		
			}
		}
		
	})();
	

	jQuery(document).on('ready', function($){
	
		jQuery('#vmap').vectorMap({
		    map: 'world_en',
		    backgroundColor: '#333333',
		    color: '#ffffff',
		    hoverOpacity: 0.7,
		    selectedColor: '#666666',
		    enableZoom: true,
		    showTooltip: true,
		    values: world_map_data,
		    scaleColors: ['#C8EEFF', '#006491'],
		    normalizeFunction: 'polynomial',
			onLabelShow: function (event, label, code) {
				if(world_map_data[code] > 0)
					label.append(': '+world_map_data[code]+' Users'); 
			}			
		});	
	
		postboxes.save_state = function(){
			return;
		};
		postboxes.save_order = function(){
			return;
		};
		postboxes.add_postbox_toggles();
	});