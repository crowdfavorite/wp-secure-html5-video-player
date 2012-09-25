<?php 

if( !class_exists( 'secure_html5_video_player_widget' ) ) :
class secure_html5_video_player_widget extends WP_Widget {
	
	
	function secure_html5_video_player_widget() {
		$widget_ops = array(
			'classname' => 'secure_html5_video_player_widget', 
			'description' => __('A widget that plays HTML5 video.', 'secure-html5-video-player')
		);
		$control_ops = array(
			'width' => 400
		//	'height' => 350
		);
		$this->WP_Widget(false, __('Secure HTML5 Video Player', 'secure-html5-video-player'), $widget_ops, $control_ops);
	}

	
	function widget( $args, $instance ) {
		extract( $args );
		$custom_width = get_option('secure_html5_video_player_default_width');
		$custom_height = get_option('secure_html5_video_player_default_height');
    $custom_preload = 'no';
		$custom_autoplay = 'no';
		$custom_loop = 'no';
		if ($instance['width']) {
			$custom_width = $instance['width'];
		}
		if ($instance['height']) {
			$custom_height = $instance['height'];
		}
		if ($instance['preload']) {
			$custom_preload = $instance['preload'];
		}
		if ($instance['autoplay']) {
			$custom_autoplay = $instance['autoplay'];
		}	
		if ($instance['loop']) {
			$custom_loop = $instance['loop'];
		}	
		print $before_widget;
		print $before_title;
		print $instance['title'];
		print $after_title;
		print do_shortcode(
			'[video file="'.$instance['video'].'" '
			.' youtube="'.$instance['youtube_video_id'].'" vimeo="'.$instance['vimeo_video_id'].'" '
			.' preload="'.$custom_preload.'" autoplay="'.$custom_autoplay.'" loop="'.$custom_loop.'" '
			.' width="'.$custom_width.'" height="'.$custom_height.'"]'
		);
		print '<div class="secure_html5_video_player_caption">';
		print $instance['caption'];
		print '</div>';
		print $after_widget;
	}

	
	function update( $new_instance, $old_instance ) {
		$instance = $old_instance;
		$instance['title'] = strip_tags( $new_instance['title'] );
		$instance['video'] = strip_tags( $new_instance['video'] );
		$instance['youtube_video_id'] = strip_tags( $new_instance['youtube_video_id'] );
		$instance['vimeo_video_id'] = strip_tags( $new_instance['vimeo_video_id'] );
		$instance['width'] = strip_tags( $new_instance['width'] );
		$instance['height'] = strip_tags( $new_instance['height'] );
		$instance['preload'] = strip_tags( $new_instance['preload'] );
		$instance['autoplay'] = strip_tags( $new_instance['autoplay'] );
		$instance['loop'] = strip_tags( $new_instance['loop'] );
		$instance['caption'] = $new_instance['caption'];
		return $instance;
	}
	
	
	function form( $instance ) {
		$defaults = array(
			'title' => '',
			'video' => '',
			'youtube_video_id' => '',
			'vimeo_video_id' => '',
			'width' => get_option('secure_html5_video_player_default_width'),
			'height' => get_option('secure_html5_video_player_default_height'),
			'preload' => get_option('secure_html5_video_player_default_preload'),
			'autoplay' => get_option('secure_html5_video_player_default_autoplay'),
			'loop' => get_option('secure_html5_video_player_default_loop'),
			'caption' => ''
		);
		$instance = wp_parse_args( ( array )$instance, $defaults ); 
?><table>

<tr>
	<td colspan="2"><label for="<?php print $this->get_field_id( 'title' ); ?>"><?php 
		_e('Title', 'secure-html5-video-player');
	?>:</label></td>
</tr>
<tr>
	<td colspan="2"><input type="text" id="<?php print $this->get_field_id( 'title' ); ?>" name="<?php print $this->get_field_name( 'title' ); ?>" value="<?php print $instance['title']; ?>" style="width:400px;"/></td>
</tr>

<tr>
	<td colspan="2"><label for="<?php print $this->get_field_id( 'video' ); ?>"><?php 
		_e('Video', 'secure-html5-video-player');
	?>:</label></td></tr>
<tr>
	<td colspan="2"><?php
		$video_files = secure_html5_video_player_filelist(true);
		if (! empty($video_files)) {
			?><select id="<?php print $this->get_field_id( 'video' ); ?>" name="<?php print $this->get_field_name( 'video' ); ?>" style="width:400px;">
			<option value=""></option>
<?php
			foreach ($video_files as $curr_video_file => $server_addr) {
				?><option value="<?php print $curr_video_file; ?>" <?php if ($instance['video'] == $curr_video_file) {
					?> selected="selected" <?php
				} ?> ><?php print $curr_video_file; ?></option><?php
			}
			?></select><?php
		}
		else {
			?><input type="text" id="<?php print $this->get_field_id( 'video' ); ?>" name="<?php print $this->get_field_name( 'video' ); ?>" value="<?php print $instance['video']; ?>" style="width:400px;"/><?php
		}
	?></td>
</tr>


<tr>
	<td colspan="2"><label for="<?php print $this->get_field_id( 'youtube_video_id' ); ?>"><?php 
		_e('Youtube video ID', 'secure-html5-video-player');
	?>:</label></td>
</tr>
<tr>
	<td colspan="2"><input type="text" id="<?php print $this->get_field_id( 'youtube_video_id' ); ?>" name="<?php print $this->get_field_name( 'youtube_video_id' ); ?>" value="<?php print $instance['youtube_video_id']; ?>" style="width:400px;"/></td>
</tr>


<tr>
	<td colspan="2"><label for="<?php print $this->get_field_id( 'vimeo_video_id' ); ?>"><?php 
		_e('Vimeo video ID', 'secure-html5-video-player');
	?>:</label></td>
</tr>
<tr>
	<td colspan="2"><input type="text" id="<?php print $this->get_field_id( 'vimeo_video_id' ); ?>" name="<?php print $this->get_field_name( 'vimeo_video_id' ); ?>" value="<?php print $instance['vimeo_video_id']; ?>" style="width:400px;"/></td>
</tr>



<tr>
	<td><label for="<?php print $this->get_field_id( 'width' ); ?>"><?php 
		_e('Width', 'secure-html5-video-player')
	?>:</label></td>
	<td style="width:100%;"><input type="text" id="<?php print $this->get_field_id( 'width' ); ?>" name="<?php print $this->get_field_name( 'width' ); ?>" value="<?php print $instance['width']; ?>" size="5" /> px</td>
</tr>	
<tr>
	<td><label for="<?php print $this->get_field_id( 'height' ); ?>"><?php 
		_e('Height', 'secure-html5-video-player')
	?>:</label></td>
	<td><input type="text" id="<?php print $this->get_field_id( 'height' ); ?>" name="<?php print $this->get_field_name( 'height' ); ?>" value="<?php print $instance['height']; ?>" size="5"  /> px</td>
</tr>	
<tr>
	<td colspan="2">
		<input type="checkbox" id="<?php print $this->get_field_id( 'preload' ); ?>" name="<?php print $this->get_field_name( 'preload' ); ?>" value="yes" <?php 
	if ($instance['preload'] == 'yes') {
		?> checked="checked" <?php
	} 
	?> />
		<label for="<?php print $this->get_field_id( 'preload' ); ?>"><?php 
		_e('Preload', 'secure-html5-video-player')
		?></label>
	</td>
</tr>	
<tr>
	<td colspan="2">
		<input type="checkbox" id="<?php print $this->get_field_id( 'autoplay' ); ?>" name="<?php print $this->get_field_name( 'autoplay' ); ?>" value="yes" <?php 
	if ($instance['autoplay'] == 'yes') {
		?> checked="checked" <?php
	} 
	?> />
		<label for="<?php print $this->get_field_id( 'autoplay' ); ?>"><?php 
		_e('Autoplay', 'secure-html5-video-player')
		?></label>
	</td>
</tr>	
<tr>
	<td colspan="2">
		<input type="checkbox" id="<?php print $this->get_field_id( 'loop' ); ?>" name="<?php print $this->get_field_name( 'loop' ); ?>" value="yes" <?php 
	if ($instance['loop'] == 'yes') {
		?> checked="checked" <?php
	} 
	?> />
		<label for="<?php print $this->get_field_id( 'loop' ); ?>"><?php 
		_e('Loop', 'secure-html5-video-player')
		?></label>
	</td>
</tr>	
<tr><td colspan="2"><label for="<?php print $this->get_field_id( 'caption' ); ?>"><?php 
		_e('Caption (Text or HTML)', 'secure-html5-video-player')
	?>:</label></td></tr>
<tr><td colspan="2"><textarea id="<?php print $this->get_field_id( 'caption' ); ?>" name="<?php print $this->get_field_name( 'caption' ); ?>" rows="5" cols="29" class="widefat" ><?php print $instance['caption']; ?></textarea></td></tr>	
</table>


<?php
	}
}
endif;


?>