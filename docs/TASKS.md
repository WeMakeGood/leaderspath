# LeadersPath Development Tasks

**Last Updated:** 2026-02-09
**Current Phase:** Phase 5b - Lesson Divi 5 Modules

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
- [x] Register `leaderspath_lesson` CPT
- [x] Register `leaderspath_course` CPT
- [x] Register `leaderspath_context` CPT (context files)
- [x] Register `leaderspath_skill` CPT

### Taxonomies
- [x] Create `includes/class-taxonomies.php`
- [x] Register `leaderspath_topic` taxonomy (for activities, lessons)
- [x] Register `leaderspath_context_cat` taxonomy (for context files)
- [x] Register `leaderspath_skill_cat` taxonomy (for skills)
- [x] Create default terms on activation

### ACF Field Groups (Programmatic via ACF Pro API)
- [x] Create `includes/class-acf-fields.php`
- [x] Register Activity Settings field group
- [x] Register Activity Chatbot Configuration field group
- [x] Register Lesson Settings field group
- [x] Register Course Settings field group
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
- [x] Custom admin columns for Activities (duration, lesson, chatbot status)
- [x] Custom admin columns for Lessons (activity count, difficulty, total duration)
- [x] Quick edit support (activity duration, chatbot enabled, lesson difficulty)
- [x] Admin notices for missing API key

---

## Phase 4: REST API & Claude Integration (COMPLETED)

**Reference:** See `docs/claude-api-integration.md` for full API documentation.

### REST API Endpoints
- [x] Create `includes/class-rest-api.php`
- [x] `POST /wp-json/leaderspath/v1/chat` - Send message to Claude
- [x] `GET /wp-json/leaderspath/v1/activities/{id}/context` - Get activity context files
- [x] `GET /wp-json/leaderspath/v1/activities/{id}/skills` - Get activity skills
- [x] `GET /wp-json/leaderspath/v1/context/{id}/download` - Download context file
- [x] `GET /wp-json/leaderspath/v1/skills/{id}/download` - Download skill definition
- [x] Implement authentication (nonce for logged-in, capability checks)
- [x] Update chat endpoint for container API response format (container_id in response, activity_id or lesson_id)
- [x] Handle pause_turn responses (continuation loop in Claude_API)
- [ ] Handle file outputs from code execution (deferred - not critical for MVP)

### Claude API Handler (COMPLETED)
- [x] Create `includes/class-claude-api.php`
- [x] Implement Messages API integration with container + code execution
- [x] Context assembly from activity files
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
- `modules/ActivityMeta/` - Theme Builder pattern (ACF data from current activity)
- `modules/ContextLibrary/` - Theme Builder pattern with compound elements and modal (activity context files)
- `modules/SkillsList/` - Theme Builder pattern (similar to Context Library, no modal, activity skills)
- `modules/Chatbot/` - Theme Builder pattern with interactive frontend JavaScript (Activity + Lesson modes)

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

### Activity Meta Module (REWRITTEN, was Lesson Meta)
- [x] Create PHP module class and traits (following PostTitle pattern)
- [x] Create TypeScript/React edit component (with StyleContainer)
- [x] Display duration, objectives, model info
- [x] Add visibility toggles and configurable labels in Content tab
- [x] Use Style::add() wrapper in PHP
- [x] Use toggle attributes in element.advanced.show pattern (like PostTitle)

---

## Phase 5b: Lesson Divi 5 Modules

Lesson-specific modules to display lesson data in Divi 5 Theme Builder templates.

**Reference:** Existing Activity modules use Theme Builder pattern with `get_queried_object_id()`.

### Facilitator Role Setup (COMPLETED)
Add a `leaderspath_facilitator` role to distinguish facilitators from students.
- [x] Add `leaderspath_facilitator` role in `includes/class-capabilities.php`
- [x] Grant facilitator role: all student capabilities + `leaderspath_view_facilitator_content`
- [x] Add new capability: `leaderspath_view_facilitator_content`
- [x] Map capability to Administrator, Editor, and Facilitator roles
- [x] Update documentation in `docs/cpt-schema.md`

### LessonMeta Module (COMPLETED)
Display lesson metadata (similar to ActivityMeta).
- [x] Create PHP module class and traits
- [x] Create TypeScript/React edit component
- [x] Display: duration, difficulty badge, activity count
- [x] Visibility toggles for each element
- [x] Configurable labels for each element
- [x] REST API endpoint (`/lessons/meta`) for VB preview

### LessonObjectives Module (COMPLETED)
Display learning objectives as a styled list.
- [x] Create PHP module class and traits
- [x] Create TypeScript/React edit component
- [x] Render `lesson_objectives` repeater as list items
- [x] Configurable title and empty state message
- [x] REST API endpoint (`/lessons/objectives`) for VB preview

