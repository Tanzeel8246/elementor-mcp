<?php
/**
 * Composite/high-level MCP abilities for Elementor.
 *
 * Registers the build-page tool that creates a complete page from
 * a declarative structure in a single call.
 *
 * @package MindCrafts_AI
 * @since   1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Registers and implements the composite abilities.
 *
 * @since 1.0.0
 */
class MindCrafts_AI_Composite_Abilities {

	/**
	 * @var MindCrafts_AI_Data
	 */
	private $data;

	/**
	 * @var MindCrafts_AI_Element_Factory
	 */
	private $factory;

	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 *
	 * @param MindCrafts_AI_Data            $data    The data access layer.
	 * @param MindCrafts_AI_Element_Factory $factory The element factory.
	 */
	public function __construct( MindCrafts_AI_Data $data, MindCrafts_AI_Element_Factory $factory ) {
		$this->data    = $data;
		$this->factory = $factory;
	}

	/**
	 * Returns the ability names registered by this class.
	 *
	 * @since 1.0.0
	 *
	 * @return string[]
	 */
	public function get_ability_names(): array {
		return array(
			'mindcrafts-ai/build-page',
		);
	}

	/**
	 * Registers all composite abilities.
	 *
	 * @since 1.0.0
	 */
	public function register(): void {
		$this->register_build_page();
	}

	/**
	 * Permission check for page creation.
	 *
	 * @since 1.0.0
	 *
	 * @param array|null $input The input parameters.
	 * @return bool
	 */
	public function check_create_permission( $input = null ): bool {
		$post_type = sanitize_key( $input['post_type'] ?? 'page' );

		if ( 'post' === $post_type ) {
			return current_user_can( 'edit_posts' );
		}

		if ( 'page' === $post_type ) {
			return current_user_can( 'edit_pages' );
		}

		return false;
	}

	// -------------------------------------------------------------------------
	// build-page
	// -------------------------------------------------------------------------

	/**
	 * Registers the build-page ability.
	 *
	 * @since 1.0.0
	 */
	private function register_build_page(): void {
		wp_register_ability(
			'mindcrafts-ai/build-page',
			array(
				'label'               => __( 'Build Page', 'mindcrafts-ai' ),
				'description'         => __( 'Creates a complete Elementor page from a declarative structure in a single call. Pass a "structure" array of containers and widgets. Each container may have "children" (nested containers or widgets). Each widget needs "widget_type" and "settings". The structure is validated before any post is created — invalid structures return an error without creating orphan pages.', 'mindcrafts-ai' ),
				'category'            => 'mindcrafts-ai',
				'execute_callback'    => array( $this, 'execute_build_page' ),
				'permission_callback' => array( $this, 'check_create_permission' ),
				'input_schema'        => array(
					'type'       => 'object',
					'properties' => array(
						'title'         => array(
							'type'        => 'string',
							'description' => __( 'Page title.', 'mindcrafts-ai' ),
						),
						'status'        => array(
							'type'        => 'string',
							'enum'        => array( 'draft', 'publish' ),
							'description' => __( 'Post status. Default: draft.', 'mindcrafts-ai' ),
						),
						'post_type'     => array(
							'type'        => 'string',
							'enum'        => array( 'page', 'post' ),
							'description' => __( 'Post type. Default: page.', 'mindcrafts-ai' ),
						),
						'page_settings' => array(
							'type'        => 'object',
							'description' => __( 'Page-level Elementor settings (background, padding, etc.).', 'mindcrafts-ai' ),
						),
						'structure'     => array(
							'type'        => 'array',
							'description' => __( 'Declarative element tree. Each item has type (container|widget), settings, and optionally children (for containers) or widget_type (for widgets). Use "children" key (not "elements") for nested items.', 'mindcrafts-ai' ),
							'items'       => array(
								'type'       => 'object',
								'properties' => array(
									'type'        => array(
										'type' => 'string',
										'enum' => array( 'container', 'widget' ),
									),
									'widget_type' => array( 'type' => 'string' ),
									'settings'    => array( 'type' => 'object' ),
									'children'    => array( 'type' => 'array' ),
								),
								'required' => array( 'type' ),
							),
						),
					),
					'required'   => array( 'title', 'structure' ),
				),
				'output_schema'       => array(
					'type'       => 'object',
					'properties' => array(
						'post_id'          => array( 'type' => 'integer' ),
						'title'            => array( 'type' => 'string' ),
						'edit_url'         => array( 'type' => 'string' ),
						'preview_url'      => array( 'type' => 'string' ),
						'elements_created' => array( 'type' => 'integer' ),
					),
				),
				'meta'                => array(
					'annotations'  => array(
						'readonly'    => false,
						'destructive' => false,
						'idempotent'  => false,
					),
					'show_in_rest' => true,
				),
			)
		);
	}

