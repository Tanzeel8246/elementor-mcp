<?php
/**
 * Registers all MindCrafts AI abilities with the WordPress Abilities API.
 *
 * @package MindCrafts_AI
 * @since   1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Central registrar that coordinates registration of all ability groups.
 *
 * @since 1.0.0
 */
class MindCrafts_AI_Ability_Registrar {

	/**
	 * The data access layer.
	 *
	 * @var MindCrafts_AI_Data
	 */
	private MindCrafts_AI_Data $data;

	/**
	 * The element factory.
	 *
	 * @var MindCrafts_AI_Element_Factory
	 */
	private MindCrafts_AI_Element_Factory $factory;

	/**
	 * The schema generator.
	 *
	 * @var MindCrafts_AI_Schema_Generator
	 */
	private MindCrafts_AI_Schema_Generator $schema_generator;

	/**
	 * The settings validator.
	 *
	 * @var MindCrafts_AI_Settings_Validator
	 */
	private MindCrafts_AI_Settings_Validator $validator;

	/**
	 * All registered ability names.
	 *
	 * @var string[]
	 */
	private array $ability_names = array();

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
	 * Registers all abilities across all groups.
	 *
	 * Must be called during the `wp_abilities_api_init` action.
	 *
	 * @since 1.0.0
	 *
	 * @return string[] Array of registered ability names.
	 */
	public function register_all(): array {
		if ( ! empty( $this->ability_names ) ) {
			return $this->ability_names;
		}

		/**
		 * Filter enabled ability groups for performance and modular loading.
		 *
		 * @since 2.0.0
		 *
		 * @param string[] $enabled_groups List of active group identifiers.
		 */
		$enabled_groups = apply_filters(
			'mindcrafts_ai_enabled_groups',
			array(
				'query',
				'pages',
				'layout',
				'widgets',
				'templates',
				'globals',
				'composite',
				'stock_images',
				'analyzer',
				'clone',
				'bulk',
				'woocommerce',
				'addons',
				'responsive',
				'theme_builder',
				'popups',
				'dynamic_tags',
				'seo',
				'marketplace',
			)
		);

		// Phase 1: Query/discovery abilities (read-only).
		if ( in_array( 'query', $enabled_groups, true ) && class_exists( 'MindCrafts_AI_Query_Abilities' ) ) {
			$query = new MindCrafts_AI_Query_Abilities( $this->data, $this->schema_generator );
			$query->register();
			$this->ability_names = array_merge( $this->ability_names, $query->get_ability_names() );
		}

		// Phase 2: Page CRUD abilities.
		if ( in_array( 'pages', $enabled_groups, true ) && class_exists( 'MindCrafts_AI_Page_Abilities' ) ) {
			$pages = new MindCrafts_AI_Page_Abilities( $this->data, $this->factory );
			$pages->register();
			$this->ability_names = array_merge( $this->ability_names, $pages->get_ability_names() );
		}

		// Phase 2: Layout/container abilities.
		if ( in_array( 'layout', $enabled_groups, true ) && class_exists( 'MindCrafts_AI_Layout_Abilities' ) ) {
			$layout = new MindCrafts_AI_Layout_Abilities( $this->data, $this->factory );
			$layout->register();
			$this->ability_names = array_merge( $this->ability_names, $layout->get_ability_names() );
		}

		// Phase 3: Widget abilities — universal + convenience.
		if ( in_array( 'widgets', $enabled_groups, true ) && class_exists( 'MindCrafts_AI_Widget_Abilities' ) ) {
			$widgets = new MindCrafts_AI_Widget_Abilities( $this->data, $this->factory, $this->schema_generator, $this->validator );
			$widgets->register();
			$this->ability_names = array_merge( $this->ability_names, $widgets->get_ability_names() );
		}

		// Phase 4: Template abilities.
		if ( in_array( 'templates', $enabled_groups, true ) && class_exists( 'MindCrafts_AI_Template_Abilities' ) ) {
			$templates = new MindCrafts_AI_Template_Abilities( $this->data, $this->factory );
			$templates->register();
			$this->ability_names = array_merge( $this->ability_names, $templates->get_ability_names() );
		}

		// Phase 4: Global settings abilities.
		if ( in_array( 'globals', $enabled_groups, true ) && class_exists( 'MindCrafts_AI_Global_Abilities' ) ) {
			$globals = new MindCrafts_AI_Global_Abilities( $this->data );
			$globals->register();
			$this->ability_names = array_merge( $this->ability_names, $globals->get_ability_names() );
		}

		// Phase 5: Composite abilities.
		if ( in_array( 'composite', $enabled_groups, true ) && class_exists( 'MindCrafts_AI_Composite_Abilities' ) ) {
			$composite = new MindCrafts_AI_Composite_Abilities( $this->data, $this->factory );
			$composite->register();
			$this->ability_names = array_merge( $this->ability_names, $composite->get_ability_names() );
		}

		// Stock image abilities (search, sideload, add).
		if ( in_array( 'stock_images', $enabled_groups, true ) && class_exists( 'MindCrafts_AI_Stock_Image_Abilities' ) ) {
			$stock_images = new MindCrafts_AI_Stock_Image_Abilities( $this->data, $this->factory );
			$stock_images->register();
			$this->ability_names = array_merge( $this->ability_names, $stock_images->get_ability_names() );
		}

		// Phase 3: Free AI Features.
		if ( in_array( 'analyzer', $enabled_groups, true ) && class_exists( 'MindCrafts_AI_Analyzer_Abilities' ) ) {
			$analyzer = new MindCrafts_AI_Analyzer_Abilities( $this->data );
			$analyzer->register();
			$this->ability_names = array_merge( $this->ability_names, $analyzer->get_ability_names() );
		}

		if ( in_array( 'clone', $enabled_groups, true ) && class_exists( 'MindCrafts_AI_Clone_Abilities' ) ) {
			$clone = new MindCrafts_AI_Clone_Abilities( $this->data );
			$clone->register();
			$this->ability_names = array_merge( $this->ability_names, $clone->get_ability_names() );
		}

		if ( in_array( 'bulk', $enabled_groups, true ) && class_exists( 'MindCrafts_AI_Bulk_Abilities' ) ) {
			$bulk = new MindCrafts_AI_Bulk_Abilities( $this->data, $this->factory );
			$bulk->register();
			$this->ability_names = array_merge( $this->ability_names, $bulk->get_ability_names() );
		}

		// WooCommerce abilities.
		if ( in_array( 'woocommerce', $enabled_groups, true ) && class_exists( 'MindCrafts_AI_WooCommerce_Abilities' ) ) {
			$woocommerce = new MindCrafts_AI_WooCommerce_Abilities( $this->data, $this->factory );
			$woocommerce->register();
			$this->ability_names = array_merge( $this->ability_names, $woocommerce->get_ability_names() );
		}

		// Addon abilities (ElementsKit, Essential Addons, etc.).
		if ( in_array( 'addons', $enabled_groups, true ) && class_exists( 'MindCrafts_AI_Addon_Abilities' ) ) {
			$addon = new MindCrafts_AI_Addon_Abilities( $this->data, $this->factory );
			$addon->register();
			$this->ability_names = array_merge( $this->ability_names, $addon->get_ability_names() );
		}

		// Responsive abilities.
		if ( in_array( 'responsive', $enabled_groups, true ) && class_exists( 'MindCrafts_AI_Responsive_Abilities' ) ) {
			$responsive = new MindCrafts_AI_Responsive_Abilities( $this->data );
			$responsive->register();
			$this->ability_names = array_merge( $this->ability_names, $responsive->get_ability_names() );
		}

		// Elementor Pro Theme Builder abilities.
		if ( in_array( 'theme_builder', $enabled_groups, true ) && class_exists( 'MindCrafts_AI_Theme_Builder_Abilities' ) ) {
			$theme_builder = new MindCrafts_AI_Theme_Builder_Abilities( $this->data, $this->factory );
			$theme_builder->register();
			$this->ability_names = array_merge( $this->ability_names, $theme_builder->get_ability_names() );
		}

		// Elementor Pro Popup abilities.
		if ( in_array( 'popups', $enabled_groups, true ) && class_exists( 'MindCrafts_AI_Popup_Abilities' ) ) {
			$popups = new MindCrafts_AI_Popup_Abilities( $this->data, $this->factory );
			$popups->register();
			$this->ability_names = array_merge( $this->ability_names, $popups->get_ability_names() );
		}

		// Dynamic Tags abilities.
		if ( in_array( 'dynamic_tags', $enabled_groups, true ) && class_exists( 'MindCrafts_AI_Dynamic_Tags_Abilities' ) ) {
			$dynamic_tags = new MindCrafts_AI_Dynamic_Tags_Abilities( $this->data );
			$dynamic_tags->register();
			$this->ability_names = array_merge( $this->ability_names, $dynamic_tags->get_ability_names() );
		}

		// SEO and Marketplace abilities (Registered only when Premium license is active).
		$license = MindCrafts_AI_License_Manager::instance();
		if ( $license->is_premium_active() ) {
			if ( in_array( 'seo', $enabled_groups, true ) && class_exists( 'MindCrafts_AI_SEO_Abilities' ) ) {
				$seo = new MindCrafts_AI_SEO_Abilities( $this->data );
				$seo->register();
				$this->ability_names = array_merge( $this->ability_names, $seo->get_ability_names() );
			}

			if ( in_array( 'marketplace', $enabled_groups, true ) && class_exists( 'MindCrafts_AI_Marketplace_Abilities' ) ) {
				$marketplace = new MindCrafts_AI_Marketplace_Abilities( $this->data );
				$marketplace->register();
				$this->ability_names = array_merge( $this->ability_names, $marketplace->get_ability_names() );
			}
		}

		/**
		 * Filters the registered ability names.
		 *
		 * Allows other plugins to add or modify ability names.
		 *
		 * @since 1.0.0
		 *
		 * @param string[] $ability_names The registered ability names.
		 */
		$this->ability_names = apply_filters( 'mindcrafts_ai_ability_names', $this->ability_names );

		return $this->ability_names;
	}

	/**
	 * Gets the list of registered ability names.
	 *
	 * @since 1.0.0
	 *
	 * @return string[] Array of ability names.
	 */
	public function get_ability_names(): array {
		return $this->ability_names;
	}
}
