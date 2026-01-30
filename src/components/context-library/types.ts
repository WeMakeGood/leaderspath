/**
 * Context Library Module TypeScript type definitions.
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
 * Custom CSS attribute interface for Context Library.
 *
 * @since 0.1.0
 */
export interface ContextLibraryCssAttr {
  [key: string]: string | undefined;
  content?: string;
  title?: string;
  grid?: string;
  item?: string;
  itemHeader?: string;
  itemTitle?: string;
  itemBadge?: string;
  itemDescription?: string;
  itemButtons?: string;
  viewButton?: string;
  downloadButton?: string;
  emptyState?: string;
}

export type ContextLibraryCssGroupAttr = FormatBreakpointStateAttr<ContextLibraryCssAttr>;

/**
 * Context Library Module attributes interface.
 *
 * @since 0.1.0
 */
export interface ContextLibraryAttrs extends InternalAttrs {
  // CSS options.
  css?: ContextLibraryCssGroupAttr;

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

  // Context files visibility toggles.
  contextFiles?: {
    advanced?: {
      showDescription?: FormatBreakpointStateAttr<string>;
      showFileType?: FormatBreakpointStateAttr<string>;
      showViewButton?: FormatBreakpointStateAttr<string>;
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

  // View button styling.
  viewButton?: {
    decoration?: {
      button?: Element.Decoration.Button.Attributes;
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
 * Context Library Module edit component props.
 *
 * @since 0.1.0
 */
export type ContextLibraryEditProps = ModuleEditProps<ContextLibraryAttrs>;

/**
 * Single context file item data.
 *
 * @since 0.1.0
 */
export interface ContextFileItem {
  id: number;
  title: string;
  description: string;
  fileType: string;
}
