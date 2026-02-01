# LeadersPath Plugin Design Document

**Version:** 0.1.0
**Last Updated:** 2026-02-01
**Status:** Implemented

## Executive Summary

LeadersPath is a WordPress plugin that powers an interactive AI learning community. Learners interact with Claude AI chatbots that have been enhanced with lesson-specific context files and executable skills, demonstrating the difference between raw LLM interactions and context-enhanced AI implementations.

## Core Architecture

### Design Philosophy

1. **Transparency First** - Learners can always see and download the context and skills powering the AI
2. **WordPress Native** - Uses CPTs, taxonomies, ACF, and standard WordPress patterns
3. **Divi Integration** - Visual Builder modules for flexible page design
4. **Stateless Conversations** - No persistence; page reload clears history for experimentation
5. **Context vs Skills** - Clear separation between reference material (context) and capabilities (skills)

### High-Level Architecture

```
┌─────────────────────────────────────────────────────────────────────┐
│                          WordPress                                   │
├─────────────────────────────────────────────────────────────────────┤
│  LeadersPath Plugin                                                  │
│                                                                      │
│  ┌─────────────────────────────────────────────────────────────────┐│
│  │                    Custom Post Types                             ││
│  │  ┌──────────┐ ┌──────────┐ ┌──────────┐ ┌─────────┐ ┌─────────┐││
│  │  │ Lessons  │ │ Courses  │ │ Cohorts  │ │ Context │ │ Skills  │││
│  │  └──────────┘ └──────────┘ └──────────┘ └─────────┘ └─────────┘││
│  └─────────────────────────────────────────────────────────────────┘│
│                                                                      │
│  ┌──────────────────────┐  ┌────────────────────────────────────┐  │
│  │    Claude API        │  │         Divi 5 Modules             │  │
│  │  ┌────────────────┐  │  │  ┌──────────┐  ┌────────────────┐  │  │
│  │  │ Messages API   │  │  │  │ Chatbot  │  │ Context Library│  │  │
│  │  │ + Container    │  │  │  └──────────┘  └────────────────┘  │  │
│  │  │ + Code Exec    │  │  │  ┌──────────┐  ┌────────────────┐  │  │
│  │  └────────────────┘  │  │  │Skills    │  │  Lesson Meta   │  │  │
│  │  ┌────────────────┐  │  │  │List      │  │                │  │  │
│  │  │  Skills API    │  │  │  └──────────┘  └────────────────┘  │  │
│  │  │  (Upload/Sync) │  │  └────────────────────────────────────┘  │
│  │  └────────────────┘  │                                          │
│  └──────────────────────┘                                          │
│                                                                      │
│  ┌──────────────────────┐  ┌────────────────────────────────────┐  │
│  │   REST API           │  │         Admin Interface            │  │
│  │  /leaderspath/v1/    │  │  Settings, Columns, Quick Edit     │  │
│  └──────────────────────┘  └────────────────────────────────────┘  │
├─────────────────────────────────────────────────────────────────────┤
│  Required Dependencies                                               │
│  ┌───────────────────┐  ┌───────────────────┐                       │
│  │  ACF Pro          │  │  Divi 5           │                       │
│  │  (Field Groups)   │  │  (Module System)  │                       │
│  └───────────────────┘  └───────────────────┘                       │
└─────────────────────────────────────────────────────────────────────┘
```

## Content Model

### Lesson (Primary Content Unit)

A Lesson is the central content type. It contains:

**Core Content:**
- Title and rich text body (standard WordPress)
- Featured image (thumbnail)
- Excerpt for summaries

**Lesson Settings (ACF):**
- Duration (minutes)
- Learning Objectives (repeater: objective text)
- Prerequisites (relationship to other lessons)
- References (repeater: title, URL, description)

**Chatbot Configuration (ACF):**
- Chatbot Enabled (true/false)
- Model Selection (opus-4.5, sonnet, haiku)
- Allow Model Switch (true/false)
- Custom System Prompt (textarea)
- Context Files (relationship to Context CPT)
- Skills (relationship to Skill CPT)
- Max Tokens (number, default 4096)
- Temperature (number, 0-1, default 0.7)

### Context File

Reference documents embedded in Claude's system prompt:

