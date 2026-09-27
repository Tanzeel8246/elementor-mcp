<?php
/**
 * Widget MCP abilities for Elementor.
 *
 * Registers the universal add-widget/update-widget tools plus convenience
 * shortcut tools for common widgets (heading, text, image, button, etc.).
 * Pro widget tools register only when Elementor Pro is active.
 *
 * @package MindCrafts_AI
 * @since   1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Registers and implements the widget abilities.
 *
 * @since 1.0.0
 */
class MindCrafts_AI_Widget_Abilities {

	/**
	 * @var MindCrafts_AI_Data
	 */
	private $data;

	/**
	 * @var MindCrafts_AI_Element_Factory
	 */
	private $factory;

	/**
	 * @var MindCrafts_AI_Schema_Generator
	 */
	private $schema_generator;

	/**
	 * @var MindCrafts_AI_Settings_Validator
	 */
	private $validator;

	/**
	 * Tracked ability names.
	 *
	 * @var string[]
	 */
	private $ability_names = array();

	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 *
	 * @param MindCrafts_AI_Data               $data             The data access layer.
	 * @param MindCrafts_AI_Element_Factory    $factory          The element factory.
	 * @param MindCrafts_AI_Schema_Generator   $schema_generator The schema generator.
	 * @param MindCrafts_AI_Settings_Validator $validator        The settings validator.
	 */
	public function __construct(
		MindCrafts_AI_Data $data,
		MindCrafts_AI_Element_Factory $factory,
		MindCrafts_AI_Schema_Generator $schema_generator,
		MindCrafts_AI_Settings_Validator $validator
	) {
		$this->data             = $data;
		$this->factory          = $factory;
		$this->schema_generator = $schema_generator;
		$this->validator        = $validator;
	}

	/**
	 * Returns the ability names registered by this class.
	 *
	 * @since 1.0.0
	 *
	 * @return string[]
	 */
	public function get_ability_names(): array {
		return $this->ability_names;
	}

	/**
	 * Registers all widget abilities.
	 *
	 * @since 1.0.0
	 */
	public function register(): void {
		// Universal tools.
		$this->register_add_widget();
		$this->register_update_widget();

		// Core widget convenience tools.
		$this->register_add_heading();
		$this->register_add_text_editor();
		$this->register_add_image();
		$this->register_add_button();
		$this->register_add_video();
		$this->register_add_icon();
		$this->register_add_spacer();
		$this->register_add_divider();
		$this->register_add_icon_box();

		// Pro widget convenience tools (only if Pro is active).
		if ( defined( 'ELEMENTOR_PRO_VERSION' ) ) {
			$this->register_add_form();
			$this->register_add_posts_grid();
			$this->register_add_countdown();
			$this->register_add_price_table();
			$this->register_add_flip_box();
			$this->register_add_animated_headline();
		}
	}

