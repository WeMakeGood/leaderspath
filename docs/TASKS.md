# LeadersPath Development Tasks

**Last Updated:** 2026-02-11
**Current Phase:** Phase 8 complete; Frontend Rebuild (research complete, implementation pending)

---

## Task Status Legend

- `[ ]` - Not started
- `[~]` - In progress
- `[x]` - Completed
- `[!]` - Blocked (see notes)

---

## Completed Phases (see git history for details)

- **Phase 1: Foundation** — Plugin scaffolding, docs, build system, Divi 5 hello-module proof of concept
- **Phase 2: Data Layer** — 5 CPTs, 3 taxonomies, ACF field groups, roles & capabilities
- **Phase 3: Admin Interface** — Settings page (encrypted API key, model selection), custom admin columns, quick edit
- **Phase 4: REST API & Claude Integration** — Chat endpoint, context/skill downloads, Claude API with container + code execution + skills
- **Phase 5: Divi 5 Modules** — 9 modules built, then **removed** (`844094c`) due to incomplete Divi 5 research leading to antipatterns. Core plugin infrastructure retained.

---

## Current: Frontend Rebuild

All Divi 5 module code (PHP modules, TypeScript components, build output, frontend assets, VB preview REST endpoints) was removed in commit `844094c`. The core plugin infrastructure remains intact.

### What Was Retained
- All `includes/` classes (CPTs, taxonomies, capabilities, ACF fields, Claude API, REST API, skill processor)
- All `admin/` classes (settings, admin columns, admin menu)
- `modules/Shared/` PHP traits (PostIdHelper, CustomCssTrait, ModuleClassnamesTrait) — reviewed and validated
- Chat REST endpoint and context/skill download endpoints
- Build tooling (webpack config, package.json, composer.json)

### What Was Removed
- All 9 Divi 5 modules (PHP + TypeScript)
- `modules/Modules.php` (module registration hub)
- `src/components/` and `src/index.ts`
- `scripts/bundle.js`, `styles/bundle.css`, `modules-json/`
- `assets/js/chatbot.js`, `assets/js/context-modal.js`
- `assets/css/chatbot.css`, `assets/css/context-modal.css`
- All VB preview REST endpoints from `class-rest-api.php`

### Research (Completed 2026-02-11)

- [x] WordPress block rendering pipeline research → `docs/wordpress-rendering-pipeline.md`
- [x] Divi 5 module architecture research → `docs/divi5-module-architecture.md`
- [x] Official extension example repo analysis (`d5-extension-example-modules`)
- [x] `@divi/types` package analysis (field library, style library, module library)
- [x] Shared PHP traits re-evaluated against validated patterns
- [x] Updated `docs/divi-modules.md` with research status and key findings

### Pending Tasks

- [ ] Validate patterns by building a simple test module first
- [ ] Rebuild modules from validated patterns (see `docs/divi5-module-architecture.md` section 12 for build order)
- [ ] Test in Divi 5 Visual Builder
- [ ] Test frontend rendering

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

Cohorts are a WooCommerce product type for enrollment management.

- [x] Create WooCommerce Cohort product type (`WC_Product_Cohort` extending `WC_Product_Simple`)
- [x] Cohort product linked to Course CPT (ACF relationship field, multiple courses per cohort)
- [x] Enrollment management via WooCommerce orders (enroll on completed, unenroll on refund/cancel)
- [x] Cohort-specific settings (start/end dates, max participants, facilitator via ACF)
- [x] Access control: enrollment gates Lesson/Activity access via REST API permission checks
- [x] Admin dashboard: cohort count and quick action button
- [x] Admin columns: courses, phase, enrollees, dates on product list
- [x] Test data script: creates sample cohort products
- [x] Documentation updated (data-contracts, cpt-schema, plugin-design, TASKS)

---

## Phase 8: Schema Refinements

- [x] Move prerequisites from Activity level to Course level (ACF field migration)
- [x] Add `course_prerequisites` relationship field to Course Settings
- [x] Add `WooCommerce::get_cohort_prerequisites()` for aggregated prerequisite resolution
- [x] Normalize `lesson_activities` return_format from `object` to `id` for consistency
- [x] Update documentation (data-contracts, cpt-schema, content-creation-guide, TASKS)

---

## Discovered Tasks

- [ ] Handle file outputs from code execution (deferred — not critical for MVP)
- [ ] Optional: Streaming response support (SSE)
- [ ] Handle skill deletion (delete from Anthropic when trashed?)

---

## Decisions Log

