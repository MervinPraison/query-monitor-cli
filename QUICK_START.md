# Query Monitor CLI - Quick Start Implementation

This guide provides a minimal working example to get started quickly.

---

## Minimal Working Example

### Step 1: Create Main Plugin File

Create `query-monitor-cli.php`:

```php
<?php
/**
 * Plugin Name: Query Monitor CLI
 * Description: WP-CLI commands for Query Monitor
 * Version: 0.1.0
 * Requires Plugins: query-monitor
 * Requires at least: 6.0
 * Requires PHP: 7.4
 * Author: Your Name
 * License: GPL v2 or later
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Only load in WP-CLI context
if ( ! defined( 'WP_CLI' ) || ! WP_CLI ) {
    return;
}

// Check if Query Monitor is available
if ( ! class_exists( 'QueryMonitor' ) ) {
    WP_CLI::warning( 'Query Monitor plugin is required. Please install and activate it first.' );
    return;
}

/**
 * Query Monitor CLI commands
 */
class QM_CLI_Commands {
    
    /**
     * Display Query Monitor environment information
     * 
     * ## OPTIONS
     * 
     * [--format=<format>]
     * : Output format (table, json)
     * ---
     * default: table
     * options:
     *   - table
     *   - json
     * ---
     * 
     * ## EXAMPLES
     * 
     *     wp qm env
     *     wp qm env --format=json
     */
    public function env( $args, $assoc_args ) {
        // Force QM to load in CLI context
        if ( ! defined( 'QM_TESTS' ) ) {
            define( 'QM_TESTS', true );
        }
        
        // Ensure collectors are loaded
        if ( ! did_action( 'qm/collectors' ) ) {
            do_action( 'plugins_loaded' );
        }
        
        // Get environment collector
        $collector = QM_Collectors::get( 'environment' );
        
        if ( ! $collector ) {
            WP_CLI::error( 'Environment collector not found.' );
        }
        
        // Process the collector
        $collector->process();
        
        // Get data
        $data = $collector->get_data();
        
        // Format output
        $format = $assoc_args['format'] ?? 'table';
        
        if ( $format === 'json' ) {
            WP_CLI::line( json_encode( array(
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
            ), JSON_PRETTY_PRINT ) );
        } else {
            WP_CLI::line( '=== PHP Information ===' );
            WP_CLI::line( sprintf( 'Version: %s', $data->php['version'] ?? 'N/A' ) );
            WP_CLI::line( sprintf( 'Memory Limit: %s', $data->php['memory_limit'] ?? 'N/A' ) );
            WP_CLI::line( sprintf( 'Max Execution Time: %s', $data->php['max_execution_time'] ?? 'N/A' ) );
            WP_CLI::line( '' );
            
            WP_CLI::line( '=== WordPress Information ===' );
            WP_CLI::line( sprintf( 'Version: %s', $data->wp['version'] ?? 'N/A' ) );
            WP_CLI::line( sprintf( 'Multisite: %s', ( $data->wp['multisite'] ?? false ) ? 'Yes' : 'No' ) );
            WP_CLI::line( '' );
            
            WP_CLI::line( '=== Database Information ===' );
            WP_CLI::line( sprintf( 'Extension: %s', $data->db['extension'] ?? 'N/A' ) );
            WP_CLI::line( sprintf( 'Server: %s', $data->db['server'] ?? 'N/A' ) );
            WP_CLI::line( sprintf( 'Version: %s', $data->db['version'] ?? 'N/A' ) );
        }
    }
    
    /**
     * Monitor database queries for a command
     * 
     * ## OPTIONS
     * 
     * <command>...
     * : The WP-CLI command to monitor
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
     *     wp qm db post list
     *     wp qm db post list --format=json
     */
    public function db( $args, $assoc_args ) {
        // Force QM to load
        if ( ! defined( 'QM_TESTS' ) ) {
            define( 'QM_TESTS', true );
        }
        
        // Ensure collectors are loaded
        if ( ! did_action( 'qm/collectors' ) ) {
            do_action( 'plugins_loaded' );
        }
        
        // Build command string
        $command = implode( ' ', $args );
        
        // Start monitoring
        $start_time = microtime( true );
        
        // Execute the command
        ob_start();
        try {
            $result = WP_CLI::runcommand( $command, array(
                'return' => true,
                'launch' => false,
                'exit_error' => false,
            ) );
            $output = ob_get_clean();
        } catch ( Exception $e ) {
            ob_get_clean();
            WP_CLI::error( 'Command execution failed: ' . $e->getMessage() );
        }
        
        // Process collectors
        QM_Collectors::init()->process();
        
        // Get database queries
        $db_collector = QM_Collectors::get( 'db_queries' );
        $db_data = $db_collector->get_data();
        
        $execution_time = microtime( true ) - $start_time;
        
        // Format output
        $format = $assoc_args['format'] ?? 'table';
        
        WP_CLI::line( sprintf(
            'Command: %s | Queries: %d | DB Time: %.4fs | Total Time: %.4fs',
            $command,
            $db_data->total_qs ?? 0,
            $db_data->total_time ?? 0,
            $execution_time
        ) );
        WP_CLI::line( '' );
        
        if ( $format === 'json' ) {
            $queries = array();
            foreach ( $db_data->rows ?? array() as $i => $query ) {
                $queries[] = array(
                    'index' => $i + 1,
                    'time' => $query['ltime'],
                    'type' => $query['type'],
                    'caller' => $query['caller_name'],
                    'sql' => $query['sql'],
                );
            }
            WP_CLI::line( json_encode( $queries, JSON_PRETTY_PRINT ) );
        } else {
            $items = array();
            foreach ( $db_data->rows ?? array() as $i => $query ) {
                $items[] = array(
                    '#' => $i + 1,
                    'Time' => sprintf( '%.4fs', $query['ltime'] ),
                    'Type' => $query['type'],
                    'Caller' => substr( $query['caller_name'], 0, 30 ),
                    'SQL' => substr( $query['sql'], 0, 60 ) . '...',
                );
            }
            
            if ( ! empty( $items ) ) {
                WP_CLI\Utils\format_items( 'table', $items, array( '#', 'Time', 'Type', 'Caller', 'SQL' ) );
            } else {
                WP_CLI::line( 'No queries recorded.' );
            }
        }
    }
    
    /**
     * Profile a WP-CLI command
     * 
     * ## OPTIONS
     * 
     * <command>...
     * : The WP-CLI command to profile
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
     *     wp qm profile post list
     *     wp qm profile cache flush --format=json
     */
    public function profile( $args, $assoc_args ) {
        // Force QM to load
        if ( ! defined( 'QM_TESTS' ) ) {
            define( 'QM_TESTS', true );
        }
        
        // Ensure collectors are loaded
        if ( ! did_action( 'qm/collectors' ) ) {
            do_action( 'plugins_loaded' );
        }
        
        // Build command string
        $command = implode( ' ', $args );
        
        // Start monitoring
        $start_time = microtime( true );
        $start_memory = memory_get_usage();
        
        // Execute the command
        ob_start();
        try {
            $result = WP_CLI::runcommand( $command, array(
                'return' => true,
                'launch' => false,
                'exit_error' => false,
            ) );
            $output = ob_get_clean();
        } catch ( Exception $e ) {
            ob_get_clean();
            WP_CLI::error( 'Command execution failed: ' . $e->getMessage() );
        }
        
        $execution_time = microtime( true ) - $start_time;
        $memory_used = memory_get_peak_usage() - $start_memory;
        
        // Process collectors
        QM_Collectors::init()->process();
        
        // Get data from collectors
        $overview = QM_Collectors::get( 'overview' )->get_data();
        $db_data = QM_Collectors::get( 'db_queries' )->get_data();
        
        // Prepare profile data
        $profile = array(
            'command' => $command,
            'execution_time' => $execution_time,
            'memory_peak' => memory_get_peak_usage(),
            'memory_used' => $memory_used,
            'memory_limit' => $overview->memory_limit ?? 0,
            'db_queries' => $db_data->total_qs ?? 0,
            'db_time' => $db_data->total_time ?? 0,
        );
        
        // Format output
        $format = $assoc_args['format'] ?? 'table';
        
        if ( $format === 'json' ) {
            WP_CLI::line( json_encode( $profile, JSON_PRETTY_PRINT ) );
        } else {
            WP_CLI::line( '=== Performance Profile ===' );
            WP_CLI::line( sprintf( 'Command: %s', $profile['command'] ) );
            WP_CLI::line( sprintf( 'Execution Time: %.4fs', $profile['execution_time'] ) );
            WP_CLI::line( sprintf( 'Peak Memory: %s', size_format( $profile['memory_peak'] ) ) );
            WP_CLI::line( sprintf( 'Memory Used: %s', size_format( $profile['memory_used'] ) ) );
            WP_CLI::line( sprintf( 'Database Queries: %d', $profile['db_queries'] ) );
            WP_CLI::line( sprintf( 'Database Time: %.4fs', $profile['db_time'] ) );
        }
    }
}

// Register the commands
WP_CLI::add_command( 'qm', 'QM_CLI_Commands' );
```

