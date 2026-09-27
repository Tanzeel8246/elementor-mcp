<?php
/**
 * Setup tab view for the MindCrafts AI admin page.
 *
 * @package MindCrafts_AI
 * @since   2.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** @var MindCrafts_AI_Admin $this */
$dependencies = $this->get_dependency_definitions();
$mcp_endpoint = rest_url( 'mcp/mindcrafts-ai-server' );
$server_name  = 'mindcrafts-ai-server';
$tool_count   = $this->get_total_tool_count();

$stdio_config = wp_json_encode(
	array(
		'mcpServers' => array(
			$server_name => array(
				'command' => 'npx.cmd',
				'args'    => array(
					'-y',
					'@automattic/mcp-wordpress-remote@latest',
				),
				'env'     => array(
					'WP_API_URL'      => $mcp_endpoint,
					'WP_API_USERNAME' => 'YOUR_WORDPRESS_USERNAME',
					'WP_API_PASSWORD' => 'YOUR_APPLICATION_PASSWORD_ONLY',
					'OAUTH_ENABLED'   => 'false',
				),
			),
		),
	),
	JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES
);

if ( ! is_string( $stdio_config ) ) {
	$stdio_config = '';
}

$notice_messages = array(
	'installed'             => __( 'Dependency installed and activated.', 'mindcrafts-ai' ),
	'activated'             => __( 'Dependency activated.', 'mindcrafts-ai' ),
	'already_active'        => __( 'Dependency is already active.', 'mindcrafts-ai' ),
	'install_failed'        => __( 'Installation failed. Please install this dependency manually, then return to this setup screen.', 'mindcrafts-ai' ),
	'activate_failed'       => __( 'Activation failed. Please check the Plugins screen for the detailed WordPress error.', 'mindcrafts-ai' ),
	'install_not_supported' => __( 'Automatic installation is not available for this dependency.', 'mindcrafts-ai' ),
	'not_installed'         => __( 'This dependency is not installed yet.', 'mindcrafts-ai' ),
	'unknown_dependency'    => __( 'Unknown dependency.', 'mindcrafts-ai' ),
);

