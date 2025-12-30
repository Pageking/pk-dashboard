<?php
if (!defined('ABSPATH')) {
	exit;
}

class PK_Dashboard_Widgets {
	
	public function __construct() {
		add_action('wp_dashboard_setup', array($this, 'disable_all_dashboard_widgets'), 100);
		add_action('admin_init', array($this, 'activate_welcome_panel_for_all_users'));
	}
	
	public function disable_all_dashboard_widgets() {
		global $wp_meta_boxes;

		foreach ($wp_meta_boxes['dashboard'] as $context => $priority_array) {
			foreach ($priority_array as $priority => $widgets) {
				if (is_array($widgets)) {
					foreach ($widgets as $widget_id => $widget) {
						remove_meta_box($widget_id, 'dashboard', $context);
					}
				}
			}
		}
	}
	
	public function activate_welcome_panel_for_all_users() {
		$user_id = get_current_user_id();
		update_user_meta($user_id, 'show_welcome_panel', 1);
	}
}