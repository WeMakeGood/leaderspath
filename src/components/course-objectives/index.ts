/**
 * Course Objectives Module entry point.
 *
 * @package LeadersPath
 * @since 0.1.0
 */

import { type Metadata, type ModuleLibrary } from '@divi/types';

import metadata from './module.json';
import { CourseObjectivesEdit } from './edit';
import { CourseObjectivesAttrs } from './types';
import { placeholderContent } from './placeholder-content';

// Styles.
import './style.scss';

/**
 * Course Objectives Module registration definition.
 *
 * @since 0.1.0
 */
export const courseObjectivesModule: ModuleLibrary.Module.RegisterDefinition<CourseObjectivesAttrs> = {
  // Imported json has no inferred type hence type-cast is necessary.
  metadata: metadata as Metadata.Values<CourseObjectivesAttrs>,
  placeholderContent,
  renderers: {
    edit: CourseObjectivesEdit,
  },
};
