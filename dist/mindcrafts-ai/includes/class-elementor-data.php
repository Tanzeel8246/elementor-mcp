<?php
/**
 * Elementor data access layer.
 *
 * Wraps Elementor internals to provide a clean API for reading and writing
 * Elementor page data, widget registrations, and element trees.
 *
 * @package MindCrafts_AI
 * @since   1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Data access layer wrapping Elementor's internal APIs.
 *
 * @since 1.0.0
 */
class MindCrafts_AI_Data {

	/**
	 * Gets the Elementor document for a post.
	 *
	 * @since 1.0.0
	 *
	 * @param int $post_id The post ID.
	 * @return \Elementor\Core\Base\Document|\WP_Error The document instance or WP_Error.
	 */
	public function get_document( int $post_id ) {
		$document = \Elementor\Plugin::$instance->documents->get( $post_id );

		if ( ! $document ) {
			return new \WP_Error(
				'document_not_found',
				sprintf(
					/* translators: %d: post ID */
					__( 'Elementor document not found for post ID %d.', 'mindcrafts-ai' ),
					$post_id
				)
			);
		}

		return $document;
	}

	/**
	 * Gets the element tree for an Elementor page.
	 *
	 * Tries the Elementor document API first, falls back to reading raw
	 * post meta if the document returns empty data (common in CLI contexts).
	 *
	 * @since 1.0.0
	 *
	 * @param int $post_id The post ID.
	 * @return array|\WP_Error The elements data array or WP_Error.
	 */
	public function get_page_data( int $post_id ) {
		$document = $this->get_document( $post_id );

		if ( is_wp_error( $document ) ) {
			return $document;
		}

		$data = $document->get_elements_data();

		if ( is_array( $data ) && ! empty( $data ) ) {
			return $data;
		}

		// Fallback: read from raw post meta (handles CLI/proxy contexts).
		$raw = get_post_meta( $post_id, '_elementor_data', true );

		if ( ! empty( $raw ) && is_string( $raw ) ) {
			$decoded = json_decode( $raw, true );
			if ( is_array( $decoded ) ) {
				return $decoded;
			}
		}

		return array();
	}

	/**
	 * Gets the page-level settings for an Elementor document.
	 *
	 * @since 1.0.0
	 *
	 * @param int $post_id The post ID.
	 * @return array|\WP_Error The page settings array or WP_Error.
	 */
	public function get_page_settings( int $post_id ) {
		$document = $this->get_document( $post_id );

		if ( is_wp_error( $document ) ) {
			return $document;
		}

		return $document->get_settings();
	}

	/**
	 * Gets the document type for a post.
	 *
	 * @since 1.0.0
	 *
	 * @param int $post_id The post ID.
	 * @return string|\WP_Error The document type string or WP_Error.
	 */
	public function get_document_type( int $post_id ) {
		$document = $this->get_document( $post_id );

		if ( is_wp_error( $document ) ) {
			return $document;
		}

		return get_post_meta( $post_id, '_elementor_template_type', true );
	}

	/**
	 * Gets all registered Elementor widget types.
	 *
	 * @since 1.0.0
	 *
	 * @return \Elementor\Widget_Base[] Array of widget instances keyed by widget name.
	 */
	public function get_registered_widgets(): array {
		return \Elementor\Plugin::$instance->widgets_manager->get_widget_types();
	}

	/**
	 * Gets the controls for a specific widget type.
	 *
	 * @since 1.0.0
	 *
	 * @param string $widget_type The widget type name.
	 * @return array|\WP_Error The controls array or WP_Error if widget not found.
	 */
	public function get_widget_controls( string $widget_type ) {
		$widget = \Elementor\Plugin::$instance->widgets_manager->get_widget_types( $widget_type );

		if ( ! $widget ) {
			return new \WP_Error(
				'widget_not_found',
				sprintf(
					/* translators: %s: widget type name */
					__( 'Widget type "%s" not found.', 'mindcrafts-ai' ),
					$widget_type
				)
			);
		}

		return $widget->get_controls();
	}

	/**
	 * Recursively searches for an element by ID within an element tree.
	 *
	 * @since 1.0.0
	 *
	 * @param array  $data The element tree array.
	 * @param string $id   The element ID to find.
	 * @return array|null The element array if found, null otherwise.
	 */
	public function find_element_by_id( array $data, string $id ): ?array {
		foreach ( $data as $element ) {
			if ( isset( $element['id'] ) && $element['id'] === $id ) {
				return $element;
			}

			if ( ! empty( $element['elements'] ) && is_array( $element['elements'] ) ) {
				$found = $this->find_element_by_id( $element['elements'], $id );
				if ( null !== $found ) {
					return $found;
				}
			}
		}

		return null;
	}

