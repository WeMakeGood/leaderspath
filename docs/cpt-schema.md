# LeadersPath Custom Post Types & Taxonomies Schema

**Version:** 0.3.0
**Last Updated:** 2026-02-09

## Overview

This document defines the custom post types (CPTs), taxonomies, and their relationships for the LeadersPath plugin. All CPTs use ACF Pro for custom field management.

### Pedagogical Model

LeadersPath is a **facilitated cohort learning experience**, not a self-paced lesson platform:

| Term | Definition |
|------|------------|
| **Course** | A reusable curriculum that contains ordered Lessons |
| **Lesson** | The atomic teaching unit, taught as a cohesive whole by a facilitator |
| **Activity** | An AI sandbox experiment within a Lesson (what learners DO, not what they LEARN) |
| **Facilitator Guide** | The central teaching document (what to present, when to run activities, discussion prompts) |
| **Context Files** | Reference documents that provide Claude with background information |
| **Skills** | Executable capabilities (Python scripts, workflows) uploaded to Anthropic |

The facilitator presents concepts, learners experiment in AI sandboxes (Activities), and discussion happens human-to-human in the cohort.

---

## Custom Post Types

### 1. Activity (`leaderspath_activity`)

AI sandbox experiments within a facilitated Lesson.

#### Registration Arguments

```php
[
    'label'               => __('Activities', 'leaderspath'),
    'labels'              => [...], // Activity-based labels
    'public'              => true,
    'publicly_queryable'  => true,
    'show_ui'             => true,
    'show_in_menu'        => true,
    'show_in_rest'        => true,
    'rest_base'           => 'activities',
    'menu_position'       => 25,
    'menu_icon'           => 'dashicons-welcome-learn-more',
    'supports'            => ['title', 'editor', 'thumbnail', 'excerpt', 'revisions', 'custom-fields'],
    'has_archive'         => true,
    'rewrite'             => ['slug' => 'activity', 'with_front' => false],
    'capability_type'     => 'leaderspath_activity',
    'map_meta_cap'        => true,
]
```

#### Content Storage

- **Title** (`post_title`): Activity name (e.g., "Experience Sycophantic AI")
- **Content** (`post_content`): Activity instructions for learners ("Try this, notice that")

#### ACF Field Group: Activity Settings

| Field Name | Field Type | Description |
|------------|------------|-------------|
| `activity_duration` | Number | Estimated duration in minutes |
| `activity_references` | Repeater | External resources |
| `activity_references.title` | Text | Resource title |
| `activity_references.url` | URL | Resource link |
| `activity_references.description` | Textarea | Resource description |

#### ACF Field Group: AI Sandbox Configuration

| Field Name | Field Type | Description |
|------------|------------|-------------|
| `chatbot_enabled` | True/False | Enable AI sandbox for this activity |
| `chatbot_model` | Select | Claude model (opus-4.5, sonnet, haiku) |
| `chatbot_allow_model_switch` | True/False | Allow users to switch models |
| `chatbot_system_prompt` | Textarea | Custom system prompt defining the AI behavior learners will experience |
| `chatbot_context_files` | Relationship | Related Context Files |
| `chatbot_skills` | Relationship | Related Skills |
| `chatbot_max_tokens` | Number | Max response tokens (default: 4096) |
| `chatbot_temperature` | Number | Temperature setting (0-1, default: 0.7) |

---

### 2. Lesson (`leaderspath_lesson`)

The atomic teaching unit, taught as a cohesive whole by a facilitator.

#### Registration Arguments

```php
[
    'label'               => __('Lessons', 'leaderspath'),
    'labels'              => [...],
    'public'              => true,
    'publicly_queryable'  => true,
    'show_ui'             => true,
    'show_in_menu'        => true,
    'show_in_rest'        => true,
    'rest_base'           => 'lessons',
    'menu_position'       => 26,
    'menu_icon'           => 'dashicons-book-alt',
    'supports'            => ['title', 'editor', 'thumbnail', 'excerpt', 'revisions', 'custom-fields'],
    'has_archive'         => true,
    'rewrite'             => ['slug' => 'lesson', 'with_front' => false],
    'capability_type'     => 'leaderspath_lesson',
    'map_meta_cap'        => true,
]
```

