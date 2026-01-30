/**
 * Chatbot Module TypeScript type definitions.
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
 * Custom CSS attribute interface for Chatbot.
 *
 * @since 0.1.0
 */
export interface ChatbotCssAttr {
  [key: string]: string | undefined;
  content?: string;
  title?: string;
  chat?: string;
  messages?: string;
  userBubble?: string;
  assistantBubble?: string;
  form?: string;
  input?: string;
  sendButton?: string;
  disabled?: string;
}

export type ChatbotCssGroupAttr = FormatBreakpointStateAttr<ChatbotCssAttr>;

/**
 * Chatbot Module attributes interface.
 *
 * @since 0.1.0
 */
export interface ChatbotAttrs extends InternalAttrs {
  // CSS options.
  css?: ChatbotCssGroupAttr;

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

  // Empty/disabled state message.
  emptyState?: Element.Types.Content.Attributes;

  // Messages container styling.
  messages?: {
    decoration?: Element.Decoration.PickedAttributes<
      'background' |
      'border' |
      'sizing' |
      'spacing'
    >;
  };

  // User message bubble styling.
  userBubble?: {
    decoration?: {
      background?: Element.Decoration.Background.Attributes;
      font?: Element.Decoration.Font.Attributes;
      border?: Element.Decoration.Border.Attributes;
      spacing?: Element.Decoration.Spacing.Attributes;
    };
  };

  // Assistant message bubble styling.
  assistantBubble?: {
    decoration?: {
      background?: Element.Decoration.Background.Attributes;
      font?: Element.Decoration.Font.Attributes;
      border?: Element.Decoration.Border.Attributes;
      spacing?: Element.Decoration.Spacing.Attributes;
    };
  };

  // Input field.
  input?: {
    innerContent?: FormatBreakpointStateAttr<string>;
    decoration?: {
      background?: Element.Decoration.Background.Attributes;
      border?: Element.Decoration.Border.Attributes;
      bodyFont?: Element.Decoration.BodyFont.Attributes;
    };
  };

  // Send button.
  sendButton?: {
    innerContent?: FormatBreakpointStateAttr<string>;
    decoration?: {
      button?: Element.Decoration.Button.Attributes;
    };
  };
}

/**
 * Chatbot Module edit component props.
 *
 * @since 0.1.0
 */
export type ChatbotEditProps = ModuleEditProps<ChatbotAttrs>;

/**
 * Chat message data structure.
 *
 * @since 0.1.0
 */
export interface ChatMessage {
  role: 'user' | 'assistant';
  content: string;
}
