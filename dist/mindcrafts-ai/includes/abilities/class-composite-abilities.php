<?php
/**
 * Composite/high-level MCP abilities for Elementor.
 *
 * Registers the build-page tool that creates a complete page from
 * a declarative structure in a single call.
 *
 * @package MindCrafts_AI
 * @since   1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Registers and implements the composite abilities.
 *
 * @since 1.0.0
 */
class MindCrafts_AI_Composite_Abilities {

	/**
	 * @var MindCrafts_AI_Data
	 */
	private $data;

	/**
	 * @var MindCrafts_AI_Element_Factory
	 */
	private $factory;

	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 *
	 * @param MindCrafts_AI_Data            $data    The data access layer.
	 * @param MindCrafts_AI_Element_Factory $factory The element factory.
	 */
	public function __construct( MindCrafts_AI_Data $data, MindCrafts_AI_Element_Factory $factory ) {
		$this->data    = $data;
		$this->factory = $factory;
	}

	/**
	 * Returns the ability names registered by this class.
	 *
	 * @since 1.0.0
	 *
	 * @return string[]
	 */
	public function get_ability_names(): array {
		return array(
			'mindcrafts-ai/build-page',
			'mindcrafts-ai/convert-html-to-elementor',
			'mindcrafts-ai/migrate-all-pages-to-native',
		);
	}

	/**
	 * Registers all composite abilities.
	 *
	 * @since 1.0.0
	 */
	public function register(): void {
		$this->register_build_page();
		$this->register_convert_html_to_elementor();
		$this->register_migrate_all_pages_to_native();
	}

	/**
	 * Permission check for page creation.
	 *
	 * @since 1.0.0
	 *
	 * @param array|null $input The input parameters.
	 * @return bool
	 */
	public function check_create_permission( $input = null ): bool {
		$post_type = sanitize_key( $input['post_type'] ?? 'page' );

		if ( 'post' === $post_type ) {
			return current_user_can( 'edit_posts' );
		}

		if ( 'page' === $post_type ) {
			return current_user_can( 'edit_pages' );
		}

		return false;
	}

	// -------------------------------------------------------------------------
	// build-page
	// -------------------------------------------------------------------------

	/**
	 * Registers the build-page ability.
	 *
	 * @since 1.0.0
	 */
	private function register_build_page(): void {
		wp_register_ability(
			'mindcrafts-ai/build-page',
			array(
				'label'               => __( 'Build Page', 'mindcrafts-ai' ),
				'description'         => __( 'Creates a complete Elementor page from a declarative structure in a single call. Pass a "structure" array of containers and widgets. IMPORTANT MANDATE FOR VISUAL DESIGNERS: You MUST construct native Elementor Containers and Widgets (heading, button, image, icon-box, etc.). NEVER dump monolithic HTML page code into a single text-editor or html widget — doing so renders the page uneditable for visual WordPress designers. Use "heading" for titles, "button" for links/CTAs, "image" for pictures, "icon-box" for feature cards, and "text-editor" ONLY for brief text paragraphs.', 'mindcrafts-ai' ),
				'category'            => 'mindcrafts-ai',
				'execute_callback'    => array( $this, 'execute_build_page' ),
				'permission_callback' => array( $this, 'check_create_permission' ),
				'input_schema'        => array(
					'type'       => 'object',
					'properties' => array(
						'title'         => array(
							'type'        => 'string',
							'description' => __( 'Page title.', 'mindcrafts-ai' ),
						),
						'status'        => array(
							'type'        => 'string',
							'enum'        => array( 'draft', 'publish' ),
							'description' => __( 'Post status. Default: draft.', 'mindcrafts-ai' ),
						),
						'post_type'     => array(
							'type'        => 'string',
							'enum'        => array( 'page', 'post' ),
							'description' => __( 'Post type. Default: page.', 'mindcrafts-ai' ),
						),
						'page_settings' => array(
							'type'        => 'object',
							'description' => __( 'Page-level Elementor settings (background, padding, etc.).', 'mindcrafts-ai' ),
						),
						'structure'     => array(
							'type'        => 'array',
							'description' => __( 'Declarative element tree. Each item has type (container|widget), settings, and optionally children (for containers) or widget_type (for widgets). Use "children" key (not "elements") for nested items. DO NOT dump raw HTML layouts inside text-editor; use native Elementor containers, heading widgets, button widgets, and image widgets for all visual elements.', 'mindcrafts-ai' ),
							'items'       => array(
								'type'       => 'object',
								'properties' => array(
									'type'        => array(
										'type' => 'string',
										'enum' => array( 'container', 'widget' ),
									),
									'widget_type' => array( 'type' => 'string' ),
									'settings'    => array( 'type' => 'object' ),
									'children'    => array( 'type' => 'array' ),
								),
								'required' => array( 'type' ),
							),
						),
					),
					'required'   => array( 'title', 'structure' ),
				),
				'output_schema'       => array(
					'type'       => 'object',
					'properties' => array(
						'post_id'          => array( 'type' => 'integer' ),
						'title'            => array( 'type' => 'string' ),
						'edit_url'         => array( 'type' => 'string' ),
						'preview_url'      => array( 'type' => 'string' ),
						'elements_created' => array( 'type' => 'integer' ),
					),
				),
				'meta'                => array(
					'annotations'  => array(
						'readonly'    => false,
						'destructive' => false,
						'idempotent'  => false,
					),
					'show_in_rest' => true,
				),
			)
		);
	}

