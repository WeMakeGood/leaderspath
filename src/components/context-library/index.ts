import { type Metadata, type ModuleLibrary } from '@divi/types';
import { ContextLibraryEdit } from './edit';
import metadata from './module.json';
import { ContextLibraryAttrs } from './types';
import { placeholderContent } from './placeholder-content';

import './module.scss';
import './style.scss';

export const contextLibrary: ModuleLibrary.Module.RegisterDefinition<ContextLibraryAttrs> = {
    metadata: metadata as Metadata.Values<ContextLibraryAttrs>,
    placeholderContent,
    renderers: {
        edit: ContextLibraryEdit,
    },
};
