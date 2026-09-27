<?php
/**
 * Diagnostics tab view for the MindCrafts AI admin settings page.
 *
 * @package MindCrafts_AI
 * @since   2.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** @var MindCrafts_AI_Admin $this */
$definitions    = $this->get_dependency_definitions();
$license_mgr    = MindCrafts_AI_License_Manager::instance();
$is_premium     = $license_mgr->is_premium_active();
$all_tools      = $this->get_all_tools();
$total_tools    = $this->get_total_tool_count();
$enabled_tools  = $this->get_enabled_tool_count();
$mcp_route      = rest_url( 'mcp/mindcrafts-ai-server' );
?>

<div class="mindcrafts-ai-diagnostics-wrap" style="max-width: 1100px; display: flex; flex-direction: column; gap: 24px;">

	<!-- Overview Metric Cards -->
	<div class="mindcrafts-ai-stats-bar">
		<div class="mindcrafts-ai-stat-card mindcrafts-ai-stat-card--success">
			<div class="mindcrafts-ai-stat-icon-wrap">
				<span class="dashicons dashicons-heart"></span>
			</div>
			<div class="mindcrafts-ai-stat-data">
				<span class="mindcrafts-ai-stat-value" style="color: var(--mc-success);"><?php esc_html_e( 'Healthy', 'mindcrafts-ai' ); ?></span>
				<span class="mindcrafts-ai-stat-label"><?php esc_html_e( 'System Status', 'mindcrafts-ai' ); ?></span>
			</div>
		</div>

		<div class="mindcrafts-ai-stat-card">
			<div class="mindcrafts-ai-stat-icon-wrap">
				<span class="dashicons dashicons-hammer"></span>
			</div>
			<div class="mindcrafts-ai-stat-data">
				<span class="mindcrafts-ai-stat-value"><?php echo esc_html( (string) $total_tools ); ?></span>
				<span class="mindcrafts-ai-stat-label"><?php esc_html_e( 'Registered Tools', 'mindcrafts-ai' ); ?></span>
			</div>
		</div>

		<div class="mindcrafts-ai-stat-card mindcrafts-ai-stat-card--accent">
			<div class="mindcrafts-ai-stat-icon-wrap">
				<span class="dashicons dashicons-awards"></span>
			</div>
			<div class="mindcrafts-ai-stat-data">
				<span class="mindcrafts-ai-stat-value" style="font-size: 18px; color: var(--mc-accent);">
					<?php echo $is_premium ? esc_html__( 'Pro Active', 'mindcrafts-ai' ) : esc_html__( 'Community', 'mindcrafts-ai' ); ?>
				</span>
				<span class="mindcrafts-ai-stat-label"><?php esc_html_e( 'License Tier', 'mindcrafts-ai' ); ?></span>
			</div>
		</div>

		<div class="mindcrafts-ai-stat-card mindcrafts-ai-stat-card--info">
			<div class="mindcrafts-ai-stat-icon-wrap">
				<span class="dashicons dashicons-rest-api"></span>
			</div>
			<div class="mindcrafts-ai-stat-data">
				<span class="mindcrafts-ai-stat-value" style="color: var(--mc-info);"><?php echo esc_html( (string) $enabled_tools ); ?></span>
				<span class="mindcrafts-ai-stat-label"><?php esc_html_e( 'Active Tools', 'mindcrafts-ai' ); ?></span>
			</div>
		</div>
	</div>

	<!-- System & Dependency Health Check -->
	<div class="mindcrafts-ai-card">
		<div class="mindcrafts-ai-card-header">
			<h2 class="mindcrafts-ai-card-title">
				<span class="dashicons dashicons-shield"></span>
				<span><?php esc_html_e( 'System & Dependency Health Check', 'mindcrafts-ai' ); ?></span>
			</h2>
		</div>

		<div class="mindcrafts-ai-table-wrap">
			<table class="mindcrafts-ai-table" role="presentation">
				<thead>
					<tr>
						<th><?php esc_html_e( 'Component', 'mindcrafts-ai' ); ?></th>
						<th><?php esc_html_e( 'Required Version / Requirement', 'mindcrafts-ai' ); ?></th>
						<th><?php esc_html_e( 'Installed', 'mindcrafts-ai' ); ?></th>
						<th style="text-align: center;"><?php esc_html_e( 'Status', 'mindcrafts-ai' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<tr>
						<td><strong><?php esc_html_e( 'PHP Version', 'mindcrafts-ai' ); ?></strong></td>
						<td>>= 7.4</td>
						<td><code><?php echo esc_html( PHP_VERSION ); ?></code></td>
						<td style="text-align: center;">
							<span class="mindcrafts-ai-badge mindcrafts-ai-badge--active"><?php esc_html_e( 'Pass', 'mindcrafts-ai' ); ?></span>
						</td>
					</tr>
					<tr>
						<td><strong><?php esc_html_e( 'WordPress Core', 'mindcrafts-ai' ); ?></strong></td>
						<td>>= 6.8</td>
						<td><code><?php echo esc_html( get_bloginfo( 'version' ) ); ?></code></td>
						<td style="text-align: center;">
							<span class="mindcrafts-ai-badge mindcrafts-ai-badge--active"><?php esc_html_e( 'Pass', 'mindcrafts-ai' ); ?></span>
						</td>
					</tr>
					<?php foreach ( $definitions as $dep_key => $dep ) : ?>
						<?php $status = $this->get_dependency_status( $dep_key ); ?>
						<tr>
							<td><strong><?php echo esc_html( $dep['label'] ); ?></strong></td>
							<td><?php echo esc_html( $dep['source_label'] ); ?></td>
							<td><?php echo $status['is_installed'] ? esc_html__( 'Installed', 'mindcrafts-ai' ) : esc_html__( 'Missing', 'mindcrafts-ai' ); ?></td>
							<td style="text-align: center;">
								<?php if ( $status['is_active'] ) : ?>
									<span class="mindcrafts-ai-badge mindcrafts-ai-badge--active"><?php esc_html_e( 'Active', 'mindcrafts-ai' ); ?></span>
								<?php else : ?>
									<span class="mindcrafts-ai-badge mindcrafts-ai-badge--inactive"><?php esc_html_e( 'Inactive', 'mindcrafts-ai' ); ?></span>
								<?php endif; ?>
							</td>
						</tr>
					<?php endforeach; ?>
					<tr>
						<td><strong><?php esc_html_e( 'Elementor Pro', 'mindcrafts-ai' ); ?></strong></td>
						<td><?php esc_html_e( 'Optional (Enables Theme Builder & Popups)', 'mindcrafts-ai' ); ?></td>
						<td><?php echo defined( 'ELEMENTOR_PRO_VERSION' ) ? esc_html( ELEMENTOR_PRO_VERSION ) : esc_html__( 'Not Installed', 'mindcrafts-ai' ); ?></td>
						<td style="text-align: center;">
							<?php if ( defined( 'ELEMENTOR_PRO_VERSION' ) ) : ?>
								<span class="mindcrafts-ai-badge mindcrafts-ai-badge--pro"><?php esc_html_e( 'Pro Active', 'mindcrafts-ai' ); ?></span>
							<?php else : ?>
								<span class="mindcrafts-ai-badge mindcrafts-ai-badge--free"><?php esc_html_e( 'Core Free Active', 'mindcrafts-ai' ); ?></span>
							<?php endif; ?>
						</td>
					</tr>
					<tr>
						<td><strong><?php esc_html_e( 'WooCommerce', 'mindcrafts-ai' ); ?></strong></td>
						<td><?php esc_html_e( 'Optional (Enables Store Builder Tools)', 'mindcrafts-ai' ); ?></td>
						<td><?php echo class_exists( 'WooCommerce' ) ? esc_html( defined( 'WC_VERSION' ) ? WC_VERSION : 'Active' ) : esc_html__( 'Not Installed', 'mindcrafts-ai' ); ?></td>
						<td style="text-align: center;">
							<?php if ( class_exists( 'WooCommerce' ) ) : ?>
								<span class="mindcrafts-ai-badge mindcrafts-ai-badge--woo"><?php esc_html_e( 'Store Active', 'mindcrafts-ai' ); ?></span>
							<?php else : ?>
								<span class="mindcrafts-ai-badge" style="background: var(--mc-bg); color: var(--mc-text-light);"><?php esc_html_e( 'Inactive', 'mindcrafts-ai' ); ?></span>
							<?php endif; ?>
						</td>
					</tr>
				</tbody>
			</table>
		</div>
	</div>

	<!-- Registered Abilities Summary -->
	<div class="mindcrafts-ai-card">
		<div class="mindcrafts-ai-card-header">
			<h2 class="mindcrafts-ai-card-title">
				<span class="dashicons dashicons-category"></span>
				<span><?php esc_html_e( 'Abilities Registry by Domain', 'mindcrafts-ai' ); ?></span>
			</h2>
		</div>

		<div class="mindcrafts-ai-table-wrap">
			<table class="mindcrafts-ai-table" role="presentation">
				<thead>
					<tr>
						<th><?php esc_html_e( 'Category', 'mindcrafts-ai' ); ?></th>
						<th style="text-align: center; width: 120px;"><?php esc_html_e( 'Tools Count', 'mindcrafts-ai' ); ?></th>
						<th><?php esc_html_e( 'Sample Abilities', 'mindcrafts-ai' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $all_tools as $cat_key => $cat ) : ?>
						<tr>
							<td><strong><?php echo esc_html( $cat['label'] ); ?></strong></td>
							<td style="text-align: center;">
								<span class="mindcrafts-ai-badge mindcrafts-ai-badge--read-only"><?php echo esc_html( (string) count( $cat['tools'] ) ); ?></span>
							</td>
							<td style="font-size: 12px; color: var(--mc-text-muted);">
								<code><?php echo esc_html( implode( '</code>, <code>', array_slice( array_keys( $cat['tools'] ), 0, 3 ) ) ); ?></code>
								<?php if ( count( $cat['tools'] ) > 3 ) : ?>
									<em>+<?php echo esc_html( (string) ( count( $cat['tools'] ) - 3 ) ); ?> more</em>
								<?php endif; ?>
							</td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		</div>

		<div style="margin-top: 20px; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 10px; padding: 14px; background: var(--mc-bg); border-radius: var(--mc-radius-md);">
			<div>
				<strong style="color: var(--mc-text-main); font-size: 13px;"><?php esc_html_e( 'MCP Server Endpoint:', 'mindcrafts-ai' ); ?></strong>
				<code style="margin-left: 6px; font-size: 12px;"><?php echo esc_html( $mcp_route ); ?></code>
			</div>
			<button type="button" class="mindcrafts-ai-copy-slug-btn" data-copy-text="<?php echo esc_attr( $mcp_route ); ?>">
				<span class="dashicons dashicons-admin-page" style="font-size: 14px; width: 14px; height: 14px;"></span>
				<span><?php esc_html_e( 'Copy Endpoint', 'mindcrafts-ai' ); ?></span>
			</button>
		</div>
	</div>

</div>
