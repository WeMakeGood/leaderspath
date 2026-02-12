import { type Metadata, type ModuleLibrary } from '@divi/types';
import { SkillsListEdit } from './edit';
import metadata from './module.json';
import { SkillsListAttrs } from './types';
import { placeholderContent } from './placeholder-content';

import './module.scss';
import './style.scss';

export const skillsList: ModuleLibrary.Module.RegisterDefinition<SkillsListAttrs> = {
    metadata: metadata as Metadata.Values<SkillsListAttrs>,
    placeholderContent,
    renderers: {
        edit: SkillsListEdit,
    },
};
