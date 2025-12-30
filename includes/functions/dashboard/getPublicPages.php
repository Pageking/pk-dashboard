<?php

function get_published_count($posts) {
	
   $postTypes = array_merge($posts, get_post_types(array('_builtin' => false, 'public' => true)));
   unset($postTypes['fl-builder-template']);

   $publicPage = array(
	   'post_type' => $postTypes,
	   'post_status' => 'publish', 
	   'posts_per_page' => -1,
   );

   $publicPageQuery = new WP_Query($publicPage);

   return $publicPageQuery->found_posts;
}