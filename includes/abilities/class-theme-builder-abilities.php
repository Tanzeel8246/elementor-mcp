<?php
/**
 * Theme Builder abilities for MindCrafts AI.
 *
 * Provides MCP tools for creating, listing, and configuring Elementor Pro
 * Theme Builder templates (headers, footers, singles, archives, 404s, loop items).
 *
 * @package MindCrafts_AI
 * @since   2.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Manages Elementor Pro Theme Builder templates and conditions.
 *
 * @since 2.0.0
 */
class MindCrafts_AI_Theme_Builder_Abilities {

	/**
	 * Data access layer.
	 *
	 * @var MindCrafts_AI_Data
	 */
	private MindCrafts_AI_Data $data;

	/**
	 * Element factory.
	 *
	 * @var MindCrafts_AI_Element_Factory
	 */
	private MindCrafts_AI_Element_Factory $factory;

	/**
	 * Constructor.
	 *
	 * @since 2.0.0
	 *
	 * @param MindCrafts_AI_Data                 $data    The data access layer.
	 * @param MindCrafts_AI_Element_Factory|null $factory Optional element factory.
	 */
	public function __construct( MindCrafts_AI_Data $data, ?MindCrafts_AI_Element_Factory $factory = null ) {
		$this->data    = $data;
		$this->factory = $factory ? $factory : new MindCrafts_AI_Element_Factory();
	}

	/**
	 * Returns the ability names registered by this class.
	 *
	 * @since 2.0.0
	 *
	 * @return string[]
	 */
	public function get_ability_names(): array {
		return array(
			'mindcrafts-ai/list-theme-templates',
			'mindcrafts-ai/create-theme-template',
			'mindcrafts-ai/set-template-conditions',
			'mindcrafts-ai/apply-theme-location',
		);
	}

