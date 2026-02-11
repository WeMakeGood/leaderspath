import React, { Fragment, ReactElement } from 'react';
import { ModuleScriptDataProps } from '@divi/module';
import { LessonActivitiesAttrs } from './types';

export const ModuleScriptData = ({
    elements,
}: ModuleScriptDataProps<LessonActivitiesAttrs>): ReactElement => (
    <Fragment>
        {elements.scriptData({ attrName: 'module' })}
    </Fragment>
);
