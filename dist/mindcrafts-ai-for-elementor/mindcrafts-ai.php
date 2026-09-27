<?php
/**
 * Plugin Name:       MindCrafts AI for Elementor
 * Description:       Transform Elementor with AI power — build complete pages from a single prompt, manage widgets, layouts, templates, WooCommerce, SEO, and more via MCP tools for AI agents like Claude and Cursor.
 * Version:           2.1.0
 * Requires at least: 6.0
 * Tested up to:      6.7
 * Requires PHP:      7.4
 * Author:            MindCrafts AI
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       mindcrafts-ai
 * Domain Path:       /languages
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Plugin constants.
define( 'MINDCRAFTS_AI_VERSION', '2.1.0' );
define( 'MINDCRAFTS_AI_DIR', plugin_dir_path( __FILE__ ) );
define( 'MINDCRAFTS_AI_URL', plugin_dir_url( __FILE__ ) );
define( 'MINDCRAFTS_AI_BASENAME', plugin_basename( __FILE__ ) );
// Ensure SSL detection behind reverse proxies/Cloudflare so WordPress enables Application Passwords.
if ( ( isset( $_SERVER['HTTP_X_FORWARDED_PROTO'] ) && 'https' === $_SERVER['HTTP_X_FORWARDED_PROTO'] ) || ( isset( $_SERVER['HTTPS'] ) && 'off' === $_SERVER['HTTPS'] ) || ( function_exists( 'is_ssl' ) && ! is_ssl() && 0 === strpos( get_option( 'siteurl' ), 'https://' ) ) ) {
	$_SERVER['HTTPS'] = 'on';
}

// Ensure Application Passwords are fully enabled for MCP agents across all filters at high priority.
add_filter( 'wp_is_application_passwords_available', '__return_true', 999 );
add_filter( 'wp_is_application_passwords_available_for_user', '__return_true', 999 );
add_filter( 'wp_is_application_passwords_supported_per_user', '__return_true', 999 );
/**
 * Loads plugin translations.
 *
 * @since 2.0.0
 */
function mindcrafts_ai_load_textdomain(): void {
	load_plugin_textdomain(
		'mindcrafts-ai',
		false,
		dirname( MINDCRAFTS_AI_BASENAME ) . '/languages'
	);
}
add_action( 'plugins_loaded', 'mindcrafts_ai_load_textdomain', 5 );

/**
 * Boots the admin page even when runtime dependencies are missing.
 *
 * @since 2.0.0
 */
function mindcrafts_ai_boot_admin(): void {
	static $booted = false;

	if ( $booted || ! is_admin() ) {
		return;
	}

	try {
		require_once MINDCRAFTS_AI_DIR . 'includes/class-license-manager.php';
		require_once MINDCRAFTS_AI_DIR . 'includes/admin/class-admin.php';

		$admin = new MindCrafts_AI_Admin();
		$admin->init();

		$booted = true;

		do_action( 'mindcrafts_ai_admin_initialized' );
	} catch ( \Throwable $e ) {
		add_action( 'admin_notices', function() use ( $e ) {
			echo '<div class="notice notice-error"><p><strong>MindCrafts AI Admin Init Error:</strong> ' . esc_html( $e->getMessage() ) . ' <small>(' . esc_html( $e->getFile() ) . ':' . (int) $e->getLine() . ')</small></p></div>';
		} );
	}
}

/**
 * Checks that all required dependencies are available.
 *
 * @since 2.0.0
 *
 * @return bool True if all dependencies are met.
 */
function mindcrafts_ai_check_dependencies(): bool {
	// Ensure bundled fallback for Abilities API and MCP Adapter is loaded first.
	if ( file_exists( MINDCRAFTS_AI_DIR . 'includes/vendor/autoload.php' ) ) {
		require_once MINDCRAFTS_AI_DIR . 'includes/vendor/autoload.php';
	}

	$missing = array();

	// Elementor is the only required external dependency.
	if ( ! did_action( 'elementor/loaded' ) ) {
		$missing[] = 'Elementor';
	}

	if ( ! empty( $missing ) ) {
		add_action( 'admin_notices', function () use ( $missing ) {
			echo '<div class="notice notice-error"><p>';
			echo wp_kses(
				sprintf(
					/* translators: %s: comma-separated list of missing dependencies */
					__( 'MindCrafts AI for Elementor requires the following to be installed and active: <strong>%s</strong>', 'mindcrafts-ai' ),
					esc_html( implode( ', ', $missing ) )
				),
				array( 'strong' => array() )
			);
			echo '</p></div>';
		} );

		return false;
	}

	return true;
}

/**
 * Initializes the plugin.
 *
 * Hooked to `plugins_loaded` at priority 20 to ensure Elementor and
 * other dependencies are loaded first.
 *
 * @since 2.0.0
 */
