<?php
/**
 * Admin settings page for MindCrafts AI.
 *
 * Provides a UI to toggle individual MCP tools on/off, manage licenses,
 * view API audit logs, inspect system diagnostics, and view connection guides.
 *
 * @package MindCrafts_AI
 * @since   1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Admin page orchestrator.
 *
 * @since 1.0.0
 */
class MindCrafts_AI_Admin {

	/**
	 * The page hook suffix returned by add_options_page().
	 *
	 * @var string
	 */
	private $hook_suffix = '';

	/**
	 * Option name for storing disabled tools.
	 *
	 * @var string
	 */
	const OPTION_DISABLED_TOOLS = 'mindcrafts_ai_disabled_tools';

	/**
	 * Settings group name.
	 *
	 * @var string
	 */
	const SETTINGS_GROUP = 'mindcrafts_ai_settings';

	/**
	 * Page slug.
	 *
	 * @var string
	 */
	const PAGE_SLUG = 'mindcrafts-ai-dashboard';

	/**
	 * Initialize hooks.
	 *
	 * @since 1.0.0
	 */
	public function init(): void {
		add_action( 'admin_menu', array( $this, 'add_settings_page' ) );
		add_action( 'admin_init', array( $this, 'register_settings' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
		add_action( 'admin_post_mindcrafts_ai_save_tools', array( $this, 'handle_tools_save' ) );
		add_action( 'admin_post_mindcrafts_ai_install_dependency', array( $this, 'handle_dependency_install' ) );
		add_action( 'admin_post_mindcrafts_ai_activate_dependency', array( $this, 'handle_dependency_activate' ) );
		add_action( 'admin_post_mindcrafts_ai_generate_app_password', array( $this, 'handle_generate_app_password' ) );

		// AJAX actions for License & Logs.
		add_action( 'wp_ajax_mindcrafts_ai_activate_license', array( $this, 'handle_ajax_activate_license' ) );
		add_action( 'wp_ajax_mindcrafts_ai_deactivate_license', array( $this, 'handle_ajax_deactivate_license' ) );
		add_action( 'wp_ajax_mindcrafts_ai_clear_logs', array( $this, 'handle_ajax_clear_logs' ) );
		add_action( 'wp_ajax_mindcrafts_ai_migrate_pages', array( $this, 'handle_ajax_migrate_pages' ) );
		add_action( 'wp_ajax_mindcrafts_ai_list_snapshots', array( $this, 'handle_ajax_list_snapshots' ) );
		add_action( 'wp_ajax_mindcrafts_ai_restore_snapshot', array( $this, 'handle_ajax_restore_snapshot' ) );

		add_filter( 'mindcrafts_ai_ability_names', array( $this, 'filter_ability_names' ) );
		add_filter( 'plugin_action_links_' . MINDCRAFTS_AI_BASENAME, array( $this, 'add_plugin_action_links' ) );
	}

	/**
	 * Adds quick links to the Plugins screen.
	 *
	 * @since 2.0.0
	 *
	 * @param string[] $links Existing action links.
	 * @return string[] Action links with Settings first.
	 */
	public function add_plugin_action_links( array $links ): array {
		$settings_link = sprintf(
			'<a href="%1$s">%2$s</a>',
			esc_url( admin_url( 'admin.php?page=' . self::PAGE_SLUG . '&tab=setup' ) ),
			esc_html__( 'Setup', 'mindcrafts-ai' )
		);

		array_unshift( $links, $settings_link );

		return $links;
	}

	/**
	 * Add the settings page under the WordPress admin menu.
	 *
	 * @since 1.0.0
	 */
	public function add_settings_page(): void {
		$this->hook_suffix = add_menu_page(
			__( 'MindCrafts AI', 'mindcrafts-ai' ),
			__( 'MindCrafts AI', 'mindcrafts-ai' ),
			'manage_options',
			self::PAGE_SLUG,
			array( $this, 'render_page' ),
			'dashicons-admin-generic',
			58
		);
	}

	/**
	 * Register the settings with the WordPress Settings API.
	 *
	 * @since 1.0.0
	 */
	public function register_settings(): void {
		register_setting(
			self::SETTINGS_GROUP,
			self::OPTION_DISABLED_TOOLS,
			array(
				'type'              => 'array',
				'default'           => array(),
				'sanitize_callback' => array( $this, 'sanitize_disabled_tools' ),
			)
		);
	}

	/**
	 * Handles the tools form save without sending ability slugs in the request.
	 *
	 * @since 2.0.0
	 */
	public function handle_tools_save(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to manage MindCrafts AI settings.', 'mindcrafts-ai' ) );
		}

		check_admin_referer( 'mindcrafts_ai_save_tools', 'mindcrafts_ai_nonce' );

		$all_tools   = $this->get_all_tool_slugs();
		$enabled_ids = array();

		if ( isset( $_POST['mindcrafts_ai_enabled_ids'] ) ) {
			$raw_enabled_ids = sanitize_text_field( wp_unslash( $_POST['mindcrafts_ai_enabled_ids'] ) );
			$enabled_ids     = '' === $raw_enabled_ids ? array() : array_map( 'absint', explode( ',', $raw_enabled_ids ) );
		} elseif ( isset( $_POST['mindcrafts_ai_enabled_tools'] ) && is_array( $_POST['mindcrafts_ai_enabled_tools'] ) ) {
			$raw_enabled_ids = wp_unslash( $_POST['mindcrafts_ai_enabled_tools'] );
			$enabled_ids     = array_map( 'absint', $raw_enabled_ids );
		}

		$enabled_tools = array();
		foreach ( array_unique( $enabled_ids ) as $tool_id ) {
			if ( isset( $all_tools[ $tool_id ] ) ) {
				$enabled_tools[] = $all_tools[ $tool_id ];
			}
		}

		$disabled_tools = array_values( array_diff( $all_tools, $enabled_tools ) );

		update_option( self::OPTION_DISABLED_TOOLS, $disabled_tools );

		wp_safe_redirect(
			add_query_arg(
				array(
					'page'             => self::PAGE_SLUG,
					'tab'              => 'tools',
					'settings-updated' => 'true',
				),
				admin_url( 'admin.php' )
			)
		);
		exit;
	}

	/**
	 * Sanitize the disabled tools option value.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed $input The raw form input.
	 * @return string[] Sanitized disabled tool slugs.
	 */
	public function sanitize_disabled_tools( $input ): array {
		if ( ! is_array( $input ) ) {
			return array();
		}

		return array_map( 'sanitize_text_field', $input );
	}

	/**
	 * Enqueue admin CSS and JS on our settings page only.
	 *
	 * @since 1.0.0
	 *
	 * @param string $hook The current admin page hook.
	 */
	public function enqueue_assets( string $hook ): void {
		if ( $hook !== $this->hook_suffix ) {
			return;
		}

		wp_enqueue_style(
			'mindcrafts-ai-admin',
			MINDCRAFTS_AI_URL . 'assets/css/admin.css',
			array( 'dashicons' ),
			MINDCRAFTS_AI_VERSION
		);

		wp_enqueue_script(
			'mindcrafts-ai-admin',
			MINDCRAFTS_AI_URL . 'assets/js/admin.js',
			array( 'jquery' ),
			MINDCRAFTS_AI_VERSION,
			true
		);

		wp_localize_script(
			'mindcrafts-ai-admin',
			'mindcraftsAiAdmin',
			array(
				'ajaxUrl'      => admin_url( 'admin-ajax.php' ),
				'nonce'        => wp_create_nonce( 'mindcrafts_ai_admin_nonce' ),
				'copied'       => __( 'Copied to clipboard!', 'mindcrafts-ai' ),
				'cleared'      => __( 'Logs cleared successfully.', 'mindcrafts-ai' ),
				'clearing'     => __( 'Clearing logs...', 'mindcrafts-ai' ),
				'error'        => __( 'An error occurred. Please try again.', 'mindcrafts-ai' ),
				'confirmClear' => __( 'Are you sure you want to clear all MCP audit logs?', 'mindcrafts-ai' ),
			)
		);
	}

	/**
	 * Filter ability names to remove disabled tools.
	 *
	 * @since 1.0.0
	 *
	 * @param string[] $names The registered ability names.
	 * @return string[] Filtered ability names.
	 */
	public function filter_ability_names( array $names ): array {
		$disabled = get_option( self::OPTION_DISABLED_TOOLS, array() );

		if ( empty( $disabled ) ) {
			return $names;
		}

		return array_values( array_diff( $names, $disabled ) );
	}

	/**
	 * Render the settings page.
	 *
	 * @since 1.0.0
	 */
	public function render_page(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$active_tab      = isset( $_GET['tab'] ) ? sanitize_key( $_GET['tab'] ) : 'setup'; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$license_manager = MindCrafts_AI_License_Manager::instance();
		$is_premium      = $license_manager->is_premium_active();
		$enabled_tools   = $this->get_enabled_tool_count();
		$total_tools     = $this->get_total_tool_count();

		$tabs = array(
			'setup'       => array(
				'label' => __( 'Setup', 'mindcrafts-ai' ),
				'icon'  => 'dashicons-admin-settings',
			),
			'tools'       => array(
				'label' => __( 'Tools', 'mindcrafts-ai' ),
				'icon'  => 'dashicons-hammer',
			),
			'connection'  => array(
				'label' => __( 'Connection', 'mindcrafts-ai' ),
				'icon'  => 'dashicons-networking',
			),
			'license'     => array(
				'label' => __( 'License', 'mindcrafts-ai' ),
				'icon'  => 'dashicons-awards',
			),
			'logs'        => array(
				'label' => __( 'Logs', 'mindcrafts-ai' ),
				'icon'  => 'dashicons-list-view',
			),
			'diagnostics' => array(
				'label' => __( 'Diagnostics', 'mindcrafts-ai' ),
				'icon'  => 'dashicons-heart',
			),
		);

		if ( ! isset( $tabs[ $active_tab ] ) ) {
			$active_tab = 'setup';
		}
		?>
		<div class="wrap mindcrafts-ai-admin">
			<!-- Branded Hero Header -->
			<div class="mindcrafts-ai-hero">
				<div class="mindcrafts-ai-hero-content">
					<div class="mindcrafts-ai-brand-badge">
						<span class="mindcrafts-ai-pulse-dot"></span>
						<span><?php esc_html_e( 'MCP Server Ready', 'mindcrafts-ai' ); ?></span>
					</div>
					<h1 class="mindcrafts-ai-hero-title">
						<span>🧠 <?php esc_html_e( 'MindCrafts AI for Elementor', 'mindcrafts-ai' ); ?></span>
						<span class="mindcrafts-ai-version-pill">v<?php echo esc_html( MINDCRAFTS_AI_VERSION ); ?></span>
					</h1>
					<p class="mindcrafts-ai-hero-desc">
						<?php esc_html_e( 'Enterprise Model Context Protocol bridge connecting Claude, Cursor, and AI agents directly to your Elementor builder engine.', 'mindcrafts-ai' ); ?>
					</p>
				</div>
				<div class="mindcrafts-ai-hero-actions">
					<span class="mindcrafts-ai-badge <?php echo esc_attr( $is_premium ? 'mindcrafts-ai-badge--pro' : 'mindcrafts-ai-badge--free' ); ?>" style="padding: 6px 14px; font-size: 11px;">
						<?php echo $is_premium ? esc_html__( '★ Pro Edition Active', 'mindcrafts-ai' ) : esc_html__( 'Community Edition', 'mindcrafts-ai' ); ?>
					</span>
					<a href="<?php echo esc_url( admin_url( 'admin.php?page=' . self::PAGE_SLUG . '&tab=tools' ) ); ?>" class="mindcrafts-ai-hero-btn mindcrafts-ai-hero-btn--glass">
						<span class="dashicons dashicons-admin-tools" style="font-size: 16px; width: 16px; height: 16px;"></span>
						<span><?php printf( esc_html__( '%1$d/%2$d Tools Online', 'mindcrafts-ai' ), absint( $enabled_tools ), absint( $total_tools ) ); ?></span>
					</a>
				</div>
			</div>

			<!-- Modern Tab Navigation -->
			<nav class="nav-tab-wrapper">
				<?php foreach ( $tabs as $tab_key => $tab_data ) : ?>
					<a href="<?php echo esc_url( admin_url( 'admin.php?page=' . self::PAGE_SLUG . '&tab=' . $tab_key ) ); ?>"
					   class="nav-tab <?php echo esc_attr( $tab_key === $active_tab ? 'nav-tab-active' : '' ); ?>">
						<span class="dashicons <?php echo esc_attr( $tab_data['icon'] ); ?>"></span>
						<span><?php echo esc_html( $tab_data['label'] ); ?></span>
					</a>
				<?php endforeach; ?>
			</nav>

			<div class="tab-content">
				<?php
				if ( 'connection' === $active_tab ) {
					include MINDCRAFTS_AI_DIR . 'includes/admin/views/page-connection.php';
				} elseif ( 'tools' === $active_tab ) {
					include MINDCRAFTS_AI_DIR . 'includes/admin/views/page-tools.php';
				} elseif ( 'license' === $active_tab ) {
					include MINDCRAFTS_AI_DIR . 'includes/admin/views/page-license.php';
				} elseif ( 'logs' === $active_tab ) {
					include MINDCRAFTS_AI_DIR . 'includes/admin/views/page-logs.php';
				} elseif ( 'diagnostics' === $active_tab ) {
					include MINDCRAFTS_AI_DIR . 'includes/admin/views/page-diagnostics.php';
				} else {
					include MINDCRAFTS_AI_DIR . 'includes/admin/views/page-setup.php';
				}
				?>
			</div>
		</div>
		<?php
	}

	/**
	 * AJAX handler to activate license.
	 *
	 * @since 2.0.0
	 */
	public function handle_ajax_activate_license(): void {
		check_ajax_referer( 'mindcrafts_ai_admin_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'mindcrafts-ai' ) ) );
		}

		$key = isset( $_POST['license_key'] ) ? sanitize_text_field( wp_unslash( $_POST['license_key'] ) ) : '';
		if ( empty( $key ) ) {
			wp_send_json_error( array( 'message' => __( 'License key cannot be empty.', 'mindcrafts-ai' ) ) );
		}

		$manager = MindCrafts_AI_License_Manager::instance();
		$result  = $manager->activate_license( $key );

		if ( $result['success'] ) {
			wp_send_json_success( $result );
		} else {
			wp_send_json_error( $result );
		}
	}

	/**
	 * AJAX handler to deactivate license.
	 *
	 * @since 2.0.0
	 */
	public function handle_ajax_deactivate_license(): void {
		check_ajax_referer( 'mindcrafts_ai_admin_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'mindcrafts-ai' ) ) );
		}

		$manager = MindCrafts_AI_License_Manager::instance();
		$result  = $manager->deactivate_license();

		wp_send_json_success( $result );
	}

	/**
	 * AJAX handler to clear MCP audit logs.
	 *
	 * @since 2.0.0
	 */
	public function handle_ajax_clear_logs(): void {
		check_ajax_referer( 'mindcrafts_ai_admin_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'mindcrafts-ai' ) ) );
		}

		$upload_dir = wp_upload_dir();
		$log_file   = $upload_dir['basedir'] . '/mindcrafts-ai/mcp_calls.log';

		if ( file_exists( $log_file ) ) {
			file_put_contents( $log_file, '' );
		}

		wp_send_json_success( array( 'message' => __( 'Logs cleared successfully.', 'mindcrafts-ai' ) ) );
	}

	/**
	 * AJAX handler to migrate all raw HTML pages into native Elementor widgets.
	 *
	 * @since 3.1.5
	 */
	public function handle_ajax_migrate_pages(): void {
		check_ajax_referer( 'mindcrafts_ai_admin_nonce', 'nonce' );

		if ( ! current_user_can( 'edit_pages' ) ) {
			wp_send_json_error( array( 'message' => __( 'Insufficient permissions to edit pages.', 'mindcrafts-ai' ) ) );
		}

		$composite = new MindCrafts_AI_Composite_Abilities(
			new MindCrafts_AI_Data(),
			new MindCrafts_AI_Element_Factory()
		);

		$result = $composite->execute_migrate_all_pages_to_native();

		if ( is_wp_error( $result ) ) {
			wp_send_json_error( array( 'message' => $result->get_error_message() ) );
		}

		wp_send_json_success( $result );
	}

	/**
	 * AJAX handler to list snapshots for a given page.
	 *
	 * @since 3.1.7
	 */
	public function handle_ajax_list_snapshots(): void {
		check_ajax_referer( 'mindcrafts_ai_admin_nonce', 'nonce' );

		if ( ! current_user_can( 'edit_pages' ) ) {
			wp_send_json_error( array( 'message' => __( 'Insufficient permissions to view page history.', 'mindcrafts-ai' ) ) );
		}

		$post_id = absint( $_POST['post_id'] ?? 0 );
		if ( ! $post_id ) {
			wp_send_json_error( array( 'message' => __( 'Invalid post ID.', 'mindcrafts-ai' ) ) );
		}

		$data      = new MindCrafts_AI_Data();
		$snapshots = $data->list_snapshots( $post_id );

		wp_send_json_success( array( 'snapshots' => $snapshots ) );
	}

	/**
	 * AJAX handler to restore a page to a snapshot.
	 *
	 * @since 3.1.7
	 */
	public function handle_ajax_restore_snapshot(): void {
		check_ajax_referer( 'mindcrafts_ai_admin_nonce', 'nonce' );

		if ( ! current_user_can( 'edit_pages' ) ) {
			wp_send_json_error( array( 'message' => __( 'Insufficient permissions to restore pages.', 'mindcrafts-ai' ) ) );
		}

		$post_id = absint( $_POST['post_id'] ?? 0 );
		$index   = intval( $_POST['index'] ?? -1 );

		if ( ! $post_id || $index < 0 ) {
			wp_send_json_error( array( 'message' => __( 'Invalid post ID or snapshot index.', 'mindcrafts-ai' ) ) );
		}

		$data   = new MindCrafts_AI_Data();
		$result = $data->restore_snapshot( $post_id, $index );

		if ( is_wp_error( $result ) ) {
			wp_send_json_error( array( 'message' => $result->get_error_message() ) );
		}

		wp_send_json_success(
			array(
				'message'     => __( 'Snapshot successfully restored! The page content has been rolled back.', 'mindcrafts-ai' ),
				'edit_url'    => admin_url( 'post.php?post=' . $post_id . '&action=elementor' ),
				'preview_url' => get_permalink( $post_id ),
			)
		);
	}

	/**
	 * Reads recent MCP audit log entries.
	 *
	 * @since 2.0.0
	 *
	 * @param int $limit Maximum number of entries to return.
	 * @return array<int, array<string, mixed>>
	 */
	public function get_log_entries( int $limit = 50 ): array {
		$upload_dir = wp_upload_dir();
		$log_file   = $upload_dir['basedir'] . '/mindcrafts-ai/mcp_calls.log';

		if ( ! file_exists( $log_file ) || ! is_readable( $log_file ) ) {
			return array();
		}

		$lines   = file( $log_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES );
		if ( empty( $lines ) ) {
			return array();
		}

		$entries = array();
		$sliced  = array_slice( $lines, -$limit );

		foreach ( array_reverse( $sliced ) as $line ) {
			$decoded = json_decode( $line, true );
			if ( is_array( $decoded ) ) {
				$entries[] = $decoded;
			}
		}

		return $entries;
	}

	/**
	 * Returns human-readable size of the MCP log file.
	 *
	 * @since 2.0.0
	 *
	 * @return string
	 */
	public function get_log_file_size(): string {
		$upload_dir = wp_upload_dir();
		$log_file   = $upload_dir['basedir'] . '/mindcrafts-ai/mcp_calls.log';

		if ( ! file_exists( $log_file ) ) {
			return '0 KB';
		}

		return size_format( filesize( $log_file ) );
	}

	/**
	 * Get required dependency definitions.
	 *
	 * @since 2.0.0
	 *
	 * @return array<string, array<string, mixed>>
	 */
	public function get_dependency_definitions(): array {
		return array(
			'elementor'     => array(
				'label'          => __( 'Elementor', 'mindcrafts-ai' ),
				'description'    => __( 'Required page builder. MindCrafts AI reads and writes Elementor documents, widgets, containers, and templates.', 'mindcrafts-ai' ),
				'is_active'      => did_action( 'elementor/loaded' ) || class_exists( '\Elementor\Plugin' ),
				'plugin_files'   => array( 'elementor/elementor.php' ),
				'name_matches'   => array( 'Elementor' ),
				'download_url'   => 'https://downloads.wordpress.org/plugin/elementor.latest-stable.zip',
				'installable'    => true,
				'source_label'   => __( 'WordPress.org', 'mindcrafts-ai' ),
				'manual_message' => '',
			),
			'mcp_adapter'   => array(
				'label'          => __( 'WordPress MCP Adapter', 'mindcrafts-ai' ),
				'description'    => __( 'Bridge that exposes WordPress Abilities as MCP tools for AI clients. Bundled directly into MindCrafts AI.', 'mindcrafts-ai' ),
				'is_active'      => class_exists( '\WP\MCP\Core\McpAdapter' ),
				'plugin_files'   => array(
					'mcp-adapter/mcp-adapter.php',
					'wordpress-mcp-adapter/mcp-adapter.php',
				),
				'name_matches'   => array( 'MCP Adapter', 'WordPress MCP Adapter' ),
				'download_url'   => '',
				'installable'    => false,
				'source_label'   => __( 'Bundled (MindCrafts AI)', 'mindcrafts-ai' ),
				'manual_message' => __( 'Bundled inside MindCrafts AI. No separate installation required.', 'mindcrafts-ai' ),
			),
			'abilities_api' => array(
				'label'          => __( 'WordPress Abilities API', 'mindcrafts-ai' ),
				'description'    => __( 'Core API used to register MindCrafts AI abilities. Bundled directly into MindCrafts AI.', 'mindcrafts-ai' ),
				'is_active'      => function_exists( 'wp_register_ability' ),
				'plugin_files'   => array(
					'abilities-api/abilities-api.php',
					'wordpress-abilities-api/abilities-api.php',
				),
				'name_matches'   => array( 'Abilities API', 'WordPress Abilities API' ),
				'download_url'   => '',
				'installable'    => false,
				'source_label'   => __( 'Bundled (MindCrafts AI)', 'mindcrafts-ai' ),
				'manual_message' => __( 'Bundled inside MindCrafts AI. No separate installation required.', 'mindcrafts-ai' ),
			),
		);
	}

	/**
	 * Get current status for one dependency.
	 *
	 * @since 2.0.0
	 *
	 * @param string $dependency_key Dependency key.
	 * @return array{definition: array<string, mixed>, plugin_file: string, is_installed: bool, is_active: bool}
	 */
	public function get_dependency_status( string $dependency_key ): array {
		$definitions = $this->get_dependency_definitions();
		$definition  = isset( $definitions[ $dependency_key ] ) ? $definitions[ $dependency_key ] : array();
		$plugin_file = $definition ? $this->get_plugin_file_for_dependency( $definition ) : '';
		$is_active   = ! empty( $definition['is_active'] );

		if ( ! $is_active && $plugin_file ) {
			$this->load_plugin_admin_functions();
			$is_active = is_plugin_active( $plugin_file );
		}

		return array(
			'definition'   => $definition,
			'plugin_file'  => $plugin_file,
			'is_installed' => '' !== $plugin_file,
			'is_active'    => $is_active,
		);
	}

	/**
	 * Install a required dependency.
	 *
	 * @since 2.0.0
	 */
	public function handle_dependency_install(): void {
		if ( ! current_user_can( 'install_plugins' ) ) {
			wp_die( esc_html__( 'You do not have permission to install plugins.', 'mindcrafts-ai' ) );
		}

		$dependency_key = isset( $_POST['dependency'] ) ? sanitize_key( wp_unslash( $_POST['dependency'] ) ) : '';
		check_admin_referer( 'mindcrafts_ai_dependency_' . $dependency_key, 'mindcrafts_ai_nonce' );

		$definitions = $this->get_dependency_definitions();
		if ( ! isset( $definitions[ $dependency_key ] ) || empty( $definitions[ $dependency_key ]['installable'] ) || empty( $definitions[ $dependency_key ]['download_url'] ) ) {
			$this->redirect_to_setup(
				array(
					'dependency'        => $dependency_key,
					'dependency_result' => 'install_not_supported',
				)
			);
		}

		$status = $this->get_dependency_status( $dependency_key );
		if ( $status['is_active'] ) {
			$this->redirect_to_setup(
				array(
					'dependency'        => $dependency_key,
					'dependency_result' => 'already_active',
				)
			);
		}

		$this->load_plugin_admin_functions();
		require_once ABSPATH . 'wp-admin/includes/file.php';
		require_once ABSPATH . 'wp-admin/includes/plugin-install.php';
		require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
		require_once ABSPATH . 'wp-admin/includes/class-automatic-upgrader-skin.php';

		$skin     = new Automatic_Upgrader_Skin();
		$upgrader = new Plugin_Upgrader( $skin );
		$result   = $upgrader->install( esc_url_raw( $definitions[ $dependency_key ]['download_url'] ) );

		if ( is_wp_error( $result ) || ! $result ) {
			$this->redirect_to_setup(
				array(
					'dependency'        => $dependency_key,
					'dependency_result' => 'install_failed',
				)
			);
		}

		$plugin_file = $this->get_plugin_file_for_dependency( $definitions[ $dependency_key ] );

		if ( $plugin_file && current_user_can( 'activate_plugins' ) ) {
			$activation_result = activate_plugin( $plugin_file );
			if ( is_wp_error( $activation_result ) ) {
				$this->redirect_to_setup(
					array(
						'dependency'        => $dependency_key,
						'dependency_result' => 'activate_failed',
					)
				);
			}
		}

		$this->redirect_to_setup(
			array(
				'dependency'        => $dependency_key,
				'dependency_result' => 'installed',
			)
		);
	}

	/**
	 * Activate an already installed dependency.
	 *
	 * @since 2.0.0
	 */
	public function handle_dependency_activate(): void {
		if ( ! current_user_can( 'activate_plugins' ) ) {
			wp_die( esc_html__( 'You do not have permission to activate plugins.', 'mindcrafts-ai' ) );
		}

		$dependency_key = isset( $_POST['dependency'] ) ? sanitize_key( wp_unslash( $_POST['dependency'] ) ) : '';
		check_admin_referer( 'mindcrafts_ai_dependency_' . $dependency_key, 'mindcrafts_ai_nonce' );

		$definitions = $this->get_dependency_definitions();
		if ( ! isset( $definitions[ $dependency_key ] ) ) {
			$this->redirect_to_setup(
				array(
					'dependency'        => $dependency_key,
					'dependency_result' => 'unknown_dependency',
				)
			);
		}

		$plugin_file = $this->get_plugin_file_for_dependency( $definitions[ $dependency_key ] );
		if ( ! $plugin_file ) {
			$this->redirect_to_setup(
				array(
					'dependency'        => $dependency_key,
					'dependency_result' => 'not_installed',
				)
			);
		}

		$result = activate_plugin( $plugin_file );
		if ( is_wp_error( $result ) ) {
			$this->redirect_to_setup(
				array(
					'dependency'        => $dependency_key,
					'dependency_result' => 'activate_failed',
				)
			);
		}

		$this->redirect_to_setup(
			array(
				'dependency'        => $dependency_key,
				'dependency_result' => 'activated',
			)
		);
	}

	/**
	 * Handles generating a new Application Password for Antigravity directly from the Connection page.
	 *
	 * @since 2.1.0
	 */
	public function handle_generate_app_password(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to perform this action.', 'mindcrafts-ai' ) );
		}

		check_admin_referer( 'mindcrafts_ai_generate_app_password' );

		require_once ABSPATH . 'wp-includes/class-wp-application-passwords.php';

		add_filter( 'wp_is_application_passwords_available', '__return_true', 999 );
		add_filter( 'wp_is_application_passwords_available_for_user', '__return_true', 999 );
		add_filter( 'wp_is_application_passwords_supported_per_user', '__return_true', 999 );

		$user_id  = get_current_user_id();
		$app_name = 'Antigravity IDE (' . gmdate( 'M j, Y' ) . ')';
		$result   = \WP_Application_Passwords::create_new_application_password(
			$user_id,
			array( 'name' => $app_name )
		);

		if ( is_wp_error( $result ) ) {
			wp_safe_redirect(
				add_query_arg(
					array(
						'page'      => self::PAGE_SLUG,
						'tab'       => 'connection',
						'app_error' => urlencode( $result->get_error_message() ),
					),
					admin_url( 'admin.php' )
				)
			);
			exit;
		}

		$raw_password = $result[0];
		set_transient( 'mindcrafts_ai_new_app_pw_' . $user_id, $raw_password, 600 );

		wp_safe_redirect(
			add_query_arg(
				array(
					'page'        => self::PAGE_SLUG,
					'tab'         => 'connection',
					'app_success' => '1',
				),
				admin_url( 'admin.php' )
			)
		);
		exit;
	}

