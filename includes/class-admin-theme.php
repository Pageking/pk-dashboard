<?php
if (!defined('ABSPATH')) {
	exit;
}

if (!class_exists('PK_Admin_Theme')) {

// Registreert het PK-kleurenschema en zet het voor iedereen vast.
class PK_Admin_Theme {

	public function __construct() {
		add_action('admin_init', array($this, 'register_color_scheme'));
		add_action('admin_init', array($this, 'remove_color_scheme_picker'));
		add_filter('get_user_option_admin_color', array($this, 'force_color_scheme'));
	}

	public function register_color_scheme() {
		$css_path = PK_DASHBOARD_PLUGIN_PATH . 'css/pk-wp-theme.css';
		$css_url  = PK_DASHBOARD_PLUGIN_URL . 'css/pk-wp-theme.css';
		$version  = is_readable($css_path) ? filemtime($css_path) : PK_DASHBOARD_VERSION;

		wp_admin_css_color(
			'pk-theme',
			'PK Dashboard',
			add_query_arg('ver', $version, $css_url),
			array('#1d1d1b', '#ffffff', '#39f7b2', '#5d63f2')
		);
	}

	public function remove_color_scheme_picker() {
		remove_action('admin_color_scheme_picker', 'admin_color_scheme_picker');
	}

	// Elk pad in core leest de schemanaam via get_user_option('admin_color'),
	// dus deze filter volstaat; user meta hoeft er niet voor geschreven te worden.
	public function force_color_scheme($color_scheme) {
		return 'pk-theme';
	}
}

}
