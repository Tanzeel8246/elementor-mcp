<?php
/**
 * Tests for MindCrafts_AI_License_Manager.
 *
 * @package MindCrafts_AI
 */

class MindCrafts_AI_License_Manager_Test extends WP_UnitTestCase {

	/**
	 * @var MindCrafts_AI_License_Manager
	 */
	private $manager;

	public function set_up(): void {
		parent::set_up();
		require_once MINDCRAFTS_AI_DIR . 'includes/class-license-manager.php';
		$this->manager = MindCrafts_AI_License_Manager::instance();
		delete_option( MindCrafts_AI_License_Manager::OPTION_KEY );
		delete_option( MindCrafts_AI_License_Manager::STATUS_KEY );
		delete_transient( MindCrafts_AI_License_Manager::CACHE_KEY );
	}

	public function tear_down(): void {
		delete_option( MindCrafts_AI_License_Manager::OPTION_KEY );
		delete_option( MindCrafts_AI_License_Manager::STATUS_KEY );
		delete_transient( MindCrafts_AI_License_Manager::CACHE_KEY );
		parent::tear_down();
	}

	public function test_empty_license_is_not_active() {
		$this->assertFalse( $this->manager->is_premium_active() );
		$this->assertSame( 'empty', $this->manager->get_license_status() );
	}

	public function test_cached_valid_status_returns_true() {
		update_option( MindCrafts_AI_License_Manager::OPTION_KEY, 'MC-TEST-KEY-123' );
		set_transient( MindCrafts_AI_License_Manager::CACHE_KEY, 1, 3600 );

		$this->assertTrue( $this->manager->is_premium_active() );
	}

	public function test_cached_invalid_status_returns_false() {
		update_option( MindCrafts_AI_License_Manager::OPTION_KEY, 'MC-TEST-KEY-123' );
		set_transient( MindCrafts_AI_License_Manager::CACHE_KEY, 0, 3600 );

		$this->assertFalse( $this->manager->is_premium_active() );
	}

	public function test_deactivate_license_clears_options_and_transient() {
		update_option( MindCrafts_AI_License_Manager::OPTION_KEY, 'MC-TEST-KEY-123' );
		update_option( MindCrafts_AI_License_Manager::STATUS_KEY, 'valid' );
		set_transient( MindCrafts_AI_License_Manager::CACHE_KEY, 1, 3600 );

		$res = $this->manager->deactivate_license();

		$this->assertTrue( $res['success'] );
		$this->assertEmpty( $this->manager->get_license_key() );
		$this->assertSame( 'empty', $this->manager->get_license_status() );
		$this->assertFalse( get_transient( MindCrafts_AI_License_Manager::CACHE_KEY ) );
	}
}
