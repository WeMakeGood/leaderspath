# LeadersPath Divi 5 Module Development Guide

**Version:** 0.1.0
**Last Updated:** 2026-01-29

## Overview

LeadersPath provides custom Divi 5 modules for building interactive lesson templates. This guide documents the module architecture and development patterns.

---

## Divi 5 Module Architecture

### File Structure per Module

```
modules/
└── ModuleName/
    ├── ModuleName.php           # Main module class
    └── traits/
        ├── CustomCssTrait.php   # Custom CSS handling
        ├── ModuleClassnamesTrait.php
        ├── ModuleScriptDataTrait.php
        ├── ModuleStylesTrait.php
        └── RenderCallbackTrait.php

src/components/
└── module-name/
    ├── edit.tsx                 # Visual Builder edit component
    ├── settings-content.tsx     # Content tab settings
    ├── settings-design.tsx      # Design tab settings
    ├── settings-advanced.tsx    # Advanced tab settings
    ├── styles.tsx               # CSS-in-JS styles
    ├── types.ts                 # TypeScript interfaces
    ├── custom-css.ts            # Custom CSS definitions
    ├── module.json              # Module configuration
    └── __tests__/
        └── edit.test.tsx
```

### Module Registration

Each module extends `ET_Builder_Module` (Divi 4) or uses the new Divi 5 module API:

```php
<?php
namespace LeadersPath\Modules\Chatbot;

use ET_Builder_Module;

class Chatbot extends ET_Builder_Module {
    public $slug       = 'leaderspath_chatbot';
    public $vb_support = 'on';
    public $icon_path;

    protected $module_credits = [
        'module_uri' => 'https://leaderspath.wemakegood.org',
        'author'     => 'WeMakeGood',
        'author_uri' => 'https://wemakegood.org',
    ];

    public function init() {
        $this->name = esc_html__('LeadersPath Chatbot', 'leaderspath');
        $this->icon_path = plugin_dir_path(__FILE__) . 'icon.svg';

        $this->settings_modal_toggles = [
            'general' => [
                'toggles' => [
                    'main_content' => esc_html__('Chatbot Settings', 'leaderspath'),
                    'context'      => esc_html__('Context & Skills', 'leaderspath'),
                ],
            ],
            'advanced' => [
                'toggles' => [
                    'layout' => esc_html__('Layout', 'leaderspath'),
                ],
            ],
        ];
    }

    public function get_fields() {
        return [
            'model' => [
                'label'       => esc_html__('Claude Model', 'leaderspath'),
                'type'        => 'select',
                'options'     => [
                    'inherit' => esc_html__('Inherit from Lesson', 'leaderspath'),
                    'opus'    => esc_html__('Claude Opus 4.5', 'leaderspath'),
                    'sonnet'  => esc_html__('Claude Sonnet', 'leaderspath'),
                    'haiku'   => esc_html__('Claude Haiku', 'leaderspath'),
                ],
                'default'     => 'inherit',
                'toggle_slug' => 'main_content',
            ],
            // ... more fields
        ];
    }

    public function render($attrs, $content, $render_slug) {
        // Render frontend HTML
    }
}
```

---

## LeadersPath Modules

### 1. Chatbot Module (`leaderspath_chatbot`)

Interactive Claude-powered chat interface.

#### Settings

**Content Tab:**

| Setting | Type | Default | Description |
|---------|------|---------|-------------|
| `model` | Select | inherit | Claude model selection |
| `allow_model_switch` | Toggle | off | Let users change model |
| `system_prompt` | Textarea | (empty) | Custom system prompt override |
| `context_source` | Select | lesson | Source: lesson, manual, none |
| `context_files` | Multi-select | [] | Manual context file selection |
| `skills_source` | Select | lesson | Source: lesson, manual, none |
| `skills` | Multi-select | [] | Manual skill selection |
| `placeholder_text` | Text | "Ask a question..." | Input placeholder |
| `welcome_message` | Textarea | (empty) | Initial message shown |

**Design Tab:**

| Setting | Type | Default | Description |
|---------|------|---------|-------------|
| `chat_height` | Range | 500px | Container height |
| `message_spacing` | Range | 16px | Space between messages |
| `user_bubble_bg` | Color | #0073aa | User message background |
| `assistant_bubble_bg` | Color | #f0f0f0 | Assistant message background |
| `font_settings` | Typography | default | Message typography |
| `input_style` | Select | default | Input field style |

**Advanced Tab:**

| Setting | Type | Default | Description |
|---------|------|---------|-------------|
| `max_tokens` | Number | 4096 | Max response tokens |
| `temperature` | Range | 0.7 | Response temperature |
| `enable_logging` | Toggle | off | Log conversations |

#### Frontend Behavior

- Renders a chat container with message history and input
- Connects to `/wp-json/leaderspath/v1/chat` endpoint
- Streams responses via Server-Sent Events
- Persists conversation in session storage (optional)

#### Visual Builder Behavior

- Shows static preview with sample conversation
- Settings changes update preview in real-time
- Context/skill selections show counts

---

### 2. Context Library Module (`leaderspath_context_library`)

Displays context files for transparency.

#### Settings

**Content Tab:**

| Setting | Type | Default | Description |
|---------|------|---------|-------------|
| `source` | Select | lesson | Source: lesson, manual |
| `context_files` | Multi-select | [] | Manual selection (if source=manual) |
| `show_descriptions` | Toggle | on | Show file descriptions |
| `show_versions` | Toggle | off | Show version numbers |
| `allow_view` | Toggle | on | Allow viewing content |
| `allow_download` | Toggle | on | Allow downloading |
| `empty_message` | Text | "No context files" | Message when empty |

**Design Tab:**

