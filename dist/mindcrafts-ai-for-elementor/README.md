# MindCrafts AI for Elementor

A production-ready WordPress plugin that extends the [WordPress MCP Adapter](https://github.com/WordPress/mcp-adapter) to expose Elementor data, widgets, containers, and Theme Builder capabilities as [MCP (Model Context Protocol)](https://modelcontextprotocol.io/) tools. This enables AI agents (Claude, Cursor, Codex, etc.) to programmatically inspect, build, and maintain Elementor designs.

---

## 🌟 Key Features

- **50+ MCP Tools** covering the complete Elementor page and store building ecosystem.
- **Enterprise Theme Builder & Popups** — Programmatically create headers, footers, single/archive layouts, popups, and triggers.
- **Full WooCommerce Suite** — Generate complete Single Product templates, Shop Archive templates, and add Cart/Checkout/Account widgets.
- **Addon Schema Discovery** — Auto-discovers and extracts settings schemas from third-party plugins (ElementsKit, Essential Addons, UAE, JetPlugins, etc.).
- **Dynamic Breakpoints** — Target exact Elementor breakpoints (mobile, tablet, laptop, widescreen) seamlessly.
- **Smart Stock Images** — Integrated Openverse CC search, media sideloading, and widget insertion in a single prompt.
- **Built-in Security & Audit Logging** — Hardened `.htaccess` protection with automatic 5MB log rotation (last 3 rotated archives preserved).
- **Admin Diagnostics & Tool Management** — Enable/disable individual tools, monitor dependencies, test license status, and view live API logs.

---

## 📋 Requirements

| Dependency | Minimum Version | Notes |
|---|---|---|
| **WordPress** | `>= 6.8` | Tested up to 6.8+ |
| **PHP** | `>= 7.4` | PHP 7.4 - 8.3 compatible |
| **Elementor Core** | `>= 3.20` | Flexbox Container experiments recommended/active |
| **Elementor Pro** | `>= 3.20` | Optional (Required for Theme Builder, Popups, and Pro WooCommerce tools) |
| **WordPress MCP Adapter** | Built-in | **Bundled** inside plugin — no separate installation needed! |
| **WordPress Abilities API** | Built-in | **Bundled** fallback inside plugin — zero setup required! |

---

## 🚀 Setup Wizard & Installation

1. **Install Plugin:**
   - Clone or copy this repository into `/wp-content/plugins/mindcrafts-ai-elementor/`.
   - Run `composer install` (if managing dependencies locally).
2. **Activate:**
   - Activate **MindCrafts AI for Elementor** in WordPress Admin > Plugins.
3. **Run Setup Checks:**
   - Navigate to **MindCrafts AI > Setup**.
   - If Elementor or the WordPress MCP Adapter are missing, use the one-click installer buttons.
4. **Configure MCP Client:**
   - Generate a WordPress Application Password at **Users > Profile > Application Passwords**.
   - Add the server config into your AI client (see configurations below).
5. **Verify Connection:**
   - Ask your AI agent to run `mindcrafts-ai/list-widgets` or check the **MindCrafts AI > Diagnostics** tab.

---

## 🔌 Connecting to the MCP Server

### Option A: WP-CLI stdio (Recommended for Local Dev)

Direct stdio pipe without network overhead or session management.

**Claude Desktop** (`claude_desktop_config.json`):
```json
{
  "mcpServers": {
    "mindcrafts-ai": {
      "command": "wp",
      "args": [
        "mcp-adapter", "serve",
        "--server=mindcrafts-ai-server",
        "--user=admin",
        "--path=/path/to/wordpress"
      ]
    }
  }
}
```

**Claude Code / Codex** (`.mcp.json` in project root):
```json
{
  "mcpServers": {
    "mindcrafts-ai": {
      "type": "stdio",
      "command": "wp",
      "args": [
        "mcp-adapter", "serve",
        "--server=mindcrafts-ai-server",
        "--user=admin",
        "--path=/path/to/wordpress"
      ]
    }
  }
}
```

### Option B: Remote Proxy (Remote Sites & Staging)

Uses the official Automattic remote bridge:

```json
{
  "mcpServers": {
    "mindcrafts-ai-server": {
      "command": "npx.cmd",
      "args": ["-y", "@automattic/mcp-wordpress-remote@latest"],
      "env": {
        "WP_API_URL": "https://your-site.com/wp-json/mcp/mindcrafts-ai-server",
        "WP_API_USERNAME": "admin",
        "WP_API_PASSWORD": "xxxx xxxx xxxx xxxx xxxx xxxx",
        "OAUTH_ENABLED": "false"
      }
    }
  }
}
```

---

## 🛠️ Complete MCP Tools Reference (50+ Tools)

All abilities are registered under the `mindcrafts-ai/` namespace.

### 1. Query & Discovery (7 Tools)
| Ability Name | Description |
|---|---|
| `mindcrafts-ai/list-widgets` | Lists all registered widget types with names, titles, categories, and icons. |
| `mindcrafts-ai/get-widget-schema` | Generates complete JSON Schema for widget settings from Elementor controls. |
| `mindcrafts-ai/get-page-structure` | Returns the hierarchical element tree (containers & widgets) for a page. |
| `mindcrafts-ai/get-element-settings` | Returns active settings for a specific element ID. |
| `mindcrafts-ai/list-pages` | Lists all Elementor-enabled pages and posts. |
| `mindcrafts-ai/list-templates` | Lists saved Elementor user templates. |
| `mindcrafts-ai/get-global-settings` | Returns active design kit settings (colors, typography, spacing). |

### 2. Page Management (5 Tools)
| Ability Name | Description |
|---|---|
| `mindcrafts-ai/create-page` | Creates a new WordPress page/post with Elementor enabled. |
| `mindcrafts-ai/update-page-settings` | Updates page-level settings (layout canvas, background, padding). |
| `mindcrafts-ai/delete-page-content` | Destructive: Clears all Elementor elements from a post. |
| `mindcrafts-ai/import-template` | Imports an Elementor template JSON structure into a post. |
| `mindcrafts-ai/export-page` | Exports a post's full Elementor data as portable JSON. |

### 3. Layout & Containers (4 Tools)
| Ability Name | Description |
|---|---|
| `mindcrafts-ai/add-container` | Adds a flexbox container (top-level or nested). |
| `mindcrafts-ai/move-element` | Relocates an element to a new parent or index position. |
| `mindcrafts-ai/remove-element` | Destructive: Removes an element and all child elements. |
| `mindcrafts-ai/duplicate-element` | Duplicates an element tree with freshly generated IDs. |

### 4. Core & Pro Widgets (17 Tools)
| Ability Name | Description |
|---|---|
| `mindcrafts-ai/add-widget` | Universal: Adds any widget type with custom settings to a container. |
| `mindcrafts-ai/update-widget` | Universal: Updates settings on any existing widget instance. |
| `mindcrafts-ai/add-heading` | Convenience: Heading widget. |
| `mindcrafts-ai/add-text-editor` | Convenience: Rich text editor widget. |
| `mindcrafts-ai/add-image` | Convenience: Image widget. |
| `mindcrafts-ai/add-button` | Convenience: Call-to-action button widget. |
| `mindcrafts-ai/add-video` | Convenience: Video player widget. |
| `mindcrafts-ai/add-icon` | Convenience: FontAwesome/SVG icon widget. |
| `mindcrafts-ai/add-spacer` | Convenience: Spacer widget. |
| `mindcrafts-ai/add-divider` | Convenience: Line divider widget. |
| `mindcrafts-ai/add-icon-box` | Convenience: Icon box widget. |
| `mindcrafts-ai/add-form` | Pro: Form builder with actions and fields. |
| `mindcrafts-ai/add-posts-grid` | Pro: Dynamic posts grid widget. |
| `mindcrafts-ai/add-countdown` | Pro: Countdown timer widget. |
| `mindcrafts-ai/add-price-table` | Pro: Pricing table widget. |
| `mindcrafts-ai/add-flip-box` | Pro: 3D interactive flip box. |
| `mindcrafts-ai/add-animated-headline` | Pro: Rotating and highlighted animated headlines. |

### 5. Theme Builder (4 Tools — Elementor Pro)
| Ability Name | Description |
|---|---|
| `mindcrafts-ai/list-theme-templates` | Lists headers, footers, singles, archives, search, and 404 templates. |
| `mindcrafts-ai/create-theme-template` | Creates a new Theme Builder template with initial layout. |
| `mindcrafts-ai/set-template-conditions` | Configures display conditions (e.g. Entire Site, Singular, Archives). |
| `mindcrafts-ai/apply-theme-location` | Binds a template to header or footer site locations. |

### 6. Popup Builder (3 Tools — Elementor Pro)
| Ability Name | Description |
|---|---|
| `mindcrafts-ai/create-popup` | Creates an Elementor popup with layout and trigger rules. |
| `mindcrafts-ai/list-popups` | Lists all existing popup templates. |
| `mindcrafts-ai/set-popup-trigger` | Configures triggers (`page_load`, `scroll`, `click`, `exit_intent`). |

### 7. Dynamic Tags (2 Tools)
| Ability Name | Description |
|---|---|
| `mindcrafts-ai/list-dynamic-tags` | Lists registered dynamic tags (post title, author, ACF, WooCommerce). |
| `mindcrafts-ai/apply-dynamic-tag` | Binds an Elementor dynamic tag to any widget setting. |

### 8. WooCommerce Builder (8 Tools)
| Ability Name | Description |
|---|---|
| `mindcrafts-ai/add-woocommerce-products` | Adds product grid with column, row, and ordering controls. |
| `mindcrafts-ai/add-woocommerce-categories` | Adds product categories grid widget. |
| `mindcrafts-ai/add-woo-cart` | Adds WooCommerce Cart widget. |
| `mindcrafts-ai/add-woo-checkout` | Adds WooCommerce Checkout widget. |
| `mindcrafts-ai/add-woo-my-account` | Adds WooCommerce My Account widget. |
| `mindcrafts-ai/add-woo-product-rating` | Adds WooCommerce Product Rating widget. |
| `mindcrafts-ai/create-woo-single-product-template` | Pro: Builds complete single product template with images, price, meta, cart. |
| `mindcrafts-ai/create-woo-archive-template` | Pro: Builds complete product archive / shop template. |

### 9. Third-Party Addons (2 Tools)
| Ability Name | Description |
|---|---|
| `mindcrafts-ai/list-addon-widgets` | Discovers third-party widgets (ElementsKit, Essential Addons, UAE, etc.) and returns full JSON schema. |
| `mindcrafts-ai/add-third-party-widget` | Adds any third-party addon widget directly to a container. |

### 10. Responsive Controls (1 Tool)
| Ability Name | Description |
|---|---|
| `mindcrafts-ai/update-responsive-settings` | Updates element settings across dynamic Elementor breakpoints (`mobile`, `tablet`, `laptop`, `widescreen`). |

### 11. Composite & High-Level Generation (4 Tools)
| Ability Name | Description |
|---|---|
| `mindcrafts-ai/build-page` | Declaratively creates a complete page with full container tree and widgets in one call. |
| `mindcrafts-ai/analyze-structure` | Analyzes nesting depth, layout health, and responsiveness. |
| `mindcrafts-ai/clone-page` | Duplicates an entire page with fresh element IDs. |
| `mindcrafts-ai/bulk-update-settings` | Batch updates multiple element settings in a single transaction. |

### 12. Stock Images & Creative Commons (3 Tools)
| Ability Name | Description |
|---|---|
| `mindcrafts-ai/search-images` | Queries Openverse API for Creative Commons images. |
| `mindcrafts-ai/sideload-image` | Downloads external image into the WordPress Media Library. |
| `mindcrafts-ai/add-stock-image` | Search + sideload + insert image widget in one action. |

### 13. Templates & Kit Globals (4 Tools)
| Ability Name | Description |
|---|---|
| `mindcrafts-ai/save-as-template` | Saves an element tree as a reusable Elementor template. |
| `mindcrafts-ai/apply-template` | Inserts a saved template into a page. |
| `mindcrafts-ai/update-global-colors` | Updates the site-wide color palette in Elementor kit. |
| `mindcrafts-ai/update-global-typography` | Updates site-wide typography styles in Elementor kit. |

---

## 🔒 Permission & Security Model

- **Read/Query Tools:** `edit_posts`
- **Page Creation & Edits:** `publish_pages`, `edit_pages`, or `edit_posts` + author verification
- **Theme Builder & Global Kit Tools:** `manage_options`
- **Destructive Tools (Delete):** `delete_posts` + ownership check
- **Media Sideloading:** `upload_files`
- **API Audit Logs:** Stored in `/wp-content/uploads/mindcrafts-ai/mcp_calls.log` protected by `.htaccess` (deny from all), sanitized (no response bodies logged), and rotated when exceeding 5MB (keeping last 3 rotated logs).

---

## ❓ Troubleshooting

| Issue | Cause | Fix |
|---|---|---|
| **"No MCP servers registered"** | Plugin inactive or missing core dependencies | Verify Elementor and MCP Adapter are active in **MindCrafts AI > Setup**. |
| **HTTP 401 Unauthorized** | Invalid credentials | Check your WordPress Application Password. User must have sufficient capabilities. |
| **Session Expired / Error** | Direct HTTP connection lost session | Use the stdio WP-CLI bridge (Option A) or `@automattic/mcp-wordpress-remote` (Option B). |
| **Theme Builder fails** | Elementor Pro missing | Theme Builder and Popup tools require an active Elementor Pro license. |
| **WP-CLI not found on Windows** | PATH variable missing `php.exe` or `wp.bat` | Provide full paths to `php.exe` and `wp-cli.phar` in your client config. |

---

## 📄 License

MindCrafts AI for Elementor is released under the **GPL-2.0-or-later** license.
