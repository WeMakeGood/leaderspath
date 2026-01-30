/**
 * Type definitions for Hello Module.
 *
 * @package LeadersPath
 * @since 0.1.0
 */

import { type ModuleLibrary } from '@divi/types';

/**
 * Hello Module attributes interface.
 */
export interface HelloModuleAttrs {
  module: ModuleLibrary.Module.Attributes.Module;
  title: ModuleLibrary.Module.Attributes.Element;
  message: ModuleLibrary.Module.Attributes.Element;
}

/**
 * Props for Hello Module edit component.
 */
export interface HelloModuleEditProps extends ModuleLibrary.Module.Edit.ComponentProps<HelloModuleAttrs> {}