	/**
	 * Registers the convert-html-to-elementor ability.
	 *
	 * @since 3.1.4
	 */
	private function register_convert_html_to_elementor(): void {
		wp_register_ability(
			'mindcrafts-ai/convert-html-to-elementor',
			array(
				'label'               => __( 'Convert HTML to Elementor', 'mindcrafts-ai' ),
				'description'         => __( 'Parses raw HTML/CSS layouts and automatically converts them into native Elementor visual Containers, Headings, Text-Editors, Buttons, and Images so WordPress designers can edit every element visually.', 'mindcrafts-ai' ),
				'category'            => 'mindcrafts-ai',
				'execute_callback'    => array( $this, 'execute_convert_html_to_elementor' ),
				'permission_callback' => array( $this, 'check_create_permission' ),
				'input_schema'        => array(
					'type'       => 'object',
					'properties' => array(
						'html'        => array(
							'type'        => 'string',
							'description' => __( 'The raw HTML string to convert into Elementor native widgets and containers.', 'mindcrafts-ai' ),
						),
						'title'       => array(
							'type'        => 'string',
							'description' => __( 'Optional page title. If provided with create_page: true, a new page will be created.', 'mindcrafts-ai' ),
						),
						'post_id'     => array(
							'type'        => 'integer',
							'description' => __( 'Optional existing post/page ID to populate with converted Elementor elements.', 'mindcrafts-ai' ),
						),
						'create_page' => array(
							'type'        => 'boolean',
							'description' => __( 'Whether to create a new WordPress page immediately. Default: false.', 'mindcrafts-ai' ),
						),
					),
					'required'   => array( 'html' ),
				),
				'output_schema'       => array(
					'type'       => 'object',
					'properties' => array(
						'success'          => array( 'type' => 'boolean' ),
						'sections_created' => array( 'type' => 'integer' ),
						'post_id'          => array( 'type' => 'integer' ),
						'edit_url'         => array( 'type' => 'string' ),
						'preview_url'      => array( 'type' => 'string' ),
						'structure'        => array( 'type' => 'array' ),
					),
				),
			)
		);
	}

