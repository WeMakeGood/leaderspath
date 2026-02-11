import { type Metadata, type ModuleLibrary } from '@divi/types';
import { ActivityMetaEdit } from './edit';
import metadata from './module.json';
import { ActivityMetaAttrs } from './types';
import { placeholderContent } from './placeholder-content';

import './module.scss';
import './style.scss';

export const activityMeta: ModuleLibrary.Module.RegisterDefinition<ActivityMetaAttrs> = {
    metadata: metadata as Metadata.Values<ActivityMetaAttrs>,
    placeholderContent,
    renderers: {
        edit: ActivityMetaEdit,
    },
};
