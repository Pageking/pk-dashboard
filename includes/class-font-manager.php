<?php
if (!defined('ABSPATH')) {
	exit;
}

if (!class_exists('PK_Font_Manager')) {

class PK_Font_Manager {

	/**
	 * Naam van de gegenereerde variabelen-stylesheet in de uploads-map.
	 * Staat bewust buiten de plugin: de plugin-map wordt bij een update overschreven
	 * en de meegeleverde CSS staat in versiebeheer.
	 */
	const GENERATED_FILE = 'pk-backend-fonts.css';

	/** Option waarin de laatst weggeschreven fontset staat, zodat we niet elke editor-load schrijven. */
	const STATE_OPTION = 'pk_dashboard_backend_fonts';

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
		$fonts = $this->get_theme_fonts();
		if (!$fonts) {
			return $mce_css;
		}

		$stylesheets = array($this->get_static_stylesheet_url());

		$generated_url = $this->get_generated_stylesheet_url($fonts);
		if ($generated_url) {
			$stylesheets[] = $generated_url;
		}

		foreach ($this->get_google_font_urls($fonts) as $font_url) {
			$stylesheets[] = $font_url;
		}

		if (!empty($mce_css)) {
			$stylesheets = array_merge(explode(',', $mce_css), $stylesheets);
		}

		return implode(',', $stylesheets);
	}

	/**
	 * Leest de lettertypes uit de Beaver Builder-theme customizer.
	 * Geeft null terug wanneer het BB-theme niet actief is.
	 */
	private function get_theme_fonts() {
		if (!class_exists('FLCustomizer') || !method_exists('FLCustomizer', 'get_mods')) {
			return null;
		}

		$mods = FLCustomizer::get_mods();
		if (!is_array($mods)) {
			return null;
		}

		// Oudere BB-installaties zetten niet elke mod, dus alles met een fallback ophalen.
		$heading = isset($mods['fl-heading-font-family']) ? $mods['fl-heading-font-family'] : '';
		$body    = isset($mods['fl-body-font-family']) ? $mods['fl-body-font-family'] : '';

		$title = $heading;
		if (!empty($mods['fl-heading-style']) && !empty($mods['fl-title-font-family'])) {
			$title = $mods['fl-title-font-family'];
		}

		if ($heading === '' && $body === '' && $title === '') {
			return null;
		}

		return array(
			'heading' => $heading,
			'body'    => $body,
			'title'   => $title,
		);
	}

	private function get_static_stylesheet_url() {
		$path = PK_DASHBOARD_PLUGIN_PATH . 'css/pk-backend-style.css';
		$url  = PK_DASHBOARD_PLUGIN_URL . 'css/pk-backend-style.css';

		return is_readable($path) ? add_query_arg('ver', filemtime($path), $url) : $url;
	}

	/**
	 * Schrijft de :root-variabelen naar de uploads-map en geeft de URL terug.
	 * Er wordt alleen geschreven wanneer de fontset wijzigde of het bestand ontbreekt.
	 */
	private function get_generated_stylesheet_url(array $fonts) {
		$upload_dir = wp_upload_dir();
		if (!empty($upload_dir['error'])) {
			return '';
		}

		$dir  = trailingslashit($upload_dir['basedir']) . 'pk-dashboard';
		$file = $dir . '/' . self::GENERATED_FILE;
		$url  = trailingslashit($upload_dir['baseurl']) . 'pk-dashboard/' . self::GENERATED_FILE;

		$state = get_option(self::STATE_OPTION);
		if (is_array($state) && isset($state['fonts']) && $state['fonts'] === $fonts && file_exists($file)) {
			return add_query_arg('ver', $state['version'], $url);
		}

		if (!wp_mkdir_p($dir)) {
			return '';
		}

		$css = sprintf(
			":root {\n\t--pk-backend-headings-font: %s;\n\t--pk-backend-body-font: %s;\n\t--pk-backend-heading1-font: %s;\n}\n",
			$this->sanitize_font_name($fonts['heading']),
			$this->sanitize_font_name($fonts['body']),
			$this->sanitize_font_name($fonts['title'])
		);

		if (file_put_contents($file, $css) === false) {
			error_log('PK Font Manager: kon ' . $file . ' niet schrijven.');
			return '';
		}

		$version = time();
		update_option(self::STATE_OPTION, array('fonts' => $fonts, 'version' => $version), false);

		return add_query_arg('ver', $version, $url);
	}

	/** Fontnamen komen uit de customizer, dus alleen tekens toelaten die in een font-family horen. */
	private function sanitize_font_name($font) {
		return trim(preg_replace('/[^A-Za-z0-9 _-]/', '', (string) $font));
	}

	private function get_google_font_urls(array $fonts) {
		$urls = array();

		foreach (array_unique($fonts) as $font) {
			if ($font === '' || in_array($font, $this->bb_default_system_fonts, true)) {
				continue;
			}

			$urls[] = 'https://fonts.googleapis.com/css2?family=' . urlencode($font) . ':wght@100;200;300;400;500;600;700;800;900&display=swap';
		}

		return $urls;
	}
}

}