### LessonActivities Module (COMPLETED)
Display ordered list of activities with navigation.
- [x] Create PHP module class and traits
- [x] Create TypeScript/React edit component
- [x] Render `lesson_activities` relationship as ordered list with links
- [x] Display activity: numbered badge + linked title
- [x] REST API endpoint (`/lessons/activities`) for VB preview

### LearnerOverview & FacilitatorGuide (NO CUSTOM MODULES NEEDED)
These WYSIWYG fields (`lesson_learner_overview`, `lesson_facilitator_guide`) can be displayed
using Divi 5's native Text module with ACF dynamic field output. Access control for the
facilitator guide is handled via Divi 5's built-in display conditions (role-based visibility).

### Module Styling Consistency Pass (COMPLETED)
Made all 4 list/card modules consistent in structure and Design tab configurability.
Hardcoded SCSS colors/spacing moved to Divi `defaultPrintedStyle` for point-and-click override.

**Phase A: Card Grid Modules (ContextLibrary + SkillsList)**
- [x] Normalize `moduleClassName` to dashes (BEM convention)
- [x] Remove hardcoded empty state background colors from SCSS
- [x] Normalize badge `defaultPrintedStyle` font-size (SkillsList: 11px → 12px)
- [x] Add `__content` flex column layout to SkillsList SCSS
- [x] Normalize SkillsList title margin to match ContextLibrary

**Phase B: LessonObjectives upgrade**
- [x] Add `defaultPrintedStyle` to title (22px, 600 weight, 1.3em lineHeight)
- [x] Add `list` attribute with `decoration.layout` (flex column, 8px gap)
- [x] Add `item` attribute with `decoration.bodyFont` (1.5em lineHeight default)
- [x] Change `emptyState` from `<p>` to `<div>` with richText editor
- [x] Split content groups: `contentTitle` + `contentEmptyState`
- [x] Add `designLayout` group to Design tab
- [x] Add `content` custom CSS field
- [x] Remove hardcoded empty state colors/borders from SCSS
- [x] Remove advancedStyles text block from styles.tsx and PHP
- [x] Set module text color `render: false`

