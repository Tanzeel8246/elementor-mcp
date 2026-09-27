<?php
/**
 * Addon abilities for MindCrafts AI.
 *
 * Discovers and manages third-party Elementor addon widgets such as
 * ElementsKit, Essential Addons, Ultimate Addons, and others with full JSON schema.
 *
 * @package MindCrafts_AI
 * @since   2.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Exposes abilities to inspect and add third-party Elementor addon widgets.
 *
 * @since 2.0.0
 */
class MindCrafts_AI_Addon_Abilities {

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
	 * @param MindCrafts_AI_Element_Factory|null $factory Optional factory instance.
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
			'mindcrafts-ai/list-addon-widgets',
			'mindcrafts-ai/add-third-party-widget',
		);
	}

	/**
	 * Registers addon abilities.
	 *
	 * @since 2.0.0
	 */
	public function register(): void {
		wp_register_ability(
			'mindcrafts-ai/list-addon-widgets',
			array(
				'label'               => __( 'List Addon Widgets', 'mindcrafts-ai' ),
				'description'         => __( 'Lists all active third-party addon widgets (ElementsKit, Essential Addons, UAE, etc.) registered in Elementor, including their settings schemas.', 'mindcrafts-ai' ),
				'category'            => 'mindcrafts-ai',
				'execute_callback'    => array( $this, 'execute_list_addon_widgets' ),
				'permission_callback' => array( $this, 'check_permission' ),
				'input_schema'        => array(
					'type'       => 'object',
					'properties' => array(),
				),
				'output_schema'       => array(
					'type'       => 'object',
					'properties' => array(
						'addon_widgets' => array(
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
									'icon'       => array( 'type' => 'string' ),
									'schema'     => array( 'type' => 'object' ),
								),
							),
						),
					),
				),
			)
		);

		wp_register_ability(
			'mindcrafts-ai/add-third-party-widget',
			array(
				'label'               => __( 'Add 3rd-Party Widget', 'mindcrafts-ai' ),
				'description'         => __( 'Adds a third-party addon widget (ElementsKit, Essential Addons, UAE, etc.) to an Elementor container.', 'mindcrafts-ai' ),
				'category'            => 'mindcrafts-ai',
				'execute_callback'    => array( $this, 'execute_add_third_party_widget' ),
				'permission_callback' => array( $this, 'check_permission' ),
				'input_schema'        => array(
					'type'       => 'object',
					'properties' => array(
						'post_id'     => array(
							'type'        => 'integer',
							'description' => __( 'The post/page ID.', 'mindcrafts-ai' ),
						),
						'parent_id'   => array(
							'type'        => 'string',
							'description' => __( 'Parent container ID.', 'mindcrafts-ai' ),
						),
						'position'    => array(
							'type'        => 'integer',
							'description' => __( 'Insert position. -1 = append.', 'mindcrafts-ai' ),
						),
						'widget_type' => array(
							'type'        => 'string',
							'description' => __( 'The addon widget type name (e.g. ekit-heading, eael-creative-button).', 'mindcrafts-ai' ),
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
						'success'    => array( 'type' => 'boolean' ),
						'element_id' => array( 'type' => 'string' ),
					),
				),
			)
		);
	}

	/**
	 * Permission check for reading/writing addon widgets.
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
	 * Lists all third-party addon widgets with schema integration.
	 *
	 * @since 2.0.0
	 *
	 * @param mixed $input Optional input.
	 * @return array{addon_widgets: array<int, array<string, mixed>>}|\WP_Error
	 */
	public function execute_list_addon_widgets( $input = null ) {
		if ( ! class_exists( '\Elementor\Plugin' ) || empty( \Elementor\Plugin::$instance->widgets_manager ) ) {
			return array( 'addon_widgets' => array() );
		}

		$widgets       = \Elementor\Plugin::$instance->widgets_manager->get_widget_types();
		$addon_widgets = array();

		$core_categories = array( 'basic', 'pro', 'general', 'theme-elements', 'woocommerce', 'wordpress' );
		$known_addon_prefixes = array(
			'ekit-',       // ElementsKit
			'eael-',       // Essential Addons
			'uael-',       // Ultimate Addons
			'premium-',    // Premium Addons
			'jet-',        // Crocoblock/JetPlugins
			'happy-',      // Happy Addons
			'mighty-',     // Mighty Addons
			'sa-',         // Sky Addons
			'rael-',       // Responsive Addons
			'htmega-',     // HT Mega
			'pafe-',       // Piotnet Addons
			'wcf-',        // WooCustom Fields
			'anywhere-',   // Anywhere Elementor
			'avt-',        // Element Pack
			'prime-',      // Prime Slider
		);

		foreach ( $widgets as $name => $widget ) {
			$categories = is_callable( array( $widget, 'get_categories' ) ) ? $widget->get_categories() : array();
			$is_core    = false;
			foreach ( $categories as $cat ) {
				if ( in_array( $cat, $core_categories, true ) ) {
					$is_core = true;
					break;
				}
			}

			$name_lower = strtolower( $name );
			$is_addon   = false;

			foreach ( $known_addon_prefixes as $prefix ) {
				if ( 0 === strpos( $name_lower, $prefix ) ) {
					$is_addon = true;
					break;
				}
			}

			if ( ! $is_core ) {
				$is_addon = true;
			}

			if ( $is_addon ) {
				$addon_widgets[] = array(
					'name'       => $widget->get_name(),
					'title'      => $widget->get_title(),
					'categories' => $categories,
					'icon'       => is_callable( array( $widget, 'get_icon' ) ) ? $widget->get_icon() : '',
					'schema'     => $this->get_widget_schema( $widget->get_name() ),
				);
			}
		}

		return array( 'addon_widgets' => $addon_widgets );
	}

	/**
	 * Generates schema for a given widget type.
	 *
	 * @since 2.0.0
	 *
	 * @param string $widget_type The widget type name.
	 * @return array<string, mixed>
	 */
	private function get_widget_schema( string $widget_type ): array {
		if ( ! class_exists( 'MindCrafts_AI_Schema_Generator' ) ) {
			return array();
		}

		$schema_gen = new MindCrafts_AI_Schema_Generator();
		$schema     = $schema_gen->generate( $widget_type );
		return is_wp_error( $schema ) ? array() : $schema;
	}

	/**
	 * Adds a third-party addon widget to an element container.
	 *
	 * @since 2.0.0
	 *
	 * @param array $input Input parameters.
	 * @return array{success: bool, element_id: string}|\WP_Error
	 */
	public function execute_add_third_party_widget( $input ) {
		$post_id     = absint( $input['post_id'] ?? 0 );
		$parent_id   = sanitize_text_field( $input['parent_id'] ?? '' );
		$position    = intval( $input['position'] ?? -1 );
		$widget_type = sanitize_text_field( $input['widget_type'] ?? '' );
		$settings    = isset( $input['settings'] ) && is_array( $input['settings'] ) ? $input['settings'] : array();

		if ( ! $post_id || empty( $parent_id ) || empty( $widget_type ) ) {
			return new \WP_Error(
				'missing_params',
				__( 'post_id, parent_id, and widget_type are required.', 'mindcrafts-ai' )
			);
		}

		if ( ! class_exists( '\Elementor\Plugin' ) || empty( \Elementor\Plugin::$instance->widgets_manager ) ) {
			return new \WP_Error( 'elementor_not_ready', __( 'Elementor is not ready.', 'mindcrafts-ai' ) );
		}

		$widget_instance = \Elementor\Plugin::$instance->widgets_manager->get_widget_types( $widget_type );
		if ( ! $widget_instance ) {
			return new \WP_Error(
				'invalid_widget_type',
				sprintf(
					/* translators: %s: widget type */
					__( 'Widget type "%s" not found. Please ensure the addon plugin is active.', 'mindcrafts-ai' ),
					$widget_type
				)
			);
		}

		$page_data = $this->data->get_page_data( $post_id );
		if ( is_wp_error( $page_data ) ) {
			return $page_data;
		}

		$widget   = $this->factory->create_widget( $widget_type, $settings );
		$inserted = $this->data->insert_element( $page_data, $parent_id, $widget, $position );
		if ( ! $inserted ) {
			return new \WP_Error( 'parent_not_found', __( 'Parent container not found.', 'mindcrafts-ai' ) );
		}

		$result = $this->data->save_page_data( $post_id, $page_data );
		if ( is_wp_error( $result ) ) {
			return $result;
		}

		return array(
			'success'    => true,
			'element_id' => $widget['id'],
		);
	}
}
