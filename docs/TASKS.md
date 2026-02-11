# LeadersPath Development Tasks

**Last Updated:** 2026-02-10
**Current Phase:** Frontend Rebuild (research pending)

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
- `modules/Shared/` PHP traits (PostIdHelper, CustomCssTrait, ModuleClassnamesTrait) — may need re-evaluation
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

### Pending Tasks

- [ ] Comprehensive Divi 5 module research (clean-room, not based on prior attempts)
- [ ] Design module architecture based on research findings
- [ ] Rebuild modules from validated patterns
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

Cohorts will become a WooCommerce product type for enrollment management.

- [ ] Create WooCommerce Cohort product type
- [ ] Cohort product linked to Course CPT
- [ ] Enrollment management via WooCommerce orders
- [ ] Cohort-specific settings (start/end dates, max participants)
- [ ] Access control: learner enrollment gates Lesson/Activity access

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
