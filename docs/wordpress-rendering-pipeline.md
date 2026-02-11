# WordPress Block Rendering Pipeline

**Last Updated:** 2026-02-11
**Relevance:** Background knowledge for Divi 5 module development

---

## Why This Matters for LeadersPath

Even though LeadersPath uses Divi 5 for its frontend modules, understanding WordPress's native block rendering pipeline is essential for three reasons:

1. **Divi 5 wraps its content in a WP block comment and hooks into `the_content`.** When rendering issues arise, knowing what happens before and after Divi's intervention helps isolate the problem.
2. **The Interactivity API and block context system represent alternatives** worth evaluating for features like the chatbot, transparency panel, and activity sandboxes, especially if Divi compatibility constraints are resolved in a future version.
3. **Dynamic blocks and `render_block` filters are the mechanism** through which WordPress resolves server-side data (ACF fields, user roles, enrollment status) into HTML. Understanding this flow clarifies how and when our data becomes available during rendering.

---

## 1. Block Grammar & Serialization

WordPress stores blocks in `post_content` using HTML comments as delimiters. This is the "block grammar," a lightweight serialization format that degrades gracefully to valid HTML when the block editor is unavailable.

### Block Delimiters

```html
<!-- Self-closing (no inner content) -->
<!-- wp:shortcode /-->

<!-- Container (wraps inner HTML) -->
<!-- wp:paragraph -->
<p>This is the paragraph content.</p>
<!-- /wp:paragraph -->

<!-- With attributes (JSON object after block name) -->
<!-- wp:image {"id":42,"sizeSlug":"large"} -->
<figure class="wp-block-image size-large">
    <img src="example.jpg" alt="" class="wp-image-42"/>
</figure>
<!-- /wp:image -->

<!-- Nested (innerBlocks inside a container) -->
<!-- wp:group {"layout":{"type":"constrained"}} -->
<div class="wp-block-group">
    <!-- wp:heading -->
    <h2 class="wp-block-heading">Title</h2>
    <!-- /wp:heading -->

    <!-- wp:paragraph -->
    <p>Body text.</p>
    <!-- /wp:paragraph -->
</div>
<!-- /wp:group -->
```

### `parse_blocks()`

Takes a raw `post_content` string and returns an array of parsed block arrays. Each parsed block contains:

| Property | Type | Description |
|----------|------|-------------|
| `blockName` | `string\|null` | Fully qualified name (e.g., `core/paragraph`). `null` for freeform content between block comments. |
| `attrs` | `array` | Attributes from the JSON object in the block comment. |
| `innerHTML` | `string` | The raw inner HTML of the block (all inner content concatenated). |
| `innerContent` | `array` | Alternating strings (HTML) and `null` entries (placeholders for innerBlocks). |
| `innerBlocks` | `array` | Nested parsed block arrays. |

```php
$blocks = parse_blocks( $post->post_content );

foreach ( $blocks as $block ) {
    if ( null === $block['blockName'] ) {
        // Freeform content (not wrapped in block comments).
        continue;
    }

    echo $block['blockName'];  // e.g., 'core/paragraph'
    echo $block['attrs'];      // e.g., ['dropCap' => true]
}
```

Introduced in WordPress 5.0.

### Reference

- Block Grammar Spec: https://github.com/WordPress/gutenberg/tree/trunk/packages/block-serialization-spec-parser
- `parse_blocks()`: https://developer.wordpress.org/reference/functions/parse_blocks/

---

## 2. Block Registration

### `register_block_type()`

Two approaches, both creating a `WP_Block_Type` object in the global block type registry:

```php
// Preferred since WP 5.8: register from block.json metadata file.
register_block_type( __DIR__ . '/block.json' );

// Manual registration with explicit arguments.
register_block_type( 'leaderspath/chatbot', [
    'render_callback' => [ $this, 'render_chatbot_block' ],
    'attributes'      => [
        'activityId' => [ 'type' => 'number' ],
    ],
] );
```

### `block.json` Metadata

The `block.json` file is the canonical way to define a block. Key properties:

```json
{
    "$schema": "https://schemas.wp.org/trunk/block.json",
    "apiVersion": 3,
    "name": "leaderspath/lesson-meta",
    "title": "Lesson Meta",
    "category": "leaderspath",
    "icon": "welcome-learn-more",
    "description": "Displays lesson metadata (difficulty, duration, objectives).",

    "attributes": {
        "showDifficulty": { "type": "boolean", "default": true },
        "showDuration": { "type": "boolean", "default": true }
    },

    "supports": {
        "align": true,
        "color": { "background": true, "text": true },
        "spacing": { "margin": true, "padding": true },
        "interactivity": true
    },

    "editorScript": "file:./index.js",
    "editorStyle": "file:./editor.css",
    "script": "file:./script.js",
    "style": "file:./style.css",
    "viewScript": "file:./view.js",
    "viewScriptModule": "file:./view.js",
    "viewStyle": "file:./view.css",

    "render": "file:./render.php",

    "usesContext": ["postId", "postType"],
    "providesContext": { "leaderspath/lessonId": "lessonId" }
}
```

| Property | Since | Description |
|----------|-------|-------------|
| `editorScript` | 5.0 | JS loaded only in the editor. |
| `editorStyle` | 5.0 | CSS loaded only in the editor. |
| `script` | 5.0 | JS loaded in both editor and frontend. |
| `style` | 5.0 | CSS loaded in both editor and frontend. |
| `viewScript` | 5.9 | JS loaded only on the frontend. |
| `viewScriptModule` | 6.5 | Frontend JS loaded as an ES module (required for Interactivity API). |
| `viewStyle` | 6.5 | CSS loaded only on the frontend. |
| `render` | 6.1 | Path to a PHP file whose output becomes the block's HTML. |

### Static vs Dynamic Blocks

- **Static blocks** store their complete HTML in `post_content`. At render time, WordPress assembles the final markup from the `innerContent` array, inserting rendered inner blocks where `null` placeholders appear.
- **Dynamic blocks** have a `render_callback` (or a `render` PHP file). WordPress calls this function at render time, passing the saved attributes and inner content. The function returns HTML.

Dynamic blocks are essential when the output depends on data that can change independently of the post (latest posts, current user data, ACF field values, enrollment status).

### Reference

- Block Registration: https://developer.wordpress.org/block-editor/reference-guides/block-api/block-registration/
- Block Metadata: https://developer.wordpress.org/block-editor/reference-guides/block-api/block-metadata/

---

## 3. Dynamic Blocks

### How `render_callback` Works

```php
register_block_type( 'leaderspath/lesson-meta', [
    'render_callback' => function ( array $attributes, string $content, WP_Block $block ): string {
        $post_id    = get_the_ID();
        $difficulty = get_field( 'lesson_difficulty', $post_id );
        $duration   = get_field( 'lesson_total_duration', $post_id );

        ob_start();
        ?>
        <div class="leaderspath-lesson-meta">
            <?php if ( ! empty( $attributes['showDifficulty'] ) && $difficulty ) : ?>
                <span class="leaderspath-lesson-meta__difficulty">
                    <?php echo esc_html( $difficulty ); ?>
                </span>
            <?php endif; ?>
            <?php if ( ! empty( $attributes['showDuration'] ) && $duration ) : ?>
                <span class="leaderspath-lesson-meta__duration">
                    <?php echo esc_html( $duration ); ?> min
                </span>
            <?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    },
] );
```

The three parameters:

| Parameter | Type | Description |
|-----------|------|-------------|
| `$attributes` | `array` | Merged result of saved attributes and defaults from the schema. |
| `$content` | `string` | The serialized inner HTML (for blocks with `innerBlocks`). |
| `$block` | `WP_Block` | The block instance with context, inner blocks, and type metadata. |

### WP_Block Class

Instantiated for each block during rendering. Key properties:

| Property | Type | Description |
|----------|------|-------------|
| `$parsed_block` | `array` | The raw parsed block array from `parse_blocks()`. |
| `$block_type` | `WP_Block_Type` | The registered block type object. |
| `$context` | `array` | Context values inherited from parent blocks. |
| `$inner_blocks` | `WP_Block_List` | Iterable list of child `WP_Block` instances. |
| `$inner_html` | `string` | The block's inner HTML. |
| `$inner_content` | `array` | Alternating HTML strings and `null` (inner block positions). |

The `$block->render()` method recursively renders the block and all its children, applying filters at each level. Added in WordPress 5.5.

