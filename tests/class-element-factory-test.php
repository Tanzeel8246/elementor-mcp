<?php
/**
 * Tests for MindCrafts_AI_Element_Factory.
 *
 * @package MindCrafts_AI
 */

class MindCrafts_AI_Element_Factory_Test extends WP_UnitTestCase {

	/**
	 * @var MindCrafts_AI_Element_Factory
	 */
	private $factory;

	public function set_up(): void {
		parent::set_up();
		require_once MINDCRAFTS_AI_DIR . 'includes/class-id-generator.php';
		require_once MINDCRAFTS_AI_DIR . 'includes/class-element-factory.php';
		$this->factory = new MindCrafts_AI_Element_Factory();
	}

	public function test_create_container_has_required_keys() {
		$container = $this->factory->create_container();

		$this->assertArrayHasKey( 'id', $container );
		$this->assertArrayHasKey( 'elType', $container );
		$this->assertArrayHasKey( 'widgetType', $container );
		$this->assertArrayHasKey( 'settings', $container );
		$this->assertArrayHasKey( 'elements', $container );
		$this->assertSame( 'container', $container['elType'] );
		$this->assertNull( $container['widgetType'] );
		$this->assertIsArray( $container['elements'] );
	}

	public function test_create_widget_has_widget_type() {
		$widget = $this->factory->create_widget( 'heading', array( 'title' => 'Hello World' ) );

		$this->assertSame( 'widget', $widget['elType'] );
		$this->assertSame( 'heading', $widget['widgetType'] );
		$this->assertSame( 'Hello World', $widget['settings']['title'] );
		$this->assertEmpty( $widget['elements'] );
	}

	public function test_container_id_is_7_chars() {
		$container = $this->factory->create_container();

		$this->assertSame( 7, strlen( $container['id'] ) );
		$this->assertMatchesRegularExpression( '/^[0-9a-f]{7}$/', $container['id'] );
	}

	public function test_row_container_gets_nowrap() {
		$container = $this->factory->create_container( array( 'flex_direction' => 'row' ) );

		$this->assertArrayHasKey( 'flex_wrap', $container['settings'] );
		$this->assertSame( 'nowrap', $container['settings']['flex_wrap'] );

		// Check explicit flex_wrap is preserved if passed
		$custom = $this->factory->create_container(
			array(
				'flex_direction' => 'row',
				'flex_wrap'      => 'wrap',
			)
		);
		$this->assertSame( 'wrap', $custom['settings']['flex_wrap'] );
	}
}
