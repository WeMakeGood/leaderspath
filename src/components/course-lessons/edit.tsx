import React, { ReactElement } from 'react';
import {
    ModuleContainer,
    ElementComponents,
} from '@divi/module';
import { __ } from '@wordpress/i18n';

import { CourseLessonsEditProps } from './types';
import { ModuleStyles } from './styles';
import { moduleClassnames } from './module-classnames';
import { ModuleScriptData } from './module-script-data';

/**
 * Course Lessons VB edit component.
 *
 * Renders placeholder lesson cards with the same semantic structure as
 * CourseLessonsRenderer::render() so Divi design controls apply correctly.
 */
const CourseLessonsEdit = ({
    attrs,
    id,
    name,
    elements,
}: CourseLessonsEditProps): ReactElement => {
    const contentValues = attrs?.content?.innerContent?.desktop?.value ?? {};

    const showHeading      = (contentValues.showHeading ?? 'on') === 'on';
    const headingText      = contentValues.headingText ?? __('Lessons', 'leaderspath');
    const showNumber       = (contentValues.showNumber ?? 'on') === 'on';
    const showDuration     = (contentValues.showDuration ?? 'on') === 'on';
    const showActivityCount = (contentValues.showActivityCount ?? 'on') === 'on';
    const showExcerpt      = (contentValues.showExcerpt ?? 'on') === 'on';
    const showPrerequisites = (contentValues.showPrerequisites ?? 'off') === 'on';

    const listDisplay = attrs?.list?.decoration?.layout?.desktop?.value?.display ?? 'flex';

    const placeholderLessons = [
        {
            title: __('Introduction to AI Leadership', 'leaderspath'),
            duration: __('45 minutes', 'leaderspath'),
            activityCount: __('3 activities', 'leaderspath'),
            excerpt: __('Explore the fundamentals of AI-assisted leadership and decision-making.', 'leaderspath'),
        },
        {
            title: __('Context-Aware AI Interactions', 'leaderspath'),
            duration: __('60 minutes', 'leaderspath'),
            activityCount: __('4 activities', 'leaderspath'),
            excerpt: __('Learn how context shapes AI responses and improves collaboration.', 'leaderspath'),
        },
        {
            title: __('Advanced Prompt Engineering', 'leaderspath'),
            duration: __('90 minutes', 'leaderspath'),
            activityCount: __('5 activities', 'leaderspath'),
            excerpt: __('Master advanced techniques for crafting effective AI prompts.', 'leaderspath'),
        },
    ];

    const placeholderPrereqs = [
        { title: __('Foundations of Digital Literacy', 'leaderspath') },
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
                <h3 className="leaderspath_course_lessons__heading">
                    {headingText}
                </h3>
            )}
            <ul className="leaderspath_course_lessons__list" style={{ display: listDisplay }}>
                {placeholderLessons.map((lesson, index) => (
                    <li key={index} className="leaderspath_course_lessons__item">
                        <article>
                            {showNumber && (
                                <span className="leaderspath_course_lessons__number">
                                    {index + 1}
                                </span>
                            )}
                            <h4 className="leaderspath_course_lessons__title">
                                <a href="#" onClick={(e) => e.preventDefault()}>
                                    {lesson.title}
                                </a>
                            </h4>
                            {(showDuration || showActivityCount) && (
                                <div className="leaderspath_course_lessons__meta">
                                    {showDuration && (
                                        <span className="leaderspath_course_lessons__duration">
                                            {lesson.duration}
                                        </span>
                                    )}
                                    {showActivityCount && (
                                        <span className="leaderspath_course_lessons__activity-count">
                                            {lesson.activityCount}
                                        </span>
                                    )}
                                </div>
                            )}
                            {showExcerpt && (
                                <p className="leaderspath_course_lessons__excerpt">
                                    {lesson.excerpt}
                                </p>
                            )}
                        </article>
                    </li>
                ))}
            </ul>
            {showPrerequisites && placeholderPrereqs.length > 0 && (
                <section className="leaderspath_course_lessons__prereqs">
                    <h4 className="leaderspath_course_lessons__prereqs-heading">
                        {__('Prerequisites', 'leaderspath')}
                    </h4>
                    <ul className="leaderspath_course_lessons__prereqs-list">
                        {placeholderPrereqs.map((prereq, index) => (
                            <li key={index}>
                                <a href="#" onClick={(e) => e.preventDefault()}>
                                    {prereq.title}
                                </a>
                            </li>
                        ))}
                    </ul>
                </section>
            )}
        </ModuleContainer>
    );
};

export { CourseLessonsEdit };
