import React, { Fragment, ReactElement } from 'react';
import { ModuleScriptDataProps } from '@divi/module';
import { ContextLibraryAttrs } from './types';

export const ModuleScriptData = ({
    elements,
}: ModuleScriptDataProps<ContextLibraryAttrs>): ReactElement => (
    <Fragment>
        {elements.scriptData({ attrName: 'module' })}
    </Fragment>
);
