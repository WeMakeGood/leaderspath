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

### Notes
- **Deploy prerequisite:** Bricks > Settings > Custom code must enable
  "Code execution" for the Administrator role before `{echo:}` tags run.
