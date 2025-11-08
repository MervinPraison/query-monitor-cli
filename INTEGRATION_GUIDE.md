# Query Monitor CLI - Integration Guide

## Overview

This guide provides detailed technical steps for integrating with Query Monitor's existing architecture to build the CLI functionality.

---

## Understanding Query Monitor Architecture

### Core Components

1. **QueryMonitor Class** (`classes/QueryMonitor.php`)
   - Main plugin initialization
   - Loads collectors and dispatchers
   - Manages plugin lifecycle

2. **Collectors** (`classes/Collectors.php`)
   - Container for all data collectors
   - Singleton pattern: `QM_Collectors::init()`
   - Methods:
     - `add()` - Register a collector
     - `get($id)` - Retrieve a collector by ID
     - `process()` - Process all collectors
     - `cease()` - Stop and discard data

3. **Collector Classes** (`collectors/*.php`)
   - Each collector extends `QM_DataCollector`
   - Implements data collection logic
   - Stores data in corresponding `QM_Data_*` objects

4. **Data Objects** (`data/*.php`)
   - DTOs (Data Transfer Objects)
   - Extend `QM_Data` base class
   - Store collected information

5. **Dispatchers** (`dispatchers/*.php`)
   - Handle output rendering
   - We won't use these for CLI (we'll create our own formatter)

---

## Key Integration Points

### 1. Query Monitor Initialization Flow

```php
// In query-monitor.php (lines 63-65)
if ( defined( 'WP_CLI' ) && WP_CLI ) {
    QM_CLI::init( __FILE__ );
}

// Lines 75-79: QM doesn't load for CLI by default
if ( 'cli' === php_sapi_name() && ! defined( 'QM_TESTS' ) ) {
    return; // QM exits here for CLI
}
```

**Key Insight**: Query Monitor currently exits for CLI contexts. We need to work around this.

### 2. Collector Registration

```php
// In QueryMonitor::action_plugins_loaded() (lines 148-150)
foreach ( apply_filters( 'qm/collectors', array(), $this ) as $collector ) {
    QM_Collectors::add( $collector );
}
```

**Available Collectors** (automatically registered):
- `db_queries` - Database queries
- `overview` - Performance overview
- `http` - HTTP requests
- `hooks` - WordPress hooks
- `php_errors` - PHP errors
- `environment` - Environment info
- `caps` - Capability checks
- `transients` - Transient operations
- And more...

### 3. Data Collection Process

```php
// In QM_Collectors::process() (lines 73-95)
public function process() {
    foreach ( $this as $collector ) {
        $collector->tear_down();
        $timer = new QM_Timer();
        $timer->start();
        $collector->process();
        $collector->process_concerns();
        $collector->set_timer( $timer->stop() );
    }
    
    foreach ( $this as $collector ) {
        $collector->post_process();
    }
}
```

### 4. Accessing Collected Data

```php
// Get a specific collector
$db_collector = QM_Collectors::get('db_queries');

// Get the data object
$data = $db_collector->get_data();

// Access data properties
$total_queries = $data->total_qs;
$total_time = $data->total_time;
$queries = $data->rows;
```

---

## Implementation Strategy

### Phase 1: Basic Infrastructure

#### Step 1: Create Plugin Structure

```
query-monitor-cli/
├── query-monitor-cli.php          # Main plugin file
├── composer.json                   # Dependencies
├── includes/
│   ├── class-qm-cli-base.php      # Base command class
│   ├── class-qm-cli-runner.php    # Command execution wrapper
│   ├── class-qm-cli-formatter.php # Output formatting
│   └── commands/
│       ├── class-qm-env-command.php
│       ├── class-qm-db-command.php
│       └── class-qm-profile-command.php
```

#### Step 2: Main Plugin File

```php
<?php
/**
 * Plugin Name: Query Monitor CLI
 * Description: WP-CLI commands for Query Monitor
 * Version: 0.1.0
 * Requires Plugins: query-monitor
 * Requires at least: 6.0
 * Requires PHP: 7.4
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
    WP_CLI::warning( 'Query Monitor plugin is required but not found.' );
    return;
}

// Load dependencies
require_once __DIR__ . '/includes/class-qm-cli-base.php';
require_once __DIR__ . '/includes/class-qm-cli-runner.php';
require_once __DIR__ . '/includes/class-qm-cli-formatter.php';

// Register commands
foreach ( glob( __DIR__ . '/includes/commands/*.php' ) as $command_file ) {
    require_once $command_file;
}
```

#### Step 3: Base Command Class