	/**
	 * Saves page data using Elementor's native save mechanism.
	 *
	 * Tries document save() first (triggers CSS regeneration). If that fails
	 * (e.g. non-browser context like WP-CLI or REST API), falls back to direct
	 * meta update and manual CSS cache invalidation + regeneration.
	 *
	 * @since 1.0.0
	 *
	 * @param int   $post_id The post ID.
	 * @param array $data    The elements data array.
	 * @return bool|\WP_Error True on success, WP_Error on failure.
	 */
	public function save_page_data( int $post_id, array $data ) {
		$document = $this->get_document( $post_id );

		if ( is_wp_error( $document ) ) {
			return $document;
		}

		// T2-4: Save a WordPress revision before modifying data.
		// This allows changes made via MCP to be undone via wp-admin > Revisions.
		if ( wp_revisions_enabled( get_post( $post_id ) ) ) {
			wp_save_post_revision( $post_id );
		}

		// Attempt native Elementor save (handles CSS regen, cache busting).
		$result = $document->save( array( 'elements' => $data ) );

		if ( false === $result ) {
			// Fallback: direct meta write for non-browser contexts (CLI, REST proxy).
			$json = wp_json_encode( $data );

			if ( false === $json ) {
				return new \WP_Error(
					'json_encode_failed',
					__( 'Failed to encode element data as JSON.', 'mindcrafts-ai' )
				);
			}

			update_post_meta( $post_id, '_elementor_data', wp_slash( $json ) );

			// Ensure all required Elementor meta flags are set.
			update_post_meta( $post_id, '_elementor_edit_mode', 'builder' );

			$existing_template_type = get_post_meta( $post_id, '_elementor_template_type', true );
			if ( empty( $existing_template_type ) ) {
				update_post_meta( $post_id, '_elementor_template_type', 'wp-' . get_post_type( $post_id ) );
			}

			if ( defined( 'ELEMENTOR_VERSION' ) ) {
				update_post_meta( $post_id, '_elementor_version', ELEMENTOR_VERSION );
			}

			// --- T1-2 FIX: Proper CSS regeneration after REST/CLI save ---
			// Step 1: Invalidate the cached CSS meta so Elementor knows it needs regeneration.
			delete_post_meta( $post_id, '_elementor_css' );

			// Step 2: Delete the physical CSS file so it is regenerated on next load.
			$upload_dir = wp_get_upload_dir();
			$css_path   = $upload_dir['basedir'] . '/elementor/css/post-' . $post_id . '.css';
			if ( file_exists( $css_path ) ) {
				wp_delete_file( $css_path );
			}

			// Step 3: Use Elementor's files manager to clear all caches globally.
			// This is the key fix for "The preview could not be loaded" in the Elementor editor.
			if (
				isset( \Elementor\Plugin::$instance->files_manager ) &&
				is_callable( array( \Elementor\Plugin::$instance->files_manager, 'clear_cache' ) )
			) {
				\Elementor\Plugin::$instance->files_manager->clear_cache();
			}

			// Step 4: Directly regenerate the post CSS file for immediate availability.
			if ( class_exists( '\Elementor\Core\Files\CSS\Post' ) ) {
				try {
					$css_file = \Elementor\Core\Files\CSS\Post::create( $post_id );
					if ( $css_file && is_callable( array( $css_file, 'update' ) ) ) {
						$css_file->update();
					}
				} catch ( \Exception $e ) {
					// CSS regeneration failure is non-fatal; log and continue.
					error_log( 'MindCrafts AI: CSS regeneration failed for post ' . $post_id . ': ' . $e->getMessage() );
				}
			}
		}

		return true;
	}

	/**
	 * Saves page-level settings.
	 *
	 * Tries native Elementor save first, falls back to direct meta for
	 * non-browser contexts (WP-CLI, REST API proxy).
	 *
	 * @since 1.0.0
	 *
	 * @param int   $post_id  The post ID.
	 * @param array $settings The page settings array.
	 * @return bool|\WP_Error True on success, WP_Error on failure.
	 */
	public function save_page_settings( int $post_id, array $settings ) {
		$document = $this->get_document( $post_id );

		if ( is_wp_error( $document ) ) {
			return $document;
		}

		$result = $document->save( array( 'settings' => $settings ) );

		if ( false === $result ) {
			// Fallback: merge settings into existing page settings meta.
			$existing = get_post_meta( $post_id, '_elementor_page_settings', true );
			if ( ! is_array( $existing ) ) {
				$existing = array();
			}

			$merged = array_merge( $existing, $settings );
			update_post_meta( $post_id, '_elementor_page_settings', $merged );

			// Invalidate CSS cache.
			delete_post_meta( $post_id, '_elementor_css' );

			// Clear files manager cache.
			if (
				isset( \Elementor\Plugin::$instance->files_manager ) &&
				is_callable( array( \Elementor\Plugin::$instance->files_manager, 'clear_cache' ) )
			) {
				\Elementor\Plugin::$instance->files_manager->clear_cache();
			}
		}

		return true;
	}

