/**
 * Lesson Meta Module edit component for Visual Builder.
 *
 * @package LeadersPath
 * @since 0.1.0
 */

import React, { ReactElement } from 'react';
import { ModuleContainer } from '@divi/module';

import { LessonMetaEditProps } from './types';
import { ModuleStyles } from './styles';
import { moduleClassnames } from './module-classnames';

/**
 * Lesson Meta Module edit component.
 *
 * This component renders the module in the Divi Visual Builder.
 * Since ACF data isn't available in the VB context, we show placeholder
 * data that demonstrates the layout.
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

  // Get visibility toggle values following PostTitle pattern.
  const showDuration = attrs?.duration?.advanced?.show?.desktop?.value ?? 'on';
  const showObjectives = attrs?.objectives?.advanced?.show?.desktop?.value ?? 'on';
  const showModel = attrs?.model?.advanced?.show?.desktop?.value ?? 'on';

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
      <div className="leaderspath-lesson-meta__content">
        {elements.render({
          attrName: 'title',
        })}

        {/* Duration Section - Placeholder */}
        {showDuration === 'on' && (
          <div className="leaderspath-lesson-meta__section leaderspath-lesson-meta__duration">
            {elements.render({
              attrName: 'durationLabel',
            })}
            <span className="leaderspath-lesson-meta__value">45 minutes</span>
          </div>
        )}

        {/* Objectives Section - Placeholder */}
        {showObjectives === 'on' && (
          <div className="leaderspath-lesson-meta__section leaderspath-lesson-meta__objectives">
            {elements.render({
              attrName: 'objectivesLabel',
            })}
            <ul className="leaderspath-lesson-meta__list">
              <li>Understand the key concepts</li>
              <li>Apply learning in practical scenarios</li>
              <li>Evaluate outcomes effectively</li>
            </ul>
          </div>
        )}

        {/* Model Section - Placeholder */}
        {showModel === 'on' && (
          <div className="leaderspath-lesson-meta__section leaderspath-lesson-meta__model">
            {elements.render({
              attrName: 'modelLabel',
            })}
            <span className="leaderspath-lesson-meta__value leaderspath-lesson-meta__badge">
              Claude Sonnet
            </span>
          </div>
        )}
      </div>
    </ModuleContainer>
  );
};
