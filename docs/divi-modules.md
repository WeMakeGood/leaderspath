# Divi 5 Module Development Reference

**Last Updated:** 2026-01-29

---

## Session Bootstrap - READ THIS FIRST

**Before writing any Divi 5 module code, you MUST review the official reference materials.**

Do NOT rely on summaries or examples in this document. The Divi 5 API is complex and summaries have proven unreliable. Instead, read the actual source code from the references below.

### Step 1: Clone the Official Example Repository

```bash
cd /private/tmp
git clone https://github.com/elegantthemes/d5-extension-example-modules.git
```

This repository contains 5 working example modules:
- **StaticModule** - Basic module (title, content, image, badge with custom color)
- **DynamicModule** - Fetches WP posts via REST API
- **ParentModule** - Container with child modules, icons
- **ChildModule** - Inherits parent attrs, icon rendering
- **D4Module** - Migration example from Divi 4

### Step 2: Review Key Files in the Example Repo

For each module pattern you need to implement, read these files:

| What You Need | Files to Read |
|---------------|---------------|
| Basic module structure | `modules/StaticModule/StaticModule.php` |
| Frontend rendering | `modules/StaticModule/StaticModuleTrait/RenderCallbackTrait.php` |
| Style generation (PHP) | `modules/StaticModule/StaticModuleTrait/ModuleStylesTrait.php` |
| Module schema/settings | `src/components/static-module/module.json` |
| VB edit component | `src/components/static-module/edit.tsx` |
| VB styles component | `src/components/static-module/styles.tsx` |
| TypeScript types | `src/components/static-module/types.ts` |
| Icon handling | `src/components/child-module/edit.tsx` (see `processFontIcon`) |
| Icon style declaration | `src/components/child-module/style-declarations/icon-font/index.ts` |
| REST API data fetching | `src/components/dynamic-module/edit.tsx` (see `useFetch`) |
| Parent/child pattern | `src/components/parent-module/` and `src/components/child-module/` |

### Step 3: Review Divi Core Modules for Complex Patterns

The Divi theme contains production modules with complete implementations:

**Location:** `/wp-content/themes/Divi/includes/builder-5/`

| Pattern Needed | Core Module to Study | Key File |
|----------------|---------------------|----------|
| Repeated items (list/grid) | Blog | `visual-builder/packages/module-library/src/components/blog/module.json` |
| Repeated item styling | Blog | `server/Packages/ModuleLibrary/Blog/BlogModule.php` |
| Button element | Button | `visual-builder/packages/module-library/src/components/button/module.json` |
| Image with overlay | Blog | See `overlay` and `overlayIcon` attributes |
| Toggle show/hide | Blog | See `image.advanced.enable`, `readMore.advanced.enable` |
| Layout options | Blog | See `blogGrid.decoration.layout` |

### Step 4: Understand Settings Organization

**Read the Blog module's `module.json` carefully.** It demonstrates:

1. **Content Tab fields** use `groupSlug` pointing to a group with `"panel": "content"`
2. **Design Tab fields** use `groupSlug` pointing to a group with `"panel": "design"`
3. **Toggles for showing/hiding elements** go in Content tab under "Elements" group
4. **Styling for repeated items** uses separate attributes with specific selectors

---

## Reference Locations Summary

### Official Example Repository
- **GitHub:** https://github.com/elegantthemes/d5-extension-example-modules
- **Clone to:** `/private/tmp/d5-extension-example-modules/`

### Divi Core Modules (Local)
- **PHP modules:** `{theme}/includes/builder-5/server/Packages/ModuleLibrary/`
- **TypeScript/JSON:** `{theme}/includes/builder-5/visual-builder/packages/module-library/src/components/`

Where `{theme}` = `/wp-content/themes/Divi`

### This Plugin's Working Modules
- `modules/HelloModule/` - Minimal working example
- `modules/LessonMeta/` - Module with ACF field integration
- `src/components/hello-module/`
- `src/components/lesson-meta/`

