# LeadersPath Custom Post Types & Taxonomies Schema

**Version:** 0.1.0
**Last Updated:** 2026-01-29

## Overview

This document defines the custom post types (CPTs), taxonomies, and their relationships for the LeadersPath plugin. All CPTs use ACF Pro for custom field management.

---

## Custom Post Types

### 1. Lesson (`leaderspath_lesson`)

The primary content unit for interactive learning experiences.

#### Registration Arguments

```php
[
    'label'               => __('Lessons', 'leaderspath'),
    'labels'              => [...], // Standard label array
    'public'              => true,
    'publicly_queryable'  => true,
    'show_ui'             => true,
    'show_in_menu'        => true,
    'show_in_rest'        => true,
    'rest_base'           => 'lessons',
    'menu_position'       => 25,
    'menu_icon'           => 'dashicons-welcome-learn-more',
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
| `lesson_duration` | Number | Estimated duration in minutes |
| `lesson_objectives` | Repeater | Learning objectives |
| `lesson_objectives.objective` | Text | Single objective |
| `lesson_prerequisites` | Relationship | Required lessons (self-referential) |
| `lesson_references` | Repeater | External resources |
| `lesson_references.title` | Text | Resource title |
| `lesson_references.url` | URL | Resource link |
| `lesson_references.description` | Textarea | Resource description |

#### ACF Field Group: Chatbot Configuration

| Field Name | Field Type | Description |
|------------|------------|-------------|
| `chatbot_enabled` | True/False | Enable chatbot for this lesson |
| `chatbot_model` | Select | Claude model (opus-4.5, sonnet, haiku) |
| `chatbot_allow_model_switch` | True/False | Allow users to switch models |
| `chatbot_system_prompt` | Textarea | Custom system prompt |
| `chatbot_context_files` | Relationship | Related Context Files |
| `chatbot_skills` | Relationship | Related Skills |
| `chatbot_max_tokens` | Number | Max response tokens (default: 4096) |
| `chatbot_temperature` | Number | Temperature setting (0-1, default: 0.7) |

---

### 2. Course (`leaderspath_course`)

Container for organizing lessons into learning paths.

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
    'menu_position'       => 26,
    'menu_icon'           => 'dashicons-book-alt',
    'supports'            => ['title', 'editor', 'thumbnail', 'excerpt', 'revisions', 'custom-fields'],
    'has_archive'         => true,
    'rewrite'             => ['slug' => 'course', 'with_front' => false],
    'capability_type'     => 'leaderspath_course',
    'map_meta_cap'        => true,
]
```

#### ACF Field Group: Course Settings

| Field Name | Field Type | Description |
|------------|------------|-------------|
| `course_lessons` | Relationship | Ordered list of lessons |
| `course_difficulty` | Select | Beginner, Intermediate, Advanced |
| `course_total_duration` | Number | Calculated total duration (read-only or computed) |
| `course_access_roles` | Checkbox | User roles that can access |

---

### 3. Cohort (`leaderspath_cohort`)

Groups of learners working through courses on a schedule.

#### Registration Arguments

```php
[
    'label'               => __('Cohorts', 'leaderspath'),
    'labels'              => [...],
    'public'              => false, // Admin only
    'publicly_queryable'  => false,
    'show_ui'             => true,
    'show_in_menu'        => true,
    'show_in_rest'        => true,
    'rest_base'           => 'cohorts',
    'menu_position'       => 27,
    'menu_icon'           => 'dashicons-groups',
    'supports'            => ['title', 'revisions', 'custom-fields'],
    'has_archive'         => false,
    'capability_type'     => 'leaderspath_cohort',
    'map_meta_cap'        => true,
]
```

#### ACF Field Group: Cohort Settings

| Field Name | Field Type | Description |
|------------|------------|-------------|
| `cohort_course` | Post Object | Associated course |
| `cohort_start_date` | Date Picker | Start date |
| `cohort_end_date` | Date Picker | End date |
| `cohort_instructor` | User | Instructor/facilitator |
| `cohort_language` | Select | Language (English, Spanish, etc.) |
| `cohort_timezone` | Select | Timezone |
| `cohort_max_participants` | Number | Maximum enrollment |
| `cohort_status` | Select | Upcoming, Active, Completed, Cancelled |

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
    'show_in_menu'        => 'edit.php?post_type=leaderspath_lesson', // Submenu under Lessons
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
| `context_version` | Text | Semantic version (e.g., 1.0.0) |
| `context_category` | Taxonomy | Context category for organization |
| `context_file_type` | Select | Type: System Prompt, Knowledge Base, Instructions, Other |

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
    'show_in_menu'        => 'edit.php?post_type=leaderspath_lesson', // Submenu under Lessons
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

