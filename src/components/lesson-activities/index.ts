/**
 * Lesson Activities Module entry point.
 *
 * @package LeadersPath
 * @since 0.1.0
 */

import { type Metadata, type ModuleLibrary } from '@divi/types';

import metadata from './module.json';
import { LessonActivitiesEdit } from './edit';
import { LessonActivitiesAttrs } from './types';
import { placeholderContent } from './placeholder-content';

// Styles.
import './style.scss';

/**
 * Lesson Activities Module registration definition.
 *
 * @since 0.1.0
 */
export const lessonActivitiesModule: ModuleLibrary.Module.RegisterDefinition<LessonActivitiesAttrs> = {
  // Imported json has no inferred type hence type-cast is necessary.
  metadata: metadata as Metadata.Values<LessonActivitiesAttrs>,
  placeholderContent,
  renderers: {
    edit: LessonActivitiesEdit,
  },
};
