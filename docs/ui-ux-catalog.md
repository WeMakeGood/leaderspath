# LeadersPath UI/UX Catalog

**Last Updated:** 2026-05-16
**Purpose:** Reference for builders (Bricks recommended) that consume LeadersPath data. Lists the surfaces, what data each one needs, and where that data lives. The plugin no longer renders this markup itself — the page builder does, reading ACF fields directly via its query/dynamic-data integrations.

For the field-level ACF reference, see [cpt-schema.md](cpt-schema.md). For the ACF-to-REST mapping, see [data-contracts.md](data-contracts.md). For the one surface the plugin still owns directly, see [shortcodes.md](shortcodes.md).

---

## Templates

Build one template per CPT in your builder of choice (Bricks query loops + dynamic data work well).

| Template | CPT | Primary Audience | Surfaces |
|---|---|---|---|
| Activity Single | `leaderspath_activity` | Learners | Chatbot widget, Activity Meta, Context Library, Skills List |
| Lesson Single | `leaderspath_lesson` | Learners + Facilitators | Lesson Meta, Lesson Objectives, Lesson Activities, Facilitator Guide, Learner Overview, References |
| Course Single | `leaderspath_course` | Learners | Course Lessons, Prerequisites |
| Course / Lesson Archives | — | Public/Learners | Standard archive — handled by theme/builder |

Cohort pages are WooCommerce product pages and use the active theme's product template.

---

## Surfaces

### Chatbot (Activity sandbox or Lesson Q&A)

**The one surface the plugin renders.** Drop the shortcode into the template:

```
[leaderspath_chatbot]
```

It auto-detects whether it's on an Activity (sandbox mode) or Lesson (Q&A mode) and reads the appropriate fields. See [shortcodes.md](shortcodes.md) for attributes.

### Activity Meta

**On:** Activity Single template
**Fields:** `activity_duration`, `chatbot_model`, `chatbot_allow_model_switch`, `chatbot_enabled`
**Display:** typically a small definition list of `Duration: 30 minutes`, `Model: Claude Sonnet`, `Model Switching: Allowed/Disabled`. Model switching status only makes sense to show when `chatbot_enabled` is true.

### Context Library

**On:** Activity Single template
**Source field:** `chatbot_context_files` (ACF relationship returning post IDs of `leaderspath_context` posts)
**Per item:** `post_title`, `context_description`, `context_version`, taxonomy term from `leaderspath_context_cat`
**Actions:** View (modal preview) and Download buttons should call `GET /leaderspath/v1/context/{id}/download`
**Suggested layout:** card grid

### Skills List

**On:** Activity Single template
**Source field:** `chatbot_skills` (ACF relationship returning post IDs of `leaderspath_skill` posts)
**Per item:** `post_title`, `skill_description`, `skill_compatibility`, `skill_version`
**Suggested layout:** card grid

### Lesson Meta

**On:** Lesson Single template
**Fields:** `lesson_total_duration`, count of `lesson_activities`
**Display:** small definition list — `Duration: 90 minutes`, `Activities: 3`

### Lesson Objectives

**On:** Lesson Single template
**Source field:** `lesson_objectives` (ACF repeater)
**Per item:** `objective` (text)
**Suggested layout:** ordered list

### Lesson Activities

**On:** Lesson Single template
**Source field:** `lesson_activities` (ACF relationship to `leaderspath_activity` posts)
**Per item:** `post_title`, permalink, `activity_duration`, excerpt
**Suggested layout:** numbered list of cards linking to each Activity

### Course Lessons

**On:** Course Single template
**Source field:** `course_lessons` (ACF relationship to `leaderspath_lesson` posts)
**Per item:** `post_title`, permalink, `lesson_total_duration`, count of `lesson_activities`, excerpt
**Suggested layout:** numbered list of cards. Append the Prerequisites section if `course_prerequisites` is non-empty.

### Course Prerequisites

**On:** Course Single template (within Course Lessons surface, or standalone)
**Source field:** `course_prerequisites` (ACF relationship to other `leaderspath_course` posts)
**Per item:** `post_title`, permalink

### Facilitator Guide / Learner Overview / References

WYSIWYG / repeater fields on Lesson — render with the builder's native rich-text and repeater displays. No custom plugin output needed.

---

## Class naming suggestion

If you want CSS classes that mirror the plugin's chatbot widget scheme (`.leaderspath_chatbot__*`), use the `leaderspath_{surface}__{element}` pattern with underscores. For example, you might use `leaderspath_lesson_meta__list`, `leaderspath_lesson_activities__item`, `leaderspath_context_library__card`.

Bricks lets you set class names directly on each element. Not required — use whatever naming your project standardizes on.

---

## Field-by-field reference

For the full ACF field inventory, see [cpt-schema.md](cpt-schema.md). For ACF-to-REST mapping, see [data-contracts.md](data-contracts.md).
