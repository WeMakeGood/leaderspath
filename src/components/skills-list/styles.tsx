/**
 * Skills List Module styles component for Visual Builder.
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

import { SkillsListAttrs } from './types';
import { cssFields } from './custom-css';

/**
 * Skills List Module styles component.
 *
 * Generates styles for the Visual Builder preview.
 *
 * @since 0.1.0
 *
 * @param {StylesProps<SkillsListAttrs>} props Component props.
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
}: StylesProps<SkillsListAttrs>): ReactElement => {
  return (
    <StyleContainer mode={mode} state={state} noStyleTag={noStyleTag}>
      {/* Module container styles */}
      {elements.style({
        attrName: 'module',
        styleProps: {
          disabledOn: {
            disabledModuleVisibility: settings?.disabledModuleVisibility,
          },
        },
      })}

      {/* Module title styles */}
      {elements.style({
        attrName: 'title',
      })}

      {/* Empty state styles */}
      {elements.style({
        attrName: 'emptyState',
      })}

      {/* Item container styles */}
      {elements.style({
        attrName: 'item',
      })}

      {/* Item title styles */}
      {elements.style({
        attrName: 'itemTitle',
      })}

      {/* Item description styles */}
      {elements.style({
        attrName: 'itemDescription',
      })}

      {/* Item badge styles */}
      {elements.style({
        attrName: 'itemBadge',
      })}

      {/* Download button styles */}
      {elements.style({
        attrName: 'downloadButton',
      })}

      {/* Custom CSS */}
      <CssStyle
        selector={orderClass}
        attr={attrs?.css}
        cssFields={cssFields}
      />
    </StyleContainer>
  );
};
