<?php
/**
 * Read-only query/discovery MCP abilities for Elementor.
 *
 * Registers 7 read-only tools that let AI agents discover widgets,
 * inspect page structures, and read Elementor data.
 *
 * @package MindCrafts_AI
 * @since   1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Registers and implements the 7 read-only query abilities.
 *
 * @since 1.0.0
 */
class MindCrafts_AI_Query_Abilities {

	/**
	 * The data access layer.
	 *
	 * @var MindCrafts_AI_Data
	 */
	private $data;

	/**
	 * The schema generator.
	 *
	 * @var MindCrafts_AI_Schema_Generator
	 */
	private $schema_generator;

	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 *
	 * @param MindCrafts_AI_Data             $data             The data access layer.
	 * @param MindCrafts_AI_Schema_Generator $schema_generator The schema generator.
	 */
	public function __construct( MindCrafts_AI_Data $data, MindCrafts_AI_Schema_Generator $schema_generator ) {
		$this->data             = $data;
		$this->schema_generator = $schema_generator;
	}

	/**
	 * Checks and increments the rate limit for a given tool.
	 *
	 * T2-5: Fixed window rate limiting using Transients API.
	 *
	 * @since 2.1.0
	 *
	 * @param string $tool_name The name of the tool.
	 * @param int    $limit     Maximum allowed calls in the period.
	 * @param int    $period    The period in seconds.
	 * @return true|\WP_Error True if allowed, WP_Error if rate limit exceeded.
	 */
	public function check_rate_limit( string $tool_name, int $limit = 30, int $period = 60 ) {
		$user_id = get_current_user_id();
		$transient_key = 'mcp_rate_' . md5( $tool_name . '_' . $user_id );

		$data = get_transient( $transient_key );
		$now  = time();

		if ( false === $data || ! is_array( $data ) ) {
			$data = array(
				'count'      => 1,
				'reset_time' => $now + $period,
			);
			set_transient( $transient_key, $data, $period );
			return true;
		}

		if ( $now > $data['reset_time'] ) {
			$data = array(
				'count'      => 1,
				'reset_time' => $now + $period,
			);
			set_transient( $transient_key, $data, $period );
			return true;
		}

		if ( $data['count'] >= $limit ) {
			return new \WP_Error(
				'rate_limit_exceeded',
				sprintf(
					/* translators: %s: tool name */
					__( 'Rate limit exceeded for tool "%s". Please wait before retrying.', 'mindcrafts-ai' ),
					$tool_name
				),
				array( 'status' => 429 )
			);
		}

		$data['count']++;
		$ttl = max( 1, $data['reset_time'] - $now );
		set_transient( $transient_key, $data, $ttl );

		return true;
	}

	/**
	 * Returns the ability names registered by this class.
	 *
	 * @since 1.0.0
	 *
	 * @return string[] Array of ability names.
	 */
	public function get_ability_names(): array {
		return array(
			'mindcrafts-ai/list-widgets',
			'mindcrafts-ai/get-widget-schema',
			'mindcrafts-ai/get-page-structure',
			'mindcrafts-ai/get-element-settings',
			'mindcrafts-ai/list-pages',
			'mindcrafts-ai/list-templates',
			'mindcrafts-ai/get-global-settings',
			'mindcrafts-ai/debug-meta',
		);
	}

	/**
	 * Registers all query abilities with the WordPress Abilities API.
	 *
	 * Must be called during the `wp_abilities_api_init` action.
	 *
	 * @since 1.0.0
	 */
	public function register(): void {
		$this->register_list_widgets();
		$this->register_get_widget_schema();
		$this->register_get_page_structure();
		$this->register_get_element_settings();
		$this->register_list_pages();
		$this->register_list_templates();
		$this->register_get_global_settings();
		$this->register_debug_meta();
	}