| Setting | Type | Default | Description |
|---------|------|---------|-------------|
| `layout` | Select | list | Display: list, cards, accordion |
| `columns` | Range | 1 | Columns (for cards layout) |
| `card_style` | Select | default | Card appearance |
| `icon` | Icon Picker | document | File icon |

#### Frontend Output

```html
<div class="leaderspath-context-library leaderspath-context-library--list">
  <div class="context-file">
    <div class="context-file__header">
      <span class="context-file__icon">📄</span>
      <h4 class="context-file__title">Organization Profile</h4>
      <span class="context-file__version">v1.2.0</span>
    </div>
    <p class="context-file__description">Core organizational information...</p>
    <div class="context-file__actions">
      <button class="context-file__view" data-file-id="123">View</button>
      <a class="context-file__download" href="...">Download</a>
    </div>
  </div>
  <!-- More files... -->
</div>
```

---

### 3. Skills List Module (`leaderspath_skills_list`)

Displays available skills for transparency.

#### Settings

**Content Tab:**

| Setting | Type | Default | Description |
|---------|------|---------|-------------|
| `source` | Select | lesson | Source: lesson, manual |
| `skills` | Multi-select | [] | Manual selection |
| `show_descriptions` | Toggle | on | Show skill descriptions |
| `show_parameters` | Toggle | off | Show input parameters |
| `allow_view` | Toggle | on | Allow viewing definition |
| `allow_download` | Toggle | on | Allow downloading |

**Design Tab:**

| Setting | Type | Default | Description |
|---------|------|---------|-------------|
| `layout` | Select | list | Display: list, cards |
| `columns` | Range | 1 | Columns (for cards) |
| `icon` | Icon Picker | tools | Skill icon |

---

### 4. Lesson Meta Module (`leaderspath_lesson_meta`)

Displays lesson metadata (duration, objectives, etc.).

#### Settings

**Content Tab:**

| Setting | Type | Default | Description |
|---------|------|---------|-------------|
| `show_duration` | Toggle | on | Show duration |
| `show_objectives` | Toggle | on | Show learning objectives |
| `show_model` | Toggle | on | Show Claude model |
| `show_prerequisites` | Toggle | off | Show prerequisite lessons |
| `duration_format` | Select | minutes | Format: minutes, hours, human |
| `objectives_style` | Select | list | Style: list, numbered, badges |

**Design Tab:**

| Setting | Type | Default | Description |
|---------|------|---------|-------------|
| `layout` | Select | stacked | Layout: stacked, inline, grid |
| `label_position` | Select | above | Label position: above, inline, hidden |
| `icon_style` | Select | default | Icon style |

#### Frontend Output

```html
<div class="leaderspath-lesson-meta leaderspath-lesson-meta--stacked">
  <div class="lesson-meta__item lesson-meta__duration">
    <span class="lesson-meta__icon">⏱️</span>
    <span class="lesson-meta__label">Duration</span>
    <span class="lesson-meta__value">45 minutes</span>
  </div>
  <div class="lesson-meta__item lesson-meta__objectives">
    <span class="lesson-meta__icon">🎯</span>
    <span class="lesson-meta__label">Learning Objectives</span>
    <ul class="lesson-meta__value">
      <li>Understand context-enhanced AI</li>
      <li>Build a basic context library</li>
    </ul>
  </div>
  <div class="lesson-meta__item lesson-meta__model">
    <span class="lesson-meta__icon">🤖</span>
    <span class="lesson-meta__label">AI Model</span>
    <span class="lesson-meta__value">Claude Sonnet</span>
  </div>
</div>
```

---

## Development Workflow

### Setup

```bash
# Install dependencies
cd /path/to/leaderspath
composer install
npm install

# Start development server (watches for changes)
npm run start
```

### Building for Production

```bash
npm run build
```

### Creating a New Module

1. **Create PHP class** in `modules/ModuleName/ModuleName.php`
2. **Create React component** in `src/components/module-name/`
3. **Add module.json** configuration
4. **Register module** in main plugin file
5. **Add to autoloader** in `composer.json`

### Testing

```bash
# Run JavaScript tests
npm run test

# Run PHP tests
./vendor/bin/phpunit
```

---

## Divi 5 API Reference

### Key Classes

- `ET_Builder_Module` - Base module class
- `ET_Builder_Element` - Lower-level element class
- `ET_Core_Data_Utils` - Utility functions

### Key Hooks

```php
// Before module renders
add_filter('et_builder_module_shortcode_output', 'modify_output', 10, 3);

// Add custom module categories
add_filter('et_builder_module_categories', 'add_categories');

// Modify module defaults
add_filter('et_pb_all_fields_unprocessed_{slug}', 'modify_fields');
```

### Asset Registration

```php
// Enqueue frontend assets
public function get_bundle_scripts() {
    return [
        [
            'type'   => 'script',
            'name'   => 'leaderspath-chatbot',
            'path'   => plugin_dir_url(__FILE__) . 'assets/js/chatbot.js',
            'deps'   => ['jquery'],
        ],
    ];
}

public function get_bundle_styles() {
    return [
        [
            'type' => 'style',
            'name' => 'leaderspath-chatbot',
            'path' => plugin_dir_url(__FILE__) . 'assets/css/chatbot.css',
        ],
    ];
}
```

---

## Resources

- [Divi 5 Extension Example Modules](https://github.com/elegantthemes/d5-extension-example-modules)
- [Divi 5 Core Module Examples](https://github.com/elegantthemes/d5-example-core-modules)
- [Elegant Themes Developer Documentation](https://www.elegantthemes.com/documentation/developers/)
- [Create Divi Extension CLI](https://www.elegantthemes.com/documentation/developers/divi-module/how-to-create-a-divi-builder-module/)
