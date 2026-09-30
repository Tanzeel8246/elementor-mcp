# MindCrafts AI for Elementor — Changelog

## [3.1.7] - 2026-09-30
### Added
- **Anti-Wipe Safety Armor on `delete-page-content`:** Protected existing live pages from destructive wiping by requiring explicit `confirm_wipe: true`. Rejects blind AI deletion requests on pages that currently contain content.
- **In-Place Page Rebuild (`build-page` `post_id` parameter):** Added direct `post_id` support to `mindcrafts-ai/build-page`. AI agents can now rebuild or update existing pages directly in place with auto-snapshots, eliminating the dangerous habit of clearing pages before rebuilding.
- **Empty-Container Skeleton Rejection Guardrail:** Enforced strict validation preventing AI agents from creating pages with empty container skeletons or zero content widgets. Pages must contain actual content widgets (headings, text, buttons, images).
- **Deep HTML Decomposer Content Retention:** Added deep parsing for `span`, `label`, `small`, `strong`, `b`, `em`, `ul`, `ol`, `li`, `blockquote`, and text divs in `MindCrafts_AI_Html_Decomposer`. Ensures zero text or design elements are dropped during conversion.
- **Emergency Disaster Recovery & Snapshot Restore UI:** Added recovery instructions and AJAX snapshot restoration handlers in the WordPress Admin dashboard (`MindCrafts AI > Setup`).

## [3.1.6] - 2026-09-27
### Fixed
- **Query Abilities Element Lookup:** Fixed fatal call to undefined method `$this->data->find_element()` in `MindCrafts_AI_Query_Abilities::execute_get_element_settings()`. Corrected invocation to `$this->data->find_element_by_id( $data, $element_id )` to resolve runtime failure when retrieving element settings by ID.

## [3.1.5] - 2026-09-26
### Added
- **1-Click 6-Page Native Converter:** Built-in automated migrator that scans all 6 core site pages (Home, Services, Projects, About, Pricing, Contact), unpacks monolithic HTML code dumps, and transforms them into 100% native Elementor Flexbox Containers, Headings, Buttons, and Images.
- **MCP Tool `mindcrafts-ai/migrate-all-pages-to-native`:** Allows AI agents or developers to trigger full-site native conversion via a single MCP request.
- **WordPress Admin Setup Dashboard UI:** Added an interactive one-click converter card in `MindCrafts AI > Setup` with AJAX execution, progress feedback, and direct "Edit in Elementor" links.

## [3.1.4] - 2026-09-26
### Added
- **Monolithic HTML Anti-Shortcut Guardrail:** Enforced strict prevention against dumping full HTML/CSS page layouts inside single `text-editor` or `html` widgets. Prevents uneditable black-box designs for visual WordPress designers.
- **Smart HTML Decomposer (`MindCrafts_AI_Html_Decomposer`):** Automatic parser that takes any raw HTML layout and intelligently converts it into native Elementor visual Containers, Heading widgets, Button widgets, Image widgets, and Text-Editor widgets.
- **Convert HTML to Elementor Tool:** Added `mindcrafts-ai/convert-html-to-elementor` allowing AI or developers to convert arbitrary HTML directly into editable visual Elementor pages or element trees.
- **Visual Designer System Prompts:** Updated `build-elementor-page` system instructions to strictly mandate modular Elementor widgets and containers for every visual section.

## [3.1.3] - 2026-09-26
### Added
- **Strict Version Control Mandate:** Enforced project-wide semantic versioning policy across plugin headers, constants, readme, and changelogs.
- **Auto-Version Policy in AGENTS.md:** All future modifications, bug fixes, or enhancements must automatically bump the version code.

## [3.1.0] - 2026-09-26
### Added
- **Smart Media Resolver (`MindCrafts_AI_Media_Resolver`):** Dual-tier image resolution pipeline. Searches local WordPress Media Library first; seamlessly downloads high-quality CC0 images from Openverse as fallback via `media_handle_sideload()`.
- **Auto-Responsive Layout Engine (`MindCrafts_AI_Element_Factory`):**
  - Containers with `flex_direction: row` automatically receive `flex_direction_mobile: column` and `flex_wrap_mobile: wrap` to prevent mobile squishing.
  - Automatic mobile padding injection (`padding_mobile: 20px/15px`) when desktop padding exceeds 40px.
  - Headings with desktop typography font size >= 36px automatically receive mobile font size clamping (26px–32px).
