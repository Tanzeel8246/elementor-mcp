<?php
/**
 * Main plugin orchestrator.
 *
 * Singleton that initializes all components, registers hooks for the
 * Abilities API and MCP Adapter, and coordinates the plugin lifecycle.
 *
 * @package MindCrafts_AI
 * @since   2.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Plugin orchestrator singleton.
 *
 * @since 2.0.0
 */
class MindCrafts_AI_Plugin {

	/**
	 * Singleton instance.
	 *
	 * @var self|null
	 */
	private static $instance = null;

	/**
	 * The data access layer.
	 *
	 * @var MindCrafts_AI_Data
	 */
	private $data;

	/**
	 * The element factory.
	 *
	 * @var MindCrafts_AI_Element_Factory
	 */
	private $factory;

	/**
	 * The schema generator.
	 *
	 * @var MindCrafts_AI_Schema_Generator
	 */
	private $schema_generator;

	/**
	 * The ability registrar.
	 *
	 * @var MindCrafts_AI_Ability_Registrar
	 */
	private $registrar;

	/**
	 * The admin settings page handler.
	 *
	 * @var MindCrafts_AI_Admin|null
	 */
	private $admin = null;

	/**
	 * Registered ability names (populated after registration).
	 *
	 * @var string[]
	 */
	private $ability_names = array();

	/**
	 * Gets the singleton instance.
	 *
	 * @since 2.0.0
	 *
	 * @return self
	 */
	public static function instance(): self {
		if ( null === self::$instance ) {
			self::$instance = new self();
			self::$instance->init();
		}

		return self::$instance;
	}

	/**
	 * Private constructor to enforce singleton.
	 *
	 * @since 2.0.0
	 */
	private function __construct() {}

	/**
	 * Initializes the plugin components and hooks.
	 *
	 * @since 2.0.0
	 */
	private function init(): void {
		// Instantiate core components.
		$this->data             = new MindCrafts_AI_Data();
		$this->factory          = new MindCrafts_AI_Element_Factory();
		$this->schema_generator = new MindCrafts_AI_Schema_Generator();
		$validator              = new MindCrafts_AI_Settings_Validator( $this->schema_generator );
		$this->registrar        = new MindCrafts_AI_Ability_Registrar( $this->data, $this->factory, $this->schema_generator, $validator );

		// Admin settings page.
		if ( is_admin() && class_exists( 'MindCrafts_AI_Admin' ) && ! did_action( 'mindcrafts_ai_admin_initialized' ) ) {
			$this->admin = new MindCrafts_AI_Admin();
			$this->admin->init();
			do_action( 'mindcrafts_ai_admin_initialized' );
		}

		// Register hooks.
		add_action( 'wp_abilities_api_categories_init', array( $this, 'register_category' ) );
		add_action( 'wp_abilities_api_init', array( $this, 'register_abilities' ) );

		// The Abilities API is lazy-loaded: wp_abilities_api_init fires on first
		// wp_get_ability() call. The default MCP server's tool registration triggers
		// this during mcp_adapter_init at priority 10. We hook at priority 20 so
		// the Abilities API is initialized and our abilities are registered by then.
		add_action( 'mcp_adapter_init', array( $this, 'register_mcp_server' ), 20 );

		// T3-5: Structured logging for all MCP server requests.
		add_filter( 'rest_post_dispatch', array( $this, 'log_mcp_api_call' ), 10, 3 );
	}

	/**
	 * Registers the ability category.
	 *
	 * Called during `wp_abilities_api_categories_init`.
	 *
	 * @since 2.0.0
	 */
	public function register_category(): void {
		wp_register_ability_category(
			'mindcrafts-ai',
			array(
				'label'       => __( 'MindCrafts AI', 'mindcrafts-ai' ),
				'description' => __( 'Tools for reading and manipulating Elementor page designs via MCP.', 'mindcrafts-ai' ),
			)
		);
	}

	/**
	 * Registers all abilities with the WordPress Abilities API.
	 *
	 * Called during `wp_abilities_api_init`.
	 *
	 * @since 2.0.0
	 */
	public function register_abilities(): void {
		try {
			$this->ability_names = $this->registrar->register_all();
		} catch ( \Throwable $e ) {
			add_action( 'admin_notices', function() use ( $e ) {
				echo '<div class="notice notice-error is-dismissible"><p><strong>MindCrafts AI Abilities Error:</strong> ' . esc_html( $e->getMessage() ) . ' <small>(' . esc_html( $e->getFile() ) . ':' . (int) $e->getLine() . ')</small></p></div>';
			} );
		}
	}

