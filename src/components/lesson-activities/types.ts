/**
 * Lesson Activities Module TypeScript type definitions.
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
 * Custom CSS attribute interface for Lesson Activities.
 *
 * @since 0.1.0
 */
export interface LessonActivitiesCssAttr {
  [key: string]: string | undefined;
  content?: string;
  title?: string;
  list?: string;
  item?: string;
  number?: string;
  link?: string;
  empty?: string;
}

export type LessonActivitiesCssGroupAttr = FormatBreakpointStateAttr<LessonActivitiesCssAttr>;

/**
 * Lesson Activities Module attributes interface.
 *
 * @since 0.1.0
 */
export interface LessonActivitiesAttrs extends InternalAttrs {
  // CSS options.
  css?: LessonActivitiesCssGroupAttr;

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

  // Activities list layout.
  list?: {
    decoration?: {
      layout?: Element.Decoration.Layout.Attributes;
    };
  };

  // Individual activity item card.
  item?: {
    decoration?: Element.Decoration.PickedAttributes<
      'background' |
      'border' |
      'spacing'
    >;
  };

  // Number badge.
  numberBadge?: {
    decoration?: {
      background?: Element.Decoration.Background.Attributes;
      font?: Element.Decoration.Font.Attributes;
    };
  };

  // Activity link text.
  link?: {
    decoration?: {
      font?: Element.Decoration.Font.Attributes;
    };
  };
}

/**
 * Lesson Activities Module edit component props.
 *
 * @since 0.1.0
 */
export type LessonActivitiesEditProps = ModuleEditProps<LessonActivitiesAttrs>;
