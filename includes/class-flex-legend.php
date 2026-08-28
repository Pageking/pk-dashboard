<?php
if (!defined('ABSPATH')) {
	exit;
}

if (!class_exists('PK_Flex_Legend')) {

// Zijbalk-legenda met de ACF flex-content rijen op het paginascherm.
// De kleurenmap zelf staat in functions/flex-legend-colors.php.
class PK_Flex_Legend {

	const METABOX_ID = 'pk-page-layout-legend';

	public function __construct() {
		add_action('admin_enqueue_scripts', array($this, 'enqueue_editor_assets'));
		add_action('add_meta_boxes_page', array($this, 'register_metabox'));
	}

	public function enqueue_editor_assets($hook_suffix) {
		if ($hook_suffix !== 'post.php' && $hook_suffix !== 'post-new.php') {
			return;
		}

		$screen = get_current_screen();
		if (!$screen || $screen->post_type !== 'page') {
			return;
		}

		$script_path = PK_DASHBOARD_PLUGIN_PATH . 'js/pk-editor-layout-legend.js';
		$script_url  = PK_DASHBOARD_PLUGIN_URL . 'js/pk-editor-layout-legend.js';
		$version     = is_readable($script_path) ? filemtime($script_path) : PK_DASHBOARD_VERSION;

		wp_enqueue_script('pk-editor-layout-legend', $script_url, array(), $version, true);

		$layout_cats = pk_flex_layout_categories();
		$colors      = array();
		foreach ($layout_cats as $slug) {
			$colors[$slug] = pk_flex_category_color($slug);
		}

		wp_localize_script('pk-editor-layout-legend', 'pkLayoutLegend', array(
			'labels'     => array('empty' => 'Geen flex-content gevonden.'),
			'layoutCats' => $layout_cats,
			'colors'     => $colors,
		));
	}

	public function register_metabox() {
		add_meta_box(
			self::METABOX_ID,
			'Flex content',
			array($this, 'render_metabox'),
			'page',
			'side',
			'default'
		);
	}

	public function render_metabox() {
		echo '<div id="pk-layout-legend" class="pk-layout-legend" aria-live="polite"></div>';
	}
}

}
