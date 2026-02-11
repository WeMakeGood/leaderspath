# Divi 5 Module Development

**Last Updated:** 2026-02-10
**Status:** Pending comprehensive research

---

## Current State

All Divi 5 module code was removed in commit `844094c` (2026-02-10). The previous implementation was built on incomplete and piecemeal research, resulting in antipatterns and inconsistent approaches across modules.

A clean-room research phase is needed before rebuilding. Do not reference prior module implementations from git history as patterns — they contained known issues.

## What Remains

- `modules/Shared/PostIdHelper.php` — CPT post ID resolution with fallback to first published post
- `modules/Shared/CustomCssTrait.php` — Custom CSS from block type registry
- `modules/Shared/ModuleClassnamesTrait.php` — Text and element classnames

These shared PHP traits may be useful but should be re-evaluated against patterns discovered during the research phase.

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

## Research Phase Requirements

Before writing any module code, a comprehensive research phase should:

1. Study the official Divi 5 extension example repository
2. Study Divi 5 core module source code (PHP and JSON)
3. Document validated patterns for Theme Builder modules (reading current post data)
4. Document the correct module registration, rendering, and styling approaches
5. Validate patterns by building a simple test module first
6. Document findings in this file before proceeding to production modules

## Data Contracts

See `docs/data-contracts.md` for the ACF field → REST endpoint mapping that modules will need to consume.
