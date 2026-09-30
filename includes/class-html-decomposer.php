<?php
/**
 * HTML Decomposer for MindCrafts AI.
 *
 * Automatically parses and decomposes monolithic HTML blocks into native
 * Elementor visual components (Containers, Headings, Text Editors, Buttons, Images).
 * This ensures that even if an AI model writes raw HTML code, the resulting
 * page in Elementor consists of 100% editable visual widgets for WordPress designers.
 *
 * @package MindCrafts_AI
 * @since   3.1.4
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Decomposes raw HTML layouts into native Elementor JSON structures.
 *
 * @since 3.1.4
 */
class MindCrafts_AI_Html_Decomposer {

	/**
	 * Detects whether a string is a monolithic HTML layout rather than a simple text paragraph.
	 *
	 * @since 3.1.4
	 *
	 * @param string $content HTML or text content.
	 * @return bool True if monolithic HTML is detected.
	 */
	public static function is_monolithic_html( string $content ): bool {
		if ( empty( $content ) || strlen( $content ) < 150 ) {
			return false;
		}

		// Check for major structural section tags.
		if ( preg_match( '/<(header|section|footer|nav|main|aside)\b/i', $content ) ) {
			return true;
		}

		// Check for multi-section wrapper patterns.
		if ( preg_match( '/class=["\'][^"\']*(wrapper|layout|page-container|grid-container)[^"\']*["\']/i', $content ) ) {
			return true;
		}

		// Check for multiple headlines combined with links, buttons, and flex divs.
		$has_headings   = preg_match_all( '/<h[1-6]\b/i', $content ) >= 2;
		$has_buttons    = preg_match( '/<(button|a\s+[^>]*class=["\'][^"\']*(btn|button)[^"\']*)/i', $content );
		$has_flex_divs  = preg_match( '/style=["\'][^"\']*display\s*:\s*flex[^"\']*["\']/i', $content );
		$has_heavy_html = strlen( $content ) > 600 && substr_count( $content, '<div' ) >= 3;

		if ( ( $has_headings && $has_buttons ) || ( $has_flex_divs && $has_heavy_html ) ) {
			return true;
		}

		return false;
	}

	/**
	 * Decomposes a monolithic HTML layout into an array of native Elementor containers and widgets.
	 *
	 * @since 3.1.4
	 *
	 * @param string                        $html    Raw HTML content.
	 * @param MindCrafts_AI_Element_Factory $factory Element factory instance.
	 * @return array Array of Elementor container/widget structures.
	 */
	public static function decompose( string $html, MindCrafts_AI_Element_Factory $factory ): array {
		if ( ! class_exists( 'DOMDocument' ) ) {
			return array();
		}

		// Suppress libxml errors for HTML5 tags.
		$previous_libxml_errors = libxml_use_internal_errors( true );

		$dom = new DOMDocument( '1.0', 'UTF-8' );
		// Ensure UTF-8 encoding.
		$loaded = $dom->loadHTML(
			'<?xml encoding="utf-8" ?>' . '<div id="mindcrafts-root">' . $html . '</div>',
			LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD
		);

		libxml_clear_errors();
		libxml_use_internal_errors( $previous_libxml_errors );

		if ( ! $loaded ) {
			return array();
		}

		$root = $dom->getElementById( 'mindcrafts-root' );
		if ( ! $root ) {
			return array();
		}

		// If root has a single wrapper child (e.g. .codehubb-wrapper), drill down.
		$first_wrapper = self::find_first_wrapper( $root );
		$working_node  = $first_wrapper ? $first_wrapper : $root;

		// Extract top-level sections (header, section, footer, or top-level divs).
		$section_nodes = self::get_top_level_sections( $working_node );

		if ( empty( $section_nodes ) ) {
			// Fallback: parse whatever children exist.
			$section_nodes = array( $working_node );
		}

		$elementor_elements = array();

		foreach ( $section_nodes as $section_node ) {
			$container = self::build_container_from_node( $section_node, $factory );
			if ( ! empty( $container ) ) {
				$elementor_elements[] = $container;
			}
		}

		return $elementor_elements;
	}

