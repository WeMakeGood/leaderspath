import React, { Fragment, ReactElement } from 'react';
import { ModuleScriptDataProps } from '@divi/module';
import { SkillsListAttrs } from './types';

export const ModuleScriptData = ({
    elements,
}: ModuleScriptDataProps<SkillsListAttrs>): ReactElement => (
    <Fragment>
        {elements.scriptData({ attrName: 'module' })}
    </Fragment>
);
