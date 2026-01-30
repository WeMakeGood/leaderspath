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

  // Get visibility toggle values.
  const showDuration = attrs?.showDuration?.innerContent?.desktop?.value ?? 'on';
  const showObjectives = attrs?.showObjectives?.innerContent?.desktop?.value ?? 'on';
  const showModel = attrs?.showModel?.innerContent?.desktop?.value ?? 'on';

  // Get label values for placeholder display.
  const durationLabel = attrs?.durationLabel?.innerContent?.desktop?.value ?? 'Duration:';
  const objectivesLabel = attrs?.objectivesLabel?.innerContent?.desktop?.value ?? 'Learning Objectives:';
  const modelLabel = attrs?.modelLabel?.innerContent?.desktop?.value ?? 'AI Model:';

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
            <span className="leaderspath-lesson-meta__label">{durationLabel}</span>
            <span className="leaderspath-lesson-meta__value">45 minutes</span>
          </div>
        )}

        {/* Objectives Section - Placeholder */}
        {showObjectives === 'on' && (
          <div className="leaderspath-lesson-meta__section leaderspath-lesson-meta__objectives">
            <span className="leaderspath-lesson-meta__label">{objectivesLabel}</span>
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
            <span className="leaderspath-lesson-meta__label">{modelLabel}</span>
            <span className="leaderspath-lesson-meta__value leaderspath-lesson-meta__badge">
              Claude Sonnet
            </span>
          </div>
        )}
      </div>
    </ModuleContainer>
  );
};
