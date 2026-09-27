<?php
/**
 * Popup Builder abilities for MindCrafts AI.
 *
 * Provides MCP tools for creating, listing, and configuring Elementor Pro Popups
 * and display triggers.
 *
 * @package MindCrafts_AI
 * @since   2.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Manages Elementor Pro Popups and display triggers.
 *
 * @since 2.0.0
 */
class MindCrafts_AI_Popup_Abilities {

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
			'mindcrafts-ai/create-popup',
			'mindcrafts-ai/list-popups',
			'mindcrafts-ai/set-popup-trigger',
		);
	}

	/**
	 * Registers popup abilities.
	 *
	 * @since 2.0.0
	 */
	public function register(): void {
		wp_register_ability(
			'mindcrafts-ai/create-popup',
			array(
				'label'               => __( 'Create Popup', 'mindcrafts-ai' ),
				'description'         => __( 'Creates an Elementor Pro popup template with optional triggers (page_load, scroll, click, exit_intent).', 'mindcrafts-ai' ),
				'category'            => 'mindcrafts-ai',
				'execute_callback'    => array( $this, 'execute_create_popup' ),
				'permission_callback' => array( $this, 'check_permission' ),
				'input_schema'        => array(
					'type'       => 'object',
					'properties' => array(
						'title'             => array(
							'type'        => 'string',
							'description' => __( 'Popup title.', 'mindcrafts-ai' ),
						),
						'trigger'           => array(
							'type'        => 'string',
							'enum'        => array( 'page_load', 'scroll', 'click', 'exit_intent' ),
							'description' => __( 'Display trigger condition.', 'mindcrafts-ai' ),
						),
						'delay_seconds'     => array(
							'type'        => 'integer',
							'description' => __( 'Delay in seconds for page_load trigger.', 'mindcrafts-ai' ),
						),
						'scroll_percentage' => array(
							'type'        => 'integer',
							'description' => __( 'Scroll percentage for scroll trigger (e.g. 50).', 'mindcrafts-ai' ),
						),
						'structure'         => array(
							'type'        => 'array',
							'description' => __( 'Optional element tree for popup content.', 'mindcrafts-ai' ),
						),
					),
					'required'   => array( 'title' ),
				),
				'output_schema'       => array(
					'type'       => 'object',
					'properties' => array(
						'success'  => array( 'type' => 'boolean' ),
						'popup_id' => array( 'type' => 'integer' ),
						'edit_url' => array( 'type' => 'string' ),
					),
				),
			)
		);

		wp_register_ability(
			'mindcrafts-ai/list-popups',
			array(
				'label'               => __( 'List Popups', 'mindcrafts-ai' ),
				'description'         => __( 'Lists all Elementor popup templates.', 'mindcrafts-ai' ),
				'category'            => 'mindcrafts-ai',
				'execute_callback'    => array( $this, 'execute_list_popups' ),
				'permission_callback' => array( $this, 'check_permission' ),
				'input_schema'        => array(
					'type'       => 'object',
					'properties' => array(),
				),
				'output_schema'       => array(
					'type'       => 'object',
					'properties' => array(
						'popups' => array(
							'type'  => 'array',
							'items' => array(
								'type'       => 'object',
								'properties' => array(
									'id'       => array( 'type' => 'integer' ),
									'title'    => array( 'type' => 'string' ),
									'edit_url' => array( 'type' => 'string' ),
								),
							),
						),
					),
				),
			)
		);

		wp_register_ability(
			'mindcrafts-ai/set-popup-trigger',
			array(
				'label'               => __( 'Set Popup Trigger', 'mindcrafts-ai' ),
				'description'         => __( 'Configures display triggers and timing rules on an Elementor popup template.', 'mindcrafts-ai' ),
				'category'            => 'mindcrafts-ai',
				'execute_callback'    => array( $this, 'execute_set_popup_trigger' ),
				'permission_callback' => array( $this, 'check_permission' ),
				'input_schema'        => array(
					'type'       => 'object',
					'properties' => array(
						'popup_id' => array(
							'type'        => 'integer',
							'description' => __( 'The popup template post ID.', 'mindcrafts-ai' ),
						),
						'trigger'  => array(
							'type'        => 'string',
							'enum'        => array( 'page_load', 'scroll', 'click', 'exit_intent' ),
							'description' => __( 'Trigger event.', 'mindcrafts-ai' ),
						),
						'settings' => array(
							'type'        => 'object',
							'description' => __( 'Additional trigger settings (e.g. delay_seconds, scroll_percentage).', 'mindcrafts-ai' ),
						),
					),
					'required'   => array( 'popup_id', 'trigger' ),
				),
				'output_schema'       => array(
					'type'       => 'object',
					'properties' => array(
						'success'  => array( 'type' => 'boolean' ),
						'popup_id' => array( 'type' => 'integer' ),
					),
				),
			)
		);
	}

	/**
	 * Permission check for popups.
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
		$popup_id = absint( $input['popup_id'] ?? 0 );
		if ( $popup_id && ! current_user_can( 'edit_post', $popup_id ) ) {
			return false;
		}
		return true;
	}

	/**
	 * Creates a new Elementor Popup.
	 *
	 * @since 2.0.0
	 *
	 * @param array $input Input parameters.
	 * @return array{success: bool, popup_id: int, edit_url: string}|\WP_Error
	 */
	public function execute_create_popup( $input ) {
		$title             = sanitize_text_field( $input['title'] ?? '' );
		$trigger           = sanitize_text_field( $input['trigger'] ?? 'page_load' );
		$delay_seconds     = absint( $input['delay_seconds'] ?? 0 );
		$scroll_percentage = absint( $input['scroll_percentage'] ?? 50 );
		$structure         = isset( $input['structure'] ) && is_array( $input['structure'] ) ? $input['structure'] : array();

		if ( empty( $title ) ) {
			return new \WP_Error( 'missing_title', __( 'Popup title is required.', 'mindcrafts-ai' ) );
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

		update_post_meta( $post_id, '_elementor_template_type', 'popup' );
		update_post_meta( $post_id, '_elementor_edit_mode', 'builder' );

		// Configure popup settings.
		$popup_settings = array(
			'triggers' => array(
				array(
					'id'    => $trigger,
					'delay' => $delay_seconds,
					'percentage' => $scroll_percentage,
				),
			),
			'timing'   => array(
				array(
					'id'    => 'times',
					'times' => 1,
				),
			),
		);
		update_post_meta( $post_id, '_elementor_page_settings', $popup_settings );

		if ( empty( $structure ) ) {
			$container = $this->factory->create_container(
				array(
					'flex_direction' => 'column',
					'padding'        => array( 'unit' => 'px', 'top' => '30', 'right' => '30', 'bottom' => '30', 'left' => '30' ),
				)
			);
			$structure = array( $container );
		}

		$saved = $this->data->save_page_data( $post_id, $structure );
		if ( is_wp_error( $saved ) ) {
			return $saved;
		}

		return array(
			'success'  => true,
			'popup_id' => $post_id,
			'edit_url' => admin_url( 'post.php?post=' . $post_id . '&action=elementor' ),
		);
	}

	/**
	 * Lists all popup templates.
	 *
	 * @since 2.0.0
	 *
	 * @param mixed $input Optional input.
	 * @return array{popups: array<int, array<string, mixed>>}
	 */
	public function execute_list_popups( $input = null ): array {
		$posts = get_posts(
			array(
				'post_type'      => 'elementor_library',
				'post_status'    => 'any',
				'posts_per_page' => 100,
				'meta_key'       => '_elementor_template_type',
				'meta_value'     => 'popup',
			)
		);

		$popups = array();
		foreach ( $posts as $post ) {
			$popups[] = array(
				'id'       => $post->ID,
				'title'    => $post->post_title,
				'edit_url' => admin_url( 'post.php?post=' . $post->ID . '&action=elementor' ),
			);
		}

		return array( 'popups' => $popups );
	}

	/**
	 * Sets popup trigger settings.
	 *
	 * @since 2.0.0
	 *
	 * @param array $input Input parameters.
	 * @return array{success: bool, popup_id: int}|\WP_Error
	 */
	public function execute_set_popup_trigger( $input ) {
		$popup_id = absint( $input['popup_id'] ?? 0 );
		$trigger  = sanitize_text_field( $input['trigger'] ?? '' );
		$settings = isset( $input['settings'] ) && is_array( $input['settings'] ) ? $input['settings'] : array();

		if ( ! $popup_id || empty( $trigger ) ) {
			return new \WP_Error( 'missing_params', __( 'popup_id and trigger are required.', 'mindcrafts-ai' ) );
		}

		$post = get_post( $popup_id );
		if ( ! $post || 'elementor_library' !== $post->post_type ) {
			return new \WP_Error( 'invalid_popup', __( 'Invalid popup post ID.', 'mindcrafts-ai' ) );
		}

		$page_settings             = get_post_meta( $popup_id, '_elementor_page_settings', true );
		if ( ! is_array( $page_settings ) ) {
			$page_settings = array();
		}

		$trigger_entry = array_merge( array( 'id' => $trigger ), $settings );
		$page_settings['triggers'] = array( $trigger_entry );

		update_post_meta( $popup_id, '_elementor_page_settings', $page_settings );

		return array(
			'success'  => true,
			'popup_id' => $popup_id,
		);
	}
}
