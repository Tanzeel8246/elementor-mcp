<?php
/**
 * Page CRUD MCP abilities for Elementor.
 *
 * Registers 5 tools for creating, updating, clearing, importing,
 * and exporting Elementor pages.
 *
 * @package MindCrafts_AI
 * @since   1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Registers and implements the page CRUD abilities.
 *
 * @since 1.0.0
 */
class MindCrafts_AI_Page_Abilities {

	/**
	 * @var Elementor_MCP_Data
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
	 * @param Elementor_MCP_Data            $data    The data access layer.
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
			'mindcrafts-ai/create-page',
			'mindcrafts-ai/update-page-settings',
			'mindcrafts-ai/delete-page-content',
			'mindcrafts-ai/import-template',
			'mindcrafts-ai/export-page',
			'mindcrafts-ai/list-snapshots',
			'mindcrafts-ai/restore-snapshot',
		);
	}

	/**
	 * Registers all page abilities.
	 *
	 * @since 1.0.0
	 */
	public function register(): void {
		$this->register_create_page();
		$this->register_update_page_settings();
		$this->register_delete_page_content();
		$this->register_import_template();
		$this->register_export_page();
		$this->register_list_snapshots();
		$this->register_restore_snapshot();
	}

	/**
	 * Permission check for page creation.
	 *
	 * @since 1.0.0
	 *
	 * @return bool
	 */
	public function check_create_permission(): bool {
		return current_user_can( 'publish_pages' ) || current_user_can( 'edit_pages' );
	}

	/**
	 * Permission check for page editing.
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

	/**
	 * Permission check for destructive page content deletion.
	 *
	 * Requires both edit and delete capabilities since this operation
	 * is destructive and removes all Elementor content from the page.
	 *
	 * @since 1.0.0
	 *
	 * @param array|null $input The input data.
	 * @return bool
	 */
	public function check_delete_permission( $input = null ): bool {
		if ( ! current_user_can( 'edit_posts' ) || ! current_user_can( 'delete_posts' ) ) {
			return false;
		}

		$post_id = absint( $input['post_id'] ?? 0 );
		if ( $post_id ) {
			if ( ! current_user_can( 'edit_post', $post_id ) || ! current_user_can( 'delete_post', $post_id ) ) {
				return false;
			}
		}

		return true;
	}

	// -------------------------------------------------------------------------
	// create-page
	// -------------------------------------------------------------------------

