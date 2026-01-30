# LeadersPath Plugin Design Document

**Version:** 0.1.0
**Last Updated:** 2026-01-29
**Status:** Draft

## Executive Summary

LeadersPath is a WordPress plugin that powers an interactive AI learning community. The plugin provides chatbot-powered lesson experiences where learners interact with Claude AI to understand the difference between raw LLM interactions and context-enhanced AI implementations.

## Core Objectives

1. **Interactive Learning** - Embed AI chatbots within lessons that demonstrate AI capabilities
2. **Context Management** - Manage and expose context libraries (markdown files, skills) per lesson
3. **Transparency** - Allow learners to view and download the context files and skills used by chatbots
4. **Visual Builder Integration** - Provide Divi 5 modules for flexible lesson template design
5. **Access Control** - Manage content access based on WordPress user roles

## Architecture Overview

```
┌─────────────────────────────────────────────────────────────────┐
│                        WordPress                                 │
├─────────────────────────────────────────────────────────────────┤
│  LeadersPath Plugin                                             │
│  ┌─────────────┐  ┌─────────────┐  ┌─────────────────────────┐ │
│  │   Custom    │  │   Context   │  │      Divi 5 Modules     │ │
│  │ Post Types  │  │   Library   │  │  ┌───────┐ ┌─────────┐  │ │
│  │             │  │             │  │  │Chatbot│ │ Context │  │ │
│  │ - Lessons   │  │ - MD Files  │  │  │       │ │  List   │  │ │
│  │ - Courses   │  │ - Skills    │  │  └───────┘ └─────────┘  │ │
│  │ - Cohorts   │  │             │  │  ┌───────┐ ┌─────────┐  │ │
│  └─────────────┘  └─────────────┘  │  │Skills │ │ Lesson  │  │ │
│                                     │  │ List  │ │  Meta   │  │ │
│  ┌─────────────┐  ┌─────────────┐  │  └───────┘ └─────────┘  │ │
│  │   Claude    │  │   Access    │  └─────────────────────────┘ │
│  │     API     │  │   Control   │                               │
│  │  Interface  │  │             │                               │
│  └─────────────┘  └─────────────┘                               │
├─────────────────────────────────────────────────────────────────┤
│  External Dependencies                                           │
│  ┌───────────┐  ┌───────────┐  ┌───────────┐                   │
│  │  ACF Pro  │  │  Divi 5   │  │WooCommerce│                   │
│  └───────────┘  └───────────┘  └───────────┘                   │
└─────────────────────────────────────────────────────────────────┘
```

## Custom Post Types

### Lesson (`leaderspath_lesson`)

The primary content unit. Each lesson contains:

- **Content** - Lesson directions, learning objectives, duration
- **Chatbot Configuration** - Model selection, system prompt, behavior flags
- **Context Library** - Associated markdown files for API context injection
- **Skills** - Associated agentic skills definitions
- **References** - Links to additional resources

### Course (`leaderspath_course`)

A container for ordered lessons:

- **Title & Description**
- **Lesson Order** - Ordered list of associated lessons
- **Access Requirements** - User role requirements

### Cohort (`leaderspath_cohort`)

Groups of learners working through courses together:

- **Associated Course**
- **Date/Time Information**
- **Instructor**
- **Language**

## Taxonomies

### Skill Category (`leaderspath_skill_cat`)

Categorizes skills for organization and filtering.

### Topic (`leaderspath_topic`)

Cross-cutting topics that can apply to lessons and courses.

## Context Library System

### Storage Options

**Option A: Custom Database Tables**
- `leaderspath_context_files` - Stores markdown content
- `leaderspath_skills` - Stores skill definitions
- Provides version history and metadata

**Option B: Custom Post Type**
- `leaderspath_context` CPT for files
- `leaderspath_skill` CPT for skills
- Leverages WordPress revision system

**Option C: File-Based with Registry**
- Files stored in `wp-content/uploads/leaderspath/`
- Metadata stored in custom table or post meta
- Direct file editing possible

