/**
 * Course Objectives Module edit component for Visual Builder.
 *
 * Fetches course objectives via REST API and renders using the same
 * HTML structure as the PHP render_callback, ensuring VB preview
 * matches the frontend output.
 *
 * @package LeadersPath
 * @since 0.1.0
 */

import React, { ReactElement } from 'react';
import { ModuleContainer } from '@divi/module';
import { __ } from '@wordpress/i18n';

import { CourseObjectivesEditProps } from './types';
import { ModuleStyles } from './styles';
import { moduleClassnames } from './module-classnames';
import { useCourseObjectives } from './use-course-objectives';

/**
 * Course Objectives Module edit component.
 *
 * Fetches real course objectives via REST API and renders it in the VB,
 * ensuring the preview matches the frontend output.
 *
 * @since 0.1.0
 *
 * @param {CourseObjectivesEditProps} props React component props.
 * @returns {ReactElement}
 */
export const CourseObjectivesEdit = (props: CourseObjectivesEditProps): ReactElement => {
  const {
    attrs,
    elements,
    id,
    name,
  } = props;

  // Fetch course objectives from REST API.
  const { data, isLoading, error } = useCourseObjectives();

  /**
   * Render the objectives list.
   */
  const renderObjectivesList = (): ReactElement => {
    if (!data?.objectives || data.objectives.length === 0) {
      return (
        <div className="leaderspath-course-objectives__empty">
          {elements.render({ attrName: 'emptyState' })}
        </div>
      );
    }

    return (
      <ul className="leaderspath-course-objectives__list">
        {data.objectives.map((objective, index) => (
          <li key={index} className="leaderspath-course-objectives__item">
            {objective}
          </li>
        ))}
      </ul>
    );
  };

  /**
   * Render the content based on loading/error state.
   */
  const renderContent = (): ReactElement => {
    if (isLoading) {
      return (
        <div className="leaderspath-course-objectives__content">
          {elements.render({ attrName: 'title' })}
          <div className="leaderspath-course-objectives__placeholder">
            <p>{__('Loading objectives...', 'leaderspath')}</p>
          </div>
        </div>
      );
    }

    if (error) {
      return (
        <div className="leaderspath-course-objectives__content">
          {elements.render({ attrName: 'title' })}
          <div className="leaderspath-course-objectives__error">
            <p>{__('Error loading objectives:', 'leaderspath')} {error}</p>
          </div>
        </div>
      );
    }

    if (!data || !data.course_id) {
      return (
        <div className="leaderspath-course-objectives__content">
          {elements.render({ attrName: 'title' })}
          <div className="leaderspath-course-objectives__placeholder">
            <p>{__('No course data available. Create a Course to see this module in action.', 'leaderspath')}</p>
          </div>
        </div>
      );
    }

    // Render the same structure as PHP render_callback.
    return (
      <div className="leaderspath-course-objectives__content">
        {elements.render({ attrName: 'title' })}
        {renderObjectivesList()}
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
