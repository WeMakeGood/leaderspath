# LeadersPath WordPress Plugin

---

## Session Bootstrap (READ FIRST)

**Every new Claude Code session MUST:**

1. **Read the task list:** `docs/TASKS.md` - Check current phase and pending tasks
2. **Understand the scope:** Only work on tasks assigned to this session
3. **Update task status:** Mark tasks in progress/completed as you work
4. **Log decisions:** Add significant decisions to the Decisions Log in TASKS.md
5. **Add session notes:** Summarize what was done at the end of the session

**Before writing any code:**
- Confirm which specific task(s) you're working on
- Check if there are blocking dependencies
- Review relevant documentation in `docs/`

**After completing work:**
- Run `npm run build` to verify the build succeeds
- Update TASKS.md with completed items
- Note any discovered tasks or blockers

---

## Project Overview

LeadersPath is a WordPress plugin developed by WeMakeGood that powers an interactive AI learning community. The plugin provides chatbot-powered lesson experiences where learners interact with Claude AI to understand the difference between raw LLM interactions and context-enhanced AI implementations.

**Key Features:**
- Custom post types for Lessons, Courses, Cohorts, Context Files, and Skills
- Claude API integration for interactive chatbot experiences
- Divi 5 modules for flexible lesson template design
- Transparency features showing learners the context and skills used by AI
- Role-based access control for content and features

## Directory Structure

```
leaderspath/
├── leaderspath.php          # Main plugin file, bootstrap
├── composer.json            # PHP dependencies
├── package.json             # Node dependencies (Divi modules)
│
├── includes/                # Core PHP classes
│   ├── class-post-types.php
│   ├── class-taxonomies.php
│   ├── class-capabilities.php
│   ├── class-acf-fields.php
│   ├── class-claude-api.php
│   ├── class-rest-api.php
│   └── class-skill-processor.php
│
├── modules/                 # Divi 5 modules (PHP)
│   ├── Chatbot/
│   ├── ContextLibrary/
│   ├── SkillsList/
│   └── LessonMeta/
│
├── src/                     # Divi 5 modules (TypeScript/React)
│   └── components/
│
├── admin/                   # Admin functionality
├── assets/                  # Frontend CSS/JS
├── templates/               # Template overrides
├── languages/               # Translation files
├── docs/                    # Documentation
├── tests/                   # PHPUnit tests
├── bin/                     # Build scripts
└── .circleci/               # CI configuration
```

## Development Commands

```bash
# WP CLI - run from anywhere in the WP install
wp plugin list
wp plugin activate leaderspath
wp plugin deactivate leaderspath

# PHP dependencies
composer install
composer dump-autoload

# Node dependencies (for Divi modules)
npm install
npm run start          # Development with watch
npm run build          # Production build
npm run test           # Run JS tests

# PHP tests
./bin/install-wp-tests.sh <db-name> <db-user> <db-pass> [db-host] [wp-version]
./vendor/bin/phpunit

# Linting
./vendor/bin/phpcs     # Check coding standards
./vendor/bin/phpcbf    # Auto-fix coding standards
```

## Documentation

All documentation lives in the `docs/` folder:

| Document | Purpose |
|----------|---------|
| [plugin-design.md](docs/plugin-design.md) | Architecture overview, design decisions |
| [cpt-schema.md](docs/cpt-schema.md) | Custom post types, taxonomies, ACF fields |
| [divi-modules.md](docs/divi-modules.md) | Divi 5 module development guide |
| [claude-api-integration.md](docs/claude-api-integration.md) | **Claude API integration (CRITICAL)** |
| [api-reference.md](docs/api-reference.md) | REST API endpoints (TODO) |

## External Dependencies

**Required:**
- PHP 8.2+ (targeting 8.3)
- WordPress 6.4+
- Advanced Custom Fields Pro
- Divi 5

**Optional:**
- WooCommerce (for e-commerce/user roles)

---

# Development Guidelines

## Coding Standards

### PHP

