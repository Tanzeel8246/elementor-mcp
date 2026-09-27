<?php
/**
 * Tools tab view for the MindCrafts AI admin settings page.
 *
 * Displays all MCP tools grouped by category with live search, filters,
 * category toggles, and modern card switch controls.
 *
 * @package MindCrafts_AI
 * @since   1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** @var MindCrafts_AI_Admin $this */
$all_tools     = $this->get_all_tools();
$disabled      = get_option( MindCrafts_AI_Admin::OPTION_DISABLED_TOOLS, array() );
$enabled_count = $this->get_enabled_tool_count();
$total_count   = $this->get_total_tool_count();
$tool_ids      = array_flip( $this->get_all_tool_slugs() );
$enabled_ids   = array();

$pro_count = 0;
$woo_count = 0;

foreach ( $all_tools as $category_id => $category ) {
	foreach ( $category['tools'] as $slug => $tool ) {
		if ( ! in_array( $slug, $disabled, true ) && isset( $tool_ids[ $slug ] ) ) {
			$enabled_ids[] = $tool_ids[ $slug ];
		}
		if ( in_array( 'pro', $tool['badges'], true ) ) {
			$pro_count++;
		}
		if ( 'woocommerce' === $category_id || in_array( 'woo', $tool['badges'], true ) ) {
			$woo_count++;
		}
	}
}

$category_icons = array(
	'query'         => 'dashicons-search',
	'page'          => 'dashicons-admin-page',
	'layout'        => 'dashicons-layout',
	'widget'        => 'dashicons-grid-view',
	'pro_widget'    => 'dashicons-superhero',
	'theme_builder' => 'dashicons-admin-appearance',
	'popup'         => 'dashicons-external',
	'dynamic_tags'  => 'dashicons-tag',
	'responsive'    => 'dashicons-smartphone',
	'template'      => 'dashicons-portfolio',
	'global'        => 'dashicons-admin-customizer',
	'composite'     => 'dashicons-rest-api',
	'stock_images'  => 'dashicons-format-image',
	'woocommerce'   => 'dashicons-cart',
);
?>

<?php if ( isset( $_GET['settings-updated'] ) && 'true' === sanitize_key( wp_unslash( $_GET['settings-updated'] ) ) ) : ?>
	<div class="mindcrafts-ai-alert mindcrafts-ai-alert--success">
		<span class="dashicons dashicons-yes-alt" style="color: var(--mc-success);"></span>
		<span><?php esc_html_e( 'MindCrafts AI tool configuration saved successfully.', 'mindcrafts-ai' ); ?></span>
	</div>
<?php endif; ?>

<!-- Stats Metrics Bar -->
<div class="mindcrafts-ai-stats-bar">
	<div class="mindcrafts-ai-stat-card">
		<div class="mindcrafts-ai-stat-icon-wrap">
			<span class="dashicons dashicons-admin-tools"></span>
		</div>
		<div class="mindcrafts-ai-stat-data">
			<span class="mindcrafts-ai-stat-value" id="mindcrafts-ai-stat-total"><?php echo esc_html( (string) $total_count ); ?></span>
			<span class="mindcrafts-ai-stat-label"><?php esc_html_e( 'Total MCP Tools', 'mindcrafts-ai' ); ?></span>
		</div>
	</div>

	<div class="mindcrafts-ai-stat-card mindcrafts-ai-stat-card--success">
		<div class="mindcrafts-ai-stat-icon-wrap">
			<span class="dashicons dashicons-yes-alt"></span>
		</div>
		<div class="mindcrafts-ai-stat-data">
			<span class="mindcrafts-ai-stat-value" id="mindcrafts-ai-stat-enabled" style="color: var(--mc-success);"><?php echo esc_html( (string) $enabled_count ); ?></span>
			<span class="mindcrafts-ai-stat-label"><?php esc_html_e( 'Active & Enabled', 'mindcrafts-ai' ); ?></span>
		</div>
	</div>

	<div class="mindcrafts-ai-stat-card mindcrafts-ai-stat-card--accent">
		<div class="mindcrafts-ai-stat-icon-wrap">
			<span class="dashicons dashicons-star-filled"></span>
		</div>
		<div class="mindcrafts-ai-stat-data">
			<span class="mindcrafts-ai-stat-value" style="color: var(--mc-accent);"><?php echo esc_html( (string) $pro_count ); ?></span>
			<span class="mindcrafts-ai-stat-label"><?php esc_html_e( 'Pro & Theme Tools', 'mindcrafts-ai' ); ?></span>
		</div>
	</div>

	<div class="mindcrafts-ai-stat-card mindcrafts-ai-stat-card--info">
		<div class="mindcrafts-ai-stat-icon-wrap">
			<span class="dashicons dashicons-cart"></span>
		</div>
		<div class="mindcrafts-ai-stat-data">
			<span class="mindcrafts-ai-stat-value" style="color: var(--mc-info);"><?php echo esc_html( (string) $woo_count ); ?></span>
			<span class="mindcrafts-ai-stat-label"><?php esc_html_e( 'WooCommerce Tools', 'mindcrafts-ai' ); ?></span>
		</div>
	</div>