	/**
	 * Permission check for widget editing.
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

	// =========================================================================
	// Universal: add-widget
	// =========================================================================

	private function register_add_widget(): void {
		$this->ability_names[] = 'mindcrafts-ai/add-widget';

		wp_register_ability(
			'mindcrafts-ai/add-widget',
			array(
				'label'               => __( 'Add Widget', 'mindcrafts-ai' ),
				'description'         => __( 'Adds any Elementor widget to a container. Use get-widget-schema to discover the available settings for each widget type.', 'mindcrafts-ai' ),
				'category'            => 'mindcrafts-ai',
				'execute_callback'    => array( $this, 'execute_add_widget' ),
				'permission_callback' => array( $this, 'check_edit_permission' ),
				'input_schema'        => array(
					'type'       => 'object',
					'properties' => array(
						'post_id'     => array(
							'type'        => 'integer',
							'description' => __( 'The post/page ID.', 'mindcrafts-ai' ),
						),
						'parent_id'   => array(
							'type'        => 'string',
							'description' => __( 'Parent container element ID.', 'mindcrafts-ai' ),
						),
						'position'    => array(
							'type'        => 'integer',
							'description' => __( 'Insert position. -1 = append.', 'mindcrafts-ai' ),
						),
						'widget_type' => array(
							'type'        => 'string',
							'description' => __( 'The widget type name (e.g. "heading", "button", "image").', 'mindcrafts-ai' ),
						),
						'settings'    => array(
							'type'        => 'object',
							'description' => __( 'Widget-specific settings.', 'mindcrafts-ai' ),
						),
					),
					'required'   => array( 'post_id', 'parent_id', 'widget_type' ),
				),
				'output_schema'       => array(
					'type'       => 'object',
					'properties' => array(
						'element_id'  => array( 'type' => 'string' ),
						'widget_type' => array( 'type' => 'string' ),
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
	 * Executes the add-widget ability.
	 *
	 * @since 1.0.0
	 *
	 * @param array $input The input parameters.
	 * @return array|\WP_Error
	 */
	public function execute_add_widget( $input ) {
		$post_id     = absint( $input['post_id'] ?? 0 );
		$parent_id   = sanitize_text_field( $input['parent_id'] ?? '' );
		$position    = intval( $input['position'] ?? -1 );
		$widget_type = sanitize_text_field( $input['widget_type'] ?? '' );
		$settings    = $input['settings'] ?? array();

		if ( ! $post_id || empty( $parent_id ) || empty( $widget_type ) ) {
			return new \WP_Error( 'missing_params', __( 'post_id, parent_id, and widget_type are required.', 'mindcrafts-ai' ) );
		}

		// Validate widget type exists.
		$widget_instance = \Elementor\Plugin::$instance->widgets_manager->get_widget_types( $widget_type );
		if ( ! $widget_instance ) {
			return new \WP_Error(
				'invalid_widget_type',
				sprintf( __( 'Widget type "%s" not found.', 'mindcrafts-ai' ), $widget_type )
			);
		}

		// Validate settings if provided.
		if ( ! empty( $settings ) ) {
			$valid = $this->validator->validate( $widget_type, $settings );
			if ( is_wp_error( $valid ) ) {
				return $valid;
			}
		}

		$page_data = $this->data->get_page_data( $post_id );

		if ( is_wp_error( $page_data ) ) {
			return $page_data;
		}

		$widget = $this->factory->create_widget( $widget_type, $settings );

		$inserted = $this->data->insert_element( $page_data, $parent_id, $widget, $position );

		if ( ! $inserted ) {
			return new \WP_Error( 'parent_not_found', __( 'Parent container not found.', 'mindcrafts-ai' ) );
		}

		$result = $this->data->save_page_data( $post_id, $page_data );

		if ( is_wp_error( $result ) ) {
			return $result;
		}

		return array(
			'element_id'  => $widget['id'],
			'widget_type' => $widget_type,
		);
	}

	// =========================================================================
	// Universal: update-widget
	// =========================================================================

