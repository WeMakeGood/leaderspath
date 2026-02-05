/**
 * Activity Meta Module edit component for Visual Builder.
 *
 * Fetches activity metadata via REST API and renders using the same
 * HTML structure as the PHP render_callback, ensuring VB preview
 * matches the frontend output.
 *
 * @package LeadersPath
 * @since 0.1.0
 */

import React, { ReactElement } from 'react';
import { ModuleContainer } from '@divi/module';
import { __ } from '@wordpress/i18n';

import { ActivityMetaEditProps } from './types';
import { ModuleStyles } from './styles';
import { moduleClassnames } from './module-classnames';
import { useActivityMeta } from './use-activity-meta';

/**
 * Activity Meta Module edit component.
 *
 * Fetches real activity data via REST API and renders it in the VB,
 * ensuring the preview matches the frontend output.
 *
 * @since 0.1.0
 *
 * @param {ActivityMetaEditProps} props React component props.
 * @returns {ReactElement}
 */
export const ActivityMetaEdit = (props: ActivityMetaEditProps): ReactElement => {
  const {
    attrs,
    elements,
    id,
    name,
  } = props;

  // Fetch activity metadata from REST API.
  const { data, isLoading, error } = useActivityMeta();

  // Get visibility toggles from attrs.
  const showDuration = attrs?.duration?.advanced?.show?.desktop?.value ?? 'on';
  const showModel = attrs?.model?.advanced?.show?.desktop?.value ?? 'on';

  /**
   * Render the duration section.
   */
  const renderDuration = (): ReactElement | null => {
    if (showDuration !== 'on' || !data?.duration) {
      return null;
    }

    return (
      <div className="leaderspath-activity-meta__section leaderspath-activity-meta__duration">
        {elements.render({ attrName: 'durationLabel' })}
        <span className="leaderspath-activity-meta__value">
          {data.duration_text}
        </span>
      </div>
    );
  };

  /**
   * Render the AI model section.
   */
  const renderModel = (): ReactElement | null => {
    // Only show if visibility is on, chatbot is enabled, and model is set.
    if (showModel !== 'on' || !data?.chatbot_enabled || !data?.model) {
      return null;
    }

    return (
      <div className="leaderspath-activity-meta__section leaderspath-activity-meta__model">
        {elements.render({ attrName: 'modelLabel' })}
        <span className="leaderspath-activity-meta__value leaderspath-activity-meta__badge">
          {data.model_name}
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
        <div className="leaderspath-activity-meta__placeholder">
          <p>{__('Loading activity data...', 'leaderspath')}</p>
        </div>
      );
    }

    if (error) {
      return (
        <div className="leaderspath-activity-meta__error">
          <p>{__('Error loading activity data:', 'leaderspath')} {error}</p>
        </div>
      );
    }

    if (!data || !data.activity_id) {
      return (
        <div className="leaderspath-activity-meta__placeholder">
          <p>{__('No activity data available. Create an Activity to see this module in action.', 'leaderspath')}</p>
        </div>
      );
    }

    // Render the same structure as PHP render_callback.
    return (
      <div className="leaderspath-activity-meta__content">
        {elements.render({ attrName: 'title' })}
        {renderDuration()}
        {renderModel()}
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
