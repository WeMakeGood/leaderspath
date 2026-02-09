/**
 * Lesson Activities Module edit component for Visual Builder.
 *
 * Fetches lesson activities via REST API and renders using the same
 * HTML structure as the PHP render_callback, ensuring VB preview
 * matches the frontend output.
 *
 * @package LeadersPath
 * @since 0.1.0
 */

import React, { ReactElement } from 'react';
import { ModuleContainer } from '@divi/module';
import { __ } from '@wordpress/i18n';

import { LessonActivitiesEditProps } from './types';
import { ModuleStyles } from './styles';
import { moduleClassnames } from './module-classnames';
import { useLessonActivities, LessonActivity } from './use-lesson-activities';

/**
 * Lesson Activities Module edit component.
 *
 * Fetches real lesson activities via REST API and renders it in the VB,
 * ensuring the preview matches the frontend output.
 *
 * @since 0.1.0
 *
 * @param {LessonActivitiesEditProps} props React component props.
 * @returns {ReactElement}
 */
export const LessonActivitiesEdit = (props: LessonActivitiesEditProps): ReactElement => {
  const {
    attrs,
    elements,
    id,
    name,
  } = props;

  // Fetch lesson activities from REST API.
  const { data, isLoading, error } = useLessonActivities();

  /**
   * Render the activities list.
   */
  const renderActivitiesList = (): ReactElement => {
    if (!data?.activities || data.activities.length === 0) {
      return (
        <div className="leaderspath-lesson-activities__empty">
          {elements.render({ attrName: 'emptyState' })}
        </div>
      );
    }

    return (
      <ol className="leaderspath-lesson-activities__list">
        {data.activities.map((activity: LessonActivity, index: number) => (
          <li key={activity.id} className="leaderspath-lesson-activities__item">
            <span className="leaderspath-lesson-activities__number">{index + 1}</span>
            <a href={activity.url} className="leaderspath-lesson-activities__link">
              {activity.title}
            </a>
          </li>
        ))}
      </ol>
    );
  };

  /**
   * Render the content based on loading/error state.
   */
  const renderContent = (): ReactElement => {
    if (isLoading) {
      return (
        <div className="leaderspath-lesson-activities__content">
          {elements.render({ attrName: 'title' })}
          <div className="leaderspath-lesson-activities__placeholder">
            <p>{__('Loading activities...', 'leaderspath')}</p>
          </div>
        </div>
      );
    }

    if (error) {
      return (
        <div className="leaderspath-lesson-activities__content">
          {elements.render({ attrName: 'title' })}
          <div className="leaderspath-lesson-activities__error">
            <p>{__('Error loading activities:', 'leaderspath')} {error}</p>
          </div>
        </div>
      );
    }

    if (!data || !data.lesson_id) {
      return (
        <div className="leaderspath-lesson-activities__content">
          {elements.render({ attrName: 'title' })}
          <div className="leaderspath-lesson-activities__placeholder">
            <p>{__('No lesson data available. Create a Lesson to see this module in action.', 'leaderspath')}</p>
          </div>
        </div>
      );
    }

    // Render the same structure as PHP render_callback.
    return (
      <div className="leaderspath-lesson-activities__content">
        {elements.render({ attrName: 'title' })}
        {renderActivitiesList()}
      </div>
    );
  };

  return (
    <ModuleContainer
      attrs={attrs}
      elements={elements}
      id={id}
      name={name}
      stylesComponent={ModuleStyles}
      classnamesFunction={moduleClassnames}
    >
      {elements.styleComponents({
        attrName: 'module',
      })}
      {renderContent()}
    </ModuleContainer>
  );
};
