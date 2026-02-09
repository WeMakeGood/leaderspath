/**
 * Course Activities Module edit component for Visual Builder.
 *
 * Fetches course activities via REST API and renders using the same
 * HTML structure as the PHP render_callback, ensuring VB preview
 * matches the frontend output.
 *
 * @package LeadersPath
 * @since 0.1.0
 */

import React, { ReactElement } from 'react';
import { ModuleContainer } from '@divi/module';
import { __ } from '@wordpress/i18n';

import { CourseActivitiesEditProps } from './types';
import { ModuleStyles } from './styles';
import { moduleClassnames } from './module-classnames';
import { useCourseActivities, CourseActivity } from './use-course-activities';

/**
 * Course Activities Module edit component.
 *
 * Fetches real course activities via REST API and renders it in the VB,
 * ensuring the preview matches the frontend output.
 *
 * @since 0.1.0
 *
 * @param {CourseActivitiesEditProps} props React component props.
 * @returns {ReactElement}
 */
export const CourseActivitiesEdit = (props: CourseActivitiesEditProps): ReactElement => {
  const {
    attrs,
    elements,
    id,
    name,
  } = props;

  // Fetch course activities from REST API.
  const { data, isLoading, error } = useCourseActivities();

  /**
   * Render the activities list.
   */
  const renderActivitiesList = (): ReactElement => {
    if (!data?.activities || data.activities.length === 0) {
      return (
        <div className="leaderspath-course-activities__empty">
          {elements.render({ attrName: 'emptyState' })}
        </div>
      );
    }

    return (
      <ol className="leaderspath-course-activities__list">
        {data.activities.map((activity: CourseActivity, index: number) => (
          <li key={activity.id} className="leaderspath-course-activities__item">
            <span className="leaderspath-course-activities__number">{index + 1}</span>
            <a href={activity.url} className="leaderspath-course-activities__link">
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
        <div className="leaderspath-course-activities__content">
          {elements.render({ attrName: 'title' })}
          <div className="leaderspath-course-activities__placeholder">
            <p>{__('Loading activities...', 'leaderspath')}</p>
          </div>
        </div>
      );
    }

    if (error) {
      return (
        <div className="leaderspath-course-activities__content">
          {elements.render({ attrName: 'title' })}
          <div className="leaderspath-course-activities__error">
            <p>{__('Error loading activities:', 'leaderspath')} {error}</p>
          </div>
        </div>
      );
    }

    if (!data || !data.course_id) {
      return (
        <div className="leaderspath-course-activities__content">
          {elements.render({ attrName: 'title' })}
          <div className="leaderspath-course-activities__placeholder">
            <p>{__('No course data available. Create a Course to see this module in action.', 'leaderspath')}</p>
          </div>
        </div>
      );
    }

    // Render the same structure as PHP render_callback.
    return (
      <div className="leaderspath-course-activities__content">
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
