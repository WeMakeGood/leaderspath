import React, { ReactElement } from 'react';
import {
    StyleContainer,
    StylesProps,
    CssStyle,
} from '@divi/module';

import { CourseLessonsAttrs } from './types';
import { cssFields } from './custom-css';

const ModuleStyles = ({
    attrs,
    elements,
    settings,
    orderClass,
    mode,
    state,
    noStyleTag,
}: StylesProps<CourseLessonsAttrs>): ReactElement => (
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
        {elements.style({ attrName: 'heading' })}
        {elements.style({ attrName: 'title' })}
        {elements.style({ attrName: 'number' })}
        {elements.style({ attrName: 'difficulty' })}
        {elements.style({ attrName: 'duration' })}
        {elements.style({ attrName: 'activityCount' })}
        {elements.style({ attrName: 'excerpt' })}
        <CssStyle
            selector={orderClass}
            attr={attrs?.css}
            cssFields={cssFields}
        />
    </StyleContainer>
);

export { ModuleStyles };
