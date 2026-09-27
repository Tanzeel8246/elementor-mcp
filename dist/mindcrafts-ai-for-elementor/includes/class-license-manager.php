<?php
/**
 * License Manager for MindCrafts AI Premium.
 *
 * Handles remote license validation, local transient caching,
 * activation, and deactivation for premium features.
 *
 * @package MindCrafts_AI
 * @since   2.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Manages MindCrafts AI license operations and premium status checks.
 *
 * @since 2.0.0
 */
class MindCrafts_AI_License_Manager {

	const API_URL    = 'https://api.mindcrafts.ai/v1/license/validate';
	const OPTION_KEY = 'mindcrafts_ai_license_key';
	const STATUS_KEY = 'mindcrafts_ai_license_status';
	const CACHE_KEY  = 'mindcrafts_ai_license_cache';
	const CACHE_TTL  = 43200; // 12 hours in seconds (12 * HOUR_IN_SECONDS).

	/**
	 * Singleton instance.
	 *
	 * @var MindCrafts_AI_License_Manager|null
	 */
	private static $instance = null;

	/**
	 * Returns the singleton instance.
	 *
	 * @since 2.0.0
	 *
	 * @return MindCrafts_AI_License_Manager
	 */
	public static function instance(): self {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Private constructor to prevent direct instantiation.
	 *
	 * @since 2.0.0
	 */
	private function __construct() {}

	/**
	 * Prevents cloning.
	 *
	 * @since 2.0.0
	 */
	private function __clone() {}

	/**
	 * Checks if the user has an active premium license.
	 *
	 * Checks transient cache first. If not cached, contacts the remote validation API.
	 * Falls back to cached local status if remote API is unreachable.
	 *
	 * @since 2.0.0
	 *
	 * @return bool
	 */
	public function is_premium_active(): bool {
		// 1. Cache check.
		$cached = get_transient( self::CACHE_KEY );
		if ( false !== $cached ) {
			return (bool) $cached;
		}

		// 2. Stored key check.
		$license = get_option( self::OPTION_KEY, '' );
		if ( empty( $license ) ) {
			return false;
		}

		// 3. Remote validation.
		$response = wp_remote_post(
			self::API_URL,
			array(
				'timeout' => 10,
				'body'    => array(
					'license_key' => $license,
					'site_url'    => home_url(),
					'plugin'      => 'mindcrafts-ai-elementor',
					'version'     => defined( 'MINDCRAFTS_AI_VERSION' ) ? MINDCRAFTS_AI_VERSION : '2.0.0',
				),
			)
		);

		if ( is_wp_error( $response ) || 200 !== wp_remote_retrieve_response_code( $response ) ) {
			// Fallback: cached status if API is unreachable.
			$cached_status = get_option( self::STATUS_KEY, 'invalid' );
			return 'valid' === $cached_status;
		}

		$body  = json_decode( wp_remote_retrieve_body( $response ), true );
		$valid = isset( $body['valid'] ) && true === $body['valid'];

		// Update cache and option.
		set_transient( self::CACHE_KEY, $valid ? 1 : 0, self::CACHE_TTL );
		update_option( self::STATUS_KEY, $valid ? 'valid' : 'invalid' );

		return $valid;
	}

	/**
	 * Activates a license key with remote verification.
	 *
	 * @since 2.0.0
	 *
	 * @param string $key The license key to activate.
	 * @return array{success: bool, message: string}
	 */
	public function activate_license( string $key ): array {
		$sanitized_key = sanitize_text_field( trim( $key ) );

		if ( empty( $sanitized_key ) ) {
			return array(
				'success' => false,
				'message' => __( 'License key cannot be empty.', 'mindcrafts-ai' ),
			);
		}

		update_option( self::OPTION_KEY, $sanitized_key );
		delete_transient( self::CACHE_KEY ); // Force re-validation.

		$active = $this->is_premium_active();

		return array(
			'success' => $active,
			'message' => $active
				? __( 'License activated successfully.', 'mindcrafts-ai' )
				: __( 'Invalid license key.', 'mindcrafts-ai' ),
		);
	}

	/**
	 * Deactivates the current license and notifies the remote server.
	 *
	 * @since 2.0.0
	 *
	 * @return array{success: bool, message: string}
	 */
	public function deactivate_license(): array {
		$license = get_option( self::OPTION_KEY, '' );

		delete_option( self::OPTION_KEY );
		delete_option( self::STATUS_KEY );
		delete_transient( self::CACHE_KEY );

		// Remote deactivation notification.
		if ( ! empty( $license ) ) {
			wp_remote_post(
				'https://api.mindcrafts.ai/v1/license/deactivate',
				array(
					'timeout' => 5,
					'body'    => array(
						'license_key' => $license,
						'site_url'    => home_url(),
					),
				)
			);
		}

		return array(
			'success' => true,
			'message' => __( 'License deactivated successfully.', 'mindcrafts-ai' ),
		);
	}

	/**
	 * Gets the stored license key.
	 *
	 * @since 2.0.0
	 *
	 * @return string
	 */
	public function get_license_key(): string {
		return (string) get_option( self::OPTION_KEY, '' );
	}

	/**
	 * Gets the current license status ('valid', 'invalid', 'expired', or 'empty').
	 *
	 * @since 2.0.0
	 *
	 * @return string
	 */
	public function get_license_status(): string {
		$key = $this->get_license_key();
		if ( empty( $key ) ) {
			return 'empty';
		}
		return (string) get_option( self::STATUS_KEY, 'invalid' );
	}

	/**
	 * Permission callback for premium abilities.
	 *
	 * @since 2.0.0
	 *
	 * @param mixed $input Optional input.
	 * @return bool
	 */
	public function check_premium_permission( $input = null ): bool {
		if ( ! current_user_can( 'edit_posts' ) ) {
			return false;
		}

		return $this->is_premium_active();
	}
}
