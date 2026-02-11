import React, { ReactElement } from 'react';
import {
    ModuleContainer,
    ElementComponents,
} from '@divi/module';
import { __ } from '@wordpress/i18n';

import { ActivityMetaEditProps } from './types';
import { ModuleStyles } from './styles';
import { moduleClassnames } from './module-classnames';
import { ModuleScriptData } from './module-script-data';

/**
 * Activity Meta VB edit component.
 *
 * Renders placeholder content with the same semantic structure as
 * ActivityMetaRenderer::render() so Divi design controls apply correctly.
 */
const ActivityMetaEdit = ({
    attrs,
    id,
    name,
    elements,
}: ActivityMetaEditProps): ReactElement => {
    const contentValues = attrs?.content?.innerContent?.desktop?.value ?? {};

    const showDuration    = (contentValues.showDuration ?? 'on') === 'on';
    const showModel       = (contentValues.showModel ?? 'on') === 'on';
    const showModelSwitch = (contentValues.showModelSwitch ?? 'on') === 'on';

    const durationLabel    = contentValues.durationLabel ?? __('Duration', 'leaderspath');
    const modelLabel       = contentValues.modelLabel ?? __('Model', 'leaderspath');
    const modelSwitchLabel = contentValues.modelSwitchLabel ?? __('Model Switching', 'leaderspath');

    const listDisplay = attrs?.list?.decoration?.layout?.desktop?.value?.display ?? 'flex';

    return (
        <ModuleContainer
            attrs={attrs}
            elements={elements}
            id={id}
            name={name}
            stylesComponent={ModuleStyles}
            classnamesFunction={moduleClassnames}
            scriptDataComponent={ModuleScriptData}
        >
            {elements.styleComponents({ attrName: 'module' })}
            <ElementComponents
                attrs={attrs?.module?.decoration ?? {}}
                id={id}
            />
            <dl className="leaderspath_activity_meta__list" style={{ display: listDisplay }}>
                {showDuration && (
                    <>
                        <dt className="leaderspath_activity_meta__label">
                            {durationLabel}
                        </dt>
                        <dd className="leaderspath_activity_meta__duration">
                            {__('15 minutes', 'leaderspath')}
                        </dd>
                    </>
                )}
                {showModel && (
                    <>
                        <dt className="leaderspath_activity_meta__label">
                            {modelLabel}
                        </dt>
                        <dd className="leaderspath_activity_meta__model" data-model="sonnet">
                            {__('Claude Sonnet', 'leaderspath')}
                        </dd>
                    </>
                )}
                {showModelSwitch && (
                    <>
                        <dt className="leaderspath_activity_meta__label">
                            {modelSwitchLabel}
                        </dt>
                        <dd className="leaderspath_activity_meta__model-switch" data-allowed="no">
                            {__('Disabled', 'leaderspath')}
                        </dd>
                    </>
                )}
            </dl>
        </ModuleContainer>
    );
};

export { ActivityMetaEdit };
