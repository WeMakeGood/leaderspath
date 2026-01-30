/**
 * Hello Module styles component.
 *
 * @package LeadersPath
 * @since 0.1.0
 */

import React, { ReactElement } from 'react';
import { type ModuleLibrary } from '@divi/types';
import { CssStyle } from '@divi/module';

import { HelloModuleAttrs } from './types';

/**
 * Generate styles for Hello Module.
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
}: ModuleLibrary.Module.Styles.Args<HelloModuleAttrs>): ReactElement => {
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

      {/* Message styles */}
      {elements.style({
        attrName: 'message',
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
