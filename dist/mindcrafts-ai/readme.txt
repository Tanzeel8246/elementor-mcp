=== MindCrafts AI for Elementor ===
Contributors: mindcraftsai
Tags: elementor, ai, mcp, claude, cursor
Requires at least: 6.8
Tested up to: 6.8
Requires PHP: 7.4
Stable tag: 3.1.6
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Transform Elementor with AI power. Build complete pages from a single prompt, manage widgets, layouts, templates, and more via MCP tools for AI agents like Claude and Cursor.

== Description ==

MindCrafts AI for Elementor is a revolutionary plugin that bridges the gap between powerful AI agents (like Claude Desktop, Claude Code, Cursor, and Windsurf) and Elementor's visual page builder.

Using the Model Context Protocol (MCP), this plugin exposes Elementor's internal data structures and building capabilities as standardized "tools" that AI agents can use to understand your site's design and build new pages for you — completely hands-free.

= Free Features =
* **AI Prompt-to-Page:** Let AI generate full pages based on text prompts.
* **Page Analyzer:** Let AI analyze page structure for SEO and design improvements.
* **Smart Clone:** Instantly clone sections with AI-driven variations.
* **Bulk Updates:** Update typography, colors, and content across multiple widgets at once.

= Premium Add-on Features =
Premium tool entry points are license-gated and return explicit add-on-required errors unless the production premium add-on is installed. This prevents silent fake writes while keeping the free plugin safe and predictable.

== Installation ==

1. Upload the plugin files to the `/wp-content/plugins/mindcrafts-ai` directory, or install the plugin through the WordPress plugins screen directly.
2. Activate the plugin through the 'Plugins' screen in WordPress.
3. Go to **MindCrafts AI > Setup**.
4. Use the setup screen to install or activate Elementor and the WordPress MCP Adapter when they are missing.
5. Copy the recommended STDIO proxy preset into your AI client, add a fresh WordPress Application Password, then restart the AI client.

== Frequently Asked Questions ==

= Does this require an API key? =
No, the plugin operates entirely locally on your WordPress site. The AI agent (like Claude Desktop) runs on your machine and communicates with your site via the MCP protocol.

= Which AI clients are supported? =
Any AI client that supports the Model Context Protocol (MCP). We provide quick-start configs for Claude Desktop, Claude Code (CLI), Cursor, and Windsurf.

= Why does the setup screen recommend the STDIO proxy? =
Some desktop AI clients do not reliably handle direct Streamable HTTP sessions with remote WordPress sites. The recommended proxy uses the official `@automattic/mcp-wordpress-remote` bridge, so Codex, Claude Desktop, Cursor, and similar MCP clients can connect with the same simple config.

= Does this work with Elementor Pro? =
Yes. MindCrafts AI can inspect registered Elementor widgets and includes convenience tools for common Elementor Pro widgets when Elementor Pro is active. Dedicated WooCommerce, SEO, add-on, responsive, and marketplace workflows require the production premium add-on.

== Changelog ==

= 3.1.6 =
* Fix: Corrected method call from find_element to find_element_by_id in class-query-abilities.php for execute_get_element_settings.
* درستگی: Query Abilities میں find_element_by_id کے درست میتھڈ کا نفاذ۔

= 3.1.5 =
* ون کلک 6-پیجز نیٹو کنورٹر: ہوم، سروسز، پروجیکٹس، اباؤٹ، پرائسنگ اور کانٹیکٹ کو خودکار نیٹو ایلیمینٹور وزٹس میں منتقل کرنے کا ڈیش بورڈ بٹن۔
* نیا ٹول شامل: mindcrafts-ai/migrate-all-pages-to-native — تمام 6 صفحات کی سیکنڈوں میں خودکار ڈیکمپوزیشن اور ایلیمینٹور کنٹینرز میں منتقلی۔
* ایڈمن ڈیش بورڈ میں مکمل لائیو پروگریس اور ایلیمینٹور میں فوری ایڈٹ کے لنکس۔

= 3.1.4 =
* اینٹی شارٹ کٹ گارڈ ریل: ٹیکسٹ ایڈیٹر میں خاموش HTML کوڈ ڈمپ کرنے پر سخت پابندی اور خودکار روک تھام۔
* سمارٹ ایچ ٹی ایم ایل ڈیکمپوزر (MindCrafts_AI_Html_Decomposer) شامل: خام HTML کوڈ کو خودکار طور پر ایلیمینٹور کے حقیقی کنٹینرز، ہیڈنگز، بٹنز، اور امیج وجٹس میں تقسیم کرتا ہے۔
* نیا ٹول شامل: mindcrafts-ai/convert-html-to-elementor — کسی بھی ایچ ٹی ایم ایل لے آؤٹ کو فوری ویژول ایلیمینٹور کمپونینٹس میں تبدیل کرنے کے لیے۔
* سسٹم پرامپٹس اپڈیٹ: ایلیمینٹور کے ویژول ڈیزائنرز کے لیے نیٹو وجٹس کی لازمی ہدایات شامل کی گئیں۔

= 3.1.3 =
* ورژن کنٹرول سسٹم شامل — تمام تبدیلیاں CHANGELOG.md میں محفوظ ہوتی ہیں۔
* Plugin header, constant, readme Stable tag یکجا ورژن اپڈیٹ پروٹوکول قائم کیا۔

= 3.1.0 =
* سمارٹ میڈیا ریزولور (MindCrafts_AI_Media_Resolver) شامل — ورڈپریس گیلری پہلے، پھر Openverse فال بیک۔
* Element Factory: خودکار موبائل رسپانسو (flex_direction_mobile، padding_mobile، heading font size)۔
* WooCommerce Single Product Template: یکطرفہ Column سے جدید 2-Column Flexbox Layout میں تبدیل۔
* سیڈ ڈیمو پروڈکٹس ٹول شامل (mindcrafts-ai/seed-demo-products) — 12 تک ڈمی پروڈکٹس۔
* Settings Validator: underscore keys، responsive suffixes اور flexbox controls کو allowlist میں۔
* Schema Generator cache: ڈائریکٹ SQL ہٹا، Redis/Memcached مطابق delete_transient() لگائی۔

= 2.3.0 =
* 8-12 سیکشنز پر مبنی فل لینتھ پیج بلیو پرنٹ فریم ورک۔
* ای کامرس اور SaaS landing page سسٹم پرامپٹس شامل۔
* WooCommerce: add-woo-menu-cart، add-woo-product-tabs، add-woo-related-products ایبیلیٹیز۔

= 2.1.0 =
* Schema caching: WordPress Transients API + in-memory dual-layer cache۔
* Snapshot system: save_snapshot() / list_snapshots() / restore_snapshot()۔
* Structured MCP API call logging with log rotation (5MB threshold)۔
* MCP Prompts registered: build-elementor-page، edit-elementor-page، apply-design-system، troubleshoot-mcp۔

= 2.0.0 =
* Major rebrand to MindCrafts AI۔
* Brand new admin dashboard and styling۔
* Setup screen with dependency status, one-click install/activate actions, and AI connection presets۔
* Page analyzer, smart clone, bulk color update, and bulk typography update tools۔
* Hardened MCP namespace, proxy endpoint, credential examples, permission checks, and validation۔

= 1.0.0 =
* Initial release of the Elementor integration core۔

