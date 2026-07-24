# Changelog

All notable changes to the LeadersPath plugin are documented here.

The format is based on [Keep a Changelog](https://keepachangelog.com/),
and this project adheres to [Semantic Versioning](https://semver.org/).

## [Unreleased]

### Added
- **Bricks integration layer** (`docs/bricks-integration.md`). Global `lp_*`
  functions expose plugin logic to Bricks at render time:
  - `lp_user_is_enrolled()` — enrollment gating (fails closed).
  - `lp_enrolled_cohorts( $phase )` — the user's cohorts, phase-filtered, for the
    dashboard's per-phase query loops.
  - `lp_is_last_activity()` — right-arrow dim state on the lesson page.
  Registered via `bricks/code/echo_function_names` and a custom
  "User is enrolled in this cohort" Element Condition.
- **ACF fields:** `activity_instructions` (learner-facing WYSIWYG, rendered as
  the opening chat message; never sent to the AI), `current_lesson` (cohort
  product — "this week" pointer, scoped to the cohort's own lessons), and
  `cohort_video` (cohort page intro video).
- **Lesson-page chatbot behavior:** each activity's chatbot persists its own
  conversation in memory for the page session; sections initialize once when
  first shown (MutationObserver on `[data-lp-workspace]`), never on teardown.
- **Per-activity reset** ("Start over"): `window.LeadersPath.resetActivity(id)`
  clears one activity's conversation + container ID and re-shows its opening
  instructions. Scoped to that activity; other conversations untouched.
- `WooCommerce::get_cohort_lessons()` — resolves a cohort's lessons via its
  courses (cohort → `cohort_courses` → `course_lessons`).

### Changed
- Chatbot renderer emits `data-activity-id` and the opening-instructions block
  (plus an inert `<template>` used to restore instructions on reset).
- Plugin version bumped to 0.7.0 (was stale at 0.1.0; aligns with existing
  `@since 0.7.0` markers in the codebase).
- **Claude API modernization.** Removed `temperature` (and the obsolete
  "opus needs temperature=1" logic) from all four request builders — sampling
  parameters are removed from the current API and 400 on current models;
  behavior is steered by the system prompt. Refreshed stale fallback model IDs
  to current bare aliases (`claude-sonnet-5`, `claude-haiku-4-5`,
  `claude-opus-4-8`); model resolution now prefers bare aliases over dated
  snapshots. Updated default tool types to `code_execution_20260521`,
  `web_search_20260209`, `web_fetch_20260209` (the current web tools require
  code-exec `20260120`+ alongside them). Corrected stale `opus-4.5` model slug
  to `opus` in settings.

### Removed
- ACF `chatbot_temperature` / `lesson_chatbot_temperature` fields — the API no
  longer accepts sampling parameters.

### Migration
- Sites with saved settings retain old tool-type strings in `wp_options`
  (`leaderspath_options`). This release ships current defaults, but existing
  rows must be migrated. Dev/test site migrated in place (old values backed up
  to `leaderspath_options_backup_premodernize`). **Production deploy:** update
  the three tool-type settings, or re-save the Settings page. Code execution
  and web tools are now GA — the beta headers remain valid but are optional.

### Notes
- **Deploy prerequisite:** Bricks > Settings > Custom code must enable
  "Code execution" for the Administrator role before `{echo:}` tags run.
