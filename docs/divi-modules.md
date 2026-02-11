# Divi 5 Module Development

**Last Updated:** 2026-02-11
**Status:** Clean slate — all module code deleted, docs rewritten

---

## Current State

All Divi 5 module code was deleted (2026-02-11) after the first implementation attempt revealed fundamental issues with post ID resolution and the rendering approach. The architecture docs have been rewritten with corrections from analyzing Divi's own theme source.

**Key corrections from failed implementation:**
- `$elements->render()` is for user-entered `innerContent`, NOT ACF data
- `HTMLUtility::render()` is the correct approach for data-driven (ACF) content
- `get_the_ID()` fails in Theme Builder — use `ET_Theme_Builder_Layout::is_theme_builder_layout()` + `ET_Post_Stack::get_main_post_id()`
- Font settings use direct pattern (no `groupType` wrapping) for data-driven elements
- VB edit components show static placeholders for current-post modules

**Authoritative references:**
- [divi5-module-architecture.md](divi5-module-architecture.md) — Complete architecture with bottom-up rendering pipeline
- [wordpress-rendering-pipeline.md](wordpress-rendering-pipeline.md) — WordPress block rendering lifecycle
- Official example repo: `github.com/elegantthemes/d5-extension-example-modules` (cloned to `/tmp/`)
- Divi theme source: `themes/divi/includes/builder-5/server/` (WooCommerce modules, DynamicContentPosts)

**Do not reference prior module implementations from git history.**

---

## What Remains (validated, kept)

- `modules/Shared/PostIdHelper.php` — TB-aware post ID resolution. Updated to use `ET_Theme_Builder_Layout::is_theme_builder_layout()` + `ET_Post_Stack::get_main_post_id()`.
- `modules/Shared/ModuleClassnamesTrait.php` — Uses `TextClassnames` only. Validated against official example.
- `modules/Modules.php` — Empty shell with VB/FE asset registration hooks.
- `src/index.ts` / `src/module-icons.ts` — Empty stubs ready for module imports.

---

## What Needs to Be Built

All LeadersPath modules are **current-post modules**: they read ACF fields from the viewed post, not from user-entered VB content.

**Lesson template modules:**
- Lesson Meta — Duration, difficulty, activity count
- Lesson Objectives — Learning objectives list
- Lesson Activities — Ordered activity list with links

**Activity template modules:**
- Activity Meta — Duration, model info
- Context Library — Card grid of context files
- Skills List — Card grid of skills
- Chatbot — Interactive chat UI (Claude API)

**Course template modules:**
- Course Lessons — Ordered lesson list with links

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

### Icon (src/icons/{icon-name}/)
- [ ] `index.tsx` — exports `name`, `viewBox`, `component` (no props)

---

## Data Contracts

See `docs/data-contracts.md` for the ACF field → REST endpoint mapping.
