# LeadersPath Development Tasks

**Last Updated:** 2026-09-18
**Current Phase:** Cohort-scoped chat context is fully built and verified (Phase 15, all 5 stories closed). WS Form cohort-purchase integration fully closed out. WooCommerce cohort-purchase path deprecated. Cohort CPT now behaves as a standard WP post type with role-scoped facilitator access.

**Compaction note (2026-09-18):** This file was compacted at commit `d55f20d` — that commit contains the full, pre-compaction history for every phase summarized below. To read a compacted phase's original task-by-task detail: `git show d55f20d:docs/TASKS.md`, or `git log --all --oneline -- docs/TASKS.md` to browse the file's history. Phases superseded before shipping (see "Removed" note per phase) were deleted outright, not summarized — that reasoning still lives in git history if needed.

---

## Task Status Legend

- `[ ]` - Not started
- `[~]` - In progress
- `[x]` - Completed
- `[!]` - Blocked (see notes)

---

## Completed Phases (compacted — full detail: `git show d55f20d:docs/TASKS.md`)

- **Phase 1: Foundation** — Plugin scaffolding, docs, build system.
- **Phase 2: Data Layer** — CPTs, taxonomies, ACF field groups, roles & capabilities.
- **Phase 3: Admin Interface** — Settings page (encrypted API key, model selection), custom admin columns, quick edit.
- **Phase 4: REST API & Claude Integration** — Chat endpoint, context/skill downloads, Claude API with container + code execution + skills.
- **Phase 5–12: Divi 5 modules built, then fully removed; frontend rebuilt as builder-agnostic PHP renderers + shortcodes.** 9 Divi modules were built module-by-module (Lesson Meta, Lesson Activities, Activity Meta, Course Lessons, Context Library, Skills List, Lesson Objectives, Chatbot), then removed entirely (`844094c`) after incomplete Divi 5 research led to real antipatterns. Migrated (`Phase 12`, 2026-05-03) to plain PHP renderers in `includes/renderers/` + WordPress shortcodes — builder-agnostic (works in Bricks, Gutenberg, classic editor). No Divi code remains in the codebase.
- **Phase 13: Bricks owns display (2026-05-16)** — Once Bricks' own query loops + dynamic data could read ACF directly, all 7 non-chatbot renderers/shortcodes were deleted. The plugin's job narrowed to schema, REST API, Claude API, and the chatbot widget (the one surface it still renders, since it bundles JS/asset wiring/nonce/dual-mode detection).
- **Phase 6: Polish & Testing** — Never substantially started; superseded by real testing done ad hoc throughout later phases (each feature verified live against real data as built, not via a separate test-writing phase). No PHPUnit/Jest harness exists as of this writing (`tests/` is still the unused scaffold).
- **Phase 7: WooCommerce Cohort Product (early cohort-as-product model)** — Built, then fully superseded by the Phase 14 correction (cohort is a purchase-time CPT instance, not the product) and later by the WS Form purchase mechanism. No longer describes live architecture.
- **Phase 8: Schema Refinements** — Prerequisites moved from Activity to Course level; `course_prerequisites` field added.
- **Phase 9: Admin Columns Cleanup (2026-02-16)** — Slug columns standardized across all CPTs; implementation-detail columns removed.
- **Phase 10: Streaming Chat Responses (SSE)** — `Claude_API::stream_message()`/`execute_stream()`, `/chat/stream` REST route, `chatbot.js` streaming branch with real-time markdown rendering, stop-generating button, retry-with-backoff for transient errors (500/502/503/529). Non-streaming `/chat` kept as the permanent fallback (browser compat), not a deprecated path. **Still open, never closed out:** verify `X-Accel-Buffering: no` actually disables proxy buffering on the real production Nginx config; client-side connection-timeout/heartbeat detection (30s no-event → "Connection interrupted"); `docs/claude-api-integration.md` streaming-architecture doc update; a deliberate slow-network (throttled DevTools) manual test pass. None of these have blocked anything shipping since — flagging in case they matter for a future infra migration, not urgent work.
- **Phase 11: Web Tools for Skills (2026-02-17)** — `web_search`/`web_fetch` auto-included server-side tools when an activity has skills (the sandboxed container has no direct internet access). Superseded in later hardening: see Phase 15 API Version work below for the per-model tool-calling fix.

