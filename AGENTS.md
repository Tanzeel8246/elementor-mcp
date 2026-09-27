# AGENTS.md

This file provides guidance to Codex (Codex.ai/code) when working with code in this repository.

## Project Overview

Elementor MCP Plugin — a WordPress plugin that extends the official WordPress MCP Adapter to expose Elementor data, widgets, structures, and methods as MCP (Model Context Protocol) tools. This enables AI tools (Codex, Cursor, etc.) to create and manipulate Elementor page designs programmatically via ~37 MCP tools.

**Current status: All phases implemented (P0/P1/P2).** Foundation layer, 7 read-only query tools, page CRUD, layout, widget, template, global, and composite tools are all complete (~37 MCP tools total). See `PLAN.md` for the full architectural specification.

## Dependencies & Requirements

- WordPress >= 6.8
- Elementor >= 3.20 (container support required)
- WordPress Abilities API (bundled in WP 6.9+, or via composer)
- WordPress MCP Adapter (`wordpress/mcp-adapter` via composer)
- PHP 7.4+

## Build & Development Commands

No external dependencies. The plugin uses WordPress core, Elementor, MCP Adapter, and the Abilities API (all loaded as separate plugins or WP core).

For plugin review tooling, the `.Codex/skills/wp-plugin-review/scripts/setup_tools.sh` script installs PHPCS, WPCS, PHPStan, and PHPUnit.

## Architecture

### MCP Server Registration

The plugin registers a dedicated MCP server `mindcrafts-ai-server` at `/wp-json/mcp/mindcrafts-ai-server`. All abilities use the `mindcrafts-ai/` namespace.

### Directory Structure

```
mindcrafts-ai/
├── mindcrafts-ai.php                          # Bootstrap: plugin header, constants, dependency checks, require_once, singleton init
├── includes/
│   ├── class-plugin.php                       # Singleton orchestrator — hooks into wp_abilities_api_categories_init, wp_abilities_api_init, mcp_adapter_init
│   ├── class-elementor-data.php               # Data access layer wrapping Elementor documents, widgets, element tree
│   ├── class-element-factory.php              # Builds valid Elementor JSON element structures (container, widget, section, column)
│   ├── class-id-generator.php                 # 7-char hex unique IDs via random_bytes()
│   ├── class-openverse-client.php             # HTTP client for Openverse image search API
│   ├── abilities/
│   │   ├── class-ability-registrar.php        # Coordinates registration of all ability groups across all phases
│   │   ├── class-query-abilities.php          # P0: 7 read-only tools (list-widgets, get-widget-schema, get-page-structure, etc.)
│   │   ├── class-page-abilities.php           # P1: 5 page CRUD tools (create-page, update-page-settings, delete-page-content, import-template, export-page)
│   │   ├── class-layout-abilities.php         # P1: 4 layout tools (add-container, move-element, remove-element, duplicate-element)
│   │   ├── class-widget-abilities.php         # P1/P2: 2 universal + 9 core + 6 Pro convenience widget tools
│   │   ├── class-template-abilities.php       # P2: 2 template tools (save-as-template, apply-template)
│   │   ├── class-global-abilities.php         # P2: 2 global tools (update-global-colors, update-global-typography)
│   │   ├── class-composite-abilities.php      # P2: 1 composite tool (build-page)
│   │   └── class-stock-image-abilities.php    # 3 stock image tools (search-images, sideload-image, add-stock-image)
│   ├── schemas/
│   │   ├── class-schema-generator.php         # Generates JSON Schema from Elementor widget controls
│   │   └── class-control-mapper.php           # Maps individual Elementor control types → JSON Schema fragments
│   └── validators/
│       ├── class-element-validator.php         # Validates element structure (id, elType, widgetType)
│       └── class-settings-validator.php        # Validates widget settings against generated schema
└── tests/                                      # PHPUnit tests (not yet created)
```

### Hook Registration Flow

The plugin integrates via three WordPress hooks (in execution order):
1. **`wp_abilities_api_categories_init`** → Registers the `mindcrafts-ai` ability category
2. **`wp_abilities_api_init`** → Registers all abilities via `wp_register_ability()` (ability names must match `[a-z0-9-]+/[a-z0-9-]+`)
3. **`mcp_adapter_init`** → Creates MCP server via `$mcp_adapter->create_server()`, passing ability names as the tools array

