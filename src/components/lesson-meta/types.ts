/**
 * Type definitions for Lesson Meta Module.
 *
 * @package LeadersPath
 * @since 0.1.0
 */

import { type ModuleLibrary } from '@divi/types';

/**
 * Toggle attribute interface for on/off controls.
 */
export interface ToggleAttribute {
  innerContent?: {
    desktop?: {
      value?: string;
    };
  };
}

/**
 * Lesson Meta Module attributes interface.
 */
export interface LessonMetaAttrs {
  module: ModuleLibrary.Module.Attributes.Module;
  title: ModuleLibrary.Module.Attributes.Element;
  showDuration: ToggleAttribute;
  durationLabel: ModuleLibrary.Module.Attributes.Element;
  showObjectives: ToggleAttribute;
  objectivesLabel: ModuleLibrary.Module.Attributes.Element;
  showModel: ToggleAttribute;
  modelLabel: ModuleLibrary.Module.Attributes.Element;
}

/**
 * Props for Lesson Meta Module edit component.
 */
export interface LessonMetaEditProps extends ModuleLibrary.Module.Edit.ComponentProps<LessonMetaAttrs> {}
