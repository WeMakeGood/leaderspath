# LeadersPath

AI-powered interactive learning platform with Claude chatbot integration for WordPress and Divi 5.

**Version:** 0.1.0
**Requires:** WordPress 6.4+, PHP 8.2+, Divi 5, ACF Pro
**License:** GPLv2 or later

---

## Overview

LeadersPath is a WordPress plugin that powers an interactive AI learning community. Learners interact with Claude AI chatbots that have been enhanced with lesson-specific context files and executable skills, demonstrating the difference between raw LLM interactions and context-enhanced AI implementations.

### Key Features

- **5 Custom Post Types:** Lessons, Courses, Cohorts, Context Files, Skills
- **4 Divi 5 Modules:** Chatbot, Context Library, Skills List, Lesson Meta
- **Claude API Integration:** Container-based API with code execution and skills support
- **Transparency:** Learners can view and download the exact context and skills powering the AI

---

## Core Concept

LeadersPath teaches AI concepts through hands-on experience:

| Component | Purpose | Storage |
|-----------|---------|---------|
| **Context Files** | Reference documents (guidelines, knowledge bases) that provide Claude with background information | WordPress only - embedded in system prompt |
| **Skills** | Executable capabilities (Python scripts, workflows) that give Claude new abilities | Uploaded to Anthropic Skills API |

Learners see exactly what context and skills are available to the chatbot, making the AI's behavior transparent and educational.

---

## Architecture

```
┌─────────────────────────────────────────────────────────────────┐
│                         WordPress                                │
├─────────────────────────────────────────────────────────────────┤
│  LeadersPath Plugin                                              │
│                                                                  │
│  ┌─────────────────────────────────────────────────────────────┐│
│  │                   Custom Post Types                          ││
│  │  ┌──────────┐ ┌──────────┐ ┌──────────┐ ┌─────────┐ ┌─────┐ ││
│  │  │ Lessons  │ │ Courses  │ │ Cohorts  │ │ Context │ │Skills│ ││
│  │  └──────────┘ └──────────┘ └──────────┘ └─────────┘ └─────┘ ││
│  └─────────────────────────────────────────────────────────────┘│
│                                                                  │
│  ┌──────────────────────┐  ┌──────────────────────────────────┐ │
│  │    Claude API        │  │       Divi 5 Modules             │ │
│  │  ┌────────────────┐  │  │  ┌──────────┐  ┌──────────────┐  │ │
│  │  │ Messages API   │  │  │  │ Chatbot  │  │Context Library│  │ │
│  │  │ + Container    │  │  │  └──────────┘  └──────────────┘  │ │
│  │  │ + Code Exec    │  │  │  ┌──────────┐  ┌──────────────┐  │ │
│  │  └────────────────┘  │  │  │Skills List│  │ Lesson Meta  │  │ │
│  │  ┌────────────────┐  │  │  └──────────┘  └──────────────┘  │ │
│  │  │  Skills API    │  │  └──────────────────────────────────┘ │
│  │  └────────────────┘  │                                       │
│  └──────────────────────┘                                       │
└─────────────────────────────────────────────────────────────────┘
```

---

## Installation

### Requirements

