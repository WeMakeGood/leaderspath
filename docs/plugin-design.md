# LeadersPath Plugin Design Document

**Version:** 0.6.0
**Last Updated:** 2026-02-11
**Status:** Core infrastructure + WooCommerce Cohort enrollment implemented; frontend research complete, module implementation pending

## Executive Summary

LeadersPath is a WordPress plugin that powers a **facilitated learning experience**. Facilitators present Lessons (atomic teaching units containing Activities) while learners experiment with AI sandboxes (Activities) to experience specific AI behaviors. Courses define reusable curricula containing ordered Lessons. Cohorts (WooCommerce products) handle enrollment and access gating. The plugin demonstrates the difference between raw LLM interactions and context-enhanced AI implementations.

## Core Architecture

### Design Philosophy

1. **Facilitated Learning** - Lessons are taught by facilitators; activities let learners experiment
2. **Transparency First** - Learners can always see and download the context and skills powering the AI
3. **WordPress Native** - Uses CPTs, taxonomies, ACF, and standard WordPress patterns
4. **Divi Integration** - Visual Builder modules for flexible page design (research complete, implementation pending)
5. **Stateless Conversations** - No persistence; page reload clears history for experimentation
6. **Context vs Skills** - Clear separation between reference material (context) and capabilities (skills)
7. **WooCommerce Integration** - Cohort products gate access via enrollment; graceful degradation without WC

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
│  │  │Activities│ │ Lessons  │ │ Courses  │ │ Context │ │ Skills  │││
│  │  └──────────┘ └──────────┘ └──────────┘ └─────────┘ └─────────┘││
│  └─────────────────────────────────────────────────────────────────┘│
│                                                                      │
│  ┌──────────────────────┐  ┌────────────────────────────────────┐  │
│  │    Claude API        │  │    WooCommerce Integration         │  │
│  │  ┌────────────────┐  │  │  ┌────────────────────────────┐   │  │
│  │  │ Messages API   │  │  │  │ Cohort Checkbox             │   │  │
│  │  │ + Container    │  │  │  │ (_cohort meta flag)        │   │  │
│  │  │ + Code Exec    │  │  │  └────────────────────────────┘   │  │
│  │  └────────────────┘  │  │  ┌────────────────────────────┐   │  │
│  │  ┌────────────────┐  │  │  │ Enrollment & Access Chain  │   │  │
│  │  │  Skills API    │  │  │  │ (User meta + order hooks)  │   │  │
│  │  │  (Upload/Sync) │  │  │  └────────────────────────────┘   │  │
│  │  └────────────────┘  │  └────────────────────────────────────┘  │
│  └──────────────────────┘                                          │
│                                                                      │
│  ┌──────────────────────┐  ┌────────────────────────────────────┐  │
│  │   REST API           │  │         Admin Interface            │  │
│  │  /leaderspath/v1/    │  │  Settings, Columns, Dashboard      │  │
│  └──────────────────────┘  └────────────────────────────────────┘  │
│                                                                      │
│  ┌──────────────────────────────────────────────────────────────┐  │
│  │      Divi 5 Modules (research complete, build pending)       │  │
│  └──────────────────────────────────────────────────────────────┘  │
├─────────────────────────────────────────────────────────────────────┤
│  Dependencies                                                        │
│  ┌───────────────────┐  ┌───────────────┐  ┌───────────────────┐   │
│  │  ACF Pro          │  │  Divi 5       │  │  WooCommerce      │   │
│  │  (Field Groups)   │  │  (Modules)    │  │  (Optional)       │   │
│  └───────────────────┘  └───────────────┘  └───────────────────┘   │
└─────────────────────────────────────────────────────────────────────┘
```

## Content Model

### Pedagogical Model

LeadersPath is a **facilitated learning experience**, not a self-paced platform:

| Term | Definition |
|------|------------|
| **Cohort** | A WooCommerce product that enrolls learners into one or more Courses |
| **Course** | A reusable curriculum containing an ordered sequence of Lessons |
| **Lesson** | The atomic teaching unit, taught as a cohesive whole by a facilitator |
| **Activity** | An AI sandbox experiment within a Lesson (what learners DO, not what they LEARN) |
| **Facilitator Guide** | The central teaching document (what to present, when to run activities, discussion prompts) |
| **Context Files** | Reference documents that provide Claude with background information |
| **Skills** | Executable capabilities (Python scripts, workflows) uploaded to Anthropic |

### Activity (AI Sandbox Experiment)

Activities are AI sandbox experiments where learners experience specific AI behaviors.

**Core Content:**
- Title (`post_title`): Activity name (e.g., "Experience Sycophantic AI")
- Content (`post_content`): Instructions for learners ("Try this, notice that")
- Excerpt for summaries

**Activity Settings (ACF):**
- Duration (minutes)
- Prerequisites (relationship to other activities)
- References (repeater: title, URL, description)

**AI Sandbox Configuration (ACF):**
- Chatbot Enabled (true/false)
- Model Selection (opus-4.5, sonnet, haiku)
- Allow Model Switch (true/false)
- Custom System Prompt (textarea) - defines the AI behavior learners will experience
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

**Taxonomy:**
- Context Category (`leaderspath_context_cat`) - for categorization (e.g., Knowledge Base, Instructions, Examples)

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

### Lesson (Atomic Teaching Unit)

The lesson is the atomic teaching unit, taught as a cohesive whole by a facilitator:

**Core Content:**
- Title and description (standard WordPress)
- Featured image
- Excerpt for marketing

**Lesson Settings (ACF):**
- `lesson_activities` - Ordered activities (relationship, sortable)
- `lesson_total_duration` - Total facilitation time (e.g., "90 minutes")
- `lesson_objectives` - Learning objectives for the lesson
- `lesson_access_roles` - User roles that can access

**Facilitator Content (ACF):**
- `lesson_facilitator_guide` - Complete teaching script with timing, activity transitions, discussion prompts

**Learner Content (ACF):**
- `lesson_learner_overview` - What learners will experience (context, not teaching content)

**Optional Lesson Q&A Chatbot (ACF):**
- `lesson_chatbot_enabled` - Enable Q&A chatbot (different from activity sandboxes)
- `lesson_chatbot_model` - Claude model
- `lesson_chatbot_system_prompt` - Should be configured as helpful assistant
- `lesson_chatbot_context_files` - Context for Q&A

### Course (Curriculum)

A reusable curriculum containing an ordered sequence of Lessons:

**Metadata (ACF):**
- `course_lessons` - Ordered list of lessons (relationship to `leaderspath_lesson`)

### Cohort (WooCommerce Product)

A virtual WooCommerce product that manages enrollment and access gating:

**Product Flag:** Simple products with `_cohort` meta set to `yes` (checkbox next to Virtual/Downloadable)

**Metadata (ACF, on `product` post type):**
- `cohort_courses` - Relationship to `leaderspath_course` posts (multi-select)
- `cohort_start_date` - Date picker (Y-m-d)
- `cohort_end_date` - Date picker (Y-m-d)
- `cohort_facilitator` - User selector (filtered to facilitator/admin roles)

**Max Participants:** Uses WooCommerce stock management (Inventory tab) instead of a custom field. Provides built-in "X left in stock" display, oversell prevention, and low-stock notifications.

**Phase:** Derived from dates — `upcoming` (before start), `active` (between start/end), `completed` (after end)

**Enrollment:** Managed via WooCommerce order lifecycle:
- Order completed → user enrolled (user meta `leaderspath_enrollments`)
- Order refunded/cancelled → user unenrolled

**Access Chain:** User enrolled in Cohort → Cohort links to Course(s) → Course contains Lessons → Lesson contains Activities. Admin/Editor roles bypass enrollment checks. If WooCommerce is not active, enrollment is not enforced.

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

**Note:** The activity's `post_content` (what learners see on the page) is NOT included in the system prompt. Only the Custom System Prompt field and attached Context Files define Claude's behavior.

## Claude API Integration

### Request Flow

```
User Message
    │
    ├── Validate: Logged in? Has capability?
    │
    ├── Build Context:
    │   ├── Get activity's custom system prompt OR default
    │   ├── Get linked context files → embed full content
    │   └── Get linked skills → include name/description only
    │
    ├── Build Request:
    │   ├── model (from activity or default)
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
| `/leaderspath/v1/context/{id}/download` | GET | Download context file content |
| `/leaderspath/v1/skills/{id}/download` | GET | Download skill package metadata |

