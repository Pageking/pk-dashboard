<?php

function get_created_posts($posts) {
	
   $postTypes = array_merge($posts, get_post_types(array('_builtin' => false, 'public' => true)));
   unset($postTypes['fl-builder-template']);

   return $postTypes;
}