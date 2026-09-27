# Project: codehubb.com Transformation & Elementor Native Migration

## Architecture
- **Environment**: WordPress 6.8+ on `https://codehubb.com`, Elementor 3.20+ with Flexbox Container support.
- **MCP Integration**: MindCrafts AI Elementor MCP Server (`mindcrafts-ai-server`) exposing native container, widget, and page operations via `wp-json/mcp/mindcrafts-ai-server`.
- **Target Pages**:
  - Home Page: Live Post ID `74` (`https://codehubb.com/`), secondary template draft Post ID `28`. (Discrepancy resolved: Post ID 8 was an image attachment).
  - Services Page: Live Post ID `36` (`https://codehubb.com/services/`). (Discrepancy resolved: Post ID 11 returns 404).
- **Design System Tokens**:
  - Canvas Background: `#131313`
  - Card / Section Surface: `#1E293B`
  - Primary Brand Accent: `#4F46E5` (Indigo)
  - Secondary Brand Accent: `#06B6D4` (Cyan)
  - Success / Green: `#10B981` (Emerald)
  - Headings Font: `'Plus Jakarta Sans'`, weight `700`, color `#FFFFFF`
  - Body Font: `'Inter'`, weight `400`, color `#94A3B8` / `#E2E8F0`, line-height `1.75em`
  - Card Styling: `1px solid rgba(255, 255, 255, 0.08)`, border-radius `16px`, glassmorphic backdrop-filter.
  - Buttons: Border-radius `8px`, gradient/accent background, distinct hover states.

## Feature Inventory
| # | Feature | Description | Milestone | Source |
|---|---------|-------------|-----------|--------|
| 1 | Tooling Fix (Query Abilities) | Fix `find_element` -> `find_element_by_id` bug at `class-query-abilities.php:570` and increment version to 3.1.6 per version policy | M0 | Survey |
| 2 | Services Page Blob Removal | Remove monolithic 107-line raw HTML widget `5dc91b2` from Post ID 36 | M1 | Survey |
| 3 | Services Page Native Layout | Reconstruct Post ID 36 using 6 native Elementor Flexbox Containers (Hero, Capabilities, Tech Stack, Process, FAQ, CTA) | M1 | Survey |
| 4 | Services Page Native Widgets | Implement native Heading, Text-Editor, Icon Box, Accordion, and Button widgets on Post ID 36 | M1 | Survey |
| 5 | Home Page Theme Widget Migration | Replace 8 proprietary `wpo-elito_*` widgets on Post ID 74 with 100% native Elementor Flexbox Containers | M2 | Survey |
| 6 | Home Page Raw HTML Blob Removal | Eliminate raw HTML widget `36d8f13` (WhatsApp inline CSS) on Post ID 74 and replace with clean native implementation | M2 | Survey |
| 7 | Home Page Content Rebuilding | Reconstruct Hero, Fun Facts, Services, Work, Projects, Testimonials, Pricing, and CTA with native widgets | M2 | Survey |
| 8 | Asset Migration (Local Sideloading) | Sideload 16 hotlinked assets from `wpolive.com` into WordPress Media Library and bind to native Image widgets | M2 | Survey |
| 9 | HTML5 Heading Hierarchy (H1/H2/H3) | Enforce exactly one semantic `<h1>` per page on both Home and Services, with logically nested `<h2>`/`<h3>` | M3 | R2 |
| 10 | Image Alt Attribute Accessibility | Add meaningful, descriptive `alt` text to 100% of images on Home and Services (replacing empty or whitespace alt) | M3 | R2 |
| 11 | Responsive Perfection & Breakpoints | Ensure pixel-perfect layout across Mobile (375px-480px), Tablet (768px-1024px), Desktop (1200px+), zero horizontal scroll | M3 | R3 |
| 12 | Dark Glassmorphic Aesthetic Polish | Apply glassmorphism borders, gradients, badge indicators, and button hover/focus states matching agency benchmarks | M3 | R3 |
| 13 | Link Integrity & Dead Link Removal | Verify all buttons, header/footer links, and CTAs have valid destinations (zero dead/404/empty links) | M4 | R4 |
| 14 | Copy Cleanliness & Placeholder Removal | Eliminate all placeholder text ("Title Text", "Sub Title Text", Lorem Ipsum, fake phone numbers) | M4 | R4 |
| 15 | Adversarial Stress-Testing & Integrity Audit | Dual Reviewer and Dual Challenger verification, followed by Forensic Auditor integrity veto gate | M4 | R4 |
| 16 | Portfolio Expansion Blueprint | Document reusable design patterns and step-by-step migration blueprint for Projects (38), About (32), Pricing (44), Contact (34) | M5 | R5 |

## Milestones
| # | Name | Scope | Dependencies | Status |
|---|------|-------|-------------|--------|
| M0 | Tooling Fix | Fix `class-query-abilities.php:570` and bump version to 3.1.6 across 4 files | none | DONE |
| M1 | Services Page Native Reconstruction | Native Flexbox Containers and core widgets for Post ID 36 | M0 | IN_PROGRESS |
| M2 | Home Page Native Reconstruction | Native Flexbox Containers, widget replacement, and asset sideloading for Post ID 74 | M0, M1 | PLANNED |
| M3 | SEO & Responsive Perfection | Single H1, nested headings, alt tags, mobile responsive scaling, glassmorphic styling | M1, M2 | PLANNED |
| M4 | Zero-Defect QA & Adversarial Audit | Link verification, copy audit, challenger stress testing, forensic audit gate | M1, M2, M3 | PLANNED |
| M5 | Portfolio Expansion Blueprint | Blueprint for remaining pages (38, 32, 44, 34) based on verified design system | M4 | PLANNED |

## Interface Contracts
### MindCrafts AI MCP Tools
- `mindcrafts-ai-build-page`:
  - Input: `post_id: int`, `title: string` (optional), `elements: array`
  - Output: `{"success": true, "post_id": int, "elements_count": int}`
- `mindcrafts-ai-get-page-structure`:
  - Input: `post_id: int`
  - Output: `{"post_id": int, "title": string, "structure": array}`
- `mindcrafts-ai-export-page`:
  - Input: `post_id: int`
  - Output: Complete Elementor data array
- `mindcrafts-ai-delete-page-content`:
  - Input: `post_id: int`
  - Output: `{"success": true, "message": string}`
- `mindcrafts-ai-update-page-settings`:
  - Input: `post_id: int`, `settings: object` (e.g. `{"hide_title": "yes"}`)
  - Output: `{"success": true, "settings": object}`
- `mindcrafts-ai-sideload-image`:
  - Input: `url: string`, `title: string` (optional)
  - Output: `{"attachment_id": int, "url": string}`

## Code Layout
- MCP Server Codebase:
  - `mindcrafts-ai.php` (Plugin header, version constant)
  - `readme.txt` (Stable tag, changelog)
  - `CHANGELOG.md` (Changelog entries)
  - `includes/abilities/class-query-abilities.php` (Query abilities including `get-element-settings`)
  - `includes/class-elementor-data.php` (Data layer and element traversal)
  - `includes/class-element-factory.php` (Elementor JSON tree construction)
- Teamwork Coordination:
  - `.agents/teamwork/orchestrator_1/` (Project state, progress, gating)
  - Subagent isolated directories under `.agents/teamwork/<subagent_name>/`