**Recommended: Option B (Custom Post Types)**
- Familiar WordPress admin interface
- Built-in revision history
- ACF Pro integration for metadata
- Easy association with lessons via relationship fields

### Context File Structure

Each context file includes:
- **Title** - Human-readable name
- **Content** - Markdown content (stored in post_content)
- **Description** - Purpose and usage notes
- **Version** - Semantic version number
- **Category** - Organization taxonomy

### Skill Structure

Each skill includes:
- **Title** - Skill name
- **Definition** - Skill JSON/YAML definition
- **Description** - What the skill does
- **Documentation** - Usage instructions
- **Version** - Semantic version number

## Divi 5 Modules

### Chatbot Module (`leaderspath/chatbot`)

Embeds an interactive Claude-powered chatbot.

**Settings:**
- Model selection (Opus 4.5, Sonnet, Haiku) or inherit from lesson
- System prompt override
- Allow model switching (boolean)
- Context files (relationship to context CPT)
- Skills (relationship to skills CPT)
- UI customization (height, placeholder text, etc.)

**Behavior:**
- Streams responses from Claude API
- Handles conversation history
- Logs interactions (optional)

### Context Library Module (`leaderspath/context-library`)

Displays context files associated with a lesson.

**Settings:**
- Source: Current lesson or manual selection
- Display format: List, cards, accordion
- Show descriptions (boolean)
- Allow download (boolean)
- Allow view content (boolean)

### Skills List Module (`leaderspath/skills-list`)

Displays skills associated with a lesson.

**Settings:**
- Source: Current lesson or manual selection
- Display format: List, cards
- Show documentation (boolean)
- Allow download (boolean)

### Lesson Meta Module (`leaderspath/lesson-meta`)

Displays lesson metadata.

**Settings:**
- Fields to display: Duration, objectives, model, prerequisites
- Layout: Inline, stacked, custom

## Claude API Integration

### Configuration

Stored in WordPress options:
- `leaderspath_claude_api_key` - Encrypted API key
- `leaderspath_default_model` - Default model (sonnet recommended)
- `leaderspath_max_tokens` - Default max response tokens
- `leaderspath_rate_limits` - Per-user rate limiting config

### Request Flow

```
User Input
    │
    ▼
┌─────────────────┐
│  Validate User  │ ← Check logged in, role permissions
└────────┬────────┘
         │
         ▼
┌─────────────────┐
│  Build Context  │ ← Gather lesson context files, skills
└────────┬────────┘
         │
         ▼
┌─────────────────┐
│ Assemble Prompt │ ← System prompt + context + user message
└────────┬────────┘
         │
         ▼
┌─────────────────┐
│   Claude API    │ ← Stream response
└────────┬────────┘
         │
         ▼
┌─────────────────┐
│ Return Response │ ← Via Server-Sent Events or chunked
└─────────────────┘
```

### Endpoints

**REST API:**
- `POST /wp-json/leaderspath/v1/chat` - Send message, receive response
- `GET /wp-json/leaderspath/v1/lesson/{id}/context` - Get lesson context files
- `GET /wp-json/leaderspath/v1/lesson/{id}/skills` - Get lesson skills

**Admin AJAX (fallback):**
- `wp_ajax_leaderspath_chat` - For non-REST environments

## Access Control

### Strategy

Use WordPress native capabilities with custom roles/caps:

**Capabilities:**
- `leaderspath_access_lessons` - Can view lesson content
- `leaderspath_access_chatbot` - Can use chatbot feature
- `leaderspath_view_context` - Can view context files
- `leaderspath_download_context` - Can download context files
- `leaderspath_manage_content` - Admin: manage lessons/courses

**Integration with Divi 5:**
- Modules check capabilities before rendering
- Provide "Access Denied" state for unauthorized users
- Hook into Divi's condition system if available

### Admin Settings

- Select which user roles have each capability
- Per-lesson access overrides (optional)
- Integration with WooCommerce customer role

## File Structure