	private function register_update_widget(): void {
		$this->ability_names[] = 'mindcrafts-ai/update-widget';

		wp_register_ability(
			'mindcrafts-ai/update-widget',
			array(
				'label'               => __( 'Update Widget', 'mindcrafts-ai' ),
				'description'         => __( 'Updates settings on an existing widget. Settings are merged (partial update).', 'mindcrafts-ai' ),
				'category'            => 'mindcrafts-ai',
				'execute_callback'    => array( $this, 'execute_update_widget' ),
				'permission_callback' => array( $this, 'check_edit_permission' ),
				'input_schema'        => array(
					'type'       => 'object',
					'properties' => array(
						'post_id'    => array(
							'type'        => 'integer',
							'description' => __( 'The post/page ID.', 'mindcrafts-ai' ),
						),
						'element_id' => array(
							'type'        => 'string',
							'description' => __( 'The widget element ID.', 'mindcrafts-ai' ),
						),
						'settings'   => array(
							'type'        => 'object',
							'description' => __( 'Partial settings to merge.', 'mindcrafts-ai' ),
						),
					),
					'required'   => array( 'post_id', 'element_id', 'settings' ),
				),
				'output_schema'       => array(
					'type'       => 'object',
					'properties' => array(
						'success'    => array( 'type' => 'boolean' ),
						'element_id' => array( 'type' => 'string' ),
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

	/**
	 * Executes the update-widget ability.
	 *
	 * @since 1.0.0
	 *
	 * @param array $input The input parameters.
	 * @return array|\WP_Error
	 */
	public function execute_update_widget( $input ) {
		$post_id    = absint( $input['post_id'] ?? 0 );
		$element_id = sanitize_text_field( $input['element_id'] ?? '' );
		$settings   = $input['settings'] ?? array();

		if ( ! $post_id || empty( $element_id ) || empty( $settings ) ) {
			return new \WP_Error( 'missing_params', __( 'post_id, element_id, and settings are required.', 'mindcrafts-ai' ) );
		}

		$page_data = $this->data->get_page_data( $post_id );

		if ( is_wp_error( $page_data ) ) {
			return $page_data;
		}

		// Find the widget to validate its type.
		$element = $this->data->find_element_by_id( $page_data, $element_id );

		if ( null === $element ) {
			return new \WP_Error( 'element_not_found', __( 'Element not found.', 'mindcrafts-ai' ) );
		}

		if ( ( $element['elType'] ?? '' ) !== 'widget' ) {
			return new \WP_Error( 'not_a_widget', __( 'Target element is not a widget.', 'mindcrafts-ai' ) );
		}

		$widget_type = sanitize_text_field( $element['widgetType'] ?? '' );
		if ( empty( $widget_type ) ) {
			return new \WP_Error( 'missing_widget_type', __( 'Target widget is missing widgetType.', 'mindcrafts-ai' ) );
		}

		$valid = $this->validator->validate( $widget_type, $settings );
		if ( is_wp_error( $valid ) ) {
			return $valid;
		}

		$updated = $this->data->update_element_settings( $page_data, $element_id, $settings );

		if ( ! $updated ) {
			return new \WP_Error( 'update_failed', __( 'Failed to update widget settings.', 'mindcrafts-ai' ) );
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

	// =========================================================================
	// Convenience tool helper
	// =========================================================================

	/**
	 * Registers a convenience widget tool and adds it to ability_names.
	 *
	 * @param string $name        Ability name suffix (e.g. 'add-heading').
	 * @param string $label       Human label.
	 * @param string $description Tool description.
	 * @param array  $extra_props Extra input schema properties beyond post_id/parent_id/position.
	 * @param array  $required    Required property names (post_id and parent_id always added).
	 * @param string $widget_type The Elementor widget type name.
	 * @param array  $defaults    Default settings for this widget type.
	 */
	private function register_convenience_tool(
		string $name,
		string $label,
		string $description,
		array $extra_props,
		array $required,
		string $widget_type,
		array $defaults = array()
	): void {
		$full_name             = 'mindcrafts-ai/' . $name;
		$this->ability_names[] = $full_name;

		$base_props = array(
			'post_id'   => array(
				'type'        => 'integer',
				'description' => __( 'The post/page ID.', 'mindcrafts-ai' ),
			),
			'parent_id' => array(
				'type'        => 'string',
				'description' => __( 'Parent container element ID.', 'mindcrafts-ai' ),
			),
			'position'  => array(
				'type'        => 'integer',
				'description' => __( 'Insert position. -1 = append.', 'mindcrafts-ai' ),
			),
		);

		$all_required = array_unique( array_merge( array( 'post_id', 'parent_id' ), $required ) );

		wp_register_ability(
			$full_name,
			array(
				'label'               => $label,
				'description'         => $description,
				'category'            => 'mindcrafts-ai',
				'execute_callback'    => function ( $input ) use ( $widget_type, $extra_props, $defaults ) {
					return $this->execute_convenience_tool( $input, $widget_type, array_keys( $extra_props ), $defaults );
				},
				'permission_callback' => array( $this, 'check_edit_permission' ),
				'input_schema'        => array(
					'type'       => 'object',
					'properties' => array_merge( $base_props, $extra_props ),
					'required'   => $all_required,
				),
				'output_schema'       => array(
					'type'       => 'object',
					'properties' => array(
						'element_id' => array( 'type' => 'string' ),
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
	 * Shared execution for convenience tools.
	 *
	 * Extracts the widget-specific settings keys from input and delegates to add-widget logic.
	 *
	 * @param array  $input        The input parameters.
	 * @param string $widget_type  The Elementor widget type.
	 * @param array  $setting_keys Setting keys to extract from input.
	 * @param array  $defaults     Default settings.
	 * @return array|\WP_Error
	 */
	private function execute_convenience_tool( $input, string $widget_type, array $setting_keys, array $defaults ) {
		$settings = $defaults;

		foreach ( $setting_keys as $key ) {
			if ( isset( $input[ $key ] ) ) {
				$settings[ $key ] = $input[ $key ];
			}
		}

		return $this->execute_add_widget(
			array(
				'post_id'     => $input['post_id'] ?? 0,
				'parent_id'   => $input['parent_id'] ?? '',
				'position'    => $input['position'] ?? -1,
				'widget_type' => $widget_type,
				'settings'    => $settings,
			)
		);
	}

	// =========================================================================
	// Core convenience tools
	// =========================================================================

	private function register_add_heading(): void {
		$this->register_convenience_tool(
			'add-heading',
			__( 'Add Heading', 'mindcrafts-ai' ),
			__( 'Adds a heading widget with title, size, alignment, and color options.', 'mindcrafts-ai' ),
			array(
				'title'       => array( 'type' => 'string', 'description' => __( 'Heading text.', 'mindcrafts-ai' ) ),
				'header_size' => array( 'type' => 'string', 'enum' => array( 'h1', 'h2', 'h3', 'h4', 'h5', 'h6' ), 'description' => __( 'HTML heading tag. Default: h2.', 'mindcrafts-ai' ) ),
				'size'        => array( 'type' => 'string', 'enum' => array( 'default', 'small', 'medium', 'large', 'xl', 'xxl' ), 'description' => __( 'Elementor size preset.', 'mindcrafts-ai' ) ),
				'align'       => array( 'type' => 'string', 'enum' => array( 'left', 'center', 'right', 'justify' ), 'description' => __( 'Text alignment.', 'mindcrafts-ai' ) ),
				'title_color' => array( 'type' => 'string', 'description' => __( 'Heading color (hex).', 'mindcrafts-ai' ) ),
				'link'        => array( 'type' => 'object', 'description' => __( 'Link object with url key.', 'mindcrafts-ai' ) ),
			),
			array( 'title' ),
			'heading',
			array( 'header_size' => 'h2' )
		);
	}

	private function register_add_text_editor(): void {
		$this->register_convenience_tool(
			'add-text-editor',
			__( 'Add Text Editor', 'mindcrafts-ai' ),
			__( 'Adds a rich text editor widget with HTML content.', 'mindcrafts-ai' ),
			array(
				'editor'     => array( 'type' => 'string', 'description' => __( 'HTML content.', 'mindcrafts-ai' ) ),
				'align'      => array( 'type' => 'string', 'enum' => array( 'left', 'center', 'right', 'justify' ), 'description' => __( 'Text alignment.', 'mindcrafts-ai' ) ),
				'text_color' => array( 'type' => 'string', 'description' => __( 'Text color (hex).', 'mindcrafts-ai' ) ),
			),
			array( 'editor' ),
			'text-editor'
		);
	}

	private function register_add_image(): void {
		$this->register_convenience_tool(
			'add-image',
			__( 'Add Image', 'mindcrafts-ai' ),
			__( 'Adds an image widget with source, size, alignment, caption, and link options.', 'mindcrafts-ai' ),
			array(
				'image'          => array( 'type' => 'object', 'description' => __( 'Image object with url (required) and optional id.', 'mindcrafts-ai' ) ),
				'image_size'     => array( 'type' => 'string', 'enum' => array( 'thumbnail', 'medium', 'medium_large', 'large', 'full' ), 'description' => __( 'Image size preset.', 'mindcrafts-ai' ) ),
				'align'          => array( 'type' => 'string', 'enum' => array( 'left', 'center', 'right' ), 'description' => __( 'Image alignment.', 'mindcrafts-ai' ) ),
				'caption_source' => array( 'type' => 'string', 'enum' => array( 'none', 'attachment', 'custom' ), 'description' => __( 'Caption source.', 'mindcrafts-ai' ) ),
				'caption'        => array( 'type' => 'string', 'description' => __( 'Custom caption text.', 'mindcrafts-ai' ) ),
				'link_to'        => array( 'type' => 'string', 'enum' => array( 'none', 'file', 'custom' ), 'description' => __( 'Link behavior.', 'mindcrafts-ai' ) ),
				'link'           => array( 'type' => 'object', 'description' => __( 'Link object with url key.', 'mindcrafts-ai' ) ),
			),
			array( 'image' ),
			'image'
		);
	}

	private function register_add_button(): void {
		$this->register_convenience_tool(
			'add-button',
			__( 'Add Button', 'mindcrafts-ai' ),
			__( 'Adds a button widget with text, link, size, type, alignment, and icon options.', 'mindcrafts-ai' ),
			array(
				'text'          => array( 'type' => 'string', 'description' => __( 'Button text.', 'mindcrafts-ai' ) ),
				'link'          => array( 'type' => 'object', 'description' => __( 'Link object with url key.', 'mindcrafts-ai' ) ),
				'size'          => array( 'type' => 'string', 'enum' => array( 'xs', 'sm', 'md', 'lg', 'xl' ), 'description' => __( 'Button size.', 'mindcrafts-ai' ) ),
				'button_type'   => array( 'type' => 'string', 'enum' => array( '', 'info', 'success', 'warning', 'danger' ), 'description' => __( 'Button style type.', 'mindcrafts-ai' ) ),
				'align'         => array( 'type' => 'string', 'enum' => array( 'left', 'center', 'right', 'justify' ), 'description' => __( 'Button alignment.', 'mindcrafts-ai' ) ),
				'selected_icon' => array( 'type' => 'object', 'description' => __( 'Icon object with value and library.', 'mindcrafts-ai' ) ),
				'icon_align'    => array( 'type' => 'string', 'enum' => array( 'row', 'row-reverse' ), 'description' => __( 'Icon position.', 'mindcrafts-ai' ) ),
			),
			array( 'text' ),
			'button',
			array( 'text' => 'Click here', 'size' => 'sm' )
		);
	}

	private function register_add_video(): void {
		$this->register_convenience_tool(
			'add-video',
			__( 'Add Video', 'mindcrafts-ai' ),
			__( 'Adds a video widget with support for YouTube, Vimeo, Dailymotion, and self-hosted HTML5 video.', 'mindcrafts-ai' ),
			array(
				'video_type'  => array( 'type' => 'string', 'enum' => array( 'youtube', 'vimeo', 'dailymotion', 'hosted' ), 'description' => __( 'Video source type.', 'mindcrafts-ai' ) ),
				'youtube_url' => array( 'type' => 'string', 'description' => __( 'YouTube URL.', 'mindcrafts-ai' ) ),
				'vimeo_url'   => array( 'type' => 'string', 'description' => __( 'Vimeo URL.', 'mindcrafts-ai' ) ),
				'autoplay'    => array( 'type' => 'string', 'enum' => array( 'yes', '' ), 'description' => __( 'Autoplay on load.', 'mindcrafts-ai' ) ),
				'mute'        => array( 'type' => 'string', 'enum' => array( 'yes', '' ), 'description' => __( 'Mute audio.', 'mindcrafts-ai' ) ),
				'loop'        => array( 'type' => 'string', 'enum' => array( 'yes', '' ), 'description' => __( 'Loop video.', 'mindcrafts-ai' ) ),
				'controls'    => array( 'type' => 'string', 'enum' => array( 'yes', '' ), 'description' => __( 'Show player controls.', 'mindcrafts-ai' ) ),
			),
			array(),
			'video',
			array( 'video_type' => 'youtube' )
		);
	}

	private function register_add_icon(): void {
		$this->register_convenience_tool(
			'add-icon',
			__( 'Add Icon', 'mindcrafts-ai' ),
			__( 'Adds an icon widget with Font Awesome or custom icon, view mode, shape, and color options.', 'mindcrafts-ai' ),
			array(
				'selected_icon' => array( 'type' => 'object', 'description' => __( 'Icon object: { "value": "fas fa-star", "library": "fa-solid" }.', 'mindcrafts-ai' ) ),
				'view'          => array( 'type' => 'string', 'enum' => array( 'default', 'stacked', 'framed' ), 'description' => __( 'Icon view mode.', 'mindcrafts-ai' ) ),
				'shape'         => array( 'type' => 'string', 'enum' => array( 'circle', 'square' ), 'description' => __( 'Icon shape (for stacked/framed).', 'mindcrafts-ai' ) ),
				'primary_color' => array( 'type' => 'string', 'description' => __( 'Primary color (hex).', 'mindcrafts-ai' ) ),
				'size'          => array( 'type' => 'object', 'description' => __( 'Icon size: { "size": 50, "unit": "px" }.', 'mindcrafts-ai' ) ),
				'link'          => array( 'type' => 'object', 'description' => __( 'Link object with url key.', 'mindcrafts-ai' ) ),
				'align'         => array( 'type' => 'string', 'enum' => array( 'left', 'center', 'right' ), 'description' => __( 'Icon alignment.', 'mindcrafts-ai' ) ),
			),
			array(),
			'icon',
			array( 'selected_icon' => array( 'value' => 'fas fa-star', 'library' => 'fa-solid' ) )
		);
	}

	private function register_add_spacer(): void {
		$this->register_convenience_tool(
			'add-spacer',
			__( 'Add Spacer', 'mindcrafts-ai' ),
			__( 'Adds a spacer widget for vertical spacing between elements.', 'mindcrafts-ai' ),
			array(
				'space' => array( 'type' => 'object', 'description' => __( 'Spacer height: { "size": 50, "unit": "px" }.', 'mindcrafts-ai' ) ),
			),
			array(),
			'spacer',
			array( 'space' => array( 'size' => 50, 'unit' => 'px' ) )
		);
	}

	private function register_add_divider(): void {
		$this->register_convenience_tool(
			'add-divider',
			__( 'Add Divider', 'mindcrafts-ai' ),
			__( 'Adds a horizontal divider/separator widget with style, weight, color, and width options.', 'mindcrafts-ai' ),
			array(
				'style'  => array( 'type' => 'string', 'enum' => array( 'solid', 'dashed', 'dotted', 'double' ), 'description' => __( 'Divider line style.', 'mindcrafts-ai' ) ),
				'weight' => array( 'type' => 'object', 'description' => __( 'Line weight: { "size": 1, "unit": "px" }.', 'mindcrafts-ai' ) ),
				'color'  => array( 'type' => 'string', 'description' => __( 'Divider color (hex).', 'mindcrafts-ai' ) ),
				'width'  => array( 'type' => 'object', 'description' => __( 'Divider width: { "size": 100, "unit": "%" }.', 'mindcrafts-ai' ) ),
				'gap'    => array( 'type' => 'object', 'description' => __( 'Gap above/below: { "size": 15, "unit": "px" }.', 'mindcrafts-ai' ) ),
			),
			array(),
			'divider',
			array( 'style' => 'solid' )
		);
	}

	private function register_add_icon_box(): void {
		$this->register_convenience_tool(
			'add-icon-box',
			__( 'Add Icon Box', 'mindcrafts-ai' ),
			__( 'Adds an icon box widget combining an icon, title, and description.', 'mindcrafts-ai' ),
			array(
				'selected_icon'  => array( 'type' => 'object', 'description' => __( 'Icon object: { "value": "fas fa-star", "library": "fa-solid" }.', 'mindcrafts-ai' ) ),
				'title_text'     => array( 'type' => 'string', 'description' => __( 'Box title.', 'mindcrafts-ai' ) ),
				'description_text' => array( 'type' => 'string', 'description' => __( 'Box description.', 'mindcrafts-ai' ) ),
				'view'           => array( 'type' => 'string', 'enum' => array( 'default', 'stacked', 'framed' ), 'description' => __( 'Icon view mode.', 'mindcrafts-ai' ) ),
				'shape'          => array( 'type' => 'string', 'enum' => array( 'circle', 'square' ), 'description' => __( 'Icon shape.', 'mindcrafts-ai' ) ),
				'link'           => array( 'type' => 'object', 'description' => __( 'Link object with url key.', 'mindcrafts-ai' ) ),
				'title_color'    => array( 'type' => 'string', 'description' => __( 'Title color (hex).', 'mindcrafts-ai' ) ),
				'primary_color'  => array( 'type' => 'string', 'description' => __( 'Icon primary color (hex).', 'mindcrafts-ai' ) ),
			),
			array( 'title_text' ),
			'icon-box',
			array(
				'selected_icon' => array( 'value' => 'fas fa-star', 'library' => 'fa-solid' ),
			)
		);
	}

	// =========================================================================
	// Pro convenience tools (only when ELEMENTOR_PRO_VERSION is defined)
	// =========================================================================

	private function register_add_form(): void {
		$this->register_convenience_tool(
			'add-form',
			__( 'Add Form (Pro)', 'mindcrafts-ai' ),
			__( 'Adds an Elementor Pro form widget with customizable fields, button, and email action.', 'mindcrafts-ai' ),
			array(
				'form_name'     => array( 'type' => 'string', 'description' => __( 'Form name.', 'mindcrafts-ai' ) ),
				'form_fields'   => array(
					'type'        => 'array',
					'description' => __( 'Array of field definitions.', 'mindcrafts-ai' ),
					'items'       => array(
						'type'       => 'object',
						'properties' => array(
							'field_type'    => array( 'type' => 'string', 'enum' => array( 'text', 'email', 'textarea', 'url', 'tel', 'select', 'radio', 'checkbox', 'number', 'date', 'hidden' ) ),
							'field_label'   => array( 'type' => 'string' ),
							'placeholder'   => array( 'type' => 'string' ),
							'required'      => array( 'type' => 'string', 'enum' => array( 'yes', '' ) ),
							'width'         => array( 'type' => 'string', 'enum' => array( '100', '80', '75', '66', '50', '33', '25' ) ),
							'field_options' => array( 'type' => 'string' ),
						),
					),
				),
				'button_text'   => array( 'type' => 'string', 'description' => __( 'Submit button text.', 'mindcrafts-ai' ) ),
				'email_to'      => array( 'type' => 'string', 'description' => __( 'Email recipient.', 'mindcrafts-ai' ) ),
				'email_subject' => array( 'type' => 'string', 'description' => __( 'Email subject.', 'mindcrafts-ai' ) ),
			),
			array( 'form_name' ),
			'form',
			array( 'button_text' => 'Send' )
		);
	}

	private function register_add_posts_grid(): void {
		$this->register_convenience_tool(
			'add-posts-grid',
			__( 'Add Posts Grid (Pro)', 'mindcrafts-ai' ),
			__( 'Adds an Elementor Pro posts grid widget to display a grid of posts.', 'mindcrafts-ai' ),
			array(
				'posts_post_type' => array( 'type' => 'string', 'enum' => array( 'post', 'page', 'any' ), 'description' => __( 'Post type to query.', 'mindcrafts-ai' ) ),
				'posts_per_page'  => array( 'type' => 'integer', 'description' => __( 'Number of posts to show.', 'mindcrafts-ai' ) ),
				'columns'         => array( 'type' => 'integer', 'description' => __( 'Number of grid columns.', 'mindcrafts-ai' ) ),
				'pagination_type' => array( 'type' => 'string', 'enum' => array( '', 'numbers', 'prev_next', 'numbers_and_prev_next', 'load_more_on_click' ), 'description' => __( 'Pagination type.', 'mindcrafts-ai' ) ),
			),
			array(),
			'posts',
			array( 'posts_post_type' => 'post', 'posts_per_page' => 6, 'columns' => 3 )
		);
	}

	private function register_add_countdown(): void {
		$this->register_convenience_tool(
			'add-countdown',
			__( 'Add Countdown (Pro)', 'mindcrafts-ai' ),
			__( 'Adds an Elementor Pro countdown timer widget.', 'mindcrafts-ai' ),
			array(
				'countdown_type' => array( 'type' => 'string', 'enum' => array( 'due_date', 'evergreen' ), 'description' => __( 'Countdown mode.', 'mindcrafts-ai' ) ),
				'due_date'       => array( 'type' => 'string', 'description' => __( 'Due date in Y-m-d H:i format.', 'mindcrafts-ai' ) ),
				'show_days'      => array( 'type' => 'string', 'enum' => array( 'yes', '' ), 'description' => __( 'Show days.', 'mindcrafts-ai' ) ),
				'show_hours'     => array( 'type' => 'string', 'enum' => array( 'yes', '' ), 'description' => __( 'Show hours.', 'mindcrafts-ai' ) ),
				'show_minutes'   => array( 'type' => 'string', 'enum' => array( 'yes', '' ), 'description' => __( 'Show minutes.', 'mindcrafts-ai' ) ),
				'show_seconds'   => array( 'type' => 'string', 'enum' => array( 'yes', '' ), 'description' => __( 'Show seconds.', 'mindcrafts-ai' ) ),
			),
			array(),
			'countdown',
			array(
				'countdown_type' => 'due_date',
				'show_days'      => 'yes',
				'show_hours'     => 'yes',
				'show_minutes'   => 'yes',
				'show_seconds'   => 'yes',
			)
		);
	}

	private function register_add_price_table(): void {
		$this->register_convenience_tool(
			'add-price-table',
			__( 'Add Price Table (Pro)', 'mindcrafts-ai' ),
			__( 'Adds an Elementor Pro price table widget for pricing page layouts.', 'mindcrafts-ai' ),
			array(
				'heading'         => array( 'type' => 'string', 'description' => __( 'Plan name/heading.', 'mindcrafts-ai' ) ),
				'sub_heading'     => array( 'type' => 'string', 'description' => __( 'Sub-heading text.', 'mindcrafts-ai' ) ),
				'currency_symbol' => array( 'type' => 'string', 'enum' => array( 'dollar', 'euro', 'pound', 'yen', 'custom' ), 'description' => __( 'Currency symbol preset.', 'mindcrafts-ai' ) ),
				'price'           => array( 'type' => 'string', 'description' => __( 'Price amount.', 'mindcrafts-ai' ) ),
				'period'          => array( 'type' => 'string', 'description' => __( 'Billing period (e.g. "/month").', 'mindcrafts-ai' ) ),
				'features_list'   => array( 'type' => 'array', 'description' => __( 'Feature list array.', 'mindcrafts-ai' ) ),
				'button_text'     => array( 'type' => 'string', 'description' => __( 'CTA button text.', 'mindcrafts-ai' ) ),
				'link'            => array( 'type' => 'object', 'description' => __( 'Button link object with url key.', 'mindcrafts-ai' ) ),
			),
			array( 'heading', 'price' ),
			'price-table',
			array( 'currency_symbol' => 'dollar', 'button_text' => 'Get Started' )
		);
	}

	private function register_add_flip_box(): void {
		$this->register_convenience_tool(
			'add-flip-box',
			__( 'Add Flip Box (Pro)', 'mindcrafts-ai' ),
			__( 'Adds an Elementor Pro flip box with front/back sides, icon, and animation effects.', 'mindcrafts-ai' ),
			array(
				'title_text_a'       => array( 'type' => 'string', 'description' => __( 'Front side title.', 'mindcrafts-ai' ) ),
				'description_text_a' => array( 'type' => 'string', 'description' => __( 'Front side description.', 'mindcrafts-ai' ) ),
				'title_text_b'       => array( 'type' => 'string', 'description' => __( 'Back side title.', 'mindcrafts-ai' ) ),
				'description_text_b' => array( 'type' => 'string', 'description' => __( 'Back side description.', 'mindcrafts-ai' ) ),
				'graphic_element'    => array( 'type' => 'string', 'enum' => array( 'none', 'image', 'icon' ), 'description' => __( 'Front graphic type.', 'mindcrafts-ai' ) ),
				'selected_icon'      => array( 'type' => 'object', 'description' => __( 'Icon object.', 'mindcrafts-ai' ) ),
				'button_text'        => array( 'type' => 'string', 'description' => __( 'Back button text.', 'mindcrafts-ai' ) ),
				'link'               => array( 'type' => 'object', 'description' => __( 'Link object with url key.', 'mindcrafts-ai' ) ),
				'flip_effect'        => array( 'type' => 'string', 'enum' => array( 'flip', 'slide', 'push', 'zoom-in', 'zoom-out', 'fade' ), 'description' => __( 'Flip animation effect.', 'mindcrafts-ai' ) ),
				'flip_direction'     => array( 'type' => 'string', 'enum' => array( 'left', 'right', 'up', 'down' ), 'description' => __( 'Flip direction.', 'mindcrafts-ai' ) ),
			),
			array( 'title_text_a' ),
			'flip-box',
			array( 'flip_effect' => 'flip', 'flip_direction' => 'left' )
		);
	}

	private function register_add_animated_headline(): void {
		$this->register_convenience_tool(
			'add-animated-headline',
			__( 'Add Animated Headline (Pro)', 'mindcrafts-ai' ),
			__( 'Adds an Elementor Pro animated headline with highlight or rotating text effects.', 'mindcrafts-ai' ),
			array(
				'headline_style'   => array( 'type' => 'string', 'enum' => array( 'highlight', 'rotate' ), 'description' => __( 'Headline animation style.', 'mindcrafts-ai' ) ),
				'animation_type'   => array( 'type' => 'string', 'enum' => array( 'typing', 'clip', 'flip', 'swirl', 'blinds', 'drop-in', 'wave', 'slide', 'slide-down' ), 'description' => __( 'Rotation animation type.', 'mindcrafts-ai' ) ),
				'marker'           => array( 'type' => 'string', 'enum' => array( 'circle', 'curly', 'underline', 'double', 'double_underline', 'underline_zigzag', 'diagonal', 'strikethrough', 'x' ), 'description' => __( 'Highlight marker style.', 'mindcrafts-ai' ) ),
				'before_text'      => array( 'type' => 'string', 'description' => __( 'Text before animated portion.', 'mindcrafts-ai' ) ),
				'highlighted_text' => array( 'type' => 'string', 'description' => __( 'Highlighted text (for highlight style).', 'mindcrafts-ai' ) ),
				'rotating_text'    => array( 'type' => 'string', 'description' => __( 'Line-separated rotating text entries.', 'mindcrafts-ai' ) ),
				'after_text'       => array( 'type' => 'string', 'description' => __( 'Text after animated portion.', 'mindcrafts-ai' ) ),
				'tag'              => array( 'type' => 'string', 'enum' => array( 'h1', 'h2', 'h3', 'h4', 'h5', 'h6' ), 'description' => __( 'HTML heading tag.', 'mindcrafts-ai' ) ),
			),
			array(),
			'animated-headline',
			array( 'headline_style' => 'highlight', 'tag' => 'h3' )
		);
	}
}
