<?php
/**
 * Plugin Name: PK Dashboard
 * Plugin URI: https://pageking.nl
 * Description: WordPress backend admin styling in PK branding.
 * Version: 1.1.10
 * Author: Pageking
 * Author URI: https://pageking.nl
 * License: GPL-2.0+
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Requires at least: 5.0
 * Requires PHP: 7.4
 * Text Domain: pk-dashboard
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('PK_DASHBOARD_VERSION', '1.1.10');
define('PK_DASHBOARD_PLUGIN_URL', plugin_dir_url(__FILE__));
define('PK_DASHBOARD_PLUGIN_PATH', plugin_dir_path(__FILE__));

// Plugin Update Checker
require PK_DASHBOARD_PLUGIN_PATH . 'plugin-update-checker/plugin-update-checker.php';
use YahnisElsts\PluginUpdateChecker\v5\PucFactory;

$pkDashboardUpdateChecker = PucFactory::buildUpdateChecker(
    'https://github.com/Pageking/pk-dashboard/',
    __FILE__,
    'pk-dashboard'
);

// Repo is public; geen authentication nodig

// Main plugin class
require_once PK_DASHBOARD_PLUGIN_PATH . 'includes/pk-dashboard-main.php';

// Initialize the plugin
function pk_dashboard_init() {
    new PK_Dashboard();
}
add_action('plugins_loaded', 'pk_dashboard_init');
