# LeadersPath Development Tasks

**Last Updated:** 2026-02-04
**Current Phase:** Phase 6 - Polish & Testing

This file tracks all development tasks across Claude Code sessions. Each session should read this file at startup and update it when tasks are completed or new tasks are discovered.

---

## Task Status Legend

- `[ ]` - Not started
- `[~]` - In progress
- `[x]` - Completed
- `[!]` - Blocked (see notes)

---

## Phase 1: Foundation (COMPLETED)

- [x] Create plugin scaffolding
- [x] Set up documentation structure
- [x] Create plugin design document
- [x] Define CPT schema
- [x] Research Divi 5 module API
- [x] Set up build system (webpack, composer, npm)
- [x] Create test module (Hello Module) to verify Divi 5 integration
- [x] Test module in Visual Builder - CONFIRMED WORKING

---

## Phase 2: Data Layer (COMPLETED)

Building inside-out: schemas → CPTs → taxonomies → ACF fields

### Custom Post Types
- [x] Create `includes/class-post-types.php`
- [x] Register `leaderspath_activity` CPT
- [x] Register `leaderspath_course` CPT
- [x] Register `leaderspath_cohort` CPT
- [x] Register `leaderspath_context` CPT (context files)
- [x] Register `leaderspath_skill` CPT

### Taxonomies
- [x] Create `includes/class-taxonomies.php`
- [x] Register `leaderspath_topic` taxonomy (for lessons, courses)
- [x] Register `leaderspath_context_cat` taxonomy (for context files)
- [x] Register `leaderspath_skill_cat` taxonomy (for skills)
- [x] Create default terms on activation

### ACF Field Groups (Programmatic via ACF Pro API)
- [x] Create `includes/class-acf-fields.php`
- [x] Register Lesson Settings field group
- [x] Register Lesson Chatbot Configuration field group
- [x] Register Course Settings field group
- [x] Register Cohort Settings field group
- [x] Register Context File Settings field group
- [x] Register Skill Settings field group

### Capabilities & Roles
- [x] Create `includes/class-capabilities.php`
- [x] Define custom capabilities for each CPT
- [x] Create `leaderspath_student` role
- [x] Map capabilities to Administrator, Editor roles
- [x] Add capability checks to CPT registration

---

## Phase 3: Admin Interface (COMPLETED)

### Settings Page
- [x] Create `admin/class-settings.php`
- [x] Add settings page under Settings menu
- [x] Claude API key field (encrypted storage)
- [x] Default model selection
- [x] Debug/logging toggle

### Admin Enhancements
- [x] Custom admin columns for Lessons (duration, course, chatbot status)
- [x] Custom admin columns for Courses (lesson count, difficulty, total duration)
- [x] Quick edit support (lesson duration, chatbot enabled, course difficulty)
- [x] Admin notices for missing API key

---

## Phase 4: REST API & Claude Integration (COMPLETED)

**Reference:** See `docs/claude-api-integration.md` for full API documentation.

### REST API Endpoints
- [x] Create `includes/class-rest-api.php`
- [x] `POST /wp-json/leaderspath/v1/chat` - Send message to Claude
- [x] `GET /wp-json/leaderspath/v1/lessons/{id}/context` - Get lesson context files
- [x] `GET /wp-json/leaderspath/v1/lessons/{id}/skills` - Get lesson skills
- [x] `GET /wp-json/leaderspath/v1/context/{id}/download` - Download context file
- [x] `GET /wp-json/leaderspath/v1/skills/{id}/download` - Download skill definition
- [x] Implement authentication (nonce for logged-in, capability checks)
- [x] Update chat endpoint for container API response format (container_id in response)
- [x] Handle pause_turn responses (continuation loop in Claude_API)
- [ ] Handle file outputs from code execution (deferred - not critical for MVP)

### Claude API Handler (COMPLETED)
- [x] Create `includes/class-claude-api.php`
- [x] Implement Messages API integration with container + code execution
- [x] Context assembly from lesson files
- [x] Model selection (dynamic from API, with fallbacks)
- [x] Error handling and logging
- [x] Test connection button in settings (queries available models)
- [x] Add container parameter with skills array
- [x] Add code execution tool to requests
- [x] Handle pause_turn stop reason (with continuation loop)
- [x] Implement container reuse within session (container_id parameter)
- [ ] Optional: Streaming response support (SSE)

### Skill Library Handler (COMPLETED)
Skills must be uploaded to Anthropic's Skills API for code execution to work.
Context files remain WordPress-only (embedded in system prompt).

- [x] Create `includes/class-skill-processor.php` (local validation + Anthropic upload)
- [x] Add `skill_anthropic_id` ACF field (stores returned skill_id)
- [x] Add `skill_anthropic_version` ACF field (stores version timestamp)
- [x] Add `skill_sync_status` ACF field (pending/synced/error)
- [x] Add `skill_sync_error` ACF field (last error message)
- [x] Add `skill_last_synced` ACF field (timestamp)
- [x] Implement `upload_to_anthropic()` method in Skill_Processor
- [x] Upload skill ZIP to `POST /v1/skills` on first save
- [x] Create new version via `POST /v1/skills/{id}/versions` on update
- [x] Store returned skill_id and version in ACF fields
- [x] Add admin notice for sync status (success/error)
- [x] Add "Re-sync" button for failed uploads with AJAX handler
- [ ] Handle skill deletion (delete from Anthropic when trashed?)

