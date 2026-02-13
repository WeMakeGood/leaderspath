# Data Contracts: ACF Fields → REST API

> **Last Updated:** 2026-02-11

When you add, change, or remove an ACF field, check this document to find every REST endpoint that needs updating. See `docs/ui-ux-catalog.md` for which Divi modules consume each field.

---

## Activity (`leaderspath_activity`)

| ACF Field | REST Endpoint | Usage |
|-----------|--------------|-------|
| `activity_duration` | — | Activity duration in minutes |
| `chatbot_enabled` | `POST /chat` (internal) | Gate for activity sandbox |
| `chatbot_model` | `POST /chat` (internal) | API param |
| `chatbot_system_prompt` | `POST /chat` (internal) | System prompt |
| `chatbot_context_files` | `POST /chat` (internal) | Context injection |
| `chatbot_skills` | `POST /chat` (internal) | Container skills array |
| `chatbot_max_tokens` | `POST /chat` (internal) | API param |
| `chatbot_temperature` | `POST /chat` (internal) | API param |
| `chatbot_allow_model_switch` | `POST /chat` (internal) | Model selector toggle |
| `activity_references` | — | Not yet surfaced |

## Lesson (`leaderspath_lesson`)

| ACF Field | REST Endpoint | Usage |
|-----------|--------------|-------|
| `lesson_total_duration` | — | Total facilitation time |
| `lesson_activities` | — | Ordered activity list |
| `lesson_objectives` | — | Learning objectives repeater |
| `lesson_facilitator_guide` | — | Teaching script (WYSIWYG) |
| `lesson_learner_overview` | — | Learner-facing content (WYSIWYG) |
| `lesson_access_roles` | — | Access control |
| `lesson_chatbot_enabled` | `POST /chat` (internal) | Q&A gate |
| `lesson_chatbot_model` | `POST /chat` (internal) | API param |
| `lesson_chatbot_system_prompt` | `POST /chat` (internal) | System prompt |
| `lesson_chatbot_context_files` | `POST /chat` (internal) | Context injection |
| `lesson_chatbot_max_tokens` | `POST /chat` (internal) | API param |
| `lesson_chatbot_temperature` | `POST /chat` (internal) | API param |

## Course (`leaderspath_course`)

| ACF Field | REST Endpoint | Usage |
|-----------|--------------|-------|
| `course_lessons` | — | Ordered lesson list |
| `course_prerequisites` | — | Prerequisite courses (relationship, returns IDs) |

## Context (`leaderspath_context`)

| ACF Field | REST Endpoint | Usage |
|-----------|--------------|-------|
| `context_description` | `GET /context/{id}/download` | Description metadata |
| `context_version` | `GET /context/{id}/download` | Version metadata |
| _(post_content)_ | `GET /context/{id}/download` | Full content |

## Skill (`leaderspath_skill`)

| ACF Field | REST Endpoint | Usage |
|-----------|--------------|-------|
| `skill_name` | `GET /skills/{id}/download` | Display name |
| `skill_description` | `GET /skills/{id}/download` | Description |
| `skill_compatibility` | `GET /skills/{id}/download` | Environment info |
| `skill_version` | `GET /skills/{id}/download` | Version |
| `skill_package` | `GET /skills/{id}/download` | ZIP attachment URL |
| `skill_anthropic_id` | `POST /chat` (internal) | Container skills array |
| `skill_anthropic_version` | `POST /chat` (internal) | Container skills array |
| `skill_sync_status` | — | Admin-only status indicator |
| `skill_sync_error` | — | Admin-only error display |
| `skill_last_synced` | — | Admin-only metadata |
| `skill_notes` | — | Admin-only notes |

## Cohort (WooCommerce `product` with `_cohort` meta = `yes`)

| ACF Field | REST Endpoint | Usage |
|-----------|--------------|-------|
| `cohort_courses` | `POST /chat` (enrollment check) | Links cohort to courses for access gating |
| `cohort_start_date` | — | Derives cohort phase (upcoming/active/completed) |
| `cohort_end_date` | — | Derives cohort phase |
| `cohort_facilitator` | — | Admin display |

**Prerequisite Aggregation:** `WooCommerce::get_cohort_prerequisites( $cohort_id )` returns a flat `array<int>` of prerequisite course IDs aggregated from the cohort's linked courses, de-duplicated and excluding courses already in the cohort.

**Max Participants:** Uses WooCommerce stock management (`_stock` meta / Inventory tab) rather than a custom field.

### Enrollment User Meta

| Meta Key | Type | Description |
|----------|------|-------------|
| `leaderspath_enrollments` | `array<int>` | Cohort product IDs the user is enrolled in |
| `leaderspath_enrollment_{cohort_id}_date` | `string` (MySQL datetime) | Enrollment timestamp per cohort |

### Access Chain

The `POST /chat` permission callback checks enrollment when WooCommerce is active:

```
User enrolled in Cohort → Cohort links to Course(s) → Course contains Lessons → Lesson contains Activities
```

Admin and Editor roles bypass enrollment checks. If WooCommerce is not active, enrollment is not enforced.

---

## Active REST Endpoints

All endpoints are under the `leaderspath/v1` namespace.

| Route | Method | Auth | Purpose |
|-------|--------|------|---------|
| `POST /chat` | POST | Login + `leaderspath_access_chatbot` + nonce + enrollment (if WC active) | Send message (activity sandbox or lesson Q&A) |
| `GET /context/{id}/download` | GET | Login | Download context file content |
| `GET /skills/{id}/download` | GET | Login | Download skill package metadata |

**Note:** VB preview endpoints for Divi modules were removed. They will be re-added during the frontend rebuild based on each module's data requirements.