	/**
	 * Shared permission callback for read-only tools.
	 *
	 * @since 1.0.0
	 *
	 * @param array|null $input The input parameters.
	 * @return bool Whether the current user can use read tools.
	 */
	public function check_read_permission( $input = null ): bool {
		if ( ! current_user_can( 'edit_posts' ) ) {
			return false;
		}

		$post_id = absint( $input['post_id'] ?? 0 );
		if ( $post_id && ! current_user_can( 'edit_post', $post_id ) ) {
			return false;
		}

		return true;
	}

	/**
	 * Registers the list-widgets ability.
	 *
	 * @since 1.0.0
	 */
	private function register_list_widgets(): void {
		wp_register_ability(
			'mindcrafts-ai/list-widgets',
			array(
				'label'               => __( 'List Elementor Widgets', 'mindcrafts-ai' ),
				'description'         => __( 'Returns all registered Elementor widget types with their names, titles, icons, categories, and keywords. Optionally filter by widget category.', 'mindcrafts-ai' ),
				'category'            => 'mindcrafts-ai',
				'execute_callback'    => array( $this, 'execute_list_widgets' ),
				'permission_callback' => array( $this, 'check_read_permission' ),
				'input_schema'        => array(
					'type'       => 'object',
					'properties' => array(
						'category' => array(
							'type'        => 'string',
							'description' => __( 'Filter widgets by category slug.', 'mindcrafts-ai' ),
						),
					),
				),
				'output_schema'       => array(
					'type'       => 'object',
					'properties' => array(
						'widgets' => array(
							'type'  => 'array',
							'items' => array(
								'type'       => 'object',
								'properties' => array(
									'name'       => array( 'type' => 'string' ),
									'title'      => array( 'type' => 'string' ),
									'icon'       => array( 'type' => 'string' ),
									'categories' => array(
										'type'  => 'array',
										'items' => array( 'type' => 'string' ),
									),
									'keywords'   => array(
										'type'  => 'array',
										'items' => array( 'type' => 'string' ),
									),
								),
							),
						),
					),
				),
				'meta'                => array(
					'annotations'  => array(
						'readonly'    => true,
						'destructive' => false,
						'idempotent'  => true,
					),
					'show_in_rest' => true,
				),
			)
		);
	}

	/**
	 * Executes the list-widgets ability.
	 *
	 * @since 1.0.0
	 *
	 * @param array|null $input The input parameters.
	 * @return array|\WP_Error The widgets list or WP_Error.
	 */
	public function execute_list_widgets( $input = null ) {
		$rate_limit = $this->check_rate_limit( 'list-widgets', 30, 60 );
		if ( is_wp_error( $rate_limit ) ) {
			return $rate_limit;
		}

		$category = sanitize_text_field( $input['category'] ?? '' );
		$widgets  = $this->data->get_registered_widgets();
		$result   = array();

		foreach ( $widgets as $name => $widget ) {
			$widget_categories = $widget->get_categories();

			if ( ! empty( $category ) && ! in_array( $category, $widget_categories, true ) ) {
				continue;
			}

			$result[] = array(
				'name'       => $widget->get_name(),
				'title'      => $widget->get_title(),
				'icon'       => $widget->get_icon(),
				'categories' => $widget_categories,
				'keywords'   => $widget->get_keywords(),
			);
		}

		return array( 'widgets' => $result );
	}

	/**
	 * Registers the get-widget-schema ability.
	 *
	 * @since 1.0.0
	 */
	private function register_get_widget_schema(): void {
		wp_register_ability(
			'mindcrafts-ai/get-widget-schema',
			array(
				'label'               => __( 'Get Widget Schema', 'mindcrafts-ai' ),
				'description'         => __( 'Returns the full JSON Schema for a widget type\'s settings, describing all available controls and their types. Use this to discover what settings a widget accepts before creating or updating it.', 'mindcrafts-ai' ),
				'category'            => 'mindcrafts-ai',
				'execute_callback'    => array( $this, 'execute_get_widget_schema' ),
				'permission_callback' => array( $this, 'check_read_permission' ),
				'input_schema'        => array(
					'type'       => 'object',
					'properties' => array(
						'widget_type' => array(
							'type'        => 'string',
							'description' => __( 'The widget type name, e.g. "heading", "button", "image".', 'mindcrafts-ai' ),
						),
					),
					'required'   => array( 'widget_type' ),
				),
				'output_schema'       => array(
					'type'       => 'object',
					'properties' => array(
						'widget_type' => array( 'type' => 'string' ),
						'title'       => array( 'type' => 'string' ),
						'schema'      => array( 'type' => 'object' ),
					),
				),
				'meta'                => array(
					'annotations'  => array(
						'readonly'    => true,
						'destructive' => false,
						'idempotent'  => true,
					),
					'show_in_rest' => true,
				),
			)
		);
	}

