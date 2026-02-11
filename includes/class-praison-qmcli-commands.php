<?php
/**
 * WP-CLI commands for Query Monitor
 */

if (!defined('ABSPATH')) {
    exit;
}

class Praison_QMCLI_Commands extends Praison_QMCLI_Base
{

    /**
     * Display Query Monitor environment information
     * 
     * ## OPTIONS
     * 
     * [--format=<format>]
     * : Output format (table, json, yaml, csv)
     * ---
     * default: table
     * options:
     *   - table
     *   - json
     *   - yaml
     *   - csv
     * ---
     * 
     * ## EXAMPLES
     * 
     *     wp qm env
     *     wp qm env --format=json
     * 
     * @when after_wp_load
     */
    public function env($args, $assoc_args)
    {
        $this->check_qm_available();
        $this->init_qm();

        // Process all collectors first to ensure dependencies are met
        $this->process_collectors();

        $collector = $this->get_collector('environment');

        if (!$collector) {
            WP_CLI::error('Environment collector not found.');
        }

        $data = $collector->get_data();

        $format = $assoc_args['format'] ?? 'table';

        if ($format === 'json') {
            WP_CLI::line(json_encode(array(
                'php' => array(
                    'version' => $data->php['version'] ?? 'N/A',
                    'memory_limit' => $data->php['memory_limit'] ?? 'N/A',
                    'max_execution_time' => $data->php['max_execution_time'] ?? 'N/A',
                ),
                'wordpress' => array(
                    'version' => $data->wp['version'] ?? 'N/A',
                    'multisite' => $data->wp['multisite'] ?? false,
                ),
                'database' => array(
                    'extension' => $data->db['extension'] ?? 'N/A',
                    'server' => $data->db['server'] ?? 'N/A',
                    'version' => $data->db['version'] ?? 'N/A',
                ),
            ), JSON_PRETTY_PRINT));
        } else {
            WP_CLI::line('=== PHP Information ===');
            WP_CLI::line(sprintf('Version: %s', $data->php['version'] ?? 'N/A'));
            WP_CLI::line(sprintf('Memory Limit: %s', $data->php['memory_limit'] ?? 'N/A'));
            WP_CLI::line(sprintf('Max Execution Time: %s', $data->php['max_execution_time'] ?? 'N/A'));
            WP_CLI::line('');

            WP_CLI::line('=== WordPress Information ===');
            WP_CLI::line(sprintf('Version: %s', $data->wp['version'] ?? 'N/A'));
            WP_CLI::line(sprintf('Multisite: %s', ($data->wp['multisite'] ?? false) ? 'Yes' : 'No'));
            WP_CLI::line('');

            WP_CLI::line('=== Database Information ===');
            WP_CLI::line(sprintf('Extension: %s', $data->db['extension'] ?? 'N/A'));
            WP_CLI::line(sprintf('Server: %s', $data->db['server'] ?? 'N/A'));
            WP_CLI::line(sprintf('Version: %s', $data->db['version'] ?? 'N/A'));
        }

        WP_CLI::success('Environment information retrieved.');
    }

