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
			$custom_font = array_key_first($system_fonts);
			array_unshift($this->bb_default_system_fonts, $custom_font);
		}
	}
	
	public function get_bb_fonts($mce_css) {
		
		if (!class_exists('FLCustomizer')) {
			return $mce_css;
		}
		
		$bb_values = FLCustomizer::get_mods();
		$fonts_to_check = array(
			'heading' => $bb_values['fl-heading-font-family'],
			'body' => $bb_values['fl-body-font-family'],
			'title' => empty($bb_values['fl-heading-style']) ? $bb_values['fl-heading-font-family'] : $bb_values['fl-title-font-family']
		);
				
		// Collect Google Font URLs
		$google_font_urls = array();
		foreach ($fonts_to_check as $type => $font) {
			if (!in_array($font, $this->bb_default_system_fonts)) {
				$google_font_urls[] = 'https://fonts.googleapis.com/css2?family=' . urlencode($font) . ':wght@100;200;300;400;500;600;700;800;900&display=swap';
			}
			else  {
				//TODO load in dynamic custom font
			}
		}
			
		// Create CSS vars for all fonts
		$system_font_vars = "
			--pk-backend-headings-font: {$fonts_to_check['heading']};
			--pk-backend-body-font: {$fonts_to_check['body']};
			--pk-backend-heading1-font: {$fonts_to_check['title']};
		";
		
		$css_file = PK_DASHBOARD_PLUGIN_PATH . 'css/pk-backend-style.css';
		
		// Prepare new CSS content with root variables
		$css_vars = ":root {
			{$system_font_vars}
		}";
		
		// Get existing CSS and remove old root variables
		$existing_css = '';
		if (file_exists($css_file)) {
			$existing_css = file_get_contents($css_file);
			$existing_css = preg_replace('/:root\s*{[^}]*}/', '', $existing_css);
		}
		
		$new_content = $css_vars . $existing_css;
		
		// Only write to file if content has changed to improve performance
		$current_content = file_exists($css_file) ? file_get_contents($css_file) : '';
		if ($current_content !== $new_content) {
			// Verify directory is writable before attempting to write
			$css_dir = dirname($css_file);
			if (!is_writable($css_dir)) {
				error_log('PK Font Manager: Cannot write to directory ' . $css_dir);
			} else {
				// Use file locking to prevent conflicts during simultaneous writes
				$fp = fopen($css_file, 'c');
				if ($fp && flock($fp, LOCK_EX)) {
					ftruncate($fp, 0);
					fwrite($fp, $new_content);
					fflush($fp);
					flock($fp, LOCK_UN);
					fclose($fp);
				} else {
					error_log('PK Font Manager: Could not acquire lock on ' . $css_file);
				}
			}
		}
		
		// Add version parameter based on file modification time for cache busting
		$custom_css = PK_DASHBOARD_PLUGIN_URL . 'css/pk-backend-style.css';
		if (file_exists($css_file)) {
			$custom_css .= '?ver=' . filemtime($css_file);
		}
		
		// Combine all CSS URLs
		$all_css = array($custom_css);
		if (!empty($google_font_urls)) {
			$all_css = array_merge($all_css, $google_font_urls);
		}
		
		// Add to existing MCE CSS if any
		if (!empty($mce_css)) {
			$all_css = array_merge(explode(',', $mce_css), $all_css);
		}
		
		return implode(',', $all_css);
	}
	
}
