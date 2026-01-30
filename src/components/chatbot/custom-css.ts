/**
 * Chatbot Module custom CSS field definitions.
 *
 * @package LeadersPath
 * @since 0.1.0
 */

import { type CssFields } from '@divi/module';

/**
 * Custom CSS fields for the Chatbot module.
 *
 * These define the available custom CSS selectors in the Advanced tab.
 *
 * @since 0.1.0
 */
export const cssFields: CssFields = {
  content: {
    subName: 'content',
    selectorSuffix: ' .leaderspath-chatbot__content',
  },
  title: {
    subName: 'title',
    selectorSuffix: ' .leaderspath-chatbot__title',
  },
  chat: {
    subName: 'chat',
    selectorSuffix: ' .leaderspath-chatbot__chat',
  },
  messages: {
    subName: 'messages',
    selectorSuffix: ' .leaderspath-chatbot__messages',
  },
  userBubble: {
    subName: 'userBubble',
    selectorSuffix: ' .leaderspath-chatbot__message--user',
  },
  assistantBubble: {
    subName: 'assistantBubble',
    selectorSuffix: ' .leaderspath-chatbot__message--assistant',
  },
  form: {
    subName: 'form',
    selectorSuffix: ' .leaderspath-chatbot__form',
  },
  input: {
    subName: 'input',
    selectorSuffix: ' .leaderspath-chatbot__input',
  },
  sendButton: {
    subName: 'sendButton',
    selectorSuffix: ' .leaderspath-chatbot__send',
  },
  disabled: {
    subName: 'disabled',
    selectorSuffix: ' .leaderspath-chatbot__disabled',
  },
};