	/**
	 * Registers the MCP server with the MCP Adapter.
	 *
	 * Called during `mcp_adapter_init`.
	 *
	 * @since 2.0.0
	 *
	 * @param \WP\MCP\Core\McpAdapter $mcp_adapter The MCP adapter instance.
	 */
	public function register_mcp_server( $mcp_adapter ): void {
		try {
			if ( ! did_action( 'wp_abilities_api_init' ) ) {
				if ( ! did_action( 'wp_abilities_api_categories_init' ) ) {
					do_action( 'wp_abilities_api_categories_init' );
				}
				do_action( 'wp_abilities_api_init' );
			}

			if ( empty( $this->ability_names ) ) {
				$this->ability_names = $this->registrar->get_ability_names();
			}

			if ( empty( $this->ability_names ) ) {
				return;
			}

			$prompts_class = new MindCrafts_AI_Prompt_Abilities();
			$prompts       = $prompts_class->get_prompts();

			$mcp_adapter->create_server(
				'mindcrafts-ai-server',                                   // server_id
				'mcp',                                                    // route_namespace
				'mindcrafts-ai-server',                                   // route
				__( 'MindCrafts AI Server', 'mindcrafts-ai' ),            // server_name
				__( 'Exposes Elementor data and design tools as MCP tools for AI agents.', 'mindcrafts-ai' ), // description
				'v' . MINDCRAFTS_AI_VERSION,                              // version
				array( \WP\MCP\Transport\HttpTransport::class ),          // transports
				null,                                                     // error_handler (use default)
				null,                                                     // observability_handler
				$this->ability_names,                                     // tools
				array(),                                                  // resources
				$prompts,                                                 // prompts
				null                                                      // transport_permission_callback
			);
		} catch ( \Throwable $e ) {
			add_action( 'admin_notices', function() use ( $e ) {
				echo '<div class="notice notice-error is-dismissible"><p><strong>MindCrafts AI Server Error:</strong> ' . esc_html( $e->getMessage() ) . ' <small>(' . esc_html( $e->getFile() ) . ':' . (int) $e->getLine() . ')</small></p></div>';
			} );
		}
	}

	/**
	 * Gets the data access layer instance.
	 *
	 * @since 2.0.0
	 *
	 * @return MindCrafts_AI_Data
	 */
	public function get_data(): MindCrafts_AI_Data {
		return $this->data;
	}

	/**
	 * Gets the element factory instance.
	 *
	 * @since 2.0.0
	 *
	 * @return MindCrafts_AI_Element_Factory
	 */
	public function get_factory(): MindCrafts_AI_Element_Factory {
		return $this->factory;
	}

	/**
	 * Gets the schema generator instance.
	 *
	 * @since 2.0.0
	 *
	 * @return MindCrafts_AI_Schema_Generator
	 */
	public function get_schema_generator(): MindCrafts_AI_Schema_Generator {
		return $this->schema_generator;
	}

	/**
	 * T3-5: Logs all MCP API requests and responses to a structured log file.
	 *
	 * @since 2.1.0
	 *
	 * @param \WP_REST_Response $response The REST response.
	 * @param \WP_REST_Server   $server   The REST server instance.
	 * @param \WP_REST_Request  $request  The REST request instance.
	 * @return \WP_REST_Response The untouched REST response.
	 */
	public function log_mcp_api_call( $response, $server, $request ) {
		$route = $request->get_route();
		if ( false === strpos( $route, '/mcp/mindcrafts-ai-server' ) ) {
			return $response;
		}

		$params  = $request->get_json_params();
		$user_id = get_current_user_id();

		$log_entry = array(
			'timestamp' => current_time( 'mysql' ),
			'user_id'   => $user_id,
			'method'    => $params['method'] ?? 'unknown',
			'tool'      => $params['params']['name'] ?? '',
			'status'    => $response->get_status(),
		);

		$upload_dir = wp_upload_dir();
		$log_dir    = $upload_dir['basedir'] . '/mindcrafts-ai';
		if ( ! file_exists( $log_dir ) ) {
			wp_mkdir_p( $log_dir );
		}

		// Security: deny direct HTTP access.
		$htaccess = $log_dir . '/.htaccess';
		if ( ! file_exists( $htaccess ) ) {
			file_put_contents( $htaccess, 'deny from all' . PHP_EOL );
		}

		// Log Rotation: 5MB threshold.
		$log_file = $log_dir . '/mcp_calls.log';
		if ( file_exists( $log_file ) && filesize( $log_file ) > 5 * 1024 * 1024 ) {
			rename( $log_file, $log_dir . '/mcp_calls_' . gmdate( 'Y-m-d_H-i-s' ) . '.log' );

			// Keep only the 3 most recent rotated logs.
			$old_logs = glob( $log_dir . '/mcp_calls_*.log' );
			if ( is_array( $old_logs ) && count( $old_logs ) > 3 ) {
				sort( $old_logs );
				$to_delete = array_slice( $old_logs, 0, count( $old_logs ) - 3 );
				foreach ( $to_delete as $old ) {
					wp_delete_file( $old );
				}
			}
		}

		file_put_contents(
			$log_file,
			wp_json_encode( $log_entry ) . PHP_EOL,
			FILE_APPEND
		);

		return $response;
	}

	/**
	 * Prevents cloning.
	 *
	 * @since 2.0.0
	 */
	private function __clone() {}
}