	/**
	 * Finds if the root has a single wrapper div containing the whole page.
	 *
	 * @since 3.1.4
	 *
	 * @param DOMNode $root The root node.
	 * @return DOMNode|null Wrapper node or null.
	 */
	private static function find_first_wrapper( DOMNode $root ): ?DOMNode {
		$elements = array();
		foreach ( $root->childNodes as $child ) {
			if ( XML_ELEMENT_NODE === $child->nodeType ) {
				$elements[] = $child;
			}
		}

		if ( 1 === count( $elements ) ) {
			$tag = strtolower( $elements[0]->nodeName );
			if ( 'div' === $tag ) {
				return $elements[0];
			}
		}

		return null;
	}

	/**
	 * Extracts top-level structural sections.
	 *
	 * @since 3.1.4
	 *
	 * @param DOMNode $parent The parent node.
	 * @return DOMNode[] Array of section nodes.
	 */
	private static function get_top_level_sections( DOMNode $parent ): array {
		$sections = array();

		foreach ( $parent->childNodes as $child ) {
			if ( XML_ELEMENT_NODE !== $child->nodeType ) {
				continue;
			}

			$tag = strtolower( $child->nodeName );

			// Check for semantic section tags or prominent divs.
			if ( in_array( $tag, array( 'header', 'section', 'footer', 'nav', 'main', 'aside' ), true ) ) {
				$sections[] = $child;
			} elseif ( 'div' === $tag ) {
				$class = $child->attributes && $child->attributes->getNamedItem( 'class' ) ? $child->attributes->getNamedItem( 'class' )->nodeValue : '';
				$style = $child->attributes && $child->attributes->getNamedItem( 'style' ) ? $child->attributes->getNamedItem( 'style' )->nodeValue : '';

				// Skip pure background glow/blur decorative divs.
				if ( false !== strpos( $style, 'filter: blur' ) || false !== strpos( $style, 'radial-gradient' ) && false !== strpos( $style, 'pointer-events: none' ) ) {
					continue;
				}

				// If it contains child sections, drill down.
				$inner_sections = self::find_inner_structural_tags( $child );
				if ( ! empty( $inner_sections ) ) {
					foreach ( $inner_sections as $inner ) {
						$sections[] = $inner;
					}
				} else {
					$sections[] = $child;
				}
			}
		}

		return $sections;
	}

	/**
	 * Finds structural tags inside a wrapper node.
	 *
	 * @since 3.1.4
	 *
	 * @param DOMNode $node Wrapper node.
	 * @return DOMNode[] Array of inner structural nodes.
	 */
	private static function find_inner_structural_tags( DOMNode $node ): array {
		$found = array();
		foreach ( $node->childNodes as $child ) {
			if ( XML_ELEMENT_NODE !== $child->nodeType ) {
				continue;
			}
			$tag = strtolower( $child->nodeName );
			if ( in_array( $tag, array( 'header', 'section', 'footer', 'nav' ), true ) ) {
				$found[] = $child;
			}
		}
		return $found;
	}

