<?php 



if ( !function_exists('secure_html5_video_player_plugin_action_links') ):
function secure_html5_video_player_plugin_action_links( $links, $file ) {
	if ( $file == plugin_basename( dirname(__FILE__).'/secure-html5-video-player.php' ) ) {
		$links[] = '<a href="options-general.php?page=secure-html5-video-player/secure-html5-video-player.php">'.__('Settings').'</a>';
	}
	return $links;
}
add_filter( 'plugin_action_links', 'secure_html5_video_player_plugin_action_links', 10, 2 );
endif;



if ( !function_exists('secure_html5_video_player_get_transient') ):
function secure_html5_video_player_get_transient($transient_key) {
	global $transient_ary;
	if (!isset($transient_ary)) $transient_ary = array();
	if (isset($transient_ary[$transient_key])) {
		return $transient_ary[$transient_key];
	}
	return false;
}
endif;



if ( !function_exists('secure_html5_video_player_set_transient') ):
function secure_html5_video_player_set_transient($transient_key, $val) {
	global $transient_ary;
	if (!isset($transient_ary)) $transient_ary = array();
	$transient_ary[$transient_key] = $val;
}
endif;



if ( !function_exists('secure_html5_video_player_remote_media_exists') ):
function secure_html5_video_player_remote_media_exists($media_server_address, $filename) {
	$access_key = secure_html5_video_player_accessKey($filename);
	$has_media_server = ('yes' == get_option('secure_html5_video_player_enable_media_server'));
	if (!$has_media_server) return FALSE;

	$transient_key = 'sh5vpExist:' . $media_server_address . ':' . $filename;
	$exists = secure_html5_video_player_get_transient($transient_key);
	if ($exists !== false) {
		return $exists == 'yes';
	}

	$media_exists = file_get_contents($media_server_address . '/getinfo.php?k=' . $access_key . '&info=exists&file=' . urlencode($filename));
	if ('1' == trim($media_exists)) {
		$exists = 'yes';
	}
	else {
		$exists = 'no';
	}
	secure_html5_video_player_set_transient($transient_key, $exists);
	return $exists == 'yes';
}
add_filter('secure_html5_video_player_remote_media_exists',
	'secure_html5_video_player_remote_media_exists', 1, 2);
endif;



if ( !function_exists('secure_html5_video_player_youtube_exists') ):
function secure_html5_video_player_youtube_exists($youtube_video_id) {
	if (! $youtube_video_id) {
		return false;
	}
	$secure_html5_video_player_youtube_override_type = get_option('secure_html5_video_player_youtube_override_type');
	if ('never' == $secure_html5_video_player_youtube_override_type) {
		return false;
	}

	$transient_key = 'sh5vpExist:youtube:' . $youtube_video_id;
	$exists = secure_html5_video_player_get_transient($transient_key);
	if ($exists !== false) {
		return $exists == 'yes';
	}
	
	$headers = get_headers("http://gdata.youtube.com/feeds/api/videos/{$youtube_video_id}?v=2");
	if (strpos($headers[0], '200') > 0) {
		$exists = 'yes';
		secure_html5_video_player_set_transient($transient_key, $exists);
		return true;
	}
	$exists = 'no';
	secure_html5_video_player_set_transient($transient_key, $exists);
	return false;
}
endif;



if ( !function_exists('secure_html5_video_player_vimeo_exists') ):
function secure_html5_video_player_vimeo_exists($vimeo_video_id) {
	if (! $vimeo_video_id) {
		return false;
	}
	$secure_html5_video_player_youtube_override_type = get_option('secure_html5_video_player_youtube_override_type');
	if ('never' == $secure_html5_video_player_youtube_override_type) {
		return false;
	}
	
	$transient_key = 'sh5vpExist:vimeo:' . $vimeo_video_id;
	$exists = secure_html5_video_player_get_transient($transient_key);
	if ($exists !== false) {
		return $exists == 'yes';
	}
	
	$headers = get_headers("http://vimeo.com/api/v2/video/{$vimeo_video_id}.php");
	if (strpos($headers[0], '200') > 0) {
		$exists = 'yes';
		secure_html5_video_player_set_transient($transient_key, $exists);
		return true;	
	}
	$exists = 'no';
	secure_html5_video_player_set_transient($transient_key, $exists);
	return false;
}
endif;



if ( !function_exists('secure_html5_video_player_media_server_address_list') ):
function secure_html5_video_player_media_server_address_list() {
	$retval = array();
	$secure_html5_video_player_media_servers = get_option('secure_html5_video_player_media_servers');
	$server_list = explode("\n", $secure_html5_video_player_media_servers);
	foreach ($server_list as $curr_server) {
		$curr_server_val = trim($curr_server);
		if (! $curr_server_val) continue;
		$retval[] = $curr_server_val;
	}
	return $retval;
}
endif;



if ( !function_exists('secure_html5_video_player_filelist') ):
function secure_html5_video_player_filelist($does_include_media_server_files) {
	$transient_key = 'secure_html5_video_player_filelist_0';
	if ($does_include_media_server_files) {
		$transient_key = 'secure_html5_video_player_filelist_1';
	}
	$video_files = secure_html5_video_player_get_transient($transient_key);
	if ($video_files !== false) {
		return $video_files;
	}
	
	$video_files = array();
	$secure_html5_video_player_video_dir = get_option('secure_html5_video_player_video_dir');
	if (is_dir($secure_html5_video_player_video_dir)) {
		$dh = opendir($secure_html5_video_player_video_dir);
		while (false !== ($filename = readdir($dh))) {
			if (secure_html5_video_player_startsWith($filename, '.')) continue;
			$video_files[ secure_html5_video_player_filename_no_ext($filename) ] = array();
		}
	}
	
	$has_media_server = ('yes' == get_option('secure_html5_video_player_enable_media_server'));
	if ($does_include_media_server_files && $has_media_server) {
		$server_list = secure_html5_video_player_media_server_address_list();
		foreach ($server_list as $media_server_address) {
			$access_key = secure_html5_video_player_accessKey('');
			$server_files = file_get_contents($media_server_address . '/getinfo.php?k=' . $access_key . '&info=list');
	
			$server_file_list = explode("\n", $server_files);
			foreach ($server_file_list as $curr_file) {
				$curr_file_val = trim($curr_file);
				if (! $curr_file_val) continue;
				if (isset($video_files[$curr_file_val])) {
					array_push($video_files[$curr_file_val], $media_server_address);
				}
				else {
					$video_files[$curr_file_val] = array($media_server_address);
				}
			}
		}
	}
	ksort($video_files);

	secure_html5_video_player_set_transient($transient_key, $video_files);
	return $video_files;
}
endif;



if ( !function_exists('secure_html5_video_player_get_client_ip') ):
function secure_html5_video_player_get_client_ip() {
	if ( isset($_SERVER["REMOTE_ADDR"]) ) {
		return $_SERVER["REMOTE_ADDR"];
	}
	else if ( isset($_SERVER["HTTP_X_FORWARDED_FOR"]) ) { 
		return $_SERVER["HTTP_X_FORWARDED_FOR"];
	}
	else if ( isset($_SERVER["HTTP_CLIENT_IP"]) ) {
		return $_SERVER["HTTP_CLIENT_IP"];
	}
	return FALSE;
}
endif;



