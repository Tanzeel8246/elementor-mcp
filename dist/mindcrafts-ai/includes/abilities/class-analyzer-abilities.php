<?php
/**
 * Analyzer abilities for MindCrafts AI.
 *
 * @package MindCrafts_AI
 * @since   2.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class MindCrafts_AI_Analyzer_Abilities {

	/**
	 * Elementor data layer.
	 *
	 * @var MindCrafts_AI_Data
	 */
	private $data;

	public function __construct( MindCrafts_AI_Data $data ) {
		$this->data = $data;
	}

	/**
	 * Permission check for page analysis.
	 *
	 * @param array|null $input Tool input.
	 * @return bool
	 */
	public function check_read_permission( $input = null ): bool {
		if ( ! current_user_can( 'edit_posts' ) ) {
			return false;
		}

		$post_id = absint( $input['post_id'] ?? 0 );
		if ( $post_id && ! current_user_can( 'edit_post', $post_id ) ) {
			return false;
		}

		return true;
	}

	public function get_ability_names(): array {
		return array(
			'mindcrafts-ai/analyze-page',
		);
	}

	public function register(): void {
		wp_register_ability(
			'mindcrafts-ai/analyze-page',
			array(
				'label'               => __( 'Analyze Page', 'mindcrafts-ai' ),
				'description'         => __( 'Analyzes the current page structure and returns text content and SEO meta data.', 'mindcrafts-ai' ),
				'category'            => 'mindcrafts-ai',
				'execute_callback'    => array( $this, 'execute_analyze_page' ),
				'permission_callback' => array( $this, 'check_read_permission' ),
				'input_schema'        => array(
					'type'       => 'object',
					'properties' => array(
						'post_id' => array( 'type' => 'integer' ),
					),
					'required'   => array( 'post_id' ),
				),
				'output_schema'       => array(
					'type'       => 'object',
					'properties' => array(
						'text_content' => array( 'type' => 'array' ),
						'images'       => array( 'type' => 'array' ),
						'headings'     => array( 'type' => 'array' ),
						'seo_meta'     => array( 'type' => 'object' ),
						'element_count' => array( 'type' => 'integer' ),
					),
				),
			)
		);
	}

	public function execute_analyze_page( $input ) {
		$post_id = absint( $input['post_id'] ?? 0 );

		if ( ! $post_id ) {
			return new \WP_Error( 'missing_post_id', __( 'The post_id parameter is required.', 'mindcrafts-ai' ) );
		}

		$post = get_post( $post_id );
		if ( ! $post ) {
			return new \WP_Error( 'post_not_found', __( 'Post not found.', 'mindcrafts-ai' ) );
		}

		$page_data = $this->data->get_page_data( $post_id );
		if ( is_wp_error( $page_data ) ) {
			return $page_data;
		}

		$analysis = array(
			'text_content'  => array(),
			'images'        => array(),
			'headings'      => array(),
			'seo_meta'      => array(
				'title'                 => get_post_meta( $post_id, '_yoast_wpseo_title', true ),
				'description'           => get_post_meta( $post_id, '_yoast_wpseo_metadesc', true ),
				'rank_math_title'       => get_post_meta( $post_id, 'rank_math_title', true ),
				'rank_math_description' => get_post_meta( $post_id, 'rank_math_description', true ),
			),
			'element_count' => $this->data->count_elements( $page_data ),
		);

		$this->walk_elements( $page_data, $analysis );

		return array(
			'text_content'  => $analysis['text_content'],
			'images'        => $analysis['images'],
			'headings'      => $analysis['headings'],
			'seo_meta'      => $analysis['seo_meta'],
			'element_count' => $analysis['element_count'],
		);
	}

	/**
	 * Extract useful text, headings, and images from an Elementor tree.
	 *
	 * @param array $elements Element tree.
	 * @param array $analysis Analysis accumulator.
	 */
	private function walk_elements( array $elements, array &$analysis ): void {
		foreach ( $elements as $element ) {
			$settings    = $element['settings'] ?? array();
			$widget_type = $element['widgetType'] ?? '';
			$element_id  = $element['id'] ?? '';

			$this->extract_text_fields( $element_id, $widget_type, $settings, $analysis );
			$this->extract_images( $element_id, $widget_type, $settings, $analysis );

			if ( ! empty( $element['elements'] ) && is_array( $element['elements'] ) ) {
				$this->walk_elements( $element['elements'], $analysis );
			}
		}
	}

	/**
	 * Extract text-like widget settings.
	 *
	 * @param string $element_id  Element ID.
	 * @param string $widget_type Widget type.
	 * @param array  $settings    Element settings.
	 * @param array  $analysis    Analysis accumulator.
	 */
	private function extract_text_fields( string $element_id, string $widget_type, array $settings, array &$analysis ): void {
		$text_keys = array(
			'title',
			'editor',
			'text',
			'button_text',
			'title_text',
			'description_text',
			'title_text_a',
			'description_text_a',
			'title_text_b',
			'description_text_b',
			'before_text',
			'highlighted_text',
			'rotating_text',
			'after_text',
		);

		foreach ( $text_keys as $key ) {
			if ( empty( $settings[ $key ] ) || ! is_string( $settings[ $key ] ) ) {
				continue;
			}

			$text = trim( wp_strip_all_tags( $settings[ $key ] ) );
			if ( '' === $text ) {
				continue;
			}

			$item = array(
				'element_id'  => $element_id,
				'widget_type' => $widget_type,
				'field'       => $key,
				'text'        => $text,
			);

			$analysis['text_content'][] = $item;

			if ( 'heading' === $widget_type && 'title' === $key ) {
				$analysis['headings'][] = array(
					'element_id' => $element_id,
					'level'      => sanitize_key( $settings['header_size'] ?? 'h2' ),
					'text'       => $text,
				);
			}
		}
	}

	/**
	 * Extract image references from image-like widget settings.
	 *
	 * @param string $element_id  Element ID.
	 * @param string $widget_type Widget type.
	 * @param array  $settings    Element settings.
	 * @param array  $analysis    Analysis accumulator.
	 */
	private function extract_images( string $element_id, string $widget_type, array $settings, array &$analysis ): void {
		if ( ! isset( $settings['image'] ) || ! is_array( $settings['image'] ) ) {
			return;
		}

		$analysis['images'][] = array(
			'element_id'  => $element_id,
			'widget_type' => $widget_type,
			'id'          => absint( $settings['image']['id'] ?? 0 ),
			'url'         => esc_url_raw( $settings['image']['url'] ?? '' ),
			'alt'         => sanitize_text_field( $settings['alt_text'] ?? '' ),
			'caption'     => sanitize_text_field( $settings['caption'] ?? '' ),
		);
	}
}