```
leaderspath/
├── leaderspath.php              # Main plugin file, bootstrap
├── composer.json                # PHP dependencies
├── package.json                 # Node dependencies (Divi modules)
│
├── includes/
│   ├── class-leaderspath.php    # Main plugin class
│   ├── class-post-types.php     # CPT registration
│   ├── class-taxonomies.php     # Taxonomy registration
│   ├── class-capabilities.php   # Capabilities management
│   ├── class-api-handler.php    # Claude API wrapper
│   └── class-rest-api.php       # REST endpoints
│
├── modules/                     # Divi 5 modules (PHP)
│   ├── Chatbot/
│   │   ├── Chatbot.php
│   │   └── traits/
│   ├── ContextLibrary/
│   │   ├── ContextLibrary.php
│   │   └── traits/
│   ├── SkillsList/
│   │   ├── SkillsList.php
│   │   └── traits/
│   └── LessonMeta/
│       ├── LessonMeta.php
│       └── traits/
│
├── src/                         # Divi 5 modules (TypeScript/React)
│   └── components/
│       ├── chatbot/
│       │   ├── edit.tsx
│       │   ├── settings-content.tsx
│       │   ├── settings-design.tsx
│       │   ├── styles.tsx
│       │   ├── types.ts
│       │   └── module.json
│       ├── context-library/
│       ├── skills-list/
│       └── lesson-meta/
│
├── admin/
│   ├── class-admin.php          # Admin functionality
│   ├── class-settings.php       # Settings page
│   └── views/
│       └── settings.php
│
├── assets/
│   ├── css/
│   │   └── frontend.css
│   └── js/
│       └── chatbot.js           # Frontend chatbot JS
│
├── templates/                   # Template overrides (if needed)
│
├── languages/                   # Translation files
│
├── docs/                        # Documentation
│   ├── plugin-design.md
│   ├── cpt-schema.md
│   ├── api-reference.md
│   └── divi-modules.md
│
├── tests/                       # PHPUnit tests
│   ├── bootstrap.php
│   └── test-*.php
│
└── bin/                         # Build/utility scripts
    └── install-wp-tests.sh
```

## Dependencies

### Required Plugins

- **Advanced Custom Fields Pro** - Field management for CPTs
- **Divi 5** - Visual builder and module system

### Optional Plugins

- **WooCommerce** - For e-commerce integration (user roles on purchase)

### Composer Dependencies

- `anthropic/sdk` - Official Claude API SDK (if available) or custom implementation
- `monolog/monolog` - Logging (optional)

### npm Dependencies

- Divi 5 extension build tools
- TypeScript
- React (provided by Divi)

## Security Considerations

1. **API Key Storage** - Encrypt at rest, never expose to frontend
2. **Input Sanitization** - Sanitize all user inputs before API calls
3. **Rate Limiting** - Prevent API abuse per user/IP
4. **Nonce Verification** - All AJAX/REST requests verified
5. **Capability Checks** - Verify permissions on every request
6. **Output Escaping** - Escape all output, especially API responses
7. **Content Security Policy** - Consider CSP for chatbot iframe if used

## Performance Considerations

1. **Streaming Responses** - Use SSE for real-time chatbot responses
2. **Context Caching** - Cache assembled context per lesson
3. **Lazy Loading** - Load chatbot JS only when module present
4. **Database Queries** - Optimize relationship queries with proper indexing

## Future Considerations

- Analytics/tracking of lesson completion
- Conversation history persistence
- Multi-language support for lessons
- Webhooks for external integrations
- Bulk import/export of context files
- AI-assisted content creation tools

## Open Questions

1. Should conversation history persist across sessions?
2. What level of interaction logging is needed for analytics?
3. Should there be a "sandbox" mode for testing without API calls?
4. How should we handle API errors gracefully in the chatbot?
5. Do we need offline/fallback functionality?

## References

- [Divi 5 Module Examples](https://github.com/elegantthemes/d5-extension-example-modules)
- [Divi 5 Core Module Examples](https://github.com/elegantthemes/d5-example-core-modules)
- [Claude API Documentation](https://docs.anthropic.com/en/api/getting-started)
- [Agent Skills Specification](https://agentskills.io/home)
- [Interview Synthesis](./website-planning-interview-synthesis-2026-01-26.md)
