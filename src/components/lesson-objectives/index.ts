/**
 * Lesson Objectives Module entry point.
 *
 * @package LeadersPath
 * @since 0.1.0
 */

import { type Metadata, type ModuleLibrary } from '@divi/types';

import metadata from './module.json';
import { LessonObjectivesEdit } from './edit';
import { LessonObjectivesAttrs } from './types';
import { placeholderContent } from './placeholder-content';

// Styles.
import './style.scss';

/**
 * Lesson Objectives Module registration definition.
 *
 * @since 0.1.0
 */
export const lessonObjectivesModule: ModuleLibrary.Module.RegisterDefinition<LessonObjectivesAttrs> = {
  // Imported json has no inferred type hence type-cast is necessary.
  metadata: metadata as Metadata.Values<LessonObjectivesAttrs>,
  placeholderContent,
  renderers: {
    edit: LessonObjectivesEdit,
  },
};
