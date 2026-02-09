/**
 * Lesson Meta Module TypeScript type definitions.
 *
 * @package LeadersPath
 * @since 0.1.0
 */

import {
  type Module,
  type ModuleLibrary,
} from '@divi/types';

/**
 * Lesson Meta Module attributes interface.
 *
 * @since 0.1.0
 */
export interface LessonMetaAttrs extends Module.Attributes.Base {
  module?: Module.Element.Attrs;
  title?: Module.Element.Attrs;
  duration?: {
    advanced?: {
      show?: Module.Attributes.Attribute;
    };
  };
  durationLabel?: Module.Element.Attrs;
  difficulty?: {
    advanced?: {
      show?: Module.Attributes.Attribute;
    };
  };
  difficultyLabel?: Module.Element.Attrs;
  activities?: {
    advanced?: {
      show?: Module.Attributes.Attribute;
    };
  };
  activitiesLabel?: Module.Element.Attrs;
  css?: Module.Options.Css.Attr;
}

/**
 * Lesson Meta Module edit component props.
 *
 * @since 0.1.0
 */
export type LessonMetaEditProps = ModuleLibrary.Module.EditProps<LessonMetaAttrs>;
