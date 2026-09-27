<?php
/**
 * Elementor عناصر کی JSON ساختوں کی فیکٹری۔
 *
 * مرحلہ 2 اپگریڈ (v2.3.0):
 * - خودکار موبائل و ٹیبلٹ رسپانسو ڈیفالٹس۔
 * - رو کنٹینرز موبائل پر خودکار کالم میں تبدیل۔
 * - ہیڈنگ وزٹس کا خودکار موبائل فونٹ سائز۔
 *
 * @package MindCrafts_AI
 * @since   1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Elementor عناصر کے صحیح JSON آرے بناتا ہے۔
 *
 * @since 1.0.0
 */
class MindCrafts_AI_Element_Factory {

	/**
	 * موبائل پیڈنگ کی حد — اس سے زیادہ ڈیسک ٹاپ پیڈنگ ہو تو موبائل ڈیفالٹس لاگو ہوں گے۔
	 *
	 * @since 2.3.0
	 */
	const MOBILE_PADDING_THRESHOLD = 40;

	/**
	 * کنٹینر عنصر بناتا ہے — خودکار رسپانسو ڈیفالٹس کے ساتھ۔
	 *
	 * @since 1.0.0
	 *
	 * @param array $settings کنٹینر کی سیٹنگز۔
	 * @param array $children بچوں کے عناصر کی صف۔
	 * @return array کنٹینر کا مکمل عنصر ڈھانچہ۔
	 */
	public function create_container( array $settings = array(), array $children = array() ): array {
		$defaults = array(
			'container_type' => 'flex',
			'content_width'  => 'boxed',
		);

		$merged = array_merge( $defaults, $settings );

		// رو کنٹینرز میں nowrap لگائیں تاکہ AI کی ترتیب خراب نہ ہو۔
		$direction = $merged['flex_direction'] ?? '';
		if ( 'row' === $direction || 'row-reverse' === $direction ) {
			if ( ! isset( $settings['flex_wrap'] ) ) {
				$merged['flex_wrap'] = 'nowrap';
			}

			// ——————————————————————————————————————————————————————————
			// مرحلہ 2 اپگریڈ: موبائل رسپانسو خودکار انجیکشن
			// ——————————————————————————————————————————————————————————

			// موبائل پر عمودی ترتیب (column) خودکار لگائیں۔
			if ( ! isset( $merged['flex_direction_mobile'] ) ) {
				$merged['flex_direction_mobile'] = 'column';
			}

			// موبائل پر flex-wrap: wrap تاکہ اوور فلو نہ ہو۔
			if ( ! isset( $merged['flex_wrap_mobile'] ) ) {
				$merged['flex_wrap_mobile'] = 'wrap';
			}

			// ٹیبلٹ پر بھی column لگائیں اگر نہیں دی گئی۔
			if ( ! isset( $merged['flex_direction_tablet'] ) ) {
				$merged['flex_direction_tablet'] = 'column';
			}
		}

		// موبائل پیڈنگ خودکار انجیکشن — اگر بڑی ڈیسک ٹاپ پیڈنگ ہو۔
		$merged = $this->inject_responsive_padding( $merged );

		return array(
			'id'         => MindCrafts_AI_Id_Generator::generate(),
			'elType'     => 'container',
			'widgetType' => null,
			'isInner'    => false,
			'settings'   => $merged,
			'elements'   => $children,
		);
	}

	/**
	 * وزٹ عنصر بناتا ہے — ہیڈنگ کے لیے خودکار موبائل فونٹ سائز۔
	 *
	 * @since 1.0.0
	 *
	 * @param string $widget_type وزٹ کی قسم (مثلاً 'heading', 'button')۔
	 * @param array  $settings    وزٹ کی سیٹنگز۔
	 * @return array وزٹ کا مکمل عنصر ڈھانچہ۔
	 */
	public function create_widget( string $widget_type, array $settings = array() ): array {
		// مرحلہ 2 اپگریڈ: ہیڈنگ وزٹس کا خودکار موبائل فونٹ سائز۔
		if ( 'heading' === $widget_type ) {
			$settings = $this->inject_heading_responsive_font( $settings );
		}

		// text-editor وزٹس کے لیے بھی رسپانسو فونٹ سائز۔
		if ( 'text-editor' === $widget_type ) {
			$settings = $this->inject_text_responsive_font( $settings );
		}

		return array(
			'id'         => MindCrafts_AI_Id_Generator::generate(),
			'elType'     => 'widget',
			'widgetType' => $widget_type,
			'isInner'    => false,
			'settings'   => $settings,
			'elements'   => array(),
		);
	}

	/**
	 * سیکشن عنصر بناتا ہے (قدیم لے آؤٹ)۔
	 *
	 * @since 1.0.0
	 *
	 * @param array $settings سیکشن کی سیٹنگز۔
	 * @param array $columns  بچے کالم عناصر۔
	 * @return array سیکشن کا مکمل عنصر ڈھانچہ۔
	 */
	public function create_section( array $settings = array(), array $columns = array() ): array {
		return array(
			'id'         => MindCrafts_AI_Id_Generator::generate(),
			'elType'     => 'section',
			'widgetType' => null,
			'isInner'    => false,
			'settings'   => $settings,
			'elements'   => $columns,
		);
	}

