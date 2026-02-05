/**
 * Course Objectives Module TypeScript type definitions.
 *
 * @package LeadersPath
 * @since 0.1.0
 */

import {
  type Module,
  type ModuleLibrary,
} from '@divi/types';

/**
 * Course Objectives Module attributes interface.
 *
 * @since 0.1.0
 */
export interface CourseObjectivesAttrs extends Module.Attributes.Base {
  module?: Module.Element.Attrs;
  title?: Module.Element.Attrs;
  emptyState?: Module.Element.Attrs;
  css?: Module.Options.Css.Attr;
}

/**
 * Course Objectives Module edit component props.
 *
 * @since 0.1.0
 */
export type CourseObjectivesEditProps = ModuleLibrary.Module.EditProps<CourseObjectivesAttrs>;