- WordPress 6.4+
- PHP 8.2+
- [Divi 5](https://www.elegantthemes.com/gallery/divi/) (required for Visual Builder modules)
- [Advanced Custom Fields Pro](https://www.advancedcustomfields.com/pro/) (required for custom fields)
- [Anthropic Claude API key](https://console.anthropic.com/)

### Steps

1. Upload the `leaderspath` folder to `/wp-content/plugins/`
2. Install and activate required plugins: Divi 5, Advanced Custom Fields Pro
3. Activate LeadersPath through the Plugins menu
4. Navigate to **Settings > LeadersPath**
5. Enter your Anthropic Claude API key
6. Click "Test Connection" to verify API access

---

## Quick Start

### 1. Create a Context File

**Add > Context Files > New**

Write markdown content that you want Claude to reference:

```markdown
# Brand Voice Guidelines

## Tone
- Professional but approachable
- Clear and concise
- Encouraging without being patronizing

## Key Messages
- We help learners understand AI through hands-on experience
- Transparency in AI is educational and empowering
```

### 2. Create a Lesson

**Add > Lessons > New**

- Fill in learning objectives and duration
- Enable the chatbot
- Attach your context file
- Optionally write a custom system prompt

### 3. Build the Lesson Page

Use Divi's Visual Builder to add LeadersPath modules:

- **LeadersPath Chatbot** - The interactive chat interface
- **LeadersPath Context Library** - Shows attached context files
- **LeadersPath Skills List** - Shows available skills
- **LeadersPath Lesson Meta** - Displays duration, objectives, model info

### 4. Test

Visit the lesson page and chat with the AI. It will have access to your context file.

---

## Divi 5 Modules

### LeadersPath Chatbot

Interactive chat interface connected to Claude API.

**Features:**
- Rich text input with keyboard shortcuts (Ctrl+B, Ctrl+I, etc.)
- Full markdown rendering (headers, lists, code blocks, links)
- Configurable styling for chat area, user bubbles, assistant bubbles
- Auto-scroll to show new messages at top of visible area
- Loading indicators and error handling

### LeadersPath Context Library

Displays context files attached to the current lesson.

**Features:**
- Responsive card grid layout
- View content modal to read full documents
- Download buttons for file export
- Configurable visibility for descriptions, badges, buttons

### LeadersPath Skills List

Displays skills attached to the current lesson.

**Features:**
- Responsive card grid layout
- Compatibility and version badges
- Download buttons for skill packages

### LeadersPath Lesson Meta

Displays lesson metadata: duration, learning objectives, AI model info.

---

## Claude API Integration

LeadersPath uses Anthropic's container-based API with code execution support.

### System Prompt Assembly

```
[Custom System Prompt OR Default]

--- Reference Materials ---
### [Context File 1 Title]
[Context File 1 Full Content]

### [Context File 2 Title]
[Context File 2 Full Content]

--- Available Skills ---
### [Skill 1 Name]
[Skill 1 Description]
```

**Note:** The lesson's page content (what learners see) is NOT included in the system prompt. Only the Custom System Prompt field and attached Context Files define Claude's behavior.

### Context Files vs Skills

| Aspect | Context Files | Skills |
|--------|---------------|--------|
| **Purpose** | Reference material | Executable capabilities |
| **Storage** | WordPress only | Anthropic Skills API |
| **Delivery** | Embedded in system prompt | Loaded by container on-demand |
| **Code execution** | No | Yes (Python, bash) |
| **API upload** | Not required | Required |

---

## Documentation

Full documentation is available in the [`docs/`](docs/) folder:

| Document | Description |
|----------|-------------|
| [plugin-design.md](docs/plugin-design.md) | Architecture overview and design decisions |
| [cpt-schema.md](docs/cpt-schema.md) | Custom post types, taxonomies, and ACF fields |
| [claude-api-integration.md](docs/claude-api-integration.md) | Claude API integration details |
| [divi-modules.md](docs/divi-modules.md) | Divi 5 module development guide |
| [content-creation-guide.md](docs/content-creation-guide.md) | Guide for creating lessons, context files, and skills |

---

## Development

### Prerequisites

```bash
# PHP dependencies
composer install

# Node dependencies (for Divi modules)
npm install
```

### Build Commands

```bash
npm run build    # Production build
npm run start    # Development with watch
```

### File Structure

```
leaderspath/
├── leaderspath.php          # Main plugin file
├── includes/                # Core PHP classes
│   ├── class-post-types.php
│   ├── class-claude-api.php
│   ├── class-rest-api.php
│   └── ...
├── modules/                 # Divi 5 PHP modules
│   ├── Chatbot/
│   ├── ContextLibrary/
│   ├── SkillsList/
│   └── LessonMeta/
├── src/                     # Divi 5 TypeScript/React
│   └── components/
├── assets/                  # Frontend CSS/JS
├── admin/                   # Admin functionality
└── docs/                    # Documentation
```

---

## Changelog

### 0.1.0

- Initial release
- 5 Custom Post Types: Lessons, Courses, Cohorts, Context Files, Skills
- 3 Taxonomies: Topics, Context Categories, Skill Categories
- 4 Divi 5 Modules: Chatbot, Context Library, Skills List, Lesson Meta
- Claude API integration with container and code execution support
- Skills API integration for executable skill packages
- Rich text input with TinyMCE (keyboard shortcuts, no toolbar)
- Markdown rendering with marked.js (GitHub Flavored Markdown)
- Admin settings page with API key encryption
- Custom capabilities and student role
- REST API endpoints for chat and content access

---

## Credits

Developed by [Make Good](https://wemakegood.org) (Managed Word, LLC)

Powered by [Anthropic's Claude API](https://www.anthropic.com)

---

## License

GPLv2 or later. See [LICENSE](LICENSE) for details.
