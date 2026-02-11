# LeadersPath UI/UX Catalog

**Last Updated:** 2026-02-11
**Purpose:** Inventory of all user-facing UI surfaces, their data needs, and the Divi 5 modules/sub-elements required to build them.

---

## Page Templates

LeadersPath content is displayed through Divi 5 Theme Builder templates assigned to each CPT. The facilitator/admin designs these templates in the Visual Builder, placing LeadersPath modules alongside native Divi modules (Text, Image, etc.).

| Template | CPT | Primary Audience | Key Modules |
|----------|-----|------------------|-------------|
| Activity Single | `leaderspath_activity` | Learners | Chatbot, Activity Meta, Context Library, Skills List |
| Lesson Single | `leaderspath_lesson` | Learners + Facilitators | Lesson Meta, Lesson Objectives, Lesson Activities, (native Text for guides) |
| Course Single | `leaderspath_course` | Learners | Course Lessons |
| Course Archive | `leaderspath_course` | Public/Learners | Native Divi Blog/Portfolio module |
| Lesson Archive | `leaderspath_lesson` | Public/Learners | Native Divi Blog/Portfolio module |

**Note:** Cohort pages are WooCommerce product pages — handled by Divi's built-in WooCommerce modules and the standard product template.

---

## Activity Page

The learner's primary interaction surface. This is where AI sandbox experiments happen.

### Module: Chatbot (`leaderspath/chatbot`)

The most complex module. Interactive chat interface connected to the Claude API.

**Data sources:**
- `chatbot_enabled` (gate — if false, module renders nothing)
- `chatbot_model`, `chatbot_system_prompt`, `chatbot_context_files`, `chatbot_skills` (used server-side by REST API, not rendered directly)
- `chatbot_allow_model_switch` (controls model selector visibility)
- `POST /leaderspath/v1/chat` endpoint

**UI elements:**

| Element | CSS Class | Description |
|---------|-----------|-------------|
| Container | `.leaderspath-chatbot` | Outer wrapper, configurable height |
| Message area | `.leaderspath-chatbot__messages` | Scrollable message list |
| User message | `.leaderspath-chatbot__message--user` | Right-aligned bubble |
| Assistant message | `.leaderspath-chatbot__message--assistant` | Left-aligned bubble |
| Input area | `.leaderspath-chatbot__input` | Fixed bottom bar |
| Text input | `.leaderspath-chatbot__input-field` | Message textarea (auto-grow) |
| Send button | `.leaderspath-chatbot__send` | Submit message |
| Typing indicator | `.leaderspath-chatbot__typing` | Animated dots during API call |
| Error message | `.leaderspath-chatbot__error` | Inline error display |
| Model selector | `.leaderspath-chatbot__model-select` | Dropdown (if `allow_model_switch`) |
| Empty state | `.leaderspath-chatbot__empty` | Initial prompt/instructions |

**Interactive behaviors (frontend JS):**
- Send message on Enter (Shift+Enter for newline)
- Auto-scroll to latest message
- Disable input during API call
- Display typing indicator during API call
- Render markdown in assistant responses
- Display inline errors (not alerts)
- Model switch dropdown (conditional)
- Clear conversation on page reload (stateless by design)

**VB preview:** Static mockup showing 2-3 sample messages in the bubble layout. No interactivity.

**Design panel needs:** Background, border, spacing (outer), font (messages), color (user bubble, assistant bubble, input), sizing (height), border-radius (bubbles).

### Module: Activity Meta (`leaderspath/activity-meta`)

Displays activity metadata.

**Data sources:**
- `activity_duration`
- `chatbot_model` (display which model is configured)
- `chatbot_allow_model_switch`

**UI elements:**

| Element | CSS Class | Description |
|---------|-----------|-------------|
| Container | `.leaderspath-activity-meta` | Wrapper |
| Duration | `.leaderspath-activity-meta__duration` | "X minutes" with icon |
| Model badge | `.leaderspath-activity-meta__model` | Model name pill/badge |
| Model switch indicator | `.leaderspath-activity-meta__model-switch` | "Model switching allowed" text |

**Design panel needs:** Font, spacing, background, border.

### Module: Context Library (`leaderspath/context-library`)

Card grid showing context files attached to the activity. Transparency feature — learners see what knowledge the AI has.

