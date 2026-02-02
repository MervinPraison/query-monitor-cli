<?php
/**
 * Plugin Name: Query Monitor CLI
 * Plugin URI: https://github.com/praison/query-monitor-cli
 * Description: WP-CLI commands and REST API endpoints for Query Monitor debugging
 * Version: 0.1.0
 * Requires at least: 6.7
 * Requires PHP: 7.4
 * Requires Plugins: query-monitor
 * Author: Mervin Praison
 * Author URI: https://praison.ai
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: query-monitor-cli
 */

if (!defined('ABSPATH')) {
	exit;
}

define('QM_CLI_VERSION', '0.1.0');
define('QM_CLI_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('QM_CLI_PLUGIN_URL', plugin_dir_url(__FILE__));

/**
 * Check if Query Monitor is available
 */
function qm_cli_check_requirements()
{
	if (!class_exists('QueryMonitor')) {
		add_action('admin_notices', function () {
			echo '<div class="notice notice-error"><p>';
			echo '<strong>' . esc_html__('Query Monitor CLI:', 'query-monitor-cli') . '</strong> ';
			echo esc_html__('Requires Query Monitor plugin to be installed and activated.', 'query-monitor-cli');
			echo '</p></div>';
		});
		return false;
	}
	return true;
}

// Load REST API endpoints
require_once QM_CLI_PLUGIN_DIR . 'includes/class-qm-rest-api.php';

// Load WP-CLI commands only in CLI context
if (defined('WP_CLI') && WP_CLI) {
	require_once QM_CLI_PLUGIN_DIR . 'includes/class-qm-cli-base.php';
	require_once QM_CLI_PLUGIN_DIR . 'includes/class-qm-cli-commands.php';
}

// Initialize the plugin
add_action('plugins_loaded', function () {
	if (!qm_cli_check_requirements()) {
		return;
	}

	// Initialize REST API
	QM_REST_API::init();
});
