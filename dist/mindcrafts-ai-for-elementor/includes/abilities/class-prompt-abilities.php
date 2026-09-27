<?php
/**
 * MCP Prompts for MindCrafts AI.
 *
 * MCP Prompts are registered system messages that guide AI agents on
 * how to correctly use the MindCrafts AI tools. They provide workflow
 * guidance, best practices, and structured instructions.
 *
 * T2-3: Written comprehensive prompts with step-by-step instructions,
 * prerequisites, and common mistakes to avoid.
 *
 * @package MindCrafts_AI
 * @since   2.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * MCP Prompt definitions for MindCrafts AI.
 *
 * @since 2.0.0
 */
class MindCrafts_AI_Prompt_Abilities {

	/**
	 * Returns all prompt definitions for the MCP server.
	 *
	 * These are registered with the MCP Adapter (not the Abilities API)
	 * because prompts are an MCP-level concept, not a WordPress ability.
	 *
	 * @since 2.0.0
	 *
	 * @return array Array of MCP prompt definition arrays.
	 */
	public function get_prompts(): array {
		return array(
			$this->get_build_page_prompt(),
			$this->get_edit_page_prompt(),
			$this->get_design_system_prompt(),
			$this->get_troubleshooting_prompt(),
		);
	}

	/**
	 * Prompt: Build a page from a description.
	 *
	 * @return array
	 */
	private function get_build_page_prompt(): array {
		return array(
			'name'        => 'build-elementor-page',
			'description' => __( 'Guides the AI to create a complete, professional Elementor page from a topic or design brief. Includes step-by-step workflow, tool usage instructions, and design best practices.', 'mindcrafts-ai' ),
			'arguments'   => array(
				array(
					'name'        => 'topic',
					'description' => __( 'The topic, business type, or design brief for the page (e.g. "a digital marketing agency homepage").', 'mindcrafts-ai' ),
					'required'    => true,
				),
				array(
					'name'        => 'style',
					'description' => __( 'Optional: Preferred design style (e.g. "dark modern", "minimal", "corporate", "vibrant startup").', 'mindcrafts-ai' ),
					'required'    => false,
				),
			),
			'messages'    => array(
				array(
					'role'    => 'user',
					'content' => array(
						'type' => 'text',
						'text' => <<<PROMPT
You are a professional Elementor web designer and WordPress expert. Your task is to create a complete, high-quality Elementor page for: {topic}
Design style: {style}

## YOUR EXACT WORKFLOW (follow this step by step):

### STEP 1: Discovery (MANDATORY — do this before building)
Call `mindcrafts-ai-get-global-settings` to understand the site's existing color palette and typography. This ensures your design matches the brand.

### STEP 2: Plan Your Structure
Design the page sections BEFORE calling build-page. A professional page should have:
- **Hero Section**: Full-width container with headline, subheadline, and CTA button
- **Features/Services**: 3-column grid of icon-box widgets
- **Social Proof**: Testimonials or stats row
- **CTA Section**: Call-to-action with contrast background
- **Footer CTA**: Final conversion opportunity

### STEP 3: Use `mindcrafts-ai-build-page`
- Set `status` to "draft" (never publish immediately)
- Use "container" type for layout sections, "widget" type for content
- Use "children" (NOT "elements") for nested items inside containers
- Each container MUST have `elType: "container"` in settings

### STEP 4: Widget Settings Reference
Common widgets you MUST use correctly:
- **heading**: `{ "title": "Your Heading", "header_size": "h1", "align": "center" }`
- **text-editor**: `{ "editor": "<p>Your paragraph text here</p>" }`
- **button**: `{ "text": "Get Started", "link": { "url": "#" }, "button_type": "info" }`
- **image**: `{ "image": { "url": "https://picsum.photos/1200/600" } }`
- **icon-box**: `{ "title_text": "Feature Name", "description_text": "Description", "selected_icon": { "library": "fa-solid", "value": "fas fa-star" } }`
- **spacer**: `{ "space": { "size": "60", "unit": "px" } }`
- **divider**: `{ "style": "solid", "weight": "1" }`

### STEP 5: Container Styling
For hero sections, add background colors:
```json
"settings": {
  "background_background": "classic",
  "background_color": "#1e1e2e",
  "padding": { "unit": "px", "top": "100", "bottom": "100", "left": "60", "right": "60", "isLinked": false }
}
```

### STEP 6: Verify
After building, use `mindcrafts-ai-get-page-structure` to confirm the page was created correctly.

## IMPORTANT RULES:
- NEVER use "elements" as a key — always use "children" for nested items
- Each widget needs "widget_type" (not "type": "widget_type")
- Always include realistic content, never use placeholder text like "Lorem ipsum"
- Minimum 5 containers/sections per professional page
- Use Elementor's `flexbox` layout for all containers
PROMPT
					),
				),
			),
		);
	}

