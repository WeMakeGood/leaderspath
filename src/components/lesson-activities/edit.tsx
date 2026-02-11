import React, { ReactElement } from 'react';
import {
    ModuleContainer,
    ElementComponents,
} from '@divi/module';
import { __ } from '@wordpress/i18n';

import { LessonActivitiesEditProps } from './types';
import { ModuleStyles } from './styles';
import { moduleClassnames } from './module-classnames';
import { ModuleScriptData } from './module-script-data';

/**
 * Lesson Activities VB edit component.
 *
 * Renders placeholder activity cards with the same semantic structure as
 * LessonActivitiesRenderer::render() so Divi design controls apply correctly.
 */
const LessonActivitiesEdit = ({
    attrs,
    id,
    name,
    elements,
}: LessonActivitiesEditProps): ReactElement => {
    const contentValues = attrs?.content?.innerContent?.desktop?.value ?? {};

    const showHeading  = (contentValues.showHeading ?? 'on') === 'on';
    const headingText  = contentValues.headingText ?? __('Activities', 'leaderspath');
    const showDuration = (contentValues.showDuration ?? 'on') === 'on';
    const showExcerpt  = (contentValues.showExcerpt ?? 'on') === 'on';
    const showNumber   = (contentValues.showNumber ?? 'on') === 'on';

    const listDisplay = attrs?.list?.decoration?.layout?.desktop?.value?.display ?? 'flex';

    const placeholderActivities = [
        {
            title: __('Conversational AI Basics', 'leaderspath'),
            duration: __('15 minutes', 'leaderspath'),
            excerpt: __('Explore how context shapes AI responses through hands-on experimentation.', 'leaderspath'),
        },
        {
            title: __('Prompt Engineering Workshop', 'leaderspath'),
            duration: __('20 minutes', 'leaderspath'),
            excerpt: __('Practice crafting effective prompts for different AI use cases.', 'leaderspath'),
        },
        {
            title: __('AI Ethics Sandbox', 'leaderspath'),
            duration: __('25 minutes', 'leaderspath'),
            excerpt: __('Examine AI bias and ethical considerations through interactive scenarios.', 'leaderspath'),
        },
    ];

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
                <h3 className="leaderspath_lesson_activities__heading">
                    {headingText}
                </h3>
            )}
            <ul className="leaderspath_lesson_activities__list" style={{ display: listDisplay }}>
                {placeholderActivities.map((activity, index) => (
                    <li key={index} className="leaderspath_lesson_activities__item">
                        <article>
                            {showNumber && (
                                <span className="leaderspath_lesson_activities__number">
                                    {index + 1}
                                </span>
                            )}
                            <h4 className="leaderspath_lesson_activities__title">
                                <a href="#" onClick={(e) => e.preventDefault()}>
                                    {activity.title}
                                </a>
                            </h4>
                            {showDuration && (
                                <span className="leaderspath_lesson_activities__duration">
                                    {activity.duration}
                                </span>
                            )}
                            {showExcerpt && (
                                <p className="leaderspath_lesson_activities__excerpt">
                                    {activity.excerpt}
                                </p>
                            )}
                        </article>
                    </li>
                ))}
            </ul>
        </ModuleContainer>
    );
};

export { LessonActivitiesEdit };
