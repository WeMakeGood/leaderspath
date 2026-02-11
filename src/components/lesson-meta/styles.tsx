import React, { ReactElement } from 'react';
import {
    StyleContainer,
    StylesProps,
    CssStyle,
} from '@divi/module';

import { LessonMetaAttrs } from './types';
import { cssFields } from './custom-css';

const ModuleStyles = ({
    attrs,
    elements,
    settings,
    orderClass,
    mode,
    state,
    noStyleTag,
}: StylesProps<LessonMetaAttrs>): ReactElement => (
    <StyleContainer mode={mode} state={state} noStyleTag={noStyleTag}>
        {elements.style({
            attrName: 'module',
            styleProps: {
                disabledOn: {
                    disabledModuleVisibility: settings?.disabledModuleVisibility,
                },
            },
        })}
        {elements.style({ attrName: 'list' })}
        {elements.style({ attrName: 'label' })}
        {elements.style({ attrName: 'duration' })}
        {elements.style({ attrName: 'difficulty' })}
        {elements.style({ attrName: 'activities' })}
        <CssStyle
            selector={orderClass}
            attr={attrs?.css}
            cssFields={cssFields}
        />
    </StyleContainer>
);

export { ModuleStyles };