#### ACF Field Group: Skill Settings

| Field Name | Field Type | Description |
|------------|------------|-------------|
| `skill_definition` | Textarea (code) | JSON/YAML skill definition |
| `skill_description` | Textarea | What this skill does |
| `skill_documentation` | WYSIWYG | Usage documentation |
| `skill_version` | Text | Semantic version |
| `skill_parameters` | Repeater | Input parameters |
| `skill_parameters.name` | Text | Parameter name |
| `skill_parameters.type` | Select | Parameter type |
| `skill_parameters.required` | True/False | Is required |
| `skill_parameters.description` | Text | Parameter description |

---

## Taxonomies

### 1. Topic (`leaderspath_topic`)

Cross-cutting topics for lessons and courses.

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

- `leaderspath_lesson`
- `leaderspath_course`

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
┌─────────────┐
│   Course    │
└──────┬──────┘
       │ has many (ordered)
       ▼
┌─────────────┐         ┌─────────────┐
│   Lesson    │◄────────│   Cohort    │
└──────┬──────┘ course  └─────────────┘
       │
       │ has many
       ▼
┌─────────────────────────────────────┐
│                                     │
│  ┌──────────────┐  ┌─────────────┐ │
│  │Context Files │  │   Skills    │ │
│  └──────────────┘  └─────────────┘ │
│                                     │
└─────────────────────────────────────┘

Taxonomies:
┌─────────────┐
│   Topic     │──────► Lesson, Course
└─────────────┘

┌─────────────────┐
│ Context Category│──────► Context File
└─────────────────┘

┌─────────────────┐
│ Skill Category  │──────► Skill
└─────────────────┘
```

---

## Capabilities

### Custom Capability Types

Each CPT has its own capability type for granular permissions:

| CPT | Capability Type | Example Capabilities |
|-----|-----------------|---------------------|
| Lesson | `leaderspath_lesson` | `edit_leaderspath_lesson`, `delete_leaderspath_lessons` |
| Course | `leaderspath_course` | `edit_leaderspath_course`, `publish_leaderspath_courses` |
| Cohort | `leaderspath_cohort` | `edit_leaderspath_cohort`, `read_private_leaderspath_cohorts` |
| Context | `leaderspath_context` | `edit_leaderspath_context` |
| Skill | `leaderspath_skill` | `edit_leaderspath_skill` |

### Role Assignments

**Administrator:**
- All capabilities for all CPTs

**Editor:**
- All capabilities for Lessons, Courses
- Read-only for Cohorts, Context Files, Skills

**LeadersPath Student** (custom role):
- `read` - Basic WordPress read
- `leaderspath_access_lessons` - View published lessons
- `leaderspath_access_chatbot` - Use chatbot feature
- `leaderspath_view_context` - View context file contents
- `leaderspath_download_context` - Download context files

---

## REST API Endpoints

All CPTs are REST-enabled. Custom endpoints supplement the defaults:

| Endpoint | Method | Description |
|----------|--------|-------------|
| `/wp-json/leaderspath/v1/lessons` | GET | List lessons (with filters) |
| `/wp-json/leaderspath/v1/lessons/{id}` | GET | Single lesson with all meta |
| `/wp-json/leaderspath/v1/lessons/{id}/context` | GET | Lesson's context files |
| `/wp-json/leaderspath/v1/lessons/{id}/skills` | GET | Lesson's skills |
| `/wp-json/leaderspath/v1/courses` | GET | List courses |
| `/wp-json/leaderspath/v1/courses/{id}` | GET | Course with ordered lessons |
| `/wp-json/leaderspath/v1/context-files/{id}/download` | GET | Download context file |
| `/wp-json/leaderspath/v1/skills/{id}/download` | GET | Download skill definition |

---

## ACF JSON Sync

ACF field groups should be synced to JSON for version control:

**Location:** `/acf-json/`

**Field Groups:**
- `group_lesson_settings.json`
- `group_lesson_chatbot.json`
- `group_course_settings.json`
- `group_cohort_settings.json`
- `group_context_settings.json`
- `group_skill_settings.json`

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