### How Dynamic Blocks Get Data

1. **From `$attributes`** -- values saved in the block comment's JSON and merged with schema defaults.
2. **From `$block->context`** -- values inherited from parent blocks (see Block Context below).
3. **From WordPress functions** -- `get_the_ID()`, `get_field()`, `get_post_meta()`, `wp_get_current_user()`, etc.
4. **From the Loop** -- in Theme Builder scenarios, the post context (global `$post`) is set before the render callback fires, so `get_the_ID()` returns the correct post.

---

## 4. Block Context

### `usesContext` / `providesContext`

Block context enables parent-child data sharing without prop drilling. A parent block declares which of its attributes it makes available, and child blocks declare which context keys they consume.

```json
// Parent block.json (e.g., leaderspath/activity-sandbox)
{
    "name": "leaderspath/activity-sandbox",
    "attributes": {
        "activityId": { "type": "number" }
    },
    "providesContext": {
        "leaderspath/activityId": "activityId"
    }
}
```

```json
// Child block.json (e.g., leaderspath/chatbot)
{
    "name": "leaderspath/chatbot",
    "usesContext": [ "leaderspath/activityId" ]
}
```

In the child's render callback:

```php
function render_chatbot( array $attributes, string $content, WP_Block $block ): string {
    $activity_id = $block->context['leaderspath/activityId'] ?? 0;

    if ( ! $activity_id ) {
        return '';
    }

    // Use $activity_id to load context files, skills, etc.
    return '<div class="leaderspath-chatbot" data-activity="' . esc_attr( $activity_id ) . '"></div>';
}
```

- Added in WordPress 5.5.
- Core example: `core/post-template` provides `postId` and `postType` context to all inner blocks, which is how blocks like `core/post-title` know which post to render.
- Context keys are namespaced (e.g., `leaderspath/activityId`) to avoid collisions.

### Reference

- Block Context: https://developer.wordpress.org/block-editor/reference-guides/block-api/block-context/

---

## 5. WordPress Interactivity API

### Overview

The Interactivity API is a lightweight (~10KB) reactive runtime for frontend interactivity. It follows a server-first approach: HTML is fully rendered on the server with `data-wp-*` directives embedded in the markup. On the client, the runtime hydrates those directives and binds them to a reactive store. No React ships to the frontend.

### PHP Setup

Server-side state is initialized with `wp_interactivity_state()` and per-element context is set with `wp_interactivity_data_wp_context()`:

```php
// In the block's render callback or render.php file.
wp_interactivity_state( 'leaderspath', [
    'chatOpen' => false,
    'messages' => [],
] );

$context = [ 'activityId' => $activity_id ];
?>
<div
    data-wp-interactive="leaderspath"
    <?php echo wp_interactivity_data_wp_context( $context ); ?>
>
    <button data-wp-on--click="actions.toggleChat">
        Open Chat
    </button>
    <div data-wp-class--is-open="state.chatOpen">
        <!-- Chat interface -->
    </div>
</div>
```

### Client-Side Store

The client-side store is defined in a view script module (`viewScriptModule` in `block.json`):

```javascript
import { store, getContext } from '@wordpress/interactivity';

const { state, actions } = store( 'leaderspath', {
    state: {
        get messageCount() {
            return state.messages.length;
        },
    },
    actions: {
        toggleChat() {
            state.chatOpen = ! state.chatOpen;
        },
        sendMessage( event ) {
            const ctx = getContext();
            // ctx.activityId is available from data-wp-context.
            const message = event.target.value;
            // ... send to REST API ...
        },
    },
    callbacks: {
        onChatOpen() {
            // Runs reactively when referenced state changes.
            if ( state.chatOpen ) {
                // Scroll to bottom, focus input, etc.
            }
        },
    },
} );
```

### Directives Reference Table