| Date | Decision | Rationale |
|------|----------|-----------|
| 2026-01-29 | CPTs for Context Files and Skills | WordPress revision history, familiar admin UI, ACF integration |
| 2026-01-29 | No conversation persistence | Fresh start on page reload enables experimentation |
| 2026-01-29 | Single plugin-wide API key | Simpler management, billing at org level |
| 2026-01-29 | No rate limiting initially | Paid service, trust users until abuse occurs |
| 2026-01-29 | Streaming optional | Nice UX but adds complexity |
| 2026-01-29 | Standard chat bubble UI | Recognizable pattern, configurable colors |
| 2026-01-29 | Inside-out development order | Data layer first, then admin, then frontend |
| 2026-01-29 | ACF fields via PHP API | Programmatic registration for version control |
| 2026-01-29 | Skills as ZIP packages | Agent Skills Spec format |
| 2026-02-03 | Facilitated cohort learning model | Facilitator-led, not self-paced |
| 2026-02-03 | Dual chatbot modes | Activity Sandbox (demonstrate behaviors) vs Lesson Q&A (helpful assistant) |
| 2026-02-03 | Privacy-first Q&A bot | No logging, no access restrictions |
| 2026-02-09 | Nomenclature: Course→Lesson, Cohort→Course | Lesson = atomic teaching unit, Course = curriculum |
| 2026-02-10 | Remove all Divi 5 module code | Incomplete research led to antipatterns; clean rebuild needed |
| 2026-02-10 | Cohort as product checkbox | Checkbox next to Virtual/Downloadable (like wc-donation-platform), not custom product type |
| 2026-02-10 | ACF Pro for Cohort fields | Same UI patterns as all other LeadersPath field groups |
| 2026-02-10 | User meta for enrollment | Simple serialized array; sufficient for MVP volumes |
| 2026-02-10 | Cohort phase derived from dates | No manual status field; auto-computed from start/end dates |
| 2026-02-10 | Multiple courses per cohort | Supports bundled curricula / certificate programs |
| 2026-02-10 | Graceful WC degradation | Plugin works without WC; enrollment not enforced |
| 2026-02-11 | Prerequisites at Course level, not Activity | Courses are the right abstraction for sequencing; activities are experiments within lessons |
| 2026-02-11 | All ACF relationship fields return IDs | Consistent `return_format => 'id'` across all relationship fields |
| 2026-02-11 | Cohort prereq aggregation via helper method | `get_cohort_prerequisites()` collects from linked courses, de-duplicates, excludes self |
| 2026-02-11 | Divi 5 frontend is 100% PHP | No React/hydration on frontend; VB is 100% React; use vanilla JS for interactive modules |
| 2026-02-11 | No Interactivity API for Divi modules | Divi bypasses WP block pipeline; use `wp_enqueue_script` for frontend JS instead |
| 2026-02-11 | Keep shared PHP traits | PostIdHelper, CustomCssTrait, ModuleClassnamesTrait validated against official patterns |
| 2026-02-11 | Query ACF directly in render_callback | Don't use Divi dynamic content tokens for complex fields (repeaters, relationships) |
| 2026-02-11 | Shared DOM structure, separate content | PHP is authoritative renderer; VB matches CSS classes for styling parity, uses placeholder/sample content |
| 2026-02-11 | Single Chatbot module for Activity + Lesson | Module detects CPT and reads appropriate `chatbot_*` or `lesson_chatbot_*` fields |
| 2026-02-11 | WYSIWYG fields use native Divi Text module | Facilitator Guide + Learner Overview use ACF dynamic content, not custom modules |

---

## Quick Reference

### Test Data

| Type | IDs | Notes |
|------|-----|-------|
| Activities | 74-77 | All have chatbot enabled, various context/skills |
| Lessons | 78-79 | AI Fundamentals (3 activities), AI in Practice (1 activity) |
| Context Files | 69-71 | Ethics, Prompt Engineering, Conversation Flows |
| Skills | 72-73 | Code Review, Writing Editor |
| Course | 80 | Spring 2026, linked to Lesson 78 |

### CLI Scripts

```bash
# Full chat system test
wp eval-file wp-content/plugins/leaderspath/bin/test-chat.php [activity_id] [message]

# Show assembled system prompt
wp eval-file wp-content/plugins/leaderspath/bin/show-system-prompt.php [activity_id]

# Create test data
wp eval-file wp-content/plugins/leaderspath/bin/create-test-data.php
```

### Active REST Endpoints

| Endpoint | Method | Description |
|----------|--------|-------------|
| `/leaderspath/v1/chat` | POST | Send message (activity_id or lesson_id + message + history) |
| `/leaderspath/v1/context/{id}/download` | GET | Download context file content |
| `/leaderspath/v1/skills/{id}/download` | GET | Download skill package metadata |
