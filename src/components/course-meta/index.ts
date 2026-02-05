/**
 * Course Meta Module entry point.
 *
 * @package LeadersPath
 * @since 0.1.0
 */

import { type Metadata, type ModuleLibrary } from '@divi/types';

import metadata from './module.json';
import { CourseMetaEdit } from './edit';
import { CourseMetaAttrs } from './types';
import { placeholderContent } from './placeholder-content';

// Styles.
import './style.scss';

/**
 * Course Meta Module registration definition.
 *
 * @since 0.1.0
 */
export const courseMetaModule: ModuleLibrary.Module.RegisterDefinition<CourseMetaAttrs> = {
  // Imported json has no inferred type hence type-cast is necessary.
  metadata: metadata as Metadata.Values<CourseMetaAttrs>,
  placeholderContent,
  renderers: {
    edit: CourseMetaEdit,
  },
};