### Settings Updates (COMPLETED)
- [x] Add beta header version fields (configurable, not hardcoded)
- [x] Add tool type version field
- [x] Static helper methods: `get_beta_headers()`, `get_code_execution_tool_type()`
- [ ] Add "Test Skills API" button (deferred - connection test already exists)

---

## Phase 5: Divi 5 Modules (Frontend) (COMPLETED)

Now that data layer exists, build the UI modules.

**Reference:** See `docs/divi-modules.md` for Divi 5 module development guide.
**Working Examples:**
- `modules/LessonMeta/` - Theme Builder pattern (ACF data from current lesson)
- `modules/ContextLibrary/` - Theme Builder pattern with compound elements and modal
- `modules/SkillsList/` - Theme Builder pattern (similar to Context Library, no modal)
- `modules/Chatbot/` - Theme Builder pattern with interactive frontend JavaScript

### Chatbot Module (COMPLETED)
- [x] Create PHP module class and traits
- [x] Create TypeScript/React edit component
- [x] Create module.json with settings schema
- [x] Implement chat UI (bubbles, input, send button)
- [x] Add styling options (colors, fonts, sizing)
- [x] Connect to REST API endpoint
- [x] Frontend JavaScript for real-time chat interaction

### Context Library Module (COMPLETED)
- [x] Create PHP module class and traits (Theme Builder pattern)
- [x] Create TypeScript/React edit component with proper types
- [x] Implement responsive card grid layout
- [x] Add view content modal (custom modal, Divi lightbox is image-only)
- [x] Add download functionality (links to REST endpoint)
- [x] Visibility toggles for description, file type badge, buttons
- [x] Configurable empty state message (rich text)
- [x] Button elements using Divi button elementType for styling consistency

### Skills List Module (COMPLETED)
- [x] Create PHP module class and traits (Theme Builder pattern)
- [x] Create TypeScript/React edit component with proper types
- [x] Implement responsive card grid layout (same as Context Library)
- [x] Visibility toggles for description, compatibility badge, version badge, download button
- [x] Download links directly to attachment URL (ZIP package)
- [x] Configurable empty state message (rich text)
- [x] Button element using Divi button elementType for styling consistency

### Lesson Meta Module (REWRITTEN)
- [x] Create PHP module class and traits (following PostTitle pattern)
- [x] Create TypeScript/React edit component (with StyleContainer)
- [x] Display duration, objectives, model info
- [x] Add visibility toggles and configurable labels in Content tab
- [x] Use Style::add() wrapper in PHP
- [x] Use toggle attributes in element.advanced.show pattern (like PostTitle)

---

## Phase 6: Polish & Testing

- [ ] Write PHPUnit tests for CPTs, taxonomies, API
- [ ] Write Jest tests for React components
- [ ] Security audit (input sanitization, capability checks, nonces)
- [ ] Performance optimization
- [ ] User documentation
- [ ] Code documentation cleanup

---

## Discovered Tasks

Tasks discovered during development that need to be addressed:

### Skill Package Processing (COMPLETED)
- [x] Create `includes/class-skill-processor.php`
- [x] Hook into `acf/save_post` for `leaderspath_skill` post type
- [x] Validate uploaded ZIP structure (must contain SKILL.md)
- [x] Parse YAML frontmatter from SKILL.md (name, description, compatibility)
- [x] Auto-populate read-only ACF fields from frontmatter
- [x] Display admin error notice if ZIP validation fails

**Implementation notes:**
- Uses `symfony/yaml` for YAML parsing (added to composer.json)
- Validates according to Agent Skills Spec (name: lowercase/hyphens, 1-64 chars; description: 1-1024 chars)
- Handles both root-level and subdirectory SKILL.md in ZIP files
- Errors stored in transients and displayed as admin notices

---

## Decisions Log

Key decisions made during development:

| Date | Decision | Rationale |
|------|----------|-----------|
| 2026-01-29 | Use CPTs for Context Files and Skills | WordPress revision history, familiar admin UI, ACF integration |
| 2026-01-29 | No conversation persistence | Fresh start on page reload enables experimentation |
| 2026-01-29 | Single plugin-wide API key | Simpler management, billing handled at org level |
| 2026-01-29 | No rate limiting initially | Paid service, trust users until abuse occurs |
| 2026-01-29 | Streaming optional, not required | Nice UX but adds complexity, implement if time permits |
| 2026-01-29 | Standard chat bubble UI | Recognizable pattern, configurable colors for branding flexibility |
| 2026-01-29 | Inline error display | Keep errors in context of conversation |
| 2026-01-29 | Inside-out development order | Build data layer first (CPTs, ACF), then admin, then frontend modules |
| 2026-01-29 | ACF fields via PHP API | Programmatic registration for version control, but uses ACF Pro UI/rendering |
| 2026-01-29 | Skills as ZIP packages | Skills follow Agent Skills Spec - directories with SKILL.md, references/, scripts/, assets/. Upload ZIP, extract frontmatter for metadata. |
| 2026-02-03 | Facilitated cohort learning model | LeadersPath is facilitator-led, not self-paced. Course = atomic unit, Activities = AI sandboxes |
| 2026-02-03 | Rename Lessons to Activities | Full migration to `leaderspath_activity` CPT slug |
| 2026-02-03 | Learning objectives at Course level | Moved from Activity to Course since Course is the teaching unit |
| 2026-02-03 | Dual chatbot modes | Activity Sandbox (demonstrate behaviors) vs Course Q&A (helpful assistant) |
| 2026-02-03 | Privacy-first Q&A bot | No logging, no access restrictions - maintains sandbox trust |

---

## Session Notes

Brief notes from each development session:

### Session 1 (2026-01-29)
- Reviewed interview synthesis document
- Created comprehensive documentation (plugin-design.md, cpt-schema.md, divi-modules.md)
- Updated CLAUDE.md with development guidelines
- Built and tested Hello Module - confirmed Divi 5 integration works
- Set up persistent task tracking system
- Updated brand name to LeadersPath (one word)

### Session 2 (2026-01-29)
- Completed Phase 2: Data Layer
  - Created `includes/class-post-types.php` with 5 CPTs (Lesson, Course, Cohort, Context, Skill)
  - Created `includes/class-taxonomies.php` with 3 taxonomies + default terms
  - Created `includes/class-capabilities.php` with roles and capabilities
  - Created `includes/class-acf-fields.php` with 6 field groups
- Updated Skill CPT to use ZIP packages (Agent Skills Spec format)
  - Fields: skill_package (file), skill_name, skill_description, skill_compatibility (read-only), skill_version, skill_notes
  - Added Skill Package Processing to Discovered Tasks (blocking - needs `acf/save_post` hook)
- Started Phase 3: Admin Interface
  - Created `admin/class-settings.php` with encrypted API key storage, model selection, debug toggle
  - Verified API key encryption/decryption working
- Next: Admin Enhancements (custom columns for Lessons/Courses)
- Added proper licensing (GPL-2.0-or-later) and authorship info
- Reorganized task order: inside-out development (data → admin → frontend)

### Session 3 (2026-01-29)
- Completed Phase 3: Admin Interface
  - Created `admin/class-admin-columns.php` with custom columns for Lessons and Courses
  - Lesson columns: Course (with edit links), Duration (sortable), Chatbot status (enabled + model)
  - Course columns: Lesson count, Difficulty (color-coded), Total Duration (calculated from lessons)
  - Quick edit support for: lesson duration, chatbot enabled, course difficulty
  - All columns include proper escaping, accessibility, and internationalization
- Completed Phase 4: REST API & Claude Integration
  - Created `includes/class-rest-api.php` with all endpoints (chat, context, skills, downloads)
  - Created `includes/class-claude-api.php` with Messages API integration
  - Dynamic model resolution: queries `/v1/models` endpoint, caches for 24h, falls back to known IDs
  - Context assembly: system prompt + lesson content + context files + skill definitions
  - Added "Test Connection" button to settings page (AJAX, shows model count)
  - Full authentication: nonce verification, capability checks, logged-in requirement
- Created test data script: `bin/create-test-data.php` (run via `wp eval-file`)
  - 3 Context Files, 2 Skills, 4 Lessons, 2 Courses, 1 Cohort
  - All REST endpoints verified working
- Remaining: Optional streaming support (SSE) - deferred
- **Next: Phase 5 - Divi 5 Modules (start with Chatbot Module)**

### Session 4 (2026-01-29)
- Started Phase 5: Divi 5 Modules (Frontend)
- Completed Lesson Meta Module
  - PHP: `modules/LessonMeta/LessonMeta.php` with 3 traits (RenderCallback, ModuleClassnames, ModuleStyles)
  - TypeScript: `src/components/lesson-meta/` with edit component, module.json, styles
  - Features: Display duration, learning objectives, AI model (from ACF fields)
  - Content tab: Title text, visibility toggles (show/hide each section), configurable labels
  - Design tab: Standard Divi decoration (spacing, borders, fonts)
  - VB shows placeholder content; frontend renders actual ACF data
- Key learnings documented:
  - **Theme Builder:** Must use `get_queried_object_id()` not `get_the_ID()` for post ID
  - **Null checks:** Always use `?? ''` for `$args['selector']` in ModuleStylesTrait
  - **Toggle values:** Return `'on'`/`'off'` strings, not booleans
  - **Attribute path:** `$attrs['name']['innerContent']['desktop']['value']`
