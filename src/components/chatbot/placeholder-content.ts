/**
 * Chatbot Module placeholder content for Visual Builder.
 *
 * @package LeadersPath
 * @since 0.1.0
 */

import { ChatbotAttrs } from './types';

/**
 * Placeholder content for the Chatbot module.
 *
 * This defines the default attribute values shown when the module
 * is first added to a page in the Visual Builder.
 *
 * @since 0.1.0
 */
export const placeholderContent: Partial<ChatbotAttrs> = {
  title: {
    innerContent: {
      desktop: {
        value: 'Chat with AI',
      },
    },
  },
  input: {
    innerContent: {
      desktop: {
        value: 'Type your message...',
      },
    },
  },
  sendButton: {
    innerContent: {
      desktop: {
        value: 'Send',
      },
    },
  },
  emptyState: {
    innerContent: {
      desktop: {
        value: 'The chatbot is not available for this lesson.',
      },
    },
  },
};