**Data sources:**
- `chatbot_context_files` (relationship → `leaderspath_context` post IDs)
- For each context: `post_title`, `context_description`, `context_file_type`, `context_version`
- `GET /leaderspath/v1/context/{id}/download` endpoint

**UI elements:**

| Element | CSS Class | Description |
|---------|-----------|-------------|
| Container | `.leaderspath-context-library` | Grid wrapper |
| Card | `.leaderspath-context-library__card` | Individual context card |
| Card icon | `.leaderspath-context-library__card-icon` | File type icon |
| Card title | `.leaderspath-context-library__card-title` | Context file name |
| Card description | `.leaderspath-context-library__card-desc` | Truncated description |
| Card meta | `.leaderspath-context-library__card-meta` | Type badge + version |
| View button | `.leaderspath-context-library__view` | Opens content in modal |
| Download button | `.leaderspath-context-library__download` | Downloads content |
| Empty state | `.leaderspath-context-library__empty` | "No context files" message |

**Sub-component: Context Modal**

| Element | CSS Class | Description |
|---------|-----------|-------------|
| Overlay | `.leaderspath-modal__overlay` | Backdrop |
| Dialog | `.leaderspath-modal__dialog` | Modal container |
| Header | `.leaderspath-modal__header` | Title + close button |
| Close button | `.leaderspath-modal__close` | X button |
| Body | `.leaderspath-modal__body` | Scrollable content area |
| Content | `.leaderspath-modal__content` | Rendered markdown |
| Footer | `.leaderspath-modal__footer` | Download button |

**Interactive behaviors (frontend JS):**
- Card click opens modal with full content
- Modal fetches content via REST on open (lazy load)
- Render markdown content in modal body
- Download button triggers file download
- Close on X, overlay click, or Escape key

**Design panel needs:** Grid columns (responsive), card background/border/border-radius/spacing, font (title, description), color.

### Module: Skills List (`leaderspath/skills-list`)

Card grid showing skills attached to the activity. Transparency feature.

**Data sources:**
- `chatbot_skills` (relationship → `leaderspath_skill` post IDs)
- For each skill: `post_title`, `skill_description`, `skill_compatibility`, `skill_version`

**UI elements:**

| Element | CSS Class | Description |
|---------|-----------|-------------|
| Container | `.leaderspath-skills-list` | Grid wrapper |
| Card | `.leaderspath-skills-list__card` | Individual skill card |
| Card icon | `.leaderspath-skills-list__card-icon` | Skill type icon |
| Card title | `.leaderspath-skills-list__card-title` | Skill name |
| Card description | `.leaderspath-skills-list__card-desc` | Truncated description |
| Card meta | `.leaderspath-skills-list__card-meta` | Compatibility + version |
| Empty state | `.leaderspath-skills-list__empty` | "No skills configured" |

**Note:** Skills do not have a view/download action for learners — they just see what capabilities the AI has. No modal needed.

**Design panel needs:** Grid columns (responsive), card background/border/border-radius/spacing, font, color.

---

## Lesson Page

The facilitator's primary teaching surface and the learner's overview of a lesson.

### Module: Lesson Meta (`leaderspath/lesson-meta`)

Displays lesson metadata.

**Data sources:**
- `lesson_total_duration`
- `lesson_difficulty`
- `lesson_activities` (count only, for "X activities")

**UI elements:**

| Element | CSS Class | Description |
|---------|-----------|-------------|
| Container | `.leaderspath-lesson-meta` | Wrapper (inline or stacked layout) |
| Duration | `.leaderspath-lesson-meta__duration` | "90 minutes" with clock icon |
| Difficulty | `.leaderspath-lesson-meta__difficulty` | Badge: Beginner/Intermediate/Advanced |
| Activity count | `.leaderspath-lesson-meta__count` | "3 activities" with icon |

**Design panel needs:** Font, spacing, layout (flex direction), background, border, icon color.

### Module: Lesson Objectives (`leaderspath/lesson-objectives`)

Ordered list of learning objectives.

**Data sources:**
- `lesson_objectives` (repeater → `objective` sub-field)

**UI elements:**

