/**
 * Activity Meta Module TypeScript type definitions.
 *
 * @package LeadersPath
 * @since 0.1.0
 */

import {
  type Module,
  type ModuleLibrary,
} from '@divi/types';

/**
 * Activity Meta Module attributes interface.
 *
 * @since 0.1.0
 */
export interface ActivityMetaAttrs extends Module.Attributes.Base {
  module?: Module.Element.Attrs;
  title?: Module.Element.Attrs;
  duration?: {
    advanced?: {
      show?: Module.Attributes.Attribute;
    };
  };
  durationLabel?: Module.Element.Attrs;
  objectives?: {
    advanced?: {
      show?: Module.Attributes.Attribute;
    };
  };
  objectivesLabel?: Module.Element.Attrs;
  model?: {
    advanced?: {
      show?: Module.Attributes.Attribute;
    };
  };
  modelLabel?: Module.Element.Attrs;
  css?: Module.Options.Css.Attr;
}

/**
 * Activity Meta Module edit component props.
 *
 * @since 0.1.0
 */
export type ActivityMetaEditProps = ModuleLibrary.Module.EditProps<ActivityMetaAttrs>;
