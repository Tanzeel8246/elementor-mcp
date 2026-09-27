<?php
/**
 * Elementor کنٹرول سکیمز کے خلاف وزٹ سیٹنگز کی تصدیق کرتا ہے۔
 *
 * مرحلہ 5 اپگریڈ (v2.3.0):
 * - گلوبل Elementor کنٹرولز (underscore keys) کو الاؤ لسٹ میں ڈالا۔
 * - رسپانسو سیفکسز (_mobile, _tablet, _laptop) کو اجازت دی۔
 * - فلیکس باکس کنٹینر کنٹرولز کو اجازت دی۔
 *
 * @package MindCrafts_AI
 * @since   1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * وزٹ سیٹنگز کو ان کے کنٹرول تعریفات کے خلاف تصدیق کرتا ہے۔
 *
 * @since 1.0.0
 */
class MindCrafts_AI_Settings_Validator {

	/**
	 * سکیما جنریٹر کا نمونہ۔
	 *
	 * @var MindCrafts_AI_Schema_Generator
	 */
	private $schema_generator;

	/**
	 * ہمیشہ اجازت یافتہ Elementor گلوبل کنٹرول keys۔
	 * یہ سکیما میں نہ ہوں تب بھی جائز ہیں۔
	 *
	 * @since 2.3.0
	 * @var string[]
	 */
	private const GLOBAL_ALLOWED_KEYS = array(
		// Elementor Advanced Tab — تمام وزٹس میں مشترک۔
		'_margin',
		'_padding',
		'_element_width',
		'_element_custom_width',
		'_position',
		'_offset_orientation_h',
		'_offset_x',
		'_offset_x_end',
		'_offset_orientation_v',
		'_offset_y',
		'_offset_y_end',
		'_z_index',
		'_element_id',
		'_css_classes',
		// Animation.
		'_animation',
		'_animation_duration',
		'_animation_delay',
		'_transform_rotate_popover',
		'_transform_rotateZ_effect',
		'_transform_scale_popover',
		'_transform_scale_effect',
		'_transform_translate_popover',
		'_transform_translateX_effect',
		'_transform_translateY_effect',
		// Responsive visibility.
		'hide_desktop',
		'hide_tablet',
		'hide_mobile',
		// Flexbox Container-level.
		'flex_direction',
		'flex_wrap',
		'justify_content',
		'align_items',
		'align_content',
		'gap',
		'content_width',
		'container_type',
		'flex_direction_mobile',
		'flex_direction_tablet',
		'flex_wrap_mobile',
		'flex_wrap_tablet',
		'justify_content_mobile',
		'justify_content_tablet',
		'align_items_mobile',
		'align_items_tablet',
		'gap_mobile',
		'gap_tablet',
		'padding',
		'padding_mobile',
		'padding_tablet',
		'margin',
		'margin_mobile',
		'margin_tablet',
		// Background.
		'background_background',
		'background_color',
		'background_image',
		'background_position',
		'background_repeat',
		'background_size',
		'background_overlay_color',
		// Border.
		'border_border',
		'border_width',
		'border_color',
		'border_radius',
		// Typography responsive.
		'typography_font_size_mobile',
		'typography_font_size_tablet',
		'typography_line_height_mobile',
		'typography_line_height_tablet',
		// Sizing.
		'min_height',
		'min_height_mobile',
		'min_height_tablet',
		'width',
		'width_mobile',
		'width_tablet',
	);

	/**
	 * رسپانسو سیفکسز جو ہمیشہ جائز ہیں۔
	 *
	 * @since 2.3.0
	 * @var string[]
	 */
	private const RESPONSIVE_SUFFIXES = array(
		'_mobile',
		'_tablet',
		'_laptop',
		'_widescreen',
	);

	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 *
	 * @param MindCrafts_AI_Schema_Generator $schema_generator سکیما جنریٹر۔
	 */
	public function __construct( MindCrafts_AI_Schema_Generator $schema_generator ) {
		$this->schema_generator = $schema_generator;
	}

	/**
	 * وزٹ کی سیٹنگز کی تصدیق کرتا ہے۔
	 *
	 * @since 1.0.0
	 *
	 * @param string $widget_type وزٹ کی قسم۔
	 * @param array  $settings    تصدیق کے لیے سیٹنگز۔
	 * @return true|\WP_Error درست ہو تو true، نہ ہو تو WP_Error۔
	 */
	public function validate( string $widget_type, array $settings ) {
		$schema = $this->schema_generator->generate( $widget_type );

		if ( is_wp_error( $schema ) ) {
			return $schema;
		}

		$valid_keys = array_keys( $schema['properties'] ?? array() );

		foreach ( array_keys( $settings ) as $key ) {
			// الاؤ لسٹ چیک: گلوبل Elementor کنٹرولز۔
			if ( in_array( $key, self::GLOBAL_ALLOWED_KEYS, true ) ) {
				continue;
			}

			// انڈر اسکور سے شروع ہونے والی تمام keys — Elementor ایڈوانسڈ کنٹرولز۔
			if ( '_' === substr( $key, 0, 1 ) ) {
				continue;
			}

			// رسپانسو سیفکسز چیک کریں۔
			if ( $this->has_responsive_suffix( $key ) ) {
				continue;
			}

			// وزٹ کی اپنی سکیما keys میں چیک کریں۔
			if ( in_array( $key, $valid_keys, true ) ) {
				continue;
			}

			// ناجائز key ملی۔
			return new \WP_Error(
				'invalid_setting',
				sprintf(
					/* translators: 1: setting key, 2: widget type */
					__( 'Setting "%1$s" is not a valid control for widget type "%2$s".', 'mindcrafts-ai' ),
					$key,
					$widget_type
				)
			);
		}

		return true;
	}

	/**
	 * جانچتا ہے کہ key میں رسپانسو سیفکس ہے یا نہیں۔
	 *
	 * @since 2.3.0
	 *
	 * @param string $key سیٹنگ key۔
	 * @return bool
	 */
	private function has_responsive_suffix( string $key ): bool {
		foreach ( self::RESPONSIVE_SUFFIXES as $suffix ) {
			if ( str_ends_with( $key, $suffix ) ) {
				return true;
			}
		}

		return false;
	}
}
