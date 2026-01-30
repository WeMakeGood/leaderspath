/**
 * LeadersPath Divi 5 Modules Entry Point.
 *
 * This file registers all LeadersPath modules with Divi's Visual Builder.
 *
 * @package LeadersPath
 * @since 0.1.0
 */

import { omit } from 'lodash';
import { addAction } from '@wordpress/hooks';
import { registerModule } from '@divi/module-library';

// Import modules
import { helloModule } from './components/hello-module';
import { lessonMetaModule } from './components/lesson-meta';
import { contextLibraryModule } from './components/context-library';
import { skillsListModule } from './components/skills-list';

/**
 * Register all LeadersPath modules with Divi.
 *
 * This hook fires after Divi has initialized its module library store,
 * allowing us to register our custom modules.
 *
 * @since 0.1.0
 */
addAction(
  'divi.moduleLibrary.registerModuleLibraryStore.after',
  'leaderspath',
  () => {
    // Register Hello Module (test module)
    registerModule(helloModule.metadata, omit(helloModule, 'metadata'));

    // Register Lesson Meta Module
    registerModule(lessonMetaModule.metadata, omit(lessonMetaModule, 'metadata'));

    // Register Context Library Module
    registerModule(contextLibraryModule.metadata, omit(contextLibraryModule, 'metadata'));

    // Register Skills List Module
    registerModule(skillsListModule.metadata, omit(skillsListModule, 'metadata'));
  }
);
