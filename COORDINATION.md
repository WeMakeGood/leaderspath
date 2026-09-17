# Coordination Log

Shared state for concurrent Claude Code sessions on this repo. See `~/.claude/CLAUDE.md` for the protocol. Prune closed-out entries.

---

- **2026-09-16 → 2026-09-17: cohort/commerce work spans a full session, fully logged in `docs/TASKS.md` Phase 14 — read that before touching anything cohort- or commerce-related.** This entry is a compacted pointer, not a duplicate; the full narrative (including two real bugs found by testing, all superseded-design markers, and every decision's rationale) lives there.

**Read `docs/TASKS.md` Phase 14 top to bottom before starting.** In particular, read it in order — several sections are marked `SUPERSEDED` and say so explicitly; don't build from a superseded section by mistake.

**Current state, compacted:**
- Cohort is a `leaderspath_cohort` CPT (purchase-time instance), not a WC product. `Enrollment` class (`includes/class-enrollment.php`) is the commerce-agnostic core — enrollment, roster, access chain, `create_cohort()`/`set_cohort_payment_status()`. Built, tested, working.
- Roster/invite system built (admin-side only — `admin/class-cohort-roster.php`). WC My Account self-service screen never built (deferred, then overtaken by the item below).
- WP-CLI commands exist: `wp leaderspath cohort create/invite`, `wp leaderspath post create/update/get/list`. Use these instead of `wp eval-file` scripts for any content/test-data work — see `class-cli-commands.php`.
- **Test content exists**: 3 Courses, 8 Lessons, 8 Activities (3 real + 5 placeholder), 3 Cohorts in varied states (active/paid, upcoming/pending-payment, completed/access-closed), 4 participant accounts. Built and content-filled this session — see Phase 14 for exactly what's populated.
- **CPT admin UI/UX audited**: menu order, `all_items` labels, field-group grouping (Details/Scheduling/Curriculum/Status) all standardized across all 6 CPTs.

**The live, unresolved question — read this before writing any commerce code:**

**WooCommerce has been dropped as the cohort-purchase mechanism.** The plan is now **WS Form Pro + Stripe Elements** (both already installed/licensed) instead of a WC product + checkout hooks — one cohort-package "product," custom fields (org info, seats, start date), Stripe payment, and a FluentCRM sync (`ws-form-fluentcrm`, already active) all in one WS Form form. `Enrollment::create_cohort()` is unaffected; WS Form becomes a new caller of it, same as WC would have been. Also unresolved: whether the existing `WooCommerce` class / the one real WC product (329) / its test cohorts should be removed, left dormant, or reconciled with the new plan — explicitly not decided, don't assume either way.

**2026-09-17: the integration mechanism is resolved (confirmed against real WS Form source + a real test form + a real test transaction, not just docs).** See `docs/TASKS.md` Phase 14, "Integration mechanism resolved" and "Payment-lifecycle question resolved." Short version: use WS Form's **"Run WordPress Hook" action** (`do_action($form, $submit)`, priority number set higher than Stripe Elements' own action's priority so it fires after payment succeeds) to call `Enrollment::create_cohort()` directly — **not** Post Manager's own direct-to-ACF field mapping (proven working in test form 3, but it would skip owner resolution/course copying/auto-enrollment that only `create_cohort()` does; the user confirmed Post Manager was just leftover test config, not part of the real plan). Fields are read by **label**, via WS Form's own first-party `wsf_field_get_objects($form, false, 'Label')` + `wsf_submit_get_value()` — no hardcoded field IDs, no custom label-matching code needed.

**Payment lifecycle — fully resolved, including the async caveat:** the submission's `ecommerce_status` meta reads `"completed"` once Stripe Elements succeeds, and (for this synchronous card-payment test) payment and post-creation both completed within the one submission. The earlier "some Stripe payment methods confirm asynchronously" caveat is now closed: **the buyer picks Credit Card or Purchase Order on the form itself**, and WS Form's own conditional-action logic (an IF/THEN gate on the Stripe Elements action) means Stripe only ever runs for the synchronous Credit Card path — a PO submission never triggers a charge through this form at all, so there's no webhook/async-confirmation path to build. The hook just reads which payment method was chosen: Credit Card + `ecommerce_status === 'completed'` → `payment_status: 'paid'`; Purchase Order → `payment_status: 'pending_payment'`, with PO handling (invoice, actually getting paid) entirely offline/manual — no PO-number field, no automation, admin flips `cohort_payment_status` by hand once resolved (that field/capability already exists).

**2026-09-17: the real form exists now — form 7, "LeadersPath Cohort Purchase," built and tested (order processing + FluentCRM) in a different session.** Inspected directly, not assumed. Its "Run WordPress Hook" action is already configured, waiting on the plugin: `action_hook_hook: "name_of_hook"` (a literal placeholder — the plugin picks the real tag), `do_action`, priority 190. FluentCRM field mapping is fully wired already. CC/PO branching turned out to be section-visibility toggling (not an action-level condition as earlier doc research suggested) — same practical effect, corrected in `docs/TASKS.md`. **Cohort Kind is resolved at the form level, not a field** — a separate form will be built for mixed cohorts; form 7 is org-cohort only.

**Resolved (2026-09-17): pre-cohort discovery/intake data is explicitly out of the plugin's scope.** User decided directly — WS Form/FluentCRM own it, it'll change over time, the plugin only builds the hook for the specific named fields `create_cohort()` needs. No ACF field, no schema, not the plugin's concern.

**2026-09-17: the handler is built, then fixed against real bugs found by a real first submission.** `includes/class-ws-form-integration.php` — `WS_Form_Integration` class, registered on `plugins_loaded` (same deferred pattern as `WooCommerce`). Hook tag: `leaderspath_ws_form_cohort_submitted` (still needs to replace form 7's placeholder `"name_of_hook"` in the WS Form UI — not done). The user ran a real submission (cohort #345) and reported wrong data; this surfaced a real bug (WS Form choice-type fields submit as a PHP array, not a scalar — the original `(string)` cast silently produced `"Array"`) and led to more form/schema changes than just a fix:

- **Seats**: "Cohort Package" now has 3 real columns (Label/Price/Seats) and submits the Seats number directly — no more label-regex-parsing. A missing/empty Seats value (the "More than 7 seats — Contact Us" row) is the signal to skip cohort creation.
- **Course**: form 7 gained a real "Course" field (submits a `leaderspath_course` post ID). `Enrollment::create_cohort()` gained a `course_id` arg for this, taking precedence over the older `offering_id` product-copy path.
- **Source record**: `cohort_source_order_id` → `cohort_source_record_id` (generic) + `cohort_source_url`. The URL then got reworked again per user request ("make it a button") — it's now plain post meta, not an ACF field, rendered as a real `<a class="button">` via a `message`-type ACF field (`ACF_Fields::render_cohort_source_link_button()`).
- **Cohort type**: new `leaderspath_cohort_type` taxonomy (terms `organization`/`mixed-group`) on the Cohort CPT — not an ACF field, not a taxonomy on Courses (both considered, then corrected). `create_cohort()` always tags `organization` — it only ever runs on the org-purchase path; a mixed cohort will be a standing post individual buyers *join* via a not-yet-built `add_to_cohort()`, not one created per-purchase.
- Cohorts #345/#346 (the real test transactions) were backfilled to reflect the fix — see `docs/TASKS.md` Phase 14 for exact values.
- `wp leaderspath cohort create` gained a `--course=<id>` flag to match.

Full writeup, in order, in `docs/TASKS.md` Phase 14 — several dated subsections from "Handler built" through "Source record rendered as a button."

**Still needed, next session:**
- **Form 7's "Run WordPress Hook" action still has the literal placeholder `action_hook_hook: "name_of_hook"`** — needs to be updated in the WS Form UI to `leaderspath_ws_form_cohort_submitted`. Plugin side is done; form side isn't updated.
- No live end-to-end test of the *fully corrected* handler yet — verification so far re-ran the field-reading logic directly against stored submission data (confirmed correct), not by triggering a brand-new real submission through the actual hook.
- `add_to_cohort()`, the mixed-cohort form, and however WS Form will list open mixed cohorts to join: not started, future work.
- All work this session (and prior) remains **uncommitted** — nothing has been committed to `feature/bricks-integration-phase1` yet.