    /**
     * Monitor database queries
     * 
     * ## OPTIONS
     * 
     * [<command>...]
     * : The WP-CLI command to monitor (optional)
     * 
     * [--format=<format>]
     * : Output format
     * ---
     * default: table
     * options:
     *   - table
     *   - json
     *   - csv
     * ---
     * 
     * [--slow-only]
     * : Show only slow queries
     * 
     * [--threshold=<seconds>]
     * : Slow query threshold in seconds
     * ---
     * default: 0.05
     * ---
     * 
     * ## EXAMPLES
     * 
     *     wp qm db
     *     wp qm db post list
     *     wp qm db post list --slow-only --threshold=0.1
     *     wp qm db post list --format=json
     * 
     * @when after_wp_load
     */
    public function db($args, $assoc_args)
    {
        $this->check_qm_available();
        $this->init_qm();

        $format = $assoc_args['format'] ?? 'table';
        $slow_only = isset($assoc_args['slow-only']);
        $threshold = floatval($assoc_args['threshold'] ?? 0.05);

        if (!empty($args)) {
            $command = implode(' ', $args);

            ob_start();
            try {
                WP_CLI::runcommand($command, array(
                    'return' => true,
                    'launch' => false,
                    'exit_error' => false,
                ));
            } catch (Exception $e) {
                WP_CLI::warning('Command execution had issues: ' . $e->getMessage());
            }
            ob_get_clean();
        }

        $this->process_collectors();

        $collector = $this->get_collector('db_queries');
        $data = $collector->get_data();

        $queries = $data->rows ?? array();

        if ($slow_only && isset($data->expensive)) {
            $queries = array_filter($queries, function ($query) use ($threshold) {
                return $query['ltime'] >= $threshold;
            });
        }

        WP_CLI::line(sprintf(
            'Total Queries: %d | Total Time: %.4fs',
            $data->total_qs ?? 0,
            $data->total_time ?? 0
        ));
        WP_CLI::line('');

        if ($format === 'json') {
            $output = array();
            foreach ($queries as $i => $query) {
                $output[] = array(
                    'index' => $i + 1,
                    'time' => $query['ltime'],
                    'type' => $query['type'],
                    'caller' => $query['caller_name'],
                    'sql' => $query['sql'],
                );
            }
            WP_CLI::line(json_encode($output, JSON_PRETTY_PRINT));
        } else {
            $items = array();
            foreach ($queries as $i => $query) {
                $items[] = array(
                    '#' => $i + 1,
                    'Time' => sprintf('%.4fs', $query['ltime']),
                    'Type' => $query['type'],
                    'Caller' => substr($query['caller_name'], 0, 30),
                    'SQL' => substr($query['sql'], 0, 60) . '...',
                );
            }

            if (!empty($items)) {
                WP_CLI\Utils\format_items('table', $items, array('#', 'Time', 'Type', 'Caller', 'SQL'));
            } else {
                WP_CLI::line('No queries recorded.');
            }
        }

        WP_CLI::success(sprintf('Found %d queries.', count($queries)));
    }

    /**
     * Profile a WP-CLI command or current state
     * 
     * ## OPTIONS
     * 
     * [<command>...]
     * : The WP-CLI command to profile (optional)
     * 
     * [--format=<format>]
     * : Output format
     * ---
     * default: table
     * options:
     *   - table
     *   - json
     * ---
     * 
     * ## EXAMPLES
     * 
     *     wp qm profile
     *     wp qm profile post list
     *     wp qm profile cache flush --format=json
     * 
     * @when after_wp_load
     */
    public function profile($args, $assoc_args)
    {
        $this->check_qm_available();
        $this->init_qm();

        $format = $assoc_args['format'] ?? 'table';
        $command = !empty($args) ? implode(' ', $args) : 'current state';

        $start_time = microtime(true);
        $start_memory = memory_get_usage();

        if (!empty($args)) {
            ob_start();
            try {
                WP_CLI::runcommand(implode(' ', $args), array(
                    'return' => true,
                    'launch' => false,
                    'exit_error' => false,
                ));
            } catch (Exception $e) {
                WP_CLI::warning('Command execution had issues: ' . $e->getMessage());
            }
            ob_get_clean();
        }

        $execution_time = microtime(true) - $start_time;
        $memory_used = memory_get_peak_usage() - $start_memory;

        $this->process_collectors();

        $overview = $this->get_collector('overview');
        $db_collector = $this->get_collector('db_queries');

        $overview_data = $overview->get_data();
        $db_data = $db_collector->get_data();

        $profile = array(
            'command' => $command,
            'execution_time' => $execution_time,
            'memory_peak' => memory_get_peak_usage(),
            'memory_used' => $memory_used,
            'memory_limit' => $overview_data->memory_limit ?? 0,
            'db_queries' => $db_data->total_qs ?? 0,
            'db_time' => $db_data->total_time ?? 0,
        );

        if ($format === 'json') {
            WP_CLI::line(json_encode($profile, JSON_PRETTY_PRINT));
        } else {
            WP_CLI::line('=== Performance Profile ===');
            WP_CLI::line(sprintf('Command: %s', $profile['command']));
            WP_CLI::line(sprintf('Execution Time: %.4fs', $profile['execution_time']));
            WP_CLI::line(sprintf('Peak Memory: %s', size_format($profile['memory_peak'])));
            WP_CLI::line(sprintf('Memory Used: %s', size_format($profile['memory_used'])));
            WP_CLI::line(sprintf('Database Queries: %d', $profile['db_queries']));
            WP_CLI::line(sprintf('Database Time: %.4fs', $profile['db_time']));
        }

        WP_CLI::success('Profile completed.');
    }

