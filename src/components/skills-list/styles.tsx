import React, { ReactElement } from 'react';
import {
    StyleContainer,
    StylesProps,
    CssStyle,
} from '@divi/module';

import { SkillsListAttrs } from './types';
import { cssFields } from './custom-css';

const ModuleStyles = ({
    attrs,
    elements,
    settings,
    orderClass,
    mode,
    state,
    noStyleTag,
}: StylesProps<SkillsListAttrs>): ReactElement => (
    <StyleContainer mode={mode} state={state} noStyleTag={noStyleTag}>
        {elements.style({
            attrName: 'module',
            styleProps: {
                disabledOn: {
                    disabledModuleVisibility: settings?.disabledModuleVisibility,
                },
            },
        })}
        {elements.style({ attrName: 'grid' })}
        {elements.style({ attrName: 'heading' })}
        {elements.style({ attrName: 'card' })}
        {elements.style({ attrName: 'cardTitle' })}
        {elements.style({ attrName: 'cardDesc' })}
        {elements.style({ attrName: 'cardMeta' })}
        <CssStyle
            selector={orderClass}
            attr={attrs?.css}
            cssFields={cssFields}
        />
    </StyleContainer>
);

export { ModuleStyles };
