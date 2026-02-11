import React, { Fragment, ReactElement } from 'react';
import { ModuleScriptDataProps } from '@divi/module';
import { LessonMetaAttrs } from './types';

export const ModuleScriptData = ({
    elements,
}: ModuleScriptDataProps<LessonMetaAttrs>): ReactElement => (
    <Fragment>
        {elements.scriptData({ attrName: 'module' })}
    </Fragment>
);
