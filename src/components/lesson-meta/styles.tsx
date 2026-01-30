/**
 * Lesson Meta Module styles component.
 *
 * @package LeadersPath
 * @since 0.1.0
 */

import React, { ReactElement } from 'react';
import { type ModuleLibrary } from '@divi/types';
import { CssStyle } from '@divi/module';

import { LessonMetaAttrs } from './types';

/**
 * Generate styles for Lesson Meta Module.
 *
 * @since 0.1.0
 *
 * @param {object} args Style component arguments.
 * @returns {ReactElement}
 */
export const ModuleStyles = ({
  attrs,
  elements,
  settings,
  selector,
  cssFields,
}: ModuleLibrary.Module.Styles.Args<LessonMetaAttrs>): ReactElement => {
  return (
    <>
      {/* Module styles */}
      {elements.style({
        attrName: 'module',
        styleProps: {
          disabledOn: {
            disabledModuleVisibility: settings?.disabledModuleVisibility,
          },
        },
      })}

      {/* Title styles */}
      {elements.style({
        attrName: 'title',
      })}

      {/* Custom CSS */}
      <CssStyle
        selector={selector}
        attr={attrs?.css}
        cssFields={cssFields}
      />
    </>
  );
};