    /**
     * Monitor HTTP requests
     * 
     * ## OPTIONS
     * 
     * [<command>...]
     * : The WP-CLI command to monitor (optional)
     * 
     * [--format=<format>]
     * : Output format
     * ---
     * default: table
     * options:
     *   - table
     *   - json
     * ---
     * 
     * ## EXAMPLES
     * 
     *     wp qm http
     *     wp qm http plugin update --all
     *     wp qm http --format=json
     * 
     * @when after_wp_load
     */
    public function http($args, $assoc_args)
    {
        $this->check_qm_available();
        $this->init_qm();

        $format = $assoc_args['format'] ?? 'table';

        if (!empty($args)) {
            $command = implode(' ', $args);

            ob_start();
            try {
                WP_CLI::runcommand($command, array(
                    'return' => true,
                    'launch' => false,
                    'exit_error' => false,
                ));
            } catch (Exception $e) {
                WP_CLI::warning('Command execution had issues: ' . $e->getMessage());
            }
            ob_get_clean();
        }

        $this->process_collectors();

        $collector = $this->get_collector('http');
        $data = $collector->get_data();

        $requests = $data->http ?? array();

        WP_CLI::line(sprintf('Total HTTP Requests: %d', count($requests)));
        WP_CLI::line('');

        if (empty($requests)) {
            WP_CLI::line('No HTTP requests recorded.');
            WP_CLI::success('HTTP monitoring completed.');
            return;
        }

        if ($format === 'json') {
            $output = array();
            foreach ($requests as $request) {
                $output[] = array(
                    'url' => $request['url'],
                    'method' => $request['args']['method'] ?? 'GET',
                    'status' => is_wp_error($request['response']) ? 'Error' : ($request['response']['response']['code'] ?? 'Unknown'),
                    'time' => $request['ltime'],
                );
            }
            WP_CLI::line(json_encode($output, JSON_PRETTY_PRINT));
        } else {
            $items = array();
            foreach ($requests as $request) {
                $items[] = array(
                    'URL' => substr($request['url'], 0, 50) . '...',
                    'Method' => $request['args']['method'] ?? 'GET',
                    'Status' => is_wp_error($request['response']) ? 'Error' : ($request['response']['response']['code'] ?? 'Unknown'),
                    'Time' => sprintf('%.4fs', $request['ltime']),
                );
            }

            WP_CLI\Utils\format_items('table', $items, array('URL', 'Method', 'Status', 'Time'));
        }

        WP_CLI::success(sprintf('Found %d HTTP requests.', count($requests)));
    }

    /**
     * Monitor WordPress hooks
     * 
     * ## OPTIONS
     * 
     * [<command>...]
     * : The WP-CLI command to monitor (optional)
     * 
     * [--format=<format>]
     * : Output format
     * ---
     * default: table
     * options:
     *   - table
     *   - json
     * ---
     * 
     * ## EXAMPLES
     * 
     *     wp qm hooks
     *     wp qm hooks post create --post_title="Test"
     *     wp qm hooks --format=json
     * 
     * @when after_wp_load
     */
    public function hooks($args, $assoc_args)
    {
        $this->check_qm_available();
        $this->init_qm();

        $format = $assoc_args['format'] ?? 'table';

        if (!empty($args)) {
            $command = implode(' ', $args);

            ob_start();
            try {
                WP_CLI::runcommand($command, array(
                    'return' => true,
                    'launch' => false,
                    'exit_error' => false,
                ));
            } catch (Exception $e) {
                WP_CLI::warning('Command execution had issues: ' . $e->getMessage());
            }
            ob_get_clean();
        }

        $this->process_collectors();

        $collector = $this->get_collector('hooks');
        $data = $collector->get_data();

        $hooks = $data->hooks ?? array();

        WP_CLI::line(sprintf('Total Hooks: %d', count($hooks)));

        if ($format === 'json') {
            WP_CLI::line(json_encode($hooks, JSON_PRETTY_PRINT));
        } else {
            WP_CLI::line('Use --format=json to see detailed hook information.');
        }

        WP_CLI::success(sprintf('Found %d hooks.', count($hooks)));
    }

