<?php
/**
 * Connection tab view for the MindCrafts AI admin settings page.
 *
 * @package MindCrafts_AI
 * @since   2.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** @var MindCrafts_AI_Admin $this */
$enabled_count   = $this->get_enabled_tool_count();
$total_count     = $this->get_total_tool_count();
$has_elementor   = did_action( 'elementor/loaded' );
$has_mcp_adapter = class_exists( '\WP\MCP\Core\McpAdapter' );
$has_abilities   = function_exists( 'wp_register_ability' );
$mcp_endpoint    = rest_url( 'mcp/mindcrafts-ai-server' );
?>

<div class="mindcrafts-ai-connection-wrap" style="display: flex; flex-direction: column; gap: 24px;">

	<!-- Service Health Grid -->
	<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 16px;">
		<div class="mindcrafts-ai-card" style="margin: 0; padding: 20px;">
			<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px;">
				<span class="dashicons dashicons-layout" style="font-size: 24px; width: 24px; height: 24px; color: var(--mc-primary);"></span>
				<?php if ( $has_elementor ) : ?>
					<span class="mindcrafts-ai-badge mindcrafts-ai-badge--active"><?php esc_html_e( 'Active', 'mindcrafts-ai' ); ?></span>
				<?php else : ?>
					<span class="mindcrafts-ai-badge mindcrafts-ai-badge--inactive"><?php esc_html_e( 'Missing', 'mindcrafts-ai' ); ?></span>
				<?php endif; ?>
			</div>
			<h3 style="margin: 0 0 4px 0; font-size: 15px; color: var(--mc-text-main);"><?php esc_html_e( 'Elementor Page Builder', 'mindcrafts-ai' ); ?></h3>
			<p style="margin: 0; font-size: 12.5px; color: var(--mc-text-muted);">
				<?php echo defined( 'ELEMENTOR_VERSION' ) ? esc_html( 'v' . ELEMENTOR_VERSION . ' loaded' ) : esc_html__( 'Please install and activate Elementor', 'mindcrafts-ai' ); ?>
			</p>
		</div>

		<div class="mindcrafts-ai-card" style="margin: 0; padding: 20px;">
			<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px;">
				<span class="dashicons dashicons-networking" style="font-size: 24px; width: 24px; height: 24px; color: var(--mc-primary);"></span>
				<?php if ( $has_mcp_adapter ) : ?>
					<span class="mindcrafts-ai-badge mindcrafts-ai-badge--active"><?php esc_html_e( 'Bundled & Ready', 'mindcrafts-ai' ); ?></span>
				<?php else : ?>
					<span class="mindcrafts-ai-badge mindcrafts-ai-badge--inactive"><?php esc_html_e( 'Missing', 'mindcrafts-ai' ); ?></span>
				<?php endif; ?>
			</div>
			<h3 style="margin: 0 0 4px 0; font-size: 15px; color: var(--mc-text-main);"><?php esc_html_e( 'WordPress MCP Adapter', 'mindcrafts-ai' ); ?></h3>
			<p style="margin: 0; font-size: 12.5px; color: var(--mc-text-muted);">
				<?php esc_html_e( 'Official MCP Protocol bridge transport', 'mindcrafts-ai' ); ?>
			</p>
		</div>

		<div class="mindcrafts-ai-card" style="margin: 0; padding: 20px;">
			<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px;">
				<span class="dashicons dashicons-rest-api" style="font-size: 24px; width: 24px; height: 24px; color: var(--mc-primary);"></span>
				<?php if ( $has_abilities ) : ?>
					<span class="mindcrafts-ai-badge mindcrafts-ai-badge--active"><?php esc_html_e( 'Available', 'mindcrafts-ai' ); ?></span>
				<?php else : ?>
					<span class="mindcrafts-ai-badge mindcrafts-ai-badge--inactive"><?php esc_html_e( 'Missing', 'mindcrafts-ai' ); ?></span>
				<?php endif; ?>
			</div>
			<h3 style="margin: 0 0 4px 0; font-size: 15px; color: var(--mc-text-main);"><?php esc_html_e( 'Abilities API', 'mindcrafts-ai' ); ?></h3>
			<p style="margin: 0; font-size: 12.5px; color: var(--mc-text-muted);">
				<?php esc_html_e( 'Tool registration & capability validation', 'mindcrafts-ai' ); ?>
			</p>
		</div>

		<div class="mindcrafts-ai-card" style="margin: 0; padding: 20px;">
			<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px;">
				<span class="dashicons dashicons-admin-tools" style="font-size: 24px; width: 24px; height: 24px; color: var(--mc-primary);"></span>
				<span class="mindcrafts-ai-badge mindcrafts-ai-badge--read-only"><?php echo esc_html( (string) $enabled_count . '/' . (string) $total_count ); ?></span>
			</div>
			<h3 style="margin: 0 0 4px 0; font-size: 15px; color: var(--mc-text-main);"><?php esc_html_e( 'MCP Tools Exposed', 'mindcrafts-ai' ); ?></h3>
			<p style="margin: 0; font-size: 12.5px; color: var(--mc-text-muted);">
				<?php esc_html_e( 'Ready for AI query and execution', 'mindcrafts-ai' ); ?>
			</p>
		</div>
	</div>

	<!-- Endpoint Connection Details Card -->
	<div class="mindcrafts-ai-card">
		<div class="mindcrafts-ai-card-header">
			<h2 class="mindcrafts-ai-card-title">
				<span class="dashicons dashicons-admin-links"></span>
				<span><?php esc_html_e( 'Server Endpoint Details', 'mindcrafts-ai' ); ?></span>
			</h2>
			<div style="display: flex; align-items: center; gap: 8px;">
				<span class="mindcrafts-ai-pulse-dot"></span>
				<span style="font-size: 12px; font-weight: 600; color: var(--mc-success);"><?php esc_html_e( 'Listening', 'mindcrafts-ai' ); ?></span>
			</div>
		</div>

		<table class="form-table" role="presentation" style="margin-top: 0;">
			<tr>
				<th scope="row" style="width: 220px; font-weight: 600; color: var(--mc-text-muted);"><?php esc_html_e( 'Server Name', 'mindcrafts-ai' ); ?></th>
				<td>
					<div style="display: flex; align-items: center; gap: 8px;">
						<code style="font-size: 13px; padding: 4px 10px; background: var(--mc-bg); border-radius: var(--mc-radius-sm);">mindcrafts-ai-server</code>
						<button type="button" class="mindcrafts-ai-copy-slug-btn" data-copy-text="mindcrafts-ai-server" title="<?php esc_attr_e( 'Copy Server Name', 'mindcrafts-ai' ); ?>">
							<span class="dashicons dashicons-admin-page" style="font-size: 14px; width: 14px; height: 14px;"></span>
							<span><?php esc_html_e( 'Copy', 'mindcrafts-ai' ); ?></span>
						</button>
					</div>
				</td>
			</tr>
			<tr>
				<th scope="row" style="font-weight: 600; color: var(--mc-text-muted);"><?php esc_html_e( 'REST MCP Endpoint', 'mindcrafts-ai' ); ?></th>
				<td>
					<div style="display: flex; align-items: center; gap: 8px; flex-wrap: wrap;">
						<code style="font-size: 13px; padding: 4px 10px; background: var(--mc-bg); border-radius: var(--mc-radius-sm); word-break: break-all;"><?php echo esc_html( $mcp_endpoint ); ?></code>
						<button type="button" class="mindcrafts-ai-copy-slug-btn" data-copy-text="<?php echo esc_attr( $mcp_endpoint ); ?>" title="<?php esc_attr_e( 'Copy Endpoint URL', 'mindcrafts-ai' ); ?>">
							<span class="dashicons dashicons-admin-page" style="font-size: 14px; width: 14px; height: 14px;"></span>
							<span><?php esc_html_e( 'Copy URL', 'mindcrafts-ai' ); ?></span>
						</button>
					</div>
				</td>
			</tr>
			<tr>
				<th scope="row" style="font-weight: 600; color: var(--mc-text-muted);"><?php esc_html_e( 'Authentication Mode', 'mindcrafts-ai' ); ?></th>
				<td>
					<span style="font-size: 13px; color: var(--mc-text-main); font-weight: 500;">
						<?php esc_html_e( 'HTTP Basic (WordPress Application Passwords) or WP-CLI stdio bridge', 'mindcrafts-ai' ); ?>
					</span>
				</td>
			</tr>
		</table>
	</div>

	<!-- Antigravity MCP Connection Setup -->
	<?php
	$current_user = wp_get_current_user();
	$generated_pw = get_transient( 'mindcrafts_ai_new_app_pw_' . $current_user->ID );
	$proxy_path   = MINDCRAFTS_AI_DIR . 'bin/mcp-proxy.mjs';
	?>
	<div class="mindcrafts-ai-card" style="border: 2px solid var(--mc-primary); background: linear-gradient(135deg, rgba(99, 102, 241, 0.04) 0%, rgba(168, 85, 247, 0.04) 100%);">
		<div class="mindcrafts-ai-card-header">
			<h2 class="mindcrafts-ai-card-title">
				<span class="dashicons dashicons-admin-generic" style="color: var(--mc-primary);"></span>
				<span><?php esc_html_e( 'Antigravity IDE 1-Click MCP Setup', 'mindcrafts-ai' ); ?></span>
			</h2>
			<?php if ( ! empty( $generated_pw ) ) : ?>
				<span class="mindcrafts-ai-badge mindcrafts-ai-badge--active"><?php esc_html_e( 'Credentials Ready', 'mindcrafts-ai' ); ?></span>
			<?php endif; ?>
		</div>

		<?php if ( ! empty( $_GET['app_error'] ) ) : ?>
			<div class="notice notice-error inline" style="margin: 0 0 16px 0; padding: 10px 14px;">
				<p><strong><?php esc_html_e( 'Error:', 'mindcrafts-ai' ); ?></strong> <?php echo esc_html( sanitize_text_field( wp_unslash( $_GET['app_error'] ) ) ); ?></p>
			</div>
		<?php endif; ?>

		<?php if ( ! empty( $generated_pw ) ) : ?>
			<?php
			$mcp_json = wp_json_encode(
				array(
					'mcpServers' => array(
						'mindcrafts-ai' => array(
							'command' => 'node',
							'args'    => array( wp_normalize_path( $proxy_path ) ),
							'env'     => array(
								'WP_URL'          => home_url(),
								'WP_USERNAME'     => $current_user->user_login,
								'WP_APP_PASSWORD' => $generated_pw,
							),
						),
					),
				),
				JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES
			);
			?>
			<div style="background: rgba(16, 185, 129, 0.08); border: 1px solid #10b981; border-radius: var(--mc-radius-md); padding: 18px; margin-bottom: 18px;">
				<div style="display: flex; align-items: center; gap: 8px; color: #047857; font-weight: 700; font-size: 14px; margin-bottom: 10px;">
					<span class="dashicons dashicons-yes-alt"></span>
					<span><?php esc_html_e( 'Application Password Generated Successfully!', 'mindcrafts-ai' ); ?></span>
				</div>
				<p style="margin: 0 0 12px 0; font-size: 13px; color: var(--mc-text-main);">
					<?php esc_html_e( 'Here is your dedicated Antigravity connection password (save it or copy the full configuration below):', 'mindcrafts-ai' ); ?>
				</p>
				<div style="display: flex; gap: 10px; align-items: center; flex-wrap: wrap; margin-bottom: 14px;">
					<code style="font-size: 16px; font-weight: 700; letter-spacing: 1px; padding: 8px 16px; background: #fff; border: 1px solid #d1fae5; border-radius: var(--mc-radius-sm); color: #065f46;">
						<?php echo esc_html( $generated_pw ); ?>
					</code>
					<button type="button" class="button mindcrafts-ai-copy-slug-btn" data-copy-text="<?php echo esc_attr( $generated_pw ); ?>">
						<span class="dashicons dashicons-admin-page" style="font-size: 14px; width: 14px; height: 14px;"></span>
						<span><?php esc_html_e( 'Copy Password', 'mindcrafts-ai' ); ?></span>
					</button>
				</div>
				<p style="margin: 0 0 8px 0; font-size: 12.5px; font-weight: 600; color: var(--mc-text-muted);">
					<?php esc_html_e( 'Ready-to-use Antigravity configuration (mcp_config.json):', 'mindcrafts-ai' ); ?>
				</p>
				<pre style="background: #1e1e2e; color: #cdd6f4; padding: 14px; border-radius: var(--mc-radius-md); font-size: 12px; line-height: 1.5; overflow-x: auto; margin: 0 0 10px 0;"><code><?php echo esc_html( $mcp_json ); ?></code></pre>
				<p style="margin: 0 0 10px 0; font-size: 11.5px; color: var(--mc-text-muted); line-height: 1.4;">
					<strong><?php esc_html_e( 'Note for Local AI IDEs (Antigravity / Cursor / Claude Desktop):', 'mindcrafts-ai' ); ?></strong>
					<?php esc_html_e( 'If your AI client runs on your personal computer (Windows/Mac), ensure the path in "args" points to where mcp-proxy.mjs is saved on your local computer.', 'mindcrafts-ai' ); ?>
				</p>
				<button type="button" class="button button-primary mindcrafts-ai-copy-slug-btn" data-copy-text="<?php echo esc_attr( $mcp_json ); ?>">
					<span class="dashicons dashicons-clipboard"></span>
					<span><?php esc_html_e( 'Copy Antigravity JSON Config', 'mindcrafts-ai' ); ?></span>
				</button>
			</div>
		<?php else : ?>
			<p style="font-size: 13.5px; color: var(--mc-text-muted); line-height: 1.6; margin-bottom: 16px;">
				<?php esc_html_e( 'Click the button below to generate an authorized Application Password for Antigravity with 1 click. You will immediately receive the exact JSON configuration to connect Antigravity to this Elementor site.', 'mindcrafts-ai' ); ?>
			</p>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="margin: 0;">
				<input type="hidden" name="action" value="mindcrafts_ai_generate_app_password">
				<?php wp_nonce_field( 'mindcrafts_ai_generate_app_password' ); ?>
				<button type="submit" class="button button-primary" style="height: 40px; padding: 0 20px; font-size: 14px; font-weight: 600; display: inline-flex; align-items: center; gap: 8px;">
					<span class="dashicons dashicons-key" style="font-size: 18px; width: 18px; height: 18px;"></span>
					<span><?php esc_html_e( 'Generate Antigravity MCP Credentials', 'mindcrafts-ai' ); ?></span>
				</button>
			</form>
		<?php endif; ?>
	</div>

	<!-- AI Testing Prompt Guide -->
	<div class="mindcrafts-ai-card">
		<div class="mindcrafts-ai-card-header">
			<h2 class="mindcrafts-ai-card-title">
				<span class="dashicons dashicons-testimonial"></span>
				<span><?php esc_html_e( 'How to Test Your AI Agent Connection', 'mindcrafts-ai' ); ?></span>
			</h2>
		</div>

		<p style="font-size: 13.5px; color: var(--mc-text-muted); line-height: 1.5; margin-bottom: 16px;">
			<?php esc_html_e( 'Once your AI client (Claude Desktop, Cursor, etc.) is configured and restarted, test the connection by sending any of these commands to your agent:', 'mindcrafts-ai' ); ?>
		</p>

		<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 14px;">
			<div style="background: var(--mc-bg); border: 1px solid var(--mc-border); border-radius: var(--mc-radius-md); padding: 16px;">
				<div style="font-weight: 700; font-size: 13px; color: var(--mc-primary); margin-bottom: 6px;">1. Discovery Check</div>
				<p style="font-size: 12.5px; color: var(--mc-text-main); margin: 0; font-style: italic;">
					"Use the <code>mindcrafts-ai/list-widgets</code> tool to list all available Elementor widgets on this website."
				</p>
			</div>

			<div style="background: var(--mc-bg); border: 1px solid var(--mc-border); border-radius: var(--mc-radius-md); padding: 16px;">
				<div style="font-weight: 700; font-size: 13px; color: var(--mc-primary); margin-bottom: 6px;">2. Page Structure Check</div>
				<p style="font-size: 12.5px; color: var(--mc-text-main); margin: 0; font-style: italic;">
					"Use <code>mindcrafts-ai/list-pages</code> to show me all existing Elementor landing pages."
				</p>
			</div>

			<div style="background: var(--mc-bg); border: 1px solid var(--mc-border); border-radius: var(--mc-radius-md); padding: 16px;">
				<div style="font-weight: 700; font-size: 13px; color: var(--mc-primary); margin-bottom: 6px;">3. Complete Page Construction</div>
				<p style="font-size: 12.5px; color: var(--mc-text-main); margin: 0; font-style: italic;">
					"Build a high-converting SaaS landing page with hero container, features, testimonials, and CTA using <code>mindcrafts-ai/build-page</code>."
				</p>
			</div>
		</div>
	</div>

</div>