	/**
	 * Inserts an element into the page data tree.
	 *
	 * @since 1.0.0
	 *
	 * @param array  $data      The element tree (passed by reference).
	 * @param string $parent_id The parent element ID. Empty string for top-level.
	 * @param array  $element   The element to insert.
	 * @param int    $position  The insertion position (-1 = append).
	 * @return bool True if inserted, false if parent not found.
	 */
	public function insert_element( array &$data, string $parent_id, array $element, int $position = -1 ): bool {
		// Top-level insertion.
		if ( empty( $parent_id ) ) {
			if ( $position < 0 || $position >= count( $data ) ) {
				$data[] = $element;
			} else {
				array_splice( $data, $position, 0, array( $element ) );
			}
			return true;
		}

		// Find parent and insert.
		foreach ( $data as &$item ) {
			if ( isset( $item['id'] ) && $item['id'] === $parent_id ) {
				if ( ! isset( $item['elements'] ) ) {
					$item['elements'] = array();
				}

				if ( $position < 0 || $position >= count( $item['elements'] ) ) {
					$item['elements'][] = $element;
				} else {
					array_splice( $item['elements'], $position, 0, array( $element ) );
				}

				return true;
			}

			if ( ! empty( $item['elements'] ) && is_array( $item['elements'] ) ) {
				if ( $this->insert_element( $item['elements'], $parent_id, $element, $position ) ) {
					return true;
				}
			}
		}

		return false;
	}

	/**
	 * Removes an element from the page data tree.
	 *
	 * @since 1.0.0
	 *
	 * @param array  $data       The element tree (passed by reference).
	 * @param string $element_id The element ID to remove.
	 * @return bool True if removed, false if not found.
	 */
	public function remove_element( array &$data, string $element_id ): bool {
		foreach ( $data as $index => &$item ) {
			if ( isset( $item['id'] ) && $item['id'] === $element_id ) {
				array_splice( $data, $index, 1 );
				return true;
			}

			if ( ! empty( $item['elements'] ) && is_array( $item['elements'] ) ) {
				if ( $this->remove_element( $item['elements'], $element_id ) ) {
					return true;
				}
			}
		}

		return false;
	}

	/**
	 * Recursively reassigns fresh IDs to all elements in a tree.
	 *
	 * @since 1.0.0
	 *
	 * @param array $elements The element tree.
	 * @return array The tree with new IDs.
	 */
	public function reassign_ids( array $elements ): array {
		foreach ( $elements as &$element ) {
			$element['id'] = MindCrafts_AI_Id_Generator::generate();

			if ( ! empty( $element['elements'] ) && is_array( $element['elements'] ) ) {
				$element['elements'] = $this->reassign_ids( $element['elements'] );
			}
		}

		return $elements;
	}

	/**
	 * Reassigns a fresh ID to a single element and all its children.
	 *
	 * @since 1.0.0
	 *
	 * @param array $element The element array.
	 * @return array The element with new IDs.
	 */
	public function reassign_element_ids( array $element ): array {
		$element['id'] = MindCrafts_AI_Id_Generator::generate();

		if ( ! empty( $element['elements'] ) && is_array( $element['elements'] ) ) {
			$element['elements'] = $this->reassign_ids( $element['elements'] );
		}

		return $element;
	}

	/**
	 * Recursively counts all elements in a tree.
	 *
	 * @since 1.0.0
	 *
	 * @param array $elements The element tree.
	 * @return int Total count.
	 */
	public function count_elements( array $elements ): int {
		$count = count( $elements );

		foreach ( $elements as $element ) {
			if ( ! empty( $element['elements'] ) && is_array( $element['elements'] ) ) {
				$count += $this->count_elements( $element['elements'] );
			}
		}

		return $count;
	}


