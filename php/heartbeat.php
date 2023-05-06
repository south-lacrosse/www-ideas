<?php
// How to turn off WordPress heartbeat if heartbeat causes too much server usage
// Add in App_Admin.php, current_screen hook

// don't use the heartbeat except for edit posts/cpt
if ($screen->base !== 'post') {
 wp_deregister_script('heartbeat');
}
// Change heartbeat frequency, defaults to 60s
add_filter( 'heartbeat_settings', function($settings) {
	$settings['interval'] = 120;
	return $settings;
}, 99, 1 );
