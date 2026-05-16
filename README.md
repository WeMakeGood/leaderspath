# LeadersPath

AI-powered facilitated cohort learning platform for WordPress.

**Version:** 0.7.0
**Requires:** WordPress 6.4+, PHP 8.2+, ACF Pro
**Recommended:** Bricks Builder (or any builder/theme that runs `do_shortcode()`)
**Optional:** WooCommerce (for cohort enrollment)
**License:** GPLv2 or later

---

## Overview

LeadersPath is a WordPress plugin that powers **facilitated cohort learning** where a facilitator presents concepts and learners experiment with AI sandboxes (Activities) to experience specific AI behaviors firsthand. The plugin demonstrates the difference between raw LLM interactions and context-enhanced AI implementations.

### Nomenclature

| Term | CPT Slug | Description |
|------|----------|-------------|
| **Activity** | `leaderspath_activity` | AI sandbox experiment — what learners DO |
| **Lesson** | `leaderspath_lesson` | Atomic teaching unit (contains Activities) |
| **Course** | `leaderspath_course` | Reusable curriculum (contains Lessons) |
| **Context File** | `leaderspath_context` | Reference material embedded in AI system prompts |
| **Skill** | `leaderspath_skill` | Executable capability (Python/bash via Anthropic Skills API) |

Cohorts are **WooCommerce Simple products** with a cohort checkbox — not a separate CPT.

### Key Features

- **5 Custom Post Types** with ACF Pro field groups — page builders read fields directly
- **`[leaderspath_chatbot]` shortcode** for the interactive chat widget (the one surface the plugin renders)
- **Dual Chatbot Modes:** Activity sandbox (demonstrate behaviors) + Lesson Q&A (helpful assistant)
- **SSE Streaming** with real-time markdown rendering
- **Claude API Integration:** Container-based API with code execution and skills support
- **Web Tools:** Server-side `web_search` + `web_fetch` for skills that need web access
- **Automatic Retry** for transient API errors with user-visible status
- **REST Endpoints** for downloading context files and skill metadata
- **WooCommerce Cohorts:** Enrollment management, access control, cohort phases
- **No Conversation Persistence:** Page reload clears chat — enables experimentation

---

## Architecture

```
WordPress (any theme/builder)
├── LeadersPath Plugin
│   ├── Data Layer
│   │   ├── 5 CPTs (Activity, Lesson, Course, Context File, Skill)
│   │   ├── 3 Taxonomies (Topics, Context Categories, Skill Categories)
│   │   └── ACF Pro field groups
│   │
│   ├── Claude API
│   │   ├── Messages API + Container (code execution)
│   │   ├── Skills API (executable skill packages)
│   │   ├── Web tools (web_search + web_fetch for skills)
│   │   ├── SSE streaming (curl + WRITEFUNCTION callback)
│   │   └── Automatic retry (transient 5xx errors)
│   │
│   ├── REST API
│   │   ├── POST /chat          (synchronous JSON)
│   │   ├── POST /chat/stream   (SSE streaming)
│   │   ├── GET  /context/{id}/download
│   │   └── GET  /skills/{id}/download
│   │
│   ├── Chatbot widget
│   │   ├── [leaderspath_chatbot] shortcode (auto-detects Activity vs Lesson)
│   │   └── Chatbot_Renderer (callable directly from theme PHP)
│   │
│   └── WooCommerce Integration (optional)
│       ├── Cohort products (Simple + checkbox)
│       ├── Enrollment on purchase, access chain
│       └── Cohort phases (upcoming/active/completed)
│
├── Page builder (Bricks recommended)
│   └── Query loops + dynamic data — read ACF fields directly to display
│       Lesson Meta, Lesson Objectives, Lesson Activities,
│       Activity Meta, Context Library, Skills List, Course Lessons
│
└── Browser
    └── chatbot.js (vanilla JS IIFE)
        ├── SSE streaming with ReadableStream
        ├── Real-time markdown rendering (marked.js)
        ├── Sync fallback for older browsers
        └── Retry indicator + "Try again" button
```

---

## Installation

### Requirements

