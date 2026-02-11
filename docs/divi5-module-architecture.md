# Divi 5 Module Architecture for LeadersPath

**Last Updated:** 2026-02-11
**Source:** Official `d5-extension-example-modules` repo + Divi 5 theme source analysis

---

## 1. Core Concepts

### Dual Rendering Model

| Context | Technology | What Runs |
|---------|------------|-----------|
| Visual Builder (editing) | React/TypeScript | `edit.tsx` — placeholder UI for design purposes |
| Frontend (visitor) | PHP | `render_callback` — real data, real HTML |

**The frontend is 100% server-rendered PHP.** No React, no hydration.

### Two Types of Dynamic Modules

This distinction is critical and was the source of our first failed implementation:

| Type | Example | Data Source | VB Strategy |
|------|---------|-------------|-------------|
| **Generic query** | DynamicModule (reference repo) | `get_posts()` — any posts | `useFetch` + REST API |
| **Current post** | LeadersPath modules | ACF fields on the viewed post | Static placeholders |

**LeadersPath modules are "current post" modules.** They read ACF fields from the specific lesson/activity/course being viewed. The VB edit component shows placeholder content with the same DOM/class structure so that design controls apply identically.

### Post ID Resolution (CRITICAL)

Divi's Theme Builder overrides WordPress globals. When a TB layout renders, `get_the_ID()` and `get_queried_object_id()` may return the **layout template post**, not the post being viewed.