	/**
	 * Executes the get-widget-schema ability.
	 *
	 * @since 1.0.0
	 *
	 * @param array $input The input parameters.
	 * @return array|\WP_Error The widget schema or WP_Error.
	 */
	public function execute_get_widget_schema( $input ) {
		$rate_limit = $this->check_rate_limit( 'get-widget-schema', 30, 60 );
		if ( is_wp_error( $rate_limit ) ) {
			return $rate_limit;
		}

		$widget_type = sanitize_text_field( $input['widget_type'] ?? '' );

		if ( empty( $widget_type ) ) {
			return new \WP_Error( 'missing_widget_type', __( 'The widget_type parameter is required.', 'mindcrafts-ai' ) );
		}

		$schema = $this->schema_generator->generate( $widget_type );

		if ( is_wp_error( $schema ) ) {
			return $schema;
		}

		$widgets_manager = \Elementor\Plugin::$instance->widgets_manager;
		$widget          = $widgets_manager->get_widget_types( $widget_type );

		return array(
			'widget_type' => $widget_type,
			'title'       => $widget->get_title(),
			'schema'      => $schema,
		);
	}

	/**
	 * Registers the get-page-structure ability.
	 *
	 * @since 1.0.0
	 */
	private function register_get_page_structure(): void {
		wp_register_ability(
			'mindcrafts-ai/get-page-structure',
			array(
				'label'               => __( 'Get Page Structure', 'mindcrafts-ai' ),
				'description'         => __( 'Returns the element tree (containers, nested containers, and widgets) for a specific page/post. Use this to understand layout structure and find element IDs.', 'mindcrafts-ai' ),
				'category'            => 'mindcrafts-ai',
				'execute_callback'    => array( $this, 'execute_get_page_structure' ),
				'permission_callback' => array( $this, 'check_read_permission' ),
				'input_schema'        => array(
					'type'       => 'object',
					'properties' => array(
						'post_id' => array(
							'type'        => 'integer',
							'description' => __( 'The WordPress post/page ID.', 'mindcrafts-ai' ),
						),
					),
					'required'   => array( 'post_id' ),
				),
				'output_schema'       => array(
					'type'       => 'object',
					'properties' => array(
						'post_id'   => array( 'type' => 'integer' ),
						'title'     => array( 'type' => 'string' ),
						'structure' => array( 'type' => 'array' ),
					),
				),
				'meta'                => array(
					'annotations'  => array(
						'readonly'    => true,
						'destructive' => false,
						'idempotent'  => true,
					),
					'show_in_rest' => true,
				),
			)
		);
	}

	/**
	 * Executes the get-page-structure ability.
	 *
	 * @since 1.0.0
	 *
	 * @param array $input The input parameters.
	 * @return array|\WP_Error The page structure or WP_Error.
	 */
	public function execute_get_page_structure( $input ) {
		$post_id = absint( $input['post_id'] ?? 0 );

		if ( ! $post_id ) {
			return new \WP_Error( 'missing_post_id', __( 'The post_id parameter is required.', 'mindcrafts-ai' ) );
		}

		$post = get_post( $post_id );
		if ( ! $post ) {
			return new \WP_Error( 'post_not_found', __( 'Post not found.', 'mindcrafts-ai' ) );
		}

		$data = $this->data->get_page_data( $post_id );

		if ( is_wp_error( $data ) ) {
			return $data;
		}

		return array(
			'post_id'   => $post_id,
			'title'     => $post->post_title,
			'structure' => $this->simplify_structure( $data ),
		);
	}

