/**
 * Context Library Module edit component for Visual Builder.
 *
 * Fetches context files via REST API and renders using the same
 * HTML structure as the PHP render_callback, ensuring VB preview
 * matches the frontend output.
 *
 * @package LeadersPath
 * @since 0.1.0
 */

import React, { ReactElement } from 'react';
import { ModuleContainer } from '@divi/module';
import { __ } from '@wordpress/i18n';

import { ContextLibraryEditProps } from './types';
import { ModuleStyles } from './styles';
import { moduleClassnames } from './module-classnames';
import { useContextFiles, ContextFile } from './use-context-files';

/**
 * Context Library Module edit component.
 *
 * Fetches real context files via REST API and renders it in the VB,
 * ensuring the preview matches the frontend output.
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

  // Fetch context files from REST API.
  const { data: contextFiles, isLoading, error } = useContextFiles();

  // Get visibility toggle values.
  const showDescription = attrs?.contextFiles?.advanced?.showDescription?.desktop?.value ?? 'on';
  const showFileType = attrs?.contextFiles?.advanced?.showFileType?.desktop?.value ?? 'on';
  const showViewButton = attrs?.contextFiles?.advanced?.showViewButton?.desktop?.value ?? 'on';
  const showDownloadButton = attrs?.contextFiles?.advanced?.showDownloadButton?.desktop?.value ?? 'on';

  /**
   * Render a single context file item.
   */
  const renderItem = (file: ContextFile): ReactElement => {
    return (
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
              {file.file_type_label}
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
                data-file-id={file.id}
              >
                {__('View Content', 'leaderspath')}
              </button>
            )}
            {showDownloadButton === 'on' && (
              <a
                href={file.download}
                className="leaderspath-context-library__button leaderspath-context-library__download-button et_pb_button"
                target="_blank"
                rel="noopener noreferrer"
              >
                {__('Download', 'leaderspath')}
              </a>
            )}
          </div>
        )}
      </div>
    );
  };

  /**
   * Render the content based on loading/error state.
   */
  const renderContent = (): ReactElement => {
    if (isLoading) {
      return (
        <div className="leaderspath-context-library__content">
          {elements.render({ attrName: 'title' })}
          <div className="leaderspath-context-library__placeholder">
            <p>{__('Loading context files...', 'leaderspath')}</p>
          </div>
        </div>
      );
    }

    if (error) {
      return (
        <div className="leaderspath-context-library__content">
          {elements.render({ attrName: 'title' })}
          <div className="leaderspath-context-library__error">
            <p>{__('Error loading context files:', 'leaderspath')} {error}</p>
          </div>
        </div>
      );
    }

    if (contextFiles.length === 0) {
      return (
        <div className="leaderspath-context-library__content">
          {elements.render({ attrName: 'title' })}
          <div className="leaderspath-context-library__empty">
            {elements.render({ attrName: 'emptyState' })}
          </div>
        </div>
      );
    }

    // Render the same structure as PHP render_callback.
    return (
      <div className="leaderspath-context-library__content">
        {elements.render({ attrName: 'title' })}
        <div className="leaderspath-context-library__grid">
          {contextFiles.map(renderItem)}
        </div>
      </div>
    );
  };

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
      {renderContent()}
    </ModuleContainer>
  );
};
