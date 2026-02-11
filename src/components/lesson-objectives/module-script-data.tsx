import React, { Fragment, ReactElement } from 'react';
import { ModuleScriptDataProps } from '@divi/module';
import { LessonObjectivesAttrs } from './types';

export const ModuleScriptData = ({
    elements,
}: ModuleScriptDataProps<LessonObjectivesAttrs>): ReactElement => (
    <Fragment>
        {elements.scriptData({ attrName: 'module' })}
    </Fragment>
);