	/**
	 * Simplifies the element tree for AI consumption.
	 *
	 * @since 1.0.0
	 *
	 * @param array $elements The raw elements array.
	 * @return array Simplified element tree.
	 */
	private function simplify_structure( array $elements ): array {
		$result = array();

		foreach ( $elements as $element ) {
			$item = array(
				'id'     => $element['id'] ?? '',
				'elType' => $element['elType'] ?? '',
			);

			if ( ! empty( $element['widgetType'] ) ) {
				$item['widgetType'] = $element['widgetType'];
			}

			// Include key settings for context.
			if ( ! empty( $element['settings'] ) ) {
				$key_settings = $this->extract_key_settings( $element );
				if ( ! empty( $key_settings ) ) {
					$item['settings_summary'] = $key_settings;
				}
			}

			if ( ! empty( $element['elements'] ) ) {
				$item['elements'] = $this->simplify_structure( $element['elements'] );
			}

			$result[] = $item;
		}

		return $result;
	}

	/**
	 * Extracts a few key settings for a summary view.
	 *
	 * @since 1.0.0
	 *
	 * @param array $element The element array.
	 * @return array Key settings for summary.
	 */
	private function extract_key_settings( array $element ): array {
		$settings = $element['settings'] ?? array();
		$summary  = array();

		// Widget-specific key settings.
		$key_fields = array( 'title', 'editor', 'text', 'image', 'link', 'html', 'header_size' );
		foreach ( $key_fields as $field ) {
			if ( isset( $settings[ $field ] ) && '' !== $settings[ $field ] ) {
				$value = $settings[ $field ];
				// Truncate long strings.
				if ( is_string( $value ) && strlen( $value ) > 100 ) {
					$value = substr( $value, 0, 100 ) . '...';
				}
				$summary[ $field ] = $value;
			}
		}

		// Container layout settings.
		if ( 'container' === ( $element['elType'] ?? '' ) ) {
			foreach ( array( 'flex_direction', 'content_width', 'container_type' ) as $field ) {
				if ( isset( $settings[ $field ] ) && '' !== $settings[ $field ] ) {
					$summary[ $field ] = $settings[ $field ];
				}
			}
		}

		return $summary;
	}

	/**
	 * Registers the get-element-settings ability.
	 *
	 * @since 1.0.0
	 */
	private function register_get_element_settings(): void {
		wp_register_ability(
			'mindcrafts-ai/get-element-settings',
			array(
				'label'               => __( 'Get Element Settings', 'mindcrafts-ai' ),
				'description'         => __( 'Returns the current settings for a specific element on a page. Provide the post ID and element ID to retrieve all control values for that element.', 'mindcrafts-ai' ),
				'category'            => 'mindcrafts-ai',
				'execute_callback'    => array( $this, 'execute_get_element_settings' ),
				'permission_callback' => array( $this, 'check_read_permission' ),
				'input_schema'        => array(
					'type'       => 'object',
					'properties' => array(
						'post_id'    => array(
							'type'        => 'integer',
							'description' => __( 'The WordPress post/page ID.', 'mindcrafts-ai' ),
						),
						'element_id' => array(
							'type'        => 'string',
							'description' => __( 'The Elementor element ID.', 'mindcrafts-ai' ),
						),
					),
					'required'   => array( 'post_id', 'element_id' ),
				),
				'output_schema'       => array(
					'type'       => 'object',
					'properties' => array(
						'post_id'    => array( 'type' => 'integer' ),
						'element_id' => array( 'type' => 'string' ),
						'elType'     => array( 'type' => 'string' ),
						'widgetType' => array( 'type' => 'string' ),
						'settings'   => array( 'type' => 'object' ),
					),
				),
				'meta'                => array(
					'annotations'  => array(
						'readonly'    => true,
						'destructive' => false,
						'idempotent'  => true,
					),
					'show_in_rest' => true,
				),
			)
		);
	}