**Storage:**
- Title in `post_title`
- Full markdown content in `post_content`

**Metadata (ACF):**
- Description (purpose and usage notes)
- Version (semantic version string)
- File Type (System Prompt, Knowledge Base, Instructions, Guidelines, Examples, Other)

**Taxonomy:**
- Context Category (`leaderspath_context_cat`)

**Key Point:** Context files are NOT uploaded to Anthropic. Their full content is embedded directly in the system prompt for every chat request.

### Skill

Executable packages uploaded to Anthropic's Skills API:

**Storage:**
- Title in `post_title`
- ZIP package as ACF file field

**Metadata (ACF):**
- skill_package (file upload, ZIP only)
- skill_name (auto-populated from SKILL.md frontmatter, read-only)
- skill_description (auto-populated from SKILL.md frontmatter, read-only)
- skill_compatibility (auto-populated from SKILL.md frontmatter, read-only)
- skill_version (user-managed semantic version)
- skill_notes (WYSIWYG, admin notes)
- skill_anthropic_id (returned from Skills API, read-only)
- skill_anthropic_version (returned from Skills API, read-only)
- skill_sync_status (pending/synced/error)
- skill_sync_error (last error message)
- skill_last_synced (timestamp)

**Taxonomy:**
- Skill Category (`leaderspath_skill_cat`)

**Key Point:** Skills ARE uploaded to Anthropic. Only their name and description appear in the system prompt; the actual instructions and scripts are loaded by Anthropic's container when triggered.

### Course

Container for organizing lessons:

**Metadata (ACF):**
- Ordered Lessons (relationship, sortable)
- Difficulty (Beginner/Intermediate/Advanced)
- Access Roles (checkboxes)

### Cohort

Scheduled learning groups:

**Metadata (ACF):**
- Associated Course (post object)
- Start/End Dates
- Instructor (user)
- Language, Timezone
- Max Participants
- Status (Upcoming/Active/Completed/Cancelled)

## Context vs Skills: The Critical Distinction

This is the most important architectural concept in LeadersPath:

### Context Files

| Aspect | Details |
|--------|---------|
| Purpose | Reference material - knowledge, guidelines, examples |
| Storage | WordPress only (`post_content`) |
| Delivery | Full content embedded in system prompt |
| API Upload | Not required |
| Capabilities | None - just information |
| Token Cost | Full content counted every request |

**When to use:** Brand guidelines, writing standards, process documentation, example content, knowledge bases, FAQ documents.

### Skills

| Aspect | Details |
|--------|---------|
| Purpose | Executable capabilities - scripts, workflows |
| Storage | Anthropic Skills API + WordPress metadata |
| Delivery | Name/description in prompt; full content loaded on-demand by container |
| API Upload | Required (via `POST /v1/skills`) |
| Capabilities | Python execution, file generation, data processing |
| Token Cost | Minimal in prompt; loaded progressively when used |

**When to use:** Data analysis scripts, document generation, API integrations, file processing, complex multi-step workflows.

### System Prompt Assembly

```
[Custom System Prompt OR Default]

--- Lesson Content ---
[Lesson post_content - the lesson instructions]

--- Reference Materials ---
### [Context File 1 Title]
[Context File 1 Full Content - embedded]

### [Context File 2 Title]
[Context File 2 Full Content - embedded]

--- Available Skills ---
### [Skill 1 Name]
[Skill 1 Description - for discovery only]

### [Skill 2 Name]
[Skill 2 Description - for discovery only]
```

## Divi 5 Module Architecture

All modules use the **Theme Builder Pattern** - they read data from the current post (lesson) using `get_queried_object_id()`.

### Module Registration

**PHP Side:** `modules/{ModuleName}/{ModuleName}.php`
- Implements `DependencyInterface`
- Registers via `ModuleRegistration::register_module()`
- Uses traits: RenderCallbackTrait, ModuleStylesTrait, ModuleClassnamesTrait, CustomCssTrait

**JavaScript Side:** `src/components/{module-name}/`
- `module.json` - Attribute schema and settings
- `edit.tsx` - Visual Builder preview
- `styles.tsx` - Dynamic styles
- `index.ts` - Module export

### LeadersPath Chatbot Module

