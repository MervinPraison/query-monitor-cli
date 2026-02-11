<?php
/**
 * Uninstall script for Praison Debug CLI for Query Monitor
 *
 * This file is executed when the plugin is uninstalled.
 * It cleans up any options or data the plugin may have stored.
 *
 * @package Praison_QMCLI
 */

// If uninstall not called from WordPress, exit.
if (!defined('WP_UNINSTALL_PLUGIN')) {
	exit;
}

// Praison Debug CLI for Query Monitor doesn't store any persistent data,
// so there's nothing to clean up on uninstall.
// This file is included for WordPress.org compliance.
