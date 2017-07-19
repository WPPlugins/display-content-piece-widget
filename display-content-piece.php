<?php
/**
 * Plugin Name: Display Content Piece Widget
 * Plugin URI: http://
 * Description: A simple little widget that can display any piece of content (eg a single post, page or individual customly defined content type).
 * Version: 0.1
 * Author: Chris Egerton
 * Author URI: http:// *
 * This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 */
 /* Add the widget function to the WP hooks. */
add_action( 'widgets_init', 'custom_content_widget' );
add_action('admin_head', 'my_action_javascript');
add_action('wp_ajax_dcp_axajcallback', 'dcp_axajcallback');		

//Javascript should be moved to external file
function my_action_javascript() {
?>
<script type="text/javascript" >
jQuery(document).ready(function($) {
	$(".ContentTypeSelect").live('change',function(){
		var id = this.id;
		var type = $('#'+id).val();
		var data = {
			action: 'dcp_axajcallback',
			dcp_action: 'list_content',
			dcp_content_type: type
		};
		jQuery.post(ajaxurl, data, function(response) {
			var auxArr = [];
			$.each(response, function(k,v){
				auxArr[k] = "<option value='" + v.i + "'>" + v.t + "</option>";
			});
			$('#' + id + '_selected').empty().append(auxArr.join(''));
		});
	});
});
</script>
<?php
}	
//jquery / json callback for select menu population
function dcp_axajcallback() {
	global $wpdb; 
	switch ($_POST['dcp_action']) {
	case 'get_post_types' :
		if ($_POST['dcp_post_types'] == 'filtered') {
			$exclude = array('nav_menu_item','revision','attachment');
			$types = array_diff(get_post_types(), $exclude);
			header('Content-type: application/json');
			echo json_encode($types);
			die;
		}
	break;
	case 'list_content' :
		if (isset($_POST['dcp_content_type'])) {
			$type = $_POST['dcp_content_type'];
			$query = "SELECT ID as i, post_title AS t FROM $wpdb->posts 
					WHERE post_type = '$type' AND post_status = 'publish'";
			$posts = $wpdb->get_results($query);
			header('Content-type: application/json');
			echo json_encode($posts);
			die;
		}
	break;
	}
}
function custom_content_widget() {
	register_widget( 'dcp_content_widget' );
}
 class dcp_content_widget extends WP_Widget {
	function dcp_content_widget() {
		/* Widget settings. */
		$widget_ops = array( 'classname' => 'dcp-content', 'description' => __( "Displays content associated with a single content piece (eg an individual post, page or customly defined content type). Install the Widget Context plugin to add further options about when and on which pages this content is displayed.") );
		$control_ops = array( 'width' => 300, 'height' => 350, 'id_base' => 'dcp_content_widget' );
		$this->WP_Widget( 'dcp_content_widget', __("Display Single Post"), $widget_ops, $control_ops );
	}
 //Widget Display Function Extension
 	function widget( $args, $instance ) {
		extract( $args );
		$contentid = $instance['type_list_selected'];
		echo $before_widget;
?>
	<div class="dcp_content" id="dcp_content-<?php echo $contentid;?>">
<?php
		$custom_page_content = get_post($contentid);
		$title = apply_filters('the_title',$custom_page_content->post_title);
			echo $before_title; 
			if ($instance['display_title'] == "visible") {
?>
		<div class="dcp_content_title"><?php echo $title;?></div>
<?php			
			}
			echo $after_title;
			$content = $custom_page_content->post_content;
			$content = apply_filters('the_content',$content);
?>
		<div class="dcp_content_body">
			<?php echo $content;?>
		</div>
	</div>
<?php
		echo $after_widget;
	}
//Widget Settings Function Extension
 	function form( $instance ) {
	if(!empty($instance['type_list_selected'])) {
		$current_post = get_post_field( 'post_title', $instance['type_list_selected']);
?>
	<p>Current Article: <em><?php echo $current_post;?><br />(Post ID: <?php echo $instance['type_list_selected']; ?>)</em></p>
<?php
	}
	else {
?>
	<h5>Use this widget to display small pieces of content in a sidebar or footer area etc of your site. Ideal for displaying custom content types.</h5>
<?php	
	}
	$defaults = array( 'type_list_selected' => '', 'display_title' => '');
	$instance = wp_parse_args( (array) $instance, $defaults );
?>
	<h4>Select Content to display:</h4>
    <p><label for="<?php echo $this->get_field_id( 'type_list' ); ?>">Content Type:</label>
    <select class="ContentTypeSelect" id='<?php echo $this->get_field_id( 'type_list' ); ?>'>
		<option value=''>Select Content Type</option>
<?php
			$exclude = array('nav_menu_item','revision','attachment');
			$types = array_diff(get_post_types(), $exclude);	
			foreach($types as $type){
				$name = ucwords(strtolower(str_replace('_', ' ', $type)));
?>
		<option value='<?php print($type);?>'><?php print($name);?></option>          
<?php
			}
?>		
    </select></p>
	<p><label for="<?php echo $this->get_field_id( 'type_list_selected' ); ?>">Content Article: </label>
	<select class="ContentSelectList" id='<?php echo $this->get_field_id( 'type_list_selected' ); ?>' name="<?php echo $this->get_field_name( 'type_list_selected' ); ?>">
		<option value=''>--------------------------</option>  
	</select></p>
	<h4><em>Other Options:</em></h4>
	<p>
		<label for="<?php echo $this->get_field_id( 'display_title' ); ?>">Article Title:</label>		
		<select id="<?php echo $this->get_field_id( 'display_title' ); ?>" name="<?php echo $this->get_field_name( 'display_title' ); ?>">
			<option <?php if ( 'visible' == $instance['display_title'] ) echo 'selected="selected"'; ?> value="visible">Title Visible</option>
			<option <?php if ( 'hidden' == $instance['display_title'] ) echo 'selected="selected"'; ?> value="hidden">Title Hidden</option>
		</select>
	</p>
	<?php 
	}
}
?>