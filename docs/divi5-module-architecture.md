# Divi 5 Module Architecture

**Last Updated:** 2026-02-11
**Source:** Research from installed `@divi/types` packages, official extension example repo, and Divi 5 internals analysis

---

## 1. Architecture Overview

### How Divi 5 Relates to WordPress Blocks

- Divi 5 stores page layouts as JSON inside a `<!-- wp:divi/placeholder {...} /-->` block comment wrapper in `post_content`
- This wrapper exists for WP compatibility only — Divi's renderer completely takes over
- Divi hooks into `the_content` filter. When it detects Divi layout JSON, it renders using its own engine
- The JSON contains a tree of module nodes (sections > rows > columns > modules) with their attributes
- ACF fields/meta stored separately in `wp_postmeta` as usual
- WordPress revision history works because layout lives in `post_content`

### Dual Rendering Model

| Context | Technology | What Runs |
|---------|------------|-----------|
| Visual Builder (editing) | React/TypeScript | `edit.tsx` components, `@divi/*` packages |
| Frontend (visitor) | PHP | `render_callback`, server-side HTML |

**The frontend is 100% server-rendered PHP.** No React, no hydration. The VB is 100% client-side React.

### Rendering Strategy: Shared Structure, Separate Content

LeadersPath modules are data-driven — they display ACF field data (relationships, repeaters, meta fields), not user-authored block content. This means the PHP `render_callback` is the **single source of truth** for output. The VB edit component exists to make the module draggable, configurable, and styled — not to replicate the full rendering logic.

**The sync contract between PHP and React is the DOM structure, not the content:**

```
PHP render_callback (frontend):
<div class="leaderspath-lesson-activities">              ← same wrapper class
    <a class="leaderspath-lesson-activities__item">       ← same child class
        AI Fundamentals                                   ← real ACF data
    </a>
    <a class="leaderspath-lesson-activities__item">
        Prompt Engineering Basics
    </a>
</div>

React edit component (VB):
<ModuleContainer ...>                                     ← generates same wrapper class
    <div className="leaderspath-lesson-activities__item"> ← same child class
        Sample Activity 1                                 ← placeholder or REST-fetched data
    </div>
    <div className="leaderspath-lesson-activities__item">
        Sample Activity 2
    </div>
</ModuleContainer>
```

**Why this works:** Divi's design panels (layout, fonts, spacing, borders, etc.) generate CSS that targets **selectors defined in `module.json`**. The selectors reference CSS classes, not content. As long as both renderers output the same class structure, all VB styling applies identically to both contexts.

```json
// module.json — selectors are the contract
"attributes": {
    "module": {
        "selector": "{{selector}}",
        "tag": "div"
    },
    "activityItem": {
        "selector": "{{selector}} .leaderspath-lesson-activities__item",
        "tag": "a"
    }
}
```

**Per-module strategy:**

| Module Type | PHP (frontend) | React (VB) | Content Parity |
|-------------|----------------|------------|----------------|
| Display fields (Meta) | Real ACF data | REST-fetched sample or placeholders | Low — structure matters, not values |
| Display lists (Activities, Lessons, Objectives) | Real ACF relationship/repeater data | REST-fetched sample data or N placeholder items | Medium — item count affects layout preview |
| Display cards (Context Library, Skills List) | Real card grid | Placeholder cards with sample data | Medium — card count affects grid preview |
| Interactive (Chatbot) | Full chat UI with JS | Static mockup (bubble layout) | None — interactivity only on frontend |

**Rules:**
1. CSS class names are defined once and shared (BEM: `leaderspath-{module}__{element}--{modifier}`)
2. `module.json` selectors reference those classes — this is the binding between VB styling and both renderers
3. PHP `render_callback` is authoritative; never duplicate rendering logic in React
4. VB edit components should fetch sample data via REST when possible, fall back to static placeholders
5. The `PostIdHelper` trait provides the current post ID (or first published post as fallback) for VB preview data

---

## 2. Module Registration

### PHP Side (VB Asset Registration)

From the official example repo's main plugin file:

