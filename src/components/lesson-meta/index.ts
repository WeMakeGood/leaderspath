import { type Metadata, type ModuleLibrary } from '@divi/types';
import { LessonMetaEdit } from './edit';
import metadata from './module.json';
import { LessonMetaAttrs } from './types';
import { placeholderContent } from './placeholder-content';

import './module.scss';
import './style.scss';

export const lessonMeta: ModuleLibrary.Module.RegisterDefinition<LessonMetaAttrs> = {
    metadata: metadata as Metadata.Values<LessonMetaAttrs>,
    placeholderContent,
    renderers: {
        edit: LessonMetaEdit,
    },
};
