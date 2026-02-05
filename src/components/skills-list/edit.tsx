/**
 * Skills List Module edit component for Visual Builder.
 *
 * Fetches skills via REST API and renders using the same
 * HTML structure as the PHP render_callback, ensuring VB preview
 * matches the frontend output.
 *
 * @package LeadersPath
 * @since 0.1.0
 */

import React, { ReactElement } from 'react';
import { ModuleContainer } from '@divi/module';
import { __ } from '@wordpress/i18n';

import { SkillsListEditProps } from './types';
import { ModuleStyles } from './styles';
import { moduleClassnames } from './module-classnames';
import { useSkills, Skill } from './use-skills';

/**
 * Skills List Module edit component.
 *
 * Fetches real skills via REST API and renders it in the VB,
 * ensuring the preview matches the frontend output.
 *
 * @since 0.1.0
 *
 * @param {SkillsListEditProps} props React component props.
 * @returns {ReactElement}
 */
export const SkillsListEdit = (props: SkillsListEditProps): ReactElement => {
  const {
    attrs,
    elements,
    id,
    name,
  } = props;

  // Fetch skills from REST API.
  const { data: skills, isLoading, error } = useSkills();

  // Get visibility toggle values.
  const showDescription = attrs?.skills?.advanced?.showDescription?.desktop?.value ?? 'on';
  const showCompatibility = attrs?.skills?.advanced?.showCompatibility?.desktop?.value ?? 'on';
  const showVersion = attrs?.skills?.advanced?.showVersion?.desktop?.value ?? 'on';
  const showDownloadButton = attrs?.skills?.advanced?.showDownloadButton?.desktop?.value ?? 'on';

  /**
   * Render a single skill item.
   */
  const renderItem = (skill: Skill): ReactElement => {
    return (
      <div
        key={skill.id}
        className="leaderspath-skills-list__item"
        data-skill-id={skill.id}
      >
        <div className="leaderspath-skills-list__item-header">
          <h4 className="leaderspath-skills-list__item-title">
            {skill.name}
          </h4>
          {(showCompatibility === 'on' || showVersion === 'on') && (
            <div className="leaderspath-skills-list__item-badges">
              {showCompatibility === 'on' && skill.compatibility && (
                <span className="leaderspath-skills-list__item-badge leaderspath-skills-list__item-badge--compatibility">
                  {skill.compatibility}
                </span>
              )}
              {showVersion === 'on' && skill.version && (
                <span className="leaderspath-skills-list__item-badge leaderspath-skills-list__item-badge--version">
                  v{skill.version}
                </span>
              )}
            </div>
          )}
        </div>

        {showDescription === 'on' && skill.description && (
          <p className="leaderspath-skills-list__item-description">
            {skill.description}
          </p>
        )}

        {showDownloadButton === 'on' && skill.download && (
          <div className="leaderspath-skills-list__item-buttons">
            <a
              href={skill.download}
              className="leaderspath-skills-list__button leaderspath-skills-list__download-button et_pb_button"
              download
            >
              {__('Download Skill', 'leaderspath')}
            </a>
          </div>
        )}
      </div>
    );
  };

  /**
   * Render the content based on loading/error state.
   */
  const renderContent = (): ReactElement => {
    if (isLoading) {
      return (
        <div className="leaderspath-skills-list__content">
          {elements.render({ attrName: 'title' })}
          <div className="leaderspath-skills-list__placeholder">
            <p>{__('Loading skills...', 'leaderspath')}</p>
          </div>
        </div>
      );
    }

    if (error) {
      return (
        <div className="leaderspath-skills-list__content">
          {elements.render({ attrName: 'title' })}
          <div className="leaderspath-skills-list__error">
            <p>{__('Error loading skills:', 'leaderspath')} {error}</p>
          </div>
        </div>
      );
    }

    if (skills.length === 0) {
      return (
        <div className="leaderspath-skills-list__content">
          {elements.render({ attrName: 'title' })}
          <div className="leaderspath-skills-list__empty">
            {elements.render({ attrName: 'emptyState' })}
          </div>
        </div>
      );
    }

    // Render the same structure as PHP render_callback.
    return (
      <div className="leaderspath-skills-list__content">
        {elements.render({ attrName: 'title' })}
        <div className="leaderspath-skills-list__grid">
          {skills.map(renderItem)}
        </div>
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
