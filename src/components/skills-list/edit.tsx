/**
 * Skills List Module edit component for Visual Builder.
 *
 * @package LeadersPath
 * @since 0.1.0
 */

import React, { ReactElement } from 'react';
import { ModuleContainer } from '@divi/module';

import { SkillsListEditProps, SkillItem } from './types';
import { ModuleStyles } from './styles';
import { moduleClassnames } from './module-classnames';

/**
 * Placeholder skills for Visual Builder preview.
 *
 * These demonstrate the layout since ACF data isn't available in VB context.
 */
const placeholderSkills: SkillItem[] = [
  {
    id: 1,
    title: 'Code Review',
    name: 'Code Review',
    description: 'Analyzes code for quality, security, and best practices. Provides structured feedback.',
    compatibility: 'Python 3.9+',
    version: '1.2.0',
    packageUrl: '#',
  },
  {
    id: 2,
    title: 'Writing Editor',
    name: 'Writing Editor',
    description: 'Helps improve writing clarity, tone, and structure. Supports multiple formats.',
    compatibility: '',
    version: '2.0.0',
    packageUrl: '#',
  },
  {
    id: 3,
    title: 'Data Analysis',
    name: 'Data Analysis',
    description: 'Processes datasets and generates insights with visualizations.',
    compatibility: 'Python 3.9+, pandas',
    version: '1.0.0',
    packageUrl: '#',
  },
];

/**
 * Skills List Module edit component.
 *
 * This component renders the module in the Divi Visual Builder.
 * Since ACF data isn't available in the VB context, we show placeholder
 * data that demonstrates the layout.
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

  // Get visibility toggle values.
  const showDescription = attrs?.skills?.advanced?.showDescription?.desktop?.value ?? 'on';
  const showCompatibility = attrs?.skills?.advanced?.showCompatibility?.desktop?.value ?? 'on';
  const showVersion = attrs?.skills?.advanced?.showVersion?.desktop?.value ?? 'on';
  const showDownloadButton = attrs?.skills?.advanced?.showDownloadButton?.desktop?.value ?? 'on';

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
      <div className="leaderspath-skills-list__content">
        {elements.render({
          attrName: 'title',
        })}

        <div className="leaderspath-skills-list__grid">
          {placeholderSkills.map((skill) => (
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

              {showDownloadButton === 'on' && (
                <div className="leaderspath-skills-list__item-buttons">
                  <a
                    href="#"
                    className="leaderspath-skills-list__button leaderspath-skills-list__download-button et_pb_button"
                  >
                    Download Skill
                  </a>
                </div>
              )}
            </div>
          ))}
        </div>
      </div>
    </ModuleContainer>
  );
};