	/**
	 * کالم عنصر بناتا ہے (قدیم لے آؤٹ)۔
	 *
	 * @since 1.0.0
	 *
	 * @param array $settings کالم کی سیٹنگز۔
	 * @param array $widgets  بچے وزٹ عناصر۔
	 * @return array کالم کا مکمل عنصر ڈھانچہ۔
	 */
	public function create_column( array $settings = array(), array $widgets = array() ): array {
		$defaults = array(
			'_column_size' => 100,
		);

		return array(
			'id'         => MindCrafts_AI_Id_Generator::generate(),
			'elType'     => 'column',
			'widgetType' => null,
			'isInner'    => false,
			'settings'   => array_merge( $defaults, $settings ),
			'elements'   => $widgets,
		);
	}

	// =========================================================================
	// مرحلہ 2 کے نجی مددگار میتھڈز
	// =========================================================================

	/**
	 * کنٹینر کی سیٹنگز میں خودکار موبائل پیڈنگ انجیکٹ کرتا ہے۔
	 *
	 * @since 2.3.0
	 *
	 * @param array $settings موجودہ سیٹنگز۔
	 * @return array اپڈیٹڈ سیٹنگز۔
	 */
	private function inject_responsive_padding( array $settings ): array {
		// اگر موبائل پیڈنگ پہلے سے موجود ہے تو چھوڑ دیں۔
		if ( isset( $settings['padding_mobile'] ) ) {
			return $settings;
		}

		// ڈیسک ٹاپ پیڈنگ کی سب سے بڑی ویلیو نکالیں۔
		$desktop_padding = $settings['padding'] ?? array();
		$max_padding     = 0;

		if ( is_array( $desktop_padding ) ) {
			foreach ( array( 'top', 'right', 'bottom', 'left' ) as $side ) {
				$val = absint( $desktop_padding[ $side ] ?? 0 );
				if ( $val > $max_padding ) {
					$max_padding = $val;
				}
			}
		}

		// اگر پیڈنگ حد سے زیادہ ہے تو موبائل پیڈنگ لگائیں۔
		if ( $max_padding >= self::MOBILE_PADDING_THRESHOLD ) {
			$settings['padding_mobile'] = array(
				'unit'     => 'px',
				'top'      => '20',
				'right'    => '15',
				'bottom'   => '20',
				'left'     => '15',
				'isLinked' => false,
			);

			// ٹیبلٹ پیڈنگ (درمیانی قدر)۔
			if ( ! isset( $settings['padding_tablet'] ) ) {
				$settings['padding_tablet'] = array(
					'unit'     => 'px',
					'top'      => '30',
					'right'    => '20',
					'bottom'   => '30',
					'left'     => '20',
					'isLinked' => false,
				);
			}
		}

		return $settings;
	}

	/**
	 * ہیڈنگ وزٹ میں خودکار موبائل فونٹ سائز انجیکٹ کرتا ہے۔
	 *
	 * @since 2.3.0
	 *
	 * @param array $settings موجودہ ہیڈنگ سیٹنگز۔
	 * @return array اپڈیٹڈ سیٹنگز۔
	 */
	private function inject_heading_responsive_font( array $settings ): array {
		// اگر ڈیسک ٹاپ فونٹ سائز موجود ہے اور موبائل نہیں۔
		if ( isset( $settings['typography_font_size'] ) && ! isset( $settings['typography_font_size_mobile'] ) ) {
			$desktop_size = absint( $settings['typography_font_size']['size'] ?? 0 );

			if ( $desktop_size >= 36 ) {
				// بڑے ہیڈنگز کے لیے موبائل سائز۔
				$mobile_size = max( 26, (int) round( $desktop_size * 0.7 ) );
				$mobile_size = min( $mobile_size, 32 ); // زیادہ سے زیادہ 32px۔

				$settings['typography_font_size_mobile'] = array(
					'size' => (string) $mobile_size,
					'unit' => $settings['typography_font_size']['unit'] ?? 'px',
				);
			} elseif ( $desktop_size >= 24 ) {
				// درمیانے ہیڈنگز۔
				$settings['typography_font_size_mobile'] = array(
					'size' => (string) max( 20, (int) round( $desktop_size * 0.85 ) ),
					'unit' => $settings['typography_font_size']['unit'] ?? 'px',
				);
			}
		}

		// مرکز سے بائیں سیدھ — موبائل پر زیادہ مناسب۔
		if ( isset( $settings['align'] ) && 'center' === $settings['align'] ) {
			if ( ! isset( $settings['align_mobile'] ) ) {
				$settings['align_mobile'] = 'center'; // موبائل پر بھی مرکز رکھیں۔
			}
		}

		return $settings;
	}

	/**
	 * ٹیکسٹ ایڈیٹر وزٹ میں خودکار موبائل فونٹ سائز انجیکٹ کرتا ہے۔
	 *
	 * @since 2.3.0
	 *
	 * @param array $settings موجودہ ٹیکسٹ ایڈیٹر سیٹنگز۔
	 * @return array اپڈیٹڈ سیٹنگز۔
	 */
	private function inject_text_responsive_font( array $settings ): array {
		if ( isset( $settings['typography_font_size'] ) && ! isset( $settings['typography_font_size_mobile'] ) ) {
			$desktop_size = absint( $settings['typography_font_size']['size'] ?? 0 );
			if ( $desktop_size >= 20 ) {
				$settings['typography_font_size_mobile'] = array(
					'size' => (string) max( 16, (int) round( $desktop_size * 0.85 ) ),
					'unit' => $settings['typography_font_size']['unit'] ?? 'px',
				);
			}
		}

		return $settings;
	}
}
