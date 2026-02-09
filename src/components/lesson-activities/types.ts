/**
 * Lesson Activities Module TypeScript type definitions.
 *
 * @package LeadersPath
 * @since 0.1.0
 */

import {
  type Module,
  type ModuleLibrary,
} from '@divi/types';

/**
 * Lesson Activities Module attributes interface.
 *
 * @since 0.1.0
 */
export interface LessonActivitiesAttrs extends Module.Attributes.Base {
  module?: Module.Element.Attrs;
  title?: Module.Element.Attrs;
  emptyState?: Module.Element.Attrs;
  css?: Module.Options.Css.Attr;
}

/**
 * Lesson Activities Module edit component props.
 *
 * @since 0.1.0
 */
export type LessonActivitiesEditProps = ModuleLibrary.Module.EditProps<LessonActivitiesAttrs>;
