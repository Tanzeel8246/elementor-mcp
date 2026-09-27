<?php
/**
 * Tests for rate limiting and licensing.
 *
 * @package MindCrafts_AI
 */

class MindCrafts_AI_Rate_Limit_Test extends WP_UnitTestCase {

	/**
	 * @var MindCrafts_AI_Query_Abilities
	 */
	private $query_abilities;

	public function set_up(): void {
		parent::set_up();
		require_once MINDCRAFTS_AI_DIR . 'includes/class-elementor-data.php';
		require_once MINDCRAFTS_AI_DIR . 'includes/schemas/class-control-mapper.php';
		require_once MINDCRAFTS_AI_DIR . 'includes/schemas/class-schema-generator.php';
		require_once MINDCRAFTS_AI_DIR . 'includes/abilities/class-query-abilities.php';

		$data                  = new MindCrafts_AI_Data();
		$generator             = new MindCrafts_AI_Schema_Generator();
		$this->query_abilities = new MindCrafts_AI_Query_Abilities( $data, $generator );
	}

	public function test_first_call_passes() {
		$tool_name = 'test-tool-' . wp_generate_password( 6, false );
		$result    = $this->query_abilities->check_rate_limit( $tool_name, 5, 60 );

		$this->assertTrue( $result );
	}

	public function test_exceeding_limit_returns_wp_error() {
		$tool_name = 'test-limit-' . wp_generate_password( 6, false );
		$limit     = 3;

		// 3 allowed calls
		for ( $i = 0; $i < $limit; $i++ ) {
			$res = $this->query_abilities->check_rate_limit( $tool_name, $limit, 60 );
			$this->assertTrue( $res );
		}

		// 4th call must exceed limit
		$exceeded = $this->query_abilities->check_rate_limit( $tool_name, $limit, 60 );
		$this->assertWPError( $exceeded );
		$this->assertSame( 'rate_limit_exceeded', $exceeded->get_error_code() );
	}

	public function test_limit_resets_after_period() {
		$tool_name = 'test-reset-' . wp_generate_password( 6, false );
		$limit     = 2;

		// Fill the quota
		$this->query_abilities->check_rate_limit( $tool_name, $limit, 60 );
		$this->query_abilities->check_rate_limit( $tool_name, $limit, 60 );
		$exceeded = $this->query_abilities->check_rate_limit( $tool_name, $limit, 60 );
		$this->assertWPError( $exceeded );

		// Simulate expired transient by deleting it
		$user_id       = get_current_user_id();
		$transient_key = 'mcp_rate_' . md5( $tool_name . '_' . $user_id );
		delete_transient( $transient_key );

		// Now the call should pass again
		$after_reset = $this->query_abilities->check_rate_limit( $tool_name, $limit, 60 );
		$this->assertTrue( $after_reset );
	}
}