	/**
	 * Executes convert-html-to-elementor ability.
	 *
	 * @since 3.1.4
	 *
	 * @param array $input The input parameters.
	 * @return array|\WP_Error
	 */
	public function execute_convert_html_to_elementor( $input ) {
		$html = $input['html'] ?? '';
		if ( empty( $html ) ) {
			return new \WP_Error( 'missing_html', __( 'HTML content is required.', 'mindcrafts-ai' ) );
		}

		if ( ! class_exists( 'MindCrafts_AI_Html_Decomposer' ) ) {
			return new \WP_Error( 'decomposer_missing', __( 'HTML Decomposer class is not loaded.', 'mindcrafts-ai' ) );
		}

		$elements = MindCrafts_AI_Html_Decomposer::decompose( $html, $this->factory );
		if ( empty( $elements ) ) {
			return new \WP_Error( 'conversion_failed', __( 'Could not parse HTML into Elementor native elements.', 'mindcrafts-ai' ) );
		}

		$create_page = ! empty( $input['create_page'] );
		$post_id     = absint( $input['post_id'] ?? 0 );
		$title       = sanitize_text_field( $input['title'] ?? 'Converted Elementor Page' );

		if ( $create_page && ! $post_id ) {
			$post_id = wp_insert_post(
				array(
					'post_title'  => $title,
					'post_status' => 'draft',
					'post_type'   => 'page',
				),
				true
			);
			if ( is_wp_error( $post_id ) ) {
				return $post_id;
			}
			update_post_meta( $post_id, '_elementor_edit_mode', 'builder' );
			update_post_meta( $post_id, '_elementor_template_type', 'wp-page' );
		}

		if ( $post_id ) {
			$saved = $this->data->save_page_data( $post_id, $elements );
			if ( is_wp_error( $saved ) ) {
				return $saved;
			}

			return array(
				'success'          => true,
				'post_id'          => $post_id,
				'sections_created' => count( $elements ),
				'edit_url'         => admin_url( 'post.php?post=' . $post_id . '&action=elementor' ),
				'preview_url'      => get_permalink( $post_id ),
			);
		}

		return array(
			'success'          => true,
			'sections_created' => count( $elements ),
			'structure'        => $elements,
		);
	}

	/**
	 * Registers the migrate-all-pages-to-native ability.
	 *
	 * @since 3.1.5
	 */
	private function register_migrate_all_pages_to_native(): void {
		wp_register_ability(
			'mindcrafts-ai/migrate-all-pages-to-native',
			array(
				'label'               => __( 'Migrate All Pages to Native Elementor', 'mindcrafts-ai' ),
				'description'         => __( 'Scans site pages (Home, Services, Projects, About, Pricing, Contact) for monolithic raw HTML dumps, automatically decomposes them into native Elementor visual Containers, Headings, Text-Editors, Buttons, and Images, and saves them as visual Elementor pages.', 'mindcrafts-ai' ),
				'category'            => 'mindcrafts-ai',
				'execute_callback'    => array( $this, 'execute_migrate_all_pages_to_native' ),
				'permission_callback' => array( $this, 'check_create_permission' ),
				'input_schema'        => array(
					'type'       => 'object',
					'properties' => array(
						'page_slugs' => array(
							'type'        => 'array',
							'items'       => array( 'type' => 'string' ),
							'description' => __( 'Optional list of page slugs to migrate. Defaults to: ["home", "services", "projects", "about", "pricing", "contact"].', 'mindcrafts-ai' ),
						),
						'page_ids'   => array(
							'type'        => 'array',
							'items'       => array( 'type' => 'integer' ),
							'description' => __( 'Optional specific page IDs to migrate.', 'mindcrafts-ai' ),
						),
					),
				),
				'output_schema'       => array(
					'type'       => 'object',
					'properties' => array(
						'success'        => array( 'type' => 'boolean' ),
						'pages_migrated' => array( 'type' => 'integer' ),
						'results'        => array( 'type' => 'array' ),
					),
				),
			)
		);
	}