All endpoints require authentication (logged in + appropriate capability).

**Note:** VB preview endpoints for Divi modules were removed and will be re-added during the frontend rebuild. See `docs/ui-ux-catalog.md` for module data requirements and `docs/divi5-module-architecture.md` for the rendering strategy.

## Security Model

### Capabilities

| Capability | Description |
|------------|-------------|
| `leaderspath_access_activities` | View published activities |
| `leaderspath_access_chatbot` | Use chatbot feature |
| `leaderspath_view_context` | View context file contents |
| `leaderspath_download_context` | Download context files |
| `leaderspath_download_skills` | Download skill packages |
| `edit_leaderspath_activities` | Edit activities (editors) |
| `edit_leaderspath_contexts` | Edit context files (editors) |
| `edit_leaderspath_skills` | Edit skills (editors) |
| `leaderspath_manage_cohorts` | Manage cohort products (admins/editors) |

### Roles

**Administrator:** All capabilities
**Editor:** All edit capabilities, read-only for courses
**LeadersPath Facilitator:** Access activities, use chatbot, view/download content, view facilitator guides
**LeadersPath Student:** Access activities, use chatbot, view/download content

### Security Measures

1. **API Key:** `LEADERSPATH_API_KEY` constant in wp-config.php (preferred) or encrypted at rest in database using WordPress salt
2. **Nonces:** All REST/AJAX requests verified
3. **Capability Checks:** Every endpoint validates permissions
4. **Enrollment Gating:** Chat endpoint verifies user is enrolled in a cohort linked to the requested content (when WooCommerce is active; admin/editor bypass)
5. **Input Sanitization:** All user input sanitized before processing
6. **Output Escaping:** All output escaped appropriately

