/**
 * Chatbot Module styles component for Visual Builder.
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

import { cssFields } from './custom-css';

/**
 * Chatbot Module styles component.
 *
 * Generates styles for the Visual Builder preview.
 *
 * @since 0.1.0
 *
 * @param {StylesProps} props Component props.
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
}: StylesProps): ReactElement => {
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

      {/* Messages container styles */}
      {elements.style({
        attrName: 'messages',
      })}

      {/* User bubble styles */}
      {elements.style({
        attrName: 'userBubble',
      })}

      {/* Assistant bubble styles */}
      {elements.style({
        attrName: 'assistantBubble',
      })}

      {/* Input field styles */}
      {elements.style({
        attrName: 'input',
      })}

      {/* Send button styles */}
      {elements.style({
        attrName: 'sendButton',
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
