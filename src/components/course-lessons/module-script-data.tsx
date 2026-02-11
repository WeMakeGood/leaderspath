import React, { Fragment, ReactElement } from 'react';
import { ModuleScriptDataProps } from '@divi/module';
import { CourseLessonsAttrs } from './types';

export const ModuleScriptData = ({
    elements,
}: ModuleScriptDataProps<CourseLessonsAttrs>): ReactElement => (
    <Fragment>
        {elements.scriptData({ attrName: 'module' })}
    </Fragment>
);