- **WooCommerce 2-Column Product Layout:** Refactored `create-woo-single-product-template` from vertical stack into modern responsive 2-column flexbox layout (Gallery left, Purchase stack right, Tabs & Related products below).
- **WooCommerce Demo Seed Tool:** Added `mindcrafts-ai/seed-demo-products` tool capable of provisioning up to 12 sample WooCommerce products with titles, prices, SKUs, and auto-downloaded media placeholders.

### Fixed
- **Settings Validator Underscore & Responsive Allowlist:** Fixed false-positive validation errors for Elementor global controls (`_margin`, `_padding`, `_z_index`, etc.), flexbox controls, and responsive suffixes (`_mobile`, `_tablet`).
- **Redis/Memcached Compatible Schema Cache:** Removed raw SQL `DELETE` queries from `clear_cache()`; replaced with tracked transient indexing and standard `delete_transient()`.

## [2.1.0] - 2026-09-24
### Added
- **Theme Builder Abilities:** Full suite of Elementor Pro Theme Builder tools (`mindcrafts-ai/list-theme-templates`, `create-theme-template`, `set-template-conditions`, `apply-theme-location`).
- **Popup Builder Abilities:** Complete popup creation and trigger automation (`mindcrafts-ai/create-popup`, `list-popups`, `set-popup-trigger`).
- **Dynamic Tags Abilities:** Dynamic tag listing and setting binding (`mindcrafts-ai/list-dynamic-tags`, `apply-dynamic-tag`).
- **WooCommerce Store Builder:** Added support for Single Product templates, Shop Archive templates, Cart, Checkout, My Account, and Product Rating widgets.
- **Third-Party Addon Schema Integration:** Automatically extracts full JSON schema for widgets from ElementsKit, Essential Addons, Ultimate Addons, JetPlugins, and more.
- **Dynamic Breakpoints:** Responsive settings now dynamically discover active breakpoints registered in Elementor (mobile, mobile_extra, tablet, tablet_extra, laptop, widescreen).
- **Production License Manager:** Remote license activation/deactivation via API, 12-hour transient caching with local offline fallback.
- **Admin Audit Logs & Diagnostics:** Live MCP server API audit log viewer with automatic 5MB log rotation (preserving 3 rotated logs), log clearing, and system diagnostics report tab.
- **Comprehensive PHPUnit Test Suite:** 30+ tests covering Element Factory, Data Layer operations, Composite Page Builder, Control Mapper, Rate Limiting, and Licensing.

### Fixed
- **SEO & Marketplace Abilities:** Cleanly gated behind active premium license check; eliminated uncalled stub instantiation.
- **Self-Alias Bug:** Removed redundant `class_alias` self-reference in `class-plugin.php`.
- **Log Security & Rotation:** Added `.htaccess` direct access denial, eliminated bulky response dumps, and added automatic 5MB rotation.
- **Line Endings:** Added root `.gitattributes` to normalize CRLF/LF line endings.
- **Admin Tool Registry:** Fixed syntax error in tool definitions and expanded registry to list all 50+ abilities across 14 categories.

## [2.0.0] - 2026-06-05
### Added
- **Major Rebrand:** Transformed "Elementor MCP" into "MindCrafts AI".
- **New Admin Dashboard:** Complete redesign of the WordPress admin settings page with brand new CSS and SVG logo support.
- **Foundational Architecture:** Added structural foundations for upcoming free tools (AI Prompt-to-Page, Page Analyzer) and premium features (WooCommerce, SEO, Licensing).
- **Security & Quality:** Addressed inline JS issues by properly enqueuing admin scripts. Fixed missing esc_attr in admin views.

### Changed
- Refactored all PHP class names, constants, and hooks to use `MindCrafts_AI_` prefix.
- Transferred legacy Elementor MCP logic to the new `mindcrafts-ai.php` entrypoint.

## [1.0.0] - Initial Release (Legacy)
### Added
- Core MCP capabilities (Reading page structures, creating layouts, modifying widgets).
- WP-CLI, HTTP Proxy, and Claude Desktop connection configurations.
