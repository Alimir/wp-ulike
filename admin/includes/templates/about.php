<?php
/**
 * About page template
 * // @echo HEADER
 */

	// no direct access allowed
	if ( ! defined('ABSPATH') ) {
	    die();
	}
?>

<div class="wrap about-wrap wp-ulike-about-page full-width-layout">

	<h1><?php echo _e( 'Welcome to WP ULike', WP_ULIKE_SLUG ) . ' ' . WP_ULIKE_VERSION; ?></h1>
	<div class="about-text"><?php echo _e('Thank you for choosing WP ULike! This version is our leanest and most powerful version yet.', WP_ULIKE_SLUG) ; ?><br />

	<?php add_thickbox(); ?>
	<a target="_blank" href="<?php echo WP_ULIKE_PLUGIN_URI . '?TB_iframe=true&amp;width=800&amp;height=600'; ?>" class="thickbox"> <?php _e('Visit our homepage',WP_ULIKE_SLUG); ?></a>
	</div>
	<div class="ulike-badge"><?php echo _e('Version',WP_ULIKE_SLUG) . ' ' . WP_ULIKE_VERSION; ?></div>
	<h2 class="nav-tab-wrapper wp-clearfix">
		<a class="nav-tab <?php if(!isset($_GET["credit"])) echo 'nav-tab-active'; ?>" href="admin.php?page=wp-ulike-about"><?php echo _e('Getting Started',WP_ULIKE_SLUG); ?></a>
		<a class="nav-tab <?php if(isset($_GET["credit"])) echo 'nav-tab-active'; ?>" href="admin.php?page=wp-ulike-about&credit=true"><?php echo _e('Credits',WP_ULIKE_SLUG); ?></a>
		<a target="_blank" class="nav-tab" href="https://wordpress.org/support/plugin/wp-ulike"><?php echo _e('Support',WP_ULIKE_SLUG); ?></a>
		<a target="_blank" class="nav-tab" href="https://wordpress.org/plugins/wp-ulike/faq/"><?php echo _e('FAQ',WP_ULIKE_SLUG); ?></a>
		<a target="_blank" class="nav-tab" href="https://wordpress.org/support/view/plugin-reviews/wp-ulike"><?php echo _e('Reviews',WP_ULIKE_SLUG); ?></a>
	</h2>

	<?php if(!isset($_GET["credit"])): ?>

	<div class="headline-feature">
		<h2><?php echo _e( 'A Little Better Every Day', WP_ULIKE_SLUG ); ?></h2>
		<p class="lead-description"><?php echo _e('If you’re looking for one of the best and fastest ways to add like and dislike functionality to your WordPress website, then the WP ULike plugin is for you! WP ULike will allow your website visitors to engage with your wide range of content types including posts, forum topics and replies, comments and activity updates.',WP_ULIKE_SLUG); ?></p>
	</div>

	<div class="feature-section is-wide has-1-columns">
		<div class="column">
			<div class="inline-svg">
				<img src="<?php echo WP_ULIKE_ASSETS_URL; ?>/img/stats-plan.png" alt="Statistics Planning" />
			</div>
			<p><?php echo _e('WP ULike’s amazing statistics tools help you to track your content effectiveness with a real-time report system and detailed statistics. What’s more, WP ULike have a wide variety of options available to customize your plugin. It will allow you to customize your like button in your favorite style.',WP_ULIKE_SLUG); ?></p>
		</div>

	</div>

<?php
	$downloads_info = wp_ulike_get_repository_downloads_info();
	if( ! empty( $downloads_info ) ) {
?>
	<div class="feature-section is-wide has-1-columns">
		<div class="column">
			<canvas id="downloads-stats"></canvas>
		</div>
	</div>
	<script>
		var config = {
			type: 'line',
			data: {
				labels: <?php echo json_encode( $downloads_info['labels'] ); ?>,
				datasets: [{
					label: '<?php echo _e( 'Downloads', WP_ULIKE_SLUG ); ?>',
					data: <?php echo json_encode( $downloads_info['data'] ); ?>,
					backgroundColor: '#F3643A',
					borderColor: '#F3643A',
					type: 'line',
					pointRadius: 0,
					fill: false,
					lineTension: 0,
					borderWidth: 2
				}]
			},
			options: {
				responsive: true,
				tooltips: {
					mode: 'index',
					intersect: false,
				},
				legend: {
					display: false,
				},
				hover: {
					mode: 'nearest',
					intersect: true
				},
				scales: {
					xAxes: [{
						display: true,
						scaleLabel: {
							display: true,
							labelString: 'Days'
						}
					}],
					yAxes: [{
						display: true,
						scaleLabel: {
							display: true,
							labelString: 'Downloads'
						}
					}]
				}
			}
		};
		window.onload = function() {
			var ctx = document.getElementById('downloads-stats').getContext('2d');
			window.myLine = new Chart(ctx, config);
		};
	</script>
<?php
	}
?>
	<hr>
	<div class="return-to-dashboard">
		<a href="admin.php?page=wp-ulike-statistics"><?php echo _e('WP ULike Statistics',WP_ULIKE_SLUG); ?> &rarr; <?php echo _e('Home',WP_ULIKE_SLUG); ?></a> <?php echo _e('OR',WP_ULIKE_SLUG); ?> <a href="admin.php?page=wp-ulike-settings"><?php echo _e('WP ULike Settings',WP_ULIKE_SLUG); ?></a>
	</div>

	<?php else: ?>

	<p class="about-description"><?php echo _e('WP ULike is created by many love and time. Enjoy it :)',WP_ULIKE_SLUG); ?></p>
	<h3 class="wp-people-group"><?php echo _e('Project Leaders',WP_ULIKE_SLUG); ?></h3>
	<ul id="wp-people-group-project-leaders" class="wp-people-group">
		<li class="wp-person" id="wp-person-alimir">
			<a href="https://profiles.wordpress.org/alimir/"><?php echo get_avatar( 'info@alimir.ir', 64 ); ?></a>
			<a class="web" target="_blank" href="https://ir.linkedin.com/in/alimirir/">Ali Mirzaei</a>
			<span class="title"><?php echo _e('Project Lead & Developer',WP_ULIKE_SLUG); ?></span>
		</li>
	</ul>

	<?php endif; ?>
</div>