	private function register_create_page(): void {
		wp_register_ability(
			'mindcrafts-ai/create-page',
			array(
				'label'               => __( 'Create Elementor Page', 'mindcrafts-ai' ),
				'description'         => __( 'Creates a new WordPress page with Elementor enabled. Optionally provide initial element content.', 'mindcrafts-ai' ),
				'category'            => 'mindcrafts-ai',
				'execute_callback'    => array( $this, 'execute_create_page' ),
				'permission_callback' => array( $this, 'check_create_permission' ),
				'input_schema'        => array(
					'type'       => 'object',
					'properties' => array(
						'title'     => array(
							'type'        => 'string',
							'description' => __( 'Page title.', 'mindcrafts-ai' ),
						),
						'status'    => array(
							'type'        => 'string',
							'enum'        => array( 'draft', 'publish' ),
							'description' => __( 'Post status. Default: draft.', 'mindcrafts-ai' ),
						),
						'post_type' => array(
							'type'        => 'string',
							'enum'        => array( 'page', 'post' ),
							'description' => __( 'Post type. Default: page.', 'mindcrafts-ai' ),
						),
						'template'  => array(
							'type'        => 'string',
							'description' => __( 'Elementor template slug.', 'mindcrafts-ai' ),
						),
						'content'   => array(
							'type'        => 'array',
							'description' => __( 'Initial element tree.', 'mindcrafts-ai' ),
						),
					),
					'required'   => array( 'title' ),
				),
				'output_schema'       => array(
					'type'       => 'object',
					'properties' => array(
						'post_id'     => array( 'type' => 'integer' ),
						'title'       => array( 'type' => 'string' ),
						'edit_url'    => array( 'type' => 'string' ),
						'preview_url' => array( 'type' => 'string' ),
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
	 * Executes the create-page ability.
	 *
	 * @since 1.0.0
	 *
	 * @param array $input The input parameters.
	 * @return array|\WP_Error
	 */
	public function execute_create_page( $input ) {
		$title     = sanitize_text_field( $input['title'] ?? '' );
		$status    = sanitize_key( $input['status'] ?? 'draft' );
		$post_type = sanitize_key( $input['post_type'] ?? 'page' );

		if ( empty( $title ) ) {
			return new \WP_Error( 'missing_title', __( 'The title parameter is required.', 'mindcrafts-ai' ) );
		}

		$post_id = wp_insert_post(
			array(
				'post_title'  => $title,
				'post_status' => $status,
				'post_type'   => $post_type,
				'meta_input'  => array(
					'_elementor_edit_mode'     => 'builder',
					'_elementor_template_type' => 'wp-' . $post_type,
				),
			),
			true
		);

		if ( is_wp_error( $post_id ) ) {
			return $post_id;
		}

		// Set page template if provided.
		if ( ! empty( $input['template'] ) ) {
			update_post_meta( $post_id, '_wp_page_template', sanitize_text_field( $input['template'] ) );
		}

		// Save initial content if provided.
		if ( ! empty( $input['content'] ) && is_array( $input['content'] ) ) {
			$save_result = $this->data->save_page_data( $post_id, $input['content'] );
		} else {
			// Save empty Elementor data to initialize.
			$save_result = $this->data->save_page_data( $post_id, array() );
		}

		if ( is_wp_error( $save_result ) ) {
			return $save_result;
		}

		$edit_url    = admin_url( 'post.php?post=' . $post_id . '&action=elementor' );
		$preview_url = get_permalink( $post_id );

		return array(
			'post_id'     => $post_id,
			'title'       => $title,
			'edit_url'    => $edit_url,
			'preview_url' => $preview_url ? $preview_url : '',
		);
	}

	// -------------------------------------------------------------------------
	// update-page-settings
	// -------------------------------------------------------------------------

	private function register_update_page_settings(): void {
		wp_register_ability(
			'mindcrafts-ai/update-page-settings',
			array(
				'label'               => __( 'Update Page Settings', 'mindcrafts-ai' ),
				'description'         => __( 'Updates page-level Elementor settings such as background, padding, custom CSS, and layout options.', 'mindcrafts-ai' ),
				'category'            => 'mindcrafts-ai',
				'execute_callback'    => array( $this, 'execute_update_page_settings' ),
				'permission_callback' => array( $this, 'check_edit_permission' ),
				'input_schema'        => array(
					'type'       => 'object',
					'properties' => array(
						'post_id'  => array(
							'type'        => 'integer',
							'description' => __( 'The post/page ID.', 'mindcrafts-ai' ),
						),
						'settings' => array(
							'type'        => 'object',
							'description' => __( 'Page settings object.', 'mindcrafts-ai' ),
						),
					),
					'required'   => array( 'post_id', 'settings' ),
				),
				'output_schema'       => array(
					'type'       => 'object',
					'properties' => array(
						'success' => array( 'type' => 'boolean' ),
						'post_id' => array( 'type' => 'integer' ),
					),
				),
				'meta'                => array(
					'annotations'  => array(
						'readonly'    => false,
						'destructive' => false,
						'idempotent'  => true,
					),
					'show_in_rest' => true,
				),
			)
		);
	}

	public function execute_update_page_settings( $input ) {
		$post_id  = absint( $input['post_id'] ?? 0 );
		$settings = $input['settings'] ?? array();

		if ( ! $post_id ) {
			return new \WP_Error( 'missing_post_id', __( 'The post_id parameter is required.', 'mindcrafts-ai' ) );
		}

		$result = $this->data->save_page_settings( $post_id, $settings );

		if ( is_wp_error( $result ) ) {
			return $result;
		}

		return array(
			'success' => true,
			'post_id' => $post_id,
		);
	}

	// -------------------------------------------------------------------------
	// delete-page-content
	// -------------------------------------------------------------------------

	private function register_delete_page_content(): void {
		wp_register_ability(
			'mindcrafts-ai/delete-page-content',
			array(
				'label'               => __( 'Delete Page Content', 'mindcrafts-ai' ),
				'description'         => __( 'Clears all Elementor content from a page, resetting it to blank while keeping the page itself.', 'mindcrafts-ai' ),
				'category'            => 'mindcrafts-ai',
				'execute_callback'    => array( $this, 'execute_delete_page_content' ),
				'permission_callback' => array( $this, 'check_delete_permission' ),
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
				'output_schema'       => array(
					'type'       => 'object',
					'properties' => array(
						'success' => array( 'type' => 'boolean' ),
					),
				),
				'meta'                => array(
					'annotations'  => array(
						'readonly'    => false,
						'destructive' => true,
						'idempotent'  => true,
					),
					'show_in_rest' => true,
				),
			)
		);
	}

	public function execute_delete_page_content( $input ) {
		$post_id = absint( $input['post_id'] ?? 0 );

		if ( ! $post_id ) {
			return new \WP_Error( 'missing_post_id', __( 'The post_id parameter is required.', 'mindcrafts-ai' ) );
		}

		// T3-4: Save history snapshot before destructive content clearing
		$this->data->save_snapshot( $post_id, __( 'Before clearing all page content', 'mindcrafts-ai' ) );

		$result = $this->data->save_page_data( $post_id, array() );

		if ( is_wp_error( $result ) ) {
			return $result;
		}

		return array( 'success' => true );
	}

	// -------------------------------------------------------------------------
	// import-template
	// -------------------------------------------------------------------------

	private function register_import_template(): void {
		wp_register_ability(
			'mindcrafts-ai/import-template',
			array(
				'label'               => __( 'Import Template', 'mindcrafts-ai' ),
				'description'         => __( 'Imports a JSON template structure into a page at an optional position.', 'mindcrafts-ai' ),
				'category'            => 'mindcrafts-ai',
				'execute_callback'    => array( $this, 'execute_import_template' ),
				'permission_callback' => array( $this, 'check_edit_permission' ),
				'input_schema'        => array(
					'type'       => 'object',
					'properties' => array(
						'post_id'       => array(
							'type'        => 'integer',
							'description' => __( 'The post/page ID.', 'mindcrafts-ai' ),
						),
						'template_json' => array(
							'type'        => 'array',
							'description' => __( 'Elementor JSON element structure to import.', 'mindcrafts-ai' ),
						),
						'position'      => array(
							'type'        => 'integer',
							'description' => __( 'Insert position. -1 = append.', 'mindcrafts-ai' ),
						),
					),
					'required'   => array( 'post_id', 'template_json' ),
				),
				'output_schema'       => array(
					'type'       => 'object',
					'properties' => array(
						'success'        => array( 'type' => 'boolean' ),
						'elements_count' => array( 'type' => 'integer' ),
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

	public function execute_import_template( $input ) {
		$post_id       = absint( $input['post_id'] ?? 0 );
		$template_json = $input['template_json'] ?? array();
		$position      = intval( $input['position'] ?? -1 );

		if ( ! $post_id ) {
			return new \WP_Error( 'missing_post_id', __( 'The post_id parameter is required.', 'mindcrafts-ai' ) );
		}

		if ( empty( $template_json ) ) {
			return new \WP_Error( 'missing_template', __( 'The template_json parameter is required.', 'mindcrafts-ai' ) );
		}

		$data = $this->data->get_page_data( $post_id );

		if ( is_wp_error( $data ) ) {
			return $data;
		}

		// Assign new IDs to all imported elements.
		$template_json = $this->data->reassign_ids( $template_json );
		$count         = $this->data->count_elements( $template_json );

		// Insert at position.
		if ( $position < 0 || $position >= count( $data ) ) {
			$data = array_merge( $data, $template_json );
		} else {
			array_splice( $data, $position, 0, $template_json );
		}

		$result = $this->data->save_page_data( $post_id, $data );

		if ( is_wp_error( $result ) ) {
			return $result;
		}

		return array(
			'success'        => true,
			'elements_count' => $count,
		);
	}

	// -------------------------------------------------------------------------
	// export-page
	// -------------------------------------------------------------------------

	private function register_export_page(): void {
		wp_register_ability(
			'mindcrafts-ai/export-page',
			array(
				'label'               => __( 'Export Page', 'mindcrafts-ai' ),
				'description'         => __( 'Exports a page\'s full Elementor data as a JSON structure that can be imported elsewhere.', 'mindcrafts-ai' ),
				'category'            => 'mindcrafts-ai',
				'execute_callback'    => array( $this, 'execute_export_page' ),
				'permission_callback' => array( $this, 'check_edit_permission' ),
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
				'output_schema'       => array(
					'type'       => 'object',
					'properties' => array(
						'json' => array( 'type' => 'array' ),
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

	public function execute_export_page( $input ) {
		$post_id = absint( $input['post_id'] ?? 0 );

		if ( ! $post_id ) {
			return new \WP_Error( 'missing_post_id', __( 'The post_id parameter is required.', 'mindcrafts-ai' ) );
		}

		$data = $this->data->get_page_data( $post_id );

		if ( is_wp_error( $data ) ) {
			return $data;
		}

		return array( 'json' => $data );
	}

	private function register_list_snapshots(): void {
		wp_register_ability(
			'mindcrafts-ai/list-snapshots',
			array(
				'label'               => __( 'List Page Snapshots', 'mindcrafts-ai' ),
				'description'         => __( 'Lists all available history snapshots for a page.', 'mindcrafts-ai' ),
				'category'            => 'mindcrafts-ai',
				'execute_callback'    => array( $this, 'execute_list_snapshots' ),
				'permission_callback' => array( $this, 'check_edit_permission' ),
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
				'output_schema'       => array(
					'type'       => 'object',
					'properties' => array(
						'snapshots' => array(
							'type'  => 'array',
							'items' => array(
								'type'       => 'object',
								'properties' => array(
									'index'       => array( 'type' => 'integer' ),
									'timestamp'   => array( 'type' => 'integer' ),
									'date'        => array( 'type' => 'string' ),
									'description' => array( 'type' => 'string' ),
								),
							),
						),
					),
				),
			)
		);
	}

	public function execute_list_snapshots( $input ) {
		$post_id = absint( $input['post_id'] ?? 0 );

		if ( ! $post_id ) {
			return new \WP_Error( 'missing_post_id', __( 'The post_id parameter is required.', 'mindcrafts-ai' ) );
		}

		$snapshots = $this->data->list_snapshots( $post_id );

		return array( 'snapshots' => $snapshots );
	}

	private function register_restore_snapshot(): void {
		wp_register_ability(
			'mindcrafts-ai/restore-snapshot',
			array(
				'label'               => __( 'Restore Page Snapshot', 'mindcrafts-ai' ),
				'description'         => __( 'Restores page content to a previously saved snapshot state.', 'mindcrafts-ai' ),
				'category'            => 'mindcrafts-ai',
				'execute_callback'    => array( $this, 'execute_restore_snapshot' ),
				'permission_callback' => array( $this, 'check_edit_permission' ),
				'input_schema'        => array(
					'type'       => 'object',
					'properties' => array(
						'post_id' => array(
							'type'        => 'integer',
							'description' => __( 'The post/page ID.', 'mindcrafts-ai' ),
						),
						'index'   => array(
							'type'        => 'integer',
							'description' => __( 'The snapshot index to restore.', 'mindcrafts-ai' ),
						),
					),
					'required'   => array( 'post_id', 'index' ),
				),
				'output_schema'       => array(
					'type'       => 'object',
					'properties' => array(
						'success' => array( 'type' => 'boolean' ),
					),
				),
			)
		);
	}

	public function execute_restore_snapshot( $input ) {
		$post_id = absint( $input['post_id'] ?? 0 );
		$index   = intval( $input['index'] ?? -1 );

		if ( ! $post_id || $index < 0 ) {
			return new \WP_Error( 'missing_params', __( 'post_id and a valid index are required.', 'mindcrafts-ai' ) );
		}

		$result = $this->data->restore_snapshot( $post_id, $index );

		if ( is_wp_error( $result ) ) {
			return $result;
		}

		return array( 'success' => true );
	}

}
