<?php
/**
 * Plugin Name: Praison Debug CLI for Query Monitor
 * Plugin URI: https://github.com/MervinPraison/query-monitor-cli
 * Description: WP-CLI commands and REST API endpoints for Query Monitor debugging
 * Version: 0.1.0
 * Requires at least: 6.7
 * Requires PHP: 7.4
 * Requires Plugins: query-monitor
 * Author: Mervin Praison
 * Author URI: https://praison.ai
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: praison-cli-for-query-monitor
 */

if (!defined('ABSPATH')) {
	exit;
}

define('PRAISON_QMCLI_VERSION', '0.1.0');
define('PRAISON_QMCLI_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('PRAISON_QMCLI_PLUGIN_URL', plugin_dir_url(__FILE__));

/**
 * Check if Query Monitor is available
 */
function praison_qmcli_check_requirements()
{
	if (!class_exists('QueryMonitor')) {
		add_action('admin_notices', function () {
			echo '<div class="notice notice-error"><p>';
			echo '<strong>' . esc_html__('Praison Debug CLI for Query Monitor:', 'praison-cli-for-query-monitor') . '</strong> ';
			echo esc_html__('Requires Query Monitor plugin to be installed and activated.', 'praison-cli-for-query-monitor');
			echo '</p></div>';
		});
		return false;
	}
	return true;
}

// Load REST API endpoints
require_once PRAISON_QMCLI_PLUGIN_DIR . 'includes/class-praison-qmcli-rest-api.php';

// Load WP-CLI commands only in CLI context
if (defined('WP_CLI') && WP_CLI) {
	require_once PRAISON_QMCLI_PLUGIN_DIR . 'includes/class-praison-qmcli-base.php';
	require_once PRAISON_QMCLI_PLUGIN_DIR . 'includes/class-praison-qmcli-commands.php';
}

// Initialize the plugin
add_action('plugins_loaded', function () {
	if (!praison_qmcli_check_requirements()) {
		return;
	}

	// Initialize REST API
	Praison_QMCLI_REST_API::init();
});