	/**
	 * Registers Theme Builder abilities.
	 *
	 * @since 2.0.0
	 */
	public function register(): void {
		wp_register_ability(
			'mindcrafts-ai/list-theme-templates',
			array(
				'label'               => __( 'List Theme Builder Templates', 'mindcrafts-ai' ),
				'description'         => __( 'Lists all Elementor Theme Builder templates (headers, footers, singles, archives, search, 404, loop-items) with their conditions.', 'mindcrafts-ai' ),
				'category'            => 'mindcrafts-ai',
				'execute_callback'    => array( $this, 'execute_list_theme_templates' ),
				'permission_callback' => array( $this, 'check_manage_permission' ),
				'input_schema'        => array(
					'type'       => 'object',
					'properties' => array(),
				),
				'output_schema'       => array(
					'type'       => 'object',
					'properties' => array(
						'templates' => array(
							'type'  => 'array',
							'items' => array(
								'type'       => 'object',
								'properties' => array(
									'id'               => array( 'type' => 'integer' ),
									'title'            => array( 'type' => 'string' ),
									'type'             => array( 'type' => 'string' ),
									'conditions_count' => array( 'type' => 'integer' ),
									'conditions'       => array( 'type' => 'array' ),
									'edit_url'         => array( 'type' => 'string' ),
								),
							),
						),
					),
				),
			)
		);

		wp_register_ability(
			'mindcrafts-ai/create-theme-template',
			array(
				'label'               => __( 'Create Theme Builder Template', 'mindcrafts-ai' ),
				'description'         => __( 'Creates a new Theme Builder template (header, footer, single, archive, search, error-404, loop-item) with optional structure.', 'mindcrafts-ai' ),
				'category'            => 'mindcrafts-ai',
				'execute_callback'    => array( $this, 'execute_create_theme_template' ),
				'permission_callback' => array( $this, 'check_manage_permission' ),
				'input_schema'        => array(
					'type'       => 'object',
					'properties' => array(
						'title'     => array(
							'type'        => 'string',
							'description' => __( 'Template title.', 'mindcrafts-ai' ),
						),
						'type'      => array(
							'type'        => 'string',
							'enum'        => array( 'header', 'footer', 'single', 'archive', 'search', 'error-404', 'loop-item' ),
							'description' => __( 'Theme template type.', 'mindcrafts-ai' ),
						),
						'structure' => array(
							'type'        => 'array',
							'description' => __( 'Optional element tree to populate the template.', 'mindcrafts-ai' ),
						),
					),
					'required'   => array( 'title', 'type' ),
				),
				'output_schema'       => array(
					'type'       => 'object',
					'properties' => array(
						'success'     => array( 'type' => 'boolean' ),
						'template_id' => array( 'type' => 'integer' ),
						'edit_url'    => array( 'type' => 'string' ),
					),
				),
			)
		);

		wp_register_ability(
			'mindcrafts-ai/set-template-conditions',
			array(
				'label'               => __( 'Set Template Display Conditions', 'mindcrafts-ai' ),
				'description'         => __( 'Sets display conditions for an Elementor Pro Theme Builder template.', 'mindcrafts-ai' ),
				'category'            => 'mindcrafts-ai',
				'execute_callback'    => array( $this, 'execute_set_template_conditions' ),
				'permission_callback' => array( $this, 'check_manage_permission' ),
				'input_schema'        => array(
					'type'       => 'object',
					'properties' => array(
						'template_id' => array(
							'type'        => 'integer',
							'description' => __( 'The template ID.', 'mindcrafts-ai' ),
						),
						'conditions'  => array(
							'type'        => 'array',
							'description' => __( 'Array of condition rules or strings (e.g. ["include/general"]).', 'mindcrafts-ai' ),
						),
					),
					'required'   => array( 'template_id', 'conditions' ),
				),
				'output_schema'       => array(
					'type'       => 'object',
					'properties' => array(
						'success'     => array( 'type' => 'boolean' ),
						'template_id' => array( 'type' => 'integer' ),
					),
				),
			)
		);

		wp_register_ability(
			'mindcrafts-ai/apply-theme-location',
			array(
				'label'               => __( 'Apply Theme Location', 'mindcrafts-ai' ),
				'description'         => __( 'Applies a theme template to an entire site location (e.g., header or footer for all pages).', 'mindcrafts-ai' ),
				'category'            => 'mindcrafts-ai',
				'execute_callback'    => array( $this, 'execute_apply_theme_location' ),
				'permission_callback' => array( $this, 'check_manage_permission' ),
				'input_schema'        => array(
					'type'       => 'object',
					'properties' => array(
						'template_id'    => array(
							'type'        => 'integer',
							'description' => __( 'The template ID.', 'mindcrafts-ai' ),
						),
						'location'       => array(
							'type'        => 'string',
							'enum'        => array( 'header', 'footer' ),
							'description' => __( 'Theme location.', 'mindcrafts-ai' ),
						),
						'condition_type' => array(
							'type'        => 'string',
							'enum'        => array( 'general', 'singular' ),
							'description' => __( 'Condition scope.', 'mindcrafts-ai' ),
						),
					),
					'required'   => array( 'template_id', 'location' ),
				),
				'output_schema'       => array(
					'type'       => 'object',
					'properties' => array(
						'success'     => array( 'type' => 'boolean' ),
						'template_id' => array( 'type' => 'integer' ),
						'location'    => array( 'type' => 'string' ),
					),
				),
			)
		);
	}

	/**
	 * Permission check for Theme Builder management.
	 *
	 * @since 2.0.0
	 *
	 * @return bool
	 */
	public function check_manage_permission(): bool {
		return current_user_can( 'manage_options' );
	}

	/**
	 * Lists Theme Builder templates.
	 *
	 * @since 2.0.0
	 *
	 * @param mixed $input Optional input.
	 * @return array{templates: array<int, array<string, mixed>>}
	 */
	public function execute_list_theme_templates( $input = null ): array {
		$theme_types = array( 'header', 'footer', 'single', 'archive', 'search', 'error-404', 'loop-item', 'single-post', 'single-page', 'product', 'product-archive' );

		$posts = get_posts(
			array(
				'post_type'      => 'elementor_library',
				'post_status'    => 'any',
				'posts_per_page' => 100,
			)
		);

		$templates = array();

		foreach ( $posts as $post ) {
			$type = get_post_meta( $post->ID, '_elementor_template_type', true );
			if ( in_array( $type, $theme_types, true ) ) {
				$conditions = get_post_meta( $post->ID, '_elementor_conditions', true );
				if ( ! is_array( $conditions ) ) {
					$conditions = array();
				}

				$templates[] = array(
					'id'               => $post->ID,
					'title'            => $post->post_title,
					'type'             => $type,
					'conditions_count' => count( $conditions ),
					'conditions'       => $conditions,
					'edit_url'         => admin_url( 'post.php?post=' . $post->ID . '&action=elementor' ),
				);
			}
		}

		return array( 'templates' => $templates );
	}

