<?php
/**
 * Plugin Name: PK Dashboard - dev
 * Plugin URI: https://pageking.nl
 * Description: WordPress backend admin styling in PK branding.
 * Version: 1.1.22
 * Author: Pageking
 * Author URI: https://pageking.nl
 * License: GPL-2.0+
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Requires at least: 5.0
 * Requires PHP: 7.4
 * Text Domain: pk-dashboard
 */

if (!defined('ABSPATH')) {
	exit;
}

// Staat er een tweede kopie van deze plugin actief (een dev-map naast de live map),
// dan stopt die hier. Zonder deze afslag zouden de constanten naar de eerste map
// blijven wijzen terwijl de tweede zijn eigen bestanden verwacht.
if (defined('PK_DASHBOARD_VERSION')) {
	return;
}

define('PK_DASHBOARD_VERSION', '1.1.22');
define('PK_DASHBOARD_PLUGIN_URL', plugin_dir_url(__FILE__));
define('PK_DASHBOARD_PLUGIN_PATH', plugin_dir_path(__FILE__));

// Publieke repo, dus geen authenticatie nodig.
require PK_DASHBOARD_PLUGIN_PATH . 'plugin-update-checker/plugin-update-checker.php';
use YahnisElsts\PluginUpdateChecker\v5\PucFactory;

$pkDashboardUpdateChecker = PucFactory::buildUpdateChecker(
	'https://github.com/Pageking/pk-dashboard/',
	__FILE__,
	'pk-dashboard'
);

require_once PK_DASHBOARD_PLUGIN_PATH . 'includes/class-pk-dashboard.php';

// Het blok is nodig: PHP hoist top-level functiedeclaraties, dus de return hierboven
// voorkomt op zichzelf geen dubbele declaratie.
if (!function_exists('pk_dashboard_init')) {
	function pk_dashboard_init() {
		new PK_Dashboard();
	}
}

add_action('plugins_loaded', 'pk_dashboard_init');