```php
// Register VB bundle
add_action('divi_visual_builder_assets_before_enqueue_scripts', function() {
    if (et_builder_d5_enabled() && et_core_is_fb_enabled()) {
        $url = plugin_dir_url(__FILE__);

        \ET\Builder\VisualBuilder\Assets\PackageBuildManager::register_package_build([
            'name' => 'leaderspath-builder-bundle',
            'version' => LEADERSPATH_VERSION,
            'script' => [
                'src' => "{$url}scripts/bundle.js",
                'deps' => ['divi-module-library', 'divi-vendor-wp-hooks'],
                'enqueue_top_window' => false,
                'enqueue_app_window' => true,
            ],
        ]);

        \ET\Builder\VisualBuilder\Assets\PackageBuildManager::register_package_build([
            'name' => 'leaderspath-builder-vb-style',
            'version' => LEADERSPATH_VERSION,
            'style' => [
                'src' => "{$url}styles/vb-bundle.css",
                'deps' => [],
                'enqueue_top_window' => false,
                'enqueue_app_window' => true,
            ],
        ]);
    }
});

// Register frontend CSS
add_action('wp_enqueue_scripts', function() {
    wp_enqueue_style(
        'leaderspath-modules',
        plugin_dir_url(__FILE__) . 'styles/bundle.css',
        [],
        LEADERSPATH_VERSION
    );
});
```

### TypeScript Side (Module Registration)

Entry point `src/index.ts`:

```typescript
import { omit } from 'lodash';
import { addAction } from '@wordpress/hooks';
import { registerModule } from '@divi/module-library';
import { lessonMeta } from './components/lesson-meta';
// ... import other modules

addAction('divi.moduleLibrary.registerModuleLibraryStore.after', 'leaderspath', () => {
    registerModule(lessonMeta.metadata, omit(lessonMeta, 'metadata'));
    // ... register other modules
});
```

### Module Definition (RegisterDefinition)

Each module exports a `RegisterDefinition`:

```typescript
import { type Metadata, type ModuleLibrary } from '@divi/types';
import metadata from './module.json';
import { MyModuleEdit } from './edit';
import { MyModuleAttrs } from './types';

export const myModule: ModuleLibrary.Module.RegisterDefinition<MyModuleAttrs> = {
    metadata: metadata as Metadata.Values<MyModuleAttrs>,
    renderers: {
        edit: MyModuleEdit,
    },
    // Optional:
    settings: {
        content: SettingsContent,
        design: SettingsDesign,
    },
};
```

### Icon Registration

```typescript
import { addFilter } from '@wordpress/hooks';
import { myModuleIcon } from './icons';

addFilter('divi.iconLibrary.icon.map', 'leaderspath', (icons) => ({
    ...icons, // IMPORTANT: spread existing icons first
    [myModuleIcon.name]: myModuleIcon,
}));
```

Icon structure:

```typescript
export const name = 'leaderspath/lesson-meta';
export const viewBox = '0 96 960 960';
export const component = (): ReactElement => (
    <path d="M114 838V710h491v128H114Z..." />
);
```

---

## 3. Module JSON Schema (module.json)

### Example module.json

```json
{
    "name": "leaderspath/lesson-meta",
    "d4Shortcode": "leaderspath_lesson_meta",
    "title": "Lesson Meta",
    "titles": ["Lesson Meta"],
    "moduleIcon": "leaderspath/lesson-meta",
    "category": "leaderspath",
    "settings": "auto",
    "attributes": {
        "module": {
            "type": "object",
            "selector": "{{selector}}",
            "default": {
                "meta": {
                    "adminLabel": { "desktop": { "value": "Lesson Meta" } }
                }
            },
            "tag": "div",
            "styleProps": {}
        },
        "difficulty": {
            "type": "object",
            "selector": "{{selector}} .leaderspath-lesson-meta__difficulty",
            "tag": "span",
            "default": {}
        }
    }
}
```

### Key module.json Properties

| Property | Type | Description |
|----------|------|-------------|
| `name` | string | `vendor/module-name` format |
| `d4Shortcode` | string | CSS class prefix, snake_case |
| `title` / `titles` | string/string[] | Display name |
| `moduleIcon` | string | References icon registered via filter |
| `category` | string | Module picker category |
| `settings` | string/`"auto"` | `"auto"` generates settings from attributes |
| `attributes` | object | Attribute schema with types, selectors, defaults |
| `childModuleName` | string | For parent modules |
| `childrenName` | string[] | Allowed child module names |

### Attribute Value Format (CRITICAL)

ALL values use breakpoint-state format:

```json
{ "desktop": { "value": "Hello World" } }
```

Types: `FormatBreakpointStateAttr<T>` -- supports `desktop`, `tablet`, `phone` breakpoints with `value` and optional `hover`/`sticky` states.

