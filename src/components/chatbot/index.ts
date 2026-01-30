/**
 * Chatbot Module entry point.
 *
 * @package LeadersPath
 * @since 0.1.0
 */

import { type Metadata, type ModuleLibrary } from '@divi/types';

import metadata from './module.json';
import { ChatbotEdit } from './edit';
import { placeholderContent } from './placeholder-content';

// Styles.
import './style.scss';

/**
 * Chatbot Module registration definition.
 *
 * Using base RegisterDefinition without generic for Divi compatibility.
 *
 * @since 0.1.0
 */
export const chatbotModule: ModuleLibrary.Module.RegisterDefinition = {
  // Imported json has no inferred type hence type-cast is necessary.
  metadata: metadata as Metadata.Values,
  placeholderContent,
  renderers: {
    edit: ChatbotEdit,
  },
};