	/**
	 * Executes migrate-all-pages-to-native ability.
	 *
	 * @since 3.1.5
	 *
	 * @param array $input Input parameters.
	 * @return array|\WP_Error
	 */
	public function execute_migrate_all_pages_to_native( $input = array() ) {
		if ( ! class_exists( 'MindCrafts_AI_Html_Decomposer' ) ) {
			return new \WP_Error( 'decomposer_missing', __( 'HTML Decomposer class is not available.', 'mindcrafts-ai' ) );
		}

		$slugs    = $input['page_slugs'] ?? array( 'home', 'services', 'projects', 'about', 'pricing', 'contact' );
		$page_ids = $input['page_ids'] ?? array();

		$target_pages = array();

		if ( ! empty( $page_ids ) ) {
			foreach ( $page_ids as $pid ) {
				$p = get_post( absint( $pid ) );
				if ( $p ) {
					$target_pages[ $p->ID ] = $p;
				}
			}
		} else {
			foreach ( $slugs as $slug ) {
				$found = get_page_by_path( $slug );
				if ( $found ) {
					$target_pages[ $found->ID ] = $found;
				}
			}

			// Also check front page
			$front_id = absint( get_option( 'page_on_front' ) );
			if ( $front_id && ! isset( $target_pages[ $front_id ] ) ) {
				$front_post = get_post( $front_id );
				if ( $front_post ) {
					$target_pages[ $front_id ] = $front_post;
				}
			}
		}

		if ( empty( $target_pages ) ) {
			return new \WP_Error( 'no_pages_found', __( 'No matching pages found to migrate.', 'mindcrafts-ai' ) );
		}

		$migrated_count = 0;
		$results        = array();

		foreach ( $target_pages as $post ) {
			$raw_html = '';

			// 1. Check post_content
			if ( MindCrafts_AI_Html_Decomposer::is_monolithic_html( $post->post_content ) ) {
				$raw_html = $post->post_content;
			} else {
				// 2. Check _elementor_data for monolithic text-editor
				$page_data = $this->data->get_page_data( $post->ID );
				if ( is_array( $page_data ) ) {
					$raw_html = $this->extract_raw_html_from_tree( $page_data );
				}
			}

			if ( empty( $raw_html ) ) {
				$results[] = array(
					'post_id' => $post->ID,
					'slug'    => $post->post_name,
					'title'   => $post->post_title,
					'status'  => 'skipped',
					'reason'  => 'No monolithic HTML found (already native or empty)',
				);
				continue;
			}

			// Decompose into native Elementor elements
			$elements = MindCrafts_AI_Html_Decomposer::decompose( $raw_html, $this->factory );

			if ( empty( $elements ) ) {
				$results[] = array(
					'post_id' => $post->ID,
					'slug'    => $post->post_name,
					'title'   => $post->post_title,
					'status'  => 'failed',
					'reason'  => 'Could not decompose HTML into Elementor elements',
				);
				continue;
			}

			// Save native Elementor structure
			$saved = $this->data->save_page_data( $post->ID, $elements );
			if ( is_wp_error( $saved ) ) {
				$results[] = array(
					'post_id' => $post->ID,
					'slug'    => $post->post_name,
					'title'   => $post->post_title,
					'status'  => 'error',
					'message' => $saved->get_error_message(),
				);
				continue;
			}

			// Clear raw HTML from post_content so Elementor renders cleanly
			wp_update_post(
				array(
					'ID'           => $post->ID,
					'post_content' => '',
				)
			);

			update_post_meta( $post->ID, '_elementor_edit_mode', 'builder' );
			update_post_meta( $post->ID, '_elementor_template_type', 'wp-page' );

			++$migrated_count;
			$results[] = array(
				'post_id'          => $post->ID,
				'slug'             => $post->post_name,
				'title'            => $post->post_title,
				'status'           => 'migrated_to_native',
				'sections_created' => count( $elements ),
				'edit_url'         => admin_url( 'post.php?post=' . $post->ID . '&action=elementor' ),
				'preview_url'      => get_permalink( $post->ID ),
			);
		}

		return array(
			'success'        => true,
			'pages_migrated' => $migrated_count,
			'results'        => $results,
		);
	}