if ( !function_exists('secure_html5_video_player_options') ):
function secure_html5_video_player_options() {
	print '<div class="wrap"><form method="post"><h2>';
	_e('Secure HTML5 Video Player', 'secure-html5-video-player');
	print '</h2>';
	if (!empty($_POST)) {
		if (isset($_REQUEST['submit'])) {
			update_secure_html5_video_player_options();
		}
		if (isset($_REQUEST['uninstall'])) {
			secure_html5_video_player_uninstall();
		}
	}
	print '<p>';
	_e('Contributors', 'secure-html5-video-player');
	print ': <a href="http://www.trillamar.com">Lucinda Brown</a>, Jinsoo Kang<br/>';
	_e('Plugin page', 'secure-html5-video-player');
	print ': <a href="http://www.trillamar.com/webcraft/secure-html5-video-player/">www.trillamar.com/webcraft/secure-html5-video-player</a><br/><br/>';
	_e('Secure HTML5 Video Player is a video plugin for WordPress built on the VideoJS HTML5 video player library. It allows you to embed video in your post or page using HTML5 with Flash fallback support for non-HTML5 browsers.  The settings can be easily configured with this control panel and with simplified short codes inserted into posts or pages.  Video files can be served privately; pseudo-streamed from a secured directory.', 'secure-html5-video-player'); 
	print '<br/><br/>';
	printf(
		__('See %s for additional information about Secure HTML5 Video Player.', 'secure-html5-video-player'), 
		'<a href="http://www.trillamar.com/webcraft/secure-html5-video-player/" target="_blank">www.trillamar.com/webcraft/secure-html5-video-player</a>'
	);
	print '<br/>';
	printf(
		__('See %s for additional information about VideoJS.', 'secure-html5-video-player'), 
		'<a href="http://videojs.com/" target="_blank">videojs.com</a>'
	);
	print '<br/>';
	printf(
		__('See %s for additional information about Flowplayer.', 'secure-html5-video-player'), 
		'<a href="http://flowplayer.org/" target="_blank">flowplayer.org</a>'
	);
	print '<br/></p><br/>';
	print '<input type="submit" name="submit" value="';
		_e('Save the options', 'secure-html5-video-player'); 
	print '" /><br/>';
	secure_html5_video_player_options_form();
	print "<div style='clear:both'></div>";
	print '<p>';
	print '<h3>';
		_e('Video Shortcode Options', 'secure-html5-video-player');
	print '</h3>';
	
	print '<h4>file</h4>';
	printf(
		__('The file name of the video without the file extension.  The video directory set in the control panel is searched for files with this name and with file extensions: mp4, m4v, ogv, ogg, theora.ogv, webm, png, jpg, jpeg, and gif.  The files that match are automatically used in the video tag and poster displayed in the page.  For example, if you have videos: %1$s, %2$s, %3$s, and the poster image: %4$s; you need only set a file value of %5$s.', 'secure-html5-video-player'), 
		'<b>myclip.mp4</b>', 
		'<b>myclip.ogv</b>', 
		'<b>myclip.webm</b>', 
		'<b>myclip.png</b>', 
		'"myclip"'
	);
	print '<br/><br/><code>[video file="myclip"]</code>';

	print '<h4>vimeo</h4>';
	_e('The Vimeo video ID.  A Vimeo video can be used as the primary video, with the HTML5 video as a fallback mechanism if the video is not available on the Vimeo service.  A Vimeo video can alternatively be used as the fallback when a specifed HTML5 video is not available.', 'secure-html5-video-player');
	print '<br/><br/><code>[video vimeo="46623590"]</code>';
	
	print '<h4>youtube</h4>';
	_e('The Youtube video ID.  A Youtube video can be used as the primary video, with the HTML5 video as a fallback mechanism if the video is not available on the Youtube service.  A Youtube video can alternatively be used as the fallback when a specifed HTML5 video is not available.', 'secure-html5-video-player');
	print '<br/><br/><code>[video youtube="u1zgFlCw8Aw"]</code>';
	
	print '<h4>mp4</h4>';
	_e('The file name or URL of the h.264/MP4 source for the video.', 'secure-html5-video-player');
	print '<br/><br/><code>[video mp4="video_clip.mp4"]</code>';
	
	print '<h4>ogg</h4>';
	_e('The file name or URL of the Ogg/Theora source for the video.', 'secure-html5-video-player');
	print '<br/><br/><code>[video ogg="video_clip.ogv"]</code>';
	
	print '<h4>webm</h4>';
	_e('The file name or URL of the VP8/WebM source for the video.', 'secure-html5-video-player');
	print '<br/><br/><code>[video webm="video_clip.webm"]</code>';
	
	print '<h4>poster</h4>';
	_e('The file name or URL of the poster frame for the video.', 'secure-html5-video-player');
	print '<br/><br/><code>[video poster="video_clip.png"]</code>';

	print '<h4>width</h4>';
	_e('The width of the video.', 'secure-html5-video-player');
	print '<br/><br/><code>[video width="640"]</code>';

	print '<h4>height</h4>';
	_e('The height of the video.', 'secure-html5-video-player');
	print '<br/><br/><code>[video height="480"]</code>';

	print '<h4>preload</h4>';
	_e('Start loading the video as soon as possible, before the user clicks play.', 'secure-html5-video-player');
	print '<br/><br/><code>[video preload="yes"]</code>';

	print '<h4>autoplay</h4>';
	_e('Start playing the video as soon as it is ready.', 'secure-html5-video-player');
	print '<br/><br/><code>[video autoplay="yes"]</code>';

	print '<h4>loop</h4>';
	_e('Replay the video from the beginning after it completes playing.', 'secure-html5-video-player');
	print '<br/><br/><code>[video loop="yes"]</code>';
	
	print '<br/><br/><h3>';
		_e('Video Shortcode Options', 'secure-html5-video-player');
	print '</h3><h4>';
		_e('Video URL example', 'secure-html5-video-player');
	print '</h4><code>[video mp4="http://video-js.zencoder.com/oceans-clip.mp4" ogg="http://video-js.zencoder.com/oceans-clip.ogg" webm="http://video-js.zencoder.com/oceans-clip.webm" poster="http://video-js.zencoder.com/oceans-clip.png" preload="yes" autoplay="no" loop="no" width="640" height="264"]</code><br/><h4>';
		_e('Video File Example using default settings', 'secure-html5-video-player');
	print '</h4><code>[video file="video_clip"]</code><br/><h4>';
		_e('Video File Example using custom settings', 'secure-html5-video-player');
	print '</h4><code>[video file="video_clip" preload="yes" autoplay="yes" loop="yes" width="1600" height="900"]</code></p><br/><br/>';
	print '<input type="submit" name="submit" value="';
		_e('Save the options', 'secure-html5-video-player');
	print '" /><br/><br/><div style="clear:both;"></div></form></div>';
}
endif;



