# LeadersPath Shortcodes

The plugin ships **one shortcode**: `[leaderspath_chatbot]`. Everything else (Lesson Meta, Activity Meta, Context Library, Skills List, Lesson Activities, Lesson Objectives, Course Lessons) is built directly in the page builder. Bricks Builder query loops read ACF fields natively and produce the markup — see [ui-ux-catalog.md](ui-ux-catalog.md) for the field-to-display reference.

The chatbot remains a shortcode because it bundles markup, JS enqueue, asset wiring, REST nonce, and ACF detection (Activity sandbox vs. Lesson Q&A) into one drop-in.

---

## `[leaderspath_chatbot]`

Interactive chatbot widget. Auto-detects whether it's on an Activity or Lesson and reads the appropriate `chatbot_*` or `lesson_chatbot_*` ACF fields. Returns empty string when the chatbot is disabled in ACF.

### Attributes

| Attribute | Default | Notes |
|---|---|---|
| `show_heading` | `yes` | Toggle the widget heading |
| `heading_text` | `AI Sandbox` | Heading label |
| `empty_text` | `Send a message to start the conversation.` | Empty-state copy |
| `post_id` | (auto) | Override the auto-resolved Activity/Lesson ID |
| `class` | — | Extra CSS classes on the wrapper |
| `id` | — | DOM `id` on the wrapper. A numeric value is instead treated as an alias for `post_id` (see Post ID resolution) |
| `height` | — | A CSS length (`600px`, `40rem`) sets a fixed height on the wrapper. `100%` (or `fill`) makes the wrapper a flex item (`flex:1;min-height:0`) that fills whatever height its host container provides — the host still needs its own height/flex chain (see `docs/bricks-integration.md`) for that to resolve against anything real. Leave unset for the widget's own `min-height: 500px` floor. |
| `debug_length` | `0` | Layout debug aid. An integer repeat count — when set, replaces the opening instructions with that many repetitions of long placeholder text, so scroll/height CSS can be checked against a tall message without a real conversation. Never sent to the AI; remove from a template once layout work is done. |

Boolean attributes accept `yes`/`no`, `true`/`false`, `on`/`off`, `1`/`0`.

### Post ID resolution

The renderer auto-resolves the post ID:

1. Explicit `post_id="123"` attribute, if it matches Activity or Lesson.
2. `id="123"`, if numeric (an alias for `post_id`, matching the `id="{activity_id}"` convention used elsewhere and how Bricks routes a loop item's ID into the shortcode). A non-numeric `id` is left alone as a cosmetic wrapper `id` attribute instead.
3. The current queried post (`get_queried_object_id()` → `get_the_ID()`).

There is no sample-post fallback: if none of the above resolves to an enabled Activity or Lesson, the shortcode renders nothing (empty string) rather than showing a stand-in post's chatbot.

### Asset loading

CSS (`assets/css/leaderspath.css`) and JS (`assets/js/chatbot.js` + `assets/js/vendor/marked.umd.js` + `assets/js/vendor/purify.min.js`) register on `wp_enqueue_scripts` but only enqueue when the shortcode actually renders. DOMPurify sanitizes marked.js's output before it's set as `innerHTML` — marked.js itself has no HTML-sanitization option in this version and will pass raw `<script>`/event-handler markup straight through otherwise (see `docs/TASKS.md` Phase 15, "Markdown/HTML conversion audit").

### Theming

Every color, and the dominant spacing/radius values, in `leaderspath.css` read a `--lp-chatbot-*` CSS custom property with a literal fallback (e.g. `border-radius: var(--lp-chatbot-radius, 8px)`), so the widget renders correctly with no host CSS at all. A host page can restyle it without editing this file by setting those custom properties anywhere that cascades to `.leaderspath_chatbot` — a Bricks global variable landing in `:root` is sufficient (confirmed working: Bricks global variables compile into a page's `:root` block, which the widget's own `var(...)` calls then read through normal cascade).

Names are deliberately generic, not tied to any host's own token names: `--lp-chatbot-color-accent`, `--lp-chatbot-color-danger`, `--lp-chatbot-color-success`, `--lp-chatbot-color-border`, `--lp-chatbot-color-surface`, `--lp-chatbot-color-surface-muted`, `--lp-chatbot-color-text`, `--lp-chatbot-color-text-muted` (plus `-hover`/`-muted-2` variants), `--lp-chatbot-radius`, `--lp-chatbot-radius-s`, `--lp-chatbot-space`, `--lp-chatbot-space-s`, `--lp-chatbot-font`, `--lp-chatbot-font-size`. Only the outer-container radius (`--lp-chatbot-radius`) and structural padding rhythm are themed this way — fine-grained values (icon dimensions, badge padding, line-heights) stay fixed as implementation detail.

Do **not** add a literal `:root { --lp-chatbot-*: ...; }` block to this file — that was tried and reverted (see `docs/TASKS.md`, 2026-09-21/24 entries): a real declaration in this file's own `:root` beats a host's `var(--sandstone-200)`-style override by source order/specificity, defeating the whole mechanism. The fallback inside each `var()` call is the only default; there is no second source of truth.

---

## Calling the renderer directly

The chatbot renderer can be invoked from theme PHP or another plugin if you need the widget outside a shortcode context:

```php
echo \LeadersPath\Renderers\Chatbot_Renderer::render(
    [
        'show_heading' => true,
        'heading_text' => 'Try It Out',
    ],
    $post_id // optional override
);
```

If you do this, you'll also need to enqueue `leaderspath` (style) and `leaderspath-chatbot` (script) yourself — the shortcode handler is what wires that up.

`Chatbot_Renderer::get_data()` returns the chatbot configuration (enabled flag, post ID, post type, allow_model_switch, default_model) for callers who want to know what would render without producing markup.
