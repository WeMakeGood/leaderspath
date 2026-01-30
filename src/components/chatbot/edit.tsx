/**
 * Chatbot Module edit component for Visual Builder.
 *
 * @package LeadersPath
 * @since 0.1.0
 */

import React, { ReactElement } from 'react';
import { ModuleContainer } from '@divi/module';
import { ModuleEditProps } from '@divi/module-library';

import { ChatMessage } from './types';
import { ModuleStyles } from './styles';
import { moduleClassnames } from './module-classnames';

/**
 * Placeholder chat messages for Visual Builder preview.
 *
 * These demonstrate the layout since the chat is interactive only on frontend.
 */
const placeholderMessages: ChatMessage[] = [
  {
    role: 'user',
    content: 'Can you explain the concept we just learned?',
  },
  {
    role: 'assistant',
    content: 'Of course! The key concept here is understanding how AI can be enhanced with context. When we provide relevant background information, the AI can give more accurate and helpful responses tailored to your specific needs.',
  },
  {
    role: 'user',
    content: 'How does that work in practice?',
  },
  {
    role: 'assistant',
    content: 'In practice, context files contain reference material like guidelines, knowledge bases, or specific instructions. These are included in the conversation so the AI understands your unique situation and requirements.',
  },
];

/**
 * Chatbot Module edit component.
 *
 * This component renders the module in the Divi Visual Builder.
 * Since the chat is only interactive on the frontend, we show placeholder
 * messages that demonstrate the layout and styling options.
 *
 * @since 0.1.0
 *
 * @param {ModuleEditProps} props React component props.
 * @returns {ReactElement}
 */
export const ChatbotEdit = (props: ModuleEditProps): ReactElement => {
  const {
    attrs,
    elements,
    id,
    name,
  } = props;

  // Get placeholder text from attributes or use defaults.
  const placeholderText = attrs?.input?.innerContent?.desktop?.value ?? 'Type your message...';
  const sendButtonText = attrs?.sendButton?.innerContent?.desktop?.value ?? 'Send';

  return (
    <ModuleContainer
      attrs={attrs}
      elements={elements}
      id={id}
      name={name}
      stylesComponent={ModuleStyles}
      classnamesFunction={moduleClassnames}
    >
      {elements.styleComponents({
        attrName: 'module',
      })}
      <div className="leaderspath-chatbot__content">
        {elements.render({
          attrName: 'title',
        })}

        <div className="leaderspath-chatbot__chat">
          <div
            className="leaderspath-chatbot__messages"
            role="log"
            aria-live="polite"
          >
            {placeholderMessages.map((message, index) => (
              <div
                key={index}
                className={`leaderspath-chatbot__message leaderspath-chatbot__message--${message.role}`}
              >
                {message.content}
              </div>
            ))}
          </div>

          <form className="leaderspath-chatbot__form">
            <div
              className="leaderspath-chatbot__input"
              data-placeholder={placeholderText}
              aria-label="Chat message"
            >
              {/* Placeholder text shown in VB preview */}
              <span style={{ color: '#999' }}>{placeholderText}</span>
            </div>
            <button
              type="button"
              className="leaderspath-chatbot__send et_pb_button"
              disabled
            >
              {sendButtonText}
            </button>
          </form>
        </div>
      </div>
    </ModuleContainer>
  );
};