    /**
     * Monitor PHP errors
     * 
     * ## OPTIONS
     * 
     * [<command>...]
     * : The WP-CLI command to monitor (optional)
     * 
     * [--format=<format>]
     * : Output format
     * ---
     * default: table
     * options:
     *   - table
     *   - json
     * ---
     * 
     * ## EXAMPLES
     * 
     *     wp qm errors
     *     wp qm errors plugin activate my-plugin
     *     wp qm errors --format=json
     * 
     * @when after_wp_load
     */
    public function errors($args, $assoc_args)
    {
        $this->check_qm_available();
        $this->init_qm();

        $format = $assoc_args['format'] ?? 'table';

        if (!empty($args)) {
            $command = implode(' ', $args);

            ob_start();
            try {
                WP_CLI::runcommand($command, array(
                    'return' => true,
                    'launch' => false,
                    'exit_error' => false,
                ));
            } catch (Exception $e) {
                WP_CLI::warning('Command execution had issues: ' . $e->getMessage());
            }
            ob_get_clean();
        }

        $this->process_collectors();

        $collector = $this->get_collector('php_errors');
        $data = $collector->get_data();

        $errors = $data->errors ?? array();

        WP_CLI::line(sprintf('Total PHP Errors: %d', count($errors)));

        if (empty($errors)) {
            WP_CLI::success('No PHP errors found!');
            return;
        }

        if ($format === 'json') {
            WP_CLI::line(json_encode($errors, JSON_PRETTY_PRINT));
        } else {
            WP_CLI::line('Use --format=json to see detailed error information.');
        }

        WP_CLI::warning(sprintf('Found %d PHP errors.', count($errors)));
    }

    /**
     * Inspect a specific post/page/URL with complete Query Monitor analysis
     * 
     * Shows ALL Query Monitor data for a specific post, page, or URL including:
     * - Database queries
     * - Performance metrics
     * - HTTP requests
     * - Hooks fired
     * - Assets loaded
     * - Cache operations
     * - Conditionals
     * - Request details
     * - Theme information
     * - And more...
     * 
     * ## OPTIONS
     * 
     * [--post_id=<id>]
     * : Post ID to inspect
     * 
     * [--slug=<slug>]
     * : Post slug to inspect
     * 
     * [--url=<url>]
     * : URL path to inspect (e.g., /sample-page/)
     * 
     * [--format=<format>]
     * : Output format
     * ---
     * default: table
     * options:
     *   - table
     *   - json
     * ---
     * 
     * [--collectors=<collectors>]
     * : Comma-separated list of collectors to show (default: all)
     * 
     * ## EXAMPLES
     * 
     *     # Inspect by post ID
     *     wp qm inspect --post_id=123
     * 
     *     # Inspect by slug
     *     wp qm inspect --slug=sample-page
     * 
     *     # Inspect by URL
     *     wp qm inspect --url=/about/
     * 
     *     # Get JSON output
     *     wp qm inspect --post_id=123 --format=json
     * 
     *     # Show specific collectors only
     *     wp qm inspect --post_id=123 --collectors=db_queries,http,hooks
     * 
     * @when after_wp_load
     */
    public function inspect($args, $assoc_args)
    {
        $this->check_qm_available();

        // Determine what to inspect
        $post_id = $assoc_args['post_id'] ?? null;
        $slug = $assoc_args['slug'] ?? null;
        $url = $assoc_args['url'] ?? null;

        if (!$post_id && !$slug && !$url) {
            WP_CLI::error('Please specify --post_id, --slug, or --url');
        }

        // Get post ID from slug if provided
        if ($slug && !$post_id) {
            $post = get_page_by_path($slug, OBJECT, array('post', 'page'));
            if (!$post) {
                WP_CLI::error(sprintf('Post not found with slug: %s', $slug));
            }
            $post_id = $post->ID;
            $url = get_permalink($post_id);
        }

        // Get URL from post ID if provided
        if ($post_id && !$url) {
            $url = get_permalink($post_id);
            if (!$url) {
                WP_CLI::error(sprintf('Could not get URL for post ID: %s', $post_id));
            }
        }

        $format = $assoc_args['format'] ?? 'table';
        $collectors_filter = isset($assoc_args['collectors']) ? explode(',', $assoc_args['collectors']) : null;

        WP_CLI::line(sprintf('Inspecting: %s', $url));
        WP_CLI::line('');

        // Initialize QM and simulate the request
        $this->init_qm();

        // Simulate loading the post/page
        if ($post_id) {
            global $post, $wp_query;
            $post = get_post($post_id);
            setup_postdata($post);
            $wp_query->is_single = true;
            $wp_query->is_singular = true;
        }

        // Process all collectors
        $this->process_collectors();

        // Get all available collectors
        $all_collectors = QM_Collectors::init();

        $report = array();

        foreach ($all_collectors as $collector) {
            $collector_id = $collector->id;

            // Skip if filtering and this collector is not in the list
            if ($collectors_filter && !in_array($collector_id, $collectors_filter)) {
                continue;
            }

            $data = $collector->get_data();

            if (!$data) {
                continue;
            }

            $report[$collector_id] = $this->format_collector_data($collector_id, $data);
        }

        if ($format === 'json') {
            WP_CLI::line(json_encode($report, JSON_PRETTY_PRINT));
        } else {
            $this->display_inspection_report($report, $post_id);
        }
    }

