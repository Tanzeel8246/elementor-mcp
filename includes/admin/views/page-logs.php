<?php
/**
 * Logs tab view for the MindCrafts AI admin settings page.
 *
 * @package MindCrafts_AI
 * @since   2.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** @var MindCrafts_AI_Admin $this */
$log_entries = $this->get_log_entries( 50 );
$log_size    = $this->get_log_file_size();
?>

<div class="mindcrafts-ai-card" style="max-width: 1100px;">
	<div class="mindcrafts-ai-card-header">
		<div>
			<h2 class="mindcrafts-ai-card-title">
				<span class="dashicons dashicons-list-view"></span>
				<span><?php esc_html_e( 'MCP Server API Audit Logs', 'mindcrafts-ai' ); ?></span>
			</h2>
			<p style="margin: 4px 0 0 0; font-size: 13px; color: var(--mc-text-muted);">
				<?php
				printf(
					/* translators: %s: file size */
					esc_html__( 'Showing the most recent 50 API invocations. Current log archive size: %s', 'mindcrafts-ai' ),
					'<strong style="color: var(--mc-primary);">' . esc_html( $log_size ) . '</strong>'
				);
				?>
			</p>
		</div>

		<div style="display: flex; gap: 10px; align-items: center;">
			<button type="button" class="mindcrafts-ai-btn" id="mindcrafts-ai-clear-logs-btn">
				<span class="dashicons dashicons-trash" style="font-size: 16px; width: 16px; height: 16px; color: var(--mc-danger);"></span>
				<span><?php esc_html_e( 'Clear Audit Logs', 'mindcrafts-ai' ); ?></span>
			</button>
		</div>
	</div>

	<div id="mindcrafts-ai-logs-feedback" style="display: none; margin-bottom: 16px;" class="mindcrafts-ai-alert mindcrafts-ai-alert--success"></div>

	<?php if ( empty( $log_entries ) ) : ?>
		<div style="padding: 40px 20px; text-align: center; background: var(--mc-bg); border-radius: var(--mc-radius-md); border: 1px dashed var(--mc-border);">
			<span class="dashicons dashicons-media-text" style="font-size: 36px; width: 36px; height: 36px; color: var(--mc-text-light); margin-bottom: 8px;"></span>
			<p style="margin: 0; font-size: 14px; font-weight: 600; color: var(--mc-text-muted);">
				<?php esc_html_e( 'No MCP calls recorded yet.', 'mindcrafts-ai' ); ?>
			</p>
			<p style="margin: 4px 0 0 0; font-size: 12.5px; color: var(--mc-text-light);">
				<?php esc_html_e( 'Audit records will automatically populate as AI agents call tools.', 'mindcrafts-ai' ); ?>
			</p>
		</div>
	<?php else : ?>
		<div class="mindcrafts-ai-table-wrap">
			<table class="mindcrafts-ai-table" role="presentation">
				<thead>
					<tr>
						<th scope="col" style="width: 170px;"><?php esc_html_e( 'Timestamp', 'mindcrafts-ai' ); ?></th>
						<th scope="col" style="width: 80px;"><?php esc_html_e( 'User', 'mindcrafts-ai' ); ?></th>
						<th scope="col" style="width: 110px;"><?php esc_html_e( 'Method', 'mindcrafts-ai' ); ?></th>
						<th scope="col"><?php esc_html_e( 'Tool / Ability Invoked', 'mindcrafts-ai' ); ?></th>
						<th scope="col" style="width: 100px; text-align: center;"><?php esc_html_e( 'Status', 'mindcrafts-ai' ); ?></th>
					</tr>
				</thead>
				<tbody id="mindcrafts-ai-logs-tbody">
					<?php foreach ( $log_entries as $entry ) : ?>
						<?php
						$status = isset( $entry['status'] ) ? absint( $entry['status'] ) : 200;
						$is_ok  = $status >= 200 && $status < 300;
						?>
						<tr>
							<td style="font-family: ui-monospace, monospace; font-size: 12px; color: var(--mc-text-muted);">
								<?php echo esc_html( $entry['timestamp'] ?? '-' ); ?>
							</td>
							<td>
								<span style="font-weight: 600; font-size: 12px; color: var(--mc-text-main);">
									<?php echo esc_html( $entry['user_id'] ? '#' . $entry['user_id'] : 'Guest' ); ?>
								</span>
							</td>
							<td>
								<span style="font-family: ui-monospace, monospace; font-size: 11px; font-weight: 700; background: var(--mc-bg); padding: 2px 6px; border-radius: 4px; border: 1px solid var(--mc-border);">
									<?php echo esc_html( $entry['method'] ?? 'POST' ); ?>
								</span>
							</td>
							<td>
								<strong style="color: var(--mc-primary); font-size: 13px;">
									<?php echo esc_html( $entry['tool'] ?? '-' ); ?>
								</strong>
							</td>
							<td style="text-align: center;">
								<span class="mindcrafts-ai-badge <?php echo esc_attr( $is_ok ? 'mindcrafts-ai-badge--active' : 'mindcrafts-ai-badge--destructive' ); ?>">
									<?php echo esc_html( (string) $status ); ?>
								</span>
							</td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		</div>
	<?php endif; ?>
</div>
