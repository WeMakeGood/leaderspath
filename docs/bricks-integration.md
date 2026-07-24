# Bricks Integration

> **Last Updated:** 2026-07-24
> **Verified against:** Bricks Builder 2.3.9 (theme source in `wp-content/themes/bricks/`) and the official Bricks Academy developer docs (https://academy.bricksbuilder.io/developer/)

Bricks Builder owns all display markup for LeadersPath surfaces (dashboard, cohort page, lesson page, course/context/skill lists). It reads plugin data three ways: ACF dynamic data (native), the `{echo:}` PHP-function tag, and Element Conditions. The last two require the **plugin** to register what Bricks is allowed to call — Bricks contains no LeadersPath logic; it only invokes plugin functions during its render pass.

This document is the contract for that boundary: what Bricks can call, how the plugin exposes it, and the security gates that will silently swallow a call if we get the registration wrong.

## Table of Contents

- [The ownership boundary](#the-ownership-boundary)
- [Render-time vs. AJAX](#render-time-vs-ajax)
- [Mechanism A: `{echo:}` PHP function tag](#mechanism-a-echo-php-function-tag)
- [Mechanism B: Element Conditions](#mechanism-b-element-conditions)
- [Mechanism C: ACF dynamic data (native)](#mechanism-c-acf-dynamic-data-native)
- [The `lp_*` public API layer](#the-lp_-public-api-layer)
- [Field naming: design docs vs. plugin](#field-naming-design-docs-vs-plugin)
- [Gotchas](#gotchas)

---

## The ownership boundary

**Plugin owns logic. Bricks owns display. The plugin owns the registration that connects them.**

- Business logic lives in the plugin's namespaced classes (`LeadersPath\Includes\WooCommerce::is_user_enrolled()`, `::get_cohort_phase()`, etc.). This is the single source of truth.
- Bricks calls that logic by **plain global function name** (`{echo:}`) or by evaluating a **condition filter** — it cannot reference a namespaced static method.
- Therefore the plugin exposes a thin layer of global `lp_*` functions that wrap the namespaced methods, and registers them with Bricks. The registration (`add_filter` calls) must live in **version-controlled plugin code**, not in Bricks editor settings or a code-snippet plugin, so the contract cannot drift out of the repo.

This keeps the seam auditable: everything Bricks is permitted to call is enumerated in the plugin's filter registrations.

---

## Render-time vs. AJAX

Everything Bricks needs from the plugin for **display** is read synchronously **during Bricks' server-side render pass** — not via AJAX. Bricks calls the plugin's PHP as it builds the page.

| Need | Mechanism | When it runs |
|------|-----------|--------------|
| Enrollment gating (show/hide) | Element Condition | Render |
| Phase bucketing (active/upcoming/completed cohorts) | `{echo:}` helper or custom query | Render |
| Last-activity arrow state | `{echo:}` helper | Render |
| "This week" badge match | Element Condition (ACF compare) | Render |
| Facilitator / schedule / description display | ACF + WC core dynamic data | Render |

AJAX/REST is only needed for **state changes triggered by user action**, of which there are two:

- **Chatbot** (`/chat`, `/chat/stream`) — already implemented.
- **`[lp_current_lesson]` facilitator write** — sets the `current_lesson` field from the frontend. Deferred post-June (June MVP sets it from WP admin). This is the only *display-adjacent* AJAX surface, and it is a write, not a read.

**Rule of thumb:** if it answers "what should this page contain when it loads," it is render-time and needs a whitelisted function or a condition — not an endpoint. If it answers "the user did something, change state," it needs REST/AJAX.

---

## Lesson page: no AJAX for activity content

The lesson page delivers all activities of a lesson on one page, and navigating between them is a **CSS show/hide toggle (Bricks Interaction), not an AJAX load**. This is a deliberate design decision, not a shortcut. (Design source: `Design/lesson-page.md` in the CoWork project.)

**How it works:**

- Bricks renders **every** activity section server-side at page load, via a query loop over the lesson's activities. Each section carries its own instructions, context-file list, skills list, marker text, and chatbot — all baked into the initial HTML.
- Switching activities toggles a CSS class (`is-active`) on sections. Nothing is fetched. Content is already in the DOM.

**Why not AJAX:**

- Bricks has no first-class "fetch loop item on click" mechanism — AJAX would mean a hand-built REST endpoint + custom JS, which is exactly the plugin-owned complexity the Bricks-owns-display split avoids.
- Activity payloads are tiny (a paragraph + two short lists); pre-rendering a whole lesson is negligible weight.
- Session-day constraint: participants run Google Meet + LeadersPath side-by-side, often on unreliable connections. Preloaded content makes switching instant and network-hiccup-proof; AJAX would stall mid-session.

**The one dynamic piece is the chatbot — initialize-once, never tear down.** Content is static/preloaded; each activity's chatbot is a live JS widget. **Chatbot-per-section (Option A):** every activity section pre-renders `[leaderspath_chatbot post_id="{activity_id}"]` with its config baked in, so no endpoint is needed — config travels in the markup. All chatbots exist in the DOM from page load; hidden sections' chatbots stay alive.

The `MutationObserver` on the workspace watches for the `is-active` class change and its job is **initialize-if-not-yet** — the first time an activity becomes visible, initialize its chatbot; on subsequent visits it is already initialized and its conversation is intact. **No teardown, no re-init, no reset on switch.** (Rejected Option B — one shared widget fetching config per switch — because it reintroduces an AJAX dependency *and* would lose per-activity conversation state.)

**Conversation memory model** (decided May 2026, reaffirmed 2026-07-24):

- **Each activity keeps its own conversation in memory for the entire page session.** Every section's chatbot is a separate widget instance with its own `history` array, alive whether visible or hidden. Hidden ≠ destroyed.
- **Navigate away and back:** the conversation is exactly as the learner left it — nothing is torn down, nothing is fetched.
- **Activities never share history.** Activity 2's chat is not Activity 1's — they are independent instances. (This is what the design docs mean by "chat resets per activity": *separate conversation per activity*, **not** "wiped on return.")
- **Only genuine reset is page reload** (stateless; enables re-experimentation from scratch).

"Keep conversations in memory" (the May decision) means each activity's transcript lives in browser JS memory for the whole page session and is never persisted to a server. There is no conversation-persistence endpoint and none is planned — "memory" here is *browser JS memory*, not durable storage. Persistence across page reloads is intentionally out of scope.

### Per-activity "Start over" reset (required by the no-teardown model)

Because conversations now persist and switching no longer wipes them, page reload became the *only* way to clear a chat — too blunt (it clears every activity) and non-obvious as an affordance. So the widget needs an explicit **per-activity reset control** ("Start over"). This is a first-class learning action, not a convenience: the sandboxes exist for experimentation ("try that again differently"), and re-running from a clean slate is a core participant move.

- **Scope:** resets **only the current activity's** chatbot. Other activities' conversations are untouched.
- **Behavior:** clears that instance's `history` array, resets its `containerId` to `null` (so the Claude API session restarts — the widget reuses container IDs for continuity, so this must reset too), and re-renders the `activity_instructions` opening message.
- **Placement:** the Activity Marker (it already owns per-activity controls — context/skills icon buttons, nav arrows), so the control reads as "reset *this* activity." Chat input row is an acceptable alternative.
- **Not a reload, not an endpoint:** purely client-side state reset on one instance. Net-new (no reset control exists in the widget today); the per-instance `history`/`containerId` state it operates on already exists in `chatbot.js`.

Design framing: teardown-on-switch made reset *implicit and accidental*; no-teardown makes persistence the default and reset *explicit and intentional* — the learner decides when to start over, scoped to the activity they're in.

---

## Mechanism A: `{echo:}` PHP function tag

Bricks' WP dynamic-data provider supports `{echo:function_name(args)}`, which renders the return value of a PHP function inline. Source: `themes/bricks/includes/integrations/dynamic-data/providers/provider-wp.php`.

### Two-level security gate — both must be satisfied

**Level 1 — platform setting (not plugin code).** "Code execution" must be enabled in **Bricks > Settings > Custom code** for the target user role *before any whitelist matters*. The official docs are emphatic: "Make sure to only enable code execution for users & user roles you trust 100%." This is a WordPress-admin configuration step on the deploy checklist, not something the plugin controls. If it's off, `{echo:}` does nothing regardless of the whitelist.

**Level 2 — the function whitelist (plugin code).** With code execution enabled, a function called via `{echo:}` still will **not execute unless its name is whitelisted** (mandatory since Bricks 1.9.7). Bricks applies:

```php
$whitelisted_function_names = apply_filters( 'bricks/code/echo_function_names', $callback );
```

(`provider-wp.php`, ~line 1345.) If the function is not whitelisted, Bricks returns an **empty string** — no error, no warning. A `{echo:lp_is_last_activity()}` tag against an unregistered function renders nothing and looks like a data problem.

The filter receives the callback name and accepts three return shapes (per the official docs):

- **Array of exact names** — `['lp_is_last_activity', 'lp_user_is_enrolled']`. Preferred.
- **Array with regex patterns** — entries prefixed with `@`, e.g. `['@^lp_']` allows every function starting with `lp_`.
- **Boolean from custom logic** — decide per call, e.g. `return strpos($callback, 'lp_') === 0;`. Returning bare `true` allows **all** functions — never do this (arbitrary-code surface).

Bricks also ships a **Code Review** tool (Settings > Custom code) that audits existing `{echo:}` calls across the site and can generate a starter whitelist array — useful for verifying our registration covers every tag actually used in templates.

Additionally (since Bricks 1.12.2): even a whitelisted function will not run in the **builder preview** for a user without code-execution capability:

```php
if ( bricks_is_builder_call() && ! \Bricks\Capabilities::current_user_can_execute_code() ) {
    return '';
}
```

So a whitelisted `lp_*` function renders correctly on the front end but may show empty in the editor for a low-capability user. Expected behavior — not a bug.

### Plugin registration pattern

```php
// In the plugin's Bricks integration bootstrap.
add_filter( 'bricks/code/echo_function_names', function ( $allowed ) {
    // Bricks passes the callback name on the first (non-array) pass; normalize.
    $lp = [
        'lp_is_last_activity',
        'lp_user_is_enrolled',
        // ...one entry per lp_ function Bricks may call
    ];
    return is_array( $allowed ) ? array_merge( $allowed, $lp ) : $lp;
    // Alternatively, to allow the whole namespace by pattern:
    //   return array_merge( (array) $allowed, [ '@^lp_[a-z_]+$' ] );
} );
```

Prefer an **explicit list** over the `@^lp_` pattern for the June build — it keeps the callable surface enumerable and reviewable. Move to a pattern only if the list becomes unwieldy.

### Usage in a template

```
{echo:lp_is_last_activity(post_id)}   → "1" or "" — drives right-arrow dim state
```

Arguments are parsed by Bricks; dynamic tags can be passed as args. Return a **string** (Bricks echoes it directly). For booleans, return `'1'`/`''` so empty-checks work in conditions and CSS.

---

## Mechanism B: Element Conditions

Element Conditions are **server-side**, evaluated at render time in `themes/bricks/includes/conditions.php` (`Conditions::check()`). They decide whether an element renders at all — the right tool for enrollment gating and phase-based visibility. There are two integration paths.

### Path 1 — `bricks/conditions/result` (simplest, no editor UI)

Bricks evaluates each condition to a boolean, then hands it to third parties to override (`conditions.php` line 1203, since Bricks 1.8.4):

```php
apply_filters( 'bricks/conditions/result', $render_set, $key, $condition );
```

- `$render_set` (bool) — Bricks' computed result; return your own bool to override.
- `$key` (string) — the condition's key. Use a sentinel key you set in the editor (e.g. a "Dynamic data" condition whose key/value you recognize) to detect "this is our custom condition."
- `$condition` (array) — the full condition row: `['key' => ..., 'compare' => ..., 'value' => ...]`.

This path needs no registration in the editor — you attach a recognizable condition in Bricks and intercept it in the filter. Lower ceremony; the tradeoff is the condition isn't a labeled dropdown option.

```php
add_filter( 'bricks/conditions/result', function ( $result, $key, $condition ) {
    if ( ( $condition['key'] ?? '' ) === 'lp_enrolled_current_cohort' ) {
        return lp_user_is_enrolled(); // plugin logic
    }
    return $result;
}, 10, 3 );
```

### Path 2 — registered condition (first-class editor dropdown)

To make "User is enrolled in this cohort" appear as a named option in the Bricks condition UI, register a **group** and an **option** (`conditions.php` lines 58, 759):

```php
add_filter( 'bricks/conditions/groups', function ( $groups ) {
    $groups[] = [ 'name' => 'leaderspath', 'label' => 'LeadersPath' ];
    return $groups;
} );

add_filter( 'bricks/conditions/options', function ( $options ) {
    $options[] = [
        'key'     => 'lp_enrolled_current_cohort',
        'group'   => 'leaderspath',
        'label'   => 'User is enrolled in this cohort',
        'compare' => [ 'type' => 'select', 'options' => [ 'true' => 'Yes', 'false' => 'No' ] ],
    ];
    return $options;
} );
```

Then still resolve the boolean in `bricks/conditions/result` keyed on `lp_enrolled_current_cohort`. More code, better editor experience. Use Path 2 for conditions facilitators/builders pick often; Path 1 for one-offs.

### Option structure reference

A condition option (from Bricks' own registrations) is:

```php
[
  'key'     => 'post_id',              // condition identifier
  'group'   => 'post',                 // which group tab it appears under
  'label'   => 'Post ID',              // editor label
  'compare' => [ 'type' => 'select', 'options' => [...], 'placeholder' => '==' ],
  'value'   => [ 'type' => 'text' ],   // the value input control
]
```

---

## Mechanism C: ACF dynamic data (native)

Native since Bricks 1.12 — no plugin code required. Bricks reads ACF fields and relationships directly:

- Field value: `{acf:field_name}`
- Relationship-filtered query loops: query loop `Include` control accepts an ACF relationship dynamic tag.
- WC core fields on the cohort product: `{post_title}`, `{post_content}` (long description), `{post_excerpt}` (short description), price, stock — all native.

Use native ACF/WC dynamic data for everything that is plain field display. Reserve Mechanisms A and B for **computed** values (is-last, is-enrolled, phase bucket) that no single field holds.

---

## The `lp_*` public API layer

The `lp_*` global functions are **thin adapters**, not a second implementation. Each wraps a namespaced method and exists solely because Bricks calls by global name. Implemented in `includes/bricks-functions.php`; registered in `includes/class-bricks-integration.php`. (Status: implemented 2026-07-24, verified against live cohort/course/lesson data.)

| `lp_*` function | Wraps | Mechanism | Returns | Purpose |
|-----------------|-------|-----------|---------|---------|
| `lp_user_is_enrolled( ?int $cohort_id = null )` | `WooCommerce::is_user_enrolled()` | Condition (B) | `bool` | Gate learner content. Fails **closed**: false for logged-out, unresolvable, or non-cohort post. No sample fallback (it's a security check). Null → current queried object, which must be a cohort product. |
| `lp_is_last_activity( ?int $activity_id = null )` | lesson `lesson_activities` order lookup | `{echo:}` (A) | `'1'`/`''` | Right-arrow dim state. Display helper — resolves current activity via `Post_Id_Helper` (incl. its sample fallback for builder previews), so it should be passed a valid activity id in real loop use. |
| `lp_enrolled_cohorts( string $phase = '' )` | `WooCommerce::get_user_enrollments()` + `get_cohort_phase()` | PHP call → query `post__in` | `array<int>` | Phase-bucketed dashboard loops. `$phase` = ''/'active'/'upcoming'/'completed'. Call in PHP for the ID array — `{echo:}` stringifies arrays awkwardly. |

Contract for these functions:

- Global namespace, `lp_` prefix (matches the design-doc convention; distinct from the plugin's internal `leaderspath_`/`LeadersPath\` code).
- No logic of their own beyond argument marshalling — delegate to the class method.
- Return render-safe values: strings for `{echo:}`, bools for conditions, arrays of IDs for query helpers.
- Every one must be registered on `bricks/code/echo_function_names` (if called via `{echo:}`) or resolved in `bricks/conditions/result` (if a condition).

---

## Field naming: design docs vs. plugin

The CoWork design docs and wireframes use short/unprefixed field names in their annotations. The **plugin's actual ACF field names differ**. Bricks dynamic-data tags must use the **plugin** names, or the tag returns empty.

| Design-doc annotation | Actual plugin field | On |
|-----------------------|---------------------|-----|
| `activities` | `lesson_activities` | Lesson |
| `context_files` | `chatbot_context_files` | Activity |
| `skills` | `chatbot_skills` | Activity |
| `lessons` | `course_lessons` | Course |
| `facilitator` | `cohort_facilitator` | Cohort product |
| `activity_instructions` | `chatbot_system_prompt` (confirm intent — see note) | Activity |
| `current_lesson` | *(to be added)* | Cohort product |
| cohort video field | *(to be added)* | Cohort product |
| `cohort_description` | WC core `post_content` / `post_excerpt` (no ACF field) | Cohort product |

**Note on `activity_instructions`:** the wireframes show learner-facing instructions surfaced at the top of the chat *and* in the stepper. The plugin has no such field — it has `chatbot_system_prompt` (the system prompt, not necessarily learner-readable). Decide whether the system prompt doubles as displayed instructions or a separate learner-facing field is needed before building the lesson page.

---

## Gotchas

- **Deploy prerequisite: "Code execution" must be enabled** in Bricks > Settings > Custom code for the relevant user role. Off by default. Without it, no `{echo:}` tag runs regardless of the whitelist. Add to the deploy checklist alongside `/dashboard/` My Account setup.
- **`{echo:}` on an unregistered function returns `''` silently.** No error. If a dynamic value renders blank, check (1) code execution is enabled, then (2) the `bricks/code/echo_function_names` whitelist.
- **`{echo:}` may render empty in the builder preview** for users without code-execution capability (since 1.12.2) while working correctly on the front end. Not a bug.
- **Never return `true` from `bricks/code/echo_function_names`** — it whitelists every function, making templates an arbitrary-code-execution surface. Always return a list or `@`-prefixed pattern.
- **Condition filter runs for *every* condition on the page.** In `bricks/conditions/result`, always gate on your recognizable `$key`/`$condition` before returning a custom value, or you'll override unrelated conditions.
- **Enrollment lives in user meta; phase is derived from ACF dates at runtime.** Neither is a `WP_Query`-able column. "Loop my cohorts where phase = active" cannot be a plain meta query — it needs the `lp_enrolled_cohorts( 'active' )` helper returning IDs, then a query loop with `post__in`.
- **The chatbot is not a data element.** It has a JS lifecycle (reinit on activity switch). It stays a shortcode (`[leaderspath_chatbot]`, alias `[lp_activity_chatbot]`), not a dynamic-data tag.
- **Registration must be plugin code.** Do not whitelist functions or define conditions via a snippets plugin or theme functions.php — the contract belongs in the versioned plugin.