	/**
	 * Executes the get-element-settings ability.
	 *
	 * @since 1.0.0
	 *
	 * @param array $input The input parameters.
	 * @return array|\WP_Error The element settings or WP_Error.
	 */
	public function execute_get_element_settings( $input ) {
		$post_id    = absint( $input['post_id'] ?? 0 );
		$element_id = sanitize_text_field( $input['element_id'] ?? '' );

		if ( ! $post_id ) {
			return new \WP_Error( 'missing_post_id', __( 'The post_id parameter is required.', 'mindcrafts-ai' ) );
		}

		if ( empty( $element_id ) ) {
			return new \WP_Error( 'missing_element_id', __( 'The element_id parameter is required.', 'mindcrafts-ai' ) );
		}

		$data = $this->data->get_page_data( $post_id );

		if ( is_wp_error( $data ) ) {
			return $data;
		}

		$element = $this->data->find_element_by_id( $data, $element_id );

		if ( ! $element ) {
			return new \WP_Error(
				'element_not_found',
				sprintf(
					/* translators: %s: element ID */
					__( 'Element "%s" not found on this page.', 'mindcrafts-ai' ),
					$element_id
				)
			);
		}

		return array(
			'post_id'    => $post_id,
			'element_id' => $element_id,
			'elType'     => $element['elType'] ?? '',
			'widgetType' => $element['widgetType'] ?? '',
			'settings'   => $element['settings'] ?? array(),
		);
	}

	/**
	 * Registers the list-pages ability.
	 *
	 * @since 1.0.0
	 */
	private function register_list_pages(): void {
		wp_register_ability(
			'mindcrafts-ai/list-pages',
			array(
				'label'               => __( 'List Elementor Pages', 'mindcrafts-ai' ),
				'description'         => __( 'Returns all WordPress pages and posts that are built with Elementor. Optionally filter by post type and status.', 'mindcrafts-ai' ),
				'category'            => 'mindcrafts-ai',
				'execute_callback'    => array( $this, 'execute_list_pages' ),
				'permission_callback' => array( $this, 'check_read_permission' ),
				'input_schema'        => array(
					'type'       => 'object',
					'properties' => array(
						'post_type' => array(
							'type'        => 'string',
							'description' => __( 'Filter by post type (e.g. "page", "post"). Default: any.', 'mindcrafts-ai' ),
						),
						'status'    => array(
							'type'        => 'string',
							'description' => __( 'Filter by post status (e.g. "publish", "draft"). Default: any.', 'mindcrafts-ai' ),
						),
						'per_page'  => array(
							'type'        => 'integer',
							'description' => __( 'Number of pages per page. Default: 50. Max: 200.', 'mindcrafts-ai' ),
						),
						'page'      => array(
							'type'        => 'integer',
							'description' => __( 'Page number (1-indexed). Default: 1.', 'mindcrafts-ai' ),
						),
					),
				),
				'output_schema'       => array(
					'type'       => 'object',
					'properties' => array(
						'pages'       => array(
							'type'  => 'array',
							'items' => array(
								'type'       => 'object',
								'properties' => array(
									'post_id'  => array( 'type' => 'integer' ),
									'title'    => array( 'type' => 'string' ),
									'type'     => array( 'type' => 'string' ),
									'status'   => array( 'type' => 'string' ),
									'url'      => array( 'type' => 'string' ),
									'modified' => array( 'type' => 'string' ),
								),
							),
						),
						'total'       => array( 'type' => 'integer', 'description' => 'Total number of matching pages.' ),
						'total_pages' => array( 'type' => 'integer', 'description' => 'Total pages of results.' ),
						'page'        => array( 'type' => 'integer' ),
						'per_page'    => array( 'type' => 'integer' ),
					),
				),
				'meta'                => array(
					'annotations'  => array(
						'readonly'    => true,
						'destructive' => false,
						'idempotent'  => true,
					),
					'show_in_rest' => true,
				),
			)
		);
	}

