<?php
/**
 * Responsive abilities for MindCrafts AI.
 *
 * Provides MCP tools to update responsive element settings across dynamic
 * breakpoints registered in Elementor.
 *
 * @package MindCrafts_AI
 * @since   2.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles responsive breakpoint adjustments on Elementor elements.
 *
 * @since 2.0.0
 */
class MindCrafts_AI_Responsive_Abilities {

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
			'mindcrafts-ai/update-responsive-settings',
		);
	}

	/**
	 * Retrieves active breakpoints dynamically from Elementor.
	 *
	 * @since 2.0.0
	 *
	 * @return string[] Array of breakpoint keys (e.g. ['mobile', 'mobile_extra', 'tablet', 'tablet_extra', 'laptop', 'widescreen']).
	 */
	private function get_active_breakpoints(): array {
		$default = array( 'mobile', 'tablet' );

		if ( ! class_exists( '\Elementor\Plugin' ) ) {
			return $default;
		}

		$breakpoints_manager = \Elementor\Plugin::$instance->breakpoints ?? null;
		if ( ! $breakpoints_manager || ! is_callable( array( $breakpoints_manager, 'get_active_breakpoints' ) ) ) {
			return $default;
		}

		$active = $breakpoints_manager->get_active_breakpoints();
		$keys   = is_array( $active ) ? array_keys( $active ) : array();

		return ! empty( $keys ) ? array_values( $keys ) : $default;
	}

	/**
	 * Registers responsive abilities.
	 *
	 * @since 2.0.0
	 */
	public function register(): void {
		$breakpoints = $this->get_active_breakpoints();

		wp_register_ability(
			'mindcrafts-ai/update-responsive-settings',
			array(
				'label'               => __( 'Update Responsive Settings', 'mindcrafts-ai' ),
				'description'         => __( 'Adjusts element settings specifically for a targeted breakpoint (mobile, tablet, laptop, widescreen, etc.) by automatically applying the appropriate responsive suffix.', 'mindcrafts-ai' ),
				'category'            => 'mindcrafts-ai',
				'execute_callback'    => array( $this, 'execute_update_responsive' ),
				'permission_callback' => array( $this, 'check_permission' ),
				'input_schema'        => array(
					'type'       => 'object',
					'properties' => array(
						'post_id'    => array(
							'type'        => 'integer',
							'description' => __( 'The post/page ID.', 'mindcrafts-ai' ),
						),
						'element_id' => array(
							'type'        => 'string',
							'description' => __( 'The element ID.', 'mindcrafts-ai' ),
						),
						'breakpoint' => array(
							'type'        => 'string',
							'enum'        => $breakpoints,
							'description' => __( 'The breakpoint view to target (e.g., mobile, tablet, laptop, widescreen).', 'mindcrafts-ai' ),
						),
						'settings'   => array(
							'type'        => 'object',
							'description' => __( 'Settings object to set for the breakpoint.', 'mindcrafts-ai' ),
						),
					),
					'required'   => array( 'post_id', 'element_id', 'breakpoint', 'settings' ),
				),
				'output_schema'       => array(
					'type'       => 'object',
					'properties' => array(
						'success'    => array( 'type' => 'boolean' ),
						'element_id' => array( 'type' => 'string' ),
					),
				),
			)
		);
	}

	/**
	 * Permission check for editing post responsive settings.
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
	 * Executes responsive settings update on an element.
	 *
	 * @since 2.0.0
	 *
	 * @param array $input Input parameters.
	 * @return array{success: bool, element_id: string}|\WP_Error
	 */
	public function execute_update_responsive( $input ) {
		$post_id    = absint( $input['post_id'] ?? 0 );
		$element_id = sanitize_text_field( $input['element_id'] ?? '' );
		$breakpoint = sanitize_text_field( $input['breakpoint'] ?? '' );
		$settings   = isset( $input['settings'] ) && is_array( $input['settings'] ) ? $input['settings'] : array();

		if ( ! $post_id || empty( $element_id ) || empty( $breakpoint ) || empty( $settings ) ) {
			return new \WP_Error(
				'missing_params',
				__( 'post_id, element_id, breakpoint, and settings are required.', 'mindcrafts-ai' )
			);
		}

		$allowed_breakpoints = $this->get_active_breakpoints();
		if ( ! in_array( $breakpoint, $allowed_breakpoints, true ) ) {
			return new \WP_Error(
				'invalid_breakpoint',
				sprintf(
					/* translators: 1: provided breakpoint, 2: comma-separated list of valid breakpoints */
					__( 'Breakpoint "%1$s" is invalid. Active breakpoints: %2$s', 'mindcrafts-ai' ),
					$breakpoint,
					implode( ', ', $allowed_breakpoints )
				)
			);
		}

		$page_data = $this->data->get_page_data( $post_id );
		if ( is_wp_error( $page_data ) ) {
			return $page_data;
		}

		$responsive_settings = array();
		$suffix              = '_' . $breakpoint;

		foreach ( $settings as $key => $value ) {
			if ( substr( $key, -strlen( $suffix ) ) === $suffix ) {
				$responsive_settings[ $key ] = $value;
			} else {
				$responsive_settings[ $key . $suffix ] = $value;
			}
		}

		$updated = $this->data->update_element_settings( $page_data, $element_id, $responsive_settings );

		if ( ! $updated ) {
			return new \WP_Error( 'element_not_found', __( 'Element not found or failed to update.', 'mindcrafts-ai' ) );
		}

		$result = $this->data->save_page_data( $post_id, $page_data );
		if ( is_wp_error( $result ) ) {
			return $result;
		}

		return array(
			'success'    => true,
			'element_id' => $element_id,
		);
	}
}
