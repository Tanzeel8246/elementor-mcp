<?php
/**
 * Marketplace abilities for MindCrafts AI.
 *
 * @package MindCrafts_AI
 * @since   2.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles template marketplace imports for MindCrafts AI.
 *
 * @package MindCrafts_AI
 * @since   2.0.0
 */
class MindCrafts_AI_Marketplace_Abilities {

	/**
	 * Data access layer.
	 *
	 * @var MindCrafts_AI_Data
	 */
	private MindCrafts_AI_Data $data;

	/**
	 * License manager.
	 *
	 * @var MindCrafts_AI_License_Manager
	 */
	private MindCrafts_AI_License_Manager $license;

	/**
	 * Constructor.
	 *
	 * @since 2.0.0
	 *
	 * @param MindCrafts_AI_Data $data The data access layer.
	 */
	public function __construct( MindCrafts_AI_Data $data ) {
		$this->data    = $data;
		$this->license = MindCrafts_AI_License_Manager::instance();
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
			'mindcrafts-ai/import-premium-template',
		);
	}

	/**
	 * Registers Marketplace abilities.
	 *
	 * @since 2.0.0
	 */
	public function register(): void {
		wp_register_ability(
			'mindcrafts-ai/import-premium-template',
			array(
				'label'               => __( 'Import Premium Template', 'mindcrafts-ai' ),
				'description'         => __( 'Downloads and imports a premium template from the MindCrafts AI marketplace.', 'mindcrafts-ai' ),
				'category'            => 'mindcrafts-ai',
				'execute_callback'    => array( $this, 'execute_import_premium' ),
				'permission_callback' => array( $this->license, 'check_premium_permission' ),
				'input_schema'        => array(
					'type'       => 'object',
					'properties' => array(
						'post_id'     => array(
							'type'        => 'integer',
							'description' => __( 'The post/page ID.', 'mindcrafts-ai' ),
						),
						'template_id' => array(
							'type'        => 'string',
							'description' => __( 'The marketplace template ID.', 'mindcrafts-ai' ),
						),
					),
					'required'   => array( 'post_id', 'template_id' ),
				),
				'output_schema'       => array(
					'type'       => 'object',
					'properties' => array(
						'success' => array( 'type' => 'boolean' ),
					),
				),
			)
		);
	}

	/**
	 * Executes marketplace template import.
	 *
	 * @since 2.0.0
	 *
	 * @param array $input Input parameters.
	 * @return \WP_Error
	 */
	public function execute_import_premium( $input ) {
		return new \WP_Error(
			'premium_addon_required',
			__( 'Template marketplace import requires the production premium add-on. This placeholder does not import templates.', 'mindcrafts-ai' )
		);
	}
}
