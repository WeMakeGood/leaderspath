=== LeadersPath ===
Contributors: christopherfrazier
Tags: learning, ai, chatbot, courses, lessons, claude, anthropic, divi
Requires at least: 6.4
Tested up to: 6.7
Requires PHP: 8.2
Stable tag: 0.1.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

AI-powered interactive learning platform with Claude chatbot integration for Divi 5.

== Description ==

LeadersPath is a WordPress plugin developed by Make Good (Managed Word, LLC) that powers an interactive AI learning community. The plugin provides chatbot-powered lesson experiences where learners interact with Claude AI to understand the difference between raw LLM interactions and context-enhanced AI implementations.

= Core Concept =

LeadersPath demonstrates the power of contextual AI by letting learners experience:

1. **Context Files** - Reference documents (guidelines, knowledge bases, standards) that provide Claude with background information
2. **Skills** - Executable AI capabilities that can run Python scripts and perform complex tasks
3. **Transparency** - Learners can view and download the exact context and skills being used by the chatbot

= How It Works =

**For Instructors:**

1. Create **Lessons** with learning objectives, duration, and chatbot configuration
2. Attach **Context Files** (markdown documents) that provide reference material for Claude
3. Attach **Skills** (ZIP packages) that give Claude executable capabilities
4. Configure chatbot settings: model selection, system prompt, temperature
5. Build lesson pages using Divi 5's Visual Builder with LeadersPath modules

**For Learners:**

1. Access lesson pages with embedded AI chatbot
2. Interact with Claude, enhanced by lesson-specific context and skills
3. View the Context Library to see what reference materials Claude has access to
4. View the Skills List to understand what capabilities Claude can use
5. Download context files and skill packages for transparency

= Custom Post Types =

**Lessons** (`leaderspath_lesson`)
The primary content unit. Contains learning objectives, duration, chatbot configuration, and relationships to context files and skills.

**Courses** (`leaderspath_course`)
Containers for organizing lessons into learning paths with difficulty levels and ordered lesson sequences.

**Cohorts** (`leaderspath_cohort`)
Groups of learners working through courses together on a schedule with assigned instructors.

**Context Files** (`leaderspath_context`)
Markdown documents embedded in Claude's system prompt. Types include: System Prompts, Knowledge Bases, Instructions, Guidelines, and Examples.

**Skills** (`leaderspath_skill`)
ZIP packages uploaded to Anthropic's Skills API. Enable Claude to execute Python scripts and perform complex tasks. Follow the Agent Skills Specification format.

= Divi 5 Modules =

**LeadersPath Chatbot**
Interactive chat interface connected to Claude API. Features:
- Rich text input with keyboard shortcuts (Ctrl+B for bold, Ctrl+I for italic, etc.)
- Full markdown rendering of responses (headers, lists, code blocks, links)
- Configurable styling for chat area, user bubbles, assistant bubbles
- Auto-scroll to show new messages at top of visible area
- Loading indicators and error handling

**LeadersPath Context Library**
Displays context files attached to the current lesson. Features:
- Responsive card grid layout
- View content modal to read full documents
- Download buttons for file export
- Configurable visibility for descriptions, badges, buttons

**LeadersPath Skills List**
Displays skills attached to the current lesson. Features:
- Responsive card grid layout
- Compatibility and version badges
- Download buttons for skill packages
- Configurable visibility for all elements

**LeadersPath Lesson Meta**
Displays lesson metadata: duration, learning objectives, AI model info.

= Claude API Integration =

LeadersPath uses Anthropic's **container-based API with code execution**:

**Context Files:**
- Stored in WordPress as post_content
- Embedded directly in Claude's system prompt
- No API upload required - pure text injection

**Skills:**
- Uploaded to Anthropic's Skills API as ZIP packages
- Referenced by skill_id in chat requests
- Enable Python script execution in sandboxed containers
- Follow Agent Skills Specification (SKILL.md with YAML frontmatter)

**Conversation Flow:**
1. User sends message via chatbot
2. System prompt assembled: custom prompt + lesson content + context files + skill metadata
3. Request sent to Claude with container and code execution enabled
4. Response streamed back and rendered as markdown

= Requirements =