| Directive | Purpose |
|-----------|---------|
| `data-wp-interactive` | Declares the namespace for this element's store. |
| `data-wp-context` | Provides local reactive context (JSON) to this element and descendants. |
| `data-wp-on--{event}` | Binds an event handler (e.g., `data-wp-on--click="actions.toggleChat"`). |
| `data-wp-on-async--{event}` | Async event handler that yields to the main thread (WP 6.6+). |
| `data-wp-bind--{attr}` | Reactively binds an HTML attribute (e.g., `data-wp-bind--aria-expanded="state.chatOpen"`). |
| `data-wp-class--{name}` | Reactively toggles a CSS class (e.g., `data-wp-class--is-open="state.chatOpen"`). |
| `data-wp-style--{prop}` | Reactively sets an inline style property. |
| `data-wp-text` | Reactively sets the element's `textContent`. |
| `data-wp-html` | Reactively sets the element's `innerHTML`. Use with caution. |
| `data-wp-each` | Renders a list from an array. Must be on a `<template>` element. |
| `data-wp-init` | Runs a callback when the element is first mounted in the DOM. |
| `data-wp-run` | Runs a callback reactively (re-runs when referenced state changes). |
| `data-wp-watch` | Alias for `data-wp-run` in some documentation. |

### Compatibility with Divi 5

**Divi 5 does NOT natively support the Interactivity API.** Divi's renderer bypasses the standard WordPress block pipeline. Divi modules are not WordPress blocks; they have their own registration, rendering, and hydration systems.

For LeadersPath Divi modules, use standard `wp_enqueue_script()` with vanilla JavaScript or a lightweight framework instead of the Interactivity API. If WordPress block-based rendering is ever adopted alongside or instead of Divi, the Interactivity API becomes the preferred approach for frontend reactivity.

### Introduced

WordPress 6.5 (March 2024) as a stable API. The `data-wp-on-async--{event}` directive was added in WordPress 6.6 (July 2024).

### Reference

- Interactivity API: https://developer.wordpress.org/block-editor/reference-guides/interactivity-api/
- Interactivity API Reference: https://developer.wordpress.org/block-editor/reference-guides/interactivity-api/api-reference/

---

## 6. `render_block` Filter Chain

### Full Filter Sequence (in order)

When WordPress renders a single block, these filters fire in this exact order:

1. **`pre_render_block`** (WP 5.1) -- Can short-circuit rendering entirely. If a non-`null` value is returned, WordPress skips all remaining steps and uses the returned string as the block's output.
2. **`render_block_data`** (WP 5.1) -- Receives and can modify the parsed block array before rendering begins. Useful for injecting attributes or swapping block names.
3. **[Inner blocks render recursively]** -- Each inner block goes through this same sequence, depth-first.
4. **[Render callback or static assembly executes]** -- For dynamic blocks, the `render_callback` runs. For static blocks, WordPress assembles HTML from `innerContent`, inserting rendered inner block output at `null` positions.
5. **`render_block_{$name}`** (WP 5.7) -- Filters the output of a specific block type. The `$name` is the block's fully qualified name with slashes replaced (e.g., `render_block_core/paragraph`).
6. **`render_block`** (WP 5.0) -- Filters the output of ALL blocks, regardless of type. Receives the rendered HTML, the parsed block array, and (since WP 6.2) the `WP_Block` instance.

### Code Examples

```php
// Short-circuit: cache expensive blocks.
add_filter( 'pre_render_block', function ( $pre_render, $parsed_block ) {
    if ( 'leaderspath/heavy-block' === $parsed_block['blockName'] ) {
        $cache_key = 'leaderspath_block_' . md5( serialize( $parsed_block ) );
        $cached    = get_transient( $cache_key );

        if ( false !== $cached ) {
            return $cached; // Non-null return skips normal rendering.
        }
    }

    return null; // Return null to proceed with normal rendering.
}, 10, 2 );

// Modify parsed block data before rendering.
add_filter( 'render_block_data', function ( $parsed_block ) {
    if ( 'leaderspath/chatbot' === $parsed_block['blockName'] ) {
        // Inject a default attribute if not set.
        $parsed_block['attrs']['theme'] = $parsed_block['attrs']['theme'] ?? 'light';
    }

    return $parsed_block;
} );

// Filter a specific block type's output.
add_filter( 'render_block_core/paragraph', function ( string $block_content, array $block ): string {
    return '<div class="leaderspath-paragraph-wrapper">' . $block_content . '</div>';
}, 10, 2 );

// Filter ALL block output (use sparingly for performance).
add_filter( 'render_block', function ( string $block_content, array $block ): string {
    if ( empty( $block['blockName'] ) ) {
        return $block_content; // Skip freeform content.
    }

    // Example: add data attributes to all LeadersPath blocks.
    if ( str_starts_with( $block['blockName'], 'leaderspath/' ) ) {
        $block_content = str_replace(
            '<div ',
            '<div data-leaderspath-block="' . esc_attr( $block['blockName'] ) . '" ',
            $block_content
        );
    }

    return $block_content;
}, 10, 2 );
```

