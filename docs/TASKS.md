# LeadersPath Development Tasks

**Last Updated:** 2026-01-29
**Current Phase:** Core Module Development

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

## Phase 2: Core Modules (CURRENT)

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
- [ ] Create module.json with settings schema
- [ ] Implement file list display (list/cards/accordion)
- [ ] Add view content modal/expandable
- [ ] Add download functionality

### Skills List Module
- [ ] Create PHP module class and traits
- [ ] Create TypeScript/React edit component
- [ ] Create module.json with settings schema
- [ ] Implement skills display
- [ ] Add view definition modal
- [ ] Add download functionality

### Lesson Meta Module
- [ ] Create PHP module class and traits
- [ ] Create TypeScript/React edit component
- [ ] Create module.json with settings schema
- [ ] Display duration, objectives, model info
- [ ] Add layout options (stacked, inline, grid)

---

## Phase 3: Backend Infrastructure

### Custom Post Types
- [ ] Register Lesson CPT
- [ ] Register Course CPT
- [ ] Register Cohort CPT
- [ ] Register Context File CPT
- [ ] Register Skill CPT
- [ ] Create ACF field groups (or define in code)

### Taxonomies
- [ ] Register Topic taxonomy
- [ ] Register Context Category taxonomy
- [ ] Register Skill Category taxonomy
- [ ] Create default terms

### REST API
- [ ] Create `/chat` endpoint for Claude API
- [ ] Create `/lesson/{id}/context` endpoint
- [ ] Create `/lesson/{id}/skills` endpoint
- [ ] Create `/context-files/{id}/download` endpoint
- [ ] Create `/skills/{id}/download` endpoint
- [ ] Implement proper authentication/nonce verification

### Claude API Integration
- [ ] Create API handler class
- [ ] Implement streaming response support
- [ ] Add context injection from lesson files
- [ ] Add skill execution support
- [ ] Create settings page for API key
- [ ] Implement error handling

---

## Phase 4: Access Control

- [ ] Define custom capabilities
- [ ] Create LeadersPath Student role
- [ ] Implement role-based content restriction
- [ ] Add admin settings for role management
- [ ] Test access control with different user roles

---

## Phase 5: Polish & Testing

- [ ] Write PHPUnit tests for core functionality
- [ ] Write Jest tests for React components
- [ ] Add inline documentation
- [ ] Create user documentation
- [ ] Performance optimization
- [ ] Security audit

---

## Discovered Tasks

Tasks discovered during development that need to be addressed:

_(Add items here as they're discovered)_

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

---

## Session Notes

Brief notes from each development session:

### Session 1 (2026-01-29)
- Reviewed interview synthesis document
- Created comprehensive documentation (plugin-design.md, cpt-schema.md, divi-modules.md)
- Updated CLAUDE.md with development guidelines
- Built and tested Hello Module - confirmed Divi 5 integration works
- Set up persistent task tracking system