	/**
	 * Updates settings for a specific element in the tree.
	 *
	 * Modifies `$data` by reference. Returns true if element was found
	 * and updated, false if the element ID was not found.
	 *
	 * @since 1.0.0
	 *
	 * @param array  $data       The element tree (passed by reference).
	 * @param string $element_id The element ID to update.
	 * @param array  $settings   The settings to merge.
	 * @return bool True if updated, false if not found.
	 */
	public function update_element_settings( array &$data, string $element_id, array $settings ): bool {
		foreach ( $data as &$item ) {
			if ( isset( $item['id'] ) && $item['id'] === $element_id ) {
				if ( ! isset( $item['settings'] ) ) {
					$item['settings'] = array();
				}
				$item['settings'] = array_merge( $item['settings'], $settings );
				return true;
			}

			if ( ! empty( $item['elements'] ) && is_array( $item['elements'] ) ) {
				if ( $this->update_element_settings( $item['elements'], $element_id, $settings ) ) {
					return true;
				}
			}
		}

		return false;
	}

	/**
	 * Saves a snapshot of the current Elementor page content.
	 *
	 * T3-4: Page history snapshots before destructive changes.
	 *
	 * @since 2.1.0
	 *
	 * @param int    $post_id     The post ID.
	 * @param string $description Description of what triggered the snapshot.
	 * @return bool True on success, false on empty content.
	 */
	public function save_snapshot( int $post_id, string $description ): bool {
		$current_data = get_post_meta( $post_id, '_elementor_data', true );
		if ( empty( $current_data ) ) {
			return false;
		}

		$snapshots = get_post_meta( $post_id, '_mindcrafts_snapshots', true );
		if ( ! is_array( $snapshots ) ) {
			$snapshots = array();
		}

		array_unshift(
			$snapshots,
			array(
				'timestamp'   => time(),
				'description' => $description,
				'data'        => $current_data,
			)
		);

		// Cap history at 10 snapshots to save DB space.
		if ( count( $snapshots ) > 10 ) {
			$snapshots = array_slice( $snapshots, 0, 10 );
		}

		return (bool) update_post_meta( $post_id, '_mindcrafts_snapshots', $snapshots );
	}

	/**
	 * Lists all snapshots for a given post.
	 *
	 * T3-4: Page history snapshots.
	 *
	 * @since 2.1.0
	 *
	 * @param int $post_id The post ID.
	 * @return array List of snapshots with formatting.
	 */
	public function list_snapshots( int $post_id ): array {
		$snapshots = get_post_meta( $post_id, '_mindcrafts_snapshots', true );
		if ( ! is_array( $snapshots ) ) {
			return array();
		}

		$result = array();
		foreach ( $snapshots as $index => $snapshot ) {
			$result[] = array(
				'index'       => $index,
				'timestamp'   => $snapshot['timestamp'],
				'date'        => date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $snapshot['timestamp'] ),
				'description' => $snapshot['description'],
			);
		}

		return $result;
	}

	/**
	 * Restores a specific snapshot by index.
	 *
	 * T3-4: Page history snapshots.
	 *
	 * @since 2.1.0
	 *
	 * @param int $post_id The post ID.
	 * @param int $index   The snapshot index to restore.
	 * @return bool|\WP_Error True on success, WP_Error on failure.
	 */
	public function restore_snapshot( int $post_id, int $index ) {
		$snapshots = get_post_meta( $post_id, '_mindcrafts_snapshots', true );
		if ( ! is_array( $snapshots ) || ! isset( $snapshots[ $index ] ) ) {
			return new \WP_Error( 'snapshot_not_found', __( 'Specified snapshot not found.', 'mindcrafts-ai' ) );
		}

		$snapshot = $snapshots[ $index ];
		
		// The transient/raw meta might be encoded or stored as string, let's decode it safely.
		$raw_data = $snapshot['data'];
		$data     = is_string( $raw_data ) ? json_decode( $raw_data, true ) : $raw_data;

		if ( ! is_array( $data ) ) {
			return new \WP_Error( 'invalid_snapshot_data', __( 'Snapshot contains invalid Elementor data.', 'mindcrafts-ai' ) );
		}

		// Save a backup of current state as a snapshot before restoring.
		$this->save_snapshot( $post_id, sprintf( __( 'Auto backup before restoring snapshot #%d', 'mindcrafts-ai' ), $index ) );

		// Perform restore.
		return $this->save_page_data( $post_id, $data );
	}
}

// Backward-compatible alias for integrations created before the 2.0 rebrand.
if ( ! class_exists( 'Elementor_MCP_Data', false ) ) {
	class_alias( 'MindCrafts_AI_Data', 'Elementor_MCP_Data' );
}
