<?php

function get_recent_draft($posts) {
	
   $recent_post = wp_get_recent_posts( array(
	   'numberposts'      => 1,
	   'post_type'        => $posts,
	   'orderby'          => 'post_modified',
	   'order'            => 'DESC',
	   'post_status'      => 'draft',
	   'suppress_filters' => true,
   ) )[0];
   return $recent_post;
   
}