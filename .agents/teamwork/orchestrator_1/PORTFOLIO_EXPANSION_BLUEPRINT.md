# Portfolio Expansion Blueprint: codehubb.com Phased Transformation (R5)

## 1. Executive Summary
This blueprint establishes the authoritative design system, native Elementor component library, SEO architecture, and execution procedures for the systematic expansion of the native Elementor transformation from Phase 1 (Home & Services) to the remaining portfolio pages of `codehubb.com`:
- **About Page** (Post ID: 32, `/about/`)
- **Projects Page** (Post ID: 38, `/projects/`)
- **Pricing Page** (Post ID: 44, `/pricing/`)
- **Contact Page** (Post ID: 34, `/contact/`)
- **Testimonials Page** (Post ID: 42, `/testimonials/`)

---

## 2. Universal Design System & Token Specifications

### 2.1 Color Palette
- **Canvas Base Background**: `#131313` (Solid Dark Basalt)
- **Primary Card / Section Surface**: `#1E293B` (Deep Slate)
- **Secondary Card / Tech Container**: `#161F30` (Navy Dark Surface)
- **Glassmorphic Card Fill**: `rgba(30, 41, 59, 0.7)` with `backdrop-filter: blur(16px)`
- **Subtle Card Borders**: `1px solid rgba(255, 255, 255, 0.08)`
- **Hover Card Borders**: `1px solid rgba(79, 70, 229, 0.4)`
- **Brand Primary Accent**: `#4F46E5` (Vibrant Indigo)
- **Brand Secondary Accent**: `#06B6D4` (High-Tech Cyan)
- **Interactive / Green Accent**: `#10B981` (Emerald / WhatsApp)
- **Warning / Star Rating Accent**: `#F59E0B` (Amber Gold)
- **Headings Color**: `#FFFFFF` (Pure White)
- **Body Text Color**: `#94A3B8` / `#E2E8F0` (Muted Slate / Light Silver)

### 2.2 Typography Scale
- **Headings Font Family**: `'Plus Jakarta Sans'`, sans-serif
  - `H1 (Page Hero)`: 52px–56px (Desktop), 38px–42px (Tablet), 32px–34px (Mobile), Weight: 700, Line Height: 1.15em, Letter Spacing: -0.02em
  - `H2 (Section Heading)`: 36px–40px (Desktop), 30px–32px (Tablet), 26px–28px (Mobile), Weight: 700, Line Height: 1.25em
  - `H3 (Card Title / Sub-Feature)`: 20px–22px, Weight: 600, Line Height: 1.35em
  - `H4 (Step / Micro-Header)`: 16px–18px, Weight: 600, Line Height: 1.4em
- **Body Font Family**: `'Inter'`, sans-serif
  - `Body Standard`: 16px, Weight: 400, Line Height: 1.75em, Color: `#94A3B8`
  - `Body Large (Hero Subtitle)`: 18px, Weight: 400, Line Height: 1.8em, Color: `#E2E8F0`
  - `Eyebrow Badge`: 13px, Weight: 600, Text Transform: uppercase, Letter Spacing: 1px, Color: `#818CF8`

---

## 3. Page-Specific Native Reconstruction Blueprints

### 3.1 About Page (Post ID: 32, `/about/`)
- **Page Settings**: `hide_title: "yes"`, `template: "elementor_header_footer"`
- **Section 1: Hero**
  - Eyebrow Badge: "ABOUT CODEHUBB"
  - Single `<h1>`: "Engineering Digital Sovereignty & Autonomous Intelligence"
  - Lead Text: "Founded to bridge enterprise architecture with state-of-the-art AI systems, delivering fault-tolerant software backed by uncompromising engineering standards."
- **Section 2: Mission & Core Values (4 Glassmorphic Cards)**
  - Card 1: Zero-Defect Philosophy (Icon: `fas fa-award`, Heading: H3)
  - Card 2: Full-Stack Ownership (Icon: `fas fa-layer-group`, Heading: H3)
  - Card 3: Model Context Protocol Pioneers (Icon: `fas fa-microchip`, Heading: H3)
  - Card 4: Contractual SLA Guarantees (Icon: `fas fa-file-contract`, Heading: H3)
- **Section 3: Leadership & Engineering Standards**
  - Container with bio, credentials, architecture leadership, and verified metrics.
- **Section 4: Call to Action Banner**
  - Heading: H2, description, and button to `/contact/`.

