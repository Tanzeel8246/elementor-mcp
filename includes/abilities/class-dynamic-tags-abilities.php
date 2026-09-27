<?php
/**
 * Dynamic Tags abilities for MindCrafts AI.
 *
 * Provides MCP tools for listing available Elementor Dynamic Tags and binding
 * them to widget/container settings.
 *
 * @package MindCrafts_AI
 * @since   2.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Manages Elementor dynamic tag inspection and application.
 *
 * @since 2.0.0
 */
class MindCrafts_AI_Dynamic_Tags_Abilities {

	/**
	 * Data access layer.
	 *
	 * @var MindCrafts_AI_Data
	 */
	private MindCrafts_AI_Data $data;

	/**
	 * Constructor.
	 *
	 * @since 2.0.0
	 *
	 * @param MindCrafts_AI_Data $data The data access layer.
	 */
	public function __construct( MindCrafts_AI_Data $data ) {
		$this->data = $data;
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
			'mindcrafts-ai/list-dynamic-tags',
			'mindcrafts-ai/apply-dynamic-tag',
		);
	}

	/**
	 * Registers dynamic tags abilities.
	 *
	 * @since 2.0.0
	 */
	public function register(): void {
		wp_register_ability(
			'mindcrafts-ai/list-dynamic-tags',
			array(
				'label'               => __( 'List Dynamic Tags', 'mindcrafts-ai' ),
				'description'         => __( 'Lists all available Elementor dynamic tags (post title, author, custom field, ACF, WooCommerce, etc.).', 'mindcrafts-ai' ),
				'category'            => 'mindcrafts-ai',
				'execute_callback'    => array( $this, 'execute_list_dynamic_tags' ),
				'permission_callback' => array( $this, 'check_permission' ),
				'input_schema'        => array(
					'type'       => 'object',
					'properties' => array(),
				),
				'output_schema'       => array(
					'type'       => 'object',
					'properties' => array(
						'dynamic_tags' => array(
							'type'  => 'array',
							'items' => array(
								'type'       => 'object',
								'properties' => array(
									'name'       => array( 'type' => 'string' ),
									'title'      => array( 'type' => 'string' ),
									'categories' => array(
										'type'  => 'array',
										'items' => array( 'type' => 'string' ),
									),
									'group'      => array( 'type' => 'string' ),
								),
							),
						),
					),
				),
			)
		);

		wp_register_ability(
			'mindcrafts-ai/apply-dynamic-tag',
			array(
				'label'               => __( 'Apply Dynamic Tag', 'mindcrafts-ai' ),
				'description'         => __( 'Binds an Elementor dynamic tag to a specific widget or container setting.', 'mindcrafts-ai' ),
				'category'            => 'mindcrafts-ai',
				'execute_callback'    => array( $this, 'execute_apply_dynamic_tag' ),
				'permission_callback' => array( $this, 'check_permission' ),
				'input_schema'        => array(
					'type'       => 'object',
					'properties' => array(
						'post_id'      => array(
							'type'        => 'integer',
							'description' => __( 'The post/page ID.', 'mindcrafts-ai' ),
						),
						'element_id'   => array(
							'type'        => 'string',
							'description' => __( 'The element ID.', 'mindcrafts-ai' ),
						),
						'setting_key'  => array(
							'type'        => 'string',
							'description' => __( 'The setting key to bind (e.g. title, description, link).', 'mindcrafts-ai' ),
						),
						'tag_name'     => array(
							'type'        => 'string',
							'description' => __( 'The dynamic tag name (e.g. post-title, post-custom-field).', 'mindcrafts-ai' ),
						),
						'tag_settings' => array(
							'type'        => 'object',
							'description' => __( 'Optional settings for the dynamic tag.', 'mindcrafts-ai' ),
						),
					),
					'required'   => array( 'post_id', 'element_id', 'setting_key', 'tag_name' ),
				),
				'output_schema'       => array(
					'type'       => 'object',
					'properties' => array(
						'success'     => array( 'type' => 'boolean' ),
						'element_id'  => array( 'type' => 'string' ),
						'setting_key' => array( 'type' => 'string' ),
					),
				),
			)
		);
	}

	/**
	 * Permission check.
	 *
	 * @since 2.0.0
	 *
	 * @param mixed $input Optional input.
	 * @return bool
	 */
	public function check_permission( $input = null ): bool {
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
	 * Lists available dynamic tags.
	 *
	 * @since 2.0.0
	 *
	 * @param mixed $input Optional input.
	 * @return array{dynamic_tags: array<int, array<string, mixed>>}
	 */
	public function execute_list_dynamic_tags( $input = null ): array {
		if ( ! class_exists( '\Elementor\Plugin' ) || empty( \Elementor\Plugin::$instance->dynamic_tags ) ) {
			return array( 'dynamic_tags' => array() );
		}

		$config = \Elementor\Plugin::$instance->dynamic_tags->get_tags_config();
		$tags   = array();

		if ( is_array( $config ) ) {
			foreach ( $config as $name => $tag_data ) {
				$tags[] = array(
					'name'       => $name,
					'title'      => $tag_data['title'] ?? $name,
					'categories' => $tag_data['categories'] ?? array(),
					'group'      => $tag_data['group'] ?? 'general',
				);
			}
		}

		return array( 'dynamic_tags' => $tags );
	}

	/**
	 * Applies a dynamic tag to an element setting.
	 *
	 * @since 2.0.0
	 *
	 * @param array $input Input parameters.
	 * @return array{success: bool, element_id: string, setting_key: string}|\WP_Error
	 */
	public function execute_apply_dynamic_tag( $input ) {
		$post_id      = absint( $input['post_id'] ?? 0 );
		$element_id   = sanitize_text_field( $input['element_id'] ?? '' );
		$setting_key  = sanitize_text_field( $input['setting_key'] ?? '' );
		$tag_name     = sanitize_text_field( $input['tag_name'] ?? '' );
		$tag_settings = isset( $input['tag_settings'] ) && is_array( $input['tag_settings'] ) ? $input['tag_settings'] : array();

		if ( ! $post_id || empty( $element_id ) || empty( $setting_key ) || empty( $tag_name ) ) {
			return new \WP_Error(
				'missing_params',
				__( 'post_id, element_id, setting_key, and tag_name are required.', 'mindcrafts-ai' )
			);
		}

		$page_data = $this->data->get_page_data( $post_id );
		if ( is_wp_error( $page_data ) ) {
			return $page_data;
		}

		$element = $this->data->find_element_by_id( $page_data, $element_id );
		if ( ! $element ) {
			return new \WP_Error( 'element_not_found', __( 'Element not found.', 'mindcrafts-ai' ) );
		}

		$current_settings = $element['settings'] ?? array();
		$dynamic_settings = $current_settings['__dynamic__'] ?? array();
		if ( ! is_array( $dynamic_settings ) ) {
			$dynamic_settings = array();
		}

		// Generate Elementor dynamic tag string or array structure.
		$tag_id = substr( md5( $tag_name . wp_json_encode( $tag_settings ) . time() ), 0, 7 );
		if ( class_exists( '\Elementor\Plugin' ) && ! empty( \Elementor\Plugin::$instance->dynamic_tags ) && is_callable( array( \Elementor\Plugin::$instance->dynamic_tags, 'tag_data_to_tag_text' ) ) ) {
			$tag_text = \Elementor\Plugin::$instance->dynamic_tags->tag_data_to_tag_text( $tag_id, $tag_name, $tag_settings );
		} else {
			$settings_encoded = rawurlencode( wp_json_encode( $tag_settings ) );
			$tag_text = sprintf( '[elementor-tag id="%s" name="%s" settings="%s"]', $tag_id, $tag_name, $settings_encoded );
		}

		$dynamic_settings[ $setting_key ] = $tag_text;

		$update_payload = array(
			'__dynamic__' => $dynamic_settings,
		);

		$updated = $this->data->update_element_settings( $page_data, $element_id, $update_payload );
		if ( ! $updated ) {
			return new \WP_Error( 'update_failed', __( 'Failed to update element settings with dynamic tag.', 'mindcrafts-ai' ) );
		}

		$saved = $this->data->save_page_data( $post_id, $page_data );
		if ( is_wp_error( $saved ) ) {
			return $saved;
		}

		return array(
			'success'     => true,
			'element_id'  => $element_id,
			'setting_key' => $setting_key,
		);
	}
}
