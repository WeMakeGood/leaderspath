# LeadersPath

AI-powered interactive learning platform with Claude chatbot integration for WordPress and Divi 5.

**Version:** 0.2.0
**Requires:** WordPress 6.4+, PHP 8.2+, Divi 5, ACF Pro
**License:** GPLv2 or later

---

## Overview

LeadersPath is a WordPress plugin that powers an interactive AI learning community. It supports **facilitated cohort learning** where learners interact with Claude AI chatbots that have been enhanced with context files and executable skills.

### Key Concept

LeadersPath is a **facilitated cohort learning experience**, not a self-paced lesson platform:

| Component | Purpose |
|-----------|---------|
| **Course** | The atomic teaching unit, taught as a cohesive whole by a facilitator |
| **Activity** | An AI sandbox experiment within a Course (what learners DO, not what they LEARN) |
| **Facilitator Guide** | The central teaching document (what to present, when to run activities, discussion prompts) |
| **Course Q&A Bot** | Optional helpful assistant for answering questions about course content |

The facilitator presents concepts, learners experiment in AI sandboxes (Activities), and discussion happens human-to-human in the cohort.

### Key Features

- **5 Custom Post Types:** Activities, Courses, Cohorts, Context Files, Skills
- **4 Divi 5 Modules:** Chatbot, Context Library, Skills List, Activity Meta
- **Dual Chatbot Modes:** Activity sandboxes (demonstrate behaviors) + Course Q&A (answer questions)
- **Claude API Integration:** Container-based API with code execution and skills support
- **Transparency:** Learners can view and download the exact context and skills powering the AI

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
│  │  │Activities│ │ Courses  │ │ Cohorts  │ │ Context │ │Skills│ ││
│  │  └──────────┘ └──────────┘ └──────────┘ └─────────┘ └─────┘ ││
│  └─────────────────────────────────────────────────────────────┘│
│                                                                  │
│  ┌──────────────────────┐  ┌──────────────────────────────────┐ │
│  │    Claude API        │  │       Divi 5 Modules             │ │
│  │  ┌────────────────┐  │  │  ┌──────────┐  ┌──────────────┐  │ │
│  │  │ Messages API   │  │  │  │ Chatbot  │  │Context Library│  │ │
│  │  │ + Container    │  │  │  └──────────┘  └──────────────┘  │ │
│  │  │ + Code Exec    │  │  │  ┌──────────┐  ┌──────────────┐  │ │
│  │  └────────────────┘  │  │  │Skills List│  │Activity Meta │  │ │
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

**Add > Activities > Context Files > New**

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

### 2. Create an Activity

**Add > Activities > New**

Activities are AI sandbox experiments. Configure:
- Title: What learners will experience (e.g., "Experience Sycophantic AI")
- Content: "Try this, notice that" instructions for learners
- AI Sandbox Configuration: System prompt defining the AI behavior
- Attach context files and skills as needed

### 3. Create a Course

**Add > Courses > New**

Courses are the teaching unit:
- Add learning objectives (what learners will achieve)
- Write a facilitator guide (teaching script with timing and discussion prompts)
- Add activities in order
- Optionally enable a Course Q&A chatbot

### 4. Build Pages with Divi

Use Divi's Visual Builder to add LeadersPath modules:

- **LeadersPath Chatbot** - Works on both Activity pages (sandbox) and Course pages (Q&A)
- **LeadersPath Context Library** - Shows attached context files
- **LeadersPath Skills List** - Shows available skills
- **LeadersPath Activity Meta** - Displays duration and AI model info

---

## Divi 5 Modules

### LeadersPath Chatbot

Interactive chat interface connected to Claude API.

**Two Modes:**
- **Activity Sandbox** (on Activity pages): AI configured to demonstrate specific behaviors
- **Course Q&A** (on Course pages): Helpful assistant for answering questions

**Features:**
- Rich text input with keyboard shortcuts (Ctrl+B, Ctrl+I, etc.)
- Full markdown rendering (headers, lists, code blocks, links)
- Configurable styling for chat area, user bubbles, assistant bubbles
- Auto-scroll to show new messages at top of visible area
- Loading indicators and error handling

### LeadersPath Context Library

Displays context files attached to the current activity.

**Features:**
- Responsive card grid layout
- View content modal to read full documents
- Download buttons for file export
- Configurable visibility for descriptions, badges, buttons

### LeadersPath Skills List

Displays skills attached to the current activity.

**Features:**
- Responsive card grid layout
- Compatibility and version badges
- Download buttons for skill packages

### LeadersPath Activity Meta

Displays activity metadata: duration and AI model info.

**Note:** Learning objectives are now displayed at the Course level, not Activity level.

---

## Chatbot Configuration: Activity vs Course

| Aspect | Activity Sandbox | Course Q&A Bot |
|--------|------------------|----------------|
| **Purpose** | Demonstrate specific AI behavior | Answer questions about content |
| **System Prompt** | Crafted to show specific behavior | Helpful, knowledgeable assistant |
| **Context** | Activity-specific files | All course content |
| **Tone** | Varies by activity design | Consistently helpful |
| **Placement** | Activity page | Course page |
| **Privacy** | Complete sandbox (no logging) | Complete sandbox (no logging) |

---

## Claude API Integration

LeadersPath uses Anthropic's container-based API with code execution support.

### System Prompt Assembly

For Activities:
```
[Custom System Prompt OR Default]

--- Reference Materials ---
### [Context File 1 Title]
[Context File 1 Full Content]

--- Available Skills ---
### [Skill 1 Name]
[Skill 1 Description]
```

For Course Q&A:
```
[Custom Q&A System Prompt OR Default Helpful Assistant]

--- Course Learning Objectives ---
1. [Objective 1]
2. [Objective 2]

--- Course Overview ---
[Learner Overview Content]

--- Reference Materials ---
### [Context File 1 Title]
[Context File 1 Full Content]
```

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
| [content-creation-guide.md](docs/content-creation-guide.md) | Guide for creating activities, context files, and skills |

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

### 0.2.0

- **Breaking:** Renamed "Lessons" to "Activities" throughout the UI
- **Breaking:** Moved learning objectives from Activity to Course level
- Added Course Q&A chatbot (optional helpful assistant)
- Added Course facilitator guide field
- Added Course learner overview field
- Updated Chatbot module to support both Activity and Course modes
- REST API now supports `course_id` parameter for Course Q&A
- Updated documentation to reflect facilitated learning model

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