---

## Phase 14: Cohort Architecture — CPT correction, Enrollment split, roster, WS Form purchase (2026-09-16 to 2026-09-17)

The single largest body of work in this plugin's history — corrected a foundational modeling error (cohort was originally conflated with the WooCommerce product it's sold through), then built the real architecture and its purchase mechanism. Full session-by-session narrative: `git show d55f20d:docs/TASKS.md`, section `## Phase 14: Cohort-Scoped Context Files + Roster (2026-09-16)` through the section immediately preceding `## Phase 15: API Version Configuration UI + Haiku Tool-Calling Fix` — REST permission audit, the WC-checkout-field-chain design that was researched then abandoned same-session, and the complete WS Form integration debugging trail.

**Removed from this file (2026-09-18 compaction):** an entire WC-specific purchase-flow design — a "Cohort Kind" (Organization/Mixed) product-type selector, a 5-hook WooCommerce checkout field chain for a customer-facing requested-start-date, and Variable Product seat-count-as-price-variation — was designed in real detail, then abandoned **before being built at all** once WS Form Pro + Stripe Elements was found to cover the same requirement without WC's cart/order machinery. That design has no WS Form analog and never shipped; removed outright rather than compacted, per the "moot/no-longer-relevant" pruning this compaction pass was asked to do. The abandonment reasoning (WC's order-management UI wasn't actually needed for one product sold to one kind of buyer) is preserved in git history if ever relevant again.

### What's real and still governs the codebase today

- **Cohort is a purchase-time instance** (`leaderspath_cohort` CPT), not the WooCommerce product — the product is a reusable catalog offering; each purchase creates one cohort instance with its own facilitator, dates, courses, and roster.
- **`Enrollment` (commerce-agnostic) vs. `WooCommerce` (commerce-specific) split** — `Enrollment::create_cohort()` is the single canonical cohort-creation operation; every caller (WS Form, WP-CLI, the dormant WC order hook) goes through it, with zero risk of drift between how a cohort gets created depending on which caller triggered it.
- **`cohort_access_closed`** — a manual, admin-only toggle checked live ahead of the enrollment chain. Corrects a real bug in the original shipped code, where any refund/cancellation auto-revoked the whole cohort's access regardless of reason; access and billing are now treated as separate questions.
- **Roster/seat-invite system** (`Enrollment::invite_to_cohort()`, `admin/class-cohort-roster.php`) — admin-side, built and verified. Uses stock WordPress account-creation + password-reset, not a custom invite-token system. WC My Account self-service was deliberately deferred, not built.
- **`Cohort_Rewrite`** (`includes/class-cohort-rewrite.php`) — registers `/learn/{cohort-slug}/lesson/{lesson-slug}/` as an explicit, cohort-aware entry point, additive to the lesson's own canonical `/lesson/{slug}/` permalink. Fails closed (real 404, not a sample-post fallback) on any unresolvable or mismatched cohort/lesson pair.
- **WP-CLI commands** (`includes/class-cli-commands.php`) — `wp leaderspath cohort create/invite`, `wp leaderspath post create/update/get/list` across all 6 managed CPTs. Wrap the same `Enrollment` methods every other caller uses.

### WooCommerce purchase mechanism deprecated — WS Form Pro + Stripe Elements is the real, live path

**Decided directly by the user:** "Based on what we've built, the WC path is completely deprecated. If we were to implement WC in the future it would need to follow the WSForm approach." `class-woocommerce.php` is left in place (architecturally harmless, per the multi-caller `Enrollment` design) but is dormant — not a live purchase path.