---

## Installation & Testing

### 1. Install the Plugin

```bash
# Navigate to your WordPress plugins directory
cd /path/to/wordpress/wp-content/plugins

# Create plugin directory
mkdir query-monitor-cli
cd query-monitor-cli

# Create the main plugin file
# Copy the code above into query-monitor-cli.php
```

### 2. Activate Query Monitor

```bash
wp plugin install query-monitor --activate
```

### 3. Activate Query Monitor CLI

```bash
wp plugin activate query-monitor-cli
```

### 4. Test the Commands

```bash
# Test environment command
wp qm env

# Test with JSON output
wp qm env --format=json

# Test database monitoring
wp qm db post list

# Test with specific post type
wp qm db post list --post_type=page

# Test profiling
wp qm profile post list

# Test with JSON output
wp qm profile cache flush --format=json
```

---

## Expected Output Examples

### Environment Command

```
=== PHP Information ===
Version: 8.1.0
Memory Limit: 256M
Max Execution Time: 30

=== WordPress Information ===
Version: 6.4.2
Multisite: No

=== Database Information ===
Extension: mysqli
Server: MySQL
Version: 8.0.35
```

### Database Command

```
Command: post list | Queries: 5 | DB Time: 0.0234s | Total Time: 0.1456s

+---+----------+--------+------------------------------+--------------------------------------------------------------+
| # | Time     | Type   | Caller                       | SQL                                                          |
+---+----------+--------+------------------------------+--------------------------------------------------------------+
| 1 | 0.0012s  | SELECT | WP_Query->get_posts          | SELECT SQL_CALC_FOUND_ROWS wp_posts.ID FROM wp_posts...     |
| 2 | 0.0008s  | SELECT | WP_Query->get_posts          | SELECT FOUND_ROWS()                                          |
| 3 | 0.0005s  | SELECT | get_post_meta                | SELECT post_id, meta_key, meta_value FROM wp_postmeta...    |
+---+----------+--------+------------------------------+--------------------------------------------------------------+
```

