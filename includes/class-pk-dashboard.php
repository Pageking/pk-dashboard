<?php
if (!defined('ABSPATH')) {
	exit;
}

if (!class_exists('PK_Dashboard')) {

class PK_Dashboard {

	public function __construct() {
		$this->load_dependencies();
		$this->init_hooks();
	}

	private function load_dependencies() {
		$files = array(
			'includes/functions/dashboard-panel.php',
			'includes/functions/flex-legend-colors.php',
			'includes/class-admin-assets.php',
			'includes/class-admin-theme.php',
			'includes/class-dashboard-widgets.php',
			'includes/class-flex-legend.php',
			'includes/class-font-manager.php',
			'includes/class-frontend-assets.php',
		);

		foreach ($files as $file) {
			require_once PK_DASHBOARD_PLUGIN_PATH . $file;
		}
	}

	private function init_hooks() {
		new PK_Admin_Assets();
		new PK_Admin_Theme();
		new PK_Dashboard_Widgets();
		new PK_Flex_Legend();
		new PK_Font_Manager();
		new PK_Frontend_Assets();

		remove_action('welcome_panel', 'wp_welcome_panel');
		add_action('welcome_panel', array($this, 'render_welcome_panel'));
	}

	public function render_welcome_panel() {
		include PK_DASHBOARD_PLUGIN_PATH . 'templates/welcome-panel.php';
	}
}

}
