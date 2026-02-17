# LeadersPath

AI-powered facilitated cohort learning platform for WordPress and Divi 5.

**Version:** 0.1.0
**Requires:** WordPress 6.4+, PHP 8.2+, Divi 5, ACF Pro
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

- **5 Custom Post Types** with ACF Pro field groups
- **8 Divi 5 Modules** for flexible page/template design
- **Dual Chatbot Modes:** Activity sandbox (demonstrate behaviors) + Lesson Q&A (helpful assistant)
- **SSE Streaming** with real-time markdown rendering
- **Claude API Integration:** Container-based API with code execution and skills support
- **Automatic Retry** for transient API errors with user-visible status
- **Transparency:** Learners can view and download the exact context and skills powering the AI
- **WooCommerce Cohorts:** Enrollment management, access control, cohort phases
- **No Conversation Persistence:** Page reload clears chat — enables experimentation

---

## Architecture

```
WordPress + Divi 5
├── LeadersPath Plugin
│   ├── Data Layer
│   │   ├── 5 CPTs (Activity, Lesson, Course, Context File, Skill)
│   │   ├── 3 Taxonomies (Topics, Context Categories, Skill Categories)
│   │   └── ACF Pro field groups
│   │
│   ├── Claude API
│   │   ├── Messages API + Container (code execution)
│   │   ├── Skills API (executable skill packages)
│   │   ├── SSE streaming (curl + WRITEFUNCTION callback)
│   │   └── Automatic retry (transient 5xx errors)
│   │
│   ├── REST API
│   │   ├── POST /chat          (synchronous JSON)
│   │   ├── POST /chat/stream   (SSE streaming)
│   │   ├── GET  /context/{id}/download
│   │   └── GET  /skills/{id}/download
│   │
│   ├── Divi 5 Modules (8 modules)
│   │   ├── Chatbot, Context Library, Skills List
│   │   ├── Lesson Meta, Lesson Activities, Lesson Objectives
│   │   ├── Activity Meta, Course Lessons
│   │   └── Frontend = PHP, Visual Builder = React/TS
│   │
│   └── WooCommerce Integration (optional)
│       ├── Cohort products (Simple + checkbox)
│       ├── Enrollment on purchase, access chain
│       └── Cohort phases (upcoming/active/completed)
│
└── Browser
    ├── chatbot.js (vanilla JS IIFE)
    │   ├── SSE streaming with ReadableStream
    │   ├── Real-time markdown rendering (marked.js)
    │   ├── Sync fallback for older browsers
    │   └── Retry indicator + "Try again" button
    └── context-modal.js (vanilla JS, REST fetch)
```

---

## Installation

### Requirements

- WordPress 6.4+
- PHP 8.2+ (targeting 8.3)
- [Divi 5](https://www.elegantthemes.com/gallery/divi/)
- [Advanced Custom Fields Pro](https://www.advancedcustomfields.com/pro/)
- [Anthropic Claude API key](https://console.anthropic.com/)
- WooCommerce (optional — for cohort enrollment)

### Steps

1. Upload the `leaderspath` folder to `/wp-content/plugins/`
2. Run `composer install` from the plugin directory
3. Run `npm install && npm run build` to compile Divi modules
4. Activate required plugins: Divi 5, Advanced Custom Fields Pro
5. Activate LeadersPath through the Plugins menu
6. Navigate to **Settings > LeadersPath**
7. Enter your Anthropic Claude API key (or set `LEADERSPATH_API_KEY` in `wp-config.php`)
8. Click "Test Connection" to verify API access

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

### 5. Build Pages with Divi 5

Use Theme Builder templates or individual pages with LeadersPath modules:

| Module | Description | Used On |
|--------|-------------|---------|
| **Chatbot** | Interactive chat with Claude | Activity pages, Lesson pages |
| **Context Library** | Card grid of attached context files with view/download | Activity pages |
| **Skills List** | Card grid of attached skills | Activity pages |
| **Activity Meta** | Duration, model name, model switching indicator | Activity pages |
| **Lesson Meta** | Difficulty, duration, activity count | Lesson pages |
| **Lesson Activities** | Ordered list of linked activities with number badges | Lesson pages |
| **Lesson Objectives** | Ordered list of learning objectives | Lesson pages |
| **Course Lessons** | List of linked lessons with metadata + prerequisites | Course pages |

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
| [divi-modules.md](docs/divi-modules.md) | Divi 5 module development guide |
| [divi5-module-architecture.md](docs/divi5-module-architecture.md) | Divi 5 architecture reference (validated patterns) |
| [ui-ux-catalog.md](docs/ui-ux-catalog.md) | UI/UX catalog: modules, elements, CSS classes |
| [content-creation-guide.md](docs/content-creation-guide.md) | Guide for creating activities, context files, and skills |
| [TASKS.md](docs/TASKS.md) | Development task tracker and decisions log |

---

## Development

### Prerequisites

```bash
composer install     # PHP dependencies
npm install          # Node dependencies (Divi modules)
```

### Build Commands

```bash
npm run build        # Production build
npm run start        # Development with watch
```

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
│   └── class-woocommerce.php
├── modules/                 # Divi 5 PHP modules
│   ├── ActivityMeta/
│   ├── Chatbot/
│   ├── ContextLibrary/
│   ├── CourseLessons/
│   ├── LessonActivities/
│   ├── LessonMeta/
│   ├── LessonObjectives/
│   ├── SkillsList/
│   ├── Shared/              # Shared PHP traits
│   └── Modules.php          # Module registration hub
├── src/                     # Divi 5 TypeScript/React (VB only)
│   └── components/
├── assets/                  # Frontend JS (chatbot, context modal)
├── admin/                   # Admin settings, columns, uploaders
├── styles/                  # Compiled CSS (bundle.css, vb-bundle.css)
├── scripts/                 # Compiled JS (bundle.js)
├── modules-json/            # Generated module.json + defaults
├── docs/                    # Documentation
├── tests/                   # PHPUnit tests
└── bin/                     # CLI scripts (test-chat, create-test-data)
```

### Key Architectural Patterns

- **Frontend = 100% PHP;** Visual Builder = 100% React/TypeScript (no hydration)
- **Bottom-up module architecture:** Layer 1 (pure PHP renderer) → Layer 2 (SCSS) → Layer 3 (Divi wrapper) → Layer 4 (settings)
- **Vanilla JS** for frontend interactivity (chatbot, context modal) — no React on the frontend
- **SSE streaming** via curl `CURLOPT_WRITEFUNCTION` (not `wp_remote_post`, which can't stream)
- **Cohorts as product meta** (not a custom WC product type)

---

## Credits

Developed by [Make Good](https://wemakegood.org) (Managed Word, LLC)

Powered by [Anthropic's Claude API](https://www.anthropic.com)

---

## License

GPLv2 or later. See [LICENSE](LICENSE) for details.