---

## Quick Reference: File Structure

Each module requires files in two locations:

```
modules/
└── ModuleName/
    ├── ModuleName.php                    # Main class, implements DependencyInterface
    └── ModuleNameTrait/
        ├── RenderCallbackTrait.php       # Frontend HTML rendering
        ├── ModuleClassnamesTrait.php     # CSS class generation
        ├── ModuleStylesTrait.php         # CSS style generation
        ├── ModuleScriptDataTrait.php     # (optional) Script data for interactions
        └── CustomCssTrait.php            # (optional) Custom CSS fields

src/components/
└── module-name/
    ├── index.ts                          # Module export & registration
    ├── edit.tsx                          # Visual Builder React component
    ├── module.json                       # Module schema (attributes, settings, groups)
    ├── types.ts                          # TypeScript interfaces
    ├── styles.tsx                        # VB style component
    ├── module-classnames.ts              # Classname generator
    ├── placeholder-content.ts            # Default placeholder values
    ├── custom-css.ts                     # (optional) Custom CSS field labels
    ├── module-script-data.tsx            # (optional) Script data component
    ├── module.scss                       # Frontend + VB styles
    └── style.scss                        # VB-only styles (usually empty)
```

---

## Quick Reference: Key Patterns

**These are pointers to where to find examples, NOT complete documentation.**

### Settings Tab Organization

See Blog module `module.json` for complete example:
- `"panel": "content"` → Content tab
- `"panel": "design"` → Design tab
- `"advanced": "auto"` → Advanced tab (auto-generated)

### Toggle to Show/Hide an Element

See `image.advanced.enable` in Blog module:
- Located in `settings.advanced.enable`
- Uses `groupSlug: "contentElements"` (Content tab)
- Component: `divi/toggle`

### Styling Repeated Items

See Blog module's `post` and `masonry` attributes:
- Separate attribute with selector targeting repeated elements
- `post.decoration.border` for item borders
- `masonry.decoration.background` for item backgrounds

### Icon Picker and Rendering

See ParentModule and ChildModule:
- Attribute: `icon.innerContent` with `divi/icon-picker` component
- Rendering: `processFontIcon()` from `@divi/icon-library`
- Styling: Custom declaration function for font-family/content

### Color Picker

Two patterns exist (see Static Module README):
- `decoration.color` - for text colors (badge example)
- `advanced.color` - for icon colors (parent module example)

### Custom Style Properties

See `advancedStyles` in styles components:
```typescript
elements.style({
  attrName: 'badge',
  styleProps: {
    advancedStyles: [{
      componentName: 'divi/common',
      props: { attr: attrs?.badge?.decoration?.color, property: 'color' }
    }]
  }
})
```

---

## Build Commands

```bash
npm install          # Install dependencies
npm run start        # Development with watch
npm run build        # Production build
```

Build output:
- `scripts/bundle.js` - Visual Builder JavaScript
- `styles/bundle.css` - Frontend styles
- `styles/vb-bundle.css` - VB-only styles
- `modules-json/` - Compiled module.json files for PHP

---

## Common Mistakes to Avoid

1. **Theme Builder:** Use `get_queried_object_id()` not `get_the_ID()` in render callbacks
2. **Null safety:** Always use `$args['orderClass'] ?? ''` in PHP styles
3. **Toggle values:** Returns `'on'`/`'off'` strings, not booleans
4. **Attribute paths:** `$attrs['name']['innerContent']['desktop']['value']`
5. **Style::add in PHP:** Must wrap `$elements->style()` calls in `Style::add(['styles' => [...]])`

---

## When in Doubt

1. Find a core Divi module that does something similar
2. Read its `module.json` for settings structure
3. Read its PHP `RenderCallbackTrait` for rendering patterns
4. Read its TypeScript `edit.tsx` and `styles.tsx` for VB patterns
5. Copy the pattern, then adapt it

**Do NOT guess at the API. The patterns are complex and inconsistent guessing leads to bugs.**