**Files:**
- `modules/Chatbot/Chatbot.php` + 4 traits
- `src/components/chatbot/` (8 TypeScript files)
- `assets/js/chatbot.js` (frontend interactivity)
- `assets/css/chatbot.css` (runtime styles)

**Dependencies:**
- TinyMCE (from wp-includes, custom handle to avoid CSS conflicts)
- marked.js (CDN, for markdown rendering)

**Features:**
- Rich text input with keyboard shortcuts (Ctrl+B, Ctrl+I, etc.)
- Markdown rendering with GitHub Flavored Markdown
- Smart scroll (new messages appear at top of visible area)
- Loading indicators (animated dots)
- Error display in conversation
- Configurable styling for all elements

### LeadersPath Context Library Module

Displays context files attached to current lesson.

**Features:**
- Responsive card grid (auto-fill, min 280px)
- View content modal (custom implementation)
- Download buttons
- Visibility toggles for all elements
- Styling options for cards, titles, descriptions, badges, buttons

### LeadersPath Skills List Module

Displays skills attached to current lesson.

**Features:**
- Responsive card grid (same as Context Library)
- Compatibility badge (e.g., "Python 3.9+")
- Version badge (e.g., "v1.2.0")
- Download links to ZIP packages
- Visibility toggles for all elements

### LeadersPath Lesson Meta Module

Displays lesson metadata.

**Features:**
- Duration display with configurable label
- Learning objectives list
- AI model indicator
- Visibility toggles for each section

## Claude API Integration

### Request Flow

```
User Message
    │
    ├── Validate: Logged in? Has capability?
    │
    ├── Build Context:
    │   ├── Get lesson's custom system prompt OR default
    │   ├── Get lesson post_content
    │   ├── Get linked context files → embed full content
    │   └── Get linked skills → include name/description only
    │
    ├── Build Request:
    │   ├── model (from lesson or default)
    │   ├── system (assembled prompt)
    │   ├── messages (conversation history)
    │   ├── container.skills (skill_ids from synced skills)
    │   └── tools (code_execution if skills present)
    │
    ├── Send to Anthropic
    │   └── Handle pause_turn responses (loop until end_turn)
    │
    └── Return Response
        ├── content (text blocks)
        └── container.id (for session reuse)
```

### Beta Headers

Configurable in Settings > LeadersPath:

| Feature | Header | Default |
|---------|--------|---------|
| Code Execution | `anthropic-beta` | `code-execution-2025-08-25` |
| Skills | `anthropic-beta` | `skills-2025-10-02` |
| Files | `anthropic-beta` | `files-api-2025-04-14` |

### Skill Sync Lifecycle

1. **Upload ZIP** via ACF file field
2. **Parse frontmatter** - Extract name, description, compatibility from SKILL.md
3. **Validate** - Name must be lowercase/hyphens, 1-64 chars; description 1-1024 chars
4. **Upload to Anthropic** - `POST /v1/skills` with multipart/form-data
5. **Store skill_id** - Save in `skill_anthropic_id` field
6. **Mark synced** - Set `skill_sync_status` to "synced"

**On update:** `POST /v1/skills/{skill_id}/versions` creates new version.

## REST API Endpoints

| Endpoint | Method | Description |
|----------|--------|-------------|
| `/leaderspath/v1/chat` | POST | Send message, receive Claude response |
| `/leaderspath/v1/lessons/{id}/context` | GET | Get lesson's context files |
| `/leaderspath/v1/lessons/{id}/skills` | GET | Get lesson's skills |
| `/leaderspath/v1/context/{id}/download` | GET | Download context file content |
| `/leaderspath/v1/skills/{id}/download` | GET | Download skill ZIP package |

All endpoints require authentication (logged in + appropriate capability).

## Security Model

### Capabilities

| Capability | Description |
|------------|-------------|
| `leaderspath_access_lessons` | View published lessons |
| `leaderspath_access_chatbot` | Use chatbot feature |
| `leaderspath_view_context` | View context file contents |
| `leaderspath_download_context` | Download context files |
| `leaderspath_download_skills` | Download skill packages |
| `edit_leaderspath_lessons` | Edit lessons (editors) |
| `edit_leaderspath_contexts` | Edit context files (editors) |
| `edit_leaderspath_skills` | Edit skills (editors) |