</div>

<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" id="mindcrafts-ai-tools-form">
	<?php wp_nonce_field( 'mindcrafts_ai_save_tools', 'mindcrafts_ai_nonce' ); ?>
	<input type="hidden" name="action" value="mindcrafts_ai_save_tools" />
	<input type="hidden" name="mindcrafts_ai_enabled_ids" id="mindcrafts-ai-enabled-ids" value="<?php echo esc_attr( implode( ',', $enabled_ids ) ); ?>" />

	<!-- Interactive Search & Filter Toolbar -->
	<div class="mindcrafts-ai-toolbar">
		<div class="mindcrafts-ai-search-box">
			<span class="dashicons dashicons-search"></span>
			<input
				type="search"
				id="mindcrafts-ai-tool-search"
				class="mindcrafts-ai-search-input"
				placeholder="<?php esc_attr_e( 'Filter tools by name, slug or description (e.g. heading, woo, container)...', 'mindcrafts-ai' ); ?>"
				autocomplete="off"
			/>
		</div>

		<div class="mindcrafts-ai-filter-chips">
			<button type="button" class="mindcrafts-ai-chip is-active" data-filter="all"><?php esc_html_e( 'All', 'mindcrafts-ai' ); ?></button>
			<button type="button" class="mindcrafts-ai-chip" data-filter="pro"><?php esc_html_e( 'Pro', 'mindcrafts-ai' ); ?></button>
			<button type="button" class="mindcrafts-ai-chip" data-filter="woo"><?php esc_html_e( 'WooCommerce', 'mindcrafts-ai' ); ?></button>
			<button type="button" class="mindcrafts-ai-chip" data-filter="read-only"><?php esc_html_e( 'Read-Only', 'mindcrafts-ai' ); ?></button>
			<button type="button" class="mindcrafts-ai-chip" data-filter="destructive"><?php esc_html_e( 'Destructive', 'mindcrafts-ai' ); ?></button>
		</div>

		<div class="mindcrafts-ai-bulk-actions">
			<button type="button" class="mindcrafts-ai-btn mindcrafts-ai-enable-all">
				<span class="dashicons dashicons-yes" style="font-size: 16px; width: 16px; height: 16px;"></span>
				<?php esc_html_e( 'Enable All', 'mindcrafts-ai' ); ?>
			</button>
			<button type="button" class="mindcrafts-ai-btn mindcrafts-ai-disable-all">
				<span class="dashicons dashicons-no-alt" style="font-size: 16px; width: 16px; height: 16px;"></span>
				<?php esc_html_e( 'Disable All', 'mindcrafts-ai' ); ?>
			</button>
			<button type="submit" class="mindcrafts-ai-btn mindcrafts-ai-btn--primary">
				<span class="dashicons dashicons-saved" style="font-size: 16px; width: 16px; height: 16px;"></span>
				<?php esc_html_e( 'Save Changes', 'mindcrafts-ai' ); ?>
			</button>
		</div>
	</div>

	<!-- Category Sections -->
	<?php foreach ( $all_tools as $category_id => $category ) : ?>
		<?php
		$icon       = $category_icons[ $category_id ] ?? 'dashicons-admin-generic';
		$cat_total  = count( $category['tools'] );
		$cat_active = 0;
		foreach ( $category['tools'] as $slug => $tool ) {
			if ( ! in_array( $slug, $disabled, true ) ) {
				$cat_active++;
			}
		}
		?>
		<div class="mindcrafts-ai-category" data-category="<?php echo esc_attr( $category_id ); ?>">
			<div class="mindcrafts-ai-category-header">
				<div class="mindcrafts-ai-category-title-group">
					<span class="dashicons <?php echo esc_attr( $icon ); ?> mindcrafts-ai-category-icon"></span>
					<h3 class="mindcrafts-ai-category-title"><?php echo esc_html( $category['label'] ); ?></h3>
					<span class="mindcrafts-ai-category-count" data-category-count="<?php echo esc_attr( $category_id ); ?>">
						<?php printf( esc_html__( '%1$d / %2$d active', 'mindcrafts-ai' ), absint( $cat_active ), absint( $cat_total ) ); ?>
					</span>
				</div>

				<div class="mindcrafts-ai-category-actions">
					<button type="button" class="mindcrafts-ai-cat-btn mindcrafts-ai-cat-enable-all"><?php esc_html_e( 'Select All', 'mindcrafts-ai' ); ?></button>
					<span style="color: var(--mc-border);">|</span>
					<button type="button" class="mindcrafts-ai-cat-btn mindcrafts-ai-cat-disable-all"><?php esc_html_e( 'None', 'mindcrafts-ai' ); ?></button>
					<span class="dashicons dashicons-arrow-down-alt2 mindcrafts-ai-toggle-arrow"></span>
				</div>
			</div>

			<div class="mindcrafts-ai-tools-grid">
				<?php foreach ( $category['tools'] as $slug => $tool ) : ?>
					<?php
					$is_enabled = ! in_array( $slug, $disabled, true );
					$badges_str = implode( ' ', $tool['badges'] );
					if ( 'woocommerce' === $category_id && ! in_array( 'woo', $tool['badges'], true ) ) {
						$badges_str .= ' woo';
					}
					?>
					<div
						class="mindcrafts-ai-tool-card <?php echo esc_attr( $is_enabled ? 'is-enabled' : 'is-disabled' ); ?>"
						data-slug="<?php echo esc_attr( $slug ); ?>"
						data-badges="<?php echo esc_attr( $badges_str ); ?>"
						data-category="<?php echo esc_attr( $category_id ); ?>"
					>
						<div class="mindcrafts-ai-card-top">
							<div class="mindcrafts-ai-card-header-left">
								<div class="mindcrafts-ai-tool-name">
									<span><?php echo esc_html( $tool['label'] ); ?></span>
									<?php foreach ( $tool['badges'] as $badge ) : ?>
										<span class="mindcrafts-ai-badge mindcrafts-ai-badge--<?php echo esc_attr( $badge ); ?>">
											<?php echo esc_html( $badge ); ?>
										</span>
									<?php endforeach; ?>
								</div>
								<div class="mindcrafts-ai-tool-desc">
									<?php echo esc_html( $tool['description'] ); ?>
								</div>
							</div>

							<!-- SaaS Toggle Switch -->
							<label class="mindcrafts-ai-switch" title="<?php esc_attr_e( 'Toggle Tool', 'mindcrafts-ai' ); ?>">
								<input
									type="checkbox"
									data-tool-id="<?php echo esc_attr( $tool_ids[ $slug ] ?? '' ); ?>"
									<?php checked( $is_enabled ); ?>
								/>
								<span class="mindcrafts-ai-slider"></span>
							</label>
						</div>

						<div class="mindcrafts-ai-card-bottom">
							<span class="mindcrafts-ai-slug-tag"><?php echo esc_html( $slug ); ?></span>
							<button type="button" class="mindcrafts-ai-copy-slug-btn" data-copy-text="<?php echo esc_attr( $slug ); ?>" title="<?php esc_attr_e( 'Copy Tool Identifier', 'mindcrafts-ai' ); ?>">
								<span class="dashicons dashicons-admin-page" style="font-size: 14px; width: 14px; height: 14px;"></span>
								<span><?php esc_html_e( 'Copy', 'mindcrafts-ai' ); ?></span>
							</button>
						</div>
					</div>
				<?php endforeach; ?>
			</div>
		</div>
	<?php endforeach; ?>

	<div style="margin-top: 24px; display: flex; justify-content: flex-end;">
		<button type="submit" class="mindcrafts-ai-btn mindcrafts-ai-btn--primary" style="padding: 10px 24px; font-size: 14px;">
			<span class="dashicons dashicons-saved" style="font-size: 18px; width: 18px; height: 18px;"></span>
			<?php esc_html_e( 'Save Tool Configuration', 'mindcrafts-ai' ); ?>
		</button>
	</div>
</form>