**Correct pattern** (from Divi's own `DynamicContentPosts`):

```php
if ( ET_Theme_Builder_Layout::is_theme_builder_layout() && is_singular() ) {
    $post_id = ET_Post_Stack::get_main_post_id();
} else {
    $post_id = get_the_ID();
}
```

This is implemented in `modules/Shared/PostIdHelper.php`. All modules use it.

### Semantic Markup

Core renderers **must use semantic HTML elements**, not generic `div`/`span` soup:

- **Metadata pairs** → `<dl>` / `<dt>` / `<dd>` (e.g., Lesson Meta)
- **Ordered content** → `<ol>` (e.g., Lesson Activities, Learning Objectives)
- **Card grids** → `<ul>` with `<article>` children (e.g., Context Library, Skills List)
- **Headings** → appropriate `<h2>`–`<h6>` level for context
- **Interactive elements** → `<button>`, `<input>`, `<form>` — never `<div onclick>`

Divi's module wrapper handles the outer container. The core renderer produces the inner content with correct semantics. SCSS targets these elements via their BEM classes.

### Layout (Flex/Grid) via Divi's Layout Panel

Divi provides a Layout panel in the Design tab that gives users full control over flex/grid properties.

**Where to put Layout:** On a **child attribute** targeting the inner container, NOT on `module`. The `module` wrapper's layout is controlled by Divi's own `.et_flex_module`/`.et_grid_module` classes. For custom inner containers (like a `<dl>`), create a dedicated attribute:

```json
"list": {
    "type": "object",
    "selector": "{{selector}} .leaderspath_lesson_meta__list",
    "tagName": "dl",
    "elementType": "element",
    "settings": {
        "decoration": {
            "layout": {
                "groupType": "group-item",
                "item": {
                    "groupSlug": "designLayout",
                    "priority": 10,
                    "render": true,
                    "component": {
                        "type": "group",
                        "name": "divi/layout",
                        "props": {
                            "grouped": false,
                            "defaultGroupAttr": {
                                "desktop": {
                                    "value": {
                                        "display": "flex",
                                        "flexDirection": "row",
                                        "flexWrap": "wrap",
                                        "columnGap": "1.5em",
                                        "rowGap": "0.5em"
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }
    }
}
```

**CRITICAL: Layout panel does NOT output `display`.** Divi's Layout panel generates flex/grid CSS properties (`flex-direction`, `flex-wrap`, `grid-template-columns`, etc.) and CSS custom properties (`--horizontal-gap`, `--vertical-gap`, `--flex-direction`), but it does NOT generate the `display` property. That comes from `.et_flex_*`/`.et_grid_*` classes which are only added to the `module` wrapper (via `Module.php` line 339). Child elements don't get these classes.

**Three-part solution for child element layout:**

1. **PHP (`ModuleStylesTrait.php`)** — Read `list.decoration.layout.desktop.value.display` and output `display` via `Style::add()` with a **2D array**:
```php
[[ 'selector' => $list_selector, 'declaration' => "display: {$list_display};" ]]
```

2. **VB (`edit.tsx`)** — Apply inline style: `<dl style={{ display: listDisplay }}>`

3. **SCSS (`module.scss`)** — Reset inherited CSS variables only (NO `display`):
```scss
.module .module__list {
    --flex-direction: row;
    --flex-wrap: wrap;
    --horizontal-gap: 1.5em;
    --vertical-gap: 0.5em;
    flex-direction: var(--flex-direction);
    flex-wrap: var(--flex-wrap);
    column-gap: var(--horizontal-gap);
    row-gap: var(--vertical-gap);
}
```

Parent `.et_flex_module` sets `--flex-direction: column`, `--horizontal-gap: var(--module-gutter)`, etc. which cascade into child elements. SCSS must reset these on the target selector so defaults are correct before user customization.

**`Style::add()` 2D array format:** Each item in the `styles` array must be a **2D array** (array of style entry arrays). `$elements->style()` returns this format. Raw declarations must be wrapped: `[[ 'selector' => ..., 'declaration' => ... ]]`. A single-level array gets silently filtered out.

### CSS Class Naming Convention

CSS classes use **underscores** matching `moduleClassName`, not BEM hyphens:

```
{moduleClassName}__{element}
```

Example: `leaderspath_lesson_meta__duration` (NOT `leaderspath-lesson-meta__duration`)

---

## 2. File Structure

### Per-Module Files

```
modules/{ModuleName}/
  {ModuleName}.php                        -- DependencyInterface class
  {ModuleName}Trait/
    RenderCallbackTrait.php               -- PHP render (real data)
    ModuleClassnamesTrait.php             -- PHP classnames
    ModuleStylesTrait.php                 -- PHP styles
    CustomCssTrait.php                    -- Custom CSS fields
    ModuleScriptDataTrait.php             -- Script data

src/components/{module-name}/
  module.json                             -- Module schema + attribute definitions
  module-default-render-attributes.json   -- Default attr values
  module-default-printed-style-attributes.json
  types.ts                                -- TypeScript interfaces
  index.ts                                -- RegisterDefinition export
  edit.tsx                                -- VB edit component (placeholder content)
  module-classnames.ts                    -- Classnames function
  styles.tsx                              -- Styles component
  custom-css.ts                           -- Custom CSS with i18n labels
  module-script-data.tsx                  -- Script data component
  placeholder-content.ts                  -- Default content for new instances
  module.scss                             -- FE+VB styles → bundle.css
  style.scss                              -- VB-only styles → vb-bundle.css

src/icons/{icon-name}/
  index.tsx                               -- SVG icon (no props)
```

### SCSS Convention

| File | Builds To | Loaded In |
|------|-----------|-----------|
| `module.scss` | `styles/bundle.css` | Frontend + VB |
| `style.scss` | `styles/vb-bundle.css` | VB only |

### Build Output

```
scripts/bundle.js         -- VB JavaScript
styles/bundle.css         -- Frontend + VB CSS (from module.scss)
styles/vb-bundle.css      -- VB-only CSS (from style.scss)
modules-json/*/           -- Copied module.json + default attr JSONs
```

---

## 3. Rendering Pipeline (Bottom-Up)

### Layer 1: Core Data (PHP — `RenderCallbackTrait`)

This is the foundation. The render callback:

1. Resolves the current post ID via `PostIdHelper::get_post_id()`
2. Reads ACF fields with `get_field( $field_name, $post_id )`
3. Builds HTML using `HTMLUtility::render()` (NOT `$elements->render()` for data-driven content)
4. Wraps everything in `Module::render()` with decoration components

```php
public static function render_callback( $attrs, $content, $block, $elements ) {
    // 1. Get the real post ID.
    $post_id = PostIdHelper::get_post_id( 'lesson' );

    // 2. Read ACF data.
    $duration   = $post_id ? (string) get_field( 'lesson_total_duration', $post_id ) : '';
    $difficulty = $post_id ? (string) get_field( 'lesson_difficulty', $post_id ) : '';

    // 3. Build HTML with HTMLUtility (data-driven content).
    $items = '';
    if ( $duration ) {
        $items .= HTMLUtility::render([
            'tag'               => 'span',
            'attributes'        => [ 'class' => 'leaderspath_lesson_meta__duration' ],
            'childrenSanitizer' => 'esc_html',
            'children'          => $duration,
        ]);
    }

    $inner = HTMLUtility::render([
        'tag'               => 'div',
        'attributes'        => [ 'class' => 'leaderspath_lesson_meta__inner' ],
        'childrenSanitizer' => 'et_core_esc_previously',
        'children'          => $items,
    ]);

    // 4. Wrap in Module::render() with decoration.
    $parent       = BlockParserStore::get_parent(
        $block->parsed_block['id'],
        $block->parsed_block['storeInstance']
    );
    $parent_attrs = $parent->attrs ?? [];

    return Module::render([
        'orderIndex'          => $block->parsed_block['orderIndex'],
        'storeInstance'       => $block->parsed_block['storeInstance'],
        'attrs'               => $attrs,
        'elements'            => $elements,
        'id'                  => $block->parsed_block['id'],
        'name'                => $block->block_type->name,
        'moduleCategory'      => $block->block_type->category,
        'classnamesFunction'  => [ self::class, 'module_classnames' ],
        'stylesComponent'     => [ self::class, 'module_styles' ],
        'scriptDataComponent' => [ self::class, 'module_script_data' ],
        'parentAttrs'         => $parent_attrs,
        'parentId'            => $parent->id ?? '',
        'parentName'          => $parent->blockName ?? '',
        'children'            => [
            ElementComponents::component([
                'attrs'         => $attrs['module']['decoration'] ?? [],
                'id'            => $block->parsed_block['id'],
                'orderIndex'    => $block->parsed_block['orderIndex'],
                'storeInstance' => $block->parsed_block['storeInstance'],
            ]),
            $inner,
        ],
    ]);
}
```

**Why `HTMLUtility::render()` instead of `$elements->render()`?**

`$elements->render()` reads content from `attrs.*.innerContent` — values the user typed into VB settings fields. Our modules don't have user-entered content; they read ACF fields. The content is determined at render time, not at save time. `HTMLUtility::render()` generates HTML from runtime data.

### Layer 2: Styles (PHP + TS — VB-agnostic)

Styles are VB-agnostic — the same `$elements->style()` calls work identically in both PHP and the VB React components because they target **CSS selectors defined in module.json**, not content.

**PHP (`ModuleStylesTrait`):**
```php
public static function module_styles( $args ) {
    Style::add([
        'id'            => $args['id'],
        'name'          => $args['name'],
        'orderIndex'    => $args['orderIndex'],
        'storeInstance' => $args['storeInstance'],
        'styles'        => [
            $args['elements']->style([ 'attrName' => 'module', 'styleProps' => [
                'disabledOn' => [
                    'disabledModuleVisibility' => $args['settings']['disabledModuleVisibility'] ?? null,
                ],
            ]]),
            $args['elements']->style([ 'attrName' => 'duration' ]),
            CssStyle::style([
                'selector'  => $args['orderClass'],
                'attr'      => $args['attrs']['css'] ?? [],
                'cssFields' => self::custom_css(),
            ]),
        ],
    ]);
}
```

**TS (`styles.tsx`):**
```tsx
export const ModuleStyles = ({
    attrs, elements, settings, orderClass, mode, state, noStyleTag,
}: StylesProps<Attrs>): ReactElement => (
    <StyleContainer mode={mode} state={state} noStyleTag={noStyleTag}>
        {elements.style({
            attrName: 'module',
            styleProps: {
                disabledOn: { disabledModuleVisibility: settings?.disabledModuleVisibility },
            },
        })}
        {elements.style({ attrName: 'duration' })}
        <CssStyle selector={orderClass} attr={attrs.css} cssFields={cssFields} />
    </StyleContainer>
);
```

### Layer 3: VB Edit Component (React — placeholder content)

For "current post" modules, the VB component renders **placeholder content** with the same DOM structure as the PHP render callback. This ensures VB design controls (fonts, colors, spacing) apply correctly — they target CSS selectors, not content.

```tsx
export const LessonMetaEdit = (props: EditProps): ReactElement => {
    const { attrs, elements, id, name } = props;

    return (
        <ModuleContainer
            attrs={attrs} elements={elements} id={id} name={name}
            stylesComponent={ModuleStyles}
            classnamesFunction={moduleClassnames}
            scriptDataComponent={ModuleScriptData}
        >
            {elements.styleComponents({ attrName: 'module' })}
            <ElementComponents attrs={attrs?.module?.decoration ?? {}} id={id} />
            <div className="leaderspath_lesson_meta__inner">
                <span className="leaderspath_lesson_meta__duration">
                    {__('90 minutes', 'leaderspath')}
                </span>
            </div>
        </ModuleContainer>
    );
};
```

### Layer 4: Settings (module.json — auto-generated panels)

Settings panels auto-generate from `module.json` attribute definitions. No code needed in the edit component for settings panels.

**Font settings pattern for data-driven elements** (direct, without group wrapping):

```json
"duration": {
    "type": "object",
    "selector": "{{selector}} .leaderspath_lesson_meta__duration",
    "tagName": "span",
    "elementType": "heading",
    "settings": {
        "decoration": {
            "font": {
                "priority": 10,
                "component": {
                    "props": {
                        "groupLabel": "Duration Text",
                        "fieldLabel": "Duration",
                        "fields": {
                            "headingLevel": { "render": false }
                        }
                    }
                }
            }
        }
    }
}
```

---

## 4. Registration

### PHP: DependencyInterface + ModuleRegistration

```php
class LessonMeta implements DependencyInterface {
    use LessonMetaTrait\RenderCallbackTrait;
    use LessonMetaTrait\ModuleClassnamesTrait;
    use LessonMetaTrait\ModuleStylesTrait;
    use LessonMetaTrait\ModuleScriptDataTrait;

    public function load() {
        $path = LEADERSPATH_MODULES_JSON_PATH . 'lesson-meta/';
        add_action('init', function() use ($path) {
            ModuleRegistration::register_module($path, [
                'render_callback' => [ LessonMeta::class, 'render_callback' ],
            ]);
        });
    }
}
```

**Do NOT use `register_block_type()`.** Divi has its own registration system.

### PHP: Modules.php (dependency tree hub)

```php
add_action('divi_module_library_modules_dependency_tree', function ($dependency_tree) {
    $dependency_tree->add_dependency( new LessonMeta() );
});
```

### TS: Module registration

```typescript
addAction('divi.moduleLibrary.registerModuleLibraryStore.after', 'leaderspath', () => {
    registerModule(lessonMeta.metadata, omit(lessonMeta, 'metadata'));
});
```

---

## 5. module.json Reference

### Required Fields

| Field | Description |
|-------|-------------|
| `name` | `vendor/module-name` format |
| `d4Shortcode` | Empty string for new modules |
| `title` | Display name (prefix "LeadersPath") |
| `titles` | Plural — a **string**, NOT an array |
| `moduleIcon` | References registered icon name |
| `moduleClassName` | CSS base with underscores (e.g., `leaderspath_lesson_meta`) |
| `moduleOrderClassName` | Same as `moduleClassName` |
| `category` | One of: `module`, `child-module`, `fullwidth-module`, `structure`, `unsupported` |
| `customCssFields` | Required for Advanced > Custom CSS tab |
| `settings` | Must be object `{ "content": "auto", "design": "auto", "advanced": "auto" }` |

### Attribute Value Format

ALL values use breakpoint-state format:
```json
{ "desktop": { "value": "Hello" } }
```

Booleans use `'on'`/`'off'` strings, NOT `true`/`false`.

### Element Attribute Properties

| Property | Description |
|----------|-------------|
| `type` | Always `"object"` |
| `selector` | CSS selector with `{{selector}}` placeholder |
| `tagName` | HTML tag (NOT `tag`) |
| `elementType` | `"heading"`, `"content"`, etc. |
| `styleProps` | Style config with `important` flags |
| `settings.decoration.font` | Font panel — use direct pattern (no `groupType` wrapping) for data-driven elements |

---

## 6. TypeScript Types

```typescript
import { ModuleEditProps } from '@divi/module-library';
import { FormatBreakpointStateAttr, InternalAttrs, type Element, type Module } from '@divi/types';
import { StylesProps, ModuleClassnamesParams, ModuleScriptDataProps } from '@divi/module';

// Attrs extend InternalAttrs
interface MyAttrs extends InternalAttrs {
    css?: FormatBreakpointStateAttr<MyCssAttr>;
    module?: { /* meta, advanced, decoration */ };
    duration?: Element.Types.Title.Attributes;
}

// Component prop types
type MyEditProps = ModuleEditProps<MyAttrs>;
// StylesProps<MyAttrs>
// ModuleClassnamesParams<MyAttrs>
// ModuleScriptDataProps<MyAttrs>
```

---

## 7. Shared PHP Utilities

### PostIdHelper (`modules/Shared/PostIdHelper.php`)

Resolves current CPT post ID with Theme Builder awareness:

```php
$post_id = PostIdHelper::get_post_id('lesson');
// Resolution order:
// 1. TB + singular → ET_Post_Stack::get_main_post_id()
// 2. Regular       → get_the_ID()
// 3. Fallback      → first published post of CPT (sample data for VB)
```

### ModuleClassnamesTrait (`modules/Shared/ModuleClassnamesTrait.php`)

Adds text option classnames. Uses `TextClassnames` only (NOT `ElementClassnames`).

---

## 8. Gotchas

1. `render_callback` has **4 params**: `($attrs, $content, $block, $elements)` — the 4th is `ModuleElements`
2. `$elements->render()` reads from `attrs.*.innerContent` — only for user-entered content, NOT ACF data
3. `HTMLUtility::render()` is for data-driven content (ACF fields, computed values)
4. `get_queried_object_id()` / `get_the_ID()` return **wrong post** in Theme Builder — use `PostIdHelper`
5. `ET_Theme_Builder_Layout::is_theme_builder_layout()` detects TB context
6. `ET_Post_Stack::get_main_post_id()` reads `$wp_query->post` directly, bypassing Divi's global overrides
7. Font settings: use direct pattern (no `groupType`/`groupSlug`) for data-driven elements
8. Font settings WITH `groupType: "group-item"` is for static modules with `innerContent` fields
9. `module.scss` → `bundle.css` (FE+VB); `style.scss` → `vb-bundle.css` (VB only) — counterintuitive naming
10. `settings` must be object `{ "content": "auto" }`, NOT bare string `"auto"`
11. `titles` is a string, NOT an array
12. `tagName` not `tag` for element HTML tag
13. Icon component takes **no props**: `(): ReactElement`
14. Registration timing: `divi.moduleLibrary.registerModuleLibraryStore.after` — too early silently fails
15. Webpack `splitChunks` required for CSS separation between FE and VB bundles
16. `ElementComponents` must be in both VB and PHP children (renders decoration layers)
17. `elements.styleComponents({ attrName: 'module' })` required in edit.tsx
18. `scriptDataComponent` required on both `ModuleContainer` (TS) and `Module::render()` (PHP)
19. VB edit component shows **placeholders** for current-post modules — same DOM structure, fake content
20. `ModuleRegistration::register_module()` NOT `register_block_type()` — Divi has its own system
21. **group-items Content tab:** data shape is `content.innerContent.desktop.value.{subName}` — flat object, NOT per-field breakpoint wrapping. Each item needs `"attrName": "content.innerContent"` to bind correctly.
22. **group-items VB defaults:** Use `"default"` on the attribute object in module.json. `defaultAttr` on items does NOT work for `group-items` (only `group-item`). `module-default-render-attributes.json` provides frontend PHP defaults only — both files needed.
23. **Layout panel does NOT output `display`:** Must be generated by PHP (`Style::add()` 2D array) and VB (inline `style`). See Layout section above.
24. **`Style::add()` 2D format:** Items in `styles` array must be 2D arrays. `$elements->style()` returns this. Raw declarations need wrapping: `[[ 'selector' => ..., 'declaration' => ... ]]`
25. **CSS variable cascade:** Parent `.et_flex_module` sets `--flex-direction: column`, `--horizontal-gap`, `--vertical-gap`. Child element SCSS must reset these explicitly or they override your defaults.
26. **DD/DT margin reset specificity:** Divi theme sets `dd { margin-left: 1.5em }`. Reset with `element.class` specificity inside parent: `.module dd.module__value { margin: 0; }`

---

## 9. Implementation Order

| Priority | Module | Data Source | Complexity |
|----------|--------|-------------|------------|
| 1 | Lesson Meta | lesson_total_duration, lesson_difficulty, lesson_activities count | Low |
| 2 | Lesson Objectives | lesson_objectives repeater | Low |
| 3 | Lesson Activities | lesson_activities relationship | Medium |
| 4 | Activity Meta | activity_* ACF fields | Low |
| 5 | Course Lessons | course_lessons relationship | Medium |
| 6 | Context Library | activity_context_files relationship | Medium |
| 7 | Skills List | activity_skills relationship | Medium |
| 8 | Chatbot | Claude API via REST | High |

---

## Sources

| Source | Location |
|--------|----------|
| Official extension example repo | `github.com/elegantthemes/d5-extension-example-modules` (cloned to `/tmp/`) |
| Divi theme source (WooCommerce modules) | `themes/divi/includes/builder-5/server/Packages/ModuleLibrary/WooCommerce/` |
| DynamicContentPosts (post ID resolution) | `themes/divi/includes/builder-5/server/Packages/Module/Layout/Components/DynamicContent/DynamicContentPosts.php` |
| ET_Post_Stack | `themes/divi/includes/builder/post/PostStack.php` |
| ET_Theme_Builder_Layout | `themes/divi/includes/builder/` |
