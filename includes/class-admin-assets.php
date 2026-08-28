<?php
if (!defined('ABSPATH')) {
	exit;
}

if (!class_exists('PK_Admin_Assets')) {

// Assets voor het dashboardscherm zelf; elders in de admin wordt niets geladen.
class PK_Admin_Assets {
	
	public function __construct() {
		add_action('admin_enqueue_scripts', array($this, 'enqueue_assets'));
	}
	
	public function enqueue_assets() {
		$current_screen = get_current_screen();
		if (!$current_screen || $current_screen->base !== 'dashboard') {
			return;
		}

		// Enqueue CSS
		wp_enqueue_style(
			'pk-dashboard-css',
			PK_DASHBOARD_PLUGIN_URL . 'css/pk-dashboard.css',
			array(),
			PK_DASHBOARD_VERSION
		);

		// Enqueue JS
		wp_enqueue_script(
			'pk-dashboard-js',
			PK_DASHBOARD_PLUGIN_URL . 'js/pk-dashboard.js',
			array('jquery'),
			PK_DASHBOARD_VERSION,
			true
		);

		// Add custom fonts
		$this->add_custom_font_css();
	}
	
	private function add_custom_font_css() {
		$fontPath = PK_DASHBOARD_PLUGIN_URL . 'fonts/';
		$custom_css = "
		@font-face {
			font-family: 'PKFont';
			src: url('{$fontPath}CodecPro-Bold.otf') format('opentype');
			font-weight: 700;
			font-style: normal;
		}
		@font-face {
			font-family: 'PKFont';
			src: url('{$fontPath}CodecPro-ExtraBold.otf') format('opentype');
			font-weight: 800;
			font-style: normal;
		}
		@font-face {
			font-family: 'PKFont';
			src: url('{$fontPath}CodecPro-Regular.otf') format('opentype');
			font-weight: 400;
			font-style: normal;
		}";

		wp_add_inline_style('pk-dashboard-css', $custom_css);
	}
}

}