**The real, shipped integration:** `includes/class-ws-form-integration.php` (`WS_Form_Integration`, hook tag `leaderspath_ws_form_cohort_submitted`) receives WS Form's "Run WordPress Hook" action on real form submissions and calls `Enrollment::create_cohort()` directly — never WS Form's own Post Manager field-mapping, which would silently skip owner resolution/course copying/auto-enrollment. Fields are read by label (`wsf_field_get_objects()`), not hardcoded field ID, so a relabeled form field breaks loudly rather than silently pointing at the wrong data.

- **Form 7, "LeadersPath Cohort Purchase"** is the real, live, tested form — org-cohort only (a separate form is planned for the mixed-cohort tier, not yet built). Payment method (Credit Card vs. Purchase Order) branches via WS Form's own section-visibility conditional logic; a synchronous Stripe Elements charge sets `cohort_payment_status = 'paid'` directly in the same hook invocation (no webhook/async-confirmation path needed — confirmed against a real test transaction), while Purchase Order sets `pending_payment` with no further automation (an admin manually flips the status once the PO/invoice resolves offline).
- **Closed out and fully verified end-to-end (2026-09-17):** a real new submission (#4) was run through the live hook and produced cohort #348, with every field (org name, seats, course, start date, payment status, owner, source record) confirmed matching between the raw submission and the created post. This integration is done, not a remaining task.
- **Two real bugs found and fixed during this build**, both by live testing rather than inspection: (1) WS Form's choice-type fields (`select`/`price_select`) submit as a PHP array of selected label(s), not a scalar — the original `(string)` cast silently produced the literal string `"Array"`, causing a wrong seat count on the first live submission; fixed to unwrap the array first. (2) Seat count was originally parsed via regex from a package label's text range — reconfigured instead to read a real Seats column value directly from the form field, removing the regex parser entirely.
- **Cohort type (org vs. mixed)** is a `leaderspath_cohort_type` taxonomy (`organization`/`mixed-group` terms), resolved at the **form level** (which form was submitted), not a field within one shared form — a mixed cohort is a standing post individual buyers *join* over time, not one created per single purchase.
- **Explicitly out of the plugin's scope, decided directly by the user:** pre-cohort discovery/intake data (an org's AI stance, hard-no's, participant list, facilitator prep notes) — "WSForm will handle that and it will change over time. It's outside your scope." No ACF field or schema exists for it, and none should be added.

**Still unbuilt, real future work, not forgotten:** `add_to_cohort( $user_id, $cohort_id )` (the enrollment primitive a future mixed-cohort form's hook handler would call — joining one buyer into an existing standing cohort), the mixed-cohort form itself, and however WS Form will list currently-open mixed cohorts to choose from.

### Cohort-scoped Context Files (Model A) — the Phase 15 foundation

Context files can belong to exactly one cohort (`context_cohort` ACF field, confidential org material) or no cohort (shared curriculum content, unchanged prior behavior). Facilitators get real create/edit access but never `edit_others_`/`read_private_`/`edit_others_leaderspath_contexts` — a `map_meta_cap` exception (`Capabilities::grant_facilitator_own_cohort_context()`) grants per-post access only to their own cohort's files and actively denies everything else (required because WordPress's own default `read_post` mapping otherwise falls back to a generic `read` cap every logged-in user holds, on this publicly-queryable CPT).

`admin/class-cohort-context.php` (`Cohort_Context`) puts context-file management on the Cohort's own edit screen (list + drag-and-drop create + detach), correcting an earlier UX direction that required a facilitator to leave the cohort screen and hunt for the right cohort in a `post_object` dropdown with no disambiguation between similarly-named cohorts.

**Known gap, not yet solved:** detaching a context file from a cohort leaves it orphaned with no easy admin-list discovery path (it's not publicly queryable in the old sense — though see the 2026-09-18 Cohort fix below, which did make the CPT itself publicly queryable; Context Files were not part of that change). A workflow for finding/managing orphaned Context Files is still needed.

---

## Phase 15: Cohort-Scoped Context Files — all 5 stories closed (2026-09-17 to 2026-09-18)

Five user stories, all now built and verified. Full original design write-up (wireframe/Build-Requirements-doc citations, sequencing rationale): `git show d55f20d:docs/TASKS.md`, section `## Phase 15: Cohort-Scoped Context Files (design, not yet built) — 2026-09-17`.

- [x] **Story 1 — facilitator attaches context files to a cohort.** `Cohort_Context` metabox (above), built in the same session Phase 15 was scoped.
- [x] **Story 2 — cohort context loads into activity chat.** `Enrollment::get_cohort_context_files()`, `Chatbot_Renderer`'s `data-cohort-id` widget attribute, `chatbot.js` sending `cohort_id` on every chat/stream/warm request, and `REST_API::resolve_cohort_id()` (the real security boundary — a client-supplied `cohort_id` is always re-verified against real enrollment before use, never trusted on its own) all wired together so `Claude_API::build_system_prompt()` merges a cohort's context files alongside the activity's own. **Verified with a real cross-cohort leak attempt, blocked**: a real user legitimately enrolled in a different cohort could not get another cohort's confidential context injected by claiming its ID, confirmed via an actual live Claude API response containing none of the injected content.
- [x] **Story 3 — "Disable All Context" toggle.** New ACF field `chatbot_disable_context` on the Activity; when on, both the activity's own context files and any cohort context are skipped together (system prompt and skills unaffected) — the "blindfolded" baseline/control curriculum exercise.
- [x] **Story 4 — drag-and-drop file upload into chat.** Built, verified end-to-end against the live Anthropic Files API, and later hardened with a real security fix (see "Security fix: cross-user/cross-activity file_id replay," below) and an admin-configurable size cap (Settings → Chat Upload Size Limit, replacing a hardcoded 30MB constant).
- [x] **Story 5 — learner-visible context/skills panel.** Superseded by story 2's automatic injection making the underlying data cohort-aware — the remaining UI-surfacing work (the lesson-page wireframe's `[f] context`/`[s] skills` toggle panel) is now just a matter of querying already-correct, access-filtered data; not yet built as a UI, but no longer blocked on any missing data layer.

**Follow-up, same day (2026-09-18):** `ACF_Fields::filter_activity_context_files_public_only()` now excludes cohort-scoped files from the Activity's own `chatbot_context_files` picker entirely (every role, no admin/editor exception) — now that cohort context auto-injects, manually picking a specific cohort's confidential file into a shared activity's field was never correct.

**Also fixed in this Phase's scope, a real pre-existing security gap:** `check_read_permission()` (`/context/{id}/download`, `/skills/{id}/download`) originally checked only `is_user_logged_in()` — any logged-in user could download any context file regardless of cohort/enrollment. Now mirrors `check_chat_permission()`'s full enrollment-chain check.

---

## Same-session standalone work (2026-09-17 to 2026-09-18)

Smaller, self-contained features/fixes that don't belong to a numbered phase. Full narrative for each: `git show d55f20d:docs/TASKS.md`.

- **Markdown/HTML conversion audit + real XSS fix (2026-09-17).** Found and fixed a real, exploitable XSS gap: `chatbot.js`'s client-side `marked.js` use had no output sanitization, confirmed exploitable via a pasted `<img onerror>` payload executing in the learner's own browser on send. Fixed by vendoring DOMPurify v3.4.15 (SRI-verified) and sanitizing `marked.js`'s output; verified in a real headless-Chrome test against the actual vendored files.
- **API Version Configuration UI + Haiku tool-calling fix (2026-09-17).** The seven API-version settings fields (`beta_*`/`tool_*`) became curated `<select>` dropdowns with a "Custom…" fallback, replacing plain text inputs with no guidance. Building the new connectivity test surfaced a real bug — Claude Haiku 4.5 rejects `web_search`/`web_fetch` outright, but the plugin added those tools unconditionally whenever an activity had skills, regardless of model. Fixed immediately (`Claude_API::build_skills_tools()`, a new `NO_PROGRAMMATIC_TOOL_CALLING_PREFIXES` constant), not just logged. `CLAUDE.md` gained a maintenance-cadence note with real lookup URLs, since a connectivity test can only catch "this value now errors," not "a newer value exists."
- **Copy-to-clipboard on chat message bubbles (2026-09-17).** Every message bubble gets a small copy button, copying the original plain-text/markdown source rather than rendered HTML. Hidden on an in-progress assistant message until streaming finishes. A real CSS regression (`flex-direction: row-reverse` silently breaking user-message right-alignment without also flipping `justify-content`) was found by the user's own live testing and fixed.
- **Story 4 file upload built and verified end-to-end (2026-09-17).** Drag-and-drop/click-to-browse file upload for skills-enabled activities, via Anthropic's Files API + `container_upload` content block. A real bug was found during verification: `wp_check_filetype_and_ext()` doesn't recognize `.md`/`.json` in WordPress's default mime map, which would have silently rejected Markdown uploads despite being an explicitly allowed type — fixed via the function's documented mime-override argument. Verified against the live API with real CSV and Markdown uploads, both correctly read by a real skill in a live chat.
- **Admin-configurable upload size cap (2026-09-17).** The 30MB upload cap became a real Settings field (1–500MB, clamped) instead of a hardcoded constant, for throughput/token-spend control.
- **Security fix: cross-user/cross-activity `file_id` replay in chat uploads (2026-09-17).** A genuinely good user question ("could uploaded files be accessible across different users' chats?") surfaced a real gap: Anthropic's Files API is workspace-scoped, not per-user, and the plugin accepted a client-supplied `file_id` with zero ownership check. **Confirmed as a real, working exploit before the fix** — a second real, legitimately-enrolled user could replay another learner's `file_id` (even across a different activity) and have Claude read their file content. Fixed with a short-lived ownership transient (checked before any `attached_file_id` is used) plus deleting the file from Anthropic immediately after its one legitimate use (shrinking retention from their standard "up to 30 days" to effectively one request). Both fixes verified against the live API with real accounts.
- **Cohort CPT fixed to behave like a standard WordPress post type (2026-09-18).** The CPT was missing `editor`/`thumbnail`/`excerpt`/`slug` support entirely and was `publicly_queryable => false` — an oversight from the original WooCommerce-to-CPT port, not a deliberate restriction. Now matches Activity/Lesson/Course's registration exactly, with a real "View" action and public permalink (`/cohort/{slug}/`) — what a non-enrolled visitor's page actually shows is a template/conditional-logic question for later, separate from this fix. Facilitators gained real (previously nonexistent) Cohort access, scoped to their own assignment via the same narrow-grant + `map_meta_cap`-exception pattern already used for Context Files, plus a `pre_get_posts` admin-list filter (UX narrowing, not the security boundary) so a facilitator's Cohorts list shows only their own cohort(s).

---

## Discovered Tasks (open, unresolved)

- [ ] Handle file outputs from code execution (deferred — not critical for MVP).
- [ ] Handle skill deletion (delete from Anthropic when a Skill post is trashed?).
- [ ] `docs/bricks-integration.md` documents `[lp_activity_chatbot]` as a real shortcode alias; it isn't registered anywhere in code. Either register the alias or correct the doc.
- [ ] Verify `can_user_access_skill()`'s "unattached = admin only" behavior is actually correct, or give it the same public/cohort treatment `can_user_access_context()` got.
- [!] **Live bug, not yet fixed:** `WooCommerce::handle_order_refunded()`/`handle_order_cancelled()` still auto-unenroll the whole cohort on any refund/cancellation, for any reason — `cohort_access_closed` (the manual toggle) exists now, so the fix (removing the auto-unenroll call) is unblocked. Real-world exposure is lower now that the WC purchase path is deprecated, but the dangerous behavior is still live in the code.
- [ ] A workflow for finding/managing orphaned (detached, non-cohort, non-public) Context Files from the admin list — needed once cohort-context detach sees regular use; not designed yet.
- [ ] Mark `class-woocommerce.php` as deprecated in its own docblock/header comment, so a future reader doesn't assume it's the live purchase path.
- [ ] Decide whether to clean up the one real WC product ("Core Cohort Package") and its test-era cohorts, or leave them as historical/dormant data.
- [ ] `add_to_cohort( $user_id, $cohort_id )` — the mixed-cohort join primitive — and the mixed-cohort form itself remain unbuilt (see Phase 14).
- [ ] Story 5's learner-visible context/skills panel UI (the lesson-page wireframe's toggle panel) is not yet built, though its data layer is now correct and cohort-aware (see Phase 15).
- [ ] PHPUnit/Jest test harness — `tests/` is still the unused scaffold (`test-sample.php`). Every feature to date has been verified live against real data instead; a real automated harness remains a separate, not-yet-started body of work.
- [ ] Phase 10 loose ends: verify `X-Accel-Buffering: no` on the real production Nginx config; client-side stream connection-timeout/heartbeat detection; `docs/claude-api-integration.md` streaming-architecture doc update; a deliberate slow-network manual test pass.
- [ ] Build sample Bricks templates for Activity/Lesson/Course CPTs and verify the chatbot widget renders correctly inside each context — never done, no blocker, just not yet exercised.
- [ ] Admin column label/rendering consistency audit across all CPTs (date formatting, count display, dashicon fallbacks) — never done beyond what the Cohort roster/admin-UI pass specifically needed.
- [ ] `wp leaderspath post update --field=...`'s known limitation: only handles scalar values and comma-separated ID lists, can't set a repeater's nested sub-fields (`lesson_objectives`, `lesson_references`) in one flag. Documented in the command's own docblock; still true.
- [ ] Whether any frontend surface besides `chatbot.js` renders unsanitized user-supplied or AI-supplied markdown/HTML via `innerHTML` — the XSS fix (see above) was scoped to the one audit that found it, not a full sweep of the plugin.

