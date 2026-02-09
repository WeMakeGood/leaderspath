/**
 * Lesson Objectives Module styles component.
 *
 * @package LeadersPath
 * @since 0.1.0
 */

import React, { ReactElement } from 'react';
import {
  StyleContainer,
  StylesProps,
  CssStyle,
} from '@divi/module';

import { LessonObjectivesAttrs } from './types';
import { cssFields } from './custom-css';

/**
 * Generate styles for Lesson Objectives Module.
 *
 * This function is equivalent of PHP function module_styles located in
 * modules/LessonObjectives/LessonObjectivesTrait/ModuleStylesTrait.php.
 *
 * @since 0.1.0
 *
 * @param {StylesProps<LessonObjectivesAttrs>} props Style component arguments.
 * @returns {ReactElement}
 */
export const ModuleStyles = ({
  attrs,
  elements,
  settings,
  orderClass,
  mode,
  state,
  noStyleTag,
}: StylesProps<LessonObjectivesAttrs>): ReactElement => {
  const textSelector = `${orderClass} .leaderspath-lesson-objectives__content`;

  return (
    <StyleContainer mode={mode} state={state} noStyleTag={noStyleTag}>
      {/* Module */}
      {elements.style({
        attrName: 'module',
        styleProps: {
          disabledOn: {
            disabledModuleVisibility: settings?.disabledModuleVisibility,
          },
          advancedStyles: [
            {
              componentName: 'divi/text',
              props: {
                selector: textSelector,
                attr: attrs?.module?.advanced?.text,
              },
            },
          ],
        },
      })}

      {/* Title */}
      {elements.style({
        attrName: 'title',
      })}

      {/* Empty State */}
      {elements.style({
        attrName: 'emptyState',
      })}

      {/* Custom CSS - must be last so it can override module styles */}
      <CssStyle
        selector={orderClass}
        attr={attrs?.css}
        cssFields={cssFields}
      />
    </StyleContainer>
  );
};
