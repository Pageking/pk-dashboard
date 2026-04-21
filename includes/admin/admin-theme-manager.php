<?php
if (!defined('ABSPATH')) {
	exit;
}

class PK_Admin_Theme {
	
	public function __construct() {
		add_action('admin_init', array($this, 'register_pk_admin_color_scheme'));
		add_action('admin_init', array($this, 'set_pk_color_scheme'));
		add_action('admin_init', array($this, 'remove_color_scheme_picker'));
		add_action('admin_enqueue_scripts', array($this, 'enqueue_editor_assets'));
		add_action('add_meta_boxes_page', array($this, 'register_page_layout_legend_metabox'));
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

	public function enqueue_editor_assets($hook_suffix) {
		if ($hook_suffix !== 'post.php' && $hook_suffix !== 'post-new.php') {
			return;
		}

		$current_screen = get_current_screen();
		if (!$current_screen || $current_screen->post_type !== 'page') {
			return;
		}

		$script_path = PK_DASHBOARD_PLUGIN_PATH . 'js/pk-editor-layout-legend.js';
		$script_url = PK_DASHBOARD_PLUGIN_URL . 'js/pk-editor-layout-legend.js';
		$script_ver = is_readable($script_path) ? filemtime($script_path) : PK_DASHBOARD_VERSION;

		wp_enqueue_script(
			'pk-editor-layout-legend',
			$script_url,
			array(),
			$script_ver,
			true
		);

		wp_localize_script('pk-editor-layout-legend', 'pkLayoutLegend', array(
			'labels' => array(
				'row' => 'Rij',
				'rowsActive' => 'rijen actief',
				'empty' => 'Nog geen rijen gevonden in deze contentopbouw.',
				'moveUp' => 'Rij omhoog',
				'moveDown' => 'Rij omlaag',
				'open' => 'Open layout',
			)
		));
	}

	public function register_page_layout_legend_metabox() {
		add_meta_box(
			'pk-page-layout-legend',
			'Rij legenda',
			array($this, 'render_page_layout_legend_metabox'),
			'page',
			'side',
			'default'
		);
	}

	public function render_page_layout_legend_metabox() {
		echo '<div id="pk-layout-legend" class="pk-layout-legend" aria-live="polite"></div>';
		echo '<div id="pk-layout-legend-debug" class="pk-layout-legend-debug" data-tone="muted" aria-live="polite"></div>';
	}
}