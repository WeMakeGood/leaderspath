/**
 * LeadersPath Divi 5 Module Bundle — Visual Builder entry point.
 *
 * Registers all LeadersPath modules with the Divi module library.
 * This bundle only runs inside the Visual Builder (React).
 * Frontend rendering is handled by PHP render_callbacks.
 *
 * @package LeadersPath
 * @since   0.7.0
 */

import { addAction } from '@wordpress/hooks';
import { registerModule } from '@divi/module-library';
import { omit } from 'lodash';

import './module-icons';

import { lessonMeta } from './components/lesson-meta';
import { lessonObjectives } from './components/lesson-objectives';
import { lessonActivities } from './components/lesson-activities';

/**
 * Register all LeadersPath modules after Divi's module library store is ready.
 */
addAction('divi.moduleLibrary.registerModuleLibraryStore.after', 'leaderspath', () => {
    registerModule(lessonMeta.metadata, omit(lessonMeta, 'metadata'));
    registerModule(lessonObjectives.metadata, omit(lessonObjectives, 'metadata'));
    registerModule(lessonActivities.metadata, omit(lessonActivities, 'metadata'));
});
