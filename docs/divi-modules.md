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

**Use when:** Displaying data from the current post being viewed (activity meta, context files, post title).

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

---

## Visual Builder Preview with Real Data (REST API Pattern)

**Last Updated:** 2026-02-04

### The Problem

Divi's Visual Builder doesn't have access to the same post context as the frontend:
- When editing Theme Builder templates, `get_queried_object_id()` returns the template ID, not a preview post
- ACF fields can't be fetched directly in React components
- `@wordpress/server-side-render` isn't available (Divi VB doesn't load WP block editor scripts)

### The Solution: Custom REST API + React Hook

Instead of hardcoded placeholder data, use REST API endpoints to fetch real data from the first post of the CPT.

**Pattern Overview:**
1. **REST Endpoint** returns JSON data (not rendered HTML)
2. **React Hook** fetches data in VB edit component
3. **VB Component** renders same HTML structure as PHP
4. **PHP Fallback** uses same logic - first post of CPT when no valid context

### Implementation

#### 1. Add REST Endpoint (class-rest-api.php)

```php
// Fallback endpoint (no ID required - uses first post)
register_rest_route(
    'leaderspath/v1',
    '/activities/meta',
    [
        'methods'             => WP_REST_Server::READABLE,
        'callback'            => [ $this, 'get_first_activity_meta' ],
        'permission_callback' => [ $this, 'check_vb_permission' ],
    ]
);

// Permission check for VB endpoints
public function check_vb_permission( WP_REST_Request $request ) {
    if ( ! is_user_logged_in() || ! current_user_can( 'edit_posts' ) ) {
        return new WP_Error( 'rest_forbidden', 'Unauthorized', [ 'status' => 401 ] );
    }
    return true;
}

// Handler fetches first post as sample
public function get_first_activity_meta() {
    $activities = get_posts([
        'post_type'      => 'leaderspath_activity',
        'posts_per_page' => 1,
        'post_status'    => 'publish',
    ]);

    if ( empty( $activities ) ) {
        return new WP_REST_Response( [ 'activity_id' => 0 ], 200 );
    }

    return new WP_REST_Response( $this->build_activity_meta_response( $activities[0]->ID ), 200 );
}
```

#### 2. Create React Hook (use-activity-meta.ts)

```typescript
import { useState, useEffect, useRef } from 'react';

export interface ActivityMeta {
  activity_id: number;
  duration: number;
  duration_text: string;
  model: string;
  model_name: string;
  chatbot_enabled: boolean;
}

export function useActivityMeta() {
  const [data, setData] = useState<ActivityMeta | null>(null);
  const [isLoading, setIsLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const isMountedRef = useRef(true);

  useEffect(() => {
    isMountedRef.current = true;
    fetchData();
    return () => { isMountedRef.current = false; };
  }, []);

  async function fetchData() {
    const wpApiSettings = (window as any).wpApiSettings || { root: '/wp-json/', nonce: '' };
    const url = `${wpApiSettings.root}leaderspath/v1/activities/meta`;

    try {
      const response = await fetch(url, {
        headers: { 'X-WP-Nonce': wpApiSettings.nonce },
        credentials: 'same-origin',
      });
      const responseData = await response.json();
      if (isMountedRef.current) setData(responseData);
    } catch (err) {
      if (isMountedRef.current) setError(err.message);
    } finally {
      if (isMountedRef.current) setIsLoading(false);
    }
  }

  return { data, isLoading, error };
}
```

#### 3. Update VB Edit Component (edit.tsx)

```tsx
import { useActivityMeta } from './use-activity-meta';

export const ActivityMetaEdit = (props) => {
  const { data, isLoading, error } = useActivityMeta();

  if (isLoading) return <div className="placeholder">Loading...</div>;
  if (error) return <div className="error">Error: {error}</div>;
  if (!data?.activity_id) return <div className="placeholder">No data</div>;

  // Render same HTML structure as PHP render_callback
  return (
    <div className="leaderspath-activity-meta__content">
      {elements.render({ attrName: 'title' })}
      {data.duration > 0 && (
        <div className="leaderspath-activity-meta__section">
          {elements.render({ attrName: 'durationLabel' })}
          <span className="leaderspath-activity-meta__value">{data.duration_text}</span>
        </div>
      )}
    </div>
  );
};
```

#### 4. Update PHP Fallback (RenderCallbackTrait.php)

```php
public static function get_activity_id(): int {
    $post_id   = get_queried_object_id();
    $post_type = $post_id ? get_post_type( $post_id ) : '';

    // Valid Activity on frontend
    if ( $post_id && 'leaderspath_activity' === $post_type ) {
        return (int) $post_id;
    }

    // Fallback: first Activity as sample
    $sample = get_posts([
        'post_type'      => 'leaderspath_activity',
        'posts_per_page' => 1,
        'post_status'    => 'publish',
    ]);

    return ! empty( $sample ) ? (int) $sample[0]->ID : 0;
}
```

### LeadersPath Modules Using This Pattern

| Module | REST Endpoint | Hook | Fallback CPT |
|--------|--------------|------|--------------|
| CourseMeta | `/courses/meta` | `useCourseMeta()` | leaderspath_course |
| ActivityMeta | `/activities/meta` | `useActivityMeta()` | leaderspath_activity |
| ContextLibrary | `/activities/context` | `useContextFiles()` | leaderspath_activity |
| SkillsList | `/activities/skills` | `useSkills()` | leaderspath_activity |

### Key Benefits

1. **True WYSIWYG** - VB preview shows real data, not placeholders
2. **Empty state testing** - If no posts exist, empty states render correctly
3. **Consistent rendering** - Same data source for VB and frontend
4. **No sample notice needed** - Data is real, just from first available post