---

## Decisions Log

Chronological, most architecturally-significant decisions across the plugin's history. Divi-5-specific rows (module architecture, VB patterns, Style::add() format, etc.) were removed in the 2026-09-18 compaction — that architecture no longer exists in the codebase; full detail if ever needed: `git show d55f20d:docs/TASKS.md`.

| Date | Decision | Rationale |
|------|----------|-----------|
| 2026-01-29 | CPTs for Context Files and Skills | WordPress revision history, familiar admin UI, ACF integration |
| 2026-01-29 | No conversation persistence | Fresh start on page reload enables experimentation |
| 2026-01-29 | Single plugin-wide API key | Simpler management, billing at org level |
| 2026-02-03 | Facilitated cohort learning model | Facilitator-led, not self-paced |
| 2026-02-03 | Dual chatbot modes | Activity Sandbox (demonstrate behaviors) vs Lesson Q&A (helpful assistant) |
| 2026-02-03 | Privacy-first Q&A bot | No logging, no access restrictions |
| 2026-02-09 | Nomenclature: Course→Lesson, Cohort→Course | Lesson = atomic teaching unit, Course = curriculum |
| 2026-02-10 | Remove all Divi 5 module code | Incomplete research led to antipatterns; clean rebuild needed |
| 2026-02-11 | Prerequisites at Course level, not Activity | Courses are the right abstraction for sequencing; activities are experiments within lessons |
| 2026-02-11 | All ACF relationship fields return IDs | Consistent `return_format => 'id'` across all relationship fields |
| 2026-02-12 | Server-side markdown rendering (non-streaming) | `league/commonmark` GFM converter in PHP; frontend JS stays lean for the sync path |
| 2026-02-12 | Chatbot frontend as vanilla JS IIFE | Config via `wp_localize_script()`, no React/framework dependency |
| 2026-02-16 | Slug column on all CPTs | Slug IS the filesystem identifier; primary cross-reference between curriculum registry and WordPress |
| 2026-02-16 | Context File editor: text-only, no TinyMCE | Context files are markdown/plain text; visual editor mangles whitespace and formatting |
| 2026-02-16 | SSE streaming for chatbot | `wp_remote_post` timeouts on long-running skills; streaming via `curl` + SSE keeps connection alive |
| 2026-02-16 | Keep non-streaming fallback permanently | Synchronous endpoint remains for browser compat + simplicity, not a deprecated path |
| 2026-02-17 | Client-side markdown for streaming, server-side for sync | During stream: client `marked.js`; on complete/sync: server `league/commonmark`. Both are real, non-overlapping jobs (confirmed 2026-09-17: streaming's `done` event carries no server-rendered HTML at all) |
| 2026-02-17 | Server-side + client-side retry for transient API errors | PHP retries 500/502/503/529 + curl failures (1s/3s backoff, max 2); JS mirrors this for the sync path with a "Try again" button on final failure |
| 2026-02-17 | References on Lesson, not Activity | References are reading materials that support the lesson as a teaching unit; activities are action-oriented sandbox experiments |
| 2026-02-17 | Web tools always-on with skills, never for lesson Q&A | Container sandbox has zero internet — server-side tools are the only web access path; lesson Q&A is a simple helper needing neither |
| 2026-05-03 | Migrated off Divi to builder-agnostic shortcodes | Elegant Themes' support/stability problems made Divi a poor long-term home; shortcodes work in any builder |
| 2026-05-16 | Plugin no longer renders CPT display markup | Bricks reads ACF natively via query loops/dynamic data; the chatbot widget stays a shortcode because it bundles JS/asset wiring/nonce/dual-mode detection |
| 2026-09-16 | Context Files can be cohort-scoped (`context_cohort` field on the file) | Org-specific material needs a confidentiality boundary; cohort-owned from creation, not public content later replaced |
| 2026-09-16 | Facilitators get real Context File access, but never `edit_others_`/`read_private_` | A full CPT grant would let a facilitator open any cohort's confidential file by post ID; per-post `map_meta_cap` exception grants only their own cohort's files and actively denies the rest |
| 2026-09-16 | `/learn/{cohort}/lesson/{lesson}/` as an additive, not replacing, URL | The lesson page pre-renders every activity's chatbot config in one server-side pass — cohort context must be known before that render starts |
| 2026-09-16 | **Corrected same day:** cohort is a purchase-time instance (`leaderspath_cohort` CPT), not the WC product itself | The product must be a reusable catalog offering sellable to many orgs; per-cohort fields living on the product only worked if one product = one cohort = sold once |
| 2026-09-16 | `cohort_access_closed` is a manual toggle, checked live — refund/cancellation no longer auto-revokes access | A refund issued for reasons unrelated to content access shouldn't retroactively cut off a team that already completed the material; access and billing are different questions |
| 2026-09-16 | Roster invites use stock WordPress account-creation + password-reset, not a custom invite-token system | No bespoke invite-status lifecycle to build; only the email *content* is customized, not the underlying link/token mechanism |
| 2026-09-16 | Cohort creation is one PHP method (`Enrollment::create_cohort()`), with every other caller (WS Form, WP-CLI) as a thin wrapper | Zero risk of drift in how a cohort gets created depending on which caller triggered it |
| 2026-09-16 | Real bug found by testing: `get_cohort_enrollees()`'s serialization-format mismatch silently returned 0 enrollees for every cohort | Found only because the roster build actually exercised the method against real enrollment data for the first time |
| 2026-09-16 | Cohort Org/Mixed stay real WooCommerce Simple/Variable product types, not a genuine custom product type | A custom type's `is_type()` compatibility auditing is real, ongoing work (as WooCommerce Subscriptions' own codebase demonstrates) — decided against after seeing that cost, not before. **Later superseded entirely** once WooCommerce was dropped as the purchase mechanism |
| 2026-09-16 | **WooCommerce dropped as the cohort purchase mechanism — WS Form Pro + Stripe Elements instead** | One product, custom fields, one CRM sync didn't need WC's cart/product/order machinery; `Enrollment::create_cohort()` unaffected, WS Form just became the new caller |
| 2026-09-17 | WS Form integration goes through "Run WordPress Hook" calling `Enrollment::create_cohort()` directly, not Post Manager's own field-mapping | Post Manager would silently skip owner resolution, course copying, and auto-enrollment — the canonical method stays the only creation path |
| 2026-09-17 | WS Form fields are looked up by label, not hardcoded field ID, using WS Form's own first-party function | A relabeled field breaks loudly (lookup returns nothing) rather than a stale ID silently pointing at the wrong field |
| 2026-09-17 | Cohort type (org vs. mixed) is a `leaderspath_cohort_type` taxonomy, resolved at the form level | `create_cohort()` only ever runs on the organization-purchase path; a mixed cohort is a standing post individual buyers join, not one created per purchase |
| 2026-09-17 | API Version Configuration fields are curated `<select>` dropdowns + a "Custom…" fallback, not plain text | Anthropic has no discovery endpoint for valid values; the list is curated from this plugin's own shipped history |
| 2026-09-17 | Fixed by excluding `web_search`/`web_fetch` for Haiku (not by blocking Haiku+skills at the field level) | Keeps `code_execution` available for Haiku+skills rather than disabling skills entirely for that model choice |
| 2026-09-17 | Chat file uploads go through Anthropic's Files API + `container_upload`, skills-enabled activities only | The only mechanism that works inside the code-execution container every skills-enabled activity already relies on; a skill-less activity has no container to upload into |
| 2026-09-17 | Chat-uploaded files get a short-lived ownership check + are deleted from Anthropic immediately after use | Anthropic's Files API is workspace-scoped, not per-user — closes a real, confirmed-exploitable cross-user file-access gap and shrinks retention from their standard 30 days to one request |
| 2026-09-18 | `leaderspath_cohort` becomes `publicly_queryable`, with a full standard-post-type `supports` array | A cohort needs its own public landing page reachable via a shared link; confidential fields stay protected by ACF/REST access, not by hiding the post from queries |
| 2026-09-18 | Cohort's own context files auto-inject into its learners' activity chats, with a real enrollment check before use | Manually re-attaching a cohort's files to every activity it reaches doesn't scale, and the URL-only cohort-resolution path (no per-request cohort param existed before this) needed a security boundary, not just wiring |
| 2026-09-18 | Activity's own `chatbot_context_files` picker excludes cohort-scoped files entirely, no admin/editor exception | With auto-injection live, manually picking one cohort's confidential file into a shared activity would apply it to every other cohort reaching that same activity |

