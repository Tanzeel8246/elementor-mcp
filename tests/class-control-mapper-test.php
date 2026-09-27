<?php
/**
 * Tests for MindCrafts_AI_Control_Mapper.
 *
 * @package MindCrafts_AI
 */

class MindCrafts_AI_Control_Mapper_Full_Test extends WP_UnitTestCase {

	public function set_up(): void {
		parent::set_up();
		require_once MINDCRAFTS_AI_DIR . 'includes/schemas/class-control-mapper.php';
	}

	public function test_section_type_is_skipped() {
		$this->assertTrue( MindCrafts_AI_Control_Mapper::should_skip( 'section' ) );
		$this->assertSame( array(), MindCrafts_AI_Control_Mapper::map( array( 'type' => 'section' ) ) );
	}

	public function test_text_maps_to_string() {
		$schema = MindCrafts_AI_Control_Mapper::map(
			array(
				'type'  => 'text',
				'label' => 'Title Text',
			)
		);

		$this->assertSame( 'string', $schema['type'] );
		$this->assertSame( 'Title Text', $schema['description'] );
	}

	public function test_slider_maps_to_object_with_size_unit() {
		$schema = MindCrafts_AI_Control_Mapper::map(
			array(
				'type'  => 'slider',
				'label' => 'Width Slider',
			)
		);

		$this->assertSame( 'object', $schema['type'] );
		$this->assertArrayHasKey( 'size', $schema['properties'] );
		$this->assertArrayHasKey( 'unit', $schema['properties'] );
	}

	public function test_select_with_options_gets_enum() {
		$schema = MindCrafts_AI_Control_Mapper::map(
			array(
				'type'    => 'select',
				'label'   => 'Alignment',
				'options' => array(
					'left'   => 'Left',
					'center' => 'Center',
					'right'  => 'Right',
				),
			)
		);

		$this->assertSame( 'string', $schema['type'] );
		$this->assertArrayHasKey( 'enum', $schema );
		$this->assertSame( array( 'left', 'center', 'right' ), $schema['enum'] );
	}

	public function test_unknown_type_falls_back_to_string() {
		$schema = MindCrafts_AI_Control_Mapper::map(
			array(
				'type'  => 'non_existent_control_xyz',
				'label' => 'Custom Field',
			)
		);

		$this->assertSame( 'string', $schema['type'] );
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
	}
}