if ( !function_exists('secure_html5_video_player_install') ):
function secure_html5_video_player_install() {
	add_option('secure_html5_video_player_video_dir', ABSPATH . 'videos');
	add_option('secure_html5_video_player_skin', 'tube');
	add_option('secure_html5_video_player_key_seed', base64_encode(AUTH_KEY));
	add_option('secure_html5_video_player_enable_flash_fallback', 'yes');
	add_option('secure_html5_video_player_enable_download_fallback', 'yes');
	
	add_option('secure_html5_video_player_default_width', 640);
	add_option('secure_html5_video_player_default_height', 480);
	add_option('secure_html5_video_player_default_preload', 'yes');
	add_option('secure_html5_video_player_default_autoplay', 'no');
	add_option('secure_html5_video_player_default_loop', 'no');

	add_option('secure_html5_video_player_enable_media_server', 'no');
	add_option('secure_html5_video_player_media_servers', '');
	add_option('secure_html5_video_player_youtube_override_type', 'fallback');
	
	add_action('widgets_init', 'secure_html5_video_player_widgets_init' );
}
endif;



if ( !function_exists('secure_html5_video_player_uninstall') ):
function secure_html5_video_player_uninstall() {
	delete_option('secure_html5_video_player_video_dir');
	delete_option('secure_html5_video_player_skin');
	delete_option('secure_html5_video_player_key_seed');
	delete_option('secure_html5_video_player_enable_flash_fallback');
	delete_option('secure_html5_video_player_enable_download_fallback');

	delete_option('secure_html5_video_player_default_width');
	delete_option('secure_html5_video_player_default_height');
	delete_option('secure_html5_video_player_default_preload');
	delete_option('secure_html5_video_player_default_autoplay');
	delete_option('secure_html5_video_player_default_loop');

	delete_option('secure_html5_video_player_enable_media_server');
	delete_option('secure_html5_video_player_media_servers');
	delete_option('secure_html5_video_player_youtube_override_type');
}
endif;



if ( !function_exists('update_secure_html5_video_player_options') ):
function update_secure_html5_video_player_options() {
	if (isset($_REQUEST['secure_html5_video_player_video_dir'])) {
		update_option('secure_html5_video_player_video_dir', $_REQUEST['secure_html5_video_player_video_dir']);
	}
	if (isset($_REQUEST['secure_html5_video_player_skin'])) {
		update_option('secure_html5_video_player_skin', $_REQUEST['secure_html5_video_player_skin']);
	}
	if (isset($_REQUEST['secure_html5_video_player_key_seed'])) {
		update_option('secure_html5_video_player_key_seed', $_REQUEST['secure_html5_video_player_key_seed']);
	}
	
	if (isset($_REQUEST['secure_html5_video_player_enable_flash_fallback']) 
	&& $_REQUEST['secure_html5_video_player_enable_flash_fallback'] == 'yes') {
		update_option('secure_html5_video_player_enable_flash_fallback', 'yes');
	}
	else {
		update_option('secure_html5_video_player_enable_flash_fallback', 'no');
	}

	if (isset($_REQUEST['secure_html5_video_player_enable_download_fallback'])
	&& $_REQUEST['secure_html5_video_player_enable_download_fallback'] == 'yes') {
		update_option('secure_html5_video_player_enable_download_fallback', 'yes');
	}
	else {
		update_option('secure_html5_video_player_enable_download_fallback', 'no');
	}
	
	if (isset($_REQUEST['secure_html5_video_player_default_width'])) {
		update_option('secure_html5_video_player_default_width', $_REQUEST['secure_html5_video_player_default_width']);
	}
	if (isset($_REQUEST['secure_html5_video_player_default_height'])) {
		update_option('secure_html5_video_player_default_height', $_REQUEST['secure_html5_video_player_default_height']);
	}

	if (isset($_REQUEST['secure_html5_video_player_default_preload'])
	&& $_REQUEST['secure_html5_video_player_default_preload'] == 'yes') {
		update_option('secure_html5_video_player_default_preload', 'yes');
	}
	else {
		update_option('secure_html5_video_player_default_preload', 'no');
	}

	if (isset($_REQUEST['secure_html5_video_player_default_autoplay'])
	&& $_REQUEST['secure_html5_video_player_default_autoplay'] == 'yes') {
		update_option('secure_html5_video_player_default_autoplay', 'yes');
	}
	else {
		update_option('secure_html5_video_player_default_autoplay', 'no');
	}

	if (isset($_REQUEST['secure_html5_video_player_default_loop'])
	&& $_REQUEST['secure_html5_video_player_default_loop'] == 'yes') {
		update_option('secure_html5_video_player_default_loop', 'yes');
	}
	else {
		update_option('secure_html5_video_player_default_loop', 'no');
	}
	
	if (isset($_REQUEST['secure_html5_video_player_enable_media_server']) 
	&& $_REQUEST['secure_html5_video_player_enable_media_server'] == 'yes') {
		update_option('secure_html5_video_player_enable_media_server', 'yes');
	}
	else {
		update_option('secure_html5_video_player_enable_media_server', 'no');
	}
	if (isset($_REQUEST['secure_html5_video_player_media_servers'])) {
		update_option('secure_html5_video_player_media_servers', $_REQUEST['secure_html5_video_player_media_servers']);
	}
	
	if (isset($_REQUEST['secure_html5_video_player_youtube_override_type'])) {
		update_option('secure_html5_video_player_youtube_override_type', $_REQUEST['secure_html5_video_player_youtube_override_type']);
	}
}
endif;