* WordPress 6.4+
* PHP 8.2+
* Divi 5 (required for Visual Builder modules)
* Advanced Custom Fields Pro (required for custom fields)
* Anthropic Claude API key

= Optional Integrations =

* WooCommerce - For e-commerce and user role assignment on purchase

== Installation ==

1. Upload the `leaderspath` folder to `/wp-content/plugins/`
2. Install and activate required plugins: Divi 5, Advanced Custom Fields Pro
3. Activate LeadersPath through the 'Plugins' menu
4. Navigate to Settings > LeadersPath
5. Enter your Anthropic Claude API key
6. Click "Test Connection" to verify API access
7. Configure default model and other settings

= First Steps =

1. Create a **Context File**: Add → Context Files → New. Write markdown content that you want Claude to reference.
2. Create a **Lesson**: Add → Lessons → New. Fill in learning objectives, enable chatbot, attach your context file.
3. Build a lesson page using Divi's Visual Builder. Add the LeadersPath Chatbot module.
4. Test the chatbot - it will have access to your context file.

== Frequently Asked Questions ==

= Does this plugin require Divi? =

Yes. LeadersPath is built specifically for Divi 5 and uses custom Divi modules for the interactive learning experience. The modules will not appear without Divi 5 active.

= What AI model does this use? =

LeadersPath integrates with Anthropic's Claude API. Supported models include Claude Opus 4.5, Claude Sonnet, and Claude Haiku. You choose the default model in settings, and can override per-lesson.

= Do I need my own API key? =

Yes. You need an Anthropic API key from https://console.anthropic.com/. LeadersPath does not include API credits - you pay Anthropic directly for usage.

= What's the difference between Context Files and Skills? =

**Context Files** are reference documents (markdown) that are embedded in Claude's system prompt. They provide background knowledge but don't enable new capabilities.

**Skills** are executable packages (ZIP files) that are uploaded to Anthropic and can run Python scripts. They give Claude new capabilities like data processing, file generation, or API calls.

= How are Skills structured? =

Skills follow the Agent Skills Specification. A skill package is a ZIP containing:
- `SKILL.md` - Required. Instructions with YAML frontmatter (name, description)
- `references/` - Optional. Additional documentation
- `scripts/` - Optional. Python scripts to execute
- `assets/` - Optional. Templates, images, data files

= Is conversation history saved? =

No. By design, conversation history is cleared on page reload. This enables learners to experiment freely without worrying about previous messages affecting new interactions.

= Can I customize the chatbot appearance? =

Yes. The Chatbot module has extensive styling options in Divi's Design tab:
- Chat area background, sizing, borders
- User bubble colors, fonts, spacing
- Assistant bubble colors, fonts, spacing
- Input field styling
- Send button styling

== Screenshots ==

1. Chatbot module in lesson page
2. Context Library showing attached reference documents
3. Skills List displaying available AI capabilities
4. Lesson settings with chatbot configuration
5. Context File editor
6. Skill package upload and sync

== Changelog ==

= 0.1.0 =
* Initial release
* 5 Custom Post Types: Lessons, Courses, Cohorts, Context Files, Skills
* 3 Taxonomies: Topics, Context Categories, Skill Categories
* 4 Divi 5 Modules: Chatbot, Context Library, Skills List, Lesson Meta
* Claude API integration with container and code execution support
* Skills API integration for executable skill packages
* Rich text input with TinyMCE (keyboard shortcuts, no toolbar)
* Markdown rendering with marked.js (GitHub Flavored Markdown)
* Admin settings page with API key encryption
* Custom capabilities and student role
* REST API endpoints for chat and content access

== Upgrade Notice ==

= 0.1.0 =
Initial release. Requires Divi 5 and ACF Pro.

== Technical Documentation ==

For developers and content creators, full documentation is available in the plugin's `docs/` folder:

* `plugin-design.md` - Architecture overview and design decisions
* `cpt-schema.md` - Custom post types, taxonomies, and ACF field definitions
* `claude-api-integration.md` - Claude API integration details
* `divi-modules.md` - Divi 5 module development guide
* `content-creation-guide.md` - Guide for creating lessons, context files, and skills

== Credits ==

Developed by Make Good (Managed Word, LLC)
https://wemakegood.org

Powered by Anthropic's Claude API
https://www.anthropic.com