```php
<?php
/**
 * Base class for QM CLI commands
 */
abstract class QM_CLI_Base {
    
    /**
     * Check if Query Monitor is available
     */
    protected function check_qm_available() {
        if ( ! class_exists( 'QueryMonitor' ) ) {
            WP_CLI::error( 'Query Monitor plugin is not active.' );
        }
    }
    
    /**
     * Initialize Query Monitor collectors
     * This bypasses the CLI check in Query Monitor
     */
    protected function init_qm() {
        // Force QM to load even in CLI context
        if ( ! defined( 'QM_TESTS' ) ) {
            define( 'QM_TESTS', true );
        }
        
        // Initialize QueryMonitor if not already done
        if ( ! did_action( 'qm/collectors' ) ) {
            // Manually trigger collector registration
            do_action( 'plugins_loaded' );
        }
    }
    
    /**
     * Get a specific collector
     */
    protected function get_collector( $id ) {
        return QM_Collectors::get( $id );
    }
    
    /**
     * Process all collectors
     */
    protected function process_collectors() {
        $collectors = QM_Collectors::init();
        $collectors->process();
    }
    
    /**
     * Format output based on format argument
     */
    protected function format_output( $data, $format = 'table' ) {
        $formatter = new QM_CLI_Formatter();
        return $formatter->format( $data, $format );
    }
}
```

#### Step 4: Command Runner

```php
<?php
/**
 * Wrapper to execute WP-CLI commands with QM monitoring
 */
class QM_CLI_Runner {
    
    /**
     * Execute a WP-CLI command with Query Monitor instrumentation
     * 
     * @param string $command The WP-CLI command to execute
     * @return array Collected data from all collectors
     */
    public static function run_with_monitoring( $command ) {
        // Initialize Query Monitor
        if ( ! defined( 'QM_TESTS' ) ) {
            define( 'QM_TESTS', true );
        }
        
        // Ensure collectors are loaded
        if ( ! did_action( 'qm/collectors' ) ) {
            do_action( 'plugins_loaded' );
        }
        
        // Start monitoring
        $start_time = microtime( true );
        
        // Execute the command
        // Note: We need to use WP_CLI::runcommand() or similar
        // This is a simplified version
        ob_start();
        $result = WP_CLI::runcommand( $command, array(
            'return' => true,
            'launch' => false,
            'exit_error' => false,
        ) );
        $output = ob_get_clean();
        
        // Process collectors to gather data
        QM_Collectors::init()->process();
        
        // Collect data from all collectors
        $collected_data = array();
        foreach ( QM_Collectors::init() as $id => $collector ) {
            $collected_data[ $id ] = $collector->get_data();
        }
        
        return array(
            'command' => $command,
            'output' => $output,
            'result' => $result,
            'data' => $collected_data,
            'execution_time' => microtime( true ) - $start_time,
        );
    }
}
```

#### Step 5: Output Formatter

```php
<?php
/**
 * Format collected data for CLI output
 */
class QM_CLI_Formatter {
    
    /**
     * Format data based on output format
     */
    public function format( $data, $format = 'table' ) {
        switch ( $format ) {
            case 'json':
                return $this->format_json( $data );
            case 'csv':
                return $this->format_csv( $data );
            case 'yaml':
                return $this->format_yaml( $data );
            case 'table':
            default:
                return $this->format_table( $data );
        }
    }
    
    /**
     * Format as JSON
     */
    private function format_json( $data ) {
        return json_encode( $data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES );
    }
    
    /**
     * Format as table using WP_CLI\Utils\format_items
     */
    private function format_table( $data ) {
        // Convert data to array of items suitable for table display
        // This will vary based on data type
        return $data;
    }
    
    /**
     * Format as CSV
     */
    private function format_csv( $data ) {
        // Convert to CSV format
        return $data;
    }
    
    /**
     * Format as YAML
     */
    private function format_yaml( $data ) {
        // Convert to YAML format (requires symfony/yaml or similar)
        return $data;
    }
}
```

---

## Specific Command Implementations

### Example 1: Environment Command

