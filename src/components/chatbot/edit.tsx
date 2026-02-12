import React, { ReactElement } from 'react';
import {
    ModuleContainer,
    ElementComponents,
} from '@divi/module';
import { __ } from '@wordpress/i18n';

import { ChatbotEditProps } from './types';
import { ModuleStyles } from './styles';
import { moduleClassnames } from './module-classnames';
import { ModuleScriptData } from './module-script-data';

/**
 * Chatbot VB edit component.
 *
 * Renders a static chat mockup with sample messages matching the same
 * DOM structure as ChatbotRenderer::render() so Divi design controls
 * apply correctly. No interactivity in VB.
 */
const ChatbotEdit = ({
    attrs,
    id,
    name,
    elements,
}: ChatbotEditProps): ReactElement => {
    const contentValues = attrs?.content?.innerContent?.desktop?.value ?? {};

    const showHeading = (contentValues.showHeading ?? 'on') === 'on';
    const headingText = contentValues.headingText ?? __('AI Sandbox', 'leaderspath');
    const emptyText   = contentValues.emptyText ?? __('Send a message to start the conversation.', 'leaderspath');

    const sendIcon = (
        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
            <line x1="22" y1="2" x2="11" y2="13" />
            <polygon points="22 2 15 22 11 13 2 9 22 2" />
        </svg>
    );

    return (
        <ModuleContainer
            attrs={attrs}
            elements={elements}
            id={id}
            name={name}
            stylesComponent={ModuleStyles}
            classnamesFunction={moduleClassnames}
            scriptDataComponent={ModuleScriptData}
        >
            {elements.styleComponents({ attrName: 'module' })}
            <ElementComponents
                attrs={attrs?.module?.decoration ?? {}}
                id={id}
            />
            <div className="leaderspath_chatbot__container">
                {showHeading && (
                    <h3 className="leaderspath_chatbot__heading">
                        {headingText}
                    </h3>
                )}
                <div className="leaderspath_chatbot__messages" role="log">
                    {/* Sample conversation for VB preview */}
                    <div className="leaderspath_chatbot__message leaderspath_chatbot__message--user">
                        <div className="leaderspath_chatbot__message__content">
                            {__('What are the key principles of prompt engineering?', 'leaderspath')}
                        </div>
                    </div>
                    <div className="leaderspath_chatbot__message leaderspath_chatbot__message--assistant">
                        <div className="leaderspath_chatbot__message__content">
                            <p>{__('Here are the key principles of prompt engineering:', 'leaderspath')}</p>
                            <ol>
                                <li><strong>{__('Be specific', 'leaderspath')}</strong> — {__('Clearly state what you want the AI to do.', 'leaderspath')}</li>
                                <li><strong>{__('Provide context', 'leaderspath')}</strong> — {__('Give relevant background information.', 'leaderspath')}</li>
                                <li><strong>{__('Set constraints', 'leaderspath')}</strong> — {__('Define the format, length, or style of the response.', 'leaderspath')}</li>
                            </ol>
                        </div>
                    </div>
                    <div className="leaderspath_chatbot__message leaderspath_chatbot__message--user">
                        <div className="leaderspath_chatbot__message__content">
                            {__('Can you give me an example?', 'leaderspath')}
                        </div>
                    </div>
                </div>
                <div className="leaderspath_chatbot__input_area">
                    <div className="leaderspath_chatbot__input_row">
                        <textarea
                            className="leaderspath_chatbot__input"
                            placeholder={__('Type your message...', 'leaderspath')}
                            rows={1}
                            readOnly
                        />
                        <button
                            type="button"
                            className="leaderspath_chatbot__send"
                            onClick={(e) => e.preventDefault()}
                        >
                            {sendIcon}
                        </button>
                    </div>
                </div>
            </div>
        </ModuleContainer>
    );
};

export { ChatbotEdit };
