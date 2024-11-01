<?php

/*
Plugin Name: WP-Show-Unresponded-Comments
Plugin URI: http://blog.knut.me/
Description: This plugin adds a widget to your dashboard showing every post which has a comment you did not respond to (Posts where the last comment is not made by a logged in user)
Version: 0.2.2
Author: Knut Ahlers
Author URI: http://blog.knut.me/
*/

$plugin_dir = basename(dirname(__FILE__));                                                          
load_plugin_textdomain( 'wp-show-unresponded-comments', 'wp-content/plugins/' . $plugin_dir, $plugin_dir );

// Create the function to output the contents of our Dashboard Widget

function WPSuC_Do() {
	
	if(isset($_GET['clearSuC']) && $_GET['clearSuC'] == 'true') {
		WPSuC_Clear();
	}
	
	$result = WPSuC_Query(10);
	
	echo "<ul>";
	foreach($result as $post) {
		$pobj = get_post($post['ID']);
		?>
		<li>
			(<?php echo date(__('Y-m-d H:i', 'wp-show-unresponded-comments'), strtotime($post['post_date'])); ?>)
			<a href="<?php echo get_permalink($post['ID']); ?>#comments" target="_blank">
				<?php echo $pobj->post_title; ?>
			</a> (<?php echo $pobj->comment_count; ?>)
		</li>
		<?php
	}
	
	if(count($result) == 0)
		echo "<li>" . __('No unanswered comments.', 'wp-show-unresponded-comments') . "</li>";
	
	echo "</ul>";
	
	if(count($result) > 0) {
		?>
		<a href="?clearSuC=true"><?php _e('Hide reminding posts...', 'wp-show-unresponded-comments'); ?></a>
		<?php
	}
} 

function WPSuC_Query($limit) {
	global $wpdb;
	
	$hiddenids = get_option('WPSuC_HiddenIDs', '0');
	if($hiddenids == "")
		$hiddenids = 0;
	
	$query = <<<QUERY
	SELECT ID, 
		post_date,
		post_status,
		comment_status,
		post_name,
		post_title
	FROM {$wpdb->prefix}posts
	WHERE post_status = 'publish'
		AND comment_status = 'open'
		AND (
			SELECT user_id
			FROM {$wpdb->prefix}comments
			WHERE comment_post_ID = ID
				AND comment_type = ''
				AND comment_ID > {$hiddenids}
				AND comment_approved = '1'
			ORDER BY comment_ID DESC
			LIMIT 1
			) = 0
	ORDER BY (
				SELECT MAX(comment_ID)
				FROM {$wpdb->prefix}comments
				WHERE comment_post_ID = ID
		     ) DESC, 
		post_date DESC
	LIMIT {$limit};
QUERY;
	
	$dbres = mysql_query($query);
  $result = array();
  while($ds = mysql_fetch_assoc($dbres)) {
          $result[] = $ds;
  }
  
  return $result;
}

function WPSuC_Clear() {
	$comments = get_comments( array('status' => 'approve', 'number' => 1) );
	$id = $comments[0]->comment_ID;
	update_option('WPSuC_HiddenIDs', $id);
}


// Create the function use in the action hook
function WPSuC_Add() {
	wp_add_dashboard_widget('WPSuC', __('Unresponded Comments', 'wp-show-unresponded-comments'), 'WPSuC_Do');	
} 

// Hoook into the 'wp_dashboard_setup' action to register our other functions
add_action('wp_dashboard_setup', 'WPSuC_Add' );

?>