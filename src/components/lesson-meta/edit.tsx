/**
 * Lesson Meta Module edit component for Visual Builder.
 *
 * Fetches lesson metadata via REST API and renders using the same
 * HTML structure as the PHP render_callback, ensuring VB preview
 * matches the frontend output.
 *
 * @package LeadersPath
 * @since 0.1.0
 */

import React, { ReactElement } from 'react';
import { ModuleContainer } from '@divi/module';
import { __ } from '@wordpress/i18n';

import { LessonMetaEditProps } from './types';
import { ModuleStyles } from './styles';
import { moduleClassnames } from './module-classnames';
import { useLessonMeta } from './use-server-side-render';

/**
 * Lesson Meta Module edit component.
 *
 * Fetches real lesson data via REST API and renders it in the VB,
 * ensuring the preview matches the frontend output.
 *
 * @since 0.1.0
 *
 * @param {LessonMetaEditProps} props React component props.
 * @returns {ReactElement}
 */
export const LessonMetaEdit = (props: LessonMetaEditProps): ReactElement => {
  const {
    attrs,
    elements,
    id,
    name,
  } = props;

  // Fetch lesson metadata from REST API.
  const { data, isLoading, error } = useLessonMeta();

  // Get visibility toggles from attrs.
  const showDuration = attrs?.duration?.advanced?.show?.desktop?.value ?? 'on';
  const showDifficulty = attrs?.difficulty?.advanced?.show?.desktop?.value ?? 'on';
  const showActivities = attrs?.activities?.advanced?.show?.desktop?.value ?? 'on';

  /**
   * Render the duration section.
   */
  const renderDuration = (): ReactElement | null => {
    if (showDuration !== 'on' || !data?.duration) {
      return null;
    }

    return (
      <div className="leaderspath-lesson-meta__section leaderspath-lesson-meta__duration">
        {elements.render({ attrName: 'durationLabel' })}
        <span className="leaderspath-lesson-meta__value">
          {data.duration}
        </span>
      </div>
    );
  };

  /**
   * Render the difficulty section.
   */
  const renderDifficulty = (): ReactElement | null => {
    if (showDifficulty !== 'on' || !data?.difficulty) {
      return null;
    }

    const badgeClass = `leaderspath-lesson-meta__value leaderspath-lesson-meta__badge leaderspath-lesson-meta__badge--${data.difficulty}`;

    return (
      <div className="leaderspath-lesson-meta__section leaderspath-lesson-meta__difficulty">
        {elements.render({ attrName: 'difficultyLabel' })}
        <span className={badgeClass}>
          {data.difficulty_name}
        </span>
      </div>
    );
  };

  /**
   * Render the activity count section.
   */
  const renderActivityCount = (): ReactElement | null => {
    if (showActivities !== 'on') {
      return null;
    }

    const count = data?.activity_count ?? 0;
    const countText = count === 1
      ? __('1 activity', 'leaderspath')
      : `${count} ${__('activities', 'leaderspath')}`;

    return (
      <div className="leaderspath-lesson-meta__section leaderspath-lesson-meta__activities">
        {elements.render({ attrName: 'activitiesLabel' })}
        <span className="leaderspath-lesson-meta__value">
          {countText}
        </span>
      </div>
    );
  };

  /**
   * Render the content based on loading/error state.
   */
  const renderContent = (): ReactElement => {
    if (isLoading) {
      return (
        <div className="leaderspath-lesson-meta__placeholder">
          <p>{__('Loading lesson data...', 'leaderspath')}</p>
        </div>
      );
    }

    if (error) {
      return (
        <div className="leaderspath-lesson-meta__error">
          <p>{__('Error loading lesson data:', 'leaderspath')} {error}</p>
        </div>
      );
    }

    if (!data || !data.lesson_id) {
      return (
        <div className="leaderspath-lesson-meta__placeholder">
          <p>{__('No lesson data available. Create a Lesson to see this module in action.', 'leaderspath')}</p>
        </div>
      );
    }

    // Render the same structure as PHP render_callback.
    return (
      <div className="leaderspath-lesson-meta__content">
        {elements.render({ attrName: 'title' })}
        {renderDuration()}
        {renderDifficulty()}
        {renderActivityCount()}
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