	/**
	 * Builds an Elementor container from an HTML DOM node.
	 *
	 * @since 3.1.4
	 *
	 * @param DOMNode                       $node    The HTML DOM node.
	 * @param MindCrafts_AI_Element_Factory $factory The element factory.
	 * @return array Elementor container element.
	 */
	private static function build_container_from_node( DOMNode $node, MindCrafts_AI_Element_Factory $factory ): array {
		$style_attr = $node->attributes && $node->attributes->getNamedItem( 'style' ) ? $node->attributes->getNamedItem( 'style' )->nodeValue : '';
		$styles     = self::parse_css_style( $style_attr );

		$container_settings = array(
			'container_type' => 'flex',
			'content_width'  => 'boxed',
		);

		// Background color.
		if ( ! empty( $styles['background-color'] ) ) {
			$container_settings['background_background'] = 'classic';
			$container_settings['background_color']      = $styles['background-color'];
		} elseif ( ! empty( $styles['background'] ) && false === strpos( $styles['background'], 'gradient' ) ) {
			$container_settings['background_background'] = 'classic';
			$container_settings['background_color']      = $styles['background'];
		}

		// Flex direction.
		if ( ! empty( $styles['flex-direction'] ) ) {
			$container_settings['flex_direction'] = $styles['flex-direction'];
		}

		// Padding.
		if ( ! empty( $styles['padding'] ) ) {
			$padding_parts = self::parse_padding_shorthand( $styles['padding'] );
			if ( ! empty( $padding_parts ) ) {
				$container_settings['padding'] = $padding_parts;
			}
		}

		// Align items and justify content.
		if ( ! empty( $styles['align-items'] ) ) {
			$container_settings['align_items'] = $styles['align-items'];
		}
		if ( ! empty( $styles['justify-content'] ) ) {
			$container_settings['justify_content'] = $styles['justify-content'];
		}

		// Extract child widgets and inner containers.
		$child_elements = self::extract_widgets_from_node( $node, $factory );

		// Zero-content prevention fallback: Never create an empty container if the node has text!
		if ( empty( $child_elements ) ) {
			$raw_text = trim( $node->textContent );
			if ( ! empty( $raw_text ) ) {
				$child_elements[] = $factory->create_widget(
					'text-editor',
					array(
						'editor' => '<p>' . esc_html( $raw_text ) . '</p>',
					)
				);
			}
		}

		return $factory->create_container( $container_settings, $child_elements );
	}