| Element | CSS Class | Description |
|---------|-----------|-------------|
| Container | `.leaderspath-lesson-objectives` | Wrapper |
| Heading | `.leaderspath-lesson-objectives__heading` | "Learning Objectives" (configurable) |
| List | `.leaderspath-lesson-objectives__list` | `<ol>` or `<ul>` |
| Item | `.leaderspath-lesson-objectives__item` | Individual objective |
| Check icon | `.leaderspath-lesson-objectives__icon` | Optional check/bullet icon per item |
| Empty state | `.leaderspath-lesson-objectives__empty` | "No objectives defined" |

**Design panel needs:** Font (heading, items), list style, spacing, icon, background, border.

### Module: Lesson Activities (`leaderspath/lesson-activities`)

Ordered list of activities with links. This is the main navigation into the AI sandboxes.

**Data sources:**
- `lesson_activities` (relationship → `leaderspath_activity` post IDs)
- For each activity: `post_title`, `permalink`, `activity_duration`, `post_excerpt`

**UI elements:**

| Element | CSS Class | Description |
|---------|-----------|-------------|
| Container | `.leaderspath-lesson-activities` | Wrapper (list or card layout) |
| Heading | `.leaderspath-lesson-activities__heading` | "Activities" (configurable) |
| Item | `.leaderspath-lesson-activities__item` | Activity row/card (linked) |
| Item number | `.leaderspath-lesson-activities__number` | Order number (1, 2, 3...) |
| Item title | `.leaderspath-lesson-activities__title` | Activity name (linked) |
| Item duration | `.leaderspath-lesson-activities__duration` | Duration badge |
| Item excerpt | `.leaderspath-lesson-activities__excerpt` | Brief description |
| Empty state | `.leaderspath-lesson-activities__empty` | "No activities assigned" |

**Design panel needs:** Font (heading, title, number, duration, excerpt), spacing, layout (list direction/gap), number badge (background color, border/radius, spacing, box shadow), background, border, link color, hover states.

### Facilitator Guide & Learner Overview

These are WYSIWYG fields — use Divi's native **Text module** with ACF dynamic content. No custom module needed.

- `lesson_facilitator_guide` → Text module with dynamic content token, wrapped in a section with capability-based visibility (facilitator/admin only)
- `lesson_learner_overview` → Text module with dynamic content token

### Lesson Q&A Chatbot

Same as the Activity Chatbot module (`leaderspath/chatbot`), but configured at the lesson level. The module reads from `lesson_chatbot_*` fields instead of `chatbot_*` fields. The REST API already handles both via `activity_id` or `lesson_id` parameter.

**Implementation:** Single Chatbot module with a settings toggle for context source (Activity vs Lesson). The `render_callback` detects the current CPT and reads the appropriate fields.

---

## Course Page

Overview of a curriculum with its lesson sequence.

### Module: Course Lessons (`leaderspath/course-lessons`)

Ordered list of lessons in the curriculum.

**Data sources:**
- `course_lessons` (relationship → `leaderspath_lesson` post IDs)
- `course_prerequisites` (relationship → `leaderspath_course` post IDs)
- For each lesson: `post_title`, `permalink`, `lesson_difficulty`, `lesson_total_duration`, `lesson_activities` (count)

**UI elements:**

| Element | CSS Class | Description |
|---------|-----------|-------------|
| Container | `.leaderspath-course-lessons` | Wrapper |
| Heading | `.leaderspath-course-lessons__heading` | "Lessons" (configurable) |
| Item | `.leaderspath-course-lessons__item` | Lesson row/card (linked) |
| Item number | `.leaderspath-course-lessons__number` | Lesson order (1, 2, 3...) |
| Item title | `.leaderspath-course-lessons__title` | Lesson name (linked) |
| Item meta | `.leaderspath-course-lessons__meta` | Duration + difficulty + activity count |
| Item excerpt | `.leaderspath-course-lessons__excerpt` | Lesson excerpt |
| Prerequisites section | `.leaderspath-course-lessons__prereqs` | "Prerequisites" list |
| Prereq item | `.leaderspath-course-lessons__prereq` | Linked course name |
| Empty state | `.leaderspath-course-lessons__empty` | "No lessons assigned" |

**Design panel needs:** Font (heading, title, meta, excerpt), spacing, layout (list vs cards), background, border, link color.

---

## Shared UI Components

