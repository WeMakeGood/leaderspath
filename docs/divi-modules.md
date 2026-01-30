# Divi 5 Module Development Reference

**Last Updated:** 2026-01-29

---

## Session Bootstrap

**Before writing any Divi 5 module code:**

1. Verify the example repo exists, clone if missing:
   ```bash
   ls /private/tmp/d5-extension-example-modules || git clone https://github.com/elegantthemes/d5-extension-example-modules.git /private/tmp/d5-extension-example-modules
   ```

2. Verify Divi 5 core modules path (adjust for your environment):
   ```
   {wp-content}/themes/Divi/includes/builder-5/
   ```

3. **Read the actual source code** from the references below. Do not guess or summarize patterns.

---

## Reference Locations

### Divi Core Modules (Primary Reference - Start Here)

Complete, production modules. These are the authoritative source.

**PHP (server-side rendering, styles, REST controllers):**
```
{wp-content}/themes/Divi/includes/builder-5/server/Packages/ModuleLibrary/
```

**JSON (module schema, attributes, settings):**
```
{wp-content}/themes/Divi/includes/builder-5/visual-builder/packages/module-library/src/components/
```

Note: Core modules only ship JSON files for the VB side - TypeScript source is not distributed. Use the example repo for TypeScript/React patterns.

### Official Example Repository (Secondary Reference)

Simpler examples with full TypeScript source. Use after understanding the core patterns.

**Location:** `/private/tmp/d5-extension-example-modules/`

| Module | Purpose |
|--------|---------|
| StaticModule | Basic module with content fields, styling, custom colors |
| DynamicModule | REST API data fetching with `useFetch` |
| ParentModule | Container module with child modules |
| ChildModule | Child module with icon handling |

---

## Module Patterns

### Pattern 1: Theme Builder Module (Current Post Data)

**Use when:** Displaying data from the current post being viewed (lesson meta, context files, post title).

**Key reference files:**
- `ModuleLibrary/PostTitle/PostTitleModule.php` - Complete PHP implementation
- `post-title/module.json` - Settings, attributes, toggles, styling options

**Getting current post ID:**
```php
$post_id = get_queried_object_id();
if ( ! $post_id ) {
    $post_id = get_the_ID();
}
```

**No REST controller needed** - data comes from WordPress's queried object during render.

### Pattern 2: Dynamic Query Module (Multiple Posts)

**Use when:** Querying and displaying multiple posts (blog, portfolio, search results).

**Key reference files:**
- `ModuleLibrary/Blog/BlogController.php` - REST controller for VB data
- `ModuleLibrary/Blog/BlogModule.php` - PHP rendering
- `blog/module.json` - Complex schema with repeated items

**Key pattern:** VB passes `currentPageId` to REST endpoint; uses `useFetch` from `@divi/rest`.

### Pattern 3: Static Module (User-Entered Content)

**Use when:** Content comes entirely from module settings, not WordPress data.

**Key reference files:**
- Example repo `modules/StaticModule/` - PHP side
- Example repo `src/components/static-module/` - TypeScript side

---

## Where to Find Specific Implementations

### Module Schema (module.json)

| What You Need | Reference File | What to Look For |
|---------------|----------------|------------------|
| Attribute structure | `static-module/module.json` | `attributes` object |
| Settings groups (tabs) | `static-module/module.json` | `settings.groups` |
| Content tab fields | `post-title/module.json` | `groupSlug` → group with `"panel": "content"` |
| Design tab fields | `post-title/module.json` | `groupSlug` → group with `"panel": "design"` |
| Advanced tab | Any module | `"advanced": "auto"` (auto-generated) |

### Field Types

| Field Type | Reference | Path in module.json |
|------------|-----------|---------------------|
| Text input | `static-module/module.json` | `title.settings.innerContent` |
| Textarea | `static-module/module.json` | `summary.settings.innerContent` |
| Rich text | `static-module/module.json` | `content.settings.innerContent` |
| Image upload | `static-module/module.json` | `image.settings.innerContent` |
| Toggle | `post-title/module.json` | `title.settings.advanced.showTitle` |
| Select dropdown | `post-title/module.json` | `featuredImage.settings.advanced.placement` |
| Color picker | `static-module/module.json` | `badge.settings.decoration.color` |

### Styling Options

| Style Type | Reference | Path in module.json |
|------------|-----------|---------------------|
| Font styling | `post-title/module.json` | `title.settings.decoration.font` |
| Spacing | `static-module/module.json` | `image.settings.decoration.spacing` |
| Border | `static-module/module.json` | `image.settings.decoration.border` |
| Box shadow | `static-module/module.json` | `image.settings.decoration.boxShadow` |
| Background | `post-title/module.json` | `textWrapper.settings.decoration.background` |

### PHP Implementation

| What You Need | Reference File |
|---------------|----------------|
| Module class structure | `PostTitle/PostTitleModule.php` or example `StaticModule/StaticModule.php` |
| Render callback | Example `StaticModuleTrait/RenderCallbackTrait.php` |
| Style generation | Example `StaticModuleTrait/ModuleStylesTrait.php` |
| Class names | Example `StaticModuleTrait/ModuleClassnamesTrait.php` |
| HTML utilities | Any render callback - see `HTMLUtility::render()` |
| Element rendering | Any render callback - see `$elements->render(['attrName' => 'element'])` |

### TypeScript Implementation

| What You Need | Reference File |
|---------------|----------------|
| Edit component | Example `static-module/edit.tsx` |
| Styles component | Example `static-module/styles.tsx` |
| Type definitions | Example `static-module/types.ts` |
| Module classnames | Example `static-module/module-classnames.ts` |
| Element rendering | `edit.tsx` - see `elements.render({ attrName: 'element' })` |
| Style declarations | `styles.tsx` - see `elements.style({ attrName: 'element' })` |

---

## File Structure

### PHP Side
```
modules/
└── ModuleName/
    ├── ModuleName.php                    # Main class, implements DependencyInterface
    └── ModuleNameTrait/
        ├── RenderCallbackTrait.php       # Frontend HTML rendering
        ├── ModuleClassnamesTrait.php     # CSS class generation
        └── ModuleStylesTrait.php         # CSS style generation
```

### TypeScript Side
```
src/components/
└── module-name/
    ├── index.ts                          # Module export & registration
    ├── edit.tsx                          # Visual Builder React component
    ├── module.json                       # Module schema
    ├── types.ts                          # TypeScript interfaces
    ├── styles.tsx                        # VB style component
    ├── module-classnames.ts              # Classname generator
    └── style.scss                        # CSS styles
```

---

## Critical Rules

1. **TypeScript and PHP must produce identical DOM** - Class names, structure, and selectors must match exactly.

2. **Attribute value paths** - `$attrs['element']['innerContent']['desktop']['value']`

3. **Toggle values** - Return `'on'`/`'off'` strings, not booleans.

4. **Null safety in styles** - Always use `$args['orderClass'] ?? ''`

5. **Theme Builder posts** - Use `get_queried_object_id()` first, then `get_the_ID()` as fallback.

6. **Style::add wrapper** - PHP styles must be wrapped: `Style::add(['id' => ..., 'styles' => [...]])`

---

## Build Commands

```bash
npm run build        # Production build
npm run start        # Development with watch
```

---

## When Stuck

1. Find a core Divi module that does something similar
2. Read its `module.json` for the complete attribute/settings structure
3. Read its PHP for render and style patterns
4. Read the example repo TypeScript for VB component patterns
5. **Do not guess** - copy the pattern exactly, then adapt
