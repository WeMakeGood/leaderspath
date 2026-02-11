# Divi 5 Module Development

**Last Updated:** 2026-02-11
**Status:** Research complete; ready for implementation

---

## Current State

All Divi 5 module code was removed in commit `844094c` (2026-02-10). The previous implementation was built on incomplete and piecemeal research, resulting in antipatterns and inconsistent approaches across modules.

A comprehensive research phase was completed on 2026-02-11. Findings are documented in:

- **[wordpress-rendering-pipeline.md](wordpress-rendering-pipeline.md)** — WordPress block rendering lifecycle, filters, Interactivity API, block context
- **[divi5-module-architecture.md](divi5-module-architecture.md)** — Divi 5 module registration, rendering, styling, file structure, gotchas

Do not reference prior module implementations from git history as patterns — they contained known issues.

## What Remains

- `modules/Shared/PostIdHelper.php` — CPT post ID resolution with fallback to first published post. **Reviewed: Keep.**
- `modules/Shared/CustomCssTrait.php` — Custom CSS from block type registry. **Reviewed: Keep.**
- `modules/Shared/ModuleClassnamesTrait.php` — Text and element classnames. **Reviewed: Keep.**

These shared PHP traits have been re-evaluated against patterns discovered in the official extension example repo and are consistent with validated patterns.

## What Needs to Be Built

The plugin needs Divi 5 modules to display data from these CPTs:

**Activity modules** (displayed on Activity single template):
- Chatbot — Interactive chat UI connected to Claude API
- Activity Meta — Duration, model info
- Context Library — Card grid of context files with view/download
- Skills List — Card grid of skills with download

**Lesson modules** (displayed on Lesson single template):
- Lesson Meta — Duration, difficulty, activity count
- Lesson Objectives — Learning objectives list
- Lesson Activities — Ordered activity list with links

**Course modules** (displayed on Course single template):
- Course Lessons — Ordered lesson list with links

**Note:** Learner Overview and Facilitator Guide are WYSIWYG fields that can use Divi's native Text module with ACF dynamic content.

## Implementation Order

See [divi5-module-architecture.md, section 12](divi5-module-architecture.md#12-leaderspath-module-plan) for the prioritized build order and complexity assessment.

## Research Phase (Completed 2026-02-11)

- [x] Study the official Divi 5 extension example repository (`d5-extension-example-modules`)
- [x] Study Divi 5 type packages (`@divi/types`, `@types/divi__module`, etc.)
- [x] Document validated patterns for Theme Builder modules (reading current post data)
- [x] Document the correct module registration, rendering, and styling approaches
- [x] Document WordPress rendering pipeline for context
- [ ] Validate patterns by building a simple test module first
- [ ] Build production modules

### Key Research Findings

1. **Dual rendering:** Frontend is 100% PHP server-rendered; Visual Builder is 100% React
2. **Registration hook:** `divi.moduleLibrary.registerModuleLibraryStore.after` (timing is critical)
3. **Attribute format:** ALL values must use `{ desktop: { value: "..." } }` breakpoint-state format
4. **Boolean toggles:** Use `'on'`/`'off'` strings, not `true`/`false`
5. **Edit components:** Must use `ModuleContainer` (not `Module`), `elements.render()` for content
6. **No Interactivity API:** Divi bypasses the WP block pipeline; use `wp_enqueue_script` for frontend JS
7. **ACF data:** Query directly in `render_callback`; do not use dynamic content tokens for complex fields

## Data Contracts

See `docs/data-contracts.md` for the ACF field → REST endpoint mapping that modules will need to consume.