The MCP Adapter converts ability names like `mindcrafts-ai/list-widgets` to tool names `mindcrafts-ai-list-widgets` (replacing `/` with `-`).

### Core Layers

1. **Data Layer** (`class-elementor-data.php`) — Read/write wrapper around `_elementor_data` post meta, widget registry, and element tree traversal. All saves go through `\Elementor\Plugin::$instance->documents->get()->save()` (never raw meta updates) to trigger CSS regeneration and cache busting.

2. **Element Factory** (`class-element-factory.php`) — Creates valid Elementor JSON structures for containers, widgets, sections, and columns. Each element gets a 7-char random hex ID.

3. **Schema Generator** (`class-schema-generator.php` + `class-control-mapper.php`) — Maps Elementor control types (TEXT, SLIDER, SELECT, MEDIA, DIMENSIONS, REPEATER, etc.) to JSON Schema. This powers the `get-widget-schema` tool that tells AI agents what settings each widget accepts.

4. **Abilities** — Grouped by domain: query (7 read-only tools), page CRUD (5), layout/container (4), widgets (16 including convenience shortcuts), templates (2), globals (2), and the composite `build-page` tool.

### Implementation Phases (from PLAN.md)

| Priority | Phase | Scope |
|----------|-------|-------|
| P0 | Foundation | Bootstrap, data layer, factory, schemas, 7 read/query tools |
| P1 | Pages & Widgets | Page CRUD (5), layout tools (4), widget tools (10+) |
| P2 | Templates & Composite | Template tools (2), global tools (2), build-page composite (1) |

### Key Design Patterns

- **Container-first**: Uses modern Elementor Container element (flexbox), not legacy Sections/Columns
- **Schema-driven validation**: Widget settings validated against auto-generated JSON schemas before saving
- **Universal + convenience**: `add-widget` works for any widget type; convenience tools (`add-heading`, `add-button`) provide simpler interfaces for common widgets
- **Pro-aware**: Pro widget tools only register when Elementor Pro is active; core tools work with free Elementor

### Permission Model

| Ability Group | Required WordPress Capability |
|---------------|-------------------------------|
| Read/Query | `edit_posts` |
| Page creation | `publish_pages` or `edit_pages` |
| Widget/layout manipulation | `edit_posts` + ownership check |
| Template management | `edit_posts` |
| Global settings | `manage_options` |
| Delete operations | `delete_posts` + ownership check |
| Stock image search | `edit_posts` |
| Stock image sideload | `upload_files` |
| Stock image add | `edit_posts` + `upload_files` + ownership check |

## All Implemented Tools (~40 total)

### P0 — Query/Discovery (7 read-only)

| Ability Name | Purpose |
|---|---|
| `mindcrafts-ai/list-widgets` | All registered widget types with names, titles, icons, categories, keywords |
| `mindcrafts-ai/get-widget-schema` | Full JSON Schema for a widget's settings (auto-generated from Elementor controls) |
| `mindcrafts-ai/get-page-structure` | Element tree for a page (containers, widgets, nesting) |
| `mindcrafts-ai/get-element-settings` | Current settings for a specific element on a page |
| `mindcrafts-ai/list-pages` | All Elementor-enabled pages/posts |
| `mindcrafts-ai/list-templates` | Saved Elementor templates from the template library |
| `mindcrafts-ai/get-global-settings` | Active kit/global settings (colors, typography, spacing) |

### P1 — Page CRUD (5 tools)

| Ability Name | Purpose |
|---|---|
| `mindcrafts-ai/create-page` | Create a new WP page/post with Elementor enabled |
| `mindcrafts-ai/update-page-settings` | Update page-level Elementor settings (background, padding, etc.) |
| `mindcrafts-ai/delete-page-content` | Clear all Elementor content from a page (destructive) |
| `mindcrafts-ai/import-template` | Import JSON template structure into a page |
| `mindcrafts-ai/export-page` | Export page's full Elementor data as JSON |

### P1 — Layout (4 tools)