---

## 7. Block Patterns & Templates

### Patterns

Block patterns are pre-defined arrangements of blocks that users can insert from the editor. They are purely a convenience feature; once inserted, the blocks are independent and fully editable.

```php
register_block_pattern( 'leaderspath/lesson-layout', [
    'title'       => __( 'Lesson Layout', 'leaderspath' ),
    'description' => __( 'Standard lesson layout with objectives, content area, and activity list.', 'leaderspath' ),
    'categories'  => [ 'leaderspath' ],
    'postTypes'   => [ 'leaderspath_lesson' ], // Restrict to Lesson CPT (WP 6.2+).
    'content'     => '<!-- wp:group {"layout":{"type":"constrained"}} -->
<div class="wp-block-group">
    <!-- wp:heading {"level":2} -->
    <h2 class="wp-block-heading">Learning Objectives</h2>
    <!-- /wp:heading -->

    <!-- wp:list -->
    <ul><li>Objective 1</li></ul>
    <!-- /wp:list -->

    <!-- wp:separator -->
    <hr class="wp-block-separator has-alpha-channel-opacity"/>
    <!-- /wp:separator -->

    <!-- wp:paragraph {"placeholder":"Lesson content..."} -->
    <p></p>
    <!-- /wp:paragraph -->
</div>
<!-- /wp:group -->',
] );

// Register a pattern category.
register_block_pattern_category( 'leaderspath', [
    'label' => __( 'LeadersPath', 'leaderspath' ),
] );
```

### Post Type Templates

A post type template defines the default block structure when a user creates a new post of that type. Unlike patterns, templates can be locked.

```php
register_post_type( 'leaderspath_lesson', [
    // ... other args ...
    'template' => [
        [ 'core/heading', [ 'placeholder' => 'Lesson Title', 'level' => 1 ] ],
        [ 'core/paragraph', [ 'placeholder' => 'Lesson overview and introduction...' ] ],
        [ 'core/heading', [ 'placeholder' => 'Activities', 'level' => 2 ] ],
        [ 'core/paragraph', [ 'placeholder' => 'Activity descriptions will appear here.' ] ],
    ],
    'template_lock' => false, // Options: 'all', 'insert', 'contentOnly', false.
] );
```

| Lock Value | Behavior |
|------------|----------|
| `false` | No restrictions. Users can add, remove, and move blocks freely. |
| `'insert'` | Users can move existing blocks but cannot add or remove them. |
| `'all'` | Users cannot add, remove, or move blocks. Content within blocks is still editable. |
| `'contentOnly'` | Only the content of blocks is editable. Structural changes (attributes, layout) are locked. (WP 6.1+) |

---

## 8. The Full Rendering Lifecycle

### From Database to Browser

```
1. WordPress resolves the template
   (template hierarchy, block theme template, or Theme Builder override)

2. the_content() is called (or the_post_content for block themes)

3. the_content filter fires — callbacks run in priority order:

   Priority 5: do_blocks($content)
   ├── parse_blocks($content) → array of parsed blocks
   └── For each block (depth-first, recursive):
       │
       ├── pre_render_block filter
       │   └── If non-null returned → use as output, skip to step 5
       │
       ├── render_block_data filter
       │   └── Modify parsed block array (attrs, blockName, etc.)
       │
       ├── new WP_Block($parsed_block, $available_context)
       │   └── Resolves attributes (merge saved + defaults)
       │   └── Resolves context (inherit from parent via usesContext)
       │
       ├── Render inner blocks (recursive — each goes through this same flow)
       │
       ├── Execute rendering:
       │   ├── Dynamic block → call render_callback($attrs, $content, $block)
       │   └── Static block  → assemble from innerContent array,
       │                        inserting rendered inner blocks at null positions
       │
       ├── render_block_{$name} filter (block-type-specific)
       │
       └── render_block filter (all blocks)

   Priority 7-9: Shortcode processing (do_shortcode)

   Priority 10: wptexturize() — smart quotes and dashes
   Priority 10: wpautop() — wraps text in <p> tags

   Priority 11: wp_filter_content_tags() — lazy loading, responsive images

4. Final HTML string returned to the template

5. Browser receives HTML

6. Interactivity API runtime (@wordpress/interactivity) loads as ES module
   └── Hydrates data-wp-* directives
   └── Binds reactive store to DOM elements
```