	/**
	 * Extracts widgets from inside an HTML node.
	 *
	 * @since 3.1.4
	 *
	 * @param DOMNode                       $node    HTML node.
	 * @param MindCrafts_AI_Element_Factory $factory Element factory.
	 * @return array Array of Elementor widgets or inner containers.
	 */
	private static function extract_widgets_from_node( DOMNode $node, MindCrafts_AI_Element_Factory $factory ): array {
		$widgets = array();

		foreach ( $node->childNodes as $child ) {
			if ( XML_ELEMENT_NODE !== $child->nodeType ) {
				continue;
			}

			$tag        = strtolower( $child->nodeName );
			$style_attr = $child->attributes && $child->attributes->getNamedItem( 'style' ) ? $child->attributes->getNamedItem( 'style' )->nodeValue : '';
			$child_css  = self::parse_css_style( $style_attr );

			// Headings (h1 - h6).
			if ( preg_match( '/^h([1-6])$/', $tag, $matches ) ) {
				$text = trim( $child->textContent );
				if ( ! empty( $text ) ) {
					$heading_settings = array(
						'title'       => $text,
						'header_size' => 'h' . $matches[1],
					);
					if ( ! empty( $child_css['color'] ) ) {
						$heading_settings['title_color'] = $child_css['color'];
					}
					if ( ! empty( $child_css['text-align'] ) ) {
						$heading_settings['align'] = $child_css['text-align'];
					}
					$widgets[] = $factory->create_widget( 'heading', $heading_settings );
				}
				continue;
			}

			// Paragraphs (<p>).
			if ( 'p' === $tag ) {
				$text = trim( $child->textContent );
				if ( ! empty( $text ) ) {
					$widgets[] = $factory->create_widget(
						'text-editor',
						array(
							'editor' => '<p>' . esc_html( $text ) . '</p>',
						)
					);
				}
				continue;
			}

			// Badges, Labels, Subtitles, Inline text (<span>, <label>, <small>, <strong>, <b>, <em>).
			if ( in_array( $tag, array( 'span', 'label', 'small', 'strong', 'b', 'em' ), true ) ) {
				$text = trim( $child->textContent );
				if ( ! empty( $text ) ) {
					if ( strlen( $text ) <= 80 ) {
						$heading_settings = array(
							'title'       => $text,
							'header_size' => 'div',
						);
						if ( ! empty( $child_css['color'] ) ) {
							$heading_settings['title_color'] = $child_css['color'];
						}
						$widgets[] = $factory->create_widget( 'heading', $heading_settings );
					} else {
						$widgets[] = $factory->create_widget(
							'text-editor',
							array(
								'editor' => '<p>' . esc_html( $text ) . '</p>',
							)
						);
					}
				}
				continue;
			}

			// Feature & Navigation Lists (<ul>, <ol>).
			if ( 'ul' === $tag || 'ol' === $tag ) {
				$items = array();
				foreach ( $child->childNodes as $li ) {
					if ( XML_ELEMENT_NODE === $li->nodeType && 'li' === strtolower( $li->nodeName ) ) {
						$li_text = trim( $li->textContent );
						if ( ! empty( $li_text ) ) {
							$items[] = '<li>' . esc_html( $li_text ) . '</li>';
						}
					}
				}
				if ( ! empty( $items ) ) {
					$widgets[] = $factory->create_widget(
						'text-editor',
						array(
							'editor' => '<' . $tag . '>' . implode( '', $items ) . '</' . $tag . '>',
						)
					);
				}
				continue;
			}

			// Blockquotes & Testimonials (<blockquote>).
			if ( 'blockquote' === $tag ) {
				$text = trim( $child->textContent );
				if ( ! empty( $text ) ) {
					$widgets[] = $factory->create_widget(
						'text-editor',
						array(
							'editor' => '<blockquote>' . esc_html( $text ) . '</blockquote>',
						)
					);
				}
				continue;
			}

			// Buttons / CTA Links (<a> or <button>).
			if ( 'a' === $tag || 'button' === $tag ) {
				$text = trim( $child->textContent );
				$href = $child->attributes && $child->attributes->getNamedItem( 'href' ) ? $child->attributes->getNamedItem( 'href' )->nodeValue : '#';

				if ( ! empty( $text ) && strlen( $text ) < 60 ) {
					$button_settings = array(
						'text' => $text,
						'link' => array( 'url' => esc_url_raw( $href ) ),
					);
					if ( ! empty( $child_css['background'] ) || ! empty( $child_css['background-color'] ) ) {
						$button_settings['background_color'] = $child_css['background-color'] ?? $child_css['background'];
					}
					if ( ! empty( $child_css['color'] ) ) {
						$button_settings['button_text_color'] = $child_css['color'];
					}
					$widgets[] = $factory->create_widget( 'button', $button_settings );
					continue;
				}
			}

			// Images (<img>).
			if ( 'img' === $tag ) {
				$src = $child->attributes && $child->attributes->getNamedItem( 'src' ) ? $child->attributes->getNamedItem( 'src' )->nodeValue : '';
				if ( ! empty( $src ) ) {
					$image_spec = array( 'url' => esc_url_raw( $src ) );

					// Resolve image via Media Resolver if available.
					if ( class_exists( 'MindCrafts_AI_Media_Resolver' ) ) {
						$resolver = new MindCrafts_AI_Media_Resolver();
						$resolved = $resolver->resolve_image( $src, 'imported' );
						if ( ! empty( $resolved['id'] ) ) {
							$image_spec['id'] = $resolved['id'];
						}
					}

					$widgets[] = $factory->create_widget(
						'image',
						array(
							'image' => $image_spec,
						)
					);
				}
				continue;
			}

			// Navbars / Group of links.
			if ( 'nav' === $tag ) {
				$nav_items = self::extract_widgets_from_node( $child, $factory );
				if ( ! empty( $nav_items ) ) {
					$nav_container = $factory->create_container(
						array(
							'flex_direction' => 'row',
							'align_items'    => 'center',
							'gap'            => array( 'size' => 16, 'unit' => 'px' ),
						),
						$nav_items
					);
					$nav_container['isInner'] = true;
					$widgets[]                = $nav_container;
				}
				continue;
			}

			// Inner flex containers or columns (<div>).
			if ( 'div' === $tag ) {
				$is_flex     = ! empty( $child_css['display'] ) && 'flex' === $child_css['display'];
				$sub_widgets = self::extract_widgets_from_node( $child, $factory );

				if ( ! empty( $sub_widgets ) ) {
					if ( $is_flex || count( $sub_widgets ) > 1 ) {
						$inner_container = $factory->create_container(
							array(
								'flex_direction' => $child_css['flex-direction'] ?? 'row',
								'align_items'    => $child_css['align-items'] ?? 'center',
								'gap'            => array( 'size' => 16, 'unit' => 'px' ),
								'content_width'  => 'full',
							),
							$sub_widgets
						);
						$inner_container['isInner'] = true;
						$widgets[]                  = $inner_container;
					} else {
						// Flatten single widgets.
						foreach ( $sub_widgets as $sw ) {
							$widgets[] = $sw;
						}
					}
				} else {
					// Fallback for divs with direct text: Never lose text!
					$div_text = trim( $child->textContent );
					if ( ! empty( $div_text ) ) {
						if ( strlen( $div_text ) <= 70 && ( ! empty( $child_css['font-weight'] ) || ! empty( $child_css['font-size'] ) ) ) {
							$widgets[] = $factory->create_widget(
								'heading',
								array(
									'title'       => $div_text,
									'header_size' => 'h3',
									'title_color' => $child_css['color'] ?? '',
								)
							);
						} else {
							$widgets[] = $factory->create_widget(
								'text-editor',
								array(
									'editor' => '<p>' . esc_html( $div_text ) . '</p>',
								)
							);
						}
					}
				}
				continue;
			}
		}

		return $widgets;
	}

