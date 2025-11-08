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
		
		// Inspect endpoint - Complete post/page analysis
		register_rest_route( self::NAMESPACE, '/inspect', array(
			'methods' => 'GET',
			'callback' => array( __CLASS__, 'get_inspect' ),
			'permission_callback' => array( __CLASS__, 'check_permission' ),
			'args' => array(
				'post_id' => array(
					'required' => false,
					'type' => 'integer',
					'description' => 'Post ID to inspect',
				),
				'slug' => array(
					'required' => false,
					'type' => 'string',
					'description' => 'Post slug to inspect',
				),
				'url' => array(
					'required' => false,
					'type' => 'string',
					'description' => 'URL path to inspect',
				),
				'collectors' => array(
					'required' => false,
					'type' => 'string',
					'description' => 'Comma-separated list of collectors to include',
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
	 * Get complete inspection data for a post/page/URL
	 */
	public static function get_inspect( $request ) {
		self::init_qm();
		
		// Get parameters
		$post_id = $request->get_param( 'post_id' );
		$slug = $request->get_param( 'slug' );
		$url = $request->get_param( 'url' );
		$collectors_filter = $request->get_param( 'collectors' );
		
		// Validate that at least one identifier is provided
		if ( ! $post_id && ! $slug && ! $url ) {
			return new WP_Error(
				'missing_parameter',
				'Please specify post_id, slug, or url parameter',
				array( 'status' => 400 )
			);
		}
		
		// Get post ID from slug if provided
		if ( $slug && ! $post_id ) {
			$post = get_page_by_path( $slug, OBJECT, array( 'post', 'page' ) );
			if ( ! $post ) {
				return new WP_Error(
					'post_not_found',
					sprintf( 'Post not found with slug: %s', $slug ),
					array( 'status' => 404 )
				);
			}
			$post_id = $post->ID;
			$url = get_permalink( $post_id );
		}
		
		// Get URL from post ID if provided
		if ( $post_id && ! $url ) {
			$url = get_permalink( $post_id );
			if ( ! $url ) {
				return new WP_Error(
					'invalid_post',
					sprintf( 'Could not get URL for post ID: %s', $post_id ),
					array( 'status' => 404 )
				);
			}
		}
		
		// Parse collectors filter
		$collectors_array = $collectors_filter ? explode( ',', $collectors_filter ) : null;
		
		// Simulate loading the post/page
		if ( $post_id ) {
			global $post, $wp_query;
			$post = get_post( $post_id );
			if ( $post ) {
				setup_postdata( $post );
				$wp_query->is_single = true;
				$wp_query->is_singular = true;
			}
		}
		
		// Process all collectors
		QM_Collectors::init()->process();
		
		// Get all available collectors
		$all_collectors = QM_Collectors::init();
		
		$report = array(
			'url' => $url,
			'post_id' => $post_id,
			'collectors' => array(),
		);
		
		// Add post information if available
		if ( $post_id ) {
			$post_obj = get_post( $post_id );
			if ( $post_obj ) {
				$report['post'] = array(
					'ID' => $post_obj->ID,
					'title' => $post_obj->post_title,
					'type' => $post_obj->post_type,
					'status' => $post_obj->post_status,
					'slug' => $post_obj->post_name,
				);
			}
		}
		
		// Collect data from all collectors
		foreach ( $all_collectors as $collector ) {
			$collector_id = $collector->id;
			
			// Skip if filtering and this collector is not in the list
			if ( $collectors_array && ! in_array( $collector_id, $collectors_array ) ) {
				continue;
			}
			
			$data = $collector->get_data();
			
			if ( ! $data ) {
				continue;
			}
			
			$report['collectors'][ $collector_id ] = self::format_collector_data( $collector_id, $data );
		}
		
		return rest_ensure_response( array(
			'success' => true,
			'data' => $report,
		) );
	}
	
	/**
	 * Format collector data for API response
	 */
	private static function format_collector_data( $collector_id, $data ) {
		$formatted = array(
			'collector' => $collector_id,
			'data' => array(),
		);
		
		// Extract relevant data based on collector type
		switch ( $collector_id ) {
			case 'db_queries':
				if ( isset( $data->queries ) ) {
					$formatted['data'] = array(
						'total_queries' => count( $data->queries ),
						'total_time' => $data->total_time ?? 0,
						'queries' => array_map( function( $query ) {
							return array(
								'sql' => $query['sql'] ?? '',
								'time' => $query['ltime'] ?? 0,
								'caller' => $query['caller'] ?? '',
								'component' => $query['component'] ?? '',
								'type' => $query['type'] ?? '',
							);
						}, $data->queries ),
					);
				}
				break;
				
			case 'http':
				if ( isset( $data->http ) ) {
					$formatted['data'] = array(
						'total_requests' => count( $data->http ),
						'requests' => array_map( function( $request ) {
							return array(
								'url' => $request['url'] ?? '',
								'method' => $request['args']['method'] ?? 'GET',
								'response_code' => $request['response']['code'] ?? 0,
								'time' => ( $request['end'] ?? 0 ) - ( $request['start'] ?? 0 ),
							);
						}, $data->http ),
					);
				}
				break;
				
			case 'hooks':
				if ( isset( $data->hooks ) ) {
					$formatted['data'] = array(
						'total_hooks' => count( $data->hooks ),
						'hooks' => array_keys( $data->hooks ),
					);
				}
				break;
				
			case 'conditionals':
				$formatted['data'] = get_object_vars( $data );
				break;
				
			case 'request':
				$formatted['data'] = array(
					'matched_query' => $data->request['matched_query'] ?? '',
					'matched_rule' => $data->request['matched_rule'] ?? '',
					'query_vars' => $data->request['query_vars'] ?? array(),
				);
				break;
				
			case 'theme':
				if ( isset( $data->stylesheet ) ) {
					$formatted['data'] = array(
						'theme' => $data->stylesheet,
						'template' => $data->template,
						'template_file' => $data->template_file ?? '',
						'template_hierarchy' => $data->template_hierarchy ?? array(),
					);
				}
				break;
				
			case 'php_errors':
				if ( isset( $data->errors ) ) {
					$formatted['data'] = array(
						'total_errors' => count( $data->errors ),
						'errors' => $data->errors,
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
				
			default:
				// Generic data extraction
				$formatted['data'] = get_object_vars( $data );
				break;
		}
		
		return $formatted;
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
