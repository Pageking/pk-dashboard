<?php
if (!defined('ABSPATH')) {
	exit;
}

class PK_Frontend_Assets {
	
	public function __construct() {
		add_action('wp_enqueue_scripts', array($this, 'enqueue_frontend_assets'));
	}
	
	public function enqueue_frontend_assets() {
		// Only load CSS if user is logged in
		if (!is_user_logged_in()) {
			return;
		}
		
		// Enqueue CSS
		wp_enqueue_style(
			'pk-frontend-css', 
			PK_DASHBOARD_PLUGIN_URL . 'css/pk-frontend.css',
			array(),
			PK_DASHBOARD_VERSION
		);
	}
}
