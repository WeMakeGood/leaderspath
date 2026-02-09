/**
 * Course Activities Module TypeScript type definitions.
 *
 * @package LeadersPath
 * @since 0.1.0
 */

import {
  type Module,
  type ModuleLibrary,
} from '@divi/types';

/**
 * Course Activities Module attributes interface.
 *
 * @since 0.1.0
 */
export interface CourseActivitiesAttrs extends Module.Attributes.Base {
  module?: Module.Element.Attrs;
  title?: Module.Element.Attrs;
  emptyState?: Module.Element.Attrs;
  css?: Module.Options.Css.Attr;
}

/**
 * Course Activities Module edit component props.
 *
 * @since 0.1.0
 */
export type CourseActivitiesEditProps = ModuleLibrary.Module.EditProps<CourseActivitiesAttrs>;
