import React, { Fragment, ReactElement } from 'react';
import { ModuleScriptDataProps } from '@divi/module';
import { ActivityMetaAttrs } from './types';

export const ModuleScriptData = ({
    elements,
}: ModuleScriptDataProps<ActivityMetaAttrs>): ReactElement => (
    <Fragment>
        {elements.scriptData({ attrName: 'module' })}
    </Fragment>
);