if ( !function_exists('secure_html5_video_player_options_form') ):
function secure_html5_video_player_options_form() {
	wp_enqueue_style('dashboard');
	wp_print_styles('dashboard');
	wp_enqueue_script('dashboard');
	wp_print_scripts('dashboard');

	$secure_html5_video_player_enable_media_server = ('yes' == get_option('secure_html5_video_player_enable_media_server') ? 'checked="checked"' : '');
	$secure_html5_video_player_media_servers = get_option('secure_html5_video_player_media_servers');

	$secure_html5_video_player_youtube_override_type = get_option('secure_html5_video_player_youtube_override_type');
	if ($secure_html5_video_player_youtube_override_type == '') {
		$secure_html5_video_player_youtube_override_type = 'fallback';
	}
	$secure_html5_video_player_youtube_override_type_never = '';
	$secure_html5_video_player_youtube_override_type_fallback = '';
	$secure_html5_video_player_youtube_override_type_primary = '';
	switch ($secure_html5_video_player_youtube_override_type) {
		case 'never':
			$secure_html5_video_player_youtube_override_type_never = 'checked="checked"';
			break;
		case 'fallback':
			$secure_html5_video_player_youtube_override_type_fallback = 'checked="checked"';
			break;
		case 'primary':
			$secure_html5_video_player_youtube_override_type_primary = 'checked="checked"';
			break;
	}

	$secure_html5_video_player_video_dir = get_option('secure_html5_video_player_video_dir');
	$secure_html5_video_player_key_seed = get_option('secure_html5_video_player_key_seed');

	$secure_html5_video_player_skin = get_option('secure_html5_video_player_skin');
	$secure_html5_video_player_skin_tube = "";
	$secure_html5_video_player_skin_vim = "";
	$secure_html5_video_player_skin_hu = "";
	$secure_html5_video_player_skin_videojs = "";
	$secure_html5_video_player_skin_native = "";
	switch ($secure_html5_video_player_skin) {
		case "tube":
			$secure_html5_video_player_skin_tube = 'selected="selected"';
			break;
		case "vim":
			$secure_html5_video_player_skin_vim = 'selected="selected"';
			break;
		case "hu":
			$secure_html5_video_player_skin_hu = 'selected="selected"';
			break;
		case "videojs":
			$secure_html5_video_player_skin_videojs = 'selected="selected"';
			break;
		case "native":
			$secure_html5_video_player_skin_native = 'selected="selected"';
			break;
	}

	$secure_html5_video_player_enable_flash_fallback = ('yes' == get_option('secure_html5_video_player_enable_flash_fallback') ? 'checked="checked"' : '');
	$secure_html5_video_player_enable_download_fallback = ('yes' == get_option('secure_html5_video_player_enable_download_fallback') ? 'checked="checked"' : '');

	$secure_html5_video_player_default_width = get_option('secure_html5_video_player_default_width');
	$secure_html5_video_player_default_height = get_option('secure_html5_video_player_default_height');

	$secure_html5_video_player_default_preload = ('yes' == get_option('secure_html5_video_player_default_preload') ? 'checked="checked"' : '');
	$secure_html5_video_player_default_autoplay = ('yes' == get_option('secure_html5_video_player_default_autoplay') ? 'checked="checked"' : '');
	$secure_html5_video_player_default_loop = ('yes' == get_option('secure_html5_video_player_default_loop') ? 'checked="checked"' : '');
	
	print '<div class="postbox-container" style="width:70%;"><br/><h3>';
	_e('Server', 'secure-html5-video-player');
	print '</h3>';

	$above_document_root = dirname($_SERVER['DOCUMENT_ROOT']);
	if (strpos($_SERVER['DOCUMENT_ROOT'], '/public_html/') !== FALSE) {
		$above_document_root = secure_html5_video_player_parent_path_with_file($_SERVER['DOCUMENT_ROOT'], 'public_html', 10);
	}
	else if (strpos($_SERVER['DOCUMENT_ROOT'], '/www/') !== FALSE) {
		$above_document_root = secure_html5_video_player_parent_path_with_file($_SERVER['DOCUMENT_ROOT'], 'www', 10);
	}

	?>
	<label for='secure_html5_video_player_video_dir'><?php _e('Video directory', 'secure-html5-video-player'); ?></label><br/>
	<input type='text' id="secure_html5_video_player_video_dir" name='secure_html5_video_player_video_dir' size='100' value='<?php print $secure_html5_video_player_video_dir ?>' /><br/>
	<small>
	<?php 
	printf(
		__('The directory on the website where videos are stored.  Your public_html directory is: %1$s. If videos should be protected, the video directory should either be a password protected directory under public_html like: %2$s; or a location outside of public_html, like: %3$s.  This is also where you will upload all of your videos, so it should be a location to where you can FTP large video files.  Your hosting control panel should have more information about creating directories protected from direct web access, and have the necessary functionality to configure them.', 'secure-html5-video-player'), 
		'<code>' . $_SERVER['DOCUMENT_ROOT'] . '</code>',
		'<code>' . $_SERVER['DOCUMENT_ROOT'] . '/videos</code>',
		'<code>' . $above_document_root . '/videos</code>'
	);
	?>
	</small><br/><br/>

	<label for='secure_html5_video_player_key_seed' style="white-space:nowrap;"><?php _e('Secure seed', 'secure-html5-video-player'); ?></label><br/>
	<input type='text' id="secure_html5_video_player_key_seed" name='secure_html5_video_player_key_seed'  size='100' value='<?php print $secure_html5_video_player_key_seed ?>' maxlength="80" />
	<input type="button" name="buttonGenerateSeed" 
		value="<?php _e('Generate Seed', 'secure-html5-video-player'); ?>" 
		onclick="
		var charAry = '0123456789QWERTYUIOPASDFGHJKLZXCVBNMqwertyuiopasdfghjklzxcvbnm';
		var buf = '';
		var seedLength = Math.floor(Math.random() * 60) + 20;
		for (i = 0; i < seedLength; i++) {
			buf += charAry[ Math.floor(Math.random() * charAry.length) ];
		}
		var secure_html5_video_player_key_seed = document.getElementById('secure_html5_video_player_key_seed');
		secure_html5_video_player_key_seed.value = buf;
		return false;
	" />
	<br/>
	<small><?php 
		printf(
			__('Arbitrary text used to generate session keys for secure video downloads.  This can be any string of any length, up to 80 characters long.  Press the [%s] button to automatically create a random one. If you are using media server(s), this value should be the same across all of them.', 'secure-html5-video-player'),
			__('Generate Seed', 'secure-html5-video-player')			
		); 
		?></small>
	<br/><br/>

	
	<label for='secure_html5_video_player_youtube_override_type'><?php _e('Allow Youtube or Vimeo to be displayed', 'secure-html5-video-player'); ?>:</label><br />
	
	<input type="radio" 
		name="secure_html5_video_player_youtube_override_type" 
		id="secure_html5_video_player_youtube_override_type_never"
		value="never"
		<?php print $secure_html5_video_player_youtube_override_type_never ?>
	 /><label for="secure_html5_video_player_youtube_override_type_never"> <?php _e('Never', 'secure-html5-video-player'); ?></label><br />
	<input type="radio" 
		name="secure_html5_video_player_youtube_override_type" 
		id="secure_html5_video_player_youtube_override_type_fallback"
		value="fallback"
		<?php print $secure_html5_video_player_youtube_override_type_fallback ?>
	 /><label for="secure_html5_video_player_youtube_override_type_fallback"> <?php _e('As a fallback, when HTML5 video is not present', 'secure-html5-video-player'); ?></label><br />
	<input type="radio" 
		name="secure_html5_video_player_youtube_override_type" 
		id="secure_html5_video_player_youtube_override_type_primary"
		value="primary"
		<?php print $secure_html5_video_player_youtube_override_type_primary ?>
	 /><label for="secure_html5_video_player_youtube_override_type_primary"> <?php _e('As the primary, but use HTML5 video when the Youtube/Vimeo video is not available', 'secure-html5-video-player'); ?></label><br />
	 
	<small><?php _e('Allows you to define when Youtube or Vimeo is used as a fallback or as the primary video.', 'secure-html5-video-player'); ?></small>
	<br/><br/>


	<input type='checkbox' value="yes" id="secure_html5_video_player_enable_media_server" name='secure_html5_video_player_enable_media_server' <?php print $secure_html5_video_player_enable_media_server ?> />
	<label for='secure_html5_video_player_enable_media_server'><?php _e('Enable media servers', 'secure-html5-video-player'); ?></label>
	<br/>
	<small><?php _e('If checked, media is permitted to be loaded from the listed media servers. ', 'secure-html5-video-player'); ?></small>
	<br/><br/>


	<label for='secure_html5_video_player_media_servers'><?php _e('Media servers', 'secure-html5-video-player'); ?></label><br/>
	<textarea id="secure_html5_video_player_media_servers" name="secure_html5_video_player_media_servers" rows="8" cols="100"><?php print ($secure_html5_video_player_media_servers); ?></textarea><br/>
	<small>
	<?php 
	printf(
		__('A list of media server URLs that serve the media files.  Each URL should be on its own line.  A media server is a separate Wordpress installation with this plugin enabled.  The URL you should list here is the path to the plugin URL on that server.  For example, if this installation is the media server, the URL you should use on the primary webserver is: %1$s.  All media servers must have the same secure seed.', 'secure-html5-video-player'), 
		'<code>' . plugins_url('secure-html5-video-player') . '</code>'
	);
	?>
	</small><br/><br/>

	<input type='submit' name='submit' value='<?php _e('Save the options', 'secure-html5-video-player'); ?>' /><br/><br/>

	<h3><?php _e('Playback', 'secure-html5-video-player'); ?></h3>
	<label for='secure_html5_video_player_default_width'><?php _e('Default width', 'secure-html5-video-player'); ?></label><br/>
	<input type='text' id="secure_html5_video_player_default_width" name='secure_html5_video_player_default_width'  size='10' value='<?php print $secure_html5_video_player_default_width ?>' /> px<br/>
	<small><?php 
		printf(
			__('Default video width.  Can be overrided by setting the %s attribute in the short tag.', 'secure-html5-video-player'),
			'<b>width</b>'
		); 
	?></small>
	<br/><br/>
	
	<label for='secure_html5_video_player_default_height'><?php _e('Default height', 'secure-html5-video-player'); ?></label><br/>
	<input type='text' id="secure_html5_video_player_default_height" name='secure_html5_video_player_default_height' size='10' value='<?php print $secure_html5_video_player_default_height ?>' /> px<br/>
	<small><?php 
		printf(
			__('Default video height.  Can be overrided by setting the %s attribute in the short tag.', 'secure-html5-video-player'),
			'<b>height</b>'
		); ?></small>
	<br/><br/>

	<input type='checkbox' value="yes" id="secure_html5_video_player_default_preload" name='secure_html5_video_player_default_preload' <?php print $secure_html5_video_player_default_preload ?> />
	<label for='secure_html5_video_player_default_preload'><?php _e("Preload video", 'secure-html5-video-player'); ?></label>
	<br/>
	<small><?php 
		printf(
			__('If checked, the video will preload by default.  Can be overrided by setting the %1$s attribute in the short tag to %2$s or %3$s.', 'secure-html5-video-player'),
			'<b>preload</b>',
			'<b>yes</b>',
			'<b>no</b>'
		); ?></small>
	<br/><br/>

	<input type='checkbox' value="yes" id="secure_html5_video_player_default_autoplay" name='secure_html5_video_player_default_autoplay' <?php print $secure_html5_video_player_default_autoplay ?> />
	<label for='secure_html5_video_player_default_autoplay'><?php _e('Autoplay video', 'secure-html5-video-player'); ?></label>
	<br/>
	<small><?php 
		printf(
			__('If checked, the video start playing automatically when the page is loaded.  Can be overrided by setting the %1$s attribute in the short tag to %2$s or %3$s.', 'secure-html5-video-player'),
			'<b>autoplay</b>',
			'<b>yes</b>',
			'<b>no</b>'
		); ?></small>
	<br/><br/>

	<input type='checkbox' value="yes" id="secure_html5_video_player_default_loop" name='secure_html5_video_player_default_loop' <?php print $secure_html5_video_player_default_loop ?> />
	<label for='secure_html5_video_player_default_loop'><?php _e('Loop video', 'secure-html5-video-player'); ?></label>
	<br/>
	<small><?php 
		printf(
			__('If checked, the video will play again after it finishes.  Can be overrided by setting the %1$s attribute in the short tag to %2$s or %3$s.', 'secure-html5-video-player'),
			'<b>loop</b>',
			'<b>yes</b>',
			'<b>no</b>'
		); ?></small>
	<br/><br/>

	<label for='secure_html5_video_player_skin'><?php _e('Player Skin', 'secure-html5-video-player'); ?>:</label>
	<select id="secure_html5_video_player_skin" name='secure_html5_video_player_skin'>
	<option value='tube' <?php print $secure_html5_video_player_skin_tube ?>><?php _e('tube', 'secure-html5-video-player'); ?></option>
	<option value='vim' <?php print $secure_html5_video_player_skin_vim ?>><?php _e('vim', 'secure-html5-video-player'); ?></option>
	<option value='hu' <?php print $secure_html5_video_player_skin_hu ?>><?php _e('hu', 'secure-html5-video-player'); ?></option>
	<option value='videojs' <?php print $secure_html5_video_player_skin_videojs ?>><?php _e('videojs', 'secure-html5-video-player'); ?></option>
	<option value='native' <?php print $secure_html5_video_player_skin_native ?>><?php _e('native', 'secure-html5-video-player'); ?></option>
	</select><br/>
	<small><?php _e('The visual appearance of the HTML5 video player.', 'secure-html5-video-player'); ?></small>
	<br/><br/>

<input type='submit' name='submit' value='<?php _e('Save the options', 'secure-html5-video-player'); ?>' /><br/><br/>

<h3><?php _e('Compatibility', 'secure-html5-video-player'); ?></h3>

	<input type='checkbox' value="yes" id="secure_html5_video_player_enable_flash_fallback" name='secure_html5_video_player_enable_flash_fallback' <?php print $secure_html5_video_player_enable_flash_fallback ?> />
	<label for='secure_html5_video_player_enable_flash_fallback'><?php _e('Enable Flash fallback', 'secure-html5-video-player'); ?></label>
	<br/>
	<small><?php _e('If checked, Flowplayer will act as a fallback for non-html5 compliant browsers.', 'secure-html5-video-player'); ?></small>
	<br/><br/>

	<input type='checkbox' value="yes" id="secure_html5_video_player_enable_download_fallback" name='secure_html5_video_player_enable_download_fallback' <?php print $secure_html5_video_player_enable_download_fallback ?> />
	<label for='secure_html5_video_player_enable_download_fallback'><?php _e('Enable download fallback', 'secure-html5-video-player'); ?></label>
	<br/>
	<small><?php _e('If checked, video download links will act as a fallback for non compliant browsers.', 'secure-html5-video-player'); ?></small>
	<br/><br/>


	<input type='submit' name='submit' value='<?php _e('Save the options', 'secure-html5-video-player'); ?>' /><br/><br/>
	</div>
	<?php
}
endif;



