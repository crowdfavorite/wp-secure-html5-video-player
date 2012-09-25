<?php
/*
Plugin Name: Secure HTML5 Video Player
Plugin URI: http://www.trillamar.com/webcraft/secure-html5-video-player/
Description: An enhanced video plugin for WordPress built on the VideoJS HTML5 video player library.  Settings can be easily configured with a control panel and simplified short codes.  Video files can be served from a secured private directory. 
Author: Lucinda Brown, Jinsoo Kang
Version: 2.1
Author URI: http://www.trillamar.com/
License: LGPLv3
*/

$secure_html5_video_player_is_android = preg_match("/android/i", $_SERVER['HTTP_USER_AGENT']);
$secure_html5_video_player_is_explorer7 = preg_match("/msie 7/i", $_SERVER['HTTP_USER_AGENT']);
$secure_html5_video_player_is_explorer8 = preg_match("/msie 8/i", $_SERVER['HTTP_USER_AGENT']);
$secure_html5_video_player_is_ios = preg_match("/mobile/i", $_SERVER['HTTP_USER_AGENT']) && preg_match("/safari/i", $_SERVER['HTTP_USER_AGENT']);



require_once('sh5vp-functions.php');
require_once('sh5vp-widgets.php');
require_once('sh5vp-metabox.php');
register_activation_hook(__FILE__, 'secure_html5_video_player_install');

add_action('wp_head', 'secure_html5_video_player_add_header');
add_action('admin_menu', 'secure_html5_video_player_menu');
add_action('plugins_loaded', 'secure_html5_video_player_plugins_loaded');

add_shortcode('video', 'secure_html5_video_player_shortcode_video');



if ( !function_exists('secure_html5_video_player_menu') ):
function secure_html5_video_player_menu() {
	add_options_page(
		__('Secure HTML5 Video Player', 'secure-html5-video-player'),
		__('Secure HTML5 Video Player', 'secure-html5-video-player'),
		'manage_options',
		__FILE__,
		'secure_html5_video_player_options'
	);
}
endif;



/**
	Selects a media server from the list of available media servers using the client
	web browser's IP address, and the requested video file name, as parameters used to 
	select the server.  In an ideal situation, this function should be overrided to 
	provide media server best positioned to serve the specified IP address by a combination 
	of server load, available bandwidth, and physical proximity to the client.
	returns the address the of the plugin directory on the remote server.
	
	To override this function, define a new function that has as arguments:
	$client_ip and $video_filename
	and returns a server address with the full URL path to the secure-html5-video-player
	installation.  Then use add_filter to register the function with wordpress.  For example,
	If the function was named my_function, then it would be registered by calling the following
	in your Wordpress template's functions.php:
	
		add_filter('secure_html5_video_player_get_media_server_address', 'my_function', 10, 2);
*/
if ( !function_exists('secure_html5_video_player_get_media_server_address') ):
function secure_html5_video_player_get_media_server_address($client_ip, $video_filename) {
	$has_media_server = ('yes' == get_option('secure_html5_video_player_enable_media_server'));
	if ($has_media_server) {
		$chksum = crc32($client_ip);
		if ($chksum < 0) $chksum = -1 * $chksum;
		$server_list = secure_html5_video_player_media_server_address_list();

		if ($video_filename) {
			$server_filelist = secure_html5_video_player_filelist(true);
			$server_list_with_file = $server_filelist[$video_filename];
			if (! empty($server_list_with_file)) {
				$server_list = $server_list_with_file;
			}
		}
		
		$num_servers = count($server_list);
		$selected_server = $chksum % $num_servers;
		if ($selected_server < $num_servers 
		&& isset($server_list[$selected_server]) 
		&& $server_list[$selected_server]) {
			return $server_list[$selected_server];
		}
	}
	$plugin_url = plugins_url('secure-html5-video-player');
	return $plugin_url;
}
add_filter('secure_html5_video_player_get_media_server_address',
	'secure_html5_video_player_get_media_server_address', 1, 2);
endif;



?>