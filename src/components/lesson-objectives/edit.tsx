/**
 * Lesson Objectives Module edit component for Visual Builder.
 *
 * Fetches lesson objectives via REST API and renders using the same
 * HTML structure as the PHP render_callback, ensuring VB preview
 * matches the frontend output.
 *
 * @package LeadersPath
 * @since 0.1.0
 */

import React, { ReactElement } from 'react';
import { ModuleContainer } from '@divi/module';
import { __ } from '@wordpress/i18n';

import { LessonObjectivesEditProps } from './types';
import { ModuleStyles } from './styles';
import { moduleClassnames } from './module-classnames';
import { useLessonObjectives } from './use-lesson-objectives';

/**
 * Lesson Objectives Module edit component.
 *
 * Fetches real lesson objectives via REST API and renders it in the VB,
 * ensuring the preview matches the frontend output.
 *
 * @since 0.1.0
 *
 * @param {LessonObjectivesEditProps} props React component props.
 * @returns {ReactElement}
 */
export const LessonObjectivesEdit = (props: LessonObjectivesEditProps): ReactElement => {
  const {
    attrs,
    elements,
    id,
    name,
  } = props;

  // Fetch lesson objectives from REST API.
  const { data, isLoading, error } = useLessonObjectives();

  /**
   * Render the objectives list.
   */
  const renderObjectivesList = (): ReactElement => {
    if (!data?.objectives || data.objectives.length === 0) {
      return (
        <div className="leaderspath-lesson-objectives__empty">
          {elements.render({ attrName: 'emptyState' })}
        </div>
      );
    }

    return (
      <ul className="leaderspath-lesson-objectives__list">
        {data.objectives.map((objective, index) => (
          <li key={index} className="leaderspath-lesson-objectives__item">
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
        <div className="leaderspath-lesson-objectives__content">
          {elements.render({ attrName: 'title' })}
          <div className="leaderspath-lesson-objectives__placeholder">
            <p>{__('Loading objectives...', 'leaderspath')}</p>
          </div>
        </div>
      );
    }

    if (error) {
      return (
        <div className="leaderspath-lesson-objectives__content">
          {elements.render({ attrName: 'title' })}
          <div className="leaderspath-lesson-objectives__error">
            <p>{__('Error loading objectives:', 'leaderspath')} {error}</p>
          </div>
        </div>
      );
    }

    if (!data || !data.lesson_id) {
      return (
        <div className="leaderspath-lesson-objectives__content">
          {elements.render({ attrName: 'title' })}
          <div className="leaderspath-lesson-objectives__placeholder">
            <p>{__('No lesson data available. Create a Lesson to see this module in action.', 'leaderspath')}</p>
          </div>
        </div>
      );
    }

    // Render the same structure as PHP render_callback.
    return (
      <div className="leaderspath-lesson-objectives__content">
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
