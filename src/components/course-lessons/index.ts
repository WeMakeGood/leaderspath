import { type Metadata, type ModuleLibrary } from '@divi/types';
import { CourseLessonsEdit } from './edit';
import metadata from './module.json';
import { CourseLessonsAttrs } from './types';
import { placeholderContent } from './placeholder-content';

import './module.scss';
import './style.scss';

export const courseLessons: ModuleLibrary.Module.RegisterDefinition<CourseLessonsAttrs> = {
    metadata: metadata as Metadata.Values<CourseLessonsAttrs>,
    placeholderContent,
    renderers: {
        edit: CourseLessonsEdit,
    },
};
