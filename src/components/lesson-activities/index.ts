import { type Metadata, type ModuleLibrary } from '@divi/types';
import { LessonActivitiesEdit } from './edit';
import metadata from './module.json';
import { LessonActivitiesAttrs } from './types';
import { placeholderContent } from './placeholder-content';

import './module.scss';
import './style.scss';

export const lessonActivities: ModuleLibrary.Module.RegisterDefinition<LessonActivitiesAttrs> = {
    metadata: metadata as Metadata.Values<LessonActivitiesAttrs>,
    placeholderContent,
    renderers: {
        edit: LessonActivitiesEdit,
    },
};