| Ability Name | Purpose |
|---|---|
| `mindcrafts-ai/add-container` | Add a flexbox container (top-level or nested) |
| `mindcrafts-ai/move-element` | Move an element to a new parent/position |
| `mindcrafts-ai/remove-element` | Remove an element and all children (destructive) |
| `mindcrafts-ai/duplicate-element` | Duplicate element with fresh IDs |

### P1/P2 — Widgets (2 universal + 9 core + 6 Pro convenience)

| Ability Name | Purpose |
|---|---|
| `mindcrafts-ai/add-widget` | Universal: add any widget type to a container |
| `mindcrafts-ai/update-widget` | Universal: update settings on an existing widget |
| `mindcrafts-ai/add-heading` | Convenience: heading widget |
| `mindcrafts-ai/add-text-editor` | Convenience: rich text editor widget |
| `mindcrafts-ai/add-image` | Convenience: image widget |
| `mindcrafts-ai/add-button` | Convenience: button widget |
| `mindcrafts-ai/add-video` | Convenience: video widget |
| `mindcrafts-ai/add-icon` | Convenience: icon widget |
| `mindcrafts-ai/add-spacer` | Convenience: spacer widget |
| `mindcrafts-ai/add-divider` | Convenience: divider widget |
| `mindcrafts-ai/add-icon-box` | Convenience: icon box widget |
| `mindcrafts-ai/add-form` | Pro: form widget |
| `mindcrafts-ai/add-posts-grid` | Pro: posts grid widget |
| `mindcrafts-ai/add-countdown` | Pro: countdown timer widget |
| `mindcrafts-ai/add-price-table` | Pro: price table widget |
| `mindcrafts-ai/add-flip-box` | Pro: flip box widget |
| `mindcrafts-ai/add-animated-headline` | Pro: animated headline widget |

### P2 — Templates (2 tools)

| Ability Name | Purpose |
|---|---|
| `mindcrafts-ai/save-as-template` | Save a page or element as reusable template |
| `mindcrafts-ai/apply-template` | Apply a saved template to a page |

### P2 — Global Settings (2 tools)

| Ability Name | Purpose |
|---|---|
| `mindcrafts-ai/update-global-colors` | Update site-wide color palette in Elementor kit |
| `mindcrafts-ai/update-global-typography` | Update site-wide typography in Elementor kit |

### P2 — Composite (1 tool)

| Ability Name | Purpose |
|---|---|
| `mindcrafts-ai/build-page` | Create complete page from declarative structure in one call |

### Stock Images (3 tools)

| Ability Name | Purpose |
|---|---|
| `mindcrafts-ai/search-images` | Search Openverse (WordPress.org) for Creative Commons images by keyword |
| `mindcrafts-ai/sideload-image` | Download an external image URL into the WordPress Media Library |
| `mindcrafts-ai/add-stock-image` | Search + sideload + add image widget to page in one call |

## Connecting to the MCP Server

### Prerequisites

- WordPress with Elementor + MCP Adapter + Elementor MCP all active
- One of: WP-CLI (for local) or Node.js 18+ (for remote/proxy)
- A WordPress Application Password (Users > Profile > Application Passwords)

### Option A: WP-CLI stdio (local dev, recommended)

The MCP Adapter includes a built-in WP-CLI stdio bridge. No HTTP round-trip, no sessions, no auth config needed.

**Codex** (`.mcp.json` already in project root):
```json
{
  "mcpServers": {
    "mindcrafts-ai": {
      "type": "stdio",
      "command": "wp",
      "args": ["mcp-adapter", "serve", "--server=mindcrafts-ai-server", "--user=admin", "--path=/path/to/wordpress"]
    }
  }
}
```

**Codex Desktop** (add to `%APPDATA%\Codex\claude_desktop_config.json`):
```json
{
  "mcpServers": {
    "mindcrafts-ai": {
      "command": "wp",
      "args": ["mcp-adapter", "serve", "--server=mindcrafts-ai-server", "--user=admin", "--path=/path/to/wordpress"]
    }
  }
}
```

**Verify:** `wp mcp-adapter list --path=/path/to/wordpress` should show `mindcrafts-ai-server`.

### Option B: Node.js HTTP proxy (remote sites)

For remote WordPress sites or environments without WP-CLI, use the bundled proxy at `bin/mcp-proxy.mjs`. It bridges stdio ↔ WordPress HTTP endpoint with Application Password auth.

