<?php
/**
 * سمارٹ میڈیا ریزولور — ورڈپریس گیلری + اوپن ورس آٹو ڈاؤنلوڈ۔
 *
 * پہلے ورڈپریس میڈیا لائبریری میں تصویر تلاش کرتا ہے۔
 * اگر نہ ملے تو Openverse سے ڈاؤنلوڈ کر کے میڈیا لائبریری میں محفوظ کرتا ہے۔
 *
 * @package MindCrafts_AI
 * @since   2.3.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Smart Media Resolver service class.
 *
 * @since 2.3.0
 */
class MindCrafts_AI_Media_Resolver {

	/**
	 * Openverse client instance.
	 *
	 * @var MindCrafts_AI_Openverse_Client
	 */
	private $openverse;

	/**
	 * In-memory cache to avoid duplicate downloads in the same request.
	 *
	 * @var array<string, array>
	 */
	private $runtime_cache = array();

	/**
	 * Constructor.
	 *
	 * @since 2.3.0
	 */
	public function __construct() {
		if ( class_exists( 'MindCrafts_AI_Openverse_Client' ) ) {
			$this->openverse = new MindCrafts_AI_Openverse_Client();
		}
	}

	/**
	 * تصویر کو حل کرتا ہے — پہلے لوکل گیلری، پھر Openverse فال بیک۔
	 *
	 * @since 2.3.0
	 *
	 * @param string|array $image_spec کی ورڈ یا ['url'=>'...','id'=>0] آبجیکٹ۔
	 * @param string       $context    سیاق و سباق (مثلاً 'hero', 'product', 'team')۔
	 * @return array{ id: int, url: string, source: string } یا خالی آبجیکٹ۔
	 */
	public function resolve_image( $image_spec, string $context = '' ): array {
		$keyword = '';

		// اگر پہلے سے مکمل attachment آبجیکٹ ہے تو فوری واپس کریں۔
		if ( is_array( $image_spec ) ) {
			if ( ! empty( $image_spec['id'] ) && absint( $image_spec['id'] ) > 0 ) {
				$url = wp_get_attachment_url( absint( $image_spec['id'] ) );
				if ( $url ) {
					return array(
						'id'     => absint( $image_spec['id'] ),
						'url'    => $url,
						'source' => 'existing_attachment',
					);
				}
			}
			// URL ہے لیکن id نہیں — URL خود کافی ہے۔
			if ( ! empty( $image_spec['url'] ) && filter_var( $image_spec['url'], FILTER_VALIDATE_URL ) ) {
				$host = wp_parse_url( $image_spec['url'], PHP_URL_HOST );
				// اگر سائٹ کا اپنا URL ہے تو attachment ID ڈھونڈیں۔
				if ( false !== strpos( $host ?? '', wp_parse_url( home_url(), PHP_URL_HOST ) ?? '' ) ) {
					$attachment_id = attachment_url_to_postid( $image_spec['url'] );
					if ( $attachment_id ) {
						return array(
							'id'     => $attachment_id,
							'url'    => $image_spec['url'],
							'source' => 'local_url',
						);
					}
				}
				// بیرونی URL ہے تو سائیڈ لوڈ کریں۔
				return $this->sideload_url( $image_spec['url'], $context );
			}
			// keyword میں سے تلاش کریں۔
			$keyword = sanitize_text_field( $image_spec['keyword'] ?? $image_spec['query'] ?? $context );
		} elseif ( is_string( $image_spec ) && ! empty( $image_spec ) ) {
			// اگر مکمل URL ہے تو سائیڈ لوڈ کریں۔
			if ( filter_var( $image_spec, FILTER_VALIDATE_URL ) ) {
				return $this->sideload_url( $image_spec, $context );
			}
			$keyword = sanitize_text_field( $image_spec );
		}

		if ( empty( $keyword ) && ! empty( $context ) ) {
			$keyword = $context;
		}

		if ( empty( $keyword ) ) {
			return array( 'id' => 0, 'url' => '', 'source' => 'none' );
		}

		// ان میموری کیش چیک کریں۔
		$cache_key = md5( $keyword );
		if ( isset( $this->runtime_cache[ $cache_key ] ) ) {
			return $this->runtime_cache[ $cache_key ];
		}

		// قدم 1: ورڈپریس میڈیا لائبریری میں تلاش کریں۔
		$local = $this->search_local_media( $keyword );
		if ( ! empty( $local['id'] ) ) {
			$this->runtime_cache[ $cache_key ] = $local;
			return $local;
		}

		// قدم 2: Openverse سے ڈاؤنلوڈ کریں۔
		$remote = $this->fetch_from_openverse( $keyword, $context );
		$this->runtime_cache[ $cache_key ] = $remote;

		return $remote;
	}