**Phase C: LessonActivities upgrade (largest scope)**
- [x] Add `defaultPrintedStyle` to title (22px, 600 weight, 1.3em lineHeight)
- [x] Add `list` attribute with `decoration.layout` (flex column, 12px gap)
- [x] Add `item` attribute with `decoration.background` (#f8f9fa), `border` (4px radius), `spacing` (12px/16px padding)
- [x] Add `numberBadge` attribute with `decoration.background` (#0073aa) and `font` (14px, 600, white)
- [x] Add `link` attribute with `decoration.font` (#0073aa, 500 weight)
- [x] Change `emptyState` from `<p>` to `<div>` with richText editor
- [x] Split content groups: `contentTitle` + `contentEmptyState`
- [x] Add `designLayout` group to Design tab
- [x] Add `content` custom CSS field
- [x] Remove all hardcoded colors/padding/border-radius from SCSS (kept structural flex/shape)
- [x] Remove advancedStyles text block from styles.tsx and PHP
- [x] Set module text color `render: false`

### Integration
- [x] Register LessonMeta, LessonObjectives, LessonActivities in `modules/Modules.php`
- [x] Register LessonMeta, LessonObjectives, LessonActivities in `src/index.ts`
- [x] Verify build completes successfully for all completed modules
- [ ] Test in Divi 5 Visual Builder
- [ ] Test frontend rendering with Lesson template

### CourseLessons Module
Display ordered list of lessons for a Course, with navigation links.
Template: LessonActivities module (clone and adapt).

**Data Source:**
- ACF field: `course_lessons` (relationship, `return_format: 'id'`, post_type: `leaderspath_lesson`)
- Note: returns IDs (not objects) — must call `get_post()` to resolve each ID

**REST API (to create):**
- `GET /leaderspath/v1/courses/lessons` — fallback to first course (VB preview)
- `GET /leaderspath/v1/courses/{id}/lessons` — specific course

**PHP Files (to create):**
- `modules/CourseLessons/CourseLessons.php` — main class (DependencyInterface)
- `modules/CourseLessons/CourseLessonsTrait/RenderCallbackTrait.php` — `get_course_id()`, `get_lessons()`, `render_callback()`
- `modules/CourseLessons/CourseLessonsTrait/ModuleClassnamesTrait.php`
- `modules/CourseLessons/CourseLessonsTrait/ModuleStylesTrait.php`
- `modules/CourseLessons/CourseLessonsTrait/CustomCssTrait.php`

**TypeScript Files (to create):**
- `src/components/course-lessons/module.json` — follow LessonActivities pattern:
  - `title` with `defaultPrintedStyle` (22px, 600, 1.3em)
  - `emptyState` with `tagName: "div"`, `inlineEditor: "richText"`, `decoration.bodyFont`
  - `list` with `decoration.layout` (flex column, 12px gap)
  - `item` with `decoration.background` (#f8f9fa), `border` (4px radius), `spacing` (12px/16px)
  - `numberBadge` with `decoration.background` + `font`
  - `link` with `decoration.font`
  - `css` attribute, `module.advanced.text.color.render: false`
  - Content groups: `contentTitle` + `contentEmptyState`
  - Design group: `designLayout`
- `src/components/course-lessons/edit.tsx`
- `src/components/course-lessons/styles.tsx`
- `src/components/course-lessons/types.ts`
- `src/components/course-lessons/use-course-lessons.ts`
- `src/components/course-lessons/module-classnames.ts`
- `src/components/course-lessons/custom-css.ts`
- `src/components/course-lessons/placeholder-content.ts`
- `src/components/course-lessons/style.scss` — structural only (flex, circle shape, text-decoration)
- `src/components/course-lessons/index.ts`

**Registration:**
- Add `CourseLessons` to `modules/Modules.php`
- Add `courseLessonsModule` to `src/index.ts`

**Tasks:**
- [ ] Add REST API endpoints (`/courses/lessons` and `/courses/{id}/lessons`) to `class-rest-api.php`
- [ ] Create PHP module class and 4 traits
- [ ] Create TypeScript module (10 files)
- [ ] Register module in `Modules.php` and `src/index.ts`
- [ ] Build and verify
- [ ] Test in Divi 5 Visual Builder
- [ ] Test frontend rendering with Course template

### Course CPT Cleanup (COMPLETED)
Removed cohort-specific fields that belong on WooCommerce product (Phase 7), not Course CPT:
- [x] Remove `course_status`, `course_start_date`, `course_end_date` ACF fields
- [x] Remove `course_instructor`, `course_language`, `course_timezone`, `course_max_participants` ACF fields
- [x] Remove `get_timezone_choices()` helper method
- [x] Update test data script (removed stale field references)
- [x] Update cpt-schema.md documentation

---

## Phase 6: Polish & Testing

- [ ] Write PHPUnit tests for CPTs, taxonomies, API
- [ ] Write Jest tests for React components
- [ ] Security audit (input sanitization, capability checks, nonces)
- [ ] Performance optimization
- [ ] User documentation
- [ ] Code documentation cleanup

---

## Phase 7: WooCommerce Cohort Product

Cohorts will become a WooCommerce product type for enrollment management.

- [ ] Create WooCommerce Cohort product type
- [ ] Cohort product linked to Course CPT
- [ ] Enrollment management via WooCommerce orders
- [ ] Cohort-specific settings (start/end dates, max participants)
- [ ] Access control: learner enrollment gates Lesson/Activity access

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
| 2026-02-03 | Facilitated cohort learning model | LeadersPath is facilitator-led, not self-paced. Lesson = atomic unit, Activities = AI sandboxes |
| 2026-02-03 | Rename Lessons to Activities | Full migration to `leaderspath_activity` CPT slug |
| 2026-02-03 | Learning objectives at Lesson level | Moved from Activity to Lesson since Lesson is the teaching unit |
| 2026-02-03 | Dual chatbot modes | Activity Sandbox (demonstrate behaviors) vs Lesson Q&A (helpful assistant) |
| 2026-02-03 | Privacy-first Q&A bot | No logging, no access restrictions - maintains sandbox trust |
| 2026-02-09 | Nomenclature swap: Course->Lesson, Cohort->Course | `leaderspath_course` renamed to `leaderspath_lesson` (atomic teaching unit); `leaderspath_cohort` renamed to `leaderspath_course` (curriculum containing Lessons) |

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

### Session 17 (2026-02-04)
- **Identified VB vs Frontend rendering disconnect**
  - VB edit.tsx uses hardcoded placeholder data
  - PHP render_callback uses real ACF data via `get_queried_object_id()`
  - These are completely separate code paths, causing VB preview to differ from frontend
  - Example: ActivityMeta still shows "Learning Objectives" in VB even though removed from PHP
- **Researched Server-Side Rendering for VB**
  - WordPress provides `@wordpress/server-side-render` component
  - Calls `/wp/v2/block-renderer/{block-name}` REST endpoint
  - Server runs PHP render_callback and returns HTML to VB
  - Divi 5 registers WordPress blocks, so endpoint IS available for our modules
- **Implementation plan documented:**
  - See `memory/divi5-ssr-implementation.md` for full details
  - Key challenge: getting current post ID in VB context for Theme Builder templates
  - Need to add `@wordpress/server-side-render` to webpack externals
  - Need to enqueue `wp-server-side-render` script when VB loads
- **Created Phase 5b task list for Course modules:**
  - Added `leaderspath_facilitator` role requirement
  - 5 new modules: CourseMeta, CourseObjectives, CourseActivities, LearnerOverview, FacilitatorGuide
- **Next steps:**
  1. Create test module using SSR to verify approach works
  2. If successful, apply SSR pattern to all Theme Builder modules
  3. Build Course modules with SSR from the start

### Session 18 (2026-02-04)
- **Built CourseMeta Divi 5 module** (first Course module)
- Established REST API pattern for VB preview:
  - Custom REST endpoint returns JSON data (not HTML)
  - React hook in edit.tsx fetches data via REST API
  - PHP render_callback handles frontend rendering with ACF data
  - Both REST and PHP fall back to first post of CPT as sample data
- Why NOT `@wordpress/server-side-render`: Divi VB doesn't load WP block editor scripts, `wp.serverSideRender` global unavailable (React error #130)
- Added endpoints: `/courses/meta` and `/courses/{id}/meta`
- Updated existing modules (ActivityMeta, ContextLibrary, SkillsList) to use REST API pattern
- Added VB fallback endpoints: `/activities/meta`, `/activities/context`, `/activities/skills`

### Session 19 (2026-02-09)
- **Built CourseObjectives Divi 5 module**
  - Displays learning objectives from `course_objectives` ACF repeater field
  - Each objective rendered as list item (`<li>`)
  - Configurable title and empty state message
  - REST endpoints: `/courses/objectives` and `/courses/{id}/objectives`
- **Added `leaderspath_facilitator` role**
  - New capability: `leaderspath_view_facilitator_content`
  - Facilitator role gets all student caps + facilitator content access
  - Capability granted to Administrator and Editor roles
  - Upgrade handling via `maybe_add_facilitator_caps()` on `admin_init`

### Session 20 (2026-02-09)
- **Built CourseActivities Divi 5 module**
  - Displays ordered list of activities from `course_activities` ACF relationship field
  - Each activity shows numbered badge + linked title
  - REST endpoints: `/courses/activities` and `/courses/{id}/activities`
  - Styled with card-like appearance for each activity item
- Updated TASKS.md with all completed Phase 5b items and session notes

### Session 21 (2026-02-09)
- **Nomenclature rename: Course->Lesson, Cohort->Course**
  - Renamed `leaderspath_course` CPT to `leaderspath_lesson` (atomic teaching unit)
  - Renamed `leaderspath_cohort` CPT to `leaderspath_course` (curriculum containing Lessons)
  - Updated ALL code: PHP classes, REST API, Claude API, admin, Divi modules (PHP + TypeScript), build scripts, test data
  - All ACF fields renamed: `course_*` -> `lesson_*`, `cohort_*` -> `course_*`
  - `cohort_course` (post_object) -> `course_lessons` (relationship to multiple lessons)
  - Divi modules renamed: CourseMeta->LessonMeta, CourseObjectives->LessonObjectives, CourseActivities->LessonActivities
  - REST endpoints: `/courses/*` -> `/lessons/*`
  - Build verified successful, stale modules-json cleaned up
  - Added Phase 7: WooCommerce Cohort Product to task list

---

## Quick Reference for Next Session

### Test Data Available
| Type | IDs | Notes |
|------|-----|-------|
| Activities | 74-77 | All have chatbot enabled, various context/skills |
| Lessons | 78-79 | AI Fundamentals (3 activities), AI in Practice (1 activity) |
| Context Files | 69-71 | Ethics, Prompt Engineering, Conversation Flows |
| Skills | 72-73 | Code Review, Writing Editor |
| Course | 80 | Spring 2026, linked to Lesson 78 |

### Key Files for Module Development
```
modules/
├── Modules.php              # PHP module registration (add new modules here)
├── HelloModule/             # Simple reference implementation
├── ActivityMeta/            # Theme Builder pattern - displays ACF data from current activity
├── ContextLibrary/          # Theme Builder pattern - compound elements, modal, buttons
├── SkillsList/              # Theme Builder pattern - similar to ContextLibrary, no modal
├── Chatbot/                 # Theme Builder pattern - interactive frontend JavaScript
├── LessonMeta/              # Theme Builder pattern - lesson duration, difficulty, activity count
├── LessonObjectives/        # Theme Builder pattern - learning objectives list
├── LessonActivities/        # Theme Builder pattern - ordered activity list with links
src/
├── index.ts                 # JS module registration (add registerModule() here)
└── components/
    ├── hello-module/        # Simple reference
    ├── activity-meta/       # Theme Builder pattern with REST API hook
    ├── context-library/     # Theme Builder pattern with REST API hook + modal
    ├── skills-list/         # Theme Builder pattern with REST API hook
    ├── chatbot/             # Theme Builder pattern with interactive chat
    ├── lesson-meta/         # REST API hook for lesson metadata
    ├── lesson-objectives/   # REST API hook for lesson objectives
    └── lesson-activities/   # REST API hook for lesson activities
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
| Current post data (activity/lesson meta, context) | Theme Builder | `PostTitle/PostTitleModule.php` |
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
- `POST /leaderspath/v1/chat` - Chat with Claude (needs activity_id or lesson_id, message, optional history/model)
- `GET /leaderspath/v1/activities/{id}/context` - Get context files for activity
- `GET /leaderspath/v1/activities/{id}/skills` - Get skills for activity
- `GET /leaderspath/v1/context/{id}/download` - Get context file content
- `GET /leaderspath/v1/skills/{id}/download` - Get skill definition
- `GET /leaderspath/v1/lessons/meta` - Lesson meta for VB preview (fallback to first lesson)
- `GET /leaderspath/v1/lessons/{id}/meta` - Lesson meta for specific lesson
- `GET /leaderspath/v1/lessons/objectives` - Lesson objectives for VB preview
- `GET /leaderspath/v1/lessons/{id}/objectives` - Lesson objectives for specific lesson
- `GET /leaderspath/v1/lessons/activities` - Lesson activities for VB preview
- `GET /leaderspath/v1/lessons/{id}/activities` - Lesson activities for specific lesson
- `GET /leaderspath/v1/activities/meta` - Activity meta for VB preview (fallback to first activity)
- `GET /leaderspath/v1/activities/{id}/meta` - Activity meta for specific activity
- `GET /leaderspath/v1/activities/context` - Context files for VB preview (first activity)
- `GET /leaderspath/v1/activities/skills` - Skills for VB preview (first activity)

### CLI Test Scripts
```bash
# Full chat system test (API, context assembly, conversation)
wp eval-file wp-content/plugins/leaderspath/bin/test-chat.php [activity_id] [message]

# Show assembled system prompt for an activity
wp eval-file wp-content/plugins/leaderspath/bin/show-system-prompt.php [activity_id]
```

### Session 22 (2026-02-09)
- **Module Styling Consistency Pass** — made all 4 list/card modules consistent
- **Phase A: Card Grid Modules (ContextLibrary + SkillsList)**
  - Normalized `moduleClassName`/`moduleOrderClassName` to BEM dashes (was underscores)
  - Removed hardcoded empty state background colors from SCSS
  - Normalized SkillsList badge `defaultPrintedStyle` from 11px to 12px
  - Added `__content` flex column layout to SkillsList SCSS
- **Phase B: LessonObjectives upgrade**
  - Rewrote module.json: added `list` (decoration.layout), `item` (bodyFont), title `defaultPrintedStyle`
  - Changed emptyState from `<p>` to `<div>` with richText editor and bodyFont
  - Split content groups: `contentTitle` + `contentEmptyState` + `designLayout`
  - Removed advancedStyles divi/text from styles.tsx and ModuleStylesTrait.php
  - Removed hardcoded empty state styling from SCSS
  - Updated types.ts to match ContextLibrary pattern (InternalAttrs, Element types)
- **Phase C: LessonActivities upgrade** (largest scope)
  - Rewrote module.json: added `list` (decoration.layout), `item` (background/border/spacing), `numberBadge` (background/font), `link` (font)
  - All hardcoded colors (#f8f9fa, #0073aa, #fff) moved to `defaultPrintedStyle`
  - SCSS stripped to structural-only (flex layout, circle shape, text-decoration reset)
  - Updated styles.tsx, types.ts, custom-css.ts, ModuleStylesTrait.php
- Key design decisions:
  - `decoration.layout` for list modules (flex column) — Divi's `gridColumnCount` only supports fixed counts, not CSS `auto-fill`
  - Keep SCSS `auto-fill` grid for card modules (ContextLibrary, SkillsList)
  - `defaultPrintedStyle` provides visual defaults that users can override via Design tab
- Build verified successful after each phase

### Build Commands
```bash
npm run build    # Production build
npm run start    # Development with watch
```
