# LeadersPath Divi 5 Module Development Guide

**Version:** 0.1.0
**Last Updated:** 2026-01-29

## Overview

LeadersPath provides custom Divi 5 modules for building interactive lesson templates. This guide documents the module architecture and development patterns based on the working implementation.

**Working Reference:** The `HelloModule` and `LessonMeta` modules demonstrate all patterns described here.

---

## Divi 5 Module Architecture

Divi 5 modules have two sides:
1. **PHP Side** - Server-side rendering for frontend, module registration
2. **TypeScript/React Side** - Visual Builder UI, edit components, module schema

### File Structure per Module

```
modules/
└── ModuleName/
    ├── ModuleName.php                    # Main module class
    └── ModuleNameTrait/
        ├── RenderCallbackTrait.php       # Server-side HTML rendering
        ├── ModuleClassnamesTrait.php     # CSS class generation
        └── ModuleStylesTrait.php         # Style compilation

src/components/
└── module-name/
    ├── index.ts                          # Module export & registration
    ├── edit.tsx                          # Visual Builder edit component
    ├── types.ts                          # TypeScript interfaces
    ├── styles.tsx                        # VB styles component
    ├── module-classnames.ts              # Classnames generator
    ├── placeholder-content.ts            # Default values for new instances
    ├── module.json                       # Module schema (attributes, settings)
    └── style.scss                        # Module CSS (BEM convention)
```

### Build Output

After `npm run build`:
```
scripts/bundle.js                         # All module JavaScript
styles/bundle.css                         # All module CSS
modules-json/{module-name}/module.json    # Compiled metadata for PHP
```

---

## PHP Side Implementation

### Main Module Class

```php
<?php
namespace LeadersPath\Modules\ModuleName;

use ET\Builder\Framework\DependencyManagement\Interfaces\DependencyInterface;
use ET\Builder\Packages\ModuleLibrary\ModuleRegistration;

class ModuleName implements DependencyInterface {
    use ModuleNameTrait\RenderCallbackTrait;
    use ModuleNameTrait\ModuleClassnamesTrait;
    use ModuleNameTrait\ModuleStylesTrait;

    public function load(): void {
        $module_json_folder_path = LEADERSPATH_MODULES_JSON_PATH . 'module-name/';

        add_action(
            'init',
            function () use ( $module_json_folder_path ) {
                ModuleRegistration::register_module(
                    $module_json_folder_path,
                    [
                        'render_callback' => [ self::class, 'render_callback' ],
                    ]
                );
            }
        );
    }
}
```

### RenderCallbackTrait

Server-side rendering for frontend. **Critical:** Use `get_queried_object_id()` for Theme Builder templates.

```php
<?php
namespace LeadersPath\Modules\ModuleName\ModuleNameTrait;

use ET\Builder\Packages\Module\Module;
use ET\Builder\Framework\Utility\HTMLUtility;
use ET\Builder\FrontEnd\BlockParser\BlockParserStore;
use ET\Builder\Packages\Module\Options\Element\ElementComponents;
use LeadersPath\Modules\ModuleName\ModuleName;

trait RenderCallbackTrait {
    public static function render_callback( $attrs, $content, $block, $elements ): string {
        // IMPORTANT: Use get_queried_object_id() for Theme Builder templates
        $post_id = get_queried_object_id();
        if ( ! $post_id ) {
            $post_id = get_the_ID();
        }

        // Render elements defined in module.json
        $title = $elements->render([
            'attrName' => 'title',
        ]);

        // Get attribute values
        $show_something = $attrs['showSomething']['innerContent']['desktop']['value'] ?? 'on';

        // Build HTML using HTMLUtility
        $content_html = HTMLUtility::render([
            'tag'               => 'div',
            'attributes'        => ['class' => 'module-name__content'],
            'childrenSanitizer' => 'et_core_esc_previously',
            'children'          => $title,
        ]);

        // Get parent context
        $parent       = BlockParserStore::get_parent( $block->parsed_block['id'], $block->parsed_block['storeInstance'] );
        $parent_attrs = $parent->attrs ?? [];

        // Return wrapped module
        return Module::render([
            'orderIndex'         => $block->parsed_block['orderIndex'],
            'storeInstance'      => $block->parsed_block['storeInstance'],
            'attrs'              => $attrs,
            'elements'           => $elements,
            'id'                 => $block->parsed_block['id'],
            'name'               => $block->block_type->name,
            'moduleCategory'     => $block->block_type->category,
            'classnamesFunction' => [ ModuleName::class, 'module_classnames' ],
            'stylesComponent'    => [ ModuleName::class, 'module_styles' ],
            'parentAttrs'        => $parent_attrs,
            'parentId'           => $parent->id ?? '',
            'parentName'         => $parent->blockName ?? '',
            'children'           => [
                ElementComponents::component([
                    'attrs'         => $attrs['module']['decoration'] ?? [],
                    'id'            => $block->parsed_block['id'],
                    'orderIndex'    => $block->parsed_block['orderIndex'],
                    'storeInstance' => $block->parsed_block['storeInstance'],
                ]),
                $content_html,
            ],
        ]);
    }
}
```

