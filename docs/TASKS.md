# LeadersPath Development Tasks

**Last Updated:** 2026-01-30
**Current Phase:** Phase 5 - Divi 5 Modules (Frontend)

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
- [x] Register `leaderspath_lesson` CPT
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

## Phase 4: REST API & Claude Integration (IN PROGRESS)

**Reference:** See `docs/claude-api-integration.md` for full API documentation.

### REST API Endpoints
- [x] Create `includes/class-rest-api.php`
- [x] `POST /wp-json/leaderspath/v1/chat` - Send message to Claude
- [x] `GET /wp-json/leaderspath/v1/lessons/{id}/context` - Get lesson context files
- [x] `GET /wp-json/leaderspath/v1/lessons/{id}/skills` - Get lesson skills
- [x] `GET /wp-json/leaderspath/v1/context/{id}/download` - Download context file
- [x] `GET /wp-json/leaderspath/v1/skills/{id}/download` - Download skill definition
- [x] Implement authentication (nonce for logged-in, capability checks)
- [ ] Update chat endpoint for container API response format
- [ ] Handle pause_turn responses
- [ ] Handle file outputs from code execution

### Claude API Handler (NEEDS REWORK)
- [x] Create `includes/class-claude-api.php`
- [~] Implement Messages API integration (needs container + code execution)
- [x] Context assembly from lesson files
- [x] Model selection (dynamic from API, with fallbacks)
- [x] Error handling and logging
- [x] Test connection button in settings (queries available models)
- [ ] Add container parameter with skills array
- [ ] Add code execution tool to requests
- [ ] Handle pause_turn stop reason
- [ ] Implement container reuse within session
- [ ] Optional: Streaming response support (SSE)

### Skill Library Handler (Upload to Anthropic)
Skills must be uploaded to Anthropic's Skills API for code execution to work.
Context files remain WordPress-only (embedded in system prompt).

- [x] Create `includes/class-skill-processor.php` (local validation only)
- [ ] Add `skill_anthropic_id` ACF field (stores returned skill_id)
- [ ] Add `skill_anthropic_version` ACF field (stores version timestamp)
- [ ] Add `skill_sync_status` ACF field (pending/synced/error)
- [ ] Add `skill_sync_error` ACF field (last error message)
- [ ] Implement `upload_to_anthropic()` method in Skill_Processor
- [ ] Upload skill ZIP to `POST /v1/skills` on first save
- [ ] Create new version via `POST /v1/skills/{id}/versions` on update
- [ ] Store returned skill_id and version in ACF fields
- [ ] Add admin notice for sync status (success/error)
- [ ] Add "Re-sync" button for failed uploads
- [ ] Handle skill deletion (delete from Anthropic when trashed?)

### Settings Updates
- [ ] Add beta header version fields (configurable, not hardcoded)
- [ ] Add tool type version field
- [ ] Add "Test Skills API" button
- [ ] Document version update process in admin

---

## Phase 5: Divi 5 Modules (Frontend) (CURRENT)

Now that data layer exists, build the UI modules.

**Reference:** See `docs/divi-modules.md` for Divi 5 module development guide.
**Working Examples:**
- `modules/LessonMeta/` - Theme Builder pattern (ACF data from current lesson)
- `modules/ContextLibrary/` - Theme Builder pattern with compound elements and modal
- `modules/SkillsList/` - Theme Builder pattern (similar to Context Library, no modal)

### Chatbot Module
- [ ] Create PHP module class and traits
- [ ] Create TypeScript/React edit component
- [ ] Create module.json with settings schema
- [ ] Implement chat UI (bubbles, input, send button)
- [ ] Add styling options (colors, fonts, sizing)
- [ ] Connect to REST API endpoint

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

---

## Quick Reference for Next Session

### Test Data Available
| Type | IDs | Notes |
|------|-----|-------|
| Lessons | 97-100 | All have chatbot enabled, various context/skills |
| Courses | 101-102 | AI Fundamentals (3 lessons), AI in Practice (1 lesson) |
| Context Files | 92-94 | Ethics, Prompt Engineering, Conversation Flows |
| Skills | 95-96 | Code Review, Writing Editor |
| Cohort | 103 | Spring 2026, linked to Course 101 |

### Key Files for Module Development
```
modules/
├── Modules.php              # PHP module registration (add new modules here)
├── HelloModule/             # Simple reference implementation
├── LessonMeta/              # Theme Builder pattern - displays ACF data from current lesson
├── ContextLibrary/          # Theme Builder pattern - compound elements, modal, buttons
├── SkillsList/              # Theme Builder pattern - similar to ContextLibrary, no modal
│   ├── SkillsList.php       # Main class implementing DependencyInterface
│   └── SkillsListTrait/     # Traits: RenderCallback, ModuleClassnames, ModuleStyles, CustomCss
src/
├── index.ts                 # JS module registration (add registerModule() here)
└── components/
    ├── hello-module/        # Simple reference
    ├── lesson-meta/         # Theme Builder pattern
    ├── context-library/     # Theme Builder pattern with compound elements + modal
    └── skills-list/         # Theme Builder pattern (same structure, no modal)
        ├── index.ts         # Module export with metadata + renderers
        ├── edit.tsx         # Visual Builder React component
        ├── module.json      # Module schema (attributes, settings groups)
        ├── types.ts         # TypeScript interfaces (extends InternalAttrs)
        ├── styles.tsx       # VB styles component with StyleContainer
        ├── custom-css.ts    # CSS fields definition
        ├── module-classnames.ts
        ├── placeholder-content.ts
        └── style.scss       # BEM CSS
assets/
├── js/context-modal.js      # Modal JavaScript for View Content (Context Library only)
└── css/context-modal.css    # Modal styles
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

### Claude API Integration - CRITICAL

**Read `docs/claude-api-integration.md` before modifying API code.**

**Current Status:** Simple Messages API (needs upgrade to container + code execution)

**Required for Full Skills:**
```
Beta Headers:
- code-execution-2025-08-25
- skills-2025-10-02
- files-api-2025-04-14 (for file handling)

Tools:
- code_execution_20250825

Container:
- skills array with type, skill_id, version
- container.id for session reuse
```

**Anthropic API Endpoints:**
- `POST /v1/messages` - Chat with container + code execution
- `POST /v1/skills` - Upload skill package
- `GET /v1/skills` - List skills (includes skill_id)
- `POST /v1/skills/{id}/versions` - Create new version

### WordPress REST Endpoints
- `POST /leaderspath/v1/chat` - Chat with Claude (needs lesson_id, message, optional history/model)
- `GET /leaderspath/v1/lessons/{id}/context` - Get context files for lesson
- `GET /leaderspath/v1/lessons/{id}/skills` - Get skills for lesson
- `GET /leaderspath/v1/context/{id}/download` - Get context file content
- `GET /leaderspath/v1/skills/{id}/download` - Get skill definition

### CLI Test Scripts
```bash
# Full chat system test (API, context assembly, conversation)
wp eval-file wp-content/plugins/leaderspath/bin/test-chat.php [lesson_id] [message]

# Show assembled system prompt for a lesson
wp eval-file wp-content/plugins/leaderspath/bin/show-system-prompt.php [lesson_id]
```

### Build Commands
```bash
npm run build    # Production build
npm run start    # Development with watch
```