function mindcrafts_ai_init(): void {
	try {
		// 1. Boot bundled vendor autoloader (Abilities API + MCP Adapter).
		require_once MINDCRAFTS_AI_DIR . 'includes/vendor/autoload.php';

		mindcrafts_ai_boot_admin();

		if ( ! mindcrafts_ai_check_dependencies() ) {
			return;
		}

		// Load class files.
		require_once MINDCRAFTS_AI_DIR . 'includes/class-id-generator.php';
		require_once MINDCRAFTS_AI_DIR . 'includes/class-elementor-data.php';
		require_once MINDCRAFTS_AI_DIR . 'includes/class-element-factory.php';
		require_once MINDCRAFTS_AI_DIR . 'includes/schemas/class-control-mapper.php';
		require_once MINDCRAFTS_AI_DIR . 'includes/schemas/class-schema-generator.php';
		require_once MINDCRAFTS_AI_DIR . 'includes/validators/class-element-validator.php';
		require_once MINDCRAFTS_AI_DIR . 'includes/validators/class-settings-validator.php';
		require_once MINDCRAFTS_AI_DIR . 'includes/abilities/class-query-abilities.php';
		require_once MINDCRAFTS_AI_DIR . 'includes/abilities/class-page-abilities.php';
		require_once MINDCRAFTS_AI_DIR . 'includes/abilities/class-layout-abilities.php';
		require_once MINDCRAFTS_AI_DIR . 'includes/abilities/class-widget-abilities.php';
		require_once MINDCRAFTS_AI_DIR . 'includes/abilities/class-template-abilities.php';
		require_once MINDCRAFTS_AI_DIR . 'includes/abilities/class-global-abilities.php';
		require_once MINDCRAFTS_AI_DIR . 'includes/abilities/class-composite-abilities.php';
		require_once MINDCRAFTS_AI_DIR . 'includes/class-openverse-client.php';
		require_once MINDCRAFTS_AI_DIR . 'includes/abilities/class-stock-image-abilities.php';
		require_once MINDCRAFTS_AI_DIR . 'includes/abilities/class-analyzer-abilities.php';
		require_once MINDCRAFTS_AI_DIR . 'includes/abilities/class-clone-abilities.php';
		require_once MINDCRAFTS_AI_DIR . 'includes/abilities/class-bulk-abilities.php';
		require_once MINDCRAFTS_AI_DIR . 'includes/abilities/class-prompt-abilities.php';

		// Phase 4: Premium Features
		require_once MINDCRAFTS_AI_DIR . 'includes/class-license-manager.php';
		require_once MINDCRAFTS_AI_DIR . 'includes/abilities/class-woocommerce-abilities.php';
		require_once MINDCRAFTS_AI_DIR . 'includes/abilities/class-seo-abilities.php';
		require_once MINDCRAFTS_AI_DIR . 'includes/abilities/class-addon-abilities.php';
		require_once MINDCRAFTS_AI_DIR . 'includes/abilities/class-responsive-abilities.php';
		require_once MINDCRAFTS_AI_DIR . 'includes/abilities/class-marketplace-abilities.php';
		require_once MINDCRAFTS_AI_DIR . 'includes/abilities/class-theme-builder-abilities.php';
		require_once MINDCRAFTS_AI_DIR . 'includes/abilities/class-popup-abilities.php';
		require_once MINDCRAFTS_AI_DIR . 'includes/abilities/class-dynamic-tags-abilities.php';

		require_once MINDCRAFTS_AI_DIR . 'includes/abilities/class-ability-registrar.php';
		require_once MINDCRAFTS_AI_DIR . 'includes/class-plugin.php';

		// Boot the plugin.
		MindCrafts_AI_Plugin::instance();
	} catch ( \Throwable $e ) {
		add_action( 'admin_notices', function() use ( $e ) {
			echo '<div class="notice notice-error is-dismissible"><p>';
			echo '<strong>MindCrafts AI Error:</strong> ' . esc_html( $e->getMessage() );
			echo ' <br><small>in ' . esc_html( $e->getFile() ) . ':' . (int) $e->getLine() . '</small>';
			echo '</p></div>';
		} );
	}
}
add_action( 'plugins_loaded', 'mindcrafts_ai_init', 20 );

/**
 * Pre-warms the widget schema cache on plugin activation.
 *
 * @since 2.0.0
 */
register_activation_hook( __FILE__, function() {
	try {
		if ( did_action( 'elementor/loaded' ) && class_exists( 'MindCrafts_AI_Schema_Generator' ) ) {
			$common_widgets = array( 'heading', 'button', 'image', 'text-editor', 'video', 'icon', 'divider', 'spacer' );
			$generator      = new MindCrafts_AI_Schema_Generator();
			foreach ( $common_widgets as $widget ) {
				$generator->generate( $widget );
			}
		}
	} catch ( \Throwable $e ) {
		// Suppress any non-critical cache pre-warming errors during plugin activation.
	}
} );