### `the_content` Filter Priority Order

| Priority | Callback | Purpose |
|----------|----------|---------|
| 5 | `do_blocks()` | Parse and render all blocks. Moved from priority 9 to 5 in WP 6.4 to ensure blocks render before shortcodes. |
| 7-9 | `do_shortcode()` | Process `[shortcode]` syntax. Runs after blocks so block output can contain shortcodes. |
| 10 | `wptexturize()` | Converts straight quotes to curly quotes, double hyphens to em dashes, etc. |
| 10 | `wpautop()` | Wraps standalone text in `<p>` tags and converts double line breaks to paragraphs. |
| 11 | `wp_filter_content_tags()` | Adds `loading="lazy"`, `decoding="async"`, and responsive `srcset` attributes to images and iframes. |

**Note:** Divi 5's theme builder hooks into this pipeline at its own priority on `the_content`, replacing or wrapping the standard output with Divi's layout system. Understanding the native priority chain helps diagnose cases where Divi output is unexpectedly modified by WordPress core filters.

---

## Version History

| Feature | WordPress Version | Date |
|---------|-------------------|------|
| Block editor, `parse_blocks()`, `render_block` filter | 5.0 | Dec 2018 |
| `pre_render_block`, `render_block_data` filters | 5.1 | Feb 2019 |
| `WP_Block` class, block context (`usesContext`/`providesContext`) | 5.5 | Aug 2020 |
| `render_block_{$name}` filter (block-type-specific) | 5.7 | Mar 2021 |
| `block.json` as canonical registration method | 5.8 | Jul 2021 |
| `viewScript` property in `block.json` | 5.9 | Jan 2022 |
| `render` property in `block.json` (PHP render file) | 6.1 | Nov 2022 |
| `template_lock: 'contentOnly'` | 6.1 | Nov 2022 |
| `postTypes` for patterns, `$block_instance` param on filters | 6.2 | Mar 2023 |
| Block API v3, `selectors` in `block.json` | 6.3 | Aug 2023 |
| `do_blocks` moved to `the_content` priority 5 (was 9) | 6.4 | Nov 2023 |
| **Interactivity API** (stable), `viewScriptModule`, `viewStyle` | 6.5 | Mar 2024 |
| `data-wp-on-async--{event}` directive | 6.6 | Jul 2024 |

---

## Official Documentation URLs

- Block Registration: https://developer.wordpress.org/block-editor/reference-guides/block-api/block-registration/
- Block Metadata (`block.json`): https://developer.wordpress.org/block-editor/reference-guides/block-api/block-metadata/
- Block Context: https://developer.wordpress.org/block-editor/reference-guides/block-api/block-context/
- Block Patterns: https://developer.wordpress.org/block-editor/reference-guides/block-api/block-patterns/
- Interactivity API: https://developer.wordpress.org/block-editor/reference-guides/interactivity-api/
- Interactivity API Reference: https://developer.wordpress.org/block-editor/reference-guides/interactivity-api/api-reference/
- Data Flow Architecture: https://developer.wordpress.org/block-editor/explanations/architecture/data-flow/
- `WP_Block` class: https://developer.wordpress.org/reference/classes/wp_block/
- `parse_blocks()`: https://developer.wordpress.org/reference/functions/parse_blocks/
- `register_block_type()`: https://developer.wordpress.org/reference/functions/register_block_type/
- `render_block` filter: https://developer.wordpress.org/reference/hooks/render_block/
- `pre_render_block` filter: https://developer.wordpress.org/reference/hooks/pre_render_block/
- WordPress Source (GitHub): https://github.com/WordPress/wordpress-develop/
- Gutenberg Source (GitHub): https://github.com/WordPress/gutenberg/