Boolean toggles use `'on'`/`'off'` strings (type `OnOff`), NOT `true`/`false`.

---

## 4. React Components (edit.tsx)

### Edit Component Pattern

```tsx
import React, { ReactElement } from 'react';
import { ModuleContainer } from '@divi/module';
import { MyModuleAttrs } from './types';
import { ModuleEditProps } from '@divi/module-library';

const MyModuleEdit = ({
    attrs,
    id,
    name,
    elements,
}: ModuleEditProps<MyModuleAttrs>): ReactElement => (
    <ModuleContainer
        attrs={attrs}
        elements={elements}
        id={id}
        name={name}
        stylesComponent={ModuleStyles}
        classnamesFunction={moduleClassnames}
        scriptDataComponent={ModuleScriptData}
        tag="div"
    >
        {elements.render({
            attrName: 'title',
        })}
        {elements.render({
            attrName: 'content',
        })}
    </ModuleContainer>
);
```

Key points:

- **Must use `ModuleContainer`** (not `Module`) -- it is the HOC that connects to the store
- **Use `elements.render()`** for content -- enables inline editing, dynamic content, responsive content
- **Use `elements.style()`** for CSS -- in the styles component
- `elements.scriptData()` -- passes data to frontend JS

### For Data-Driven Modules (like LeadersPath)

The VB edit component can:

- Make REST API calls to fetch preview data
- Show a simplified/placeholder preview
- You do NOT need pixel-perfect parity with the PHP frontend output

---

## 5. Field Library

### Settings Panels

Three tabs: Content, Design, Advanced

When `"settings": "auto"` in module.json, Divi auto-generates panels from attribute definitions.

For custom panels:

```typescript
export const SettingsContent = ({
    defaultSettingsAttrs,
    parentAttrs,
    groupConfiguration,
}: Module.Settings.Panel.Props<MyAttrs>): ReactElement => (
    <ModuleGroups groups={groupConfiguration} />
);
```

### Available Setting Groups (from @divi/types)

admin-label, animation, background, border, box-shadow, button, composite, conditions, css, disabled-on, dividers, filters, font, font-body, font-header, form-field, gutter, icon, id-classes, link, overflow, position, scroll, sizing, spacing, sticky, text, text-shadow, transform, transition, visibility-settings, z-index

### Field Types (from @divi/field-library)

text, textarea, rich-text, color, range, select, multi-select, toggle, radio, upload, icon-picker, date-picker, code, composite, and more

---

## 6. Style Library

### StyleContainer Component

```tsx
import { StyleContainer } from '@divi/module';

const ModuleStyles = ({ attrs, elements, settings, mode, state, noStyleTag }) => (
    <StyleContainer mode={mode} state={state} noStyleTag={noStyleTag}>
        {elements.style({
            attrName: 'module',
            styleProps: {
                disabledOn: {
                    disabledModuleVisibility: settings?.disabledModuleVisibility,
                },
            },
        })}
        {elements.style({ attrName: 'title' })}
        {elements.style({ attrName: 'content' })}
    </StyleContainer>
);
```

### StyleDeclarations

```typescript
import { StyleDeclarations } from '@divi/style-library';
const declarations = new StyleDeclarations({ returnType: 'string', important: false });
declarations.add('color', '#333');
declarations.add('font-size', '16px');
const css = declarations.value; // "color: #333; font-size: 16px;"
```

### CSS Property Groups Handled by Style System

Background, Border, Box Shadow, Spacing, Sizing, Font, Filters, Transform, Position, Overflow, Z-Index, Animation, Transition, Text, Text Shadow, Icon, Button, Dividers, Disabled On

---

## 7. Frontend Rendering (PHP)

### render_callback

```php
public static function render_callback(array $attrs, string $content, \WP_Block $block): string {
    $post_id = PostIdHelper::get_post_id('lesson');
    if (!$post_id) return '';

    $activities = get_field('lesson_activities', $post_id) ?: [];
    $output = '<div class="leaderspath-lesson-activities">';
    foreach ($activities as $activity_id) {
        $output .= sprintf(
            '<a href="%s">%s</a>',
            esc_url(get_permalink($activity_id)),
            esc_html(get_the_title($activity_id))
        );
    }
    return $output . '</div>';
}
```

### module_classnames (PHP)