	/**
	 * Executes the list-pages ability.
	 *
	 * @since 1.0.0
	 *
	 * @param array|null $input The input parameters.
	 * @return array The pages list.
	 */
	public function execute_list_pages( $input = null ): array {
		$post_type = sanitize_text_field( $input['post_type'] ?? '' );
		$status    = sanitize_text_field( $input['status'] ?? '' );
		$per_page  = min( absint( $input['per_page'] ?? 50 ), 200 );
		$page      = max( 1, absint( $input['page'] ?? 1 ) );

		$query_args = array(
			'post_type'      => ! empty( $post_type ) ? $post_type : array( 'page', 'post' ),
			'post_status'    => ! empty( $status ) ? $status : 'any',
			'posts_per_page' => $per_page,
			'paged'          => $page,
			'meta_query'     => array(
				array(
					'key'   => '_elementor_edit_mode',
					'value' => 'builder',
				),
			),
			'orderby'        => 'modified',
			'order'          => 'DESC',
			'no_found_rows'  => false,
		);

		$query = new \WP_Query( $query_args );
		$pages = array();

		foreach ( $query->posts as $post ) {
			$pages[] = array(
				'post_id'  => $post->ID,
				'title'    => $post->post_title,
				'type'     => $post->post_type,
				'status'   => $post->post_status,
				'url'      => get_permalink( $post->ID ),
				'modified' => $post->post_modified,
			);
		}

		$total       = (int) $query->found_posts;
		$total_pages = (int) $query->max_num_pages;

		return array(
			'pages'       => $pages,
			'total'       => $total,
			'total_pages' => $total_pages,
			'page'        => $page,
			'per_page'    => $per_page,
		);
	}

	/**
	 * Registers the list-templates ability.
	 *
	 * @since 1.0.0
	 */
	private function register_list_templates(): void {
		wp_register_ability(
			'mindcrafts-ai/list-templates',
			array(
				'label'               => __( 'List Elementor Templates', 'mindcrafts-ai' ),
				'description'         => __( 'Returns all saved Elementor templates from the template library. Optionally filter by template type (page, section, container).', 'mindcrafts-ai' ),
				'category'            => 'mindcrafts-ai',
				'execute_callback'    => array( $this, 'execute_list_templates' ),
				'permission_callback' => array( $this, 'check_read_permission' ),
				'input_schema'        => array(
					'type'       => 'object',
					'properties' => array(
						'template_type' => array(
							'type'        => 'string',
							'description' => __( 'Filter by template type (e.g. "page", "section", "container").', 'mindcrafts-ai' ),
						),
					),
				),
				'output_schema'       => array(
					'type'       => 'object',
					'properties' => array(
						'templates' => array(
							'type'  => 'array',
							'items' => array(
								'type'       => 'object',
								'properties' => array(
									'id'    => array( 'type' => 'integer' ),
									'title' => array( 'type' => 'string' ),
									'type'  => array( 'type' => 'string' ),
									'date'  => array( 'type' => 'string' ),
								),
							),
						),
					),
				),
				'meta'                => array(
					'annotations'  => array(
						'readonly'    => true,
						'destructive' => false,
						'idempotent'  => true,
					),
					'show_in_rest' => true,
				),
			)
		);
	}

	/**
	 * Executes the list-templates ability.
	 *
	 * @since 1.0.0
	 *
	 * @param array|null $input The input parameters.
	 * @return array The templates list.
	 */
	public function execute_list_templates( $input = null ): array {
		$template_type = sanitize_text_field( $input['template_type'] ?? '' );

		$query_args = array(
			'post_type'      => 'elementor_library',
			'posts_per_page' => 100,
			'post_status'    => 'publish',
		);

		if ( ! empty( $template_type ) ) {
			$query_args['meta_query'] = array(
				array(
					'key'   => '_elementor_template_type',
					'value' => $template_type,
				),
			);
		}

		$query     = new \WP_Query( $query_args );
		$templates = array();

		foreach ( $query->posts as $post ) {
			$templates[] = array(
				'id'    => $post->ID,
				'title' => $post->post_title,
				'type'  => get_post_meta( $post->ID, '_elementor_template_type', true ),
				'date'  => $post->post_date,
			);
		}

		return array( 'templates' => $templates );
	}

