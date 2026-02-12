import React, { ReactElement } from 'react';
import {
    ModuleContainer,
    ElementComponents,
} from '@divi/module';
import { __ } from '@wordpress/i18n';

import { ContextLibraryEditProps } from './types';
import { ModuleStyles } from './styles';
import { moduleClassnames } from './module-classnames';
import { ModuleScriptData } from './module-script-data';

/**
 * Context Library VB edit component.
 *
 * Renders placeholder context file cards with the same semantic structure as
 * ContextLibraryRenderer::render() so Divi design controls apply correctly.
 * Modal is not rendered in VB — it's a frontend-only interactive feature.
 */
const ContextLibraryEdit = ({
    attrs,
    id,
    name,
    elements,
}: ContextLibraryEditProps): ReactElement => {
    const contentValues = attrs?.content?.innerContent?.desktop?.value ?? {};

    const showHeading     = (contentValues.showHeading ?? 'on') === 'on';
    const headingText     = contentValues.headingText ?? __('Context Library', 'leaderspath');
    const showIcon        = (contentValues.showIcon ?? 'on') === 'on';
    const showDescription = (contentValues.showDescription ?? 'on') === 'on';
    const showMeta        = (contentValues.showMeta ?? 'on') === 'on';
    const showView        = (contentValues.showView ?? 'on') === 'on';
    const showDownload    = (contentValues.showDownload ?? 'on') === 'on';

    const gridLayout  = attrs?.grid?.decoration?.layout?.desktop?.value ?? {};
    const gridDisplay = gridLayout?.display ?? 'grid';
    const gridColumns = gridLayout?.gridColumnCount ?? '3';

    const iconColor = attrs?.cardIcon?.advanced?.color?.desktop?.value ?? '';
    const iconSize  = attrs?.cardIcon?.advanced?.size?.desktop?.value ?? '';
    const iconStyle: React.CSSProperties = {
        ...(iconColor ? { color: iconColor } : {}),
        ...(iconSize ? { fontSize: iconSize } : {}),
    };

    const placeholderContextFiles = [
        {
            title: __('AI Ethics Guidelines', 'leaderspath'),
            description: __('Comprehensive ethical framework for AI interactions in educational settings.', 'leaderspath'),
            typeLabel: __('Knowledge Base', 'leaderspath'),
            type: 'knowledge_base',
            version: '1.2.0',
        },
        {
            title: __('Prompt Engineering Reference', 'leaderspath'),
            description: __('Best practices and patterns for crafting effective AI prompts.', 'leaderspath'),
            typeLabel: __('Instructions', 'leaderspath'),
            type: 'instructions',
            version: '2.0.0',
        },
        {
            title: __('Conversation Examples', 'leaderspath'),
            description: __('Sample conversations demonstrating context-aware AI interactions.', 'leaderspath'),
            typeLabel: __('Examples', 'leaderspath'),
            type: 'examples',
            version: '1.0.0',
        },
    ];

    const typeIcons: Record<string, string> = {
        knowledge_base: 'M21 5c-1.11-.35-2.33-.5-3.5-.5-1.95 0-4.05.4-5.5 1.5-1.45-1.1-3.55-1.5-5.5-1.5S2.45 4.9 1 6v14.65c0 .25.25.5.5.5.1 0 .15-.05.25-.05C3.1 20.45 5.05 20 6.5 20c1.95 0 4.05.4 5.5 1.5 1.35-.85 3.8-1.5 5.5-1.5 1.65 0 3.35.3 4.75 1.05.1.05.15.05.25.05.25 0 .5-.25.5-.5V6c-.6-.45-1.25-.75-2-1zm0 13.5c-1.1-.35-2.3-.5-3.5-.5-1.7 0-4.15.65-5.5 1.5V8c1.35-.85 3.8-1.5 5.5-1.5 1.2 0 2.4.15 3.5.5v11.5z',
        instructions: 'M14 2H6c-1.1 0-1.99.9-1.99 2L4 20c0 1.1.89 2 1.99 2H18c1.1 0 2-.9 2-2V8l-6-6zm2 16H8v-2h8v2zm0-4H8v-2h8v2zm-3-5V3.5L18.5 9H13z',
        examples: 'M9.4 16.6L4.8 12l4.6-4.6L8 6l-6 6 6 6 1.4-1.4zm5.2 0l4.6-4.6-4.6-4.6L16 6l6 6-6 6-1.4-1.4z',
    };

    return (
        <ModuleContainer
            attrs={attrs}
            elements={elements}
            id={id}
            name={name}
            stylesComponent={ModuleStyles}
            classnamesFunction={moduleClassnames}
            scriptDataComponent={ModuleScriptData}
        >
            {elements.styleComponents({ attrName: 'module' })}
            <ElementComponents
                attrs={attrs?.module?.decoration ?? {}}
                id={id}
            />
            {showHeading && (
                <h3 className="leaderspath_context_library__heading">
                    {headingText}
                </h3>
            )}
            <ul className="leaderspath_context_library__grid" style={{
                display: gridDisplay,
                ...(gridDisplay === 'grid' ? { gridTemplateColumns: `repeat(${gridColumns}, 1fr)` } : {}),
            }}>
                {placeholderContextFiles.map((file, index) => (
                    <li key={index} className="leaderspath_context_library__item">
                        <article className="leaderspath_context_library__card">
                            {showIcon && (
                                <div className="leaderspath_context_library__card_icon" data-type={file.type} style={iconStyle}>
                                    <svg viewBox="0 0 24 24" width="1em" height="1em" fill="currentColor">
                                        <path d={typeIcons[file.type] || typeIcons.instructions} />
                                    </svg>
                                </div>
                            )}
                            <h4 className="leaderspath_context_library__card_title">
                                {file.title}
                            </h4>
                            {showDescription && (
                                <p className="leaderspath_context_library__card_desc">
                                    {file.description}
                                </p>
                            )}
                            {showMeta && (
                                <div className="leaderspath_context_library__card_meta">
                                    <span className="leaderspath_context_library__badge" data-type={file.type}>
                                        {file.typeLabel}
                                    </span>
                                    <span className="leaderspath_context_library__version">
                                        v{file.version}
                                    </span>
                                </div>
                            )}
                            {(showView || showDownload) && (
                                <div className="leaderspath_context_library__actions">
                                    {showView && (
                                        <button
                                            type="button"
                                            className="leaderspath_context_library__view"
                                            onClick={(e) => e.preventDefault()}
                                        >
                                            {__('View', 'leaderspath')}
                                        </button>
                                    )}
                                    {showDownload && (
                                        <button
                                            type="button"
                                            className="leaderspath_context_library__download"
                                            onClick={(e) => e.preventDefault()}
                                        >
                                            {__('Download', 'leaderspath')}
                                        </button>
                                    )}
                                </div>
                            )}
                        </article>
                    </li>
                ))}
            </ul>
        </ModuleContainer>
    );
};

export { ContextLibraryEdit };
