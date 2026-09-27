<?php
/**
 * License tab view for the MindCrafts AI admin settings page.
 *
 * @package MindCrafts_AI
 * @since   2.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$license_manager = MindCrafts_AI_License_Manager::instance();
$is_active       = $license_manager->is_premium_active();
$current_key     = $license_manager->get_license_key();
$status          = $license_manager->get_license_status();
?>

<div class="mindcrafts-ai-license-card" style="max-width: 900px;">
	<!-- Banner -->
	<div class="mindcrafts-ai-license-banner">
		<div>
			<div style="display: inline-flex; align-items: center; gap: 6px; background: rgba(255,255,255,0.15); padding: 3px 10px; border-radius: 9999px; font-size: 11px; font-weight: 700; text-transform: uppercase; margin-bottom: 8px;">
				<span class="dashicons dashicons-awards" style="font-size: 14px; width: 14px; height: 14px; color: #F59E0B;"></span>
				<span><?php esc_html_e( 'Enterprise & Pro Engine', 'mindcrafts-ai' ); ?></span>
			</div>
			<h2><?php esc_html_e( 'MindCrafts AI Pro License', 'mindcrafts-ai' ); ?></h2>
			<p>
				<?php esc_html_e( 'Unlock advanced AI automation: Theme Builder locations, SEO meta generation, cloud template marketplace, and multi-break-point responsive optimizations.', 'mindcrafts-ai' ); ?>
			</p>
		</div>

		<div>
			<?php if ( $is_active ) : ?>
				<span class="mindcrafts-ai-badge mindcrafts-ai-badge--active" style="padding: 8px 16px; font-size: 12px; background: #ECFDF5; color: #065F46; border: 1px solid #A7F3D0;">
					<span class="dashicons dashicons-yes" style="font-size: 16px; width: 16px; height: 16px; margin-right: 4px;"></span>
					<?php esc_html_e( 'Pro License Active', 'mindcrafts-ai' ); ?>
				</span>
			<?php elseif ( ! empty( $current_key ) ) : ?>
				<span class="mindcrafts-ai-badge mindcrafts-ai-badge--destructive" style="padding: 8px 16px; font-size: 12px;">
					<?php echo esc_html( ucfirst( $status ) ); ?>
				</span>
			<?php else : ?>
				<span class="mindcrafts-ai-badge" style="background: rgba(255,255,255,0.2); color: #FFFFFF; padding: 8px 16px; font-size: 12px;">
					<?php esc_html_e( 'Community Edition', 'mindcrafts-ai' ); ?>
				</span>
			<?php endif; ?>
		</div>
	</div>

	<!-- Form Area -->
	<div class="mindcrafts-ai-license-form-area">
		<div id="mindcrafts-ai-license-feedback" style="display: none; margin-bottom: 20px;" class="mindcrafts-ai-alert"></div>

		<div>
			<label for="mindcrafts_ai_license_key" style="display: block; font-weight: 700; font-size: 14px; color: var(--mc-text-main); margin-bottom: 6px;">
				<?php esc_html_e( 'License Key', 'mindcrafts-ai' ); ?>
			</label>
			<p style="font-size: 12.5px; color: var(--mc-text-muted); margin: 0 0 10px 0;">
				<?php esc_html_e( 'Enter your MindCrafts AI Pro key (format: MC-XXXX-XXXX-XXXX-XXXX) to validate your site.', 'mindcrafts-ai' ); ?>
			</p>

			<div class="mindcrafts-ai-key-input-wrap">
				<input
					type="password"
					id="mindcrafts_ai_license_key"
					name="mindcrafts_ai_license_key"
					value="<?php echo esc_attr( $current_key ); ?>"
					placeholder="MC-XXXX-XXXX-XXXX-XXXX"
					<?php echo $is_active ? 'readonly' : ''; ?>
				/>
				<button type="button" class="mindcrafts-ai-btn" id="mindcrafts-ai-toggle-key" title="<?php esc_attr_e( 'Toggle Password Visibility', 'mindcrafts-ai' ); ?>">
					<span class="dashicons dashicons-visibility"></span>
				</button>
			</div>

			<div style="margin-top: 18px; display: flex; gap: 10px; align-items: center;">
				<button type="button" class="mindcrafts-ai-btn mindcrafts-ai-btn--primary" id="mindcrafts-ai-activate-btn" <?php echo $is_active ? 'disabled' : ''; ?>>
					<span class="dashicons dashicons-unlock" style="font-size: 16px; width: 16px; height: 16px;"></span>
					<span><?php esc_html_e( 'Activate Pro License', 'mindcrafts-ai' ); ?></span>
				</button>
				<button type="button" class="mindcrafts-ai-btn" id="mindcrafts-ai-deactivate-btn" <?php echo ! $is_active ? 'disabled' : ''; ?>>
					<span class="dashicons dashicons-lock" style="font-size: 16px; width: 16px; height: 16px;"></span>
					<span><?php esc_html_e( 'Deactivate', 'mindcrafts-ai' ); ?></span>
				</button>
			</div>
		</div>

		<!-- Pro Features Grid -->
		<div class="mindcrafts-ai-pro-features-grid">
			<div class="mindcrafts-ai-feature-item">
				<span class="dashicons dashicons-search mindcrafts-ai-feature-icon"></span>
				<div class="mindcrafts-ai-feature-text">
					<strong><?php esc_html_e( 'AI SEO Meta Optimizer', 'mindcrafts-ai' ); ?></strong>
					<span><?php esc_html_e( 'Analyze and optimize headings, titles, descriptions & OpenGraph cards.', 'mindcrafts-ai' ); ?></span>
				</div>
			</div>

			<div class="mindcrafts-ai-feature-item">
				<span class="dashicons dashicons-cloud-saved mindcrafts-ai-feature-icon"></span>
				<div class="mindcrafts-ai-feature-text">
					<strong><?php esc_html_e( 'Cloud Template Marketplace', 'mindcrafts-ai' ); ?></strong>
					<span><?php esc_html_e( 'Import pre-built high-converting landing templates into any page.', 'mindcrafts-ai' ); ?></span>
				</div>
			</div>

			<div class="mindcrafts-ai-feature-item">
				<span class="dashicons dashicons-cart mindcrafts-ai-feature-icon"></span>
				<div class="mindcrafts-ai-feature-text">
					<strong><?php esc_html_e( 'WooCommerce Automated Store', 'mindcrafts-ai' ); ?></strong>
					<span><?php esc_html_e( 'Single-command product single and archive template creation.', 'mindcrafts-ai' ); ?></span>
				</div>
			</div>

			<div class="mindcrafts-ai-feature-item">
				<span class="dashicons dashicons-shield-alt mindcrafts-ai-feature-icon"></span>
				<div class="mindcrafts-ai-feature-text">
					<strong><?php esc_html_e( 'Enterprise Priority Support', 'mindcrafts-ai' ); ?></strong>
					<span><?php esc_html_e( 'Priority updates and direct architectural guidance for AI pipelines.', 'mindcrafts-ai' ); ?></span>
				</div>
			</div>
		</div>
	</div>
</div>
