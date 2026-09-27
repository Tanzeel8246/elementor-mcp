<?php
/**
 * Tests for MindCrafts_AI_Data layer operations.
 *
 * @package MindCrafts_AI
 */

class MindCrafts_AI_Data_Test extends WP_UnitTestCase {

	/**
	 * @var MindCrafts_AI_Data
	 */
	private $data;

	/**
	 * @var MindCrafts_AI_Element_Factory
	 */
	private $factory;

	public function set_up(): void {
		parent::set_up();
		require_once MINDCRAFTS_AI_DIR . 'includes/class-id-generator.php';
		require_once MINDCRAFTS_AI_DIR . 'includes/class-element-factory.php';
		require_once MINDCRAFTS_AI_DIR . 'includes/class-elementor-data.php';

		$this->data    = new MindCrafts_AI_Data();
		$this->factory = new MindCrafts_AI_Element_Factory();
	}

	public function test_insert_element_top_level() {
		$tree      = array();
		$container = $this->factory->create_container();

		$result = $this->data->insert_element( $tree, '', $container );

		$this->assertTrue( $result );
		$this->assertCount( 1, $tree );
		$this->assertSame( $container['id'], $tree[0]['id'] );
	}

	public function test_insert_element_nested() {
		$container = $this->factory->create_container();
		$tree      = array( $container );
		$widget    = $this->factory->create_widget( 'heading', array( 'title' => 'Nested Heading' ) );

		$result = $this->data->insert_element( $tree, $container['id'], $widget );

		$this->assertTrue( $result );
		$this->assertCount( 1, $tree[0]['elements'] );
		$this->assertSame( $widget['id'], $tree[0]['elements'][0]['id'] );
	}

	public function test_remove_element() {
		$container = $this->factory->create_container();
		$widget    = $this->factory->create_widget( 'button' );
		$container['elements'] = array( $widget );
		$tree      = array( $container );

		$removed = $this->data->remove_element( $tree, $widget['id'] );

		$this->assertTrue( $removed );
		$this->assertEmpty( $tree[0]['elements'] );

		// Remove non-existent returns false
		$not_found = $this->data->remove_element( $tree, 'non_existent_id' );
		$this->assertFalse( $not_found );
	}

	public function test_update_element_settings_merges() {
		$widget = $this->factory->create_widget( 'heading', array( 'title' => 'Initial Title', 'size' => 'large' ) );
		$tree   = array( $widget );

		$updated = $this->data->update_element_settings( $tree, $widget['id'], array( 'title' => 'Updated Title', 'color' => '#ff0000' ) );

		$this->assertTrue( $updated );
		$this->assertSame( 'Updated Title', $tree[0]['settings']['title'] );
		$this->assertSame( 'large', $tree[0]['settings']['size'] );
		$this->assertSame( '#ff0000', $tree[0]['settings']['color'] );
	}

	public function test_find_element_by_id_recursive() {
		$parent = $this->factory->create_container();
		$child  = $this->factory->create_container();
		$leaf   = $this->factory->create_widget( 'icon' );

		$child['elements']  = array( $leaf );
		$parent['elements'] = array( $child );
		$tree               = array( $parent );

		$found_leaf = $this->data->find_element_by_id( $tree, $leaf['id'] );
		$this->assertNotNull( $found_leaf );
		$this->assertSame( $leaf['id'], $found_leaf['id'] );
		$this->assertSame( 'icon', $found_leaf['widgetType'] );

		$not_found = $this->data->find_element_by_id( $tree, 'ghost_id' );
		$this->assertNull( $not_found );
	}

	public function test_reassign_ids_changes_all_ids() {
		$parent = $this->factory->create_container();
		$child  = $this->factory->create_widget( 'heading' );
		$parent['elements'] = array( $child );
		$tree               = array( $parent );

		$old_parent_id = $parent['id'];
		$old_child_id  = $child['id'];

		$new_tree = $this->data->reassign_ids( $tree );

		$this->assertNotEquals( $old_parent_id, $new_tree[0]['id'] );
		$this->assertNotEquals( $old_child_id, $new_tree[0]['elements'][0]['id'] );
		$this->assertSame( 7, strlen( $new_tree[0]['id'] ) );
		$this->assertSame( 7, strlen( $new_tree[0]['elements'][0]['id'] ) );
	}

	public function test_snapshot_capped_at_10() {
		$post_id = $this->factory_post();
		update_post_meta( $post_id, '_elementor_data', wp_json_encode( array( 'test' => 'data' ) ) );

		for ( $i = 1; $i <= 15; $i++ ) {
			$this->data->save_snapshot( $post_id, "Snapshot #{$i}" );
		}

		$snapshots = get_post_meta( $post_id, '_mindcrafts_snapshots', true );
		$this->assertIsArray( $snapshots );
		$this->assertCount( 10, $snapshots );
		$this->assertSame( 'Snapshot #15', $snapshots[0]['description'] );
	}

	private function factory_post(): int {
		return wp_insert_post(
			array(
				'post_title'  => 'Test Page',
				'post_status' => 'publish',
				'post_type'   => 'page',
			)
		);
	}
}
