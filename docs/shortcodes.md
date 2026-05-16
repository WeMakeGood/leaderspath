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
| `id` | — | DOM `id` on the wrapper |

Boolean attributes accept `yes`/`no`, `true`/`false`, `on`/`off`, `1`/`0`.

### Post ID resolution

The renderer auto-resolves the post ID:

1. Explicit `post_id="123"` attribute, if it matches Activity or Lesson.
2. The current queried post (`get_queried_object_id()` → `get_the_ID()`).
3. The most recent published Activity, then Lesson, as a builder-preview fallback.

### Asset loading

CSS (`assets/css/leaderspath.css`) and JS (`assets/js/chatbot.js` + `assets/js/vendor/marked.umd.js`) register on `wp_enqueue_scripts` but only enqueue when the shortcode actually renders.

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
