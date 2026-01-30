/**
 * Lesson Meta Module entry point.
 *
 * @package LeadersPath
 * @since 0.1.0
 */

import { type Metadata, type ModuleLibrary } from '@divi/types';

import metadata from './module.json';
import { LessonMetaEdit } from './edit';
import { LessonMetaAttrs } from './types';
import { placeholderContent } from './placeholder-content';

// Styles
import './style.scss';

/**
 * Lesson Meta Module registration definition.
 */
export const lessonMetaModule: ModuleLibrary.Module.RegisterDefinition<LessonMetaAttrs> = {
  metadata: metadata as Metadata.Values<LessonMetaAttrs>,
  placeholderContent,
  renderers: {
    edit: LessonMetaEdit,
  },
};