```php
public static function module_classnames(array $args): void {
    $classnames_instance = $args['classnamesInstance'];
    $attrs = $args['attrs'];

    $classnames_instance->add(
        TextClassnames::text_options_classnames($attrs['module']['advanced']['text'] ?? []),
        true
    );

    $classnames_instance->add(
        ElementClassnames::classnames([
            'attrs' => array_merge(
                $attrs['module']['decoration'] ?? [],
                ['link' => $attrs['module']['advanced']['link'] ?? []]
            ),
        ])
    );
}
```

### module_styles (PHP)

Generates CSS for the frontend from attributes. Each module's PHP class implements a `module_styles` static method that receives the same `$args` array and outputs `<style>` declarations for the module's selector.

### module_script_data (PHP)

Passes data from PHP to frontend JavaScript:

```php
public static function module_script_data(array $args): array {
    return [
        'chatEndpoint' => rest_url('leaderspath/v1/chat'),
        'nonce'        => wp_create_nonce('wp_rest'),
    ];
}
```

### Frontend Asset Loading

- Module JS/CSS only loaded when the module is present on the page
- Use `wp_enqueue_script()` / `wp_enqueue_style()` in the render callback
- Frontend JS is vanilla JavaScript (no React)
- VB bundle only loads in the Visual Builder

---

## 8. Theme Builder & Dynamic Content

### Theme Builder

- Templates assigned to post types (e.g., `leaderspath_activity`)
- `get_the_ID()` / `get_queried_object_id()` returns the actual post being viewed
- Custom modules placed in Theme Builder templates, not in post content
- Template resolution: specific post > taxonomy > post type > global default

### Dynamic Content

- Divi has built-in ACF dynamic content providers for simple fields
- Complex fields (repeaters, relationships) need custom module PHP `render_callback`
- LeadersPath modules should query ACF directly in `render_callback` -- NOT use dynamic content tokens

### VB Preview Data

For data-driven modules, fetch preview data via REST API in the edit component. The `PostIdHelper` class (see section 12) provides fallback to the first published post of the expected CPT when the VB context does not have a specific post.

---

## 9. Parent/Child Module Pattern

### Parent Module Definition

```typescript
export const parentModule: ModuleLibrary.Module.RegisterDefinition<ParentModuleAttrs> = {
    metadata: metadata as Metadata.Values<ParentModuleAttrs>,
    childrenName: ['example/child-module'],
    template: [['example/child-module', {}], ['example/child-module', {}]],
    renderers: { edit: ParentModuleEdit },
};
```

### Child Module Definition

```typescript
export const childModule: ModuleLibrary.Module.RegisterDefinition<ChildModuleAttrs> = {
    metadata: metadata as Metadata.Values<ChildModuleAttrs>,
    parentsName: ['example/parent-module'],
    settings: { content: SettingsContent, design: SettingsDesign },
    renderers: { edit: ChildModuleEdit },
};
```

### Settings with Parent Attribute Inheritance

```typescript
export const SettingsContent = ({
    defaultSettingsAttrs,
    parentAttrs,
    groupConfiguration,
}: Module.Settings.Panel.Props<ChildModuleAttrs, ParentModuleAttrs>): ReactElement => {
    if (groupConfiguration?.contentIcon?.component?.props) {
        const defaultIconAttrs = mergeAttrs({
            defaultAttrs: defaultSettingsAttrs?.icon?.innerContent,
            attrs: parentAttrs?.asMutable({ deep: true })?.icon?.innerContent,
        });
        set(groupConfiguration, ['contentIcon', 'component', 'props', 'fields', 'iconInnercontent', 'defaultAttr'], defaultIconAttrs);
    }
    return <ModuleGroups groups={groupConfiguration} />;
};
```

---

## 10. File Structure

### Per-Module Structure

```
src/components/{module-name}/
  index.ts              -- Exports RegisterDefinition
  module.json           -- Module metadata (copied to modules-json/)
  types.ts              -- TypeScript interfaces
  edit.tsx              -- VB edit component
  module-classnames.ts  -- Classnames function
  module-styles.tsx     -- Styles component
  settings-content.tsx  -- Content settings panel (optional if "auto")
  module.scss           -- Module-specific styles

modules/{ModuleName}/
  {ModuleName}.php      -- PHP module class
```

### Build Output

```
scripts/bundle.js       -- Compiled TS (VB only)
styles/bundle.css       -- Frontend CSS
styles/vb-bundle.css    -- VB-specific CSS
modules-json/*/module.json -- Copied metadata
```