$notice_result = isset( $_GET['dependency_result'] ) ? sanitize_key( wp_unslash( $_GET['dependency_result'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
?>

<?php if ( $notice_result && isset( $notice_messages[ $notice_result ] ) ) : ?>
	<?php $is_err = in_array( $notice_result, array( 'install_failed', 'activate_failed', 'install_not_supported', 'not_installed', 'unknown_dependency' ), true ); ?>
	<div class="mindcrafts-ai-alert <?php echo esc_attr( $is_err ? 'mindcrafts-ai-alert--error' : 'mindcrafts-ai-alert--success' ); ?>">
		<span class="dashicons <?php echo esc_attr( $is_err ? 'dashicons-warning' : 'dashicons-yes-alt' ); ?>"></span>
		<span><?php echo esc_html( $notice_messages[ $notice_result ] ); ?></span>
	</div>
<!-- 1-Click Native Elementor Page Converter Banner -->
<div class="mindcrafts-ai-card" style="border: 2px solid #4F46E5; background: linear-gradient(135deg, rgba(79, 70, 229, 0.07) 0%, rgba(6, 182, 212, 0.07) 100%); margin-bottom: 24px; padding: 24px; border-radius: 14px;">
	<div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 16px;">
		<div style="flex: 1 1 500px;">
			<div style="display: inline-flex; align-items: center; gap: 6px; background: rgba(79, 70, 229, 0.15); border: 1px solid rgba(79, 70, 229, 0.35); padding: 4px 12px; border-radius: 9999px; margin-bottom: 10px;">
				<span class="dashicons dashicons-superhero" style="color: #4F46E5; font-size: 14px; width: 14px; height: 14px;"></span>
				<span style="font-size: 11px; font-weight: 700; color: #4F46E5; text-transform: uppercase;"><?php esc_html_e( 'Pixel-Perfect Migration Engine', 'mindcrafts-ai' ); ?></span>
			</div>
			<h2 style="margin: 0 0 8px 0; font-size: 20px; font-weight: 800; color: #1e293b;">
				<?php esc_html_e( '1-Click Convert All 6 Pages to Native Elementor Widgets', 'mindcrafts-ai' ); ?>
			</h2>
			<p style="margin: 0; color: #64748b; font-size: 13px; line-height: 1.6;">
				<?php esc_html_e( 'Automatically scans Home, Services, Projects, About, Pricing, and Contact. Decomposes raw HTML text-editor dumps into 100% native Elementor Flexbox Containers, Headings, Buttons, Images, and Text-Editors while preserving the exact dark visual theme.', 'mindcrafts-ai' ); ?>
			</p>
		</div>
		<div>
			<button type="button" id="mindcrafts-ai-migrate-pages-btn" class="button button-primary button-hero" style="background: linear-gradient(135deg, #4F46E5 0%, #06B6D4 100%); border: none; font-weight: 700; box-shadow: 0 4px 14px rgba(79, 70, 229, 0.4);">
				<span class="dashicons dashicons-update" style="margin-top: 4px;"></span>
				<span><?php esc_html_e( 'Convert All 6 Pages to Native Elementor Now', 'mindcrafts-ai' ); ?></span>
			</button>
		</div>
	</div>
	<div id="mindcrafts-ai-migration-results" style="display: none; margin-top: 18px; padding: 16px; background: #ffffff; border: 1px solid #e2e8f0; border-radius: 10px; box-shadow: 0 2px 8px rgba(0,0,0,0.05);"></div>
</div>

<div class="mindcrafts-ai-steps">

	<!-- STEP 1: Dependencies & Environment -->
	<div class="mindcrafts-ai-step-card">
		<div class="mindcrafts-ai-step-number">1</div>
		<div class="mindcrafts-ai-step-body">
			<h2 class="mindcrafts-ai-step-title"><?php esc_html_e( 'Core Engine & Architecture Requirements', 'mindcrafts-ai' ); ?></h2>
			<p class="mindcrafts-ai-step-desc">
				<?php esc_html_e( 'MindCrafts AI runs 100% standalone with bundled Abilities API and MCP Adapter. Verify that Elementor is active to allow AI agents to construct pages.', 'mindcrafts-ai' ); ?>
			</p>

			<div class="mindcrafts-ai-dependency-grid">
				<?php foreach ( $dependencies as $dependency_key => $dependency ) : ?>
					<?php $status = $this->get_dependency_status( $dependency_key ); ?>
					<div class="mindcrafts-ai-dependency-card">
						<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
							<strong style="font-size: 14px; color: var(--mc-text-main);"><?php echo esc_html( $dependency['label'] ); ?></strong>
							<?php if ( $status['is_active'] ) : ?>
								<span class="mindcrafts-ai-badge mindcrafts-ai-badge--active"><?php esc_html_e( 'Active / Ready', 'mindcrafts-ai' ); ?></span>
							<?php elseif ( $status['is_installed'] ) : ?>
								<span class="mindcrafts-ai-badge mindcrafts-ai-badge--read-only"><?php esc_html_e( 'Installed', 'mindcrafts-ai' ); ?></span>
							<?php else : ?>
								<span class="mindcrafts-ai-badge mindcrafts-ai-badge--inactive"><?php esc_html_e( 'Missing', 'mindcrafts-ai' ); ?></span>
							<?php endif; ?>
						</div>

						<p style="font-size: 12.5px; color: var(--mc-text-muted); margin: 0 0 10px 0; line-height: 1.4;">
							<?php echo esc_html( $dependency['description'] ); ?>
						</p>

						<div style="font-size: 11.5px; color: var(--mc-text-light); margin-bottom: 12px;">
							<span><?php esc_html_e( 'Source:', 'mindcrafts-ai' ); ?> <strong><?php echo esc_html( $dependency['source_label'] ); ?></strong></span>
						</div>

						<?php if ( ! $status['is_active'] ) : ?>
							<?php if ( $status['is_installed'] && current_user_can( 'activate_plugins' ) ) : ?>
								<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="margin: 0;">
									<input type="hidden" name="action" value="mindcrafts_ai_activate_dependency" />
									<input type="hidden" name="dependency" value="<?php echo esc_attr( $dependency_key ); ?>" />
									<?php wp_nonce_field( 'mindcrafts_ai_dependency_' . $dependency_key, 'mindcrafts_ai_nonce' ); ?>
									<button type="submit" class="mindcrafts-ai-btn mindcrafts-ai-btn--primary" style="width: 100%; justify-content: center;">
										<?php esc_html_e( 'Activate Plugin', 'mindcrafts-ai' ); ?>
									</button>
								</form>
							<?php elseif ( ! empty( $dependency['installable'] ) && current_user_can( 'install_plugins' ) ) : ?>
								<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="margin: 0;">
									<input type="hidden" name="action" value="mindcrafts_ai_install_dependency" />
									<input type="hidden" name="dependency" value="<?php echo esc_attr( $dependency_key ); ?>" />
									<?php wp_nonce_field( 'mindcrafts_ai_dependency_' . $dependency_key, 'mindcrafts_ai_nonce' ); ?>
									<button type="submit" class="mindcrafts-ai-btn mindcrafts-ai-btn--primary" style="width: 100%; justify-content: center;">
										<?php esc_html_e( 'Install & Activate', 'mindcrafts-ai' ); ?>
									</button>
								</form>
							<?php endif; ?>
						<?php else : ?>
							<div style="display: flex; align-items: center; gap: 6px; color: var(--mc-success); font-size: 12px; font-weight: 600;">
								<span class="dashicons dashicons-yes"></span>
								<span><?php esc_html_e( 'Operational', 'mindcrafts-ai' ); ?></span>
							</div>
						<?php endif; ?>
					</div>
				<?php endforeach; ?>
			</div>
		</div>
	</div>

	<!-- STEP 2: Application Password -->
	<div class="mindcrafts-ai-step-card">
		<div class="mindcrafts-ai-step-number">2</div>
		<div class="mindcrafts-ai-step-body">
			<h2 class="mindcrafts-ai-step-title"><?php esc_html_e( 'Generate WordPress Application Password', 'mindcrafts-ai' ); ?></h2>
			<p class="mindcrafts-ai-step-desc">
				<?php esc_html_e( 'For security, AI agents connect through an isolated WordPress Application Password rather than your account login password.', 'mindcrafts-ai' ); ?>
			</p>

			<div style="background: var(--mc-bg); border: 1px solid var(--mc-border); border-radius: var(--mc-radius-md); padding: 16px;">
				<ol style="margin: 0 0 0 18px; padding: 0; line-height: 1.6; font-size: 13px; color: var(--mc-text-main);">
					<li>
						<?php
						printf(
							/* translators: %s: profile URL */
							__( 'Navigate to your <a href="%s" target="_blank">WordPress Profile screen</a>.', 'mindcrafts-ai' ),
							esc_url( admin_url( 'profile.php#application-passwords-section' ) )
						);
						?>
					</li>
					<li><?php esc_html_e( 'Scroll down to the "Application Passwords" section.', 'mindcrafts-ai' ); ?></li>
					<li><?php esc_html_e( 'Enter a name (e.g. "Claude Elementor Agent" or "Cursor MCP") and click "Add New Application Password".', 'mindcrafts-ai' ); ?></li>
					<li><?php esc_html_e( 'Copy the generated 24-character password and insert it into your MCP configuration below.', 'mindcrafts-ai' ); ?></li>
				</ol>
			</div>
		</div>
	</div>

	<!-- STEP 3: AI Client Configuration -->
	<div class="mindcrafts-ai-step-card">
		<div class="mindcrafts-ai-step-number">3</div>
		<div class="mindcrafts-ai-step-body">
			<div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px; margin-bottom: 6px;">
				<h2 class="mindcrafts-ai-step-title" style="margin: 0;"><?php esc_html_e( 'Configure Your AI Client (Claude / Cursor / Windsurf)', 'mindcrafts-ai' ); ?></h2>
				<button type="button" class="mindcrafts-ai-btn mindcrafts-ai-btn--primary" id="mindcrafts-ai-copy-config-btn" data-copy-target="mindcrafts-ai-config-source">
					<span class="dashicons dashicons-admin-page" style="font-size: 15px; width: 15px; height: 15px;"></span>
					<span id="mindcrafts-ai-copy-config-text"><?php esc_html_e( 'Copy Config JSON', 'mindcrafts-ai' ); ?></span>
				</button>
			</div>
			<p class="mindcrafts-ai-step-desc">
				<?php esc_html_e( 'Add this server block to your claude_desktop_config.json, .mcp.json, or Cursor MCP settings. Replace USERNAME and APPLICATION_PASSWORD with your credentials.', 'mindcrafts-ai' ); ?>
			</p>

			<div class="mindcrafts-ai-code-block">
				<div class="mindcrafts-ai-code-header">
					<div class="mindcrafts-ai-code-dots">
						<span class="mindcrafts-ai-code-dot mindcrafts-ai-code-dot--red"></span>
						<span class="mindcrafts-ai-code-dot mindcrafts-ai-code-dot--yellow"></span>
						<span class="mindcrafts-ai-code-dot mindcrafts-ai-code-dot--green"></span>
					</div>
					<span class="mindcrafts-ai-code-title">claude_desktop_config.json / .mcp.json</span>
				</div>
				<pre><code><?php echo esc_html( $stdio_config ); ?></code></pre>
			</div>
			<textarea id="mindcrafts-ai-config-source" class="mindcrafts-ai-copy-source"><?php echo esc_textarea( $stdio_config ); ?></textarea>

			<!-- Quick Checklist -->
			<div style="margin-top: 18px; padding-top: 14px; border-top: 1px solid var(--mc-border); display: flex; flex-wrap: wrap; gap: 20px; font-size: 12.5px; color: var(--mc-text-muted);">
				<div style="display: flex; align-items: center; gap: 6px;">
					<span class="dashicons dashicons-yes" style="color: var(--mc-success);"></span>
					<span><?php esc_html_e( 'Zero-dependency standalone', 'mindcrafts-ai' ); ?></span>
				</div>
				<div style="display: flex; align-items: center; gap: 6px;">
					<span class="dashicons dashicons-yes" style="color: var(--mc-success);"></span>
					<span><?php printf( esc_html__( '%d tools registered', 'mindcrafts-ai' ), absint( $tool_count ) ); ?></span>
				</div>
				<div style="display: flex; align-items: center; gap: 6px;">
					<span class="dashicons dashicons-yes" style="color: var(--mc-success);"></span>
					<span><?php esc_html_e( 'Streamable MCP transport', 'mindcrafts-ai' ); ?></span>
				</div>
			</div>
		</div>
	</div>

</div>
