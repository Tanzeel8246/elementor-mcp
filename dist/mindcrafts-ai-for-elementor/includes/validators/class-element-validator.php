<?php
/**
 * Validates Elementor element structures before saving.
 *
 * @package MindCrafts_AI
 * @since   1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Validates element structures for correctness.
 *
 * @since 1.0.0
 */
class MindCrafts_AI_Element_Validator {

	/**
	 * Validates an element structure.
	 *
	 * @since 1.0.0
	 *
	 * @param array $element The element array to validate.
	 * @return true|\WP_Error True if valid, WP_Error on failure.
	 */
	public function validate( array $element ) {
		if ( empty( $element['id'] ) ) {
			return new \WP_Error( 'missing_id', __( 'Element is missing an ID.', 'mindcrafts-ai' ) );
		}

		if ( empty( $element['elType'] ) ) {
			return new \WP_Error( 'missing_el_type', __( 'Element is missing elType.', 'mindcrafts-ai' ) );
		}

		$valid_types = array( 'container', 'widget', 'section', 'column' );
		if ( ! in_array( $element['elType'], $valid_types, true ) ) {
			return new \WP_Error(
				'invalid_el_type',
				sprintf(
					/* translators: %s: element type */
					__( 'Invalid element type: %s.', 'mindcrafts-ai' ),
					$element['elType']
				)
			);
		}

		if ( 'widget' === $element['elType'] && empty( $element['widgetType'] ) ) {
			return new \WP_Error( 'missing_widget_type', __( 'Widget element is missing widgetType.', 'mindcrafts-ai' ) );
		}

		return true;
	}
}
