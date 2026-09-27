<?php
/**
 * SEO abilities for MindCrafts AI.
 *
 * @package MindCrafts_AI
 * @since   2.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles AI SEO optimization tools for Elementor pages.
 *
 * @package MindCrafts_AI
 * @since   2.0.0
 */
class MindCrafts_AI_SEO_Abilities {

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
			'mindcrafts-ai/optimize-seo',
		);
	}

	/**
	 * Registers SEO abilities.
	 *
	 * @since 2.0.0
	 */
	public function register(): void {
		wp_register_ability(
			'mindcrafts-ai/optimize-seo',
			array(
				'label'               => __( 'Optimize SEO', 'mindcrafts-ai' ),
				'description'         => __( 'AI-driven generation of meta tags, alt texts, and structure optimizations.', 'mindcrafts-ai' ),
				'category'            => 'mindcrafts-ai',
				'execute_callback'    => array( $this, 'execute_optimize_seo' ),
				'permission_callback' => array( $this->license, 'check_premium_permission' ),
				'input_schema'        => array(
					'type'       => 'object',
					'properties' => array(
						'post_id' => array(
							'type'        => 'integer',
							'description' => __( 'The post/page ID to optimize.', 'mindcrafts-ai' ),
						),
					),
					'required'   => array( 'post_id' ),
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
	 * Executes SEO optimization.
	 *
	 * @since 2.0.0
	 *
	 * @param array $input Input parameters.
	 * @return \WP_Error
	 */
	public function execute_optimize_seo( $input ) {
		return new \WP_Error(
			'premium_addon_required',
			__( 'SEO optimization tools require the production premium add-on. This placeholder does not modify SEO data.', 'mindcrafts-ai' )
		);
	}
}