#### ACF Field Group: Lesson Settings

| Field Name | Field Type | Description |
|------------|------------|-------------|
| `lesson_activities` | Relationship | Ordered list of activities |
| `lesson_difficulty` | Select | Beginner, Intermediate, Advanced |
| `lesson_total_duration` | Text | Total facilitation time (e.g., "90 minutes") |
| `lesson_objectives` | Repeater | Learning objectives for the lesson |
| `lesson_objectives.objective` | Text | Single objective |
| `lesson_access_roles` | Checkbox | User roles that can access |

#### ACF Field Group: Facilitator Content

| Field Name | Field Type | Description |
|------------|------------|-------------|
| `lesson_facilitator_guide` | WYSIWYG | Complete teaching script with timing, activity transitions, discussion prompts |

#### ACF Field Group: Learner Content

| Field Name | Field Type | Description |
|------------|------------|-------------|
| `lesson_learner_overview` | WYSIWYG | What learners will experience (context, not teaching content) |

#### ACF Field Group: Lesson Q&A Chatbot

Optional lesson-level Q&A assistant (different from activity sandboxes).

| Field Name | Field Type | Description |
|------------|------------|-------------|
| `lesson_chatbot_enabled` | True/False | Enable Q&A chatbot for this lesson |
| `lesson_chatbot_model` | Select | Claude model |
| `lesson_chatbot_system_prompt` | Textarea | System prompt (should be helpful assistant) |
| `lesson_chatbot_context_files` | Relationship | Context files for Q&A |
| `lesson_chatbot_max_tokens` | Number | Max response tokens |
| `lesson_chatbot_temperature` | Number | Temperature setting |

---

### 3. Course (`leaderspath_course`)

A reusable curriculum containing Lessons. Groups learners working through lessons on a schedule with a facilitator.

#### Registration Arguments

```php
[
    'label'               => __('Courses', 'leaderspath'),
    'labels'              => [...],
    'public'              => true,
    'publicly_queryable'  => true,
    'show_ui'             => true,
    'show_in_menu'        => true,
    'show_in_rest'        => true,
    'rest_base'           => 'courses',
    'rest_namespace'      => 'wp/v2',
    'query_var'           => true,
    'rewrite'             => ['slug' => 'course', 'with_front' => false],
    'menu_position'       => 27,
    'menu_icon'           => 'dashicons-groups',
    'supports'            => ['title', 'editor', 'thumbnail', 'excerpt', 'revisions', 'custom-fields'],
    'has_archive'         => true,
    'capability_type'     => 'leaderspath_course',
    'map_meta_cap'        => true,
]
```

#### ACF Field Group: Course Settings

| Field Name | Field Type | Description |
|------------|------------|-------------|
| `course_lessons` | Relationship | Ordered list of lessons (`leaderspath_lesson` posts) |
| `course_prerequisites` | Relationship | Courses that should be completed before this one (`leaderspath_course` posts) |

---

### Cohort (WooCommerce Product with `_cohort` Flag)

Cohorts are WooCommerce Simple products with the `_cohort` meta flag set to `yes`. The "Cohort" checkbox appears next to "Virtual" and "Downloadable" in the product data panel, following the same pattern as the wc-donation-platform plugin.

**Not a CPT** — Cohorts are standard WooCommerce Simple products (post type `product`) identified by the `_cohort` post meta key. Checking the Cohort checkbox auto-checks Virtual (no shipping).

#### ACF Field Group: Cohort Settings

| Field Name | Field Type | Description |
|------------|------------|-------------|
| `cohort_courses` | Relationship | Ordered list of courses (`leaderspath_course` posts) |
| `cohort_start_date` | Date Picker | Cohort start date (return format: `Y-m-d`) |
| `cohort_end_date` | Date Picker | Cohort end date (return format: `Y-m-d`) |
| `cohort_facilitator` | User | Facilitator user (filtered to `leaderspath_facilitator` and `administrator` roles) |

