/**
 * Hello Module edit component for Visual Builder.
 *
 * @package LeadersPath
 * @since 0.1.0
 */

import React, { ReactElement } from 'react';
import { ModuleContainer } from '@divi/module';

import { HelloModuleEditProps } from './types';
import { ModuleStyles } from './styles';
import { moduleClassnames } from './module-classnames';

/**
 * Hello Module edit component.
 *
 * This component renders the module in the Divi Visual Builder.
 *
 * @since 0.1.0
 *
 * @param {HelloModuleEditProps} props React component props.
 * @returns {ReactElement}
 */
export const HelloModuleEdit = (props: HelloModuleEditProps): ReactElement => {
  const {
    attrs,
    elements,
    id,
    name,
  } = props;

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
      <div className="leaderspath-hello__content">
        {elements.render({
          attrName: 'title',
        })}
        {elements.render({
          attrName: 'message',
        })}
      </div>
    </ModuleContainer>
  );
};