- Rewrote `docs/divi-modules.md` with accurate Divi 5 patterns from working code
- Build and frontend render verified working
- **Next: Context Library Module or Skills List Module**

### Session 5 (2026-01-29)
- **IMPORTANT: Divi 5 documentation overhaul**
- Previous documentation was inaccurate/incomplete, leading to failed module builds
- Researched and documented proper reference sources:
  1. **Official Example Repo:** https://github.com/elegantthemes/d5-extension-example-modules
     - Clone to `/private/tmp/d5-extension-example-modules/`
     - Contains 5 example modules: Static, Dynamic, Parent, Child, D4
  2. **Divi Core Modules:** `/wp-content/themes/Divi/includes/builder-5/`
     - `server/Packages/ModuleLibrary/` - PHP module classes
     - `visual-builder/packages/module-library/src/components/` - TypeScript/JSON
- Key patterns learned from studying Blog module (`module.json`):
  - **Toggles for show/hide** go in Content tab under "Elements" group (`groupSlug: "contentElements"`)
  - **Styling repeated items** requires separate attributes with specific selectors
  - **Settings organization:** `panel: "content"` vs `panel: "design"` in group definitions
- Rewrote `docs/divi-modules.md` as a bootstrap guide pointing to references, NOT summaries
- **Next session MUST:** Read `docs/divi-modules.md` and clone example repo before coding

### Session 6 (2026-01-29)
- **Deep dive into Divi 5 module architecture**
- Identified three distinct module patterns:
  1. **Theme Builder Module** (PostTitle, PostContent) - displays current post data via `get_queried_object_id()`
  2. **Dynamic Query Module** (Blog, Portfolio) - queries multiple posts via REST controller
  3. **Static Module** - user-entered content only
- **Context Library should use Theme Builder pattern** - it displays data from the current lesson
- Surveyed all 70+ core Divi modules to understand available patterns
- Key discoveries:
  - Core modules only ship JSON (no TypeScript source) - use example repo for TS patterns
  - `PostTitleModule.php` is the best reference for Theme Builder modules
  - REST controllers are registered centrally in `RESTRegistration.php`, not in module classes
  - `module.json` is the authoritative source for attributes, settings, and styling options
- Documented where to find specific implementations:
  - Field types (text, toggle, select, color picker)
  - Styling options (font, spacing, border, background)
  - PHP patterns (render callback, styles, classnames)
  - TypeScript patterns (edit.tsx, styles.tsx, types.ts)
- **Completely rewrote `docs/divi-modules.md`** as a pure reference guide:
  - Points to specific files for each pattern
  - No summaries or code examples that could drift from source
  - Clear "start with core modules, then example repo" hierarchy
- **Next: Build Context Library module using Theme Builder pattern**

### Session 7 (2026-01-30)
- **Rewrote LessonMeta module from scratch** following correct patterns
- Identified antipatterns in previous implementation:
  - Missing `Style::add()` wrapper in PHP ModuleStylesTrait
  - Missing `StyleContainer` wrapper in TypeScript styles.tsx
  - Toggle attributes at wrong level (should be `element.advanced.show` not root-level `showX`)
  - Missing `CustomCssTrait` with proper block type registry reference
- New implementation follows PostTitleModule pattern exactly:
  - PHP: `LessonMeta.php` + 4 traits (RenderCallback, ModuleStyles, ModuleClassnames, CustomCss)
  - TypeScript: 10 files including proper `StyleContainer`, `cssFields`, types
  - module.json: Toggle attributes in `duration.advanced.show`, `objectives.advanced.show`, `model.advanced.show`
  - Labels use `elements.render()` with proper element definitions
- Build verified successful

### Session 8 (2026-01-30)
- **Built Context Library module** using Theme Builder pattern
- Architecture decisions:
  - **Theme Builder pattern** - uses `get_queried_object_id()` to get current lesson's context files
  - **Compound elements** (not child modules) - context items are data-driven from ACF, not user-added in VB
  - **Custom modal** for View Content - Divi's lightbox is image-only; Canvases are VB layout feature
  - **Divi button elementType** for buttons - ensures styling consistency via `decoration.button`
  - **Responsive card grid** - CSS `auto-fill` with `minmax(280px, 1fr)`
- Files created:
  - PHP: `modules/ContextLibrary/` - main class + 4 traits (RenderCallback, ModuleStyles, ModuleClassnames, CustomCss)
  - TypeScript: `src/components/context-library/` - 9 files (edit, styles, types, custom-css, etc.)
  - Assets: `assets/js/context-modal.js`, `assets/css/context-modal.css`
- Features:
  - Displays context files from `chatbot_context_files` ACF relationship field
  - Visibility toggles: description, file type badge, view button, download button
  - Configurable title and empty state message (rich text)
  - Styling: module background/border, item cards, item titles, descriptions, badges, buttons
  - Custom CSS fields for all elements
