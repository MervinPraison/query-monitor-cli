<?php
/**
 * WP-CLI commands for Query Monitor
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class QM_CLI_Commands extends QM_CLI_Base {
	
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
	public function env( $args, $assoc_args ) {
		$this->check_qm_available();
		$this->init_qm();
		
		// Process all collectors first to ensure dependencies are met
		$this->process_collectors();
		
		$collector = $this->get_collector( 'environment' );
		
		if ( ! $collector ) {
			WP_CLI::error( 'Environment collector not found.' );
		}
		
		$data = $collector->get_data();
		
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
		
		WP_CLI::success( 'Environment information retrieved.' );
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
	public function db( $args, $assoc_args ) {
		$this->check_qm_available();
		$this->init_qm();
		
		$format = $assoc_args['format'] ?? 'table';
		$slow_only = isset( $assoc_args['slow-only'] );
		$threshold = floatval( $assoc_args['threshold'] ?? 0.05 );
		
		if ( ! empty( $args ) ) {
			$command = implode( ' ', $args );
			
			ob_start();
			try {
				WP_CLI::runcommand( $command, array(
					'return' => true,
					'launch' => false,
					'exit_error' => false,
				) );
			} catch ( Exception $e ) {
				WP_CLI::warning( 'Command execution had issues: ' . $e->getMessage() );
			}
			ob_get_clean();
		}
		
		$this->process_collectors();
		
		$collector = $this->get_collector( 'db_queries' );
		$data = $collector->get_data();
		
		$queries = $data->rows ?? array();
		
		if ( $slow_only && isset( $data->expensive ) ) {
			$queries = array_filter( $queries, function( $query ) use ( $threshold ) {
				return $query['ltime'] >= $threshold;
			} );
		}
		
		WP_CLI::line( sprintf(
			'Total Queries: %d | Total Time: %.4fs',
			$data->total_qs ?? 0,
			$data->total_time ?? 0
		) );
		WP_CLI::line( '' );
		
		if ( $format === 'json' ) {
			$output = array();
			foreach ( $queries as $i => $query ) {
				$output[] = array(
					'index' => $i + 1,
					'time' => $query['ltime'],
					'type' => $query['type'],
					'caller' => $query['caller_name'],
					'sql' => $query['sql'],
				);
			}
			WP_CLI::line( json_encode( $output, JSON_PRETTY_PRINT ) );
		} else {
			$items = array();
			foreach ( $queries as $i => $query ) {
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
		
		WP_CLI::success( sprintf( 'Found %d queries.', count( $queries ) ) );
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
	public function profile( $args, $assoc_args ) {
		$this->check_qm_available();
		$this->init_qm();
		
		$format = $assoc_args['format'] ?? 'table';
		$command = ! empty( $args ) ? implode( ' ', $args ) : 'current state';
		
		$start_time = microtime( true );
		$start_memory = memory_get_usage();
		
		if ( ! empty( $args ) ) {
			ob_start();
			try {
				WP_CLI::runcommand( implode( ' ', $args ), array(
					'return' => true,
					'launch' => false,
					'exit_error' => false,
				) );
			} catch ( Exception $e ) {
				WP_CLI::warning( 'Command execution had issues: ' . $e->getMessage() );
			}
			ob_get_clean();
		}
		
		$execution_time = microtime( true ) - $start_time;
		$memory_used = memory_get_peak_usage() - $start_memory;
		
		$this->process_collectors();
		
		$overview = $this->get_collector( 'overview' );
		$db_collector = $this->get_collector( 'db_queries' );
		
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
		
		WP_CLI::success( 'Profile completed.' );
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
	public function http( $args, $assoc_args ) {
		$this->check_qm_available();
		$this->init_qm();
		
		$format = $assoc_args['format'] ?? 'table';
		
		if ( ! empty( $args ) ) {
			$command = implode( ' ', $args );
			
			ob_start();
			try {
				WP_CLI::runcommand( $command, array(
					'return' => true,
					'launch' => false,
					'exit_error' => false,
				) );
			} catch ( Exception $e ) {
				WP_CLI::warning( 'Command execution had issues: ' . $e->getMessage() );
			}
			ob_get_clean();
		}
		
		$this->process_collectors();
		
		$collector = $this->get_collector( 'http' );
		$data = $collector->get_data();
		
		$requests = $data->http ?? array();
		
		WP_CLI::line( sprintf( 'Total HTTP Requests: %d', count( $requests ) ) );
		WP_CLI::line( '' );
		
		if ( empty( $requests ) ) {
			WP_CLI::line( 'No HTTP requests recorded.' );
			WP_CLI::success( 'HTTP monitoring completed.' );
			return;
		}
		
		if ( $format === 'json' ) {
			$output = array();
			foreach ( $requests as $request ) {
				$output[] = array(
					'url' => $request['url'],
					'method' => $request['args']['method'] ?? 'GET',
					'status' => is_wp_error( $request['response'] ) ? 'Error' : ( $request['response']['response']['code'] ?? 'Unknown' ),
					'time' => $request['ltime'],
				);
			}
			WP_CLI::line( json_encode( $output, JSON_PRETTY_PRINT ) );
		} else {
			$items = array();
			foreach ( $requests as $request ) {
				$items[] = array(
					'URL' => substr( $request['url'], 0, 50 ) . '...',
					'Method' => $request['args']['method'] ?? 'GET',
					'Status' => is_wp_error( $request['response'] ) ? 'Error' : ( $request['response']['response']['code'] ?? 'Unknown' ),
					'Time' => sprintf( '%.4fs', $request['ltime'] ),
				);
			}
			
			WP_CLI\Utils\format_items( 'table', $items, array( 'URL', 'Method', 'Status', 'Time' ) );
		}
		
		WP_CLI::success( sprintf( 'Found %d HTTP requests.', count( $requests ) ) );
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
	public function hooks( $args, $assoc_args ) {
		$this->check_qm_available();
		$this->init_qm();
		
		$format = $assoc_args['format'] ?? 'table';
		
		if ( ! empty( $args ) ) {
			$command = implode( ' ', $args );
			
			ob_start();
			try {
				WP_CLI::runcommand( $command, array(
					'return' => true,
					'launch' => false,
					'exit_error' => false,
				) );
			} catch ( Exception $e ) {
				WP_CLI::warning( 'Command execution had issues: ' . $e->getMessage() );
			}
			ob_get_clean();
		}
		
		$this->process_collectors();
		
		$collector = $this->get_collector( 'hooks' );
		$data = $collector->get_data();
		
		$hooks = $data->hooks ?? array();
		
		WP_CLI::line( sprintf( 'Total Hooks: %d', count( $hooks ) ) );
		
		if ( $format === 'json' ) {
			WP_CLI::line( json_encode( $hooks, JSON_PRETTY_PRINT ) );
		} else {
			WP_CLI::line( 'Use --format=json to see detailed hook information.' );
		}
		
		WP_CLI::success( sprintf( 'Found %d hooks.', count( $hooks ) ) );
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
	public function errors( $args, $assoc_args ) {
		$this->check_qm_available();
		$this->init_qm();
		
		$format = $assoc_args['format'] ?? 'table';
		
		if ( ! empty( $args ) ) {
			$command = implode( ' ', $args );
			
			ob_start();
			try {
				WP_CLI::runcommand( $command, array(
					'return' => true,
					'launch' => false,
					'exit_error' => false,
				) );
			} catch ( Exception $e ) {
				WP_CLI::warning( 'Command execution had issues: ' . $e->getMessage() );
			}
			ob_get_clean();
		}
		
		$this->process_collectors();
		
		$collector = $this->get_collector( 'php_errors' );
		$data = $collector->get_data();
		
		$errors = $data->errors ?? array();
		
		WP_CLI::line( sprintf( 'Total PHP Errors: %d', count( $errors ) ) );
		
		if ( empty( $errors ) ) {
			WP_CLI::success( 'No PHP errors found!' );
			return;
		}
		
		if ( $format === 'json' ) {
			WP_CLI::line( json_encode( $errors, JSON_PRETTY_PRINT ) );
		} else {
			WP_CLI::line( 'Use --format=json to see detailed error information.' );
		}
		
		WP_CLI::warning( sprintf( 'Found %d PHP errors.', count( $errors ) ) );
	}
}

// Register the commands
if ( class_exists( 'WP_CLI' ) ) {
	WP_CLI::add_command( 'qm', 'QM_CLI_Commands' );
}
