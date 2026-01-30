# LeadersPath Development Tasks

**Last Updated:** 2026-01-29
**Current Phase:** Data Layer (Inside-Out Development)

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

## Phase 3: Admin Interface (CURRENT)

### Settings Page
- [x] Create `admin/class-settings.php`
- [x] Add settings page under Settings menu
- [x] Claude API key field (encrypted storage)
- [x] Default model selection
- [x] Debug/logging toggle

### Admin Enhancements
- [ ] Custom admin columns for Lessons (duration, course, chatbot status)
- [ ] Custom admin columns for Courses (lesson count, status)
- [ ] Quick edit support where appropriate
- [ ] Admin notices for missing API key

---

## Phase 4: REST API & Claude Integration

### REST API Endpoints
- [ ] Create `includes/class-rest-api.php`
- [ ] `POST /wp-json/leaderspath/v1/chat` - Send message to Claude
- [ ] `GET /wp-json/leaderspath/v1/lessons/{id}/context` - Get lesson context files
- [ ] `GET /wp-json/leaderspath/v1/lessons/{id}/skills` - Get lesson skills
- [ ] `GET /wp-json/leaderspath/v1/context/{id}/download` - Download context file
- [ ] `GET /wp-json/leaderspath/v1/skills/{id}/download` - Download skill definition
- [ ] Implement authentication (nonce for logged-in, capability checks)

### Claude API Handler
- [ ] Create `includes/class-claude-api.php`
- [ ] Implement Messages API integration
- [ ] Context assembly from lesson files
- [ ] Model selection (Opus 4.5, Sonnet, Haiku)
- [ ] Error handling and logging
- [ ] Optional: Streaming response support (SSE)

---

## Phase 5: Divi 5 Modules (Frontend)

Now that data layer exists, build the UI modules.

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

### Lesson Meta Module
- [ ] Create PHP module class and traits
- [ ] Create TypeScript/React edit component
- [ ] Display duration, objectives, model info
- [ ] Add layout options (stacked, inline, grid)

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