	/**
	 * Prompt: Edit an existing Elementor page.
	 *
	 * @return array
	 */
	private function get_edit_page_prompt(): array {
		return array(
			'name'        => 'edit-elementor-page',
			'description' => __( 'Guides the AI to find and edit an existing Elementor page — updating content, styles, or layout safely.', 'mindcrafts-ai' ),
			'arguments'   => array(
				array(
					'name'        => 'instruction',
					'description' => __( 'What to change on the page (e.g. "Update the hero headline to say Welcome to CodeHub, change the button color to blue").', 'mindcrafts-ai' ),
					'required'    => true,
				),
				array(
					'name'        => 'page_identifier',
					'description' => __( 'Page title or ID to edit. If not provided, AI will list pages first.', 'mindcrafts-ai' ),
					'required'    => false,
				),
			),
			'messages'    => array(
				array(
					'role'    => 'user',
					'content' => array(
						'type' => 'text',
						'text' => <<<PROMPT
You are a professional Elementor developer. Your task is to edit an existing Elementor page.

Instruction: {instruction}
Page: {page_identifier}

## WORKFLOW:

### STEP 1: Find the Page
If you have a page ID, skip to Step 2.
Otherwise, call `mindcrafts-ai-list-pages` to find the page by title.

### STEP 2: Inspect the Structure
Call `mindcrafts-ai-get-page-structure` with the post_id to see the current element tree.
Note the element IDs of elements you need to modify.

### STEP 3: Make Changes
For text/setting changes on existing widgets:
- Use `mindcrafts-ai-update-widget` with the element_id and new settings

For adding new elements:
- Use `mindcrafts-ai-add-container` or `mindcrafts-ai-add-widget`

For moving elements:
- Use `mindcrafts-ai-move-element`

For removing elements:
- Use `mindcrafts-ai-remove-element` (CAUTION: irreversible)

### STEP 4: Verify Changes
Call `mindcrafts-ai-get-element-settings` on modified elements to confirm changes saved correctly.

## IMPORTANT RULES:
- Always inspect the current structure BEFORE making changes
- Never remove elements without being explicitly told to
- Use update-widget for style changes, not add-widget + remove-widget
PROMPT
					),
				),
			),
		);
	}

	/**
	 * Prompt: Apply a design system to a page.
	 *
	 * @return array
	 */
	private function get_design_system_prompt(): array {
		return array(
			'name'        => 'apply-design-system',
			'description' => __( 'Guides the AI to update site-wide global colors and typography to match a design system or brand guidelines.', 'mindcrafts-ai' ),
			'arguments'   => array(
				array(
					'name'        => 'brand_description',
					'description' => __( 'Describe the brand or provide hex colors and font preferences (e.g. "Tech startup: primary #5046e4, accent #06d6a0, use Inter font family").', 'mindcrafts-ai' ),
					'required'    => true,
				),
			),
			'messages'    => array(
				array(
					'role'    => 'user',
					'content' => array(
						'type' => 'text',
						'text' => <<<PROMPT
You are a brand designer and Elementor expert. Apply a design system to this WordPress site.

Brand/Design: {brand_description}

## WORKFLOW:

### STEP 1: Read Current Settings
Call `mindcrafts-ai-get-global-settings` to see existing colors and typography IDs.

### STEP 2: Update Global Colors
Call `mindcrafts-ai-update-global-colors` with a colors array. Format:
```json
{
  "colors": [
    { "id": "primary", "title": "Primary", "color": "#5046e4" },
    { "id": "secondary", "title": "Secondary", "color": "#06d6a0" },
    { "id": "text", "title": "Text", "color": "#1f2937" },
    { "id": "accent", "title": "Accent", "color": "#f59e0b" }
  ]
}
```

### STEP 3: Update Typography
Call `mindcrafts-ai-update-global-typography` with typography settings.

### STEP 4: Confirm
Call `mindcrafts-ai-get-global-settings` again to confirm changes applied.

## RULES:
- Never guess hex colors — if not provided, derive sensible ones from the brand description
- Keep 4-5 colors maximum for a clean design system
- Primary color should be the main brand color
- Text color should be dark (for light backgrounds) or light (for dark themes)
PROMPT
					),
				),
			),
		);
	}

	/**
	 * Prompt: Troubleshoot MindCrafts AI issues.
	 *
	 * @return array
	 */
	private function get_troubleshooting_prompt(): array {
		return array(
			'name'        => 'troubleshoot-mcp',
			'description' => __( 'Helps diagnose and fix common issues with MindCrafts AI MCP tools — such as pages not appearing in the Elementor editor, empty pages, or tool errors.', 'mindcrafts-ai' ),
			'arguments'   => array(
				array(
					'name'        => 'issue',
					'description' => __( 'Describe the problem you are experiencing.', 'mindcrafts-ai' ),
					'required'    => true,
				),
				array(
					'name'        => 'post_id',
					'description' => __( 'Post ID of the affected page, if known.', 'mindcrafts-ai' ),
					'required'    => false,
				),
			),
			'messages'    => array(
				array(
					'role'    => 'user',
					'content' => array(
						'type' => 'text',
						'text' => <<<PROMPT
You are a WordPress and Elementor debugging expert. Help diagnose this issue: {issue}
Affected page ID: {post_id}

## DIAGNOSTIC STEPS:

### If page is empty in Elementor editor:
1. Call `mindcrafts-ai-debug-meta` with the post_id
2. Check if `_elementor_data` exists and contains valid JSON
3. Check if `_elementor_edit_mode` equals "builder"
4. Check if `_elementor_template_type` is set (should be "wp-page" or "wp-post")
5. If data exists but editor is empty: CSS cache issue — the page needs to be deactivated/reactivated in Elementor

### If build-page returns an error:
- "Widget type not found": Call `mindcrafts-ai-list-widgets` to get the exact widget_type name
- "missing_structure": Your structure array is empty or malformed
- "invalid_element_type": Each item must have type "container" or "widget"

### If Elementor editor shows "Preview could not be loaded":
This is usually caused by a plugin conflict. Steps:
1. Check if page works in safe mode (add ?elementor-preview=safe to the URL)
2. If it works in safe mode, a plugin is causing the conflict
3. Common culprits: caching plugins (LiteSpeed Cache, W3 Total Cache), security plugins

## REPORTING:
After each diagnostic call, summarize what you found and what the next step should be.
PROMPT
					),
				),
			),
		);
	}
}