## File Structure

```
leaderspath/
├── leaderspath.php              # Bootstrap, constants, autoloader
├── composer.json                # PHP dependencies (symfony/yaml)
├── package.json                 # Node dependencies (Divi build tools)
│
├── includes/
│   ├── class-post-types.php     # CPT registration
│   ├── class-taxonomies.php     # Taxonomy registration
│   ├── class-capabilities.php   # Roles and capabilities
│   ├── class-acf-fields.php     # ACF field group registration
│   ├── class-claude-api.php     # Claude API wrapper
│   ├── class-rest-api.php       # REST endpoints
│   ├── class-skill-processor.php # Skill validation and sync
│   └── class-woocommerce.php    # WC integration: cohort checkbox, enrollment, access chain (loaded if WC active)
│
├── admin/
│   ├── class-admin-menu.php     # Admin menu registration + submenu ordering
│   ├── class-settings.php       # Settings page
│   └── class-admin-columns.php  # Custom admin columns
│
├── modules/                     # Divi 5 PHP modules (implementation pending)
│   └── Shared/                  # Shared traits (validated)
│       ├── PostIdHelper.php
│       ├── CustomCssTrait.php
│       └── ModuleClassnamesTrait.php
│
├── src/                         # Divi 5 TypeScript (implementation pending)
│   └── components/              # Empty — research complete, ready for build
│
├── docs/
│   ├── plugin-design.md         # This file
│   ├── cpt-schema.md            # CPT and ACF field details
│   ├── divi-modules.md          # Module status and implementation plan
│   ├── divi5-module-architecture.md # Divi 5 architecture reference
│   ├── wordpress-rendering-pipeline.md # WP block rendering reference
│   ├── ui-ux-catalog.md         # UI/UX catalog (modules, elements, CSS classes)
│   ├── claude-api-integration.md # API integration guide
│   ├── data-contracts.md        # ACF → REST endpoint mapping
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
| Single plugin-wide API key | Simpler management; billing at org level. Supports `LEADERSPATH_API_KEY` constant in wp-config.php or encrypted database storage |
| No rate limiting (initially) | Paid service, trust users; add later if needed |
| Streaming optional | Nice UX but adds complexity; implement if time permits |
| Context files in system prompt | Simple, reliable; no additional API calls |
| Skills uploaded to Anthropic | Required for code execution; progressive loading |
| Cohort as product checkbox | Checkbox next to Virtual/Downloadable (like wc-donation-platform); simpler than custom product type |
| ACF Pro for Cohort fields | Same UI patterns as all other LeadersPath field groups |
| User meta for enrollment | Simple serialized array; sufficient for MVP volumes |
| Cohort phase derived from dates | No manual status field; auto-computed from start/end dates |
| Multiple courses per cohort | Supports bundled curricula and certificate programs |
| Graceful WC degradation | Plugin works without WC; enrollment not enforced |

## Future Considerations

- Analytics/tracking of activity completion and chat metrics
- Conversation history persistence (opt-in per activity)
- Multi-language activity support
- Bulk import/export of context files and skills
- AI-assisted content creation tools
- Integration with LMS plugins (LearnDash, LifterLMS)
- Webhook notifications for external systems
