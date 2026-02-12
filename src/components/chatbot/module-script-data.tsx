import React, { Fragment, ReactElement } from 'react';
import { ModuleScriptDataProps } from '@divi/module';
import { ChatbotAttrs } from './types';

export const ModuleScriptData = ({
    elements,
}: ModuleScriptDataProps<ChatbotAttrs>): ReactElement => (
    <Fragment>
        {elements.scriptData({ attrName: 'module' })}
    </Fragment>
);
