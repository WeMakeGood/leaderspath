import React, { ReactElement } from 'react';
import {
    StyleContainer,
    StylesProps,
    CssStyle,
} from '@divi/module';

import { ChatbotAttrs } from './types';
import { cssFields } from './custom-css';

const ModuleStyles = ({
    attrs,
    elements,
    settings,
    orderClass,
    mode,
    state,
    noStyleTag,
}: StylesProps<ChatbotAttrs>): ReactElement => (
    <StyleContainer mode={mode} state={state} noStyleTag={noStyleTag}>
        {elements.style({
            attrName: 'module',
            styleProps: {
                disabledOn: {
                    disabledModuleVisibility: settings?.disabledModuleVisibility,
                },
            },
        })}
        {elements.style({ attrName: 'container' })}
        {elements.style({ attrName: 'heading' })}
        {elements.style({ attrName: 'userMessage' })}
        {elements.style({ attrName: 'assistantMessage' })}
        {elements.style({ attrName: 'inputField' })}
        {elements.style({ attrName: 'sendButton' })}
        <CssStyle
            selector={orderClass}
            attr={attrs?.css}
            cssFields={cssFields}
        />
    </StyleContainer>
);

export { ModuleStyles };
