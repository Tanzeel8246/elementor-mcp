<?php
/**
 * Tests for MindCrafts_AI_Composite_Abilities (build-page).
 *
 * @package MindCrafts_AI
 */

class MindCrafts_AI_Build_Page_Test extends WP_UnitTestCase {

	/**
	 * @var MindCrafts_AI_Composite_Abilities
	 */
	private $composite;

	public function set_up(): void {
		parent::set_up();
		require_once MINDCRAFTS_AI_DIR . 'includes/class-id-generator.php';
		require_once MINDCRAFTS_AI_DIR . 'includes/class-element-factory.php';
		require_once MINDCRAFTS_AI_DIR . 'includes/class-elementor-data.php';
		require_once MINDCRAFTS_AI_DIR . 'includes/abilities/class-composite-abilities.php';

		$data            = new MindCrafts_AI_Data();
		$factory         = new MindCrafts_AI_Element_Factory();
		$this->composite = new MindCrafts_AI_Composite_Abilities( $data, $factory );
	}

	public function test_missing_title_returns_error() {
		$result = $this->composite->execute_build_page(
			array(
				'title'     => '',
				'structure' => array(
					array(
						'type'     => 'container',
						'settings' => array(),
					),
				),
			)
		);

		$this->assertWPError( $result );
		$this->assertSame( 'missing_title', $result->get_error_code() );
	}

	public function test_invalid_structure_returns_wp_error_without_creating_post() {
		$posts_before = count( get_posts( array( 'post_type' => 'page', 'post_status' => 'any' ) ) );

		$result = $this->composite->execute_build_page(
			array(
				'title'     => 'Invalid Page',
				'structure' => 'not-an-array',
			)
		);

		$posts_after = count( get_posts( array( 'post_type' => 'page', 'post_status' => 'any' ) ) );

		$this->assertWPError( $result );
		$this->assertSame( $posts_before, $posts_after );
	}

	public function test_invalid_widget_type_returns_error() {
		$posts_before = count( get_posts( array( 'post_type' => 'page', 'post_status' => 'any' ) ) );

		$result = $this->composite->execute_build_page(
			array(
				'title'     => 'Broken Widget Page',
				'structure' => array(
					array(
						'type'     => 'container',
						'children' => array(
							array(
								'type'        => 'widget',
								'widget_type' => '', // Empty widget type is invalid.
							),
						),
					),
				),
			)
		);

		$posts_after = count( get_posts( array( 'post_type' => 'page', 'post_status' => 'any' ) ) );

		$this->assertWPError( $result );
		$this->assertSame( $posts_before, $posts_after );
	}

	public function test_valid_structure_creates_post() {
		$result = $this->composite->execute_build_page(
			array(
				'title'     => 'Landing Page',
				'status'    => 'draft',
				'structure' => array(
					array(
						'type'     => 'container',
						'settings' => array( 'flex_direction' => 'column' ),
						'children' => array(
							array(
								'type'        => 'widget',
								'widget_type' => 'heading',
								'settings'    => array( 'title' => 'Welcome to MindCrafts AI' ),
							),
						),
					),
				),
			)
		);

		$this->assertIsArray( $result );
		$this->assertArrayHasKey( 'post_id', $result );
		$this->assertArrayHasKey( 'edit_url', $result );
		$this->assertArrayHasKey( 'elements_created', $result );
		$this->assertGreaterThan( 0, $result['elements_created'] );

		$post = get_post( $result['post_id'] );
		$this->assertNotNull( $post );
		$this->assertSame( 'Landing Page', $post->post_title );
	}
}
