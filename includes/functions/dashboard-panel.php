<?php
if (!defined('ABSPATH')) {
	exit;
}

// Datahelpers voor het welkomstpaneel. Geprefixt omdat het globale functies zijn.
if (!function_exists('pk_get_created_posts')) {

	// Vult de meegegeven post types aan met alle publieke custom post types.
	// Beaver Builder-templates blijven buiten beeld.
	function pk_get_created_posts($posts) {
		$post_types = array_merge($posts, get_post_types(array('_builtin' => false, 'public' => true)));

		unset(
			$post_types['fl-builder-template'],
			$post_types['fl-theme-layout'],
			$post_types['fl-builder-userstemplate']
		);

		return $post_types;
	}

	// Telt gepubliceerde items over de standaard- en custom post types heen.
	// Haalt alleen found_posts op, dus zonder de post-objecten te laden.
	function pk_get_published_count($posts) {
		$query = new WP_Query(array(
			'post_type'              => pk_get_created_posts($posts),
			'post_status'            => 'publish',
			'posts_per_page'         => 1,
			'fields'                 => 'ids',
			'ignore_sticky_posts'    => true,
			'update_post_meta_cache' => false,
			'update_post_term_cache' => false,
		));

		return (int) $query->found_posts;
	}

	// Meest recent bijgewerkte concept.
	// Filters blijven aan staan zodat WPML het concept in de actieve taal teruggeeft.
	function pk_get_recent_draft($posts) {
		$results = wp_get_recent_posts(array(
			'numberposts' => 1,
			'post_type'   => $posts,
			'orderby'     => 'post_modified',
			'order'       => 'DESC',
			'post_status' => 'draft',
		));

		return !empty($results) ? $results[0] : null;
	}
}
