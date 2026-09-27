<?php
/**
 * Bulk Update abilities for MindCrafts AI.
 *
 * @package MindCrafts_AI
 * @since   2.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class MindCrafts_AI_Bulk_Abilities {

	/**
	 * Elementor data layer.
	 *
	 * @var MindCrafts_AI_Data
	 */
	private $data;

	/**
	 * Element factory.
	 *
	 * @var MindCrafts_AI_Element_Factory|null
	 */
	private $factory;

	public function __construct( MindCrafts_AI_Data $data, MindCrafts_AI_Element_Factory $factory = null ) {
		$this->data    = $data;
		$this->factory = $factory;
	}

	/**
	 * Permission check for bulk updates.
	 *
	 * @param array|null $input Tool input.
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

	public function get_ability_names(): array {
		return array(
			'mindcrafts-ai/bulk-update-colors',
			'mindcrafts-ai/bulk-update-typography',
		);
	}

	public function register(): void {
		wp_register_ability(
			'mindcrafts-ai/bulk-update-colors',
			array(
				'label'               => __( 'Bulk Update Colors', 'mindcrafts-ai' ),
				'description'         => __( 'Finds and replaces a specific color across all widgets in a page.', 'mindcrafts-ai' ),
				'category'            => 'mindcrafts-ai',
				'execute_callback'    => array( $this, 'execute_bulk_update_colors' ),
				'permission_callback' => array( $this, 'check_edit_permission' ),
				'input_schema'        => array(
					'type'       => 'object',
					'properties' => array(
						'post_id'   => array( 'type' => 'integer' ),
						'old_color' => array( 'type' => 'string' ),
						'new_color' => array( 'type' => 'string' ),
					),
					'required'   => array( 'post_id', 'old_color', 'new_color' ),
				),
				'output_schema'       => array(
					'type'       => 'object',
					'properties' => array(
						'success'       => array( 'type' => 'boolean' ),
						'updated_count' => array( 'type' => 'integer' ),
					),
				),
			)
		);

		wp_register_ability(
			'mindcrafts-ai/bulk-update-typography',
			array(
				'label'               => __( 'Bulk Update Typography', 'mindcrafts-ai' ),
				'description'         => __( 'Finds and replaces a font family across all Elementor settings in a page.', 'mindcrafts-ai' ),
				'category'            => 'mindcrafts-ai',
				'execute_callback'    => array( $this, 'execute_bulk_update_typography' ),
				'permission_callback' => array( $this, 'check_edit_permission' ),
				'input_schema'        => array(
					'type'       => 'object',
					'properties' => array(
						'post_id'  => array( 'type' => 'integer' ),
						'old_font' => array( 'type' => 'string' ),
						'new_font' => array( 'type' => 'string' ),
					),
					'required'   => array( 'post_id', 'old_font', 'new_font' ),
				),
				'output_schema'       => array(
					'type'       => 'object',
					'properties' => array(
						'success'       => array( 'type' => 'boolean' ),
						'updated_count' => array( 'type' => 'integer' ),
					),
				),
			)
		);
	}

	public function execute_bulk_update_colors( $input ) {
		$post_id   = absint( $input['post_id'] ?? 0 );
		$old_color = sanitize_hex_color( $input['old_color'] ?? '' );
		$new_color = sanitize_hex_color( $input['new_color'] ?? '' );

		if ( ! $post_id || empty( $old_color ) || empty( $new_color ) ) {
			return new \WP_Error( 'missing_params', __( 'post_id, old_color, and new_color are required. Colors must be valid hex values.', 'mindcrafts-ai' ) );
		}

		return $this->replace_in_page_data( $post_id, $old_color, $new_color, true );
	}

	/**
	 * Executes the bulk typography update ability.
	 *
	 * @param array $input Tool input.
	 * @return array|\WP_Error
	 */
	public function execute_bulk_update_typography( $input ) {
		$post_id  = absint( $input['post_id'] ?? 0 );
		$old_font = sanitize_text_field( $input['old_font'] ?? '' );
		$new_font = sanitize_text_field( $input['new_font'] ?? '' );

		if ( ! $post_id || empty( $old_font ) || empty( $new_font ) ) {
			return new \WP_Error( 'missing_params', __( 'post_id, old_font, and new_font are required.', 'mindcrafts-ai' ) );
		}

		return $this->replace_in_page_data( $post_id, $old_font, $new_font, false );
	}

	/**
	 * Replaces scalar values inside a page's Elementor JSON tree.
	 *
	 * @param int    $post_id          Post ID.
	 * @param string $old_value        Value to replace.
	 * @param string $new_value        Replacement value.
	 * @param bool   $case_insensitive Whether to replace case-insensitively.
	 * @return array|\WP_Error
	 */
	private function replace_in_page_data( int $post_id, string $old_value, string $new_value, bool $case_insensitive ) {
		$page_data = $this->data->get_page_data( $post_id );
		if ( is_wp_error( $page_data ) ) {
			return $page_data;
		}

		$count = 0;
		$this->replace_recursive( $page_data, $old_value, $new_value, $count, $case_insensitive );

		if ( 0 === $count ) {
			return array(
				'success'       => true,
				'updated_count' => 0,
			);
		}

		$result = $this->data->save_page_data( $post_id, $page_data );
		if ( is_wp_error( $result ) ) {
			return $result;
		}

		return array(
			'success'       => true,
			'updated_count' => $count,
		);
	}

	/**
	 * Recursively replace scalar string values.
	 *
	 * @param mixed  $value            Current value.
	 * @param string $old_value        Value to replace.
	 * @param string $new_value        Replacement value.
	 * @param int    $count            Replacement counter.
	 * @param bool   $case_insensitive Whether to replace case-insensitively.
	 */
	private function replace_recursive( &$value, string $old_value, string $new_value, int &$count, bool $case_insensitive ): void {
		if ( is_array( $value ) ) {
			foreach ( $value as &$child ) {
				$this->replace_recursive( $child, $old_value, $new_value, $count, $case_insensitive );
			}
			return;
		}

		if ( ! is_string( $value ) || '' === $value ) {
			return;
		}

		$updated = $case_insensitive ? str_ireplace( $old_value, $new_value, $value, $replacements ) : str_replace( $old_value, $new_value, $value, $replacements );
		if ( $replacements > 0 ) {
			$value  = $updated;
			$count += $replacements;
		}
	}
}
