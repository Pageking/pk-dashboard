<?php
if (!defined('ABSPATH')) {
	exit;
}

class PK_Dashboard {
	
	public function __construct() {
		$this->load_dependencies();
		$this->init_hooks();
	}
	
	private function load_dependencies() {
		// Load admin classes
		require_once PK_DASHBOARD_PLUGIN_PATH . 'includes/admin/class-admin-assets.php';
		require_once PK_DASHBOARD_PLUGIN_PATH . 'includes/admin/dashboard-widget-manager.php';
		require_once PK_DASHBOARD_PLUGIN_PATH . 'includes/admin/admin-theme-manager.php';
		require_once PK_DASHBOARD_PLUGIN_PATH . 'includes/fonts/wysiwyg-font-manager.php';
		
		// Load frontend classes
		require_once PK_DASHBOARD_PLUGIN_PATH . 'includes/class-frontend-assets.php';
		
		// Load function files
		$this->load_functions();
	}
	
	private function load_functions() {
		$function_files = [
			// 'includes/functions/dashboard/clearCache.php',
			'includes/functions/dashboard/getPublicPages.php',
			'includes/functions/dashboard/getCreatedPosts.php',
			'includes/functions/dashboard/getRecentDraft.php',
			'includes/functions/dashboard/disableWidgets.php',
			'includes/functions/backend/setPkTheme.php',
			'includes/functions/backend/setPkFonts.php'
		];
		
		foreach ($function_files as $file) {
			$file_path = PK_DASHBOARD_PLUGIN_PATH . $file;
			if (file_exists($file_path)) {
				require_once $file_path;
			}
		}
	}
	
	private function init_hooks() {
		// Initialize classes
		new PK_Admin_Assets();
		new PK_Frontend_Assets();
		new PK_Dashboard_Widgets();
		new PK_Admin_Theme();
		new PK_Font_Manager();
		
		// Welcome panel hooks
		remove_action('welcome_panel', 'wp_welcome_panel');
		add_action('welcome_panel', array($this, 'load_welcome_panel'));
	}
	
	public function load_welcome_panel() {
		include PK_DASHBOARD_PLUGIN_PATH . 'layouts/panel.php';
	}
}