- WordPress 6.4+
- PHP 8.2+ (targeting 8.3)
- [Advanced Custom Fields Pro](https://www.advancedcustomfields.com/pro/)
- [Anthropic Claude API key](https://console.anthropic.com/)
- A page builder or theme that runs `do_shortcode()` (e.g. [Bricks Builder](https://bricksbuilder.io/), Gutenberg, classic editor)
- WooCommerce (optional — for cohort enrollment)

### Steps

1. Upload the `leaderspath` folder to `/wp-content/plugins/`
2. Run `composer install` from the plugin directory
3. Activate required plugins: Advanced Custom Fields Pro
4. Activate LeadersPath through the Plugins menu
5. Navigate to **Settings > LeadersPath**
6. Enter your Anthropic Claude API key (or set `LEADERSPATH_API_KEY` in `wp-config.php`)
7. Click "Test Connection" to verify API access

---

## Quick Start

### 1. Create Context Files

**Context Files > Add New**

Write or drag-and-drop markdown/text content that Claude will reference:

```markdown
# Brand Voice Guidelines

## Tone
- Professional but approachable
- Clear and concise

## Key Messages
- AI transparency is educational and empowering
```

Supported formats: `.md`, `.txt`, `.json`, `.yaml`, `.xml`, `.csv`, `.html`, `.css`, `.js`, `.ts`, `.py`, `.php`, `.rb`, `.sh`, `.sql`

### 2. Create Activities

**Activities > Add New**

Activities are AI sandbox experiments. Configure:
- **Title:** What learners will experience (e.g., "Experience Sycophantic AI")
- **System Prompt:** Defines the AI's behavior for this sandbox
- **Context Files:** Reference material embedded in the system prompt
- **Skills:** Executable capabilities (Python/bash) loaded into the container
- **Model Settings:** Model selection, temperature, max tokens, model switching

### 3. Create Lessons

**Lessons > Add New**

Lessons are the atomic teaching unit:
- **Learning Objectives:** What learners will achieve
- **Facilitator Guide:** Teaching script (what to present, when to run activities, discussion prompts)
- **Learner Overview:** Visible to learners as a content summary
- **Activities:** Linked AI sandbox experiments (relationship field)
- **Lesson Q&A Chatbot:** Optional helpful assistant for answering questions about lesson content

### 4. Create Courses

**Courses > Add New**

Courses are reusable curricula:
- **Lessons:** Ordered list of lessons (relationship field)
- **Prerequisites:** Other courses that must be completed first

### 5. Build Pages in Your Builder

Build one template per CPT in your page builder. Bricks Builder query loops read ACF fields directly — see [docs/ui-ux-catalog.md](docs/ui-ux-catalog.md) for the surface/field reference and [docs/cpt-schema.md](docs/cpt-schema.md) for the full field inventory.

The one piece of markup the plugin still ships is the chatbot widget — drop it into Activity or Lesson templates:

```
[leaderspath_chatbot]
```

See [docs/shortcodes.md](docs/shortcodes.md) for shortcode attributes.

---

## Chatbot

### Dual Modes

| Aspect | Activity Sandbox | Lesson Q&A |
|--------|------------------|------------|
| **Purpose** | Demonstrate specific AI behavior | Answer questions about lesson content |
| **System Prompt** | Crafted per-activity to show specific behavior | Helpful, knowledgeable assistant |
| **Context** | Activity-specific context files + skills | Lesson objectives, overview, context files |
| **Placement** | Activity pages | Lesson pages |

### Streaming

The chatbot uses SSE streaming by default with automatic fallback to synchronous requests for browsers that don't support `ReadableStream`.

- **Real-time markdown rendering:** `marked.js` with `requestAnimationFrame` throttling — headings, bold, lists, and code blocks render progressively as tokens arrive
- **Stop generating:** AbortController cancels the stream, keeping partial response
- **Automatic retry:** Transient API errors (500, 502, 503, 529) retry up to 2 times with 1s/3s backoff. The user sees "Retrying... (attempt 2 of 3)" with a "Try again" button if all retries fail.
- **Server requirements:** SSE streaming requires Nginx/PHP-FPM configuration to disable response buffering. See [server-requirements.md](docs/server-requirements.md).

### No Conversation Persistence

Chat history is held in memory only. Page reload clears the conversation — this is intentional to encourage experimentation.

---

## Claude API Integration

LeadersPath uses Anthropic's Container API with code execution and skills support.

### System Prompt Assembly

**Activity sandbox:**
```
[Custom System Prompt OR Default]
--- Reference Materials ---
### [Context File Title]
[Context File Content]
--- Available Skills ---
### [Skill Name]
[Skill Description]
```

**Lesson Q&A:**
```
[Custom Q&A Prompt OR Default Helpful Assistant]
--- Lesson Learning Objectives ---
1. [Objective]
--- Lesson Overview ---
[Learner Overview Content]
--- Reference Materials ---
### [Context File Title]
[Context File Content]
```

### Context Files vs Skills

| Aspect | Context Files | Skills |
|--------|---------------|--------|
| **Purpose** | Reference material (guidelines, standards) | Executable capabilities (scripts, workflows) |
| **Storage** | WordPress `post_content` only | Anthropic Skills API + WordPress metadata |
| **Delivery** | Embedded in system prompt | Via `container.skills` array |
| **Code execution** | No | Yes (Python, bash) |

### API Key Configuration

Set `LEADERSPATH_API_KEY` as a constant in `wp-config.php` (preferred) or enter it in Settings > LeadersPath (stored encrypted in the database).

---

## WooCommerce Cohorts

When WooCommerce is active, cohorts provide enrollment management:

- **Cohort = Simple Product** with the "Cohort Product" checkbox enabled
- **Linked to Courses** via ACF relationship field (multiple courses per cohort)
- **Enrollment:** Automatic on completed order, removed on refund/cancel
- **Access Control:** Enrollment gates Lesson/Activity access via REST API permission checks
- **Max Participants:** Uses WooCommerce stock management
- **Cohort Phases:** Automatically derived from start/end date fields (upcoming, active, completed)
- **Prerequisites:** Aggregated from linked courses (`course_prerequisites` field)

The plugin works without WooCommerce — enrollment is not enforced when WC is inactive.

---

## REST API

All endpoints are under the `leaderspath/v1` namespace.

| Endpoint | Method | Description |
|----------|--------|-------------|
| `/chat` | POST | Send message — synchronous JSON response |
| `/chat/stream` | POST | Send message — SSE streaming response |
| `/context/{id}/download` | GET | Download context file content |
| `/skills/{id}/download` | GET | Download skill package metadata |

### Chat Parameters

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `activity_id` | integer | One of activity_id or lesson_id | Activity sandbox mode |
| `lesson_id` | integer | One of activity_id or lesson_id | Lesson Q&A mode |
| `message` | string | Yes | User message (max 32,000 chars) |
| `history` | array | No | Previous conversation `[{role, content}]` |
| `model` | string | No | `sonnet`, `haiku`, or `opus-4.5` |
| `container_id` | string | No | Container ID for session continuity |

---

## Documentation

Detailed documentation is in the [`docs/`](docs/) folder:

| Document | Description |
|----------|-------------|
| [plugin-design.md](docs/plugin-design.md) | Architecture overview and design decisions |
| [cpt-schema.md](docs/cpt-schema.md) | Custom post types, taxonomies, ACF fields |
| [claude-api-integration.md](docs/claude-api-integration.md) | Claude API integration details |
| [data-contracts.md](docs/data-contracts.md) | ACF field to REST endpoint mapping |
| [shortcodes.md](docs/shortcodes.md) | Shortcode reference + template-mode tokens |
| [ui-ux-catalog.md](docs/ui-ux-catalog.md) | CSS class reference for every shortcode |
| [content-creation-guide.md](docs/content-creation-guide.md) | Guide for creating activities, context files, and skills |
| [server-requirements.md](docs/server-requirements.md) | Server config for SSE streaming (Nginx, PHP-FPM) |
| [TASKS.md](docs/TASKS.md) | Development task tracker and decisions log |

---

## Development

### Prerequisites

```bash
composer install     # PHP dependencies
```

There is no JS/CSS build step — frontend assets ship as committed files in `assets/`.

### File Structure

```
leaderspath/
├── leaderspath.php          # Main plugin file (bootstrap)
├── includes/                # Core PHP classes
│   ├── class-post-types.php
│   ├── class-taxonomies.php
│   ├── class-capabilities.php
│   ├── class-acf-fields.php
│   ├── class-claude-api.php
│   ├── class-rest-api.php
│   ├── class-skill-processor.php
│   ├── class-woocommerce.php
│   ├── class-shortcodes.php # [leaderspath_chatbot] registration + assets
│   └── renderers/
│       ├── class-post-id-helper.php
│       └── class-chatbot-renderer.php
├── admin/                   # Admin settings, columns, uploaders
├── assets/
│   ├── css/leaderspath.css  # Chatbot widget styles (loaded on demand)
│   └── js/                  # chatbot.js + vendor/marked.umd.js
├── docs/                    # Documentation
├── tests/                   # PHPUnit tests
└── bin/                     # CLI scripts (test-chat, create-test-data)
```

### Key Architectural Patterns

- **Page builder owns display.** All CPT display (Lesson Meta, Activity Meta, Context Library, Skills List, Lesson Activities, Lesson Objectives, Course Lessons) is built in Bricks Builder query loops + dynamic data reading ACF directly.
- **`Chatbot_Renderer`** is the one rendered surface the plugin owns. Called from the `[leaderspath_chatbot]` shortcode, or directly from theme PHP.
- **Vanilla JS** for the chatbot frontend — no React, no framework.
- **SSE streaming** via curl `CURLOPT_WRITEFUNCTION` (not `wp_remote_post`, which can't stream).
- **Cohorts as product meta** (not a custom WC product type).

---

## Credits

Developed by [Make Good](https://wemakegood.org) (Managed Word, LLC)

Powered by [Anthropic's Claude API](https://www.anthropic.com)

---

## License

GPLv2 or later. See [LICENSE](LICENSE) for details.