### Profile Command

```
=== Performance Profile ===
Command: post list
Execution Time: 0.1456s
Peak Memory: 45.2 MB
Memory Used: 12.3 MB
Database Queries: 5
Database Time: 0.0234s
```

---

## Troubleshooting

### Issue: "Query Monitor plugin is required"

**Solution**: Install and activate Query Monitor first:
```bash
wp plugin install query-monitor --activate
```

### Issue: "Environment collector not found"

**Solution**: This means Query Monitor collectors aren't loading. Check:
1. Query Monitor is activated
2. No PHP errors preventing QM from loading
3. Try: `wp plugin list` to verify both plugins are active

### Issue: No queries recorded

**Solution**: 
1. Ensure the command you're monitoring actually runs queries
2. Check if `SAVEQUERIES` is defined as `false` somewhere
3. Try a simple command like `wp post list` first

### Issue: Command execution fails

**Solution**:
1. Test the command without `wp qm` first: `wp post list`
2. Check for syntax errors in the command
3. Use `--debug` flag: `wp qm db post list --debug`

---

## Next Steps

Once this minimal version is working:

1. **Add more collectors**: HTTP, hooks, errors, etc.
2. **Improve formatting**: Better tables, colors, filtering
3. **Add options**: Thresholds, filters, sorting
4. **Create separate command classes**: Better organization
5. **Add comprehensive error handling**
6. **Write tests**
7. **Create documentation**

---

## Extending the Plugin

### Add a New Command

```php
/**
 * Monitor HTTP requests
 */
public function http( $args, $assoc_args ) {
    // Force QM to load
    if ( ! defined( 'QM_TESTS' ) ) {
        define( 'QM_TESTS', true );
    }
    
    // Ensure collectors are loaded
    if ( ! did_action( 'qm/collectors' ) ) {
        do_action( 'plugins_loaded' );
    }
    
    // Build and execute command
    $command = implode( ' ', $args );
    
    ob_start();
    WP_CLI::runcommand( $command, array(
        'return' => true,
        'launch' => false,
        'exit_error' => false,
    ) );
    ob_get_clean();
    
    // Process collectors
    QM_Collectors::init()->process();
    
    // Get HTTP data
    $http_collector = QM_Collectors::get( 'http' );
    $http_data = $http_collector->get_data();
    
    // Display results
    if ( empty( $http_data->http ) ) {
        WP_CLI::line( 'No HTTP requests recorded.' );
        return;
    }
    
    $items = array();
    foreach ( $http_data->http as $request ) {
        $items[] = array(
            'URL' => $request['url'],
            'Method' => $request['args']['method'] ?? 'GET',
            'Status' => is_wp_error( $request['response'] ) ? 'Error' : $request['response']['response']['code'],
            'Time' => sprintf( '%.4fs', $request['ltime'] ),
        );
    }
    
    WP_CLI\Utils\format_items( 'table', $items, array( 'URL', 'Method', 'Status', 'Time' ) );
}
```

---

## Summary

This quick start provides:
- ✅ Single-file implementation
- ✅ Three working commands (env, db, profile)
- ✅ JSON and table output formats
- ✅ Integration with Query Monitor collectors
- ✅ Ready to test immediately

**Total Lines of Code**: ~350 lines for a working plugin!

---

**Status**: Ready to Use  
**Last Updated**: 2025-11-08