	/**
	 * Executes the build-page ability.
	 *
	 * T1-3 FIX: Structure is validated and built BEFORE the post is created,
	 * preventing orphan posts on failure.
	 * T1-6 FIX: Counter uses pass-by-reference local variable, not class property.
	 *
	 * @since 1.0.0
	 *
	 * @param array $input The input parameters.
	 * @return array|\WP_Error
	 */
	public function execute_build_page( $input ) {
		$title         = sanitize_text_field( $input['title'] ?? '' );
		$status        = sanitize_key( $input['status'] ?? 'draft' );
		$post_type     = sanitize_key( $input['post_type'] ?? 'page' );
		$page_settings = $input['page_settings'] ?? array();
		$structure     = $input['structure'] ?? array();

		if ( empty( $title ) ) {
			return new \WP_Error( 'missing_title', __( 'The title parameter is required.', 'mindcrafts-ai' ) );
		}

		if ( empty( $structure ) || ! is_array( $structure ) ) {
			return new \WP_Error( 'missing_structure', __( 'The structure parameter is required and must be an array.', 'mindcrafts-ai' ) );
		}

		if ( ! in_array( $status, array( 'draft', 'publish' ), true ) ) {
			return new \WP_Error( 'invalid_status', __( 'The status parameter must be draft or publish.', 'mindcrafts-ai' ) );
		}

		if ( ! in_array( $post_type, array( 'page', 'post' ), true ) ) {
			return new \WP_Error( 'invalid_post_type', __( 'The post_type parameter must be page or post.', 'mindcrafts-ai' ) );
		}

		$publish_cap = ( 'page' === $post_type ) ? 'publish_pages' : 'publish_posts';
		if ( 'publish' === $status && ! current_user_can( $publish_cap ) ) {
			return new \WP_Error( 'publish_not_allowed', __( 'You do not have permission to publish this content type.', 'mindcrafts-ai' ) );
		}

		// T1-3 FIX: Validate and build the element tree BEFORE creating the post.
		// Invalid structures now return errors without creating any orphan pages.
		$elements_count = 0;
		$elements       = $this->build_elements( $structure, false, $elements_count );

		if ( is_wp_error( $elements ) ) {
			return $elements; // Fail early — no post created yet.
		}

		// 1. Create the WordPress post only after structure is validated.
		$post_id = wp_insert_post(
			array(
				'post_title'  => $title,
				'post_status' => $status,
				'post_type'   => $post_type,
			),
			true
		);

		if ( is_wp_error( $post_id ) ) {
			return $post_id;
		}

		// Explicitly set protected meta keys.
		update_post_meta( $post_id, '_elementor_edit_mode', 'builder' );
		update_post_meta( $post_id, '_elementor_template_type', 'wp-' . $post_type );

		// 2. Save the element data (CSS regeneration handled inside save_page_data).
		$result = $this->data->save_page_data( $post_id, $elements );

		if ( is_wp_error( $result ) ) {
			wp_delete_post( $post_id, true );
			return $result;
		}

		// 3. Save page settings if provided.
		if ( ! empty( $page_settings ) ) {
			$settings_result = $this->data->save_page_settings( $post_id, $page_settings );
			if ( is_wp_error( $settings_result ) ) {
				wp_delete_post( $post_id, true );
				return $settings_result;
			}
		}

		$edit_url    = admin_url( 'post.php?post=' . $post_id . '&action=elementor' );
		$preview_url = get_permalink( $post_id );

		return array(
			'post_id'          => $post_id,
			'title'            => $title,
			'edit_url'         => $edit_url,
			'preview_url'      => $preview_url ? $preview_url : '',
			'elements_created' => $elements_count,
		);
	}

	// -------------------------------------------------------------------------
	// Helpers
	// -------------------------------------------------------------------------

	/**
	 * Recursively builds Elementor elements from the declarative structure.
	 *
	 * T1-6 FIX: Uses pass-by-reference counter instead of class property
	 * to avoid state pollution between calls.
	 *
	 * NOTE: Input uses "children" key; Elementor stores children under "elements".
	 * This is intentional — the input API uses "children" for clarity.
	 *
	 * @param array $items    The declarative structure items.
	 * @param bool  $is_inner Whether these are nested (inner) containers.
	 * @param int   $counter  Pass-by-reference counter for total elements created.
	 * @return array|\WP_Error The Elementor element tree.
	 */
	private function build_elements( array $items, bool $is_inner = false, int &$counter = 0 ) {
		$elements = array();

		foreach ( $items as $item ) {
			$type = $item['type'] ?? '';

			if ( 'container' === $type ) {
				$settings = $item['settings'] ?? array();
				$children = $item['children'] ?? array();

				// Recursively build children.
				$child_elements = $this->build_elements( $children, true, $counter );
				if ( is_wp_error( $child_elements ) ) {
					return $child_elements;
				}

				$container = $this->factory->create_container( $settings, $child_elements );

				if ( $is_inner ) {
					$container['isInner'] = true;
				}

				++$counter;
				$elements[] = $container;

			} elseif ( 'widget' === $type ) {
				$widget_type = sanitize_text_field( $item['widget_type'] ?? '' );
				$settings    = $item['settings'] ?? array();

				if ( empty( $widget_type ) ) {
					return new \WP_Error( 'missing_widget_type', __( 'Widget items require a widget_type value.', 'mindcrafts-ai' ) );
				}

				$widget_instance = \Elementor\Plugin::$instance->widgets_manager->get_widget_types( $widget_type );
				if ( ! $widget_instance ) {
					return new \WP_Error(
						'invalid_widget_type',
						sprintf(
							/* translators: %s: widget type */
							__( 'Widget type "%s" not found. Use list-widgets to see available types.', 'mindcrafts-ai' ),
							$widget_type
						)
					);
				}

				$widget = $this->factory->create_widget( $widget_type, $settings );
				++$counter;
				$elements[] = $widget;
			} else {
				return new \WP_Error( 'invalid_element_type', __( 'Each structure item must have type "container" or "widget".', 'mindcrafts-ai' ) );
			}
		}

		return $elements;
	}
}
