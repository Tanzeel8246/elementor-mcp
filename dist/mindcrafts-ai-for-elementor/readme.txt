=== MindCrafts AI for Elementor ===
Contributors: mindcraftsai
Tags: elementor, ai, mcp, claude, cursor
Requires at least: 6.8
Tested up to: 6.8
Requires PHP: 7.4
Stable tag: 2.0.0
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

= 2.0.0 =
* Major rebrand to MindCrafts AI.
* Brand new admin dashboard and styling.
* Added a setup screen with dependency status, one-click install/activate actions, and AI connection presets.
* Added working page analyzer, smart clone, bulk color update, and bulk typography update tools.
* Hardened MCP namespace, proxy endpoint, credential examples, permission checks, and validation.

= 1.0.0 =
* Initial release of the Elementor integration core.