	/**
	 * Find the installed plugin file for a dependency.
	 *
	 * @since 2.0.0
	 */
	private function get_plugin_file_for_dependency( array $definition ): string {
		$this->load_plugin_admin_functions();

		$plugins = get_plugins();

		foreach ( (array) $definition['plugin_files'] as $plugin_file ) {
			if ( isset( $plugins[ $plugin_file ] ) ) {
				return $plugin_file;
			}
		}

		foreach ( $plugins as $plugin_file => $plugin_data ) {
			foreach ( (array) $definition['name_matches'] as $name_match ) {
				if ( ! empty( $plugin_data['Name'] ) && false !== stripos( $plugin_data['Name'], $name_match ) ) {
					return $plugin_file;
				}
			}
		}

		return '';
	}

	/**
	 * Load admin plugin helpers.
	 *
	 * @since 2.0.0
	 */
	private function load_plugin_admin_functions(): void {
		if ( ! function_exists( 'get_plugins' ) || ! function_exists( 'is_plugin_active' ) || ! function_exists( 'activate_plugin' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}
	}

	/**
	 * Redirect back to the Setup tab.
	 *
	 * @since 2.0.0
	 */
	private function redirect_to_setup( array $args = array() ): void {
		wp_safe_redirect(
			add_query_arg(
				array_merge(
					array(
						'page' => self::PAGE_SLUG,
						'tab'  => 'setup',
					),
					$args
				),
				admin_url( 'admin.php' )
			)
		);
		exit;
	}

	/**
	 * Get all tools grouped by category for the UI and management.
	 *
	 * @since 1.0.0
	 *
	 * @return array<string, array{label: string, tools: array<string, array{label: string, description: string, badges: string[]}>}> Grouped tools.
	 */
	public function get_all_tools(): array {
		return array(
			'query'            => array(
				'label' => __( 'Query & Discovery', 'mindcrafts-ai' ),
				'tools' => array(
					'mindcrafts-ai/list-widgets'         => array(
						'label'       => __( 'List Widgets', 'mindcrafts-ai' ),
						'description' => __( 'Lists all available Elementor widget types and their names.', 'mindcrafts-ai' ),
						'badges'      => array( 'read-only' ),
					),
					'mindcrafts-ai/get-widget-schema'    => array(
						'label'       => __( 'Get Widget Schema', 'mindcrafts-ai' ),
						'description' => __( 'Returns the JSON schema for a specific widget type.', 'mindcrafts-ai' ),
						'badges'      => array( 'read-only' ),
					),
					'mindcrafts-ai/get-page-structure'   => array(
						'label'       => __( 'Get Page Structure', 'mindcrafts-ai' ),
						'description' => __( 'Returns the full Elementor element tree for a page.', 'mindcrafts-ai' ),
						'badges'      => array( 'read-only' ),
					),
					'mindcrafts-ai/get-element-settings' => array(
						'label'       => __( 'Get Element Settings', 'mindcrafts-ai' ),
						'description' => __( 'Returns the settings of a specific element by ID.', 'mindcrafts-ai' ),
						'badges'      => array( 'read-only' ),
					),
					'mindcrafts-ai/list-pages'           => array(
						'label'       => __( 'List Pages', 'mindcrafts-ai' ),
						'description' => __( 'Lists all pages/posts that use Elementor.', 'mindcrafts-ai' ),
						'badges'      => array( 'read-only' ),
					),
					'mindcrafts-ai/list-templates'       => array(
						'label'       => __( 'List Templates', 'mindcrafts-ai' ),
						'description' => __( 'Lists all saved Elementor templates.', 'mindcrafts-ai' ),
						'badges'      => array( 'read-only' ),
					),
					'mindcrafts-ai/get-global-settings'  => array(
						'label'       => __( 'Get Global Settings', 'mindcrafts-ai' ),
						'description' => __( 'Returns global colors, typography, and theme settings.', 'mindcrafts-ai' ),
						'badges'      => array( 'read-only' ),
					),
				),
			),
			'page'             => array(
				'label' => __( 'Page Management', 'mindcrafts-ai' ),
				'tools' => array(
					'mindcrafts-ai/create-page'          => array(
						'label'       => __( 'Create Page', 'mindcrafts-ai' ),
						'description' => __( 'Creates a new WordPress page with Elementor enabled.', 'mindcrafts-ai' ),
						'badges'      => array(),
					),
					'mindcrafts-ai/update-page-settings' => array(
						'label'       => __( 'Update Page Settings', 'mindcrafts-ai' ),
						'description' => __( 'Updates Elementor page-level settings (layout, canvas, etc).', 'mindcrafts-ai' ),
						'badges'      => array(),
					),
					'mindcrafts-ai/delete-page-content'  => array(
						'label'       => __( 'Delete Page Content', 'mindcrafts-ai' ),
						'description' => __( 'Removes all Elementor content from a page.', 'mindcrafts-ai' ),
						'badges'      => array( 'destructive' ),
					),
					'mindcrafts-ai/import-template'      => array(
						'label'       => __( 'Import Template', 'mindcrafts-ai' ),
						'description' => __( 'Imports an Elementor template structure onto a page.', 'mindcrafts-ai' ),
						'badges'      => array(),
					),
					'mindcrafts-ai/export-page'          => array(
						'label'       => __( 'Export Page', 'mindcrafts-ai' ),
						'description' => __( 'Exports page Elementor data as JSON.', 'mindcrafts-ai' ),
						'badges'      => array( 'read-only' ),
					),
				),
			),
			'layout'           => array(
				'label' => __( 'Layout & Containers', 'mindcrafts-ai' ),
				'tools' => array(
					'mindcrafts-ai/add-container'      => array(
						'label'       => __( 'Add Container', 'mindcrafts-ai' ),
						'description' => __( 'Adds a flexbox container (top-level or nested).', 'mindcrafts-ai' ),
						'badges'      => array(),
					),
					'mindcrafts-ai/move-element'       => array(
						'label'       => __( 'Move Element', 'mindcrafts-ai' ),
						'description' => __( 'Moves an element to a new parent or position.', 'mindcrafts-ai' ),
						'badges'      => array(),
					),
					'mindcrafts-ai/remove-element'     => array(
						'label'       => __( 'Remove Element', 'mindcrafts-ai' ),
						'description' => __( 'Removes an element and all child elements.', 'mindcrafts-ai' ),
						'badges'      => array( 'destructive' ),
					),
					'mindcrafts-ai/duplicate-element'  => array(
						'label'       => __( 'Duplicate Element', 'mindcrafts-ai' ),
						'description' => __( 'Duplicates an element tree with fresh unique IDs.', 'mindcrafts-ai' ),
						'badges'      => array(),
					),
				),
			),
			'widgets'          => array(
				'label' => __( 'Core Widgets', 'mindcrafts-ai' ),
				'tools' => array(
					'mindcrafts-ai/add-widget'         => array(
						'label'       => __( 'Add Widget (Universal)', 'mindcrafts-ai' ),
						'description' => __( 'Universal tool to add any registered Elementor widget.', 'mindcrafts-ai' ),
						'badges'      => array(),
					),
					'mindcrafts-ai/update-widget'      => array(
						'label'       => __( 'Update Widget', 'mindcrafts-ai' ),
						'description' => __( 'Updates settings on any existing widget.', 'mindcrafts-ai' ),
						'badges'      => array(),
					),
					'mindcrafts-ai/add-heading'        => array(
						'label'       => __( 'Add Heading', 'mindcrafts-ai' ),
						'description' => __( 'Convenience tool for heading widget.', 'mindcrafts-ai' ),
						'badges'      => array(),
					),
					'mindcrafts-ai/add-text-editor'    => array(
						'label'       => __( 'Add Text Editor', 'mindcrafts-ai' ),
						'description' => __( 'Convenience tool for rich text editor widget.', 'mindcrafts-ai' ),
						'badges'      => array(),
					),
					'mindcrafts-ai/add-image'          => array(
						'label'       => __( 'Add Image', 'mindcrafts-ai' ),
						'description' => __( 'Convenience tool for image widget.', 'mindcrafts-ai' ),
						'badges'      => array(),
					),
					'mindcrafts-ai/add-button'         => array(
						'label'       => __( 'Add Button', 'mindcrafts-ai' ),
						'description' => __( 'Convenience tool for button widget.', 'mindcrafts-ai' ),
						'badges'      => array(),
					),
					'mindcrafts-ai/add-video'          => array(
						'label'       => __( 'Add Video', 'mindcrafts-ai' ),
						'description' => __( 'Convenience tool for video widget.', 'mindcrafts-ai' ),
						'badges'      => array(),
					),
					'mindcrafts-ai/add-icon'           => array(
						'label'       => __( 'Add Icon', 'mindcrafts-ai' ),
						'description' => __( 'Convenience tool for icon widget.', 'mindcrafts-ai' ),
						'badges'      => array(),
					),
					'mindcrafts-ai/add-spacer'         => array(
						'label'       => __( 'Add Spacer', 'mindcrafts-ai' ),
						'description' => __( 'Convenience tool for spacer widget.', 'mindcrafts-ai' ),
						'badges'      => array(),
					),
					'mindcrafts-ai/add-divider'        => array(
						'label'       => __( 'Add Divider', 'mindcrafts-ai' ),
						'description' => __( 'Convenience tool for divider widget.', 'mindcrafts-ai' ),
						'badges'      => array(),
					),
					'mindcrafts-ai/add-icon-box'       => array(
						'label'       => __( 'Add Icon Box', 'mindcrafts-ai' ),
						'description' => __( 'Convenience tool for icon box widget.', 'mindcrafts-ai' ),
						'badges'      => array(),
					),
				),
			),
			'pro_widgets'      => array(
				'label' => __( 'Elementor Pro Widgets', 'mindcrafts-ai' ),
				'tools' => array(
					'mindcrafts-ai/add-form'               => array(
						'label'       => __( 'Add Form', 'mindcrafts-ai' ),
						'description' => __( 'Pro form builder widget with fields and submit actions.', 'mindcrafts-ai' ),
						'badges'      => array( 'pro' ),
					),
					'mindcrafts-ai/add-posts-grid'         => array(
						'label'       => __( 'Add Posts Grid', 'mindcrafts-ai' ),
						'description' => __( 'Pro posts grid widget with query parameters.', 'mindcrafts-ai' ),
						'badges'      => array( 'pro' ),
					),
					'mindcrafts-ai/add-countdown'          => array(
						'label'       => __( 'Add Countdown', 'mindcrafts-ai' ),
						'description' => __( 'Pro countdown timer widget.', 'mindcrafts-ai' ),
						'badges'      => array( 'pro' ),
					),
					'mindcrafts-ai/add-price-table'        => array(
						'label'       => __( 'Add Price Table', 'mindcrafts-ai' ),
						'description' => __( 'Pro pricing table widget.', 'mindcrafts-ai' ),
						'badges'      => array( 'pro' ),
					),
					'mindcrafts-ai/add-flip-box'           => array(
						'label'       => __( 'Add Flip Box', 'mindcrafts-ai' ),
						'description' => __( 'Pro animated 3D flip box widget.', 'mindcrafts-ai' ),
						'badges'      => array( 'pro' ),
					),
					'mindcrafts-ai/add-animated-headline'  => array(
						'label'       => __( 'Add Animated Headline', 'mindcrafts-ai' ),
						'description' => __( 'Pro rotating and highlighted animated headline.', 'mindcrafts-ai' ),
						'badges'      => array( 'pro' ),
					),
				),
			),
			'templates_globals'=> array(
				'label' => __( 'Templates & Globals', 'mindcrafts-ai' ),
				'tools' => array(
					'mindcrafts-ai/save-as-template'          => array(
						'label'       => __( 'Save as Template', 'mindcrafts-ai' ),
						'description' => __( 'Saves a page or element tree as a reusable template.', 'mindcrafts-ai' ),
						'badges'      => array(),
					),
					'mindcrafts-ai/apply-template'            => array(
						'label'       => __( 'Apply Template', 'mindcrafts-ai' ),
						'description' => __( 'Applies an existing template into a page or container.', 'mindcrafts-ai' ),
						'badges'      => array(),
					),
					'mindcrafts-ai/update-global-colors'      => array(
						'label'       => __( 'Update Global Colors', 'mindcrafts-ai' ),
						'description' => __( 'Updates site-wide color palette in the active Elementor kit.', 'mindcrafts-ai' ),
						'badges'      => array(),
					),
					'mindcrafts-ai/update-global-typography'  => array(
						'label'       => __( 'Update Global Typography', 'mindcrafts-ai' ),
						'description' => __( 'Updates site-wide typography styles in the active kit.', 'mindcrafts-ai' ),
						'badges'      => array(),
					),
				),
			),
			'composite'        => array(
				'label' => __( 'Composite & AI Generation', 'mindcrafts-ai' ),
				'tools' => array(
					'mindcrafts-ai/build-page'           => array(
						'label'       => __( 'Build Page', 'mindcrafts-ai' ),
						'description' => __( 'Creates a complete page from a declarative JSON structure in one call.', 'mindcrafts-ai' ),
						'badges'      => array(),
					),
					'mindcrafts-ai/analyze-structure'    => array(
						'label'       => __( 'Analyze Structure', 'mindcrafts-ai' ),
						'description' => __( 'Audits layout hierarchy, container depths, and responsiveness.', 'mindcrafts-ai' ),
						'badges'      => array( 'read-only' ),
					),
					'mindcrafts-ai/clone-page'           => array(
						'label'       => __( 'Clone Page', 'mindcrafts-ai' ),
						'description' => __( 'Clones an entire Elementor page with regenerated IDs.', 'mindcrafts-ai' ),
						'badges'      => array(),
					),
					'mindcrafts-ai/bulk-update-settings' => array(
						'label'       => __( 'Bulk Update Settings', 'mindcrafts-ai' ),
						'description' => __( 'Updates settings across multiple elements in one batch operation.', 'mindcrafts-ai' ),
						'badges'      => array(),
					),
				),
			),
			'stock_images'     => array(
				'label' => __( 'Stock Images', 'mindcrafts-ai' ),
				'tools' => array(
					'mindcrafts-ai/search-images'    => array(
						'label'       => __( 'Search Images', 'mindcrafts-ai' ),
						'description' => __( 'Searches Openverse for Creative Commons licensed images.', 'mindcrafts-ai' ),
						'badges'      => array( 'read-only' ),
					),
					'mindcrafts-ai/sideload-image'   => array(
						'label'       => __( 'Sideload Image', 'mindcrafts-ai' ),
						'description' => __( 'Downloads an external image into the WordPress Media Library.', 'mindcrafts-ai' ),
						'badges'      => array(),
					),
					'mindcrafts-ai/add-stock-image'  => array(
						'label'       => __( 'Add Stock Image', 'mindcrafts-ai' ),
						'description' => __( 'Searches, downloads, and adds a stock image to the page in one call.', 'mindcrafts-ai' ),
						'badges'      => array(),
					),
				),
			),
			'woocommerce'      => array(
				'label' => __( 'WooCommerce Builder', 'mindcrafts-ai' ),
				'tools' => array(
					'mindcrafts-ai/add-woocommerce-products'            => array(
						'label'       => __( 'Add WooCommerce Products', 'mindcrafts-ai' ),
						'description' => __( 'Adds a WooCommerce products grid widget.', 'mindcrafts-ai' ),
						'badges'      => array( 'woo' ),
					),
					'mindcrafts-ai/add-woocommerce-categories'          => array(
						'label'       => __( 'Add WooCommerce Categories', 'mindcrafts-ai' ),
						'description' => __( 'Adds a WooCommerce category grid widget.', 'mindcrafts-ai' ),
						'badges'      => array( 'woo' ),
					),
					'mindcrafts-ai/add-woo-cart'                        => array(
						'label'       => __( 'Add Cart Widget', 'mindcrafts-ai' ),
						'description' => __( 'Adds the WooCommerce Cart widget.', 'mindcrafts-ai' ),
						'badges'      => array( 'woo' ),
					),
					'mindcrafts-ai/add-woo-checkout'                    => array(
						'label'       => __( 'Add Checkout Widget', 'mindcrafts-ai' ),
						'description' => __( 'Adds the WooCommerce Checkout widget.', 'mindcrafts-ai' ),
						'badges'      => array( 'woo' ),
					),
					'mindcrafts-ai/add-woo-my-account'                  => array(
						'label'       => __( 'Add My Account Widget', 'mindcrafts-ai' ),
						'description' => __( 'Adds the WooCommerce My Account widget.', 'mindcrafts-ai' ),
						'badges'      => array( 'woo' ),
					),
					'mindcrafts-ai/add-woo-product-rating'              => array(
						'label'       => __( 'Add Product Rating', 'mindcrafts-ai' ),
						'description' => __( 'Adds the WooCommerce Product Rating widget.', 'mindcrafts-ai' ),
						'badges'      => array( 'woo' ),
					),
					'mindcrafts-ai/create-woo-single-product-template'  => array(
						'label'       => __( 'Create Single Product Template', 'mindcrafts-ai' ),
						'description' => __( 'Builds complete single product theme template.', 'mindcrafts-ai' ),
						'badges'      => array( 'woo', 'pro' ),
					),
					'mindcrafts-ai/create-woo-archive-template'         => array(
						'label'       => __( 'Create Shop Archive Template', 'mindcrafts-ai' ),
						'description' => __( 'Builds complete shop product archive template.', 'mindcrafts-ai' ),
						'badges'      => array( 'woo', 'pro' ),
					),
				),
			),
			'addons'           => array(
				'label' => __( 'Third-Party Addons', 'mindcrafts-ai' ),
				'tools' => array(
					'mindcrafts-ai/list-addon-widgets'      => array(
						'label'       => __( 'List Addon Widgets', 'mindcrafts-ai' ),
						'description' => __( 'Lists all active ElementsKit, Essential Addons, UAE, etc. widgets with schema.', 'mindcrafts-ai' ),
						'badges'      => array( 'read-only' ),
					),
					'mindcrafts-ai/add-third-party-widget'  => array(
						'label'       => __( 'Add 3rd-Party Widget', 'mindcrafts-ai' ),
						'description' => __( 'Adds any third-party addon widget to a container.', 'mindcrafts-ai' ),
						'badges'      => array(),
					),
				),
			),
			'responsive'       => array(
				'label' => __( 'Responsive Controls', 'mindcrafts-ai' ),
				'tools' => array(
					'mindcrafts-ai/update-responsive-settings' => array(
						'label'       => __( 'Update Responsive Settings', 'mindcrafts-ai' ),
						'description' => __( 'Updates element settings for specific mobile, tablet, laptop, or widescreen breakpoints.', 'mindcrafts-ai' ),
						'badges'      => array(),
					),
				),
			),
			'theme_builder'    => array(
				'label' => __( 'Theme Builder', 'mindcrafts-ai' ),
				'tools' => array(
					'mindcrafts-ai/list-theme-templates'    => array(
						'label'       => __( 'List Theme Templates', 'mindcrafts-ai' ),
						'description' => __( 'Lists all Theme Builder templates with display conditions.', 'mindcrafts-ai' ),
						'badges'      => array( 'read-only', 'pro' ),
					),
					'mindcrafts-ai/create-theme-template'   => array(
						'label'       => __( 'Create Theme Template', 'mindcrafts-ai' ),
						'description' => __( 'Creates header, footer, single, archive, or search template.', 'mindcrafts-ai' ),
						'badges'      => array( 'pro' ),
					),
					'mindcrafts-ai/set-template-conditions' => array(
						'label'       => __( 'Set Conditions', 'mindcrafts-ai' ),
						'description' => __( 'Configures display conditions for Theme Builder templates.', 'mindcrafts-ai' ),
						'badges'      => array( 'pro' ),
					),
					'mindcrafts-ai/apply-theme-location'    => array(
						'label'       => __( 'Apply Theme Location', 'mindcrafts-ai' ),
						'description' => __( 'Binds template to header or footer site locations.', 'mindcrafts-ai' ),
						'badges'      => array( 'pro' ),
					),
				),
			),
			'popups'           => array(
				'label' => __( 'Popup Builder', 'mindcrafts-ai' ),
				'tools' => array(
					'mindcrafts-ai/create-popup'        => array(
						'label'       => __( 'Create Popup', 'mindcrafts-ai' ),
						'description' => __( 'Creates an Elementor popup with trigger conditions.', 'mindcrafts-ai' ),
						'badges'      => array( 'pro' ),
					),
					'mindcrafts-ai/list-popups'         => array(
						'label'       => __( 'List Popups', 'mindcrafts-ai' ),
						'description' => __( 'Lists all popup templates.', 'mindcrafts-ai' ),
						'badges'      => array( 'read-only', 'pro' ),
					),
					'mindcrafts-ai/set-popup-trigger'   => array(
						'label'       => __( 'Set Popup Trigger', 'mindcrafts-ai' ),
						'description' => __( 'Configures page_load, scroll, click, or exit intent triggers.', 'mindcrafts-ai' ),
						'badges'      => array( 'pro' ),
					),
				),
			),
			'dynamic_tags'     => array(
				'label' => __( 'Dynamic Tags', 'mindcrafts-ai' ),
				'tools' => array(
					'mindcrafts-ai/list-dynamic-tags'  => array(
						'label'       => __( 'List Dynamic Tags', 'mindcrafts-ai' ),
						'description' => __( 'Lists all registered dynamic tags from Elementor.', 'mindcrafts-ai' ),
						'badges'      => array( 'read-only' ),
					),
					'mindcrafts-ai/apply-dynamic-tag'  => array(
						'label'       => __( 'Apply Dynamic Tag', 'mindcrafts-ai' ),
						'description' => __( 'Binds a dynamic tag to an element setting.', 'mindcrafts-ai' ),
						'badges'      => array(),
					),
				),
			),
			'premium'          => array(
				'label' => __( 'Premium Features', 'mindcrafts-ai' ),
				'tools' => array(
					'mindcrafts-ai/optimize-seo'             => array(
						'label'       => __( 'Optimize SEO', 'mindcrafts-ai' ),
						'description' => __( 'AI generation of meta tags, alt text, and SEO schema.', 'mindcrafts-ai' ),
						'badges'      => array( 'premium' ),
					),
					'mindcrafts-ai/import-premium-template'  => array(
						'label'       => __( 'Import Premium Template', 'mindcrafts-ai' ),
						'description' => __( 'Downloads and imports templates from MindCrafts marketplace.', 'mindcrafts-ai' ),
						'badges'      => array( 'premium' ),
					),
				),
			),
		);
	}

	/**
	 * Get a flat list of all tool slugs.
	 *
	 * @since 1.0.0
	 *
	 * @return string[] All tool slugs.
	 */
	public function get_all_tool_slugs(): array {
		$slugs = array();

		foreach ( $this->get_all_tools() as $category ) {
			foreach ( $category['tools'] as $slug => $tool ) {
				$slugs[] = $slug;
			}
		}

		return $slugs;
	}

	/**
	 * Count enabled tools.
	 *
	 * @since 1.0.0
	 *
	 * @return int Number of enabled tools.
	 */
	public function get_enabled_tool_count(): int {
		$all      = $this->get_all_tool_slugs();
		$disabled = get_option( self::OPTION_DISABLED_TOOLS, array() );

		return count( array_diff( $all, $disabled ) );
	}

	/**
	 * Count total tools.
	 *
	 * @since 1.0.0
	 *
	 * @return int Total number of tools.
	 */
	public function get_total_tool_count(): int {
		return count( $this->get_all_tool_slugs() );
	}
}
