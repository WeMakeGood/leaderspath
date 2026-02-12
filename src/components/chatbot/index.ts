import { type Metadata, type ModuleLibrary } from '@divi/types';
import { ChatbotEdit } from './edit';
import metadata from './module.json';
import { ChatbotAttrs } from './types';
import { placeholderContent } from './placeholder-content';

import './module.scss';
import './style.scss';

export const chatbot: ModuleLibrary.Module.RegisterDefinition<ChatbotAttrs> = {
    metadata: metadata as Metadata.Values<ChatbotAttrs>,
    placeholderContent,
    renderers: {
        edit: ChatbotEdit,
    },
};
