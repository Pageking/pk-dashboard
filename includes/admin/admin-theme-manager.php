<?php
if (!defined('ABSPATH')) {
	exit;
}

class PK_Admin_Theme {
	
	public function __construct() {
		add_action('admin_init', array($this, 'register_pk_admin_color_scheme'));
		add_action('admin_init', array($this, 'set_pk_color_scheme'));
		add_action('admin_init', array($this, 'remove_color_scheme_picker'));
		add_filter('get_user_option_admin_color', array($this, 'remove_admin_color_schemes'));
	}
	
	public function register_pk_admin_color_scheme() {
		$theme_css_path = PK_DASHBOARD_PLUGIN_PATH . 'css/pk-wp-theme.css';
		$theme_css_url  = PK_DASHBOARD_PLUGIN_URL . 'css/pk-wp-theme.css';
		$cache_bust     = is_readable($theme_css_path) ? filemtime($theme_css_path) : PK_DASHBOARD_VERSION;
		$theme_css_url  = add_query_arg('ver', $cache_bust, $theme_css_url);

		wp_admin_css_color(
			'pk-theme',
			'PK Dashboard',
			$theme_css_url,
			array('#1d1d1b', '#ffffff', '#39f7b2', '#5d63f2')
		);
	}
	
	public function set_pk_color_scheme() {
		$user_id = get_current_user_id();
		update_user_meta($user_id, 'admin_color', 'pk-theme');
	}
	
	public function remove_color_scheme_picker() {
		remove_action('admin_color_scheme_picker', 'admin_color_scheme_picker');
	}
	
	public function remove_admin_color_schemes($color_scheme) {
		return 'pk-theme';
	}
}