    /**
     * Format collector data for display
     */
    private function format_collector_data($collector_id, $data)
    {
        $formatted = array(
            'collector' => $collector_id,
            'data' => array(),
        );

        // Extract relevant data based on collector type
        switch ($collector_id) {
            case 'db_queries':
                if (isset($data->queries)) {
                    $formatted['data'] = array(
                        'total_queries' => count($data->queries),
                        'total_time' => $data->total_time ?? 0,
                        'queries' => array_map(function ($query) {
                            return array(
                                'sql' => $query['sql'] ?? '',
                                'time' => $query['ltime'] ?? 0,
                                'caller' => $query['caller'] ?? '',
                                'component' => $query['component'] ?? '',
                            );
                        }, array_slice($data->queries, 0, 10)), // First 10 queries
                    );
                }
                break;

            case 'http':
                if (isset($data->http)) {
                    $formatted['data'] = array(
                        'total_requests' => count($data->http),
                        'requests' => array_map(function ($request) {
                            return array(
                                'url' => $request['url'] ?? '',
                                'method' => $request['args']['method'] ?? 'GET',
                                'response_code' => $request['response']['code'] ?? 0,
                                'time' => $request['end'] - $request['start'],
                            );
                        }, $data->http),
                    );
                }
                break;

            case 'hooks':
                if (isset($data->hooks)) {
                    $formatted['data'] = array(
                        'total_hooks' => count($data->hooks),
                        'hooks' => array_keys(array_slice($data->hooks, 0, 20)),
                    );
                }
                break;

            case 'conditionals':
                $formatted['data'] = get_object_vars($data);
                break;

            case 'request':
                $formatted['data'] = array(
                    'matched_query' => $data->request['matched_query'] ?? '',
                    'matched_rule' => $data->request['matched_rule'] ?? '',
                    'query_vars' => $data->request['query_vars'] ?? array(),
                );
                break;

            case 'theme':
                if (isset($data->stylesheet)) {
                    $formatted['data'] = array(
                        'theme' => $data->stylesheet,
                        'template' => $data->template,
                        'template_file' => $data->template_file ?? '',
                        'template_hierarchy' => $data->template_hierarchy ?? array(),
                    );
                }
                break;

            case 'timing':
                $formatted['data'] = get_object_vars($data);
                break;

            case 'php_errors':
                if (isset($data->errors)) {
                    $formatted['data'] = array(
                        'total_errors' => count($data->errors),
                        'errors' => $data->errors,
                    );
                }
                break;

            case 'assets_scripts':
            case 'assets_styles':
                if (isset($data->assets)) {
                    $formatted['data'] = array(
                        'total' => count($data->assets),
                        'assets' => array_keys($data->assets),
                    );
                }
                break;

            case 'cache':
                $formatted['data'] = array(
                    'hits' => $data->stats['hits'] ?? 0,
                    'misses' => $data->stats['misses'] ?? 0,
                    'total' => $data->stats['total'] ?? 0,
                );
                break;

            case 'transients':
                if (isset($data->trans)) {
                    $formatted['data'] = array(
                        'total' => count($data->trans),
                        'transients' => array_keys($data->trans),
                    );
                }
                break;

            default:
                // Generic data extraction
                $formatted['data'] = get_object_vars($data);
                break;
        }

        return $formatted;
    }