```json
{
  "mcpServers": {
    "mindcrafts-ai": {
      "command": "node",
      "args": ["bin/mcp-proxy.mjs"],
      "env": {
        "WP_URL": "https://your-site.com",
        "WP_USERNAME": "admin",
        "WP_APP_PASSWORD": "xxxx xxxx xxxx xxxx xxxx xxxx"
      }
    }
  }
}
```

### Option C: Direct HTTP (VS Code MCP extension)

```json
{
  "servers": {
    "mindcrafts-ai": {
      "type": "http",
      "url": "https://your-site.com/wp-json/mcp/mindcrafts-ai-server",
      "headers": {
        "Authorization": "Basic BASE64_ENCODED_CREDENTIALS"
      }
    }
  }
}
```

### Testing with MCP Inspector

```bash
npx @modelcontextprotocol/inspector wp mcp-adapter serve --server=mindcrafts-ai-server --user=admin --path=/path/to/wordpress
```

### Troubleshooting

- **"No MCP servers registered"**: Ensure Elementor MCP plugin is active and dependencies are met
- **HTTP 401**: Check Application Password is correct and user has `edit_posts` capability
- **Session errors**: The HTTP endpoint requires `Mcp-Session-Id` header after `initialize`; the proxy handles this automatically
- **WP-CLI not found on Windows**: Use full path to `php.exe` and `wp-cli.phar`

## Version Control & Release Policy (Strict Mandate)

**Current Version:** `3.1.6`

Whenever **ANY** modification, bug fix, feature addition, or architectural refactor is made to this codebase, the agent or developer **MUST ALWAYS** increment the version number and synchronize all version references. Never leave the version unchanged after modifying code.

### Required Steps on Every Code Change:
1. **Determine SemVer Bump**:
   - **Patch** (`x.y.Z`): Bug fixes, refactoring, small security patches, internal improvements.
   - **Minor** (`x.Y.0`): New MCP abilities/tools, new classes, backwards-compatible feature additions.
   - **Major** (`X.0.0`): Breaking architectural shifts, protocol changes, dependency overhauls.
2. **Synchronize All 4 Version Locations**:
   - `mindcrafts-ai.php` → Plugin header `Version: X.Y.Z`
   - `mindcrafts-ai.php` → Constant `define( 'MINDCRAFTS_AI_VERSION', 'X.Y.Z' );`
   - `readme.txt` → `Stable tag: X.Y.Z` and new entry under `== Changelog ==`
   - `CHANGELOG.md` → New section `## [X.Y.Z] - YYYY-MM-DD` detailing Added/Changed/Fixed items
3. **Verification**: Verify that the version matches identically across all 4 files before concluding any task.

## WordPress Plugin Conventions

This project follows the patterns defined in `.Codex/skills/wp-plugin-dev/`:

- **Bootstrap file is minimal** — `mindcrafts-ai.php` contains only plugin header, constants, dependency checks, `require_once` statements, and singleton init. No feature logic.
- **WordPress naming**: `snake_case` for functions/variables, `Upper_Snake_Case` for classes, `UPPER_SNAKE` for constants
- **Prefix everything**: All functions, classes, hooks, options use the plugin prefix
- **All strings translatable** using `__()`, `_e()`, `esc_html__()`, etc.
- **Security is non-negotiable**: Sanitize all input (`sanitize_text_field`, `absint`, etc.), escape all output (`esc_html`, `esc_attr`, `esc_url`), use `$wpdb->prepare()` for SQL, verify nonces on forms/AJAX, check capabilities before privileged operations
- **Enqueue assets properly** via `wp_enqueue_script()`/`wp_enqueue_style()`, never hardcode tags
- **GPL-2.0-or-later** license required

## Codex Skills Available

Two custom skills are configured in `.Codex/skills/`:

- **wp-plugin-dev** — Scaffolding and building WordPress plugins following WP coding standards. Has reference docs for architecture patterns, security functions, and WP.org guidelines.
- **wp-plugin-review** — Automated + manual plugin review (PHPCS/WPCS, PHPStan, PHPUnit, security audit, accessibility). Produces a structured Markdown report.