- Build verified successful
- **Bug fix:** `CssStyle` must be imported from `@divi/module`, not `@divi/module-library` (causes React error #130)
- VB and frontend rendering verified working

### Session 9 (2026-01-30)
- **Built Skills List module** using same Theme Builder pattern as Context Library
- Design decisions:
  - **No modal** - Skills are complex ZIP packages; description + download is sufficient
  - **Two badges** - Compatibility (e.g., "Python 3.9+") and Version (e.g., "v1.2.0") as toggleable elements
  - **Direct attachment URL** - Download links directly to ZIP attachment, not via REST endpoint
  - **Same grid layout** - Responsive card grid with `auto-fill` matching Context Library
- Files created:
  - PHP: `modules/SkillsList/` - main class + 4 traits (same structure as ContextLibrary)
  - TypeScript: `src/components/skills-list/` - 9 files (edit, styles, types, custom-css, etc.)
- Features:
  - Displays skills from `chatbot_skills` ACF relationship field
  - Visibility toggles: description, compatibility badge, version badge, download button
  - Configurable title and empty state message (rich text)
  - Styling: module background/border, item cards, item titles, descriptions, badges, button
  - Custom CSS fields for all elements
- **Bug fixes during development:**
  - **placeholder-content.ts**: Must NOT wrap attrs in `{ attrs: {...} }` - export attributes directly
  - **placeholder-content.ts**: Don't use `placeholder.title` from `@divi/module` - use string directly
  - **ModuleClassnamesTrait.php**: Use `TextClassnames::text_options_classnames()` and `ElementClassnames::classnames()`, NOT `Module::process_classnames()` (doesn't exist)
  - **CustomCssTrait.php**: Use `WP_Block_Type_Registry` to get CSS fields, NOT `CssStyle::custom_css_fields()` (doesn't exist)
  - **ModuleStylesTrait.php**: Must wrap `$elements->style()` calls in `Style::add()` with `id`, `name`, `orderIndex`, `storeInstance`, `styles` array - otherwise styles appear in VB but NOT on frontend
- Build and frontend verified successful
- **Next: Build Chatbot module (interactive chat UI)**

### Session 10 (2026-01-30)
- **Implemented Skill Package Processing** (was blocking Skill CPT admin)
- Added `symfony/yaml` dependency via Composer (v7.4.1)
- Created `includes/class-skill-processor.php`:
  - Hooks into `acf/save_post` (priority 20) for `leaderspath_skill` posts
  - Extracts SKILL.md from uploaded ZIP (handles root or subdirectory)
  - Parses YAML frontmatter using Symfony YAML parser
  - Validates per Agent Skills Spec:
    - `name`: 1-64 chars, lowercase + hyphens, no reserved words ("anthropic", "claude")
    - `description`: 1-1024 chars, no angle brackets
    - `compatibility`: 1-500 chars (optional)
  - Auto-populates `skill_name`, `skill_description`, `skill_compatibility` ACF fields
  - Displays admin notices for validation errors (stored in transients)
- Updated `leaderspath.php` to load and instantiate `Skill_Processor`
- **Fixed ACF relationship field search for Context Files and Skills:**
  - Added `exclude_from_search => false` to both CPT registrations (WordPress defaults to true when `public` is false)
  - Removed taxonomy filter from ACF relationship fields (was causing empty results when no terms assigned)
  - Added `Capabilities::maybe_add_caps()` on `admin_init` to auto-fix missing capabilities during development
- Skill package upload tested and working - fields auto-populate from SKILL.md frontmatter
- **Next: Implement full Claude API integration with container and skills**

### Session 11 (2026-01-30)
- **Discovered critical gap in Claude API integration**
- Current implementation only uses simple Messages API
- Full skill support requires:
  - **Container API** with code execution for script execution
  - **Skills API** to upload skills to Anthropic (`POST /v1/skills`)
  - **Beta headers**: `code-execution-2025-08-25`, `skills-2025-10-02`
  - **Code execution tool** in requests
- Created `docs/claude-api-integration.md` documenting:
  - All required API endpoints
  - Beta headers and versioning strategy (configurable, not hardcoded)
  - Request/response structures
  - Skill lifecycle (WordPress upload → Anthropic upload → chat usage)
  - Container management
  - Error handling
  - Implementation checklist
- **Key insight**: Skills in system prompt are for **discovery only** (name + description)
  - Actual skill instructions and scripts load via container when triggered
  - Context files remain fully embedded in system prompt
- Created CLI test scripts: `bin/test-chat.php`, `bin/show-system-prompt.php`
- **Phase 4 marked as IN PROGRESS** - needs container + code execution rework
- **Next: Implement Anthropic Skills API upload in Skill_Processor**

### Session 12 (2026-01-30)
- **Completed Claude API integration with Container + Code Execution**
- Updated `includes/class-claude-api.php`:
  - Added container parameter with skills array when skills are configured
  - Added code execution tool to requests
  - Implemented `get_skills_for_api()` to filter synced skills
  - Implemented `handle_pause_turn()` for long-running code execution
  - Implemented `extract_response_content()` for multi-block responses
  - Container ID returned in response for session reuse
- Updated `admin/class-settings.php`:
  - Added API Version Configuration section
  - Added configurable beta header fields (code_execution, skills, files)
  - Added configurable tool type field
  - Added `get_beta_headers()` and `get_code_execution_tool_type()` static methods
- Updated `includes/class-acf-fields.php`:
  - Added Anthropic sync fields: `skill_anthropic_id`, `skill_anthropic_version`
  - Added `skill_sync_status` (pending/synced/error), `skill_sync_error`, `skill_last_synced`
- Updated `includes/class-skill-processor.php`:
  - Implemented `upload_to_anthropic()` method
  - Implemented `create_skill()` for new skills (multipart/form-data)
  - Implemented `create_skill_version()` for updates
  - Implemented `delete_from_anthropic()` method
  - Implemented `resync_skill()` for retry functionality
  - Added sync status admin notices with re-sync button
  - Added AJAX handler for re-sync
- **Phase 4 marked as COMPLETED**
- **Next: Build Chatbot Divi 5 module (Phase 5)**

### Session 13 (2026-01-30)
- **Built Chatbot Divi 5 Module** (final module in Phase 5)
- Created PHP module structure:
  - `modules/Chatbot/Chatbot.php` - Main class implementing DependencyInterface
  - `modules/Chatbot/ChatbotTrait/RenderCallbackTrait.php` - Server rendering + script enqueue
  - `modules/Chatbot/ChatbotTrait/ModuleClassnamesTrait.php` - CSS class generation
  - `modules/Chatbot/ChatbotTrait/ModuleStylesTrait.php` - Dynamic styles
  - `modules/Chatbot/ChatbotTrait/CustomCssTrait.php` - Custom CSS fields
- Created TypeScript/React components:
  - `src/components/chatbot/module.json` - Full attribute schema for chat UI
  - `src/components/chatbot/edit.tsx` - VB preview with placeholder messages
  - `src/components/chatbot/styles.tsx` - VB style component
  - `src/components/chatbot/types.ts` - TypeScript interfaces
  - `src/components/chatbot/custom-css.ts`, `module-classnames.ts`, `placeholder-content.ts`, `index.ts`
  - `src/components/chatbot/style.scss` - BEM CSS with animations
- Created frontend interactivity:
  - `assets/js/chatbot.js` - IIFE pattern, fetch API to REST endpoint, DOM manipulation
  - `assets/css/chatbot.css` - Additional runtime styles (scrollbar, focus states, animations)
- Module features:
  - Theme Builder pattern (uses `get_queried_object_id()` for activity_id)
  - Configurable user/assistant bubble colors, fonts, backgrounds
  - Chat area with configurable height
  - Input field with placeholder text
  - Send button using Divi button elementType
  - Disabled state when chatbot not enabled on lesson
  - Loading indicator (animated dots)
  - Error display in conversation
  - Basic markdown-like formatting (code blocks, bold, paragraphs)
- Registered module in `modules/Modules.php` and `src/index.ts`
- Build successful: `npm run build` compiles without errors
- **Phase 5 marked as COMPLETED**
- **Next: Phase 6 - Polish & Testing**

### Session 14 (2026-01-30)
- **Enhanced Chatbot with rich text input and markdown rendering**
- Added TinyMCE for rich text input:
  - Loaded directly from `/wp-includes/js/tinymce/tinymce.min.js` with custom handle
  - Configured with `skin: false` and `content_css: false` to prevent theme CSS conflicts
  - Inline editor with no toolbar - users use keyboard shortcuts (Ctrl+B, Ctrl+I, etc.)
  - Enter submits, Shift+Enter for new line
  - HTML-to-Markdown conversion before sending to Claude API
- Added marked.js for Markdown rendering:
  - CDN: `https://cdn.jsdelivr.net/npm/marked/marked.min.js`
  - GitHub Flavored Markdown (gfm: true)
  - Breaks enabled for single line breaks
  - Properly renders headers, lists, code blocks, bold, italic, links
- Script dependencies: `leaderspath-chatbot` depends on `leaderspath-tinymce` and `marked`
- Changed input from `<textarea>` to `<div>` for TinyMCE inline mode
- Updated VB preview (edit.tsx) to show div instead of textarea
- Fixed scroll behavior for new messages:
  - Changed from `scrollTop = scrollHeight` (scrolls to bottom) to `scrollIntoView({ block: 'start' })`
  - New messages now appear at the top of the visible area, not the bottom
  - Users don't have to scroll back up to read long responses
- **Key learnings:**
  - `wp_enqueue_editor()` loads CSS that breaks Divi theme - avoid it
  - TinyMCE `content_style` option injects global `body` styles - removed it
  - TinyMCE 6 `editor.mode.set()` requires additional plugins - use `contenteditable` directly
  - marked.js needs `gfm: true` and `breaks: true` for proper block element parsing
  - Use `scrollIntoView({ block: 'start' })` to show new messages at top, not bottom

### Session 15 (2026-02-03)
- **Major pedagogical model update: Facilitated Cohort Learning**
- LeadersPath is now understood as a **facilitated cohort learning experience**, NOT a self-paced lesson platform
- Key conceptual changes:
  - **Course** = atomic teaching unit (taught as cohesive whole by facilitator)
  - **Activity** = AI sandbox experiment (what learners DO, not what they LEARN)
  - **Facilitator** = human who presents concepts, manages discussion, runs activities
- **Terminology update: "Lessons" → "Activities"**
  - Full migration to `leaderspath_activity` CPT slug
  - Updated rest_base from 'lessons' to 'activities'
  - Updated rewrite slug from 'lesson' to 'activity'
- **Moved learning objectives to Course level**
  - Removed `lesson_objectives` repeater from Activity Settings
  - Added `course_objectives` repeater to new Course Settings field group
- **Added Course facilitator and learner content fields**
  - `course_facilitator_guide` (WYSIWYG) - teaching script for facilitator
  - `course_learner_overview` (WYSIWYG) - content visible to learners
- **Added Course Q&A Chatbot** (separate from Activity sandboxes)
  - New ACF field group: `course_chatbot_enabled`, `course_chatbot_model`, `course_chatbot_system_prompt`, etc.
  - Updated REST API to support `course_id` parameter alongside `activity_id`
  - Added `send_course_message()` and `build_course_system_prompt()` to Claude_API
  - Updated Chatbot module to auto-detect Activity vs Course mode via post type
  - Updated chatbot.js to send appropriate context ID based on mode
- **Privacy-first design decisions:**
  - NO logging of Q&A conversations
  - NO access restrictions on Q&A bot (available without cohort enrollment)
  - Both chatbot modes maintain complete sandbox isolation
- **Documentation updates:**
  - Rewrote `docs/cpt-schema.md` with new pedagogical model and relationship diagram
  - Updated `README.md` to version 0.2.0 with Activity terminology and dual chatbot modes
- **Files modified:**
  - `includes/class-acf-fields.php` - Activity fields, new Course field groups
  - `includes/class-post-types.php` - Activity labels
  - `includes/class-rest-api.php` - course_id support, handle_course_chat()
  - `includes/class-claude-api.php` - send_course_message(), build_course_system_prompt()
  - `modules/Chatbot/ChatbotTrait/RenderCallbackTrait.php` - dual mode detection
  - `assets/js/chatbot.js` - mode-aware API calls
  - `modules/LessonMeta/LessonMetaTrait/RenderCallbackTrait.php` - removed objectives
  - `docs/cpt-schema.md` - complete rewrite
  - `README.md` - version 0.2.0 update
- Build verified successful (webpack compiled with only Sass deprecation warnings)

### Session 16 (2026-02-04)
- **Completed Lesson → Activity migration**
  - Fixed plural capability issue: `leaderspath_activities` (not `leaderspath_activitys`)
  - Updated `CPT_CAPS` constant to use associative array mapping singular to plural
  - Ran WP-CLI to remove old lesson capabilities and add correct activity capabilities
  - Renamed LessonMeta module to ActivityMeta throughout codebase
- **Updated all documentation to match actual schema**
  - Verified create-test-data.php against class-acf-fields.php
  - Removed dead `objectives` data from activity definitions (no `activity_objectives` ACF field)
  - Fixed cpt-schema.md: removed incorrect `context_category` ACF field reference (it's a taxonomy)
  - Updated activity content language ("In this activity" not "In this lesson")
- **Enhanced test data with complete Course fields**
  - Added `course_total_duration` (e.g., "90 minutes")
  - Added `course_objectives` repeater with learning objectives
  - Added `course_facilitator_guide` with full teaching scripts
  - Added `course_learner_overview` with learner-facing content
  - Added Course Q&A Chatbot configuration (enabled, model, system prompt, context files)
  - Updated function signature to pass context_ids for course chatbot references
- **Fixed ACF relationship field issue**
  - Course Activities relationship field was showing empty list
  - Removed `taxonomy` filter that was pre-filtering by unassigned terms
- **Test data created successfully:**
  - 3 Context Files (IDs 69-71)
  - 2 Skills (IDs 72-73)
  - 4 Activities (IDs 74-77)
  - 2 Courses (IDs 78-79) with full facilitator guides and Q&A chatbots
  - 1 Cohort (ID 80)
- Build verified successful

---

## Quick Reference for Next Session

### Test Data Available
| Type | IDs | Notes |
|------|-----|-------|
| Activities | 74-77 | All have chatbot enabled, various context/skills |
| Courses | 78-79 | AI Fundamentals (3 activities), AI in Practice (1 activity) |
| Context Files | 69-71 | Ethics, Prompt Engineering, Conversation Flows |
| Skills | 72-73 | Code Review, Writing Editor |
| Cohort | 80 | Spring 2026, linked to Course 78 |

### Key Files for Module Development
```
modules/
├── Modules.php              # PHP module registration (add new modules here)
├── HelloModule/             # Simple reference implementation
├── ActivityMeta/            # Theme Builder pattern - displays ACF data from current activity
├── ContextLibrary/          # Theme Builder pattern - compound elements, modal, buttons
├── SkillsList/              # Theme Builder pattern - similar to ContextLibrary, no modal
├── Chatbot/                 # Theme Builder pattern - interactive frontend JavaScript
│   ├── Chatbot.php          # Main class implementing DependencyInterface
│   └── ChatbotTrait/        # Traits: RenderCallback, ModuleClassnames, ModuleStyles, CustomCss
src/
├── index.ts                 # JS module registration (add registerModule() here)
└── components/
    ├── hello-module/        # Simple reference
    ├── activity-meta/       # Theme Builder pattern
    ├── context-library/     # Theme Builder pattern with compound elements + modal
    ├── skills-list/         # Theme Builder pattern (same structure, no modal)
    └── chatbot/             # Theme Builder pattern with interactive chat
        ├── index.ts         # Module export with metadata + renderers
        ├── edit.tsx         # Visual Builder React component (placeholder messages)
        ├── module.json      # Module schema (chat area, bubbles, input, button)
        ├── types.ts         # TypeScript interfaces
        ├── styles.tsx       # VB styles component with StyleContainer
        ├── custom-css.ts    # CSS fields definition
        ├── module-classnames.ts
        ├── placeholder-content.ts
        └── style.scss       # BEM CSS with loading animations
assets/
├── js/context-modal.js      # Modal JavaScript for View Content (Context Library only)
├── js/chatbot.js            # Chatbot frontend interactivity (REST API calls)
├── css/context-modal.css    # Modal styles
└── css/chatbot.css          # Chatbot runtime styles
```

### Divi 5 Module Development - CRITICAL

**Before writing ANY module code:**
1. Read `docs/divi-modules.md` for bootstrap instructions and file locations
2. Verify example repo exists: `ls /private/tmp/d5-extension-example-modules || git clone https://github.com/elegantthemes/d5-extension-example-modules.git /private/tmp/d5-extension-example-modules`
3. Start with core Divi modules (primary), then example repo (secondary)

**Module Pattern Selection:**
| Use Case | Pattern | Primary Reference |
|----------|---------|-------------------|
| Current post data (lesson meta, context) | Theme Builder | `PostTitle/PostTitleModule.php` |
| Query multiple posts | Dynamic Query | `Blog/BlogController.php` |
| User-entered content only | Static | Example repo `StaticModule/` |

**Key reference files:**
| What You Need | Where to Look |
|---------------|---------------|
| Module schema, attributes, settings | Core `{module}/module.json` |
| PHP render, styles, classnames | Core `{Module}Module.php` or example repo traits |
| TypeScript edit component | Example repo `src/components/{module}/edit.tsx` |
| TypeScript styles component | Example repo `src/components/{module}/styles.tsx` |
| Toggle/select field definitions | `post-title/module.json` |
| Color picker, custom styling | `static-module/module.json` |

**Do NOT guess at patterns. Read the actual source code.**

### Claude API Integration - IMPLEMENTED

**Read `docs/claude-api-integration.md` before modifying API code.**

**Current Status:** Container API with Code Execution and Skills support ✓

**Implementation Details:**
```
Beta Headers (configurable in Settings):
- code-execution-2025-08-25
- skills-2025-10-02
- files-api-2025-04-14 (for file handling)

Tools:
- code_execution_20250825

Container:
- skills array built from synced skills (skill_sync_status = 'synced')
- container.id returned in response for session reuse
- Max 8 skills per request
```

**Skill Upload Flow:**
1. Upload ZIP via ACF → Local validation (frontmatter) → Upload to Anthropic
2. Anthropic returns `skill_id` → Stored in `skill_anthropic_id` field
3. Skills with `skill_sync_status = 'synced'` included in chat requests

**Anthropic API Endpoints:**
- `POST /v1/messages` - Chat with container + code execution
- `POST /v1/skills` - Upload skill package
- `GET /v1/skills` - List skills (includes skill_id)
- `POST /v1/skills/{id}/versions` - Create new version

### WordPress REST Endpoints
- `POST /leaderspath/v1/chat` - Chat with Claude (needs activity_id or course_id, message, optional history/model)
- `GET /leaderspath/v1/activities/{id}/context` - Get context files for activity
- `GET /leaderspath/v1/activities/{id}/skills` - Get skills for activity
- `GET /leaderspath/v1/context/{id}/download` - Get context file content
- `GET /leaderspath/v1/skills/{id}/download` - Get skill definition

### CLI Test Scripts
```bash
# Full chat system test (API, context assembly, conversation)
wp eval-file wp-content/plugins/leaderspath/bin/test-chat.php [activity_id] [message]

# Show assembled system prompt for an activity
wp eval-file wp-content/plugins/leaderspath/bin/show-system-prompt.php [activity_id]
```

### Build Commands
```bash
npm run build    # Production build
npm run start    # Development with watch
```
