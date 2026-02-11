import React, { ReactElement } from 'react';
import {
    ModuleContainer,
    ElementComponents,
} from '@divi/module';
import { __ } from '@wordpress/i18n';

import { LessonObjectivesEditProps } from './types';
import { ModuleStyles } from './styles';
import { moduleClassnames } from './module-classnames';
import { ModuleScriptData } from './module-script-data';

/**
 * Lesson Objectives VB edit component.
 *
 * Renders placeholder content with the same semantic structure as
 * LessonObjectivesRenderer::render() so Divi design controls apply correctly.
 */
const LessonObjectivesEdit = ({
    attrs,
    id,
    name,
    elements,
}: LessonObjectivesEditProps): ReactElement => {
    const contentValues = attrs?.content?.innerContent?.desktop?.value ?? {};

    const showHeading = (contentValues.showHeading ?? 'on') === 'on';
    const headingText = contentValues.headingText ?? __('Learning Objectives', 'leaderspath');

    // Read layout display mode from list attribute (Layout panel).
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
            {showHeading && (
                <h3 className="leaderspath_lesson_objectives__heading">
                    {headingText}
                </h3>
            )}
            <ol className="leaderspath_lesson_objectives__list" style={{ display: listDisplay }}>
                <li className="leaderspath_lesson_objectives__item">
                    {__('Understand core AI concepts and terminology', 'leaderspath')}
                </li>
                <li className="leaderspath_lesson_objectives__item">
                    {__('Apply prompt engineering techniques effectively', 'leaderspath')}
                </li>
                <li className="leaderspath_lesson_objectives__item">
                    {__('Evaluate AI outputs for accuracy and bias', 'leaderspath')}
                </li>
            </ol>
        </ModuleContainer>
    );
};

export { LessonObjectivesEdit };