---

## Quick Reference

### Test Data

| Type | IDs | Notes |
|------|-----|-------|
| Activities | 74-77 | All have chatbot enabled, various context/skills |
| Lessons | 78-79 | AI Fundamentals (3 activities), AI in Practice (1 activity) |
| Context Files | 69-71 | Ethics, Prompt Engineering, Conversation Flows |
| Skills | 72-73 | Code Review, Writing Editor |
| Course | 80 | Spring 2026, linked to Lesson 78 |

### CLI Scripts

```bash
# Full chat system test
wp eval-file wp-content/plugins/leaderspath/bin/test-chat.php [activity_id] [message]

# Show assembled system prompt
wp eval-file wp-content/plugins/leaderspath/bin/show-system-prompt.php [activity_id]

# Create test data
wp eval-file wp-content/plugins/leaderspath/bin/create-test-data.php
```

### Active REST Endpoints

| Endpoint | Method | Description |
|----------|--------|-------------|
| `/leaderspath/v1/chat` | POST | Send message — synchronous JSON response |
| `/leaderspath/v1/chat/stream` | POST | Send message — SSE streaming response |
| `/leaderspath/v1/chat/warm` | POST | Fire-and-forget prompt-cache pre-warm |
| `/leaderspath/v1/chat/upload` | POST | Upload a file for the next chat turn (skills-enabled activities only) |
| `/leaderspath/v1/context/{id}/download` | GET | Download context file content |
| `/leaderspath/v1/skills/{id}/download` | GET | Download skill package metadata |
