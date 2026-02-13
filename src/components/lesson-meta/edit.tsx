import React, { ReactElement } from 'react';
import {
    ModuleContainer,
    ElementComponents,
} from '@divi/module';
import { __ } from '@wordpress/i18n';

import { LessonMetaEditProps } from './types';
import { ModuleStyles } from './styles';
import { moduleClassnames } from './module-classnames';
import { ModuleScriptData } from './module-script-data';

/**
 * Lesson Meta VB edit component.
 *
 * Renders placeholder content with the same semantic structure as
 * LessonMetaRenderer::render() so Divi design controls apply correctly.
 * Reads Content tab attrs for show/hide toggles and label text.
 */
const LessonMetaEdit = ({
    attrs,
    id,
    name,
    elements,
}: LessonMetaEditProps): ReactElement => {
    const contentValues = attrs?.content?.innerContent?.desktop?.value ?? {};

    const showDuration   = (contentValues.showDuration ?? 'on') === 'on';
    const showActivities = (contentValues.showActivities ?? 'on') === 'on';

    const durationLabel   = contentValues.durationLabel ?? __('Duration', 'leaderspath');
    const activitiesLabel = contentValues.activitiesLabel ?? __('Activities', 'leaderspath');

    // Read layout display mode from list attribute (Layout panel).
    // Divi's Layout panel generates flex/grid properties but NOT the
    // display property — we must apply it ourselves.
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
            <dl className="leaderspath_lesson_meta__list" style={{ display: listDisplay }}>
                {showDuration && (
                    <>
                        <dt className="leaderspath_lesson_meta__label">
                            {durationLabel}
                        </dt>
                        <dd className="leaderspath_lesson_meta__duration">
                            {__('90 minutes', 'leaderspath')}
                        </dd>
                    </>
                )}
                {showActivities && (
                    <>
                        <dt className="leaderspath_lesson_meta__label">
                            {activitiesLabel}
                        </dt>
                        <dd className="leaderspath_lesson_meta__activities">
                            {__('3 Activities', 'leaderspath')}
                        </dd>
                    </>
                )}
            </dl>
        </ModuleContainer>
    );
};

export { LessonMetaEdit };