### Key webpack externals (NOT bundled)

`@divi/module`, `@divi/module-library`, `@divi/module-utils`, `@divi/field-library`, `@divi/style-library`, `@divi/icon-library`, `@divi/data`, `@divi/rest`, `@divi/modal`, `@divi/types`, `@wordpress/hooks`, `@wordpress/i18n`, `react`, `react-dom`, `lodash`

---

## 11. Gotchas and Critical Notes

1. **Registration timing:** Must use `divi.moduleLibrary.registerModuleLibraryStore.after` -- registering too early silently fails
2. **Attribute format:** ALL values must be `{ desktop: { value: "..." } }` -- flat values break responsive editing
3. **Boolean toggles:** Use `'on'`/`'off'` strings, not `true`/`false`
4. **Frontend requires PHP:** `render_callback` is mandatory for every displayed module
5. **CSS selectors:** Use `{{selector}}` placeholder in module.json
6. **`ModuleContainer` required:** Edit components must use `ModuleContainer`, not `Module`
7. **Use `elements.render()`:** Do not render HTML directly -- use the elements API for inline editing support
8. **Webpack externals:** `@divi/*` packages are NOT bundled -- loaded from Divi runtime
9. **ImmutableObject in settings:** Settings props use `seamless-immutable` -- do not mutate attrs directly
10. **No Interactivity API:** Divi bypasses the WP block pipeline -- use standard `wp_enqueue_script` for frontend JS

---

## 12. LeadersPath Module Plan

### Modules to Build (in order)

| Priority | Module | Type | Data Source | Complexity |
|----------|--------|------|-------------|------------|
| 1 | Lesson Meta | Display fields | lesson_* ACF fields | Low |
| 2 | Lesson Activities | Display list | lesson_activities relationship | Medium |
| 3 | Lesson Objectives | Display list | lesson_objectives repeater | Low |
| 4 | Course Lessons | Display list | course_lessons relationship | Medium |
| 5 | Activity Meta | Display fields | activity_* ACF fields | Low |
| 6 | Context Library | Display cards | activity_context_files relationship | Medium |
| 7 | Skills List | Display cards | activity_skills relationship | Medium |
| 8 | Chatbot | Interactive | Claude API via REST | High |

### Shared PHP Traits (retained from previous build)

These traits live in `modules/Shared/` and are retained from the prior implementation. They have been reviewed and are consistent with patterns observed in the official extension example.

#### PostIdHelper (`modules/Shared/PostIdHelper.php`)

Resolves the current CPT post ID with a fallback for VB/REST contexts. Call `PostIdHelper::get_post_id('lesson')` (pass the CPT slug without the `leaderspath_` prefix). When the queried object is not the expected CPT, it returns the first published post of that type as sample data. **Keep.**

#### ModuleClassnamesTrait (`modules/Shared/ModuleClassnamesTrait.php`)

Standard classname generation using Divi's `TextClassnames` and `ElementClassnames` helpers. Applied identically across all modules. **Keep.**

#### CustomCssTrait (`modules/Shared/CustomCssTrait.php`)

Reads custom CSS fields from the registered block type metadata. Each module using this trait implements `block_name()` to return its `vendor/module-name` string. **Keep.**

### Data Contracts

All ACF fields consumed by modules are documented in `docs/data-contracts.md`. When adding or modifying a module, check that document for the field names, types, and return formats. Key notes:

- All relationship fields use `return_format => 'id'` (returns `array<int>`)
- Repeater fields (e.g., `lesson_objectives`) return nested arrays
- WYSIWYG fields (facilitator guide, learner overview) can use Divi's native Text module with ACF dynamic content -- no custom module needed

---

## Sources

| Source | Location |
|--------|----------|
| `@divi/types` (v1.0.10+) | `node_modules/@divi/types/src/` |
| `@types/divi__module` | `node_modules/@types/divi__module/build-types/` |
| `@types/divi__module-library` | `node_modules/@types/divi__module-library/build-types/` |
| `@types/divi__field-library` | `node_modules/@types/divi__field-library/build-types/` |
| `@types/divi__style-library` | `node_modules/@types/divi__style-library/build-types/` |
| Official example repo | https://github.com/elegantthemes/d5-extension-example-modules |
| Existing shared traits | `modules/Shared/*.php` |
