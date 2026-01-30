# Divi 5 Test Module Implementation Notes

**Date:** 2026-01-29
**Status:** Built, Ready for Testing

## What Was Created

A simple "Hello Module" test module to verify Divi 5 integration works correctly before building the full LeadersPath modules (Chatbot, Context Library, Skills List, Lesson Meta).

### Files Created

**PHP (Server-side / Frontend rendering):**
- `modules/HelloModule/HelloModule.php` - Main module class
- `modules/HelloModule/HelloModuleTrait/RenderCallbackTrait.php` - Frontend HTML rendering
- `modules/HelloModule/HelloModuleTrait/ModuleClassnamesTrait.php` - CSS class generation
- `modules/HelloModule/HelloModuleTrait/ModuleStylesTrait.php` - Style generation
- `modules/Modules.php` - Module registration with Divi's dependency tree

**TypeScript/React (Visual Builder):**
- `src/components/hello-module/module.json` - Module metadata and attribute schema
- `src/components/hello-module/edit.tsx` - Visual Builder edit component
- `src/components/hello-module/index.ts` - Module registration export
- `src/components/hello-module/types.ts` - TypeScript interfaces
- `src/components/hello-module/styles.tsx` - Style component
- `src/components/hello-module/module-classnames.ts` - Classname generator
- `src/components/hello-module/placeholder-content.ts` - Default content
- `src/components/hello-module/style.scss` - Frontend styles
- `src/index.ts` - Main entry point for all modules

**Build Configuration:**
- `package.json` - npm dependencies and scripts
- `composer.json` - PHP autoloading
- `webpack.config.js` - Build configuration
- `tsconfig.json` - TypeScript configuration

**Built Output:**
- `scripts/bundle.js` - Compiled JavaScript for Visual Builder
- `styles/bundle.css` - Compiled CSS
- `modules-json/hello-module/module.json` - Copied module metadata

## Key Divi 5 Architecture Learnings

### 1. Module Registration Flow

Modules are registered in two places:

1. **PHP Side** - Using `divi_module_library_modules_dependency_tree` action:
   ```php
   add_action('divi_module_library_modules_dependency_tree', function($dependency_tree) {
       $dependency_tree->add_dependency(new HelloModule());
   });
   ```

2. **JavaScript Side** - Using `divi.moduleLibrary.registerModuleLibraryStore.after` hook:
   ```typescript
   addAction('divi.moduleLibrary.registerModuleLibraryStore.after', 'leaderspath', () => {
       registerModule(helloModule.metadata, omit(helloModule, 'metadata'));
   });
   ```

### 2. Module.json Schema

The `module.json` file defines:
- Module name (must match namespace: `leaderspath/hello-module`)
- Attributes schema with nested settings for content, design, advanced tabs
- CSS selectors for styling
- Custom CSS fields
- Settings panel configuration

### 3. Trait-Based PHP Architecture

Divi 5 modules use traits for organization:
- `RenderCallbackTrait` - Frontend HTML output
- `ModuleClassnamesTrait` - CSS class generation
- `ModuleStylesTrait` - Inline style generation
- `ModuleScriptDataTrait` - JavaScript data (optional)

### 4. Visual Builder Assets

Assets are registered using `PackageBuildManager`:
```php
\ET\Builder\VisualBuilder\Assets\PackageBuildManager::register_package_build([
    'name'    => 'leaderspath-builder-bundle-script',
    'version' => LEADERSPATH_VERSION,
    'script'  => [
        'src'  => LEADERSPATH_URL . 'scripts/bundle.js',
        'deps' => ['divi-module-library', 'divi-vendor-wp-hooks'],
        'enqueue_app_window' => true,
    ],
]);
```

### 5. TypeScript Type Issues

The `@divi/types` package has TypeScript errors in the beta. Solution: use `transpileOnly: true` in ts-loader to skip type checking. Types are for IDE autocomplete, not build validation.

## Testing Instructions

1. **Activate the plugin:**
   ```bash
   wp plugin activate leaderspath
   ```

2. **Open any page/post in Divi Visual Builder**

3. **Search for "Hello Module"** in the module picker

4. **Add the module** and verify:
   - Module appears in builder
   - Settings panel shows Title and Message fields
   - Content renders on frontend
   - Styles apply correctly

## Known Issues / Limitations

1. **Divi 5 is still in beta** - API may change before final release (Feb 26, 2026)

2. **Type package conflicts** - React version conflicts between @divi/types and project. Using npm's peer dependency resolution.

3. **Sass deprecation warning** - Legacy JS API warning from sass-loader. Not blocking.

4. **No shortcode fallback yet** - Shortcode support would need separate implementation.

## Next Steps

If the test module works:

1. **Create Chatbot Module** - The main interactive component
   - Text input with streaming API response
   - Model selection dropdown
   - Context file injection
   - Skill execution

2. **Create Context Library Module** - Display/download context files

3. **Create Skills List Module** - Display/download skills

4. **Create Lesson Meta Module** - Display lesson metadata

5. **Add REST API endpoints** for chat functionality

## References

- [Divi 5 Extension Example (GitHub)](https://github.com/elegantthemes/d5-extension-example-modules)
- [Divi 5 Core Modules Example (GitHub)](https://github.com/elegantthemes/d5-example-core-modules)
- Divi 5 installed version: `5.0.0-public-beta.7.4`
