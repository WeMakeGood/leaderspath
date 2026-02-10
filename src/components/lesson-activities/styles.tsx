/**
 * Lesson Activities Module styles component.
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

import { LessonActivitiesAttrs } from './types';
import { cssFields } from './custom-css';

/**
 * Generate styles for Lesson Activities Module.
 *
 * This function is equivalent of PHP function module_styles located in
 * modules/LessonActivities/LessonActivitiesTrait/ModuleStylesTrait.php.
 *
 * @since 0.1.0
 *
 * @param {StylesProps<LessonActivitiesAttrs>} props Style component arguments.
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
}: StylesProps<LessonActivitiesAttrs>): ReactElement => {
  return (
    <StyleContainer mode={mode} state={state} noStyleTag={noStyleTag}>
      {/* Module */}
      {elements.style({
        attrName: 'module',
        styleProps: {
          disabledOn: {
            disabledModuleVisibility: settings?.disabledModuleVisibility,
          },
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

      {/* List layout */}
      {elements.style({
        attrName: 'list',
      })}

      {/* Item card */}
      {elements.style({
        attrName: 'item',
      })}

      {/* Number badge */}
      {elements.style({
        attrName: 'numberBadge',
      })}

      {/* Link text */}
      {elements.style({
        attrName: 'link',
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
