/**
 * Course Activities Module entry point.
 *
 * @package LeadersPath
 * @since 0.1.0
 */

import { type Metadata, type ModuleLibrary } from '@divi/types';

import metadata from './module.json';
import { CourseActivitiesEdit } from './edit';
import { CourseActivitiesAttrs } from './types';
import { placeholderContent } from './placeholder-content';

// Styles.
import './style.scss';

/**
 * Course Activities Module registration definition.
 *
 * @since 0.1.0
 */
export const courseActivitiesModule: ModuleLibrary.Module.RegisterDefinition<CourseActivitiesAttrs> = {
  // Imported json has no inferred type hence type-cast is necessary.
  metadata: metadata as Metadata.Values<CourseActivitiesAttrs>,
  placeholderContent,
  renderers: {
    edit: CourseActivitiesEdit,
  },
};