if ( !function_exists('secure_html5_video_player_printFile') ):
function secure_html5_video_player_printFile($file) {
	$fp = fopen($file, "r");
	if (!$fp) return;
	$chars_sent = 0;
	while (!feof($fp)) {
		$content = fread($fp, 1024);
		print $content;
		$chars_sent += strlen($content);
		if (connection_aborted()) {
			break;
		}
	}
	fclose($fp);
}
endif;



if ( !function_exists('secure_html5_video_player_filename_no_ext') ):
function secure_html5_video_player_filename_no_ext($str) {
	$retval = $str;
	$pos = strrpos($str, '.');
	if ($pos > 0) {
		$retval = substr($str, 0, $pos);
	}
	$pos = strrpos($str, '/');
	if ($pos > 0) {
		$retval = substr($str, $pos + 1);
	}
	if (secure_html5_video_player_endsWith($retval, '.theora')) {
		$retval = secure_html5_video_player_filename_no_ext($retval);
	}
	return $retval;
}
endif;



if ( !function_exists('secure_html5_video_player_to_object_id') ):
function secure_html5_video_player_to_object_id($prefix, $str) {
	$retval = secure_html5_video_player_filename_no_ext($str);
	$trans = array(
		"?" => "-", 
		"=" => "-", 
		"&" => "-",
		"." => "-",
		"$" => "-",
		"%" => "-"
	);
	return $prefix . strtr($retval, $trans);
}
endif;




