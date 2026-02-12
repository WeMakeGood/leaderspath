# Divi 5 Module Development

**Last Updated:** 2026-02-12
**Status:** All 8 modules implemented and build-verified

---

## Current State

All 8 Divi 5 modules are implemented and build-verified. Icons use Divi's built-in icon library (e.g., `"divi/module-comments"`) — no custom icon registration.

**Authoritative references:**
- [divi5-module-architecture.md](divi5-module-architecture.md) — Complete architecture with bottom-up rendering pipeline
- [wordpress-rendering-pipeline.md](wordpress-rendering-pipeline.md) — WordPress block rendering lifecycle
- Official example repo: `github.com/elegantthemes/d5-extension-example-modules` (cloned to `/tmp/`)
- Divi theme source: `themes/divi/includes/builder-5/server/` (WooCommerce modules, DynamicContentPosts)

---

## What Remains (validated, kept)

- `modules/Shared/PostIdHelper.php` — TB-aware post ID resolution. Updated to use `ET_Theme_Builder_Layout::is_theme_builder_layout()` + `ET_Post_Stack::get_main_post_id()`.
- `modules/Shared/ModuleClassnamesTrait.php` — Uses `TextClassnames` only. Validated against official example.
- `modules/Modules.php` — Empty shell with VB/FE asset registration hooks.
- `src/index.ts` / `src/module-icons.ts` — Empty stubs ready for module imports.

---

## Implemented Modules

All LeadersPath modules are **current-post modules**: they read ACF fields from the viewed post, not from user-entered VB content. Icons use Divi's built-in icon library.

**Lesson template modules:**
- Lesson Meta (`divi/module-wc-meta`) — Duration, difficulty, activity count
- Lesson Objectives (`divi/module-icon-list`) — Learning objectives list
- Lesson Activities (`divi/module-bar-counters`) — Ordered activity list with links

**Activity template modules:**
- Activity Meta (`divi/module-countdown-timer`) — Duration, model info
- Context Library (`divi/module-gallery`) — Card grid of context files
- Skills List (`divi/module-blurb`) — Card grid of skills
- Chatbot (`divi/module-comments`) — Interactive chat UI (Claude API)

**Course template modules:**
- Course Lessons (`divi/module-accordion`) — Ordered lesson list with links

**Not needed as custom modules:** Learner Overview and Facilitator Guide are WYSIWYG fields — use Divi's native Text module with ACF dynamic content.

---

## Implementation Order

See [divi5-module-architecture.md, section 9](divi5-module-architecture.md#9-implementation-order) for the prioritized build order.

---

## Per-Module File Checklist

Every module requires these files:

### PHP (modules/{ModuleName}/)
- [ ] `{ModuleName}.php` — `DependencyInterface`, `ModuleRegistration::register_module()`
- [ ] `{ModuleName}Trait/RenderCallbackTrait.php` — 4 params, `PostIdHelper` + `HTMLUtility::render()` + `Module::render()`
- [ ] `{ModuleName}Trait/ModuleClassnamesTrait.php` — `TextClassnames`
- [ ] `{ModuleName}Trait/ModuleStylesTrait.php` — `Style::add()` + `$elements->style()`
- [ ] `{ModuleName}Trait/CustomCssTrait.php` — reads from block type registry
- [ ] `{ModuleName}Trait/ModuleScriptDataTrait.php` — `ElementScriptData::set()`

### TypeScript (src/components/{module-name}/)
- [ ] `module.json` — `moduleClassName`, `customCssFields`, attribute `settings` with direct font pattern
- [ ] `module-default-render-attributes.json`
- [ ] `module-default-printed-style-attributes.json`
- [ ] `types.ts` — extends `InternalAttrs`
- [ ] `index.ts` — `RegisterDefinition` with `defaultAttrs`, `defaultPrintedStyleAttrs`, `placeholderContent`
- [ ] `edit.tsx` — `ModuleContainer` + placeholder content matching PHP DOM structure
- [ ] `styles.tsx` — `StylesProps<T>`, `orderClass`, `CssStyle`
- [ ] `module-classnames.ts` — `ModuleClassnamesParams<T>` from `@divi/module`
- [ ] `custom-css.ts` — i18n labels for `customCssFields`
- [ ] `module-script-data.tsx` — `ModuleScriptDataProps<T>`
- [ ] `placeholder-content.ts` — default content for new instances
- [ ] `module.scss` — FE+VB styles (→ `bundle.css`)
- [ ] `style.scss` — VB-only styles (→ `vb-bundle.css`)

### Icons

Modules use Divi's built-in icon library names (e.g., `"divi/module-comments"`). No custom icon files needed — set `moduleIcon` in `module.json`.

---

## Data Contracts

See `docs/data-contracts.md` for the ACF field → REST endpoint mapping.
