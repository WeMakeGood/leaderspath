/**
 * Lesson Objectives Module TypeScript type definitions.
 *
 * @package LeadersPath
 * @since 0.1.0
 */

import { ModuleEditProps } from '@divi/module-library';
import {
  FormatBreakpointStateAttr,
  InternalAttrs,
  type Element,
} from '@divi/types';

/**
 * Custom CSS attribute interface for Lesson Objectives.
 *
 * @since 0.1.0
 */
export interface LessonObjectivesCssAttr {
  [key: string]: string | undefined;
  content?: string;
  title?: string;
  list?: string;
  item?: string;
  empty?: string;
}

export type LessonObjectivesCssGroupAttr = FormatBreakpointStateAttr<LessonObjectivesCssAttr>;

/**
 * Lesson Objectives Module attributes interface.
 *
 * @since 0.1.0
 */
export interface LessonObjectivesAttrs extends InternalAttrs {
  // CSS options.
  css?: LessonObjectivesCssGroupAttr;

  // Module container.
  module?: {
    meta?: Element.Meta.Attributes;
    advanced?: {
      link?: Element.Advanced.Link.Attributes;
      htmlAttributes?: Element.Advanced.IdClasses.Attributes;
      text?: Element.Advanced.Text.Attributes;
    };
    decoration?: Element.Decoration.PickedAttributes<
      'animation' |
      'background' |
      'border' |
      'boxShadow' |
      'disabledOn' |
      'filters' |
      'overflow' |
      'position' |
      'scroll' |
      'sizing' |
      'spacing' |
      'sticky' |
      'transform' |
      'transition' |
      'zIndex'
    >;
  };

  // Module title.
  title?: Element.Types.Title.Attributes;

  // Empty state message.
  emptyState?: Element.Types.Content.Attributes;

  // Objectives list layout.
  list?: {
    decoration?: {
      layout?: Element.Decoration.Layout.Attributes;
    };
  };

  // Individual objective item.
  item?: {
    decoration?: {
      bodyFont?: Element.Decoration.BodyFont.Attributes;
    };
  };
}

/**
 * Lesson Objectives Module edit component props.
 *
 * @since 0.1.0
 */
export type LessonObjectivesEditProps = ModuleEditProps<LessonObjectivesAttrs>;