	/**
	 * Registers the get-global-settings ability.
	 *
	 * @since 1.0.0
	 */
	private function register_get_global_settings(): void {
		wp_register_ability(
			'mindcrafts-ai/get-global-settings',
			array(
				'label'               => __( 'Get Global Settings', 'mindcrafts-ai' ),
				'description'         => __( 'Returns the active Elementor kit/global settings including colors, typography, spacing, and breakpoints. These are the site-wide design tokens used across all pages.', 'mindcrafts-ai' ),
				'category'            => 'mindcrafts-ai',
				'execute_callback'    => array( $this, 'execute_get_global_settings' ),
				'permission_callback' => array( $this, 'check_read_permission' ),
				'input_schema'        => array(
					'type'       => 'object',
					'properties' => array(),
				),
				'output_schema'       => array(
					'type'       => 'object',
					'properties' => array(
						'colors'      => array( 'type' => 'array' ),
						'typography'  => array( 'type' => 'array' ),
						'settings'    => array( 'type' => 'object' ),
					),
				),
				'meta'                => array(
					'annotations'  => array(
						'readonly'    => true,
						'destructive' => false,
						'idempotent'  => true,
					),
					'show_in_rest' => true,
				),
			)
		);
	}

	/**
	 * Executes the get-global-settings ability.
	 *
	 * @since 1.0.0
	 *
	 * @param array|null $input The input parameters (unused).
	 * @return array|\WP_Error The global settings or WP_Error.
	 */
	public function execute_get_global_settings( $input = null ) {
		$kits_manager = \Elementor\Plugin::$instance->kits_manager;
		$kit          = $kits_manager->get_active_kit();

		if ( ! $kit ) {
			return new \WP_Error( 'kit_not_found', __( 'Active Elementor kit not found.', 'mindcrafts-ai' ) );
		}

		$settings = $kit->get_settings();

		// Extract commonly useful global settings.
		$colors     = $settings['system_colors'] ?? $settings['custom_colors'] ?? array();
		$typography = $settings['system_typography'] ?? $settings['custom_typography'] ?? array();

		return array(
			'colors'     => $colors,
			'typography' => $typography,
			'settings'   => $settings,
		);
	}

	/**
	 * Registers the debug-meta ability.
	 *
	 * @since 1.0.0
	 */
	private function register_debug_meta(): void {
		wp_register_ability(
			'mindcrafts-ai/debug-meta',
			array(
				'label'               => __( 'Debug Meta', 'mindcrafts-ai' ),
				'description'         => __( 'Returns all raw meta for a post.', 'mindcrafts-ai' ),
				'category'            => 'mindcrafts-ai',
				'execute_callback'    => array( $this, 'execute_debug_meta' ),
				'permission_callback' => array( $this, 'check_read_permission' ),
				'input_schema'        => array(
					'type'       => 'object',
					'properties' => array(
						'post_id' => array(
							'type'        => 'integer',
							'description' => __( 'The post/page ID.', 'mindcrafts-ai' ),
						),
					),
					'required'   => array( 'post_id' ),
				),
			)
		);
	}

	/**
	 * Executes the debug-meta ability.
	 *
	 * @since 1.0.0
	 *
	 * @param array $input The input data.
	 * @return array|\WP_Error Result data.
	 */
	public function execute_debug_meta( $input ) {
		$post_id = absint( $input['post_id'] ?? 0 );

		if ( ! $post_id ) {
			return new \WP_Error( 'missing_post_id', __( 'The post_id parameter is required.', 'mindcrafts-ai' ) );
		}

		$meta = get_post_meta( $post_id );
		
		return array(
			'post_id' => $post_id,
			'meta'    => $meta,
		);
	}
}