	/**
	 * Parses an inline CSS style string into an associative key-value array.
	 *
	 * @since 3.1.4
	 *
	 * @param string $style Inline style string.
	 * @return array Key-value pairs of CSS properties.
	 */
	public static function parse_css_style( string $style ): array {
		$rules = array();
		if ( empty( $style ) ) {
			return $rules;
		}

		$declarations = explode( ';', $style );
		foreach ( $declarations as $decl ) {
			$decl = trim( $decl );
			if ( empty( $decl ) ) {
				continue;
			}
			$parts = explode( ':', $decl, 2 );
			if ( 2 === count( $parts ) ) {
				$key           = strtolower( trim( $parts[0] ) );
				$val           = trim( $parts[1] );
				$rules[ $key ] = $val;
			}
		}

		return $rules;
	}

	/**
	 * Parses CSS padding shorthand (e.g. "90px 24px 70px" or "36px") into Elementor format.
	 *
	 * @since 3.1.4
	 *
	 * @param string $padding Padding string.
	 * @return array Elementor padding dimensions array.
	 */
	public static function parse_padding_shorthand( string $padding ): array {
		$parts = preg_split( '/\s+/', trim( $padding ) );
		if ( empty( $parts ) ) {
			return array();
		}

		$clean_num = function( $val ) {
			return (string) absint( preg_replace( '/[^0-9]/', '', $val ) );
		};

		if ( 1 === count( $parts ) ) {
			$v = $clean_num( $parts[0] );
			return array(
				'unit'     => 'px',
				'top'      => $v,
				'right'    => $v,
				'bottom'   => $v,
				'left'     => $v,
				'isLinked' => true,
			);
		} elseif ( 2 === count( $parts ) ) {
			$tb = $clean_num( $parts[0] );
			$lr = $clean_num( $parts[1] );
			return array(
				'unit'     => 'px',
				'top'      => $tb,
				'right'    => $lr,
				'bottom'   => $tb,
				'left'     => $lr,
				'isLinked' => false,
			);
		} elseif ( 4 === count( $parts ) ) {
			return array(
				'unit'     => 'px',
				'top'      => $clean_num( $parts[0] ),
				'right'    => $clean_num( $parts[1] ),
				'bottom'   => $clean_num( $parts[2] ),
				'left'     => $clean_num( $parts[3] ),
				'isLinked' => false,
			);
		}

		return array();
	}
}
