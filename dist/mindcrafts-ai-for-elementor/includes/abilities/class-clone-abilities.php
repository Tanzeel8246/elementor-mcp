<?php
/**
 * Smart Clone abilities for MindCrafts AI.
 *
 * @package MindCrafts_AI
 * @since   2.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class MindCrafts_AI_Clone_Abilities {

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
	 * Permission check for cloning.
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
			'mindcrafts-ai/smart-clone',
		);
	}

	public function register(): void {
		wp_register_ability(
			'mindcrafts-ai/smart-clone',
			array(
				'label'               => __( 'Smart Clone Element', 'mindcrafts-ai' ),
				'description'         => __( 'Clones an element and optionally applies variation settings in one step.', 'mindcrafts-ai' ),
				'category'            => 'mindcrafts-ai',
				'execute_callback'    => array( $this, 'execute_smart_clone' ),
				'permission_callback' => array( $this, 'check_edit_permission' ),
				'input_schema'        => array(
					'type'       => 'object',
					'properties' => array(
						'post_id'    => array( 'type' => 'integer' ),
						'element_id' => array( 'type' => 'string' ),
						'variations' => array( 'type' => 'object' ),
					),
					'required'   => array( 'post_id', 'element_id' ),
				),
				'output_schema'       => array(
					'type'       => 'object',
					'properties' => array(
						'success'        => array( 'type' => 'boolean' ),
						'new_element_id' => array( 'type' => 'string' ),
					),
				),
			)
		);
	}

	public function execute_smart_clone( $input ) {
		$post_id    = absint( $input['post_id'] ?? 0 );
		$element_id = sanitize_text_field( $input['element_id'] ?? '' );
		$variations = $input['variations'] ?? array();

		if ( ! $post_id || empty( $element_id ) ) {
			return new \WP_Error( 'missing_params', __( 'post_id and element_id are required.', 'mindcrafts-ai' ) );
		}

		if ( ! is_array( $variations ) ) {
			$variations = array();
		}

		$page_data = $this->data->get_page_data( $post_id );
		if ( is_wp_error( $page_data ) ) {
			return $page_data;
		}

		$element = $this->data->find_element_by_id( $page_data, $element_id );
		if ( null === $element ) {
			return new \WP_Error( 'element_not_found', __( 'Element not found.', 'mindcrafts-ai' ) );
		}

		$clone = $this->data->reassign_element_ids( $element );
		$clone = $this->apply_variations( $clone, $variations );

		if ( ! $this->insert_after( $page_data, $element_id, $clone ) ) {
			return new \WP_Error( 'insert_failed', __( 'Failed to insert cloned element.', 'mindcrafts-ai' ) );
		}

		$result = $this->data->save_page_data( $post_id, $page_data );
		if ( is_wp_error( $result ) ) {
			return $result;
		}

		return array(
			'success'        => true,
			'new_element_id' => $clone['id'],
		);
	}

	/**
	 * Applies root-level setting variations to a cloned element.
	 *
	 * @param array $element    Cloned element.
	 * @param array $variations Variation settings.
	 * @return array
	 */
	private function apply_variations( array $element, array $variations ): array {
		if ( empty( $variations ) ) {
			return $element;
		}

		$settings = $variations['settings'] ?? $variations;
		unset( $settings['children'], $settings['child_variations'] );

		if ( is_array( $settings ) && ! empty( $settings ) ) {
			$element['settings'] = array_merge( $element['settings'] ?? array(), $settings );
		}

		return $element;
	}

	/**
	 * Inserts an element immediately after a target element in the tree.
	 *
	 * @param array  $data      Page data tree.
	 * @param string $target_id Target element ID.
	 * @param array  $element   Element to insert.
	 * @return bool
	 */
	private function insert_after( array &$data, string $target_id, array $element ): bool {
		foreach ( $data as $index => &$item ) {
			if ( isset( $item['id'] ) && $item['id'] === $target_id ) {
				array_splice( $data, $index + 1, 0, array( $element ) );
				return true;
			}

			if ( ! empty( $item['elements'] ) && is_array( $item['elements'] ) ) {
				if ( $this->insert_after( $item['elements'], $target_id, $element ) ) {
					return true;
				}
			}
		}

		return false;
	}
}
