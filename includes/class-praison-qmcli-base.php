<?php
/**
 * Base class for Praison QMCLI commands
 */

if (!defined('ABSPATH')) {
    exit;
}

abstract class Praison_QMCLI_Base
{

    /**
     * Check if Query Monitor is available
     */
    protected function check_qm_available()
    {
        if (!class_exists('QueryMonitor')) {
            WP_CLI::error('Query Monitor plugin is not active.');
        }
    }

    /**
     * Initialize Query Monitor collectors
     */
    protected function init_qm()
    {
        static $initialized = false;

        if ($initialized) {
            return;
        }

        if (!defined('QM_TESTS')) {
            // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedConstantFound -- QM_TESTS is a Query Monitor constant.
            define('QM_TESTS', true);
        }

        // Initialize QueryMonitor instance
        $qm_dir = WP_PLUGIN_DIR . '/query-monitor';
        $qm_file = $qm_dir . '/query-monitor.php';

        if (file_exists($qm_file)) {
            $qm = QueryMonitor::init($qm_file);

            // Manually load and register collectors
            // We need to do this because in CLI context, the normal WordPress hooks don't fire
            $collector_files = glob($qm_dir . '/collectors/*.php');
            if ($collector_files) {
                foreach ($collector_files as $file) {
                    include_once $file;
                }
            }

            // Load data files
            $data_files = glob($qm_dir . '/data/*.php');
            if ($data_files) {
                foreach ($data_files as $file) {
                    include_once $file;
                }
            }

            // Now apply the filter to register collectors
            // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- qm/collectors is a Query Monitor hook.
            $collectors = apply_filters('qm/collectors', array(), $qm);
            foreach ($collectors as $collector) {
                QM_Collectors::add($collector);
            }

            $initialized = true;
        }
    }

    /**
     * Get a specific collector
     */
    protected function get_collector($id)
    {
        return QM_Collectors::get($id);
    }

    /**
     * Process all collectors
     */
    protected function process_collectors()
    {
        QM_Collectors::init()->process();
    }
}