	/**
	 * Helper to recursively search an Elementor element tree for a monolithic text-editor/html widget.
	 *
	 * @since 3.1.5
	 *
	 * @param array $tree Elementor element array.
	 * @return string Extracted raw HTML or empty string.
	 */
	private function extract_raw_html_from_tree( array $tree ): string {
		foreach ( $tree as $element ) {
			$el_type = $element['elType'] ?? '';
			if ( 'widget' === $el_type ) {
				$widget_type = $element['widgetType'] ?? '';
				if ( 'text-editor' === $widget_type || 'html' === $widget_type ) {
					$content = $element['settings']['editor'] ?? $element['settings']['html'] ?? '';
					if ( MindCrafts_AI_Html_Decomposer::is_monolithic_html( $content ) ) {
						return $content;
					}
				}
			}

			if ( ! empty( $element['elements'] ) && is_array( $element['elements'] ) ) {
				$found = $this->extract_raw_html_from_tree( $element['elements'] );
				if ( ! empty( $found ) ) {
					return $found;
				}
			}
		}

		return '';
	}

	/**
	 * Executes the build-page ability.
	 *
	 * T1-3 FIX: Structure is validated and built BEFORE the post is created,
	 * preventing orphan posts on failure.
	 * T1-6 FIX: Counter uses pass-by-reference local variable, not class property.
	 *
	 * @since 1.0.0
	 *
	 * @param array $input The input parameters.
	 * @return array|\WP_Error
	 */
	public function execute_build_page( $input ) {
		$title         = sanitize_text_field( $input['title'] ?? '' );
		$status        = sanitize_key( $input['status'] ?? 'draft' );
		$post_type     = sanitize_key( $input['post_type'] ?? 'page' );
		$page_settings = $input['page_settings'] ?? array();
		$structure     = $input['structure'] ?? array();

		if ( empty( $title ) ) {
			return new \WP_Error( 'missing_title', __( 'The title parameter is required.', 'mindcrafts-ai' ) );
		}

		if ( empty( $structure ) || ! is_array( $structure ) ) {
			return new \WP_Error( 'missing_structure', __( 'The structure parameter is required and must be an array.', 'mindcrafts-ai' ) );
		}

		if ( ! in_array( $status, array( 'draft', 'publish' ), true ) ) {
			return new \WP_Error( 'invalid_status', __( 'The status parameter must be draft or publish.', 'mindcrafts-ai' ) );
		}

		if ( ! in_array( $post_type, array( 'page', 'post' ), true ) ) {
			return new \WP_Error( 'invalid_post_type', __( 'The post_type parameter must be page or post.', 'mindcrafts-ai' ) );
		}

		$publish_cap = ( 'page' === $post_type ) ? 'publish_pages' : 'publish_posts';
		if ( 'publish' === $status && ! current_user_can( $publish_cap ) ) {
			return new \WP_Error( 'publish_not_allowed', __( 'You do not have permission to publish this content type.', 'mindcrafts-ai' ) );
		}

		// T1-3 FIX: Validate and build the element tree BEFORE creating the post.
		// Invalid structures now return errors without creating any orphan pages.
		$elements_count = 0;
		$elements       = $this->build_elements( $structure, false, $elements_count );

		if ( is_wp_error( $elements ) ) {
			return $elements; // Fail early — no post created yet.
		}

		// 1. Create the WordPress post only after structure is validated.
		$post_id = wp_insert_post(
			array(
				'post_title'  => $title,
				'post_status' => $status,
				'post_type'   => $post_type,
			),
			true
		);

		if ( is_wp_error( $post_id ) ) {
			return $post_id;
		}

		// Explicitly set protected meta keys.
		update_post_meta( $post_id, '_elementor_edit_mode', 'builder' );
		update_post_meta( $post_id, '_elementor_template_type', 'wp-' . $post_type );

		// 2. Save the element data (CSS regeneration handled inside save_page_data).
		$result = $this->data->save_page_data( $post_id, $elements );

		if ( is_wp_error( $result ) ) {
			wp_delete_post( $post_id, true );
			return $result;
		}

		// 3. Save page settings if provided.
		if ( ! empty( $page_settings ) ) {
			$settings_result = $this->data->save_page_settings( $post_id, $page_settings );
			if ( is_wp_error( $settings_result ) ) {
				wp_delete_post( $post_id, true );
				return $settings_result;
			}
		}

		$edit_url    = admin_url( 'post.php?post=' . $post_id . '&action=elementor' );
		$preview_url = get_permalink( $post_id );

		return array(
			'post_id'          => $post_id,
			'title'            => $title,
			'edit_url'         => $edit_url,
			'preview_url'      => $preview_url ? $preview_url : '',
			'elements_created' => $elements_count,
		);
	}