These are not standalone Divi modules but reusable elements used across multiple modules.

### Badge/Pill

Used for difficulty levels, model names, file types, versions.

| Element | CSS Class | Description |
|---------|-----------|-------------|
| Badge | `.leaderspath-badge` | Inline pill/tag |
| Variant: difficulty | `.leaderspath-badge--beginner` / `--intermediate` / `--advanced` | Color-coded difficulty |
| Variant: type | `.leaderspath-badge--type` | File type label |
| Variant: model | `.leaderspath-badge--model` | Claude model name |

### Icon + Text

Used for duration, activity count, and other meta items.

| Element | CSS Class | Description |
|---------|-----------|-------------|
| Container | `.leaderspath-icon-text` | Inline flex wrapper |
| Icon | `.leaderspath-icon-text__icon` | SVG or dashicon |
| Text | `.leaderspath-icon-text__text` | Value text |

### Empty State

Consistent pattern when a module has no data to display.

| Element | CSS Class | Description |
|---------|-----------|-------------|
| Container | `.leaderspath-empty` | Centered message area |
| Icon | `.leaderspath-empty__icon` | Informational icon |
| Message | `.leaderspath-empty__message` | "No items to display" text |

### Modal (Context Viewer)

Shared modal for viewing context file content. Used by Context Library module.

See Context Library sub-component table above. The `.leaderspath-modal` classes are module-agnostic and reusable.

---

## Module Dependency Map

```
Activity Page:
  leaderspath/chatbot
    ├── JS: Chat engine (fetch, markdown rendering, model switch)
    ├── CSS: Chat bubbles, input, typing indicator
    └── REST: POST /leaderspath/v1/chat

  leaderspath/activity-meta
    └── CSS: Meta display (badges, icon-text)

  leaderspath/context-library
    ├── JS: Modal open/close, content fetch, markdown rendering
    ├── CSS: Card grid, modal
    └── REST: GET /leaderspath/v1/context/{id}/download

  leaderspath/skills-list
    └── CSS: Card grid

Lesson Page:
  leaderspath/lesson-meta
    └── CSS: Meta display (badges, icon-text)

  leaderspath/lesson-objectives
    └── CSS: Objective list

  leaderspath/lesson-activities
    └── CSS: Activity list/cards

  leaderspath/chatbot (reused, lesson context)
    └── (same as above)

  Native Divi Text module × 2
    └── ACF dynamic content (facilitator guide, learner overview)

Course Page:
  leaderspath/course-lessons
    └── CSS: Lesson list/cards
```

---

## Shared Frontend Assets

| Asset | Modules That Use It | Load Condition |
|-------|---------------------|----------------|
| `chatbot.js` | Chatbot | Module present on page |
| `context-modal.js` | Context Library | Module present on page |
| `markdown-renderer.js` | Chatbot, Context Library | Either module present |
| `modules.css` | All modules | Any LeadersPath module present |

**Note:** Consider whether `chatbot.js` and `context-modal.js` should be one bundle or separate. Separate is better for pages that only have one (e.g., Course page has neither). The markdown renderer is shared and should be a separate dependency.

---

## Responsive Considerations

| Breakpoint | Key Adjustments |
|------------|-----------------|
| Desktop (>980px) | Full layouts — card grids 2-3 columns, chat at configured height |
| Tablet (768-980px) | Card grids collapse to 2 columns, chat may reduce height |
| Phone (<768px) | Card grids single column, chat full-width, activity list stacked |

These are handled by Divi's responsive attribute system (`desktop`/`tablet`/`phone` breakpoints in `FormatBreakpointStateAttr`). Module authors define defaults per breakpoint in `module.json`; site builders override in the VB design panel.

---

## Accessibility Requirements

| Requirement | Implementation |
|-------------|----------------|
| Keyboard navigation | Chat input focusable, Enter to send, Tab through cards |
| Screen reader labels | `aria-label` on interactive elements, `role="log"` on chat messages |
| Focus management | Return focus to input after send, focus trap in modal |
| Color contrast | WCAG AA minimum on all text (handled by Divi's color system + sensible defaults) |
| Reduced motion | Respect `prefers-reduced-motion` for typing indicator animation |
| Modal accessibility | `role="dialog"`, `aria-modal="true"`, Escape to close, focus trap |
