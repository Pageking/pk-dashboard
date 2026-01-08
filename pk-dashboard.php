<?php
/*
Plugin Name: PK Dashboard
Description: Wordpress backend in PK styling.
Version: 1.1.7
Author: Pageking
*/

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('PK_DASHBOARD_VERSION', '1.1.7');
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
