import React, { ReactElement } from 'react';
import {
    ModuleContainer,
    ElementComponents,
} from '@divi/module';
import { __ } from '@wordpress/i18n';

import { SkillsListEditProps } from './types';
import { ModuleStyles } from './styles';
import { moduleClassnames } from './module-classnames';
import { ModuleScriptData } from './module-script-data';

/**
 * Skills List VB edit component.
 *
 * Renders placeholder skill cards with the same semantic structure as
 * SkillsListRenderer::render() so Divi design controls apply correctly.
 */
const SkillsListEdit = ({
    attrs,
    id,
    name,
    elements,
}: SkillsListEditProps): ReactElement => {
    const contentValues = attrs?.content?.innerContent?.desktop?.value ?? {};

    const showHeading     = (contentValues.showHeading ?? 'on') === 'on';
    const headingText     = contentValues.headingText ?? __('Skills', 'leaderspath');
    const showIcon        = (contentValues.showIcon ?? 'on') === 'on';
    const showDescription = (contentValues.showDescription ?? 'on') === 'on';
    const showMeta        = (contentValues.showMeta ?? 'on') === 'on';

    const gridLayout  = attrs?.grid?.decoration?.layout?.desktop?.value ?? {};
    const gridDisplay = gridLayout?.display ?? 'grid';
    const gridColumns = gridLayout?.gridColumnCount ?? '3';

    const iconColor = attrs?.cardIcon?.advanced?.color?.desktop?.value ?? '';
    const iconSize  = attrs?.cardIcon?.advanced?.size?.desktop?.value ?? '';
    const iconStyle: React.CSSProperties = {
        ...(iconColor ? { color: iconColor } : {}),
        ...(iconSize ? { fontSize: iconSize } : {}),
    };

    const placeholderSkills = [
        {
            title: __('Code Review Assistant', 'leaderspath'),
            description: __('Analyzes code submissions for best practices, potential bugs, and style consistency.', 'leaderspath'),
            compatibility: __('Python 3.9+', 'leaderspath'),
            version: '1.0.0',
        },
        {
            title: __('Writing Editor', 'leaderspath'),
            description: __('Provides constructive feedback on written content with grammar and clarity suggestions.', 'leaderspath'),
            compatibility: '',
            version: '2.1.0',
        },
        {
            title: __('Data Analyzer', 'leaderspath'),
            description: __('Processes CSV data files and generates statistical summaries with visualizations.', 'leaderspath'),
            compatibility: __('Python 3.9+, pandas', 'leaderspath'),
            version: '1.3.0',
        },
    ];

    // Gear/cog icon for skills.
    const skillIconPath = 'M19.14 12.94c.04-.3.06-.61.06-.94 0-.32-.02-.64-.07-.94l2.03-1.58a.49.49 0 00.12-.61l-1.92-3.32a.488.488 0 00-.59-.22l-2.39.96c-.5-.38-1.03-.7-1.62-.94l-.36-2.54c-.04-.24-.24-.41-.48-.41h-3.84c-.24 0-.44.17-.47.41l-.36 2.54c-.59.24-1.13.57-1.62.94l-2.39-.96a.49.49 0 00-.59.22L2.74 8.87c-.12.21-.08.47.12.61l2.03 1.58c-.05.3-.09.63-.09.94s.02.64.07.94l-2.03 1.58a.49.49 0 00-.12.61l1.92 3.32c.12.22.37.29.59.22l2.39-.96c.5.38 1.03.7 1.62.94l.36 2.54c.05.24.24.41.48.41h3.84c.24 0 .44-.17.47-.41l.36-2.54c.59-.24 1.13-.56 1.62-.94l2.39.96c.22.08.47 0 .59-.22l1.92-3.32c.12-.22.07-.47-.12-.61l-2.01-1.58zM12 15.6c-1.98 0-3.6-1.62-3.6-3.6s1.62-3.6 3.6-3.6 3.6 1.62 3.6 3.6-1.62 3.6-3.6 3.6z';

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
                <h3 className="leaderspath_skills_list__heading">
                    {headingText}
                </h3>
            )}
            <ul className="leaderspath_skills_list__grid" style={{
                display: gridDisplay,
                ...(gridDisplay === 'grid' ? { gridTemplateColumns: `repeat(${gridColumns}, 1fr)` } : {}),
            }}>
                {placeholderSkills.map((skill, index) => (
                    <li key={index} className="leaderspath_skills_list__item">
                        <article className="leaderspath_skills_list__card">
                            {showIcon && (
                                <div className="leaderspath_skills_list__card_icon" style={iconStyle}>
                                    <svg viewBox="0 0 24 24" width="1em" height="1em" fill="currentColor">
                                        <path d={skillIconPath} />
                                    </svg>
                                </div>
                            )}
                            <h4 className="leaderspath_skills_list__card_title">
                                {skill.title}
                            </h4>
                            {showDescription && (
                                <p className="leaderspath_skills_list__card_desc">
                                    {skill.description}
                                </p>
                            )}
                            {showMeta && (skill.compatibility || skill.version) && (
                                <div className="leaderspath_skills_list__card_meta">
                                    {skill.compatibility && (
                                        <span className="leaderspath_skills_list__compat">
                                            {skill.compatibility}
                                        </span>
                                    )}
                                    {skill.version && (
                                        <span className="leaderspath_skills_list__version">
                                            v{skill.version}
                                        </span>
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

export { SkillsListEdit };