	// -------------------------------------------------------------------------
	// قدم 1: لوکل میڈیا لائبریری
	// -------------------------------------------------------------------------

	/**
	 * ورڈپریس میڈیا لائبریری میں کی ورڈ کی بنیاد پر تصویر تلاش کرتا ہے۔
	 *
	 * @since 2.3.0
	 *
	 * @param string $keyword تلاش کا لفظ۔
	 * @return array{ id: int, url: string, source: string }
	 */
	private function search_local_media( string $keyword ): array {
		// ٹائٹل / کیپشن / آلٹ ٹیکسٹ میں تلاش کریں۔
		$query = new WP_Query(
			array(
				'post_type'      => 'attachment',
				'post_status'    => 'inherit',
				'posts_per_page' => 1,
				'post_mime_type' => 'image',
				's'              => $keyword,
				'fields'         => 'ids',
			)
		);

		if ( ! empty( $query->posts ) ) {
			$id  = absint( $query->posts[0] );
			$url = wp_get_attachment_url( $id );
			if ( $url ) {
				return array(
					'id'     => $id,
					'url'    => $url,
					'source' => 'local_media_library',
				);
			}
		}

		// آلٹ ٹیکسٹ میں ڈائریکٹ میٹا تلاش کریں۔
		global $wpdb;
		$alt_id = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT post_id FROM {$wpdb->postmeta}
				 WHERE meta_key = '_wp_attachment_image_alt'
				 AND meta_value LIKE %s
				 LIMIT 1",
				'%' . $wpdb->esc_like( $keyword ) . '%'
			)
		);

		if ( $alt_id ) {
			$url = wp_get_attachment_url( absint( $alt_id ) );
			if ( $url ) {
				return array(
					'id'     => absint( $alt_id ),
					'url'    => $url,
					'source' => 'local_media_alt',
				);
			}
		}

		return array( 'id' => 0, 'url' => '', 'source' => 'not_found' );
	}

	// -------------------------------------------------------------------------
	// قدم 2: Openverse فال بیک
	// -------------------------------------------------------------------------

	/**
	 * Openverse سے تصویر ڈھونڈ کر سائیڈ لوڈ کرتا ہے۔
	 *
	 * @since 2.3.0
	 *
	 * @param string $keyword تلاش کا لفظ۔
	 * @param string $context  سیاق و سباق۔
	 * @return array{ id: int, url: string, source: string }
	 */
	private function fetch_from_openverse( string $keyword, string $context = '' ): array {
		if ( ! $this->openverse ) {
			return array( 'id' => 0, 'url' => '', 'source' => 'openverse_unavailable' );
		}

		$search_term = $keyword;
		if ( ! empty( $context ) && $context !== $keyword ) {
			$search_term = $keyword . ' ' . $context;
		}

		$response = $this->openverse->search_images(
			array(
				'q'            => $search_term,
				'page_size'    => 5,
				'aspect_ratio' => 'wide',
				'size'         => 'large',
				'license'      => 'cc0',
			)
		);

		if ( is_wp_error( $response ) || empty( $response['results'] ) ) {
			// cc0 نہ ملے تو تمام لائسنسز آزمائیں۔
			$response = $this->openverse->search_images(
				array(
					'q'            => $keyword,
					'page_size'    => 3,
					'aspect_ratio' => 'wide',
				)
			);
		}

		if ( is_wp_error( $response ) || empty( $response['results'] ) ) {
			return array( 'id' => 0, 'url' => '', 'source' => 'openverse_no_results' );
		}

		// بہترین نتیجہ منتخب کریں۔
		$image     = $response['results'][0];
		$image_url = $image['url'] ?? '';

		if ( empty( $image_url ) ) {
			return array( 'id' => 0, 'url' => '', 'source' => 'openverse_empty_url' );
		}

		return $this->sideload_url(
			$image_url,
			$context,
			array(
				'title'       => $image['title'] ?? $keyword,
				'alt_text'    => $image['title'] ?? $keyword,
				'attribution' => $image['attribution'] ?? '',
			)
		);
	}

	// -------------------------------------------------------------------------
	// سائیڈ لوڈ ہیلپر
	// -------------------------------------------------------------------------

	/**
	 * بیرونی URL کو ورڈپریس میڈیا لائبریری میں ڈاؤنلوڈ کرتا ہے۔
	 *
	 * @since 2.3.0
	 *
	 * @param string $url   بیرونی تصویر کا پتہ۔
	 * @param string $context سیاق و سباق۔
	 * @param array  $meta  اضافی میٹا (title, alt_text, attribution)۔
	 * @return array{ id: int, url: string, source: string }
	 */
	public function sideload_url( string $url, string $context = '', array $meta = array() ): array {
		if ( ! function_exists( 'media_handle_sideload' ) ) {
			require_once ABSPATH . 'wp-admin/includes/file.php';
			require_once ABSPATH . 'wp-admin/includes/media.php';
			require_once ABSPATH . 'wp-admin/includes/image.php';
		}

		if ( ! wp_http_validate_url( $url ) ) {
			return array( 'id' => 0, 'url' => $url, 'source' => 'invalid_url' );
		}

		$tmp = download_url( esc_url_raw( $url ), 30 );

		if ( is_wp_error( $tmp ) ) {
			return array( 'id' => 0, 'url' => '', 'source' => 'download_failed' );
		}

		$url_path = wp_parse_url( $url, PHP_URL_PATH );
		$filename = $url_path ? basename( $url_path ) : 'image.jpg';
		if ( ! preg_match( '/\.\w{3,4}$/', $filename ) ) {
			$filename .= '.jpg';
		}

		$file_array = array(
			'name'     => sanitize_file_name( $filename ),
			'tmp_name' => $tmp,
		);

		$post_data = array();
		if ( ! empty( $meta['title'] ) ) {
			$post_data['post_title'] = sanitize_text_field( $meta['title'] );
		}
		if ( ! empty( $meta['attribution'] ) ) {
			$post_data['post_excerpt'] = sanitize_text_field( $meta['attribution'] );
		}

		$attachment_id = media_handle_sideload( $file_array, 0, null, $post_data );

		// عارضی فائل صاف کریں۔
		if ( file_exists( $tmp ) ) {
			wp_delete_file( $tmp );
		}

		if ( is_wp_error( $attachment_id ) ) {
			return array( 'id' => 0, 'url' => '', 'source' => 'sideload_failed' );
		}

		// آلٹ ٹیکسٹ لگائیں۔
		$alt = sanitize_text_field( $meta['alt_text'] ?? $meta['title'] ?? $context );
		if ( ! empty( $alt ) ) {
			update_post_meta( $attachment_id, '_wp_attachment_image_alt', $alt );
		}

		$local_url = wp_get_attachment_url( $attachment_id );

		return array(
			'id'     => $attachment_id,
			'url'    => $local_url ? $local_url : '',
			'source' => 'openverse_sideloaded',
		);
	}
}
