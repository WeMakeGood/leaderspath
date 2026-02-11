import { type Metadata, type ModuleLibrary } from '@divi/types';
import { LessonObjectivesEdit } from './edit';
import metadata from './module.json';
import { LessonObjectivesAttrs } from './types';
import { placeholderContent } from './placeholder-content';

import './module.scss';
import './style.scss';

export const lessonObjectives: ModuleLibrary.Module.RegisterDefinition<LessonObjectivesAttrs> = {
    metadata: metadata as Metadata.Values<LessonObjectivesAttrs>,
    placeholderContent,
    renderers: {
        edit: LessonObjectivesEdit,
    },
};