    /**
     * Display inspection report in table format
     */
    private function display_inspection_report($report, $post_id)
    {
        if ($post_id) {
            $post = get_post($post_id);
            WP_CLI::line(WP_CLI::colorize('%G=== Post Information ===%n'));
            WP_CLI::line(sprintf('ID: %d', $post->ID));
            WP_CLI::line(sprintf('Title: %s', $post->post_title));
            WP_CLI::line(sprintf('Type: %s', $post->post_type));
            WP_CLI::line(sprintf('Status: %s', $post->post_status));
            WP_CLI::line('');
        }

        foreach ($report as $collector_id => $collector_data) {
            WP_CLI::line(WP_CLI::colorize(sprintf('%%G=== %s ===%%n', strtoupper(str_replace('_', ' ', $collector_id)))));

            if (empty($collector_data['data'])) {
                WP_CLI::line('No data available');
                WP_CLI::line('');
                continue;
            }

            // Display based on collector type
            switch ($collector_id) {
                case 'db_queries':
                    WP_CLI::line(sprintf('Total Queries: %d', $collector_data['data']['total_queries']));
                    WP_CLI::line(sprintf('Total Time: %.4fs', $collector_data['data']['total_time']));
                    WP_CLI::line('');
                    WP_CLI::line('Top 10 Queries:');
                    foreach ($collector_data['data']['queries'] as $i => $query) {
                        WP_CLI::line(sprintf('%d. [%.4fs] %s', $i + 1, $query['time'], substr($query['sql'], 0, 100) . '...'));
                        WP_CLI::line(sprintf('   Caller: %s | Component: %s', $query['caller'], $query['component']));
                    }
                    break;

                case 'http':
                    WP_CLI::line(sprintf('Total HTTP Requests: %d', $collector_data['data']['total_requests']));
                    foreach ($collector_data['data']['requests'] as $i => $request) {
                        WP_CLI::line(sprintf(
                            '%d. [%s] %s - Status: %d (%.4fs)',
                            $i + 1,
                            $request['method'],
                            $request['url'],
                            $request['response_code'],
                            $request['time']
                        ));
                    }
                    break;

                case 'hooks':
                    WP_CLI::line(sprintf('Total Hooks Fired: %d', $collector_data['data']['total_hooks']));
                    WP_CLI::line('Sample Hooks (first 20):');
                    WP_CLI::line(implode(', ', $collector_data['data']['hooks']));
                    break;

                case 'theme':
                    foreach ($collector_data['data'] as $key => $value) {
                        if (is_array($value)) {
                            WP_CLI::line(sprintf('%s: %s', ucfirst(str_replace('_', ' ', $key)), implode(', ', $value)));
                        } else {
                            WP_CLI::line(sprintf('%s: %s', ucfirst(str_replace('_', ' ', $key)), $value));
                        }
                    }
                    break;

                case 'conditionals':
                    $true_conditionals = array();
                    foreach ($collector_data['data'] as $key => $value) {
                        if ($value === true) {
                            $true_conditionals[] = $key;
                        }
                    }
                    WP_CLI::line('True Conditionals: ' . implode(', ', $true_conditionals));
                    break;

                case 'cache':
                    WP_CLI::line(sprintf(
                        'Hits: %d | Misses: %d | Total: %d',
                        $collector_data['data']['hits'],
                        $collector_data['data']['misses'],
                        $collector_data['data']['total']
                    ));
                    break;

                default:
                    // Generic display
                    foreach ($collector_data['data'] as $key => $value) {
                        if (is_scalar($value)) {
                            WP_CLI::line(sprintf('%s: %s', $key, $value));
                        } elseif (is_array($value) && count($value) < 10) {
                            WP_CLI::line(sprintf('%s: %s', $key, json_encode($value)));
                        }
                    }
                    break;
            }

            WP_CLI::line('');
        }

        WP_CLI::success('Inspection complete!');
    }
}

// Register the commands
if (class_exists('WP_CLI')) {
    WP_CLI::add_command('qm', 'Praison_QMCLI_Commands');
}