	// -------------------------------------------------------------------------
	// Helpers
	// -------------------------------------------------------------------------

	/**
	 * Recursively builds Elementor elements from the declarative structure.
	 *
	 * T1-6 FIX: Uses pass-by-reference counter instead of class property
	 * to avoid state pollution between calls.
	 *
	 * NOTE: Input uses "children" key; Elementor stores children under "elements".
	 * This is intentional — the input API uses "children" for clarity.
	 *
	 * @param array $items    The declarative structure items.
	 * @param bool  $is_inner Whether these are nested (inner) containers.
	 * @param int   $counter  Pass-by-reference counter for total elements created.
	 * @return array|\WP_Error The Elementor element tree.
	 */
	private function build_elements( array $items, bool $is_inner = false, int &$counter = 0 ) {
		$elements = array();

		foreach ( $items as $item ) {
			$type = $item['type'] ?? '';

			if ( 'container' === $type ) {
				$settings = $item['settings'] ?? array();
				$children = $item['children'] ?? array();

				// Recursively build children.
				$child_elements = $this->build_elements( $children, true, $counter );
				if ( is_wp_error( $child_elements ) ) {
					return $child_elements;
				}

				$container = $this->factory->create_container( $settings, $child_elements );

				if ( $is_inner ) {
					$container['isInner'] = true;
				}

				++$counter;
				$elements[] = $container;

			} elseif ( 'widget' === $type ) {
				$widget_type = sanitize_text_field( $item['widget_type'] ?? '' );
				$settings    = $item['settings'] ?? array();

				if ( empty( $widget_type ) ) {
					return new \WP_Error( 'missing_widget_type', __( 'Widget items require a widget_type value.', 'mindcrafts-ai' ) );
				}

				// گارڈ ریل اور آٹو ڈیکمپوزیشن: اگر AI نے ٹیکسٹ ایڈیٹر میں مکمل HTML ڈمپ کی ہے
				// تو اسے خودکار طور پر ایلیمینٹور کے حقیقی کنٹینرز اور وزٹس میں تبدیل کریں۔
				if ( ( 'text-editor' === $widget_type || 'html' === $widget_type ) && class_exists( 'MindCrafts_AI_Html_Decomposer' ) ) {
					$raw_html = $settings['editor'] ?? $settings['html'] ?? '';
					if ( MindCrafts_AI_Html_Decomposer::is_monolithic_html( $raw_html ) ) {
						$decomposed = MindCrafts_AI_Html_Decomposer::decompose( $raw_html, $this->factory );
						if ( ! empty( $decomposed ) ) {
							foreach ( $decomposed as $decomposed_elem ) {
								$elements[] = $decomposed_elem;
								++$counter;
							}
							continue;
						} else {
							return new \WP_Error(
								'monolithic_html_prohibited',
								__( 'REJECTED: Monolithic HTML page dump detected in a single text-editor/html widget. MindCrafts AI strictly enforces native Elementor components so that WordPress designers can visually edit every heading, button, container, and image in the Elementor visual editor. You must construct native Elementor containers and widgets (heading, button, image, icon-box, etc.).', 'mindcrafts-ai' )
							);
						}
					}
				}

				$widget_instance = \Elementor\Plugin::$instance->widgets_manager->get_widget_types( $widget_type );
				if ( ! $widget_instance ) {
					return new \WP_Error(
						'invalid_widget_type',
						sprintf(
							/* translators: %s: widget type */
							__( 'Widget type "%s" not found. Use list-widgets to see available types.', 'mindcrafts-ai' ),
							$widget_type
						)
					);
				}

				$widget = $this->factory->create_widget( $widget_type, $settings );
				++$counter;
				$elements[] = $widget;
			} else {
				return new \WP_Error( 'invalid_element_type', __( 'Each structure item must have type "container" or "widget".', 'mindcrafts-ai' ) );
			}
		}

		return $elements;
	}
}