### Roles

**Administrator:** All capabilities
**Editor:** All edit capabilities, read-only for cohorts
**LeadersPath Student:** Access lessons, use chatbot, view/download content

### Security Measures

1. **API Key:** Encrypted at rest using WordPress salt
2. **Nonces:** All REST/AJAX requests verified
3. **Capability Checks:** Every endpoint validates permissions
4. **Input Sanitization:** All user input sanitized before processing
5. **Output Escaping:** All output escaped appropriately

## File Structure

```
leaderspath/
├── leaderspath.php              # Bootstrap, constants, autoloader
├── composer.json                # PHP dependencies (symfony/yaml)
├── package.json                 # Node dependencies (Divi build tools)
│
├── includes/
│   ├── class-leaderspath.php    # Main plugin class
│   ├── class-post-types.php     # CPT registration
│   ├── class-taxonomies.php     # Taxonomy registration
│   ├── class-capabilities.php   # Roles and capabilities
│   ├── class-acf-fields.php     # ACF field group registration
│   ├── class-claude-api.php     # Claude API wrapper
│   ├── class-rest-api.php       # REST endpoints
│   └── class-skill-processor.php # Skill validation and sync
│
├── admin/
│   └── class-settings.php       # Settings page
│   └── class-admin-columns.php  # Custom admin columns
│
├── modules/                     # Divi 5 PHP modules
│   ├── Modules.php              # Module registration hub
│   ├── Chatbot/
│   │   ├── Chatbot.php
│   │   └── ChatbotTrait/
│   │       ├── RenderCallbackTrait.php
│   │       ├── ModuleStylesTrait.php
│   │       ├── ModuleClassnamesTrait.php
│   │       └── CustomCssTrait.php
│   ├── ContextLibrary/
│   ├── SkillsList/
│   └── LessonMeta/
│
├── src/                         # Divi 5 TypeScript modules
│   ├── index.ts                 # Module registration
│   └── components/
│       ├── chatbot/
│       │   ├── module.json
│       │   ├── edit.tsx
│       │   ├── styles.tsx
│       │   ├── types.ts
│       │   └── ...
│       ├── context-library/
│       ├── skills-list/
│       └── lesson-meta/
│
├── assets/
│   ├── js/
│   │   ├── chatbot.js           # Frontend chat interactivity
│   │   └── context-modal.js     # Modal for viewing content
│   └── css/
│       ├── chatbot.css
│       └── context-modal.css
│
├── modules-json/                # Built module.json files
├── scripts/bundle.js            # Built VB JavaScript
├── styles/bundle.css            # Built styles
│
├── docs/
│   ├── plugin-design.md         # This file
│   ├── cpt-schema.md            # CPT and ACF field details
│   ├── claude-api-integration.md # API integration guide
│   ├── divi-modules.md          # Module development guide
│   └── content-creation-guide.md # Content authoring guide
│
├── bin/
│   ├── create-test-data.php     # Test data generator
│   ├── test-chat.php            # CLI chat tester
│   └── show-system-prompt.php   # View assembled prompts
│
└── tests/                       # PHPUnit tests
```

## Design Decisions

| Decision | Rationale |
|----------|-----------|
| CPTs for Context/Skills | WordPress admin UI, revision history, ACF integration |
| No conversation persistence | Fresh start enables experimentation without baggage |
| Single plugin-wide API key | Simpler management; billing at org level |
| No rate limiting (initially) | Paid service, trust users; add later if needed |
| Streaming optional | Nice UX but adds complexity; implement if time permits |
| Context files in system prompt | Simple, reliable; no additional API calls |
| Skills uploaded to Anthropic | Required for code execution; progressive loading |
| TinyMCE without toolbar | Clean UI; keyboard shortcuts are sufficient |
| marked.js from CDN | Lightweight, reliable markdown parsing |

## Future Considerations

- Analytics/tracking of lesson completion and chat metrics
- Conversation history persistence (opt-in per lesson)
- Multi-language lesson support
- Bulk import/export of context files and skills
- AI-assisted content creation tools
- Integration with LMS plugins (LearnDash, LifterLMS)
- Webhook notifications for external systems
