<?php
/**
 * Auto-generates JSON Schema from Elementor widget control definitions.
 *
 * T2-1: Adds Transients API caching so schema generation (which involves
 * parsing Elementor's entire control registry) only runs once per widget
 * per day instead of on every MCP request.
 *
 * @package MindCrafts_AI
 * @since   1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Generates JSON Schema for widget settings based on Elementor's control registry.
 *
 * @since 1.0.0
 */
class MindCrafts_AI_Schema_Generator {

	/**
	 * Transient cache prefix for widget schemas.
	 *
	 * @since 2.1.0
	 */
	const CACHE_PREFIX = 'mindcrafts_schema_';

	/**
	 * How long to cache schemas in seconds (24 hours).
	 *
	 * @since 2.1.0
	 */
	const CACHE_TTL = DAY_IN_SECONDS;

	/**
	 * In-memory cache for this request (avoids hitting DB multiple times per request).
	 *
	 * @var array<string, array>
	 */
	private $runtime_cache = array();

	/**
	 * Generates a JSON Schema for a widget type's settings.
	 *
	 * Results are cached in WordPress transients (24h) and in-memory for
	 * the current request to avoid redundant Elementor control parsing.
	 *
	 * @since 1.0.0
	 *
	 * @param string $widget_type The widget type name (e.g. 'heading', 'button').
	 * @return array|\WP_Error JSON Schema array on success, WP_Error if widget not found.
	 */
	public function generate( string $widget_type ) {
		// 1. Check in-memory cache first (same request, multiple calls).
		if ( isset( $this->runtime_cache[ $widget_type ] ) ) {
			return $this->runtime_cache[ $widget_type ];
		}

		// 2. Check WordPress transient cache.
		$elementor_ver = defined( 'ELEMENTOR_VERSION' ) ? ELEMENTOR_VERSION : '3.0.0';
		$cache_key     = self::CACHE_PREFIX . md5( $widget_type . $elementor_ver );
		$cached_value  = get_transient( $cache_key );
		if ( false !== $cached_value && is_array( $cached_value ) ) {
			$this->runtime_cache[ $widget_type ] = $cached_value;
			return $cached_value;
		}

		// 3. Generate fresh schema.
		if ( ! class_exists( '\Elementor\Plugin' ) || ! isset( \Elementor\Plugin::$instance ) || ! isset( \Elementor\Plugin::$instance->widgets_manager ) ) {
			return new \WP_Error(
				'elementor_not_ready',
				__( 'Elementor widgets manager is not loaded or ready.', 'mindcrafts-ai' )
			);
		}

		$widgets_manager = \Elementor\Plugin::$instance->widgets_manager;
		$widget          = $widgets_manager->get_widget_types( $widget_type );

		if ( ! $widget ) {
			return new \WP_Error(
				'widget_not_found',
				sprintf(
					/* translators: %s: widget type name */
					__( 'Widget type "%s" not found.', 'mindcrafts-ai' ),
					$widget_type
				)
			);
		}

		$controls   = $widget->get_controls();
		$properties = array();

		if ( is_array( $controls ) ) {
			foreach ( $controls as $control_id => $control ) {
				$control_type = $control['type'] ?? '';

				if ( MindCrafts_AI_Control_Mapper::should_skip( $control_type ) ) {
					continue;
				}

				$schema_fragment = MindCrafts_AI_Control_Mapper::map( $control );
				if ( ! empty( $schema_fragment ) ) {
					$properties[ $control_id ] = $schema_fragment;
				}
			}
		}

		$schema = array(
			'type'        => 'object',
			'description' => sprintf(
				/* translators: %s: widget title */
				__( 'Settings for the %s widget.', 'mindcrafts-ai' ),
				$widget->get_title()
			),
			'properties'  => $properties,
		);

		// 4. ٹرانزینٹ کیش اور ان میموری کیش میں محفوظ کریں۔
		set_transient( $cache_key, $schema, self::CACHE_TTL );
		$this->track_cache_key( $cache_key );
		$this->runtime_cache[ $widget_type ] = $schema;

		return $schema;
	}

	/**
	 * Generates schemas for all registered widgets.
	 *
	 * @since 1.0.0
	 *
	 * @return array Associative array of widget_type => JSON Schema.
	 */
	public function generate_all(): array {
		$widgets_manager = \Elementor\Plugin::$instance->widgets_manager;
		$widgets         = $widgets_manager->get_widget_types();
		$schemas         = array();

		foreach ( $widgets as $name => $widget ) {
			$schema = $this->generate( $name );
			if ( ! is_wp_error( $schema ) ) {
				$schemas[ $name ] = $schema;
			}
		}

		return $schemas;
	}

	/**
	 * Clears all cached schemas from transients.
	 *
	 * Call this when Elementor is updated or widgets are registered/deregistered.
	 *
	 * @since 2.1.0
	 */
	public function clear_cache(): void {
		// ان میموری کیش فوری صاف کریں۔
		$this->runtime_cache = array();

		// مرحلہ 5 اپگریڈ: ڈائریکٹ SQL کے بجائے ٹرانزینٹ انڈیکس استعمال کریں
		// تاکہ Redis/Memcached/WP VIP ماحول میں بھی صحیح کام کرے۔
		$tracked_keys = get_option( '_mindcrafts_cached_widget_keys', array() );

		if ( is_array( $tracked_keys ) ) {
			foreach ( $tracked_keys as $transient_key ) {
				delete_transient( $transient_key );
			}
		}

		// انڈیکس بھی صاف کریں۔
		delete_option( '_mindcrafts_cached_widget_keys' );
	}

	/**
	 * ٹرانزینٹ key کو انڈیکس میں محفوظ کرتا ہے تاکہ clear_cache میں SQL کی ضرورت نہ رہے۔
	 *
	 * @since 2.3.0
	 *
	 * @param string $cache_key محفوظ کی جانے والی key۔
	 */
	private function track_cache_key( string $cache_key ): void {
		$tracked = get_option( '_mindcrafts_cached_widget_keys', array() );

		if ( ! is_array( $tracked ) ) {
			$tracked = array();
		}

		if ( ! in_array( $cache_key, $tracked, true ) ) {
			$tracked[] = $cache_key;
			// زیادہ سے زیادہ 500 keys رکھیں — پرانی ہٹائیں۔
			if ( count( $tracked ) > 500 ) {
				$tracked = array_slice( $tracked, -500 );
			}

			update_option( '_mindcrafts_cached_widget_keys', $tracked, false );
		}
	}
}