```php
<?php
/**
 * Display Query Monitor environment information
 */
class QM_Env_Command extends QM_CLI_Base {
    
    /**
     * Display environment information
     * 
     * ## OPTIONS
     * 
     * [--format=<format>]
     * : Output format (table, json, yaml)
     * ---
     * default: table
     * options:
     *   - table
     *   - json
     *   - yaml
     * ---
     * 
     * ## EXAMPLES
     * 
     *     wp qm env
     *     wp qm env --format=json
     */
    public function __invoke( $args, $assoc_args ) {
        $this->check_qm_available();
        $this->init_qm();
        
        // Get environment collector
        $collector = $this->get_collector( 'environment' );
        
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
            WP_CLI::line( json_encode( $data, JSON_PRETTY_PRINT ) );
        } else {
            // Display as table
            $this->display_env_table( $data );
        }
    }
    
    private function display_env_table( $data ) {
        WP_CLI::line( 'PHP Information:' );
        WP_CLI::line( '  Version: ' . $data->php['version'] );
        WP_CLI::line( '  Memory Limit: ' . $data->php['memory_limit'] );
        // ... more fields
        
        WP_CLI::line( '' );
        WP_CLI::line( 'WordPress Information:' );
        WP_CLI::line( '  Version: ' . $data->wp['version'] );
        // ... more fields
    }
}

// Register the command
WP_CLI::add_command( 'qm env', 'QM_Env_Command' );
```

### Example 2: Database Command

```php
<?php
/**
 * Monitor database queries
 */
class QM_DB_Command extends QM_CLI_Base {
    
    /**
     * Monitor database queries for a command
     * 
     * ## OPTIONS
     * 
     * <command>
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
     *     wp qm db "post list"
     *     wp qm db "post list" --slow-only --threshold=0.1
     *     wp qm db "post list" --format=json
     */
    public function __invoke( $args, $assoc_args ) {
        $this->check_qm_available();
        
        $command = $args[0];
        $format = $assoc_args['format'] ?? 'table';
        $slow_only = isset( $assoc_args['slow-only'] );
        $threshold = floatval( $assoc_args['threshold'] ?? 0.05 );
        
        // Run command with monitoring
        $result = QM_CLI_Runner::run_with_monitoring( $command );
        
        // Get database queries data
        $db_data = $result['data']['db_queries'];
        
        // Filter if needed
        if ( $slow_only && isset( $db_data->expensive ) ) {
            $queries = $db_data->expensive;
        } else {
            $queries = $db_data->rows;
        }
        
        // Display summary
        WP_CLI::line( sprintf(
            'Total Queries: %d | Total Time: %.4fs',
            $db_data->total_qs,
            $db_data->total_time
        ) );
        WP_CLI::line( '' );
        
        // Format output
        if ( $format === 'json' ) {
            WP_CLI::line( json_encode( $queries, JSON_PRETTY_PRINT ) );
        } else {
            $this->display_queries_table( $queries );
        }
    }
    
    private function display_queries_table( $queries ) {
        $items = array();
        
        foreach ( $queries as $i => $query ) {
            $items[] = array(
                '#' => $i + 1,
                'Time' => sprintf( '%.4fs', $query['ltime'] ),
                'Type' => $query['type'],
                'Caller' => $query['caller_name'],
                'SQL' => substr( $query['sql'], 0, 80 ) . '...',
            );
        }
        
        WP_CLI\Utils\format_items( 'table', $items, array( '#', 'Time', 'Type', 'Caller', 'SQL' ) );
    }
}

WP_CLI::add_command( 'qm db', 'QM_DB_Command' );
```

### Example 3: Profile Command

```php
<?php
/**
 * Profile WP-CLI command performance
 */
class QM_Profile_Command extends QM_CLI_Base {
    
    /**
     * Profile a WP-CLI command
     * 
     * ## OPTIONS
     * 
     * <command>
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
     *     wp qm profile "post list"
     *     wp qm profile "cache flush" --format=json
     */
    public function __invoke( $args, $assoc_args ) {
        $this->check_qm_available();
        
        $command = $args[0];
        $format = $assoc_args['format'] ?? 'table';
        
        // Run command with monitoring
        $result = QM_CLI_Runner::run_with_monitoring( $command );
        
        // Get overview data
        $overview = $result['data']['overview'];
        $db_data = $result['data']['db_queries'];
        
        // Prepare profile data
        $profile = array(
            'command' => $command,
            'execution_time' => $result['execution_time'],
            'memory_peak' => $overview->memory,
            'memory_limit' => $overview->memory_limit,
            'memory_usage_percent' => $overview->memory_usage,
            'db_queries' => $db_data->total_qs,
            'db_time' => $db_data->total_time,
        );
        
        // Format output
        if ( $format === 'json' ) {
            WP_CLI::line( json_encode( $profile, JSON_PRETTY_PRINT ) );
        } else {
            $this->display_profile_table( $profile );
        }
    }
    
    private function display_profile_table( $profile ) {
        WP_CLI::line( 'Performance Profile' );
        WP_CLI::line( '==================' );
        WP_CLI::line( '' );
        WP_CLI::line( sprintf( 'Command: %s', $profile['command'] ) );
        WP_CLI::line( sprintf( 'Execution Time: %.4fs', $profile['execution_time'] ) );
        WP_CLI::line( sprintf( 'Peak Memory: %s', size_format( $profile['memory_peak'] ) ) );
        WP_CLI::line( sprintf( 'Memory Usage: %.2f%%', $profile['memory_usage_percent'] ) );
        WP_CLI::line( sprintf( 'Database Queries: %d', $profile['db_queries'] ) );
        WP_CLI::line( sprintf( 'Database Time: %.4fs', $profile['db_time'] ) );
    }
}

WP_CLI::add_command( 'qm profile', 'QM_Profile_Command' );
```

