/**
 * Context Library Module edit component for Visual Builder.
 *
 * @package LeadersPath
 * @since 0.1.0
 */

import React, { ReactElement } from 'react';
import { ModuleContainer } from '@divi/module';

import { ContextLibraryEditProps, ContextFileItem } from './types';
import { ModuleStyles } from './styles';
import { moduleClassnames } from './module-classnames';

/**
 * Placeholder context files for Visual Builder preview.
 *
 * These demonstrate the layout since ACF data isn't available in VB context.
 */
const placeholderFiles: ContextFileItem[] = [
  {
    id: 1,
    title: 'Organization Profile',
    description: 'Company background, mission, and values that inform AI responses.',
    fileType: 'Knowledge Base',
  },
  {
    id: 2,
    title: 'Brand Guidelines',
    description: 'Voice, tone, and messaging standards for consistent communication.',
    fileType: 'Instructions',
  },
  {
    id: 3,
    title: 'Product Documentation',
    description: 'Technical specifications and features for accurate assistance.',
    fileType: 'Knowledge Base',
  },
];

/**
 * Context Library Module edit component.
 *
 * This component renders the module in the Divi Visual Builder.
 * Since ACF data isn't available in the VB context, we show placeholder
 * data that demonstrates the layout.
 *
 * @since 0.1.0
 *
 * @param {ContextLibraryEditProps} props React component props.
 * @returns {ReactElement}
 */
export const ContextLibraryEdit = (props: ContextLibraryEditProps): ReactElement => {
  const {
    attrs,
    elements,
    id,
    name,
  } = props;

  // Get visibility toggle values.
  const showDescription = attrs?.contextFiles?.advanced?.showDescription?.desktop?.value ?? 'on';
  const showFileType = attrs?.contextFiles?.advanced?.showFileType?.desktop?.value ?? 'on';
  const showViewButton = attrs?.contextFiles?.advanced?.showViewButton?.desktop?.value ?? 'on';
  const showDownloadButton = attrs?.contextFiles?.advanced?.showDownloadButton?.desktop?.value ?? 'on';

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
      <div className="leaderspath-context-library__content">
        {elements.render({
          attrName: 'title',
        })}

        <div className="leaderspath-context-library__grid">
          {placeholderFiles.map((file) => (
            <div
              key={file.id}
              className="leaderspath-context-library__item"
              data-file-id={file.id}
            >
              <div className="leaderspath-context-library__item-header">
                <h4 className="leaderspath-context-library__item-title">
                  {file.title}
                </h4>
                {showFileType === 'on' && (
                  <span className="leaderspath-context-library__item-badge">
                    {file.fileType}
                  </span>
                )}
              </div>

              {showDescription === 'on' && file.description && (
                <p className="leaderspath-context-library__item-description">
                  {file.description}
                </p>
              )}

              {(showViewButton === 'on' || showDownloadButton === 'on') && (
                <div className="leaderspath-context-library__item-buttons">
                  {showViewButton === 'on' && (
                    <button
                      type="button"
                      className="leaderspath-context-library__button leaderspath-context-library__view-button et_pb_button"
                    >
                      View Content
                    </button>
                  )}
                  {showDownloadButton === 'on' && (
                    <a
                      href="#"
                      className="leaderspath-context-library__button leaderspath-context-library__download-button et_pb_button"
                    >
                      Download
                    </a>
                  )}
                </div>
              )}
            </div>
          ))}
        </div>
      </div>
    </ModuleContainer>
  );
};