- Follow [WordPress Coding Standards](https://developer.wordpress.org/coding-standards/wordpress-coding-standards/php/)
- PHPCS configured in `.phpcs.xml.dist`
- **Prefix everything:** All functions, classes, hooks, and global variables use `leaderspath_` prefix
- **Namespace:** Use `LeadersPath\` namespace for classes
- **Type hints:** Use PHP 8+ type hints for parameters and return types
- **Strict types:** Declare `strict_types=1` in all PHP files

```php
<?php
declare(strict_types=1);

namespace LeadersPath\Includes;

class PostTypes {
    public function __construct() {
        add_action('init', [$this, 'register_post_types']);
    }

    public function register_post_types(): void {
        // Implementation
    }
}
```

### JavaScript/TypeScript

- Use TypeScript for Divi 5 module components
- Follow WordPress JavaScript coding standards where applicable
- Use ES6+ features
- Prefer functional components with hooks for React

### CSS

- Use BEM naming convention: `.block__element--modifier`
- Prefix all classes with `leaderspath-`
- Use CSS custom properties for theming
- Mobile-first responsive design

```css
.leaderspath-chatbot {
    --chatbot-height: 500px;
    --chatbot-user-bg: #0073aa;
}

.leaderspath-chatbot__message {
    padding: 1rem;
}

.leaderspath-chatbot__message--user {
    background: var(--chatbot-user-bg);
}
```

## File Naming Conventions

| Type | Convention | Example |
|------|------------|---------|
| PHP Classes | `class-{name}.php` | `class-post-types.php` |
| PHP Traits | `trait-{name}.php` | `trait-render-callback.php` |
| React Components | `{name}.tsx` | `edit.tsx` |
| CSS | `{feature}.css` | `chatbot.css` |
| Tests | `test-{name}.php` | `test-post-types.php` |

## Git Workflow

### Branch Naming

- `main` - Production-ready code
- `develop` - Integration branch
- `feature/{ticket}-{description}` - New features
- `fix/{ticket}-{description}` - Bug fixes
- `docs/{description}` - Documentation updates

### Commit Messages

Use conventional commits:

```
type(scope): description

[optional body]

[optional footer]
```

Types: `feat`, `fix`, `docs`, `style`, `refactor`, `test`, `chore`

Examples:
```
feat(chatbot): add streaming response support
fix(api): handle rate limit errors gracefully
docs(readme): update installation instructions
```

## Security Requirements

1. **Sanitize all input:** Use `sanitize_text_field()`, `wp_kses()`, etc.
2. **Escape all output:** Use `esc_html()`, `esc_attr()`, `wp_kses_post()`
3. **Verify nonces:** All form submissions and AJAX requests
4. **Check capabilities:** Before any privileged operation
5. **Validate data types:** Use strict type checking
6. **Never trust user input:** Including from logged-in users

```php
// Example: Secure AJAX handler
public function handle_chat_request(): void {
    // Verify nonce
    if (!wp_verify_nonce($_POST['nonce'] ?? '', 'leaderspath_chat')) {
        wp_send_json_error('Invalid nonce', 403);
    }

    // Check capability
    if (!current_user_can('leaderspath_access_chatbot')) {
        wp_send_json_error('Unauthorized', 401);
    }

    // Sanitize input
    $message = sanitize_textarea_field($_POST['message'] ?? '');

    // Process and escape output
    $response = $this->process_message($message);
    wp_send_json_success(esc_html($response));
}
```

## Performance Guidelines

1. **Lazy load assets:** Only enqueue JS/CSS when module is present
2. **Cache expensive operations:** Use transients for API responses
3. **Optimize queries:** Use proper indexes, avoid N+1 queries
4. **Stream large responses:** Use SSE for chatbot responses
5. **Minimize dependencies:** Don't add libraries for single functions

## Testing Requirements

- Write tests for all public methods
- Minimum 80% code coverage for critical paths
- Test edge cases and error conditions
- Mock external services (Claude API)

```php
class Test_API_Handler extends WP_UnitTestCase {
    public function test_send_message_validates_input(): void {
        $handler = new API_Handler();

        $this->expectException(InvalidArgumentException::class);
        $handler->send_message('');
    }
}
```

---

# Decision-Making Framework

## When to Create New Files

Create a new file when:
- Adding a new class (one class per file)
- Adding a new Divi module
- Adding a new REST endpoint that warrants its own controller
- Adding significant new functionality that doesn't fit existing files

Don't create new files for:
- Small helper functions (add to existing utilities)
- Minor extensions to existing features
- Configuration that belongs in existing config files

## When to Use ACF vs Custom Meta

**Use ACF for:**
- Content editor-facing fields
- Complex field types (repeaters, relationships, flexible content)
- Fields that benefit from ACF's UI

**Use custom post meta for:**
- Simple programmatic data
- Performance-critical lookups
- Data that editors don't need to see

## When to Add Dependencies

Before adding a new dependency, ask:
1. Can this be done with WordPress core functions?
2. Can this be done with existing dependencies?
3. Is this actively maintained?
4. What's the size/performance impact?
5. Does this introduce security concerns?

## API Design Decisions

- Use REST API for public endpoints
- Use admin-ajax only for backward compatibility
- Version all APIs (`/v1/`, `/v2/`)
- Follow WordPress REST API conventions
- Return consistent response structures

---

# Documentation Standards

## Code Documentation

### PHP DocBlocks

Every class, method, and complex function needs documentation:

```php
/**
 * Handles communication with the Claude API.
 *
 * @since 1.0.0
 * @package LeadersPath
 */
class API_Handler {
    /**
     * Send a message to the Claude API.
     *
     * @since 1.0.0
     *
     * @param string $message     The user's message.
     * @param array  $context     Optional. Context files to include.
     * @param string $model       Optional. Claude model to use.
     * @return array {
     *     Response data from Claude.
     *
     *     @type string $content    The response text.
     *     @type int    $tokens     Tokens used.
     *     @type string $model      Model that responded.
     * }
     * @throws API_Exception If the API request fails.
     */
    public function send_message(
        string $message,
        array $context = [],
        string $model = 'sonnet'
    ): array {
        // Implementation
    }
}
```

### Inline Comments

- Explain *why*, not *what*
- Comment complex algorithms
- Note workarounds and their reasons
- Reference tickets for non-obvious fixes

```php
// Rate limiting: Claude API allows 60 requests/minute per key.
// We track per-user to allow concurrent users while preventing abuse.
$rate_key = 'leaderspath_rate_' . get_current_user_id();
```

## Markdown Documentation

- Use ATX-style headers (`#`, `##`, `###`)
- Include a table of contents for long documents
- Use code blocks with language hints
- Keep line length reasonable (~100 chars)
- Update "Last Updated" date when modifying

## Changelog

Maintain `CHANGELOG.md` using [Keep a Changelog](https://keepachangelog.com/) format:

```markdown
## [Unreleased]

### Added
- New chatbot module for Divi 5

### Changed
- Updated Claude API to use streaming responses

### Fixed
- Context files now properly escape special characters
```

---

# Quick Reference

## Prefixes

| Type | Prefix |
|------|--------|
| Functions | `leaderspath_` |
| Classes | `LeadersPath\` (namespace) |
| Hooks (actions/filters) | `leaderspath_` |
| Post Types | `leaderspath_` |
| Taxonomies | `leaderspath_` |
| Options | `leaderspath_` |
| Transients | `leaderspath_` |
| CSS Classes | `leaderspath-` |
| JS Globals | `LeadersPath` |

## Key Files

| Purpose | File |
|---------|------|
| Plugin bootstrap | `leaderspath.php` |
| Main class | `includes/class-leaderspath.php` |
| Post types | `includes/class-post-types.php` |
| Claude API | `includes/class-api-handler.php` |
| REST endpoints | `includes/class-rest-api.php` |
| Admin settings | `admin/class-settings.php` |

## Useful Hooks

```php
// After plugin loads
do_action('leaderspath_loaded');

// Before chatbot renders
apply_filters('leaderspath_chatbot_system_prompt', $prompt, $activity_id);

// After chat message sent
do_action('leaderspath_chat_message_sent', $message, $response, $user_id);

// Filter context files for an activity
apply_filters('leaderspath_activity_context_files', $files, $activity_id);
```

---

# Current Project State

## Completed Work

- **Divi 5 Integration:** Proven working with Hello Module test case
- **Build System:** Webpack + TypeScript configured and functional
- **Documentation:** Plugin design, CPT schema, Divi module guide complete

## In Progress

See `docs/TASKS.md` for current task list.

## Key Architectural Decisions

| Decision | Details |
|----------|---------|
| **Context Files** | WordPress-only storage (post_content), embedded in system prompt |
| **Skills** | Uploaded to Anthropic Skills API for code execution, referenced by skill_id |
| **Claude API** | Container API with code execution enabled (not simple Messages API) |
| **Conversation Persistence** | None - page reload clears conversation to enable experimentation |
| **API Key** | Single plugin-wide key stored in WordPress options |
| **Rate Limiting** | None initially - paid service, add later if abuse occurs |
| **Chat UI** | Standard bubble layout (user right, assistant left), configurable colors |
| **Error Display** | Inline in chat conversation |
| **Streaming** | Optional enhancement, not required for MVP |

## Claude API Architecture (CRITICAL)

**Read `docs/claude-api-integration.md` before modifying API code.**

### Context Files vs Skills

| Aspect | Context Files | Skills |
|--------|---------------|--------|
| **Purpose** | Reference material (guidelines, standards) | Executable capabilities (scripts, workflows) |
| **Storage** | WordPress post_content only | Anthropic Skills API + WordPress metadata |
| **Delivery** | Embedded in system prompt | Via `container.skills` array |
| **Code execution** | No | Yes (Python, bash) |
| **API upload** | Not required | Required for code execution |

### Required API Features

```
Beta Headers:
- code-execution-2025-08-25
- skills-2025-10-02
- files-api-2025-04-14 (for file handling)

Tools:
- code_execution_20250825

Container:
- skills: [{type, skill_id, version}, ...]
- id: reuse for session continuity
```

### Skill Lifecycle

1. **WordPress upload** → ZIP validated locally, frontmatter parsed
2. **Anthropic upload** → `POST /v1/skills` returns `skill_id`
3. **WordPress stores** → `skill_anthropic_id`, `skill_anthropic_version`
4. **Chat request** → skill_id included in `container.skills` array
5. **Update** → New ZIP creates version via `POST /v1/skills/{id}/versions`

## Module Registration Pattern

**PHP Side** (in `modules/Modules.php`):
```php
add_action('divi_module_library_modules_dependency_tree', function($dependency_tree) {
    $dependency_tree->add_dependency(new YourModule());
});
```

**JavaScript Side** (in `src/index.ts`):
```typescript
addAction('divi.moduleLibrary.registerModuleLibraryStore.after', 'leaderspath', () => {
    registerModule(yourModule.metadata, omit(yourModule, 'metadata'));
});
```

## File Locations for New Modules

| Component | Location |
|-----------|----------|
| PHP Module Class | `modules/{ModuleName}/{ModuleName}.php` |
| PHP Traits | `modules/{ModuleName}/{ModuleName}Trait/*.php` |
| React Component | `src/components/{module-name}/edit.tsx` |
| Module Schema | `src/components/{module-name}/module.json` |
| Styles | `src/components/{module-name}/style.scss` |

## Build Output

| File | Purpose |
|------|---------|
| `scripts/bundle.js` | Visual Builder JavaScript |
| `styles/bundle.css` | Frontend + VB styles |
| `modules-json/{module-name}/module.json` | Module metadata for PHP registration |
