<?php
/**
 * Template MCP abilities for Elementor.
 *
 * Registers 2 tools for saving and applying Elementor templates.
 *
 * @package MindCrafts_AI
 * @since   1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Registers and implements the template abilities.
 *
 * @since 1.0.0
 */
class MindCrafts_AI_Template_Abilities {

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
			'mindcrafts-ai/save-as-template',
			'mindcrafts-ai/apply-template',
		);
	}

	/**
	 * Registers all template abilities.
	 *
	 * @since 1.0.0
	 */
	public function register(): void {
		$this->register_save_as_template();
		$this->register_apply_template();
	}

	/**
	 * Permission check for template operations.
	 *
	 * @since 1.0.0
	 *
	 * @param array|null $input The input data.
	 * @return bool
	 */
	public function check_edit_permission( $input = null ): bool {
		if ( ! current_user_can( 'edit_posts' ) ) {
			return false;
		}

		$post_id = absint( $input['post_id'] ?? 0 );
		if ( $post_id && ! current_user_can( 'edit_post', $post_id ) ) {
			return false;
		}

		return true;
	}

	// -------------------------------------------------------------------------
	// save-as-template
	// -------------------------------------------------------------------------

	private function register_save_as_template(): void {
		wp_register_ability(
			'mindcrafts-ai/save-as-template',
			array(
				'label'               => __( 'Save As Template', 'mindcrafts-ai' ),
				'description'         => __( 'Saves a page or a specific element as a reusable Elementor template.', 'mindcrafts-ai' ),
				'category'            => 'mindcrafts-ai',
				'execute_callback'    => array( $this, 'execute_save_as_template' ),
				'permission_callback' => array( $this, 'check_edit_permission' ),
				'input_schema'        => array(
					'type'       => 'object',
					'properties' => array(
						'post_id'       => array(
							'type'        => 'integer',
							'description' => __( 'The source post/page ID.', 'mindcrafts-ai' ),
						),
						'element_id'    => array(
							'type'        => 'string',
							'description' => __( 'Specific element ID to save. Omit to save the entire page.', 'mindcrafts-ai' ),
						),
						'title'         => array(
							'type'        => 'string',
							'description' => __( 'Template title.', 'mindcrafts-ai' ),
						),
						'template_type' => array(
							'type'        => 'string',
							'enum'        => array( 'page', 'section', 'container' ),
							'description' => __( 'Template type. Default: page.', 'mindcrafts-ai' ),
						),
					),
					'required'   => array( 'post_id', 'title' ),
				),
				'output_schema'       => array(
					'type'       => 'object',
					'properties' => array(
						'template_id' => array( 'type' => 'integer' ),
						'title'       => array( 'type' => 'string' ),
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
	 * Executes the save-as-template ability.
	 *
	 * @since 1.0.0
	 *
	 * @param array $input The input parameters.
	 * @return array|\WP_Error
	 */
	public function execute_save_as_template( $input ) {
		$post_id       = absint( $input['post_id'] ?? 0 );
		$element_id    = sanitize_text_field( $input['element_id'] ?? '' );
		$title         = sanitize_text_field( $input['title'] ?? '' );
		$template_type = sanitize_key( $input['template_type'] ?? 'page' );

		if ( ! $post_id || empty( $title ) ) {
			return new \WP_Error( 'missing_params', __( 'post_id and title are required.', 'mindcrafts-ai' ) );
		}

		$page_data = $this->data->get_page_data( $post_id );

		if ( is_wp_error( $page_data ) ) {
			return $page_data;
		}

		// Get the elements to save.
		if ( ! empty( $element_id ) ) {
			$element = $this->data->find_element_by_id( $page_data, $element_id );
			if ( null === $element ) {
				return new \WP_Error( 'element_not_found', __( 'Element not found.', 'mindcrafts-ai' ) );
			}
			$elements_data = array( $element );
		} else {
			$elements_data = $page_data;
		}

		// Create the template post in Elementor's library CPT.
		$template_id = wp_insert_post(
			array(
				'post_title'  => $title,
				'post_status' => 'publish',
				'post_type'   => 'elementor_library',
				'meta_input'  => array(
					'_elementor_edit_mode'     => 'builder',
					'_elementor_template_type' => $template_type,
				),
			),
			true
		);

		if ( is_wp_error( $template_id ) ) {
			return $template_id;
		}

		// Set the template type taxonomy.
		wp_set_object_terms( $template_id, $template_type, 'elementor_library_type' );

		// Save the element data to the template.
		$save_result = $this->data->save_page_data( $template_id, $elements_data );

		if ( is_wp_error( $save_result ) ) {
			return $save_result;
		}

		return array(
			'template_id' => $template_id,
			'title'       => $title,
		);
	}

	// -------------------------------------------------------------------------
	// apply-template
	// -------------------------------------------------------------------------

	private function register_apply_template(): void {
		wp_register_ability(
			'mindcrafts-ai/apply-template',
			array(
				'label'               => __( 'Apply Template', 'mindcrafts-ai' ),
				'description'         => __( 'Applies a saved Elementor template to a page at a given position, inserting its elements with fresh IDs.', 'mindcrafts-ai' ),
				'category'            => 'mindcrafts-ai',
				'execute_callback'    => array( $this, 'execute_apply_template' ),
				'permission_callback' => array( $this, 'check_edit_permission' ),
				'input_schema'        => array(
					'type'       => 'object',
					'properties' => array(
						'post_id'     => array(
							'type'        => 'integer',
							'description' => __( 'The target post/page ID.', 'mindcrafts-ai' ),
						),
						'template_id' => array(
							'type'        => 'integer',
							'description' => __( 'The template post ID to apply.', 'mindcrafts-ai' ),
						),
						'parent_id'   => array(
							'type'        => 'string',
							'description' => __( 'Parent container ID. Empty for top-level.', 'mindcrafts-ai' ),
						),
						'position'    => array(
							'type'        => 'integer',
							'description' => __( 'Insert position. -1 = append.', 'mindcrafts-ai' ),
						),
					),
					'required'   => array( 'post_id', 'template_id' ),
				),
				'output_schema'       => array(
					'type'       => 'object',
					'properties' => array(
						'success'        => array( 'type' => 'boolean' ),
						'elements_added' => array( 'type' => 'integer' ),
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
	 * Executes the apply-template ability.
	 *
	 * @since 1.0.0
	 *
	 * @param array $input The input parameters.
	 * @return array|\WP_Error
	 */
	public function execute_apply_template( $input ) {
		$post_id     = absint( $input['post_id'] ?? 0 );
		$template_id = absint( $input['template_id'] ?? 0 );
		$parent_id   = sanitize_text_field( $input['parent_id'] ?? '' );
		$position    = intval( $input['position'] ?? -1 );

		if ( ! $post_id || ! $template_id ) {
			return new \WP_Error( 'missing_params', __( 'post_id and template_id are required.', 'mindcrafts-ai' ) );
		}

		// Get the template elements.
		$template_data = $this->data->get_page_data( $template_id );

		if ( is_wp_error( $template_data ) ) {
			return $template_data;
		}

		if ( empty( $template_data ) ) {
			return new \WP_Error( 'empty_template', __( 'Template has no elements.', 'mindcrafts-ai' ) );
		}

		// Get the target page data.
		$page_data = $this->data->get_page_data( $post_id );

		if ( is_wp_error( $page_data ) ) {
			return $page_data;
		}

		// Reassign IDs to prevent collisions.
		$template_data = $this->data->reassign_ids( $template_data );
		$count         = $this->data->count_elements( $template_data );

		// Insert template elements.
		if ( ! empty( $parent_id ) ) {
			// Insert each template element into the parent.
			foreach ( $template_data as $i => $element ) {
				$pos      = ( $position >= 0 ) ? $position + $i : -1;
				$inserted = $this->data->insert_element( $page_data, $parent_id, $element, $pos );

				if ( ! $inserted ) {
					return new \WP_Error(
						'parent_not_found',
						sprintf(
							/* translators: %s: parent element ID */
							__( 'Parent element "%s" not found.', 'mindcrafts-ai' ),
							$parent_id
						)
					);
				}
			}
		} else {
			// Top-level insertion.
			if ( $position < 0 || $position >= count( $page_data ) ) {
				$page_data = array_merge( $page_data, $template_data );
			} else {
				array_splice( $page_data, $position, 0, $template_data );
			}
		}

		$result = $this->data->save_page_data( $post_id, $page_data );

		if ( is_wp_error( $result ) ) {
			return $result;
		}

		return array(
			'success'        => true,
			'elements_added' => $count,
		);
	}

}
