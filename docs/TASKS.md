# LeadersPath Development Tasks

**Last Updated:** 2026-01-29
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

## Phase 4: REST API & Claude Integration (COMPLETED)

### REST API Endpoints
- [x] Create `includes/class-rest-api.php`
- [x] `POST /wp-json/leaderspath/v1/chat` - Send message to Claude
- [x] `GET /wp-json/leaderspath/v1/lessons/{id}/context` - Get lesson context files
- [x] `GET /wp-json/leaderspath/v1/lessons/{id}/skills` - Get lesson skills
- [x] `GET /wp-json/leaderspath/v1/context/{id}/download` - Download context file
- [x] `GET /wp-json/leaderspath/v1/skills/{id}/download` - Download skill definition
- [x] Implement authentication (nonce for logged-in, capability checks)

### Claude API Handler
- [x] Create `includes/class-claude-api.php`
- [x] Implement Messages API integration
- [x] Context assembly from lesson files
- [x] Model selection (dynamic from API, with fallbacks)
- [x] Error handling and logging
- [x] Test connection button in settings (queries available models)
- [ ] Optional: Streaming response support (SSE)

---

## Phase 5: Divi 5 Modules (Frontend) (CURRENT)

Now that data layer exists, build the UI modules.

**Reference:** See `docs/divi-modules.md` for Divi 5 module development guide.
**Working Example:** `modules/HelloModule/` and `src/components/hello-module/` demonstrate the pattern.

### Chatbot Module
- [ ] Create PHP module class and traits
- [ ] Create TypeScript/React edit component
- [ ] Create module.json with settings schema
- [ ] Implement chat UI (bubbles, input, send button)
- [ ] Add styling options (colors, fonts, sizing)
- [ ] Connect to REST API endpoint

### Context Library Module
- [ ] Create PHP module class and traits
- [ ] Create TypeScript/React edit component
- [ ] Implement file list display (list/cards/accordion)
- [ ] Add view content modal/expandable
- [ ] Add download functionality

### Skills List Module
- [ ] Create PHP module class and traits
- [ ] Create TypeScript/React edit component
- [ ] Implement skills display
- [ ] Add view definition modal
- [ ] Add download functionality

### Lesson Meta Module (COMPLETED)
- [x] Create PHP module class and traits
- [x] Create TypeScript/React edit component
- [x] Display duration, objectives, model info
- [x] Add visibility toggles and configurable labels in Content tab

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

### Skill Package Processing (BLOCKING for Skill CPT admin)
- [ ] Create `includes/class-skill-processor.php`
- [ ] Hook into `acf/save_post` for `leaderspath_skill` post type
- [ ] Validate uploaded ZIP structure (must contain SKILL.md)
- [ ] Parse YAML frontmatter from SKILL.md (name, description, compatibility)
- [ ] Auto-populate read-only ACF fields from frontmatter
- [ ] Display admin error notice if ZIP validation fails

**Note:** The Skill CPT edit screen won't function properly until this processor is implemented. The read-only fields (skill_name, skill_description, skill_compatibility) will remain empty without it.

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
├── LessonMeta/              # Full working module with ACF integration
│   ├── LessonMeta.php       # Main class implementing DependencyInterface
│   └── LessonMetaTrait/     # Traits: RenderCallback, ModuleClassnames, ModuleStyles
src/
├── index.ts                 # JS module registration (add registerModule() here)
└── components/
    ├── hello-module/        # Simple reference
    └── lesson-meta/         # Full working module
        ├── index.ts         # Module export with metadata + renderers
        ├── edit.tsx         # Visual Builder React component
        ├── module.json      # Module schema (attributes, settings groups)
        ├── types.ts         # TypeScript interfaces
        ├── styles.tsx       # VB styles component
        ├── module-classnames.ts
        ├── placeholder-content.ts
        └── style.scss       # BEM CSS
```

### Critical Divi 5 Module Patterns
- **See `docs/divi-modules.md`** for complete reference with code examples
- **Theme Builder templates:** Use `get_queried_object_id()` not `get_the_ID()`
- **Null safety:** Use `$args['selector'] ?? ''` in ModuleStylesTrait
- **Toggle values:** `'on'`/`'off'` strings (not booleans)
- **Attribute path:** `$attrs['name']['innerContent']['desktop']['value']`

### REST Endpoints Available
- `POST /leaderspath/v1/chat` - Chat with Claude (needs lesson_id, message, optional history/model)
- `GET /leaderspath/v1/lessons/{id}/context` - Get context files for lesson
- `GET /leaderspath/v1/lessons/{id}/skills` - Get skills for lesson
- `GET /leaderspath/v1/context/{id}/download` - Get context file content
- `GET /leaderspath/v1/skills/{id}/download` - Get skill definition

### Build Commands
```bash
npm run build    # Production build
npm run start    # Development with watch
```