---

## Critical Integration Challenges & Solutions

### Challenge 1: QM Exits for CLI Context

**Problem**: Query Monitor exits early when running in CLI (line 75-79 in query-monitor.php)

**Solution**: 
```php
// Define QM_TESTS constant before QM loads
if ( ! defined( 'QM_TESTS' ) ) {
    define( 'QM_TESTS', true );
}
```

This bypasses the CLI check and allows QM to load.

### Challenge 2: Collectors Not Auto-Registered

**Problem**: Collectors are registered on `plugins_loaded` action, which may not fire in our context

**Solution**:
```php
// Manually trigger the action if needed
if ( ! did_action( 'qm/collectors' ) ) {
    do_action( 'plugins_loaded' );
}

// Or manually load and register collectors
$qm = QueryMonitor::init( plugin_dir_path( __FILE__ ) . 'query-monitor/query-monitor.php' );
$qm->action_plugins_loaded();
```

### Challenge 3: Running Commands in Isolation

**Problem**: We need to monitor a specific command without interference

**Solution**: Use `WP_CLI::runcommand()` with proper options:
```php
$result = WP_CLI::runcommand( $command, array(
    'return' => true,      // Return output instead of printing
    'launch' => false,     // Don't launch in separate process
    'exit_error' => false, // Don't exit on error
) );
```

### Challenge 4: Data Persistence

**Problem**: QM data is typically output immediately, not stored

**Solution**: Process collectors and extract data before it's discarded:
```php
// Process all collectors
QM_Collectors::init()->process();

// Extract data immediately
$data = array();
foreach ( QM_Collectors::init() as $id => $collector ) {
    $data[ $id ] = $collector->get_data();
}

// Now we have persistent data to work with
```

---

## Testing Strategy

### Unit Tests

```php
class QM_CLI_Test extends WP_UnitTestCase {
    
    public function test_qm_available() {
        $this->assertTrue( class_exists( 'QueryMonitor' ) );
    }
    
    public function test_collector_registration() {
        $collector = QM_Collectors::get( 'db_queries' );
        $this->assertInstanceOf( 'QM_Collector_DB_Queries', $collector );
    }
    
    public function test_command_execution() {
        $result = QM_CLI_Runner::run_with_monitoring( 'post list --post_type=post' );
        $this->assertArrayHasKey( 'data', $result );
        $this->assertArrayHasKey( 'db_queries', $result['data'] );
    }
}
```

### Integration Tests

```bash
# Test environment command
wp qm env --format=json | jq '.php.version'

# Test database monitoring
wp qm db "post list" --format=json | jq '.[] | select(.ltime > 0.05)'

# Test profiling
wp qm profile "cache flush"
```

---

## Next Steps

1. **Create plugin structure** as outlined above
2. **Implement base classes** (QM_CLI_Base, QM_CLI_Runner, QM_CLI_Formatter)
3. **Start with simple commands** (env, profile)
4. **Add database monitoring** (db command)
5. **Expand to other collectors** (http, hooks, errors, etc.)
6. **Add comprehensive tests**
7. **Create documentation**
8. **Package and distribute**

---

## Key Takeaways

1. **Don't duplicate code** - Use QM's existing collectors
2. **Bypass CLI check** - Use `QM_TESTS` constant
3. **Manual initialization** - Trigger `plugins_loaded` if needed
4. **Extract data early** - Process collectors and store data before it's lost
5. **Use WP_CLI utilities** - Leverage `WP_CLI::runcommand()` and formatting functions
6. **Follow WP-CLI conventions** - Use standard argument patterns and output formats

---

## Resources

- Query Monitor GitHub: https://github.com/johnbillion/query-monitor
- WP-CLI Commands Cookbook: https://make.wordpress.org/cli/handbook/guides/commands-cookbook/
- WP-CLI Internal API: https://make.wordpress.org/cli/handbook/references/internal-api/

---

**Document Version**: 1.0  
**Last Updated**: 2025-11-08  
**Status**: Ready for Implementation
