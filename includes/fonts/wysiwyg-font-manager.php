<?php
if (!defined('ABSPATH')) {
	exit;
}
class PK_Font_Manager {
	
	private $bb_default_system_fonts = [
		'Helvetica',
		'Verdana', 
		'Arial',
		'Times',
		'Georgia',
		'Courier',
		'system-ui'
	];
		
	public function __construct() {
		add_action('admin_init', array($this, 'define_default_bb_fonts'));
		add_filter('mce_css', array($this, 'get_bb_fonts'));
	}
	
	public function define_default_bb_fonts() {
		$system_fonts = apply_filters('fl_theme_system_fonts', array());
		
		if (!empty($system_fonts)) {
			foreach ($system_fonts as $font_name => $font_data) {
				array_unshift($this->bb_default_system_fonts, $font_name);
			}
		}
	}
	
	public function get_bb_fonts($mce_css) {
		
		if (!class_exists('FLCustomizer')) {
			return $mce_css;
		}
		
		$bb_values = FLCustomizer::get_mods();
		$fonts_to_check = array(
			'heading' => $bb_values['fl-heading-font-family'],
			'body'    => $bb_values['fl-body-font-family'],
			'title'   => empty($bb_values['fl-heading-style']) ? $bb_values['fl-heading-font-family'] : $bb_values['fl-title-font-family']
		);
		
		$font_urls = array();
		foreach ($fonts_to_check as $type => $font) {
			if (!in_array($font, $this->bb_default_system_fonts)) {
				$font_urls[] = 'https://fonts.googleapis.com/css2?family=' . urlencode($font) . ':wght@100;200;300;400;500;600;700;800;900&display=swap';
			}
		}
		
		$css_file = PK_DASHBOARD_PLUGIN_PATH . 'css/pk-backend-style.css';
		$custom_css = PK_DASHBOARD_PLUGIN_URL . 'css/pk-backend-style.css';
		if (file_exists($css_file)) {
			$custom_css .= '?ver=' . filemtime($css_file);
		}
		
		$all_css = array($custom_css);
		if (!empty($font_urls)) {
			$all_css = array_merge($all_css, $font_urls);
		}
		
		if (!empty($mce_css)) {
			$all_css = array_merge(explode(',', $mce_css), $all_css);
		}
		
		return implode(',', $all_css);
	}
	
}