if ( !function_exists('secure_html5_video_player_endsWith') ):
function secure_html5_video_player_endsWith($Haystack, $Needle){
	return strrpos($Haystack, $Needle) === strlen($Haystack) - strlen($Needle);
}
endif;



if ( !function_exists('secure_html5_video_player_startsWith') ):
function secure_html5_video_player_startsWith($haystack, $needle) {
    $length = strlen($needle);
    return (substr($haystack, 0, $length) === $needle);
}
endif;



if ( !function_exists('secure_html5_video_player_accessKey') ):
function secure_html5_video_player_accessKey($filename) {
	$has_media_server = ('yes' == get_option('secure_html5_video_player_enable_media_server'));
	$secure_html5_video_player_key_seed = get_option('secure_html5_video_player_key_seed');
	$script_tz = date_default_timezone_get();
	//date_default_timezone_set('America/Los_Angeles');
	date_default_timezone_set(get_option('timezone_string'));
	$f = '';
	if ($filename) {
		$f = secure_html5_video_player_filename_no_ext($filename);
	}
	$access_key = sha1(date('d-n-y') . $secure_html5_video_player_key_seed . $f);
	date_default_timezone_set($script_tz);
	return $access_key;
}
endif;



if ( !function_exists('secure_html5_video_player_add_header') ):
function secure_html5_video_player_add_header() {
	global $secure_html5_video_player_is_android;
	global $secure_html5_video_player_is_explorer7;
	global $secure_html5_video_player_is_explorer8;
	global $secure_html5_video_player_is_ios;
	if ($secure_html5_video_player_is_explorer7 || $secure_html5_video_player_is_explorer8 
	|| $secure_html5_video_player_is_ios || $secure_html5_video_player_is_android) {
		return;
	}
	
	$secure_html5_video_player_skin = get_option('secure_html5_video_player_skin');
	$plugin_dir = plugins_url('secure-html5-video-player');
	
	if ($secure_html5_video_player_skin != 'native') {
		print "<link rel='stylesheet' href='{$plugin_dir}/video-js/video-js.css' type='text/css' />\n";
		if ($secure_html5_video_player_skin != 'videojs') {
			print "<link rel='stylesheet' href='{$plugin_dir}/video-js/skins/".$secure_html5_video_player_skin.".css' type='text/css' />\n";
		}
		print "<script src='{$plugin_dir}/video-js/video.js' type='text/javascript' ></script>\n";
		print "<script type='text/javascript' > VideoJS.setupAllWhenReady(); </script>\n";
	}
}
endif;