### 3.2 Projects Showcase Page (Post ID: 38, `/projects/`)
- **Page Settings**: `hide_title: "yes"`, `template: "elementor_header_footer"`
- **Section 1: Hero**
  - Eyebrow Badge: "PROVEN ARCHITECTURES"
  - Single `<h1>`: "Enterprise Systems, High-Scale Apps & AI Deployments"
  - Lead Text: "Explore case studies demonstrating 100/100 Lighthouse performance, sub-second API latencies, and production multi-agent automation systems."
- **Section 2: Filterable / Categorized Project Grid (6 Native Cards)**
  - Card 1: Enterprise Headless Commerce Engine (Next.js 15, GraphQL, Stripe)
  - Card 2: Cross-Platform Logistics Suite (Flutter, Laravel 11, Redis)
  - Card 3: Autonomous Customer Intelligence Agent (MCP, Python, Vector DB)
  - Card 4: Cloud-Native Fintech Core (Kubernetes, PostgreSQL, Go)
  - Card 5: Real-Time Fleet Telemetry Platform (Node.js, WebSockets, TimescaleDB)
  - Card 6: AI-Powered Clinical Workflow Automation (FastAPI, HIPAA Cloud, RAG)
  - Each card includes: Local media screenshot with descriptive `alt`, category tag, title (H3), technical summary, and "View Architecture" CTA button.

### 3.3 Pricing Page (Post ID: 44, `/pricing/`)
- **Page Settings**: `hide_title: "yes"`, `template: "elementor_header_footer"`
- **Section 1: Hero**
  - Eyebrow Badge: "TRANSPARENT VALUE"
  - Single `<h1>`: "Predictable Engineering Engagements & SLAs"
  - Lead Text: "Transparent, milestone-driven investment models with zero hidden retainers and guaranteed delivery timelines."
- **Section 2: 3-Tier Native Pricing Architecture (Zero Raw HTML)**
  - Tier 1: Starter Architecture Sprint ($4,900)
    - Target: High-growth startups needing technical blueprints, MVP builds, or audit.
    - Native feature list with green checkmarks.
    - CTA Button: "Book Architecture Sprint" -> `/contact/`
  - Tier 2: Dedicated Product Engineering ($9,800/mo) - Highlighted Glassmorphic Card
    - Target: Scaling enterprises requiring dedicated principal engineers.
    - Native feature list + 99.8% SLA badge.
    - CTA Button: "Start Engineering Engagement" -> `/contact/`
  - Tier 3: Enterprise Sovereign ($19,500+)
    - Target: Custom multi-region cloud infrastructure, enterprise MCP automation pipelines.
    - CTA Button: "Consult Principal Architect" -> `/contact/`
- **Section 3: FAQ & SLA Guarantee Accordion**
  - Native `accordion` widget covering billing cycles, IP ownership, and SLA enforcement.

### 3.4 Contact Page (Post ID: 34, `/contact/`)
- **Page Settings**: `hide_title: "yes"`, `template: "elementor_header_footer"`
- **Section 1: Hero**
  - Eyebrow Badge: "INITIATE ENGAGEMENT"
  - Single `<h1>`: "Direct Access to Principal Web & AI Architects"
  - Lead Text: "No account managers or sales runarounds. Connect directly with senior systems architects to evaluate your technical requirements."
- **Section 2: Two-Column Flex Container**
  - Column A (Contact Information & Office Hours):
    - Direct Email, Enterprise Support Hours (24/7 SLA), WhatsApp Quick Access.
    - Verified international format phone/WhatsApp link.
  - Column B (Engagement Intake Form):
    - Name, Corporate Email, Project Scope, Budget Tier, Architecture Requirements.
    - Zero dead buttons; submit action routes to confirmed enterprise endpoint.

---

## 4. MCP Automation Pipeline for Rollout

To execute the transformation across the remaining pages programmatically:

1. **Extraction**:
   Call `mindcrafts-ai-export-page(post_id: target_id)` to extract existing copy and metadata.
2. **Schema Formulation**:
   Map sections into the standardized JSON tree schema established in `home_page_payload.json` and `services_page_payload.json`.
3. **Application**:
   Call `mindcrafts-ai-build-page` or `mindcrafts-ai-import-template` with `post_id` and the generated native tree.
4. **Header Suppression**:
   Call `mindcrafts-ai-update-page-settings(post_id: target_id, settings: {"hide_title": "yes"})`.
5. **Quality Verification**:
   - Verify `mindcrafts-ai-get-page-structure(post_id: target_id)` returns 100% native containers and widgets.
   - Verify live page DOM via `read_url_content` confirms single `<h1>`, valid nested hierarchy, and zero 404 links.