**Max Participants:** Uses WooCommerce stock management (Inventory tab > Stock quantity) instead of a custom ACF field. This gives us built-in "X left in stock" display, oversell prevention, and low-stock notifications for free.

**Phase derivation:** The cohort phase (upcoming/active/completed) is computed from `cohort_start_date` and `cohort_end_date` compared to the current date. No separate status field is stored.

#### Enrollment

When a WooCommerce order containing a cohort product is completed, the customer is enrolled:
- User meta `leaderspath_enrollments` (array of cohort product IDs)
- User meta `leaderspath_enrollment_{cohort_id}_date` (enrollment timestamp)
- `leaderspath_student` role granted if not already present

On order refund or cancellation, enrollment is removed.

---

### 4. Context File (`leaderspath_context`)

Markdown files used as context for Claude API calls.

#### Registration Arguments

```php
[
    'label'               => __('Context Files', 'leaderspath'),
    'labels'              => [...],
    'public'              => false,
    'publicly_queryable'  => true, // For REST access
    'show_ui'             => true,
    'show_in_menu'        => 'edit.php?post_type=leaderspath_activity', // Submenu under Activities
    'show_in_rest'        => true,
    'rest_base'           => 'context-files',
    'menu_icon'           => 'dashicons-media-text',
    'supports'            => ['title', 'editor', 'revisions', 'custom-fields'],
    'has_archive'         => false,
    'rewrite'             => false,
    'capability_type'     => 'leaderspath_context',
    'map_meta_cap'        => true,
]
```

#### Content Storage

- **Title** (`post_title`): Human-readable file name
- **Content** (`post_content`): Full markdown content (use code editor or plain textarea)

#### ACF Field Group: Context File Settings

| Field Name | Field Type | Description |
|------------|------------|-------------|
| `context_description` | Textarea | Purpose and usage notes |
| `context_file_type` | Select | Type: System Prompt, Knowledge Base, Instructions, Examples, Other |
| `context_version` | Text | Semantic version (e.g., 1.0.0) |

**Note:** Context Files also use the `leaderspath_context_cat` taxonomy for organization (separate from ACF fields).

---

### 5. Skill (`leaderspath_skill`)

Agentic skill definitions for Claude API.

#### Registration Arguments

```php
[
    'label'               => __('Skills', 'leaderspath'),
    'labels'              => [...],
    'public'              => false,
    'publicly_queryable'  => true, // For REST access
    'show_ui'             => true,
    'show_in_menu'        => 'edit.php?post_type=leaderspath_activity', // Submenu under Activities
    'show_in_rest'        => true,
    'rest_base'           => 'skills',
    'menu_icon'           => 'dashicons-admin-tools',
    'supports'            => ['title', 'revisions', 'custom-fields'],
    'has_archive'         => false,
    'rewrite'             => false,
    'capability_type'     => 'leaderspath_skill',
    'map_meta_cap'        => true,
]
```

#### Skill Package Structure

