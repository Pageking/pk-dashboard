<?php
function get_recent_draft($posts) {
	$results = wp_get_recent_posts(array(
		'numberposts'      => 1,
		'post_type'        => $posts,
		'orderby'          => 'post_modified',
		'order'            => 'DESC',
		'post_status'      => 'draft',
		'suppress_filters' => true,
	));

	return !empty($results) ? $results[0] : null;
}