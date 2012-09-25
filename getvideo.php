<?php 

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


define('WP_USE_THEMES', false);
define( 'ABSPATH', secure_html5_video_player_parent_path_with_file(__FILE__, 'wp-config.php', 10) . '/');

require_once( ABSPATH . 'wp-config.php' );
require_once('sh5vp-functions.php');

$filename = $_GET['file'];

$access_key = secure_html5_video_player_accessKey($filename);
if ($_GET['k'] != $access_key) {
	exit();
}


$filepath = get_option('secure_html5_video_player_video_dir') . '/' . $filename;
if (!file_exists($filepath)) {
	exit();
}


$content_type = 'application/octet-stream';
if (secure_html5_video_player_endsWith($filename, '.ogv') || secure_html5_video_player_endsWith($filename, '.ogg')) {
	$content_type = 'video/ogg';
}
else if (secure_html5_video_player_endsWith($filename, '.webm')) {
	$content_type = 'video/webm';
}
else if (secure_html5_video_player_endsWith($filename, '.mp4') || secure_html5_video_player_endsWith($filename, '.m4v')) {
	$content_type = 'video/mp4';
}
else if (secure_html5_video_player_endsWith($filename, '.jpg') || secure_html5_video_player_endsWith($filename, '.jpeg')) {
	$content_type = 'image/jpeg';
}
else if (secure_html5_video_player_endsWith($filename, '.png')) {
	$content_type = 'image/png';
}
else if (secure_html5_video_player_endsWith($filename, '.gif')) {
	$content_type = 'image/gif';
}


$filesize = filesize($filepath);
$fp = fopen($filepath, 'r');
if (!$fp) exit();


$content_length = $filesize;
$range_start = 0;
$range_end = $filesize - 1;


if (empty($_SERVER["HTTP_RANGE"])) {
	//header('Accept-Ranges: bytes');
	header('Content-Type: ' . $content_type);
	header('Content-Length: ' . $filesize );
	header('Content-Disposition: inline; filename=' . $filename);
}
else if ( strpos($_SERVER['HTTP_RANGE'], ',') !== false ) {
	//reject multibyte range request
	header('HTTP/1.1 416 Requested Range Not Satisfiable');
	header('Content-Range: bytes 0-' . ($filesize - 1) . '/' . $filesize);
	fclose($fp);
	exit();
}
else {
	$pos_equals_sign = strpos( $_SERVER['HTTP_RANGE'], '=');
	$range = substr( $_SERVER['HTTP_RANGE'], $pos_equals_sign + 1);
	
	//extract byte range
	$range_ary  = explode('-', $range);
	$range_start = $range_ary[0];
	if ( isset($range_ary[1]) && is_numeric($range_ary[1]) ) {
		$range_end = $range_ary[1];	
	}
	else {
		$range_end = $filesize - 1;	
	}
	if ($range_end >= $filesize) {
		$range_end = $filesize - 1;				
	}
		
	if ($range_start > $range_end || $range_start >= $filesize || $range_end >= $filesize) {
		//reject improper ranges
		header('HTTP/1.1 416 Requested Range Not Satisfiable');
		header('Content-Range: bytes 0-' . ($filesize - 1) . '/' . $filesize);
		fclose($fp);
		exit();
	}
	$content_length = $range_end - $range_start + 1;
	fseek($fp, $range_start, SEEK_SET);
	header('HTTP/1.1 206 Partial Content');
	header('Accept-Ranges: 0-' . $content_length);
	header('Content-Type: ' . $content_type);
	header('Content-Disposition: inline; filename=' . $filename);
	header('Content-Range: bytes ' . $range_start . '-' . $range_end . '/' . $filesize);
	header('Content-Length: ' . $content_length);
}



$chars_sent = 0;
while (!feof($fp)) {
	set_time_limit(0);
	$content = fread($fp, 1024);
	echo $content;
	$chars_sent += strlen($content);
	if (connection_aborted()) {
		break;
	}
	flush();
	ob_flush();
}
fclose($fp);


?>