	/**
	 * Creates a new Theme Builder template.
	 *
	 * @since 2.0.0
	 *
	 * @param array $input Input parameters.
	 * @return array{success: bool, template_id: int, edit_url: string}|\WP_Error
	 */
	public function execute_create_theme_template( $input ) {
		$title     = sanitize_text_field( $input['title'] ?? '' );
		$type      = sanitize_text_field( $input['type'] ?? '' );
		$structure = isset( $input['structure'] ) && is_array( $input['structure'] ) ? $input['structure'] : array();

		if ( empty( $title ) || empty( $type ) ) {
			return new \WP_Error( 'missing_params', __( 'title and type are required.', 'mindcrafts-ai' ) );
		}

		$post_id = wp_insert_post(
			array(
				'post_type'   => 'elementor_library',
				'post_title'  => $title,
				'post_status' => 'publish',
			),
			true
		);

		if ( is_wp_error( $post_id ) ) {
			return $post_id;
		}

		update_post_meta( $post_id, '_elementor_template_type', $type );
		update_post_meta( $post_id, '_elementor_edit_mode', 'builder' );

		if ( empty( $structure ) ) {
			$container = $this->factory->create_container(
				array(
					'flex_direction' => 'row',
					'padding'        => array( 'unit' => 'px', 'top' => '20', 'right' => '20', 'bottom' => '20', 'left' => '20' ),
				)
			);
			$structure = array( $container );
		}

		$saved = $this->data->save_page_data( $post_id, $structure );
		if ( is_wp_error( $saved ) ) {
			return $saved;
		}

		return array(
			'success'     => true,
			'template_id' => $post_id,
			'edit_url'    => admin_url( 'post.php?post=' . $post_id . '&action=elementor' ),
		);
	}

	/**
	 * Sets display conditions for a Theme Builder template.
	 *
	 * @since 2.0.0
	 *
	 * @param array $input Input parameters.
	 * @return array{success: bool, template_id: int}|\WP_Error
	 */
	public function execute_set_template_conditions( $input ) {
		if ( ! defined( 'ELEMENTOR_PRO_VERSION' ) ) {
			return new \WP_Error(
				'elementor_pro_required',
				__( 'Setting template conditions requires Elementor Pro.', 'mindcrafts-ai' )
			);
		}

		$template_id = absint( $input['template_id'] ?? 0 );
		$conditions  = isset( $input['conditions'] ) && is_array( $input['conditions'] ) ? $input['conditions'] : array();

		if ( ! $template_id ) {
			return new \WP_Error( 'missing_template_id', __( 'template_id is required.', 'mindcrafts-ai' ) );
		}

		$post = get_post( $template_id );
		if ( ! $post || 'elementor_library' !== $post->post_type ) {
			return new \WP_Error( 'invalid_template', __( 'Invalid template post ID.', 'mindcrafts-ai' ) );
		}

		update_post_meta( $template_id, '_elementor_conditions', $conditions );

		return array(
			'success'     => true,
			'template_id' => $template_id,
		);
	}

	/**
	 * Applies a template to a site-wide location (header/footer).
	 *
	 * @since 2.0.0
	 *
	 * @param array $input Input parameters.
	 * @return array{success: bool, template_id: int, location: string}|\WP_Error
	 */
	public function execute_apply_theme_location( $input ) {
		$template_id    = absint( $input['template_id'] ?? 0 );
		$location       = sanitize_text_field( $input['location'] ?? '' );
		$condition_type = sanitize_text_field( $input['condition_type'] ?? 'general' );

		if ( ! $template_id || empty( $location ) ) {
			return new \WP_Error( 'missing_params', __( 'template_id and location are required.', 'mindcrafts-ai' ) );
		}

		$post = get_post( $template_id );
		if ( ! $post || 'elementor_library' !== $post->post_type ) {
			return new \WP_Error( 'invalid_template', __( 'Invalid template post ID.', 'mindcrafts-ai' ) );
		}

		update_post_meta( $template_id, '_elementor_template_type', $location );

		$condition_slug = ( 'singular' === $condition_type ) ? 'include/singular' : 'include/general';
		update_post_meta( $template_id, '_elementor_conditions', array( $condition_slug ) );

		return array(
			'success'     => true,
			'template_id' => $template_id,
			'location'    => $location,
		);
	}
}