if ( !function_exists('secure_html5_video_player_shortcode_video') ):
function secure_html5_video_player_shortcode_video($atts) {
	global $secure_html5_video_player_is_android;
	global $secure_html5_video_player_is_explorer7;
	global $secure_html5_video_player_is_explorer8;
	global $secure_html5_video_player_is_ios;
	
	$video_tag = '';
	$count_file_exists = 0;

	$secure_html5_video_player_youtube_override_type = get_option('secure_html5_video_player_youtube_override_type');
	$secure_html5_video_player_video_dir = get_option('secure_html5_video_player_video_dir');
	$secure_html5_video_player_skin = get_option('secure_html5_video_player_skin');
	$plugin_dir = plugins_url('secure-html5-video-player');
	
  extract(shortcode_atts(array(
    'file' => '',
    'mp4' => '',
    'webm' => '',
    'ogg' => '',
    'poster' => '',
    'width' => get_option('secure_html5_video_player_default_width'),
		'height' => get_option('secure_html5_video_player_default_height'),
    'preload' => get_option('secure_html5_video_player_default_preload'),
    'autoplay' => get_option('secure_html5_video_player_default_autoplay'),
    'loop' => get_option('secure_html5_video_player_default_loop'),
    'youtube' => '',
    'vimeo' => ''
  ), $atts));
	
	if (!$width || $width <= 0) {
		$width = '640';
	}
	if (!$height || $height <= 0) {
		$height = '480';
	}
	
	$youtube_tag = '';
	$youtube_exists = secure_html5_video_player_youtube_exists($youtube);
	if ($youtube_exists) {
		$autoplay_youtube = '0';
		if ($autoplay == 'yes' || $autoplay == 'true') {
			$autoplay_youtube = '1';
		}
		$origin = urlencode(site_url());
		$object_tag_id = secure_html5_video_player_to_object_id('ytplayer-', $youtube);
		$youtube_tag .= "<!-- Begin - Secure HTML5 Video Player -->\n";
		$youtube_tag .= "<iframe id='{$object_tag_id}' type='text/html' width='{$width}' height='{$height}' src='http://www.youtube.com/embed/{$youtube}?autoplay={$autoplay_youtube}&origin={$origin}' frameborder='0' /></iframe>\n";
		$youtube_tag .= "<!-- End - Secure HTML5 Video Player -->\n";
	}
	
	$vimeo_tag = '';
	$vimeo_exists = secure_html5_video_player_vimeo_exists($vimeo);
	if ($vimeo_exists) {
		$autoplay_vimeo = '0';
		if ($autoplay == 'yes' || $autoplay == 'true') {
			$autoplay_vimeo = '1';
		}
		$loop_vimeo = '0';
		if ($loop == 'yes' || $loop == 'true') {
			$loop_vimeo = '1';
		}
		$object_tag_id = secure_html5_video_player_to_object_id('vimeoplayer-', $vimeo);
		$vimeo_tag .= "<!-- Begin - Secure HTML5 Video Player -->\n";
		$vimeo_tag .= "<iframe id='{$object_tag_id}' src='http://player.vimeo.com/video/{$vimeo}?autoplay={$autoplay_vimeo}&amp;loop={$loop_vimeo}' width='{$width}' height='{$height}' frameborder='0'></iframe>";
		$vimeo_tag .= "<!-- End - Secure HTML5 Video Player -->\n";
	}
	
	{
		$video_tag .= "<!-- Begin - Secure HTML5 Video Player -->\n";

		if ($file) {
			$file = secure_html5_video_player_filename_no_ext($file);
		}
		$media_plugin_dir = apply_filters('secure_html5_video_player_get_media_server_address', secure_html5_video_player_get_client_ip(), $file);
		$has_media_server = ('yes' == get_option('secure_html5_video_player_enable_media_server'));
		if ($has_media_server) {
			$video_tag .= "<!-- Using media server: " .$media_plugin_dir. " -->\n";
		}	
		$object_tag_id = '';
		
		if ($file) {
			$object_tag_id = secure_html5_video_player_to_object_id('vjs-ff-', $file);
			$access_key = secure_html5_video_player_accessKey($file);
			
			if ($has_media_server 
			&& apply_filters('secure_html5_video_player_remote_media_exists', $media_plugin_dir, "{$file}.mp4")) {
				$mp4 = "{$media_plugin_dir}/getvideo.php?k={$access_key}&file={$file}.mp4";
				$count_file_exists++;
			}
			else if ($has_media_server 
			&& apply_filters('secure_html5_video_player_remote_media_exists', $media_plugin_dir, "{$file}.m4v")) {
				$mp4 = "{$media_plugin_dir}/getvideo.php?k={$access_key}&file={$file}.m4v";
				$count_file_exists++;
			}
			else if (file_exists("{$secure_html5_video_player_video_dir}/{$file}.mp4")) {
				$mp4 = "{$plugin_dir}/getvideo.php?k={$access_key}&file={$file}.mp4";
				$count_file_exists++;
			}	
			else if (file_exists("{$secure_html5_video_player_video_dir}/{$file}.m4v")) {
				$mp4 = "{$plugin_dir}/getvideo.php?k={$access_key}&file={$file}.m4v";
				$count_file_exists++;
			}
			
			if ($has_media_server 
			&& apply_filters('secure_html5_video_player_remote_media_exists', $media_plugin_dir, "{$file}.webm")) {
				$webm = "{$media_plugin_dir}/getvideo.php?k={$access_key}&file={$file}.webm";
				$count_file_exists++;
			}
			else if (file_exists("{$secure_html5_video_player_video_dir}/{$file}.webm")) {
				$webm = "{$plugin_dir}/getvideo.php?k={$access_key}&file={$file}.webm";
				$count_file_exists++;
			}
			
			if ($has_media_server 
			&& apply_filters('secure_html5_video_player_remote_media_exists', $media_plugin_dir, "{$file}.ogv")) {
				$ogg = "{$media_plugin_dir}/getvideo.php?k={$access_key}&file={$file}.ogv";
				$count_file_exists++;
			}
			else if ($has_media_server 
			&& apply_filters('secure_html5_video_player_remote_media_exists', $media_plugin_dir, "{$file}.ogg")) {
				$ogg = "{$media_plugin_dir}/getvideo.php?k={$access_key}&file={$file}.ogg";
				$count_file_exists++;
			}
			else if ($has_media_server 
			&& apply_filters('secure_html5_video_player_remote_media_exists', $media_plugin_dir, "{$file}.theora.ogv")) {
				$ogg = "{$media_plugin_dir}/getvideo.php?k={$access_key}&file={$file}.theora.ogv";
				$count_file_exists++;
			}
			else if (file_exists("{$secure_html5_video_player_video_dir}/{$file}.ogv")) {
				$ogg = "{$plugin_dir}/getvideo.php?k={$access_key}&file={$file}.ogv";
				$count_file_exists++;
			}
			else if (file_exists("{$secure_html5_video_player_video_dir}/{$file}.ogg")) {
				$ogg = "{$plugin_dir}/getvideo.php?k={$access_key}&file={$file}.ogg";
				$count_file_exists++;
			}
			else if (file_exists("{$secure_html5_video_player_video_dir}/{$file}.theora.ogv")) {
				$ogg = "{$plugin_dir}/getvideo.php?k={$access_key}&file={$file}.theora.ogv";
				$count_file_exists++;
			}
			
			if (!$poster) {
				if ($has_media_server 
				&& apply_filters('secure_html5_video_player_remote_media_exists', $media_plugin_dir, "{$file}.png")) {
					$poster = "{$media_plugin_dir}/getvideo.php?k={$access_key}&file={$file}.png";
				}
				else if ($has_media_server 
				&& apply_filters('secure_html5_video_player_remote_media_exists', $media_plugin_dir, "{$file}.jpg")) {
					$poster = "{$media_plugin_dir}/getvideo.php?k={$access_key}&file={$file}.jpg";
				}
				else if ($has_media_server 
				&& apply_filters('secure_html5_video_player_remote_media_exists', $media_plugin_dir, "{$file}.jpeg")) {
					$poster = "{$media_plugin_dir}/getvideo.php?k={$access_key}&file={$file}.jpeg";
				}
				else if ($has_media_server 
				&& apply_filters('secure_html5_video_player_remote_media_exists', $media_plugin_dir, "{$file}.gif")) {
					$poster = "{$media_plugin_dir}/getvideo.php?k={$access_key}&file={$file}.gif";
				}
				else if (file_exists("{$secure_html5_video_player_video_dir}/{$file}.png")) {
					$poster = "{$plugin_dir}/getvideo.php?k={$access_key}&file={$file}.png";
				}
				else if (file_exists("{$secure_html5_video_player_video_dir}/{$file}.jpg")) {
					$poster = "{$plugin_dir}/getvideo.php?k={$access_key}&file={$file}.jpg";
				}
				else if (file_exists("{$secure_html5_video_player_video_dir}/{$file}.jpeg")) {
					$poster = "{$plugin_dir}/getvideo.php?k={$access_key}&file={$file}.jpeg";
				}
				else if (file_exists("{$secure_html5_video_player_video_dir}/{$file}.gif")) {
					$poster = "{$plugin_dir}/getvideo.php?k={$access_key}&file={$file}.gif";
				}
			}
		}	
		
		// MP4 Source Supplied
		if ($mp4) {
			if (! $object_tag_id) {
				$object_tag_id = secure_html5_video_player_to_object_id('vjs-ff-', $mp4);
			}
		 // $mp4_source = '<source src="'.$mp4.'" type=\'video/mp4; codecs="avc1.42E01E, mp4a.40.2"\' />';
			$mp4_source = '<source src="'.$mp4.'" type="video/mp4" />';
			$mp4_link = '<a href="'.$mp4.'">MP4</a>';
			$count_file_exists++;
		}
	
		// WebM Source Supplied
		if ($webm) {
			//$webm_source = '<source src="'.$webm.'" type=\'video/webm; codecs="vp8, vorbis"\' />';
			$webm_source = '<source src="'.$webm.'" type="video/webm" />';
			$webm_link = '<a href="'.$webm.'">WebM</a>';
			$count_file_exists++;
		}
	
		// Ogg source supplied
		if ($ogg) {
			//$ogg_source = '<source src="'.$ogg.'" type=\'video/ogg; codecs="theora, vorbis"\' />';
			$ogg_source = '<source src="'.$ogg.'" type="video/ogg" />';
			$ogg_link = '<a href="'.$ogg.'">Ogg</a>';
			$count_file_exists++;
		}
	
		if ($poster) {
			$poster_attribute = 'poster="'.$poster.'"';
			$flow_player_poster = '"'. urlencode($poster) .'", ';
			$image_fallback = "<img src='$poster' width='$width' height='$height' alt='Poster Image' title='No video playback capabilities.' />";
		}
	
		if ($preload == 'yes' || $preload == 'true') {
			$preload_attribute = 'preload="auto"';
			$flow_player_preload = ',"autoBuffering":true';
		}
		else {
			$preload_attribute = 'preload="none"';
			$flow_player_preload = ',"autoBuffering":false';
		}
	
		if ($autoplay == 'yes' || $autoplay == 'true') {
			$autoplay_attribute = 'autoplay="autoplay"';
			$flow_player_autoplay = ',"autoPlay":true';
		}
		else {
			$autoplay_attribute = "";
			$flow_player_autoplay = ',"autoPlay":false';
		}
	
		if ($loop == 'yes' || $loop == 'true') {
			$loop_attribute = 'loop="loop"';
		}
		else {
			$loop_attribute = "";
		}
		
		$video_tag_skin = '';
		if ($secure_html5_video_player_skin != 'videojs') {
			$video_tag_skin = $secure_html5_video_player_skin . '-css';
		}
		$video_tag .= "<div class='video-js-box {$video_tag_skin}'>\n";
		
		if ($secure_html5_video_player_is_ios || $secure_html5_video_player_is_android) {
			// iOS and Android devices
			$video_tag .= "<video class='video-js' onClick='this.play();' width='{$width}' height='{$height}' {$poster_attribute} controls=\"controls\" {$preload_attribute} {$autoplay_attribute} {$loop_attribute} >\n";
			if ($mp4_source) {
				$video_tag .= "{$mp4_source}\n";
			}
			$video_tag .= "</video>\n";
		}
		else if (($secure_html5_video_player_is_explorer7 || $secure_html5_video_player_is_explorer8) && $mp4) {
			// IE 7 or IE 8
			$video_tag .= "<object id='{$object_tag_id}' class='vjs-flash-fallback' ";
			$video_tag .= " width='{$width}' height='{$height}' type='application/x-shockwave-flash' data='{$plugin_dir}/flowplayer/flowplayer-3.2.7.swf'>\n";
			$video_tag .= "<param name='movie' value='{$plugin_dir}/flowplayer/flowplayer-3.2.7.swf' />\n";
			$video_tag .= "<param name='wmode' value='transparent' />\n"; 
			$video_tag .= "<param name='allowfullscreen' value='true' />\n";
			$video_tag .= "<param name='flashvars' value='config={\"playlist\":[ $flow_player_poster {\"url\": \"" . urlencode($mp4) . "\" $flow_player_autoplay $flow_player_preload }]}' />\n";
			$video_tag .= "{$image_fallback}\n";
			$video_tag .= "</object>\n";
		}
		else {
			// everything else
			$video_tag .= "<video class='video-js' width='{$width}' height='{$height}' {$poster_attribute} controls=\"controls\" {$preload_attribute} {$autoplay_attribute} {$loop_attribute} >\n";
			if ($mp4_source) {
				$video_tag .= "{$mp4_source}\n";
			}
			if ($webm_source) {
				$video_tag .= "{$webm_source}\n";
			}
			if ($ogg_source) {
				$video_tag .= "{$ogg_source}\n";
			}
			if ($count_file_exists == 0) {
				$video_tag .= "<!-- " . __('file not found', 'secure-html5-video-player') . ": {$secure_html5_video_player_video_dir}/{$file} -->\n";
			}
		
			if ('yes' == get_option('secure_html5_video_player_enable_flash_fallback') && $mp4) {
				//Flash Fallback. Use any flash video player here. Make sure to keep the vjs-flash-fallback class.
				$video_tag .= "<object id='{$object_tag_id}' class='vjs-flash-fallback' ";
				$video_tag .= " width='{$width}' height='{$height}' type='application/x-shockwave-flash' data='{$plugin_dir}/flowplayer/flowplayer-3.2.7.swf'>\n";
				$video_tag .= "<param name='movie' value='{$plugin_dir}/flowplayer/flowplayer-3.2.7.swf' />\n";
				$video_tag .= "<param name='wmode' value='transparent' />\n"; 
				$video_tag .= "<param name='allowfullscreen' value='true' />\n";
				$video_tag .= "<param name='flashvars' value='config={\"playlist\":[ $flow_player_poster {\"url\": \"" . urlencode($mp4) . "\" $flow_player_autoplay $flow_player_preload }]}' />\n";
				$video_tag .= "{$image_fallback}\n";
				$video_tag .= "</object>\n";
			}
			$video_tag .= "</video>\n";
			
			if ('yes' == get_option('secure_html5_video_player_enable_download_fallback')) {
				//Download links provided for devices that can't play video in the browser.
				$video_tag .= "<p class='vjs-no-video'><strong>Download Video:</strong>\n";
				if ($mp4_link) {
					$video_tag .= "{$mp4_link}\n";
				}
				if ($webm_link) {
					$video_tag .= "{$webm_link}\n";
				}
				if ($ogg_link) {
					$video_tag .= "{$ogg_link}\n";
				}
				$video_tag .= "</p>\n";
			}
		}
		$video_tag .= "</div>\n";
		$video_tag .= "<!-- End - Secure HTML5 Video Player -->\n";
	}
	if ($vimeo_exists) {
		if ($count_file_exists == 0) {
			return $vimeo_tag;
		}
		else if ('primary' == $secure_html5_video_player_youtube_override_type) {
			return $vimeo_tag;
		}	
	}
	else if ($youtube_exists) {
		if ($count_file_exists == 0) {
			return $youtube_tag;
		}
		else if ('primary' == $secure_html5_video_player_youtube_override_type) {
			return $youtube_tag;
		}	
	}
	return $video_tag;
}
endif;



if ( !function_exists('secure_html5_video_player_parent_path_with_file') ):
function secure_html5_video_player_parent_path_with_file($filepath, $needle, $limit) {
	$curr_path = dirname($filepath);
	for ($i = 0; $i < $limit; $i++) {
		$ls = scandir($curr_path);
		if (in_array($needle, $ls)) return $curr_path;
		$curr_path = dirname($curr_path);
	}
	return NULL;
}
endif;



if ( !function_exists('secure_html5_video_player_widgets_init') ):
function secure_html5_video_player_widgets_init() {
	register_widget( 'secure_html5_video_player_widget' );
}
endif;



if ( !function_exists('secure_html5_video_player_plugins_loaded') ):
function secure_html5_video_player_plugins_loaded() {
	load_plugin_textdomain(
		'secure-html5-video-player', 
		false, 
		dirname( plugin_basename( __FILE__ ) ) . '/languages/'
	);
	add_action('widgets_init', 'secure_html5_video_player_widgets_init' );
}
endif;



?>