### ModuleClassnamesTrait

```php
<?php
namespace LeadersPath\Modules\ModuleName\ModuleNameTrait;

use ET\Builder\Packages\Module\Options\Element\ElementClassnames;
use ET\Builder\Packages\Module\Options\Text\TextClassnames;

trait ModuleClassnamesTrait {
    public static function module_classnames( array $args ): void {
        $classnames_instance = $args['classnamesInstance'];
        $attrs               = $args['attrs'];

        $classnames_instance->add(
            TextClassnames::text_options_classnames( $attrs['module']['advanced']['text'] ?? [] ),
            true
        );

        $classnames_instance->add(
            ElementClassnames::classnames([
                'attrs' => array_merge(
                    $attrs['module']['decoration'] ?? [],
                    ['link' => $attrs['module']['advanced']['link'] ?? []]
                ),
            ])
        );
    }
}
```

### ModuleStylesTrait

```php
<?php
namespace LeadersPath\Modules\ModuleName\ModuleNameTrait;

use ET\Builder\Packages\Module\Options\Css\CssStyle;

trait ModuleStylesTrait {
    public static function module_styles( array $args ): void {
        $attrs    = $args['attrs'] ?? [];
        $elements = $args['elements'];

        $elements->style([
            'attrName'   => 'module',
            'styleProps' => [
                'disabledOn' => [
                    'disabledModuleVisibility' => $args['settings']['disabledModuleVisibility'] ?? null,
                ],
            ],
        ]);

        $elements->style(['attrName' => 'title']);

        // IMPORTANT: Use null coalescing for selector
        CssStyle::style([
            'selector'  => $args['selector'] ?? '',
            'attr'      => $attrs['css'] ?? [],
            'cssFields' => $args['cssFields'] ?? [],
        ]);
    }
}
```

### Module Registration (modules/Modules.php)

```php
<?php
namespace LeadersPath\Modules;

use LeadersPath\Modules\HelloModule\HelloModule;
use LeadersPath\Modules\LessonMeta\LessonMeta;

add_action(
    'divi_module_library_modules_dependency_tree',
    function ( $dependency_tree ) {
        $dependency_tree->add_dependency( new HelloModule() );
        $dependency_tree->add_dependency( new LessonMeta() );
    }
);
```

---

## TypeScript/React Side Implementation

### module.json Schema

Defines module metadata, attributes, and settings panel organization.

```json
{
  "name": "leaderspath/module-name",
  "title": "Module Name",
  "titles": "Module Names",
  "moduleIcon": "divi/module",
  "moduleClassName": "leaderspath-module-name",
  "moduleOrderClassName": "leaderspath-module-name",
  "category": "module",
  "attributes": {
    "module": {
      "type": "object",
      "selector": "{{selector}}",
      "settings": {
        "meta": { "adminLabel": {} },
        "advanced": { "link": {}, "text": {}, "htmlAttributes": {} },
        "decoration": {
          "background": {}, "sizing": {}, "spacing": {}, "border": {},
          "boxShadow": {}, "filters": {}, "transform": {}, "animation": {},
          "overflow": {}, "disabledOn": {}, "transition": {}, "position": {},
          "zIndex": {}, "scroll": {}, "sticky": {}
        }
      }
    },
    "title": {
      "type": "object",
      "selector": "{{selector}} .leaderspath-module-name__title",
      "tagName": "h3",
      "inlineEditor": "plainText",
      "elementType": "heading",
      "settings": {
        "innerContent": {
          "groupType": "group-item",
          "item": {
            "groupSlug": "contentMain",
            "priority": 10,
            "render": true,
            "attrName": "title.innerContent",
            "label": "Title",
            "component": { "name": "divi/text", "type": "field" }
          }
        },
        "decoration": {
          "font": {
            "component": {
              "props": { "groupLabel": "Title Text", "fieldLabel": "Title" }
            }
          }
        }
      }
    },
    "showSomething": {
      "type": "object",
      "default": { "innerContent": { "desktop": { "value": "on" } } },
      "settings": {
        "innerContent": {
          "groupType": "group-item",
          "item": {
            "groupSlug": "contentElements",
            "priority": 10,
            "render": true,
            "attrName": "showSomething.innerContent",
            "label": "Show Something",
            "features": { "sticky": false, "responsive": false, "hover": false },
            "component": { "name": "divi/toggle", "type": "field" }
          }
        }
      }
    }
  },
  "settings": {
    "content": "auto",
    "design": "auto",
    "advanced": "auto",
    "groups": {
      "contentMain": {
        "panel": "content",
        "priority": 10,
        "groupName": "contentMain",
        "component": { "name": "divi/composite", "props": { "groupLabel": "Content" } }
      },
      "contentElements": {
        "panel": "content",
        "priority": 20,
        "groupName": "contentElements",
        "component": { "name": "divi/composite", "props": { "groupLabel": "Elements" } }
      }
    }
  }
}
```

**Key Concepts:**
- `groupSlug` determines which panel group a field appears in
- `panel: "content"` = Content tab, `panel: "design"` = Design tab
- Toggle controls use `"component": { "name": "divi/toggle" }`
- Text fields use `"component": { "name": "divi/text" }`

### types.ts

```typescript
import { type ModuleLibrary } from '@divi/types';

export interface ToggleAttribute {
  innerContent?: { desktop?: { value?: string } };
}

export interface ModuleNameAttrs {
  module: ModuleLibrary.Module.Attributes.Module;
  title: ModuleLibrary.Module.Attributes.Element;
  showSomething: ToggleAttribute;
}

export interface ModuleNameEditProps
  extends ModuleLibrary.Module.Edit.ComponentProps<ModuleNameAttrs> {}
```

### edit.tsx

```typescript
import React, { ReactElement } from 'react';
import { ModuleContainer } from '@divi/module';
import { ModuleNameEditProps } from './types';
import { ModuleStyles } from './styles';
import { moduleClassnames } from './module-classnames';

export const ModuleNameEdit = (props: ModuleNameEditProps): ReactElement => {
  const { attrs, elements, id, name } = props;

  const showSomething = attrs?.showSomething?.innerContent?.desktop?.value ?? 'on';

  return (
    <ModuleContainer
      attrs={attrs}
      elements={elements}
      id={id}
      name={name}
      stylesComponent={ModuleStyles}
      classnamesFunction={moduleClassnames}
    >
      {elements.styleComponents({ attrName: 'module' })}
      <div className="leaderspath-module-name__content">
        {elements.render({ attrName: 'title' })}
        {showSomething === 'on' && (
          <div className="leaderspath-module-name__section">
            Placeholder content for VB preview
          </div>
        )}
      </div>
    </ModuleContainer>
  );
};
```

### styles.tsx

```typescript
import React, { ReactElement } from 'react';
import { type ModuleLibrary } from '@divi/types';
import { CssStyle } from '@divi/module';
import { ModuleNameAttrs } from './types';

export const ModuleStyles = ({
  attrs, elements, settings, selector, cssFields,
}: ModuleLibrary.Module.Styles.Args<ModuleNameAttrs>): ReactElement => {
  return (
    <>
      {elements.style({
        attrName: 'module',
        styleProps: {
          disabledOn: { disabledModuleVisibility: settings?.disabledModuleVisibility },
        },
      })}
      {elements.style({ attrName: 'title' })}
      <CssStyle selector={selector} attr={attrs?.css} cssFields={cssFields} />
    </>
  );
};
```

### index.ts

```typescript
import { type Metadata, type ModuleLibrary } from '@divi/types';
import metadata from './module.json';
import { ModuleNameEdit } from './edit';
import { ModuleNameAttrs } from './types';
import { placeholderContent } from './placeholder-content';
import './style.scss';

export const moduleNameModule: ModuleLibrary.Module.RegisterDefinition<ModuleNameAttrs> = {
  metadata: metadata as Metadata.Values<ModuleNameAttrs>,
  placeholderContent,
  renderers: { edit: ModuleNameEdit },
};
```

### Module Registration (src/index.ts)

```typescript
import { omit } from 'lodash';
import { addAction } from '@wordpress/hooks';
import { registerModule } from '@divi/module-library';
import { moduleNameModule } from './components/module-name';

addAction(
  'divi.moduleLibrary.registerModuleLibraryStore.after',
  'leaderspath',
  () => {
    registerModule(moduleNameModule.metadata, omit(moduleNameModule, 'metadata'));
  }
);
```

---

## Development Workflow

### Commands

```bash
npm install          # Install dependencies
npm run start        # Development with watch
npm run build        # Production build
```

### Creating a New Module

1. **Create PHP files:**
   - `modules/ModuleName/ModuleName.php`
   - `modules/ModuleName/ModuleNameTrait/RenderCallbackTrait.php`
   - `modules/ModuleName/ModuleNameTrait/ModuleClassnamesTrait.php`
   - `modules/ModuleName/ModuleNameTrait/ModuleStylesTrait.php`

2. **Create TypeScript files:**
   - `src/components/module-name/module.json`
   - `src/components/module-name/types.ts`
   - `src/components/module-name/edit.tsx`
   - `src/components/module-name/styles.tsx`
   - `src/components/module-name/module-classnames.ts`
   - `src/components/module-name/placeholder-content.ts`
   - `src/components/module-name/index.ts`
   - `src/components/module-name/style.scss`

3. **Register the module:**
   - Add `use` statement and `add_dependency()` in `modules/Modules.php`
   - Add import and `registerModule()` in `src/index.ts`

4. **Build:** `npm run build`

### Common Gotchas

1. **Theme Builder templates:** Use `get_queried_object_id()` not `get_the_ID()`
2. **Null checks:** Always use `?? ''` for `$args['selector']` in styles trait
3. **Toggle values:** Toggles return `'on'` or `'off'` strings, not booleans
4. **Attribute access:** Use path like `$attrs['name']['innerContent']['desktop']['value']`

---

## Naming Conventions

| Type | Convention | Example |
|------|-----------|---------|
| PHP Module Class | PascalCase | `LessonMeta` |
| PHP Trait Folder | `{ClassName}Trait/` | `LessonMetaTrait/` |
| Module JSON name | kebab-case with namespace | `leaderspath/lesson-meta` |
| Module CSS class | kebab-case with prefix | `leaderspath-lesson-meta` |
| CSS BEM elements | double underscore | `leaderspath-lesson-meta__title` |
| React Component | PascalCase | `LessonMetaEdit` |
| React folder | kebab-case | `lesson-meta/` |
| ACF field names | snake_case | `lesson_duration` |

---

## Resources

- [Divi 5 Extension Example](https://github.com/elegantthemes/d5-extension-example-modules) - `/tmp/d5-extension-example` (downloaded)
- Working examples in this repo: `modules/HelloModule/`, `modules/LessonMeta/`
