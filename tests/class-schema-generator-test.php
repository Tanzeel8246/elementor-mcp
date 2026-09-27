<?php
/**
 * Tests for schema control mapping.
 *
 * @package MindCrafts_AI
 */

class MindCrafts_AI_Control_Mapper_Test extends WP_UnitTestCase {

	public function set_up() {
		parent::set_up();
		require_once MINDCRAFTS_AI_DIR . 'includes/schemas/class-control-mapper.php';
	}

	public function test_text_control_maps_to_string_schema() {
		$schema = MindCrafts_AI_Control_Mapper::map(
			array(
				'type'  => 'text',
				'label' => 'Heading',
			)
		);

		$this->assertSame( 'string', $schema['type'] );
		$this->assertSame( 'Heading', $schema['description'] );
	}

	public function test_repeater_control_maps_nested_fields() {
		$schema = MindCrafts_AI_Control_Mapper::map(
			array(
				'type'   => 'repeater',
				'fields' => array(
					array(
						'name' => 'item_title',
						'type' => 'text',
					),
				),
			)
		);

		$this->assertSame( 'array', $schema['type'] );
		$this->assertArrayHasKey( 'item_title', $schema['items']['properties'] );
		$this->assertSame( 'string', $schema['items']['properties']['item_title']['type'] );
	}

	public function test_structural_controls_are_skipped() {
		$this->assertTrue( MindCrafts_AI_Control_Mapper::should_skip( 'section' ) );
		$this->assertSame( array(), MindCrafts_AI_Control_Mapper::map( array( 'type' => 'section' ) ) );
	}
}