Skills are ZIP packages following the [Agent Skills Specification](https://docs.anthropic.com/en/docs/agents/skills). Each package contains:

```
skill-name/
├── SKILL.md           # Required - main instructions with YAML frontmatter
├── references/        # Optional - additional documentation
│   └── REFERENCE.md
├── scripts/           # Optional - utility scripts
│   └── utility.py
└── assets/            # Optional - templates, images
    └── template.docx
```

The `SKILL.md` file must begin with YAML frontmatter containing `name` and `description`:

```yaml
---
name: skill-name
description: What this skill does and when to use it.
compatibility: Requires Python 3.9+ (optional)
---
```

#### ACF Field Group: Skill Settings

| Field Name | Field Type | Description |
|------------|------------|-------------|
| `skill_package` | File (ZIP) | The uploaded skill package |
| `skill_name` | Text (read-only) | Extracted from SKILL.md frontmatter |
| `skill_description` | Textarea (read-only) | Extracted from SKILL.md frontmatter |
| `skill_compatibility` | Text (read-only) | Environment prerequisites from frontmatter |
| `skill_version` | Text | Semantic version for tracking |
| `skill_notes` | WYSIWYG | Internal admin notes |
| `skill_anthropic_id` | Text (read-only) | Anthropic skill_id after upload |
| `skill_anthropic_version` | Text (read-only) | Anthropic version timestamp |
| `skill_sync_status` | Select (read-only) | pending/synced/error |
| `skill_sync_error` | Textarea (read-only) | Last error message |
| `skill_last_synced` | Text (read-only) | Last sync timestamp |

---

## Taxonomies

### 1. Topic (`leaderspath_topic`)

Cross-cutting topics for activities and lessons.

#### Registration Arguments

```php
[
    'label'              => __('Topics', 'leaderspath'),
    'labels'             => [...],
    'public'             => true,
    'publicly_queryable' => true,
    'show_ui'            => true,
    'show_in_menu'       => true,
    'show_in_rest'       => true,
    'rest_base'          => 'topics',
    'hierarchical'       => true, // Like categories
    'rewrite'            => ['slug' => 'topic', 'with_front' => false],
    'show_admin_column'  => true,
]
```

#### Associated Post Types

- `leaderspath_activity` (Activities)
- `leaderspath_lesson` (Lessons)

---

### 2. Context Category (`leaderspath_context_cat`)

Organizes context files by type/purpose.

#### Registration Arguments

```php
[
    'label'              => __('Context Categories', 'leaderspath'),
    'labels'             => [...],
    'public'             => false,
    'publicly_queryable' => false,
    'show_ui'            => true,
    'show_in_menu'       => true,
    'show_in_rest'       => true,
    'rest_base'          => 'context-categories',
    'hierarchical'       => true,
    'rewrite'            => false,
    'show_admin_column'  => true,
]
```

#### Associated Post Types

- `leaderspath_context`

#### Default Terms

- Organization Profile
- Brand Guidelines
- Process Documentation
- Technical Specifications
- Example Content

---

### 3. Skill Category (`leaderspath_skill_cat`)

Organizes skills by function.

#### Registration Arguments

```php
[
    'label'              => __('Skill Categories', 'leaderspath'),
    'labels'             => [...],
    'public'             => false,
    'publicly_queryable' => false,
    'show_ui'            => true,
    'show_in_menu'       => true,
    'show_in_rest'       => true,
    'rest_base'          => 'skill-categories',
    'hierarchical'       => true,
    'rewrite'            => false,
    'show_admin_column'  => true,
]
```

#### Associated Post Types

- `leaderspath_skill`

#### Default Terms

- Content Generation
- Data Analysis
- Research
- Code Generation
- Communication

---

## Relationships Diagram

```
┌─────────────────────────────────────────────────────────────────────────┐
│                          FACILITATED LEARNING                            │
├─────────────────────────────────────────────────────────────────────────┤
│                                                                          │
│  ┌─────────────┐                                                         │
│  │   Cohort    │ ─── WooCommerce product (enrollment)                  │
│  │  (WC prod)  │     - Links to Course(s)                              │
│  │             │     - Start/End dates, Max participants                │
│  │             │     - Facilitator, Pricing                            │
│  └──────┬──────┘                                                         │
│         │ links to (many-to-many)                                        │
│         ▼                                                                │
│  ┌─────────────┐                                                         │
│  │   Course    │ ─── Reusable curriculum                                │
│  │             │     - Ordered Lessons                                  │
│  └──────┬──────┘                                                         │
│         │ contains (ordered)                                             │
│         ▼                                                                │
│  ┌─────────────┐                                                         │
│  │   Lesson    │ ─── Atomic teaching unit                               │
│  │             │     - Facilitator Guide (primary teaching doc)         │
│  │             │     - Learner Overview                                 │
│  │             │     - Learning Objectives                              │
│  │             │     - Optional Q&A Chatbot                             │
│  └──────┬──────┘                                                         │
│         │ contains (ordered)                                             │
│         ▼                                                                │
│  ┌─────────────┐                                                         │
│  │  Activity   │ ─── AI sandbox experiment                              │
│  │             │     - "Try this, notice that" instructions             │
│  │             │     - System prompt (defines AI behavior)              │
│  └──────┬──────┘                                                         │
│         │ uses                                                           │
│         ▼                                                                │
│  ┌─────────────────────────────────────┐                                │
│  │  ┌──────────────┐  ┌─────────────┐ │                                │
│  │  │Context Files │  │   Skills    │ │                                │
│  │  │ (reference)  │  │ (executable)│ │                                │
│  │  └──────────────┘  └─────────────┘ │                                │
│  └─────────────────────────────────────┘                                │
│                                                                          │
│  Access Chain:                                                           │
│  User enrolled in Cohort → Cohort links to Course(s) →                 │
│  Course contains Lessons → Lesson contains Activities                   │
│                                                                          │
└─────────────────────────────────────────────────────────────────────────┘

Taxonomies:
┌─────────────┐
│   Topic     │──────► Activity, Lesson
└─────────────┘

┌─────────────────┐
│ Context Category│──────► Context File
└─────────────────┘

┌─────────────────┐
│ Skill Category  │──────► Skill
└─────────────────┘
```

---

## Chatbot Configuration: Activity vs Lesson

| Aspect | Activity Sandbox | Lesson Q&A Bot |
|--------|------------------|----------------|
| **Purpose** | Demonstrate specific AI behavior | Answer questions about content |
| **System Prompt** | Crafted to show specific behavior | Helpful, knowledgeable assistant |
| **Context** | Activity-specific files | All lesson content |
| **Tone** | Varies by activity design | Consistently helpful |
| **Placement** | Activity page | Lesson page |
| **Privacy** | Complete sandbox (no logging) | Complete sandbox (no logging) |

---

## Capabilities

### Custom Capability Types

Each CPT has its own capability type for granular permissions:

| CPT | Capability Type | Example Capabilities |
|-----|-----------------|---------------------|
| Activity | `leaderspath_activity` | `edit_leaderspath_activity`, `delete_leaderspath_activities` |
| Lesson | `leaderspath_lesson` | `edit_leaderspath_lesson`, `publish_leaderspath_lessons` |
| Course | `leaderspath_course` | `edit_leaderspath_course`, `read_private_leaderspath_courses` |
| Context | `leaderspath_context` | `edit_leaderspath_context` |
| Skill | `leaderspath_skill` | `edit_leaderspath_skill` |

### Role Assignments

**Administrator:**
- All capabilities for all CPTs

**Editor:**
- All capabilities for Activities, Lessons
- Read-only for Courses, Context Files, Skills

**LeadersPath Facilitator** (custom role):
- All Student capabilities (below)
- `leaderspath_view_facilitator_content` - View facilitator guides

**LeadersPath Student** (custom role):
- `read` - Basic WordPress read
- `leaderspath_access_activities` - View published activities
- `leaderspath_access_chatbot` - Use chatbot feature
- `leaderspath_view_context` - View context file contents
- `leaderspath_download_context` - Download context files

---

## REST API Endpoints

All CPTs are REST-enabled. Custom endpoints supplement the defaults:

| Endpoint | Method | Description |
|----------|--------|-------------|
| `/wp-json/leaderspath/v1/chat` | POST | Send message to Claude (activity or lesson) |
| `/wp-json/leaderspath/v1/activities/{id}/context` | GET | Activity's context files |
| `/wp-json/leaderspath/v1/activities/{id}/skills` | GET | Activity's skills |
| `/wp-json/leaderspath/v1/lessons/{id}/context` | GET | Lesson Q&A chatbot's context files |
| `/wp-json/leaderspath/v1/context/{id}/download` | GET | Download context file |
| `/wp-json/leaderspath/v1/skills/{id}/download` | GET | Download skill definition |

---

## Migration Considerations

When activating the plugin:

1. Register all CPTs and taxonomies
2. Flush rewrite rules
3. Create default taxonomy terms
4. Add capabilities to Administrator role
5. Create LeadersPath Student role if not exists

When deactivating:

1. Do NOT delete CPTs or data
2. Remove custom roles (optional, configurable)
3. Keep capabilities for data integrity

### Field Names

- The Activity CPT slug is `leaderspath_activity`
- The `lesson_activities` field stores activity IDs for a lesson
- The `course_lessons` field stores lesson IDs for a course
- All ACF fields use `activity_` prefix (e.g., `activity_duration`, `activity_references`)
