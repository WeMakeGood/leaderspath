/**
 * Skills List Module type definitions.
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
 * Custom CSS attribute interface for Skills List.
 *
 * @since 0.1.0
 */
export interface SkillsListCssAttr {
  [key: string]: string | undefined;
  content?: string;
  title?: string;
  grid?: string;
  item?: string;
  itemHeader?: string;
  itemTitle?: string;
  itemBadges?: string;
  itemBadge?: string;
  itemDescription?: string;
  itemButtons?: string;
  downloadButton?: string;
  emptyState?: string;
}

export type SkillsListCssGroupAttr = FormatBreakpointStateAttr<SkillsListCssAttr>;

/**
 * Skills List Module attributes interface.
 *
 * @since 0.1.0
 */
export interface SkillsListAttrs extends InternalAttrs {
  // CSS options.
  css?: SkillsListCssGroupAttr;

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

  // Skills visibility toggles.
  skills?: {
    advanced?: {
      showDescription?: FormatBreakpointStateAttr<string>;
      showCompatibility?: FormatBreakpointStateAttr<string>;
      showVersion?: FormatBreakpointStateAttr<string>;
      showDownloadButton?: FormatBreakpointStateAttr<string>;
    };
  };

  // Item container styling.
  item?: {
    decoration?: Element.Decoration.PickedAttributes<
      'background' |
      'border' |
      'boxShadow' |
      'spacing'
    >;
  };

  // Item title styling.
  itemTitle?: {
    decoration?: {
      font?: Element.Decoration.Font.Attributes;
    };
  };

  // Item description styling.
  itemDescription?: {
    decoration?: {
      bodyFont?: Element.Decoration.BodyFont.Attributes;
    };
  };

  // Item badge styling.
  itemBadge?: {
    decoration?: {
      background?: Element.Decoration.Background.Attributes;
      font?: Element.Decoration.Font.Attributes;
      border?: Element.Decoration.Border.Attributes;
    };
  };

  // Download button styling.
  downloadButton?: {
    decoration?: {
      button?: Element.Decoration.Button.Attributes;
    };
  };
}

/**
 * Skill item data structure from PHP/ACF.
 *
 * @since 0.1.0
 */
export interface SkillItem {
  id: number;
  title: string;
  name: string;
  description: string;
  compatibility: string;
  version: string;
  packageUrl: string;
}

/**
 * Props for the Skills List edit component.
 *
 * @since 0.1.0
 */
export type SkillsListEditProps = ModuleEditProps<SkillsListAttrs>;
