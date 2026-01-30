/**
 * Hello Module entry point.
 *
 * @package LeadersPath
 * @since 0.1.0
 */

import { type Metadata, type ModuleLibrary } from '@divi/types';

import metadata from './module.json';
import { HelloModuleEdit } from './edit';
import { HelloModuleAttrs } from './types';
import { placeholderContent } from './placeholder-content';

// Styles
import './style.scss';

/**
 * Hello Module registration definition.
 */
export const helloModule: ModuleLibrary.Module.RegisterDefinition<HelloModuleAttrs> = {
  metadata: metadata as Metadata.Values<HelloModuleAttrs>,
  placeholderContent,
  renderers: {
    edit: HelloModuleEdit,
  },
};
