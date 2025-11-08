<?php
/**
 * REST API endpoints for Query Monitor data
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class QM_REST_API {
	
	const NAMESPACE = 'query-monitor/v1';
	
	/**
	 * Initialize REST API routes
	 */
	public static function init() {
		add_action( 'rest_api_init', array( __CLASS__, 'register_routes' ) );
	}
	
	/**
	 * Register all REST API routes
	 */
	public static function register_routes() {
		// Environment endpoint
		register_rest_route( self::NAMESPACE, '/environment', array(
			'methods' => 'GET',
			'callback' => array( __CLASS__, 'get_environment' ),
			'permission_callback' => array( __CLASS__, 'check_permission' ),
		) );
		
		// Database queries endpoint
		register_rest_route( self::NAMESPACE, '/database', array(
			'methods' => 'POST',
			'callback' => array( __CLASS__, 'get_database_queries' ),
			'permission_callback' => array( __CLASS__, 'check_permission' ),
			'args' => array(
				'command' => array(
					'required' => false,
					'type' => 'string',
					'description' => 'Command to monitor',
				),
			),
		) );
		
		// Profile endpoint
		register_rest_route( self::NAMESPACE, '/profile', array(
			'methods' => 'POST',
			'callback' => array( __CLASS__, 'get_profile' ),
			'permission_callback' => array( __CLASS__, 'check_permission' ),
			'args' => array(
				'command' => array(
					'required' => false,
					'type' => 'string',
					'description' => 'Command to profile',
				),
			),
		) );
		
		// HTTP requests endpoint
		register_rest_route( self::NAMESPACE, '/http', array(
			'methods' => 'POST',
			'callback' => array( __CLASS__, 'get_http_requests' ),
			'permission_callback' => array( __CLASS__, 'check_permission' ),
			'args' => array(
				'command' => array(
					'required' => false,
					'type' => 'string',
					'description' => 'Command to monitor',
				),
			),
		) );
		
		// Hooks endpoint
		register_rest_route( self::NAMESPACE, '/hooks', array(
			'methods' => 'POST',
			'callback' => array( __CLASS__, 'get_hooks' ),
			'permission_callback' => array( __CLASS__, 'check_permission' ),
			'args' => array(
				'command' => array(
					'required' => false,
					'type' => 'string',
					'description' => 'Command to monitor',
				),
			),
		) );
		
		// PHP errors endpoint
		register_rest_route( self::NAMESPACE, '/errors', array(
			'methods' => 'POST',
			'callback' => array( __CLASS__, 'get_php_errors' ),
			'permission_callback' => array( __CLASS__, 'check_permission' ),
			'args' => array(
				'command' => array(
					'required' => false,
					'type' => 'string',
					'description' => 'Command to monitor',
				),
			),
		) );
	}
	
	/**
	 * Check if user has permission to access QM data
	 */
	public static function check_permission() {
		return current_user_can( 'view_query_monitor' ) || current_user_can( 'manage_options' );
	}
	
	/**
	 * Initialize Query Monitor collectors
	 */
	private static function init_qm() {
		static $initialized = false;
		
		if ( $initialized ) {
			return;
		}
		
		if ( ! defined( 'QM_TESTS' ) ) {
			define( 'QM_TESTS', true );
		}
		
		// Initialize QueryMonitor instance
		$qm_dir = WP_PLUGIN_DIR . '/query-monitor';
		$qm_file = $qm_dir . '/query-monitor.php';
		
		if ( file_exists( $qm_file ) ) {
			$qm = QueryMonitor::init( $qm_file );
			
			// Manually load and register collectors
			$collector_files = glob( $qm_dir . '/collectors/*.php' );
			if ( $collector_files ) {
				foreach ( $collector_files as $file ) {
					include_once $file;
				}
			}
			
			// Load data files
			$data_files = glob( $qm_dir . '/data/*.php' );
			if ( $data_files ) {
				foreach ( $data_files as $file ) {
					include_once $file;
				}
			}
			
			// Now apply the filter to register collectors
			$collectors = apply_filters( 'qm/collectors', array(), $qm );
			foreach ( $collectors as $collector ) {
				QM_Collectors::add( $collector );
			}
			
			$initialized = true;
		}
	}
	
	/**
	 * Get environment information
	 */
	public static function get_environment( $request ) {
		self::init_qm();
		
		// Process all collectors first to ensure dependencies are met
		QM_Collectors::init()->process();
		
		$collector = QM_Collectors::get( 'environment' );
		if ( ! $collector ) {
			return new WP_Error( 'collector_not_found', 'Environment collector not found', array( 'status' => 404 ) );
		}
		
		$data = $collector->get_data();
		
		return rest_ensure_response( array(
			'success' => true,
			'data' => array(
				'php' => array(
					'version' => $data->php['version'] ?? 'N/A',
					'memory_limit' => $data->php['memory_limit'] ?? 'N/A',
					'max_execution_time' => $data->php['max_execution_time'] ?? 'N/A',
					'extensions' => $data->php['extensions'] ?? array(),
				),
				'wordpress' => array(
					'version' => $data->wp['version'] ?? 'N/A',
					'multisite' => $data->wp['multisite'] ?? false,
					'debug_mode' => $data->wp['WP_DEBUG'] ?? false,
				),
				'database' => array(
					'extension' => $data->db['extension'] ?? 'N/A',
					'server' => $data->db['server'] ?? 'N/A',
					'version' => $data->db['version'] ?? 'N/A',
					'database' => $data->db['database'] ?? 'N/A',
				),
				'server' => array(
					'software' => $data->server['name'] ?? 'N/A',
					'version' => $data->server['version'] ?? 'N/A',
				),
			),
		) );
	}
	
	/**
	 * Get database queries
	 */
	public static function get_database_queries( $request ) {
		self::init_qm();
		
		// Process collectors to capture current state
		QM_Collectors::init()->process();
		
		$collector = QM_Collectors::get( 'db_queries' );
		if ( ! $collector ) {
			return new WP_Error( 'collector_not_found', 'Database collector not found', array( 'status' => 404 ) );
		}
		
		$data = $collector->get_data();
		
		$queries = array();
		foreach ( $data->rows ?? array() as $i => $query ) {
			$queries[] = array(
				'index' => $i + 1,
				'time' => $query['ltime'],
				'type' => $query['type'],
				'caller' => $query['caller_name'],
				'sql' => $query['sql'],
				'component' => isset( $query['component'] ) ? $query['component']->name : 'Unknown',
			);
		}
		
		return rest_ensure_response( array(
			'success' => true,
			'data' => array(
				'total_queries' => $data->total_qs ?? 0,
				'total_time' => $data->total_time ?? 0,
				'queries' => $queries,
			),
		) );
	}
	
	/**
	 * Get performance profile
	 */
	public static function get_profile( $request ) {
		self::init_qm();
		
		$start_time = microtime( true );
		$start_memory = memory_get_usage();
		
		// Process collectors
		QM_Collectors::init()->process();
		
		$overview = QM_Collectors::get( 'overview' );
		$db_collector = QM_Collectors::get( 'db_queries' );
		
		if ( ! $overview || ! $db_collector ) {
			return new WP_Error( 'collector_not_found', 'Required collectors not found', array( 'status' => 404 ) );
		}
		
		$overview_data = $overview->get_data();
		$db_data = $db_collector->get_data();
		
		$execution_time = microtime( true ) - $start_time;
		$memory_used = memory_get_peak_usage() - $start_memory;
		
		return rest_ensure_response( array(
			'success' => true,
			'data' => array(
				'execution_time' => $execution_time,
				'memory_peak' => memory_get_peak_usage(),
				'memory_used' => $memory_used,
				'memory_limit' => $overview_data->memory_limit ?? 0,
				'db_queries' => $db_data->total_qs ?? 0,
				'db_time' => $db_data->total_time ?? 0,
			),
		) );
	}
	
	/**
	 * Get HTTP requests
	 */
	public static function get_http_requests( $request ) {
		self::init_qm();
		
		QM_Collectors::init()->process();
		
		$collector = QM_Collectors::get( 'http' );
		if ( ! $collector ) {
			return new WP_Error( 'collector_not_found', 'HTTP collector not found', array( 'status' => 404 ) );
		}
		
		$data = $collector->get_data();
		
		$requests = array();
		foreach ( $data->http ?? array() as $request_data ) {
			$requests[] = array(
				'url' => $request_data['url'],
				'method' => $request_data['args']['method'] ?? 'GET',
				'status' => is_wp_error( $request_data['response'] ) ? 'Error' : ( $request_data['response']['response']['code'] ?? 'Unknown' ),
				'time' => $request_data['ltime'],
				'component' => isset( $request_data['component'] ) ? $request_data['component']->name : 'Unknown',
			);
		}
		
		return rest_ensure_response( array(
			'success' => true,
			'data' => array(
				'total_requests' => count( $requests ),
				'total_time' => $data->ltime ?? 0,
				'requests' => $requests,
			),
		) );
	}
	
	/**
	 * Get hooks information
	 */
	public static function get_hooks( $request ) {
		self::init_qm();
		
		QM_Collectors::init()->process();
		
		$collector = QM_Collectors::get( 'hooks' );
		if ( ! $collector ) {
			return new WP_Error( 'collector_not_found', 'Hooks collector not found', array( 'status' => 404 ) );
		}
		
		$data = $collector->get_data();
		
		return rest_ensure_response( array(
			'success' => true,
			'data' => array(
				'total_hooks' => count( $data->hooks ?? array() ),
				'hooks' => $data->hooks ?? array(),
			),
		) );
	}
	
	/**
	 * Get PHP errors
	 */
	public static function get_php_errors( $request ) {
		self::init_qm();
		
		QM_Collectors::init()->process();
		
		$collector = QM_Collectors::get( 'php_errors' );
		if ( ! $collector ) {
			return new WP_Error( 'collector_not_found', 'PHP errors collector not found', array( 'status' => 404 ) );
		}
		
		$data = $collector->get_data();
		
		return rest_ensure_response( array(
			'success' => true,
			'data' => array(
				'total_errors' => count( $data->errors ?? array() ),
				'errors' => $data->errors ?? array(),
			),
		) );
	}
}
