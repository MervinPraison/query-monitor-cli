<?php
/**
 * Uninstall script for Query Monitor CLI
 *
 * This file is executed when the plugin is uninstalled.
 * It cleans up any options or data the plugin may have stored.
 *
 * @package Query_Monitor_CLI
 */

// If uninstall not called from WordPress, exit.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

// Query Monitor CLI doesn't store any persistent data,
// so there's nothing to clean up on uninstall.
// This file is included for WordPress.org compliance.
