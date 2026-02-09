/**
 * Lesson Activities Module classnames function.
 *
 * @package LeadersPath
 * @since 0.1.0
 */

import { type ModuleLibrary } from '@divi/types';
import {
  elementClassnames,
  textOptionsClassnames,
} from '@divi/module';

import { LessonActivitiesAttrs } from './types';

/**
 * Generate classnames for Lesson Activities Module.
 *
 * This function is equivalent of PHP function module_classnames located in
 * modules/LessonActivities/LessonActivitiesTrait/ModuleClassnamesTrait.php.
 *
 * @since 0.1.0
 *
 * @param {ModuleLibrary.Module.Classnames.Args<LessonActivitiesAttrs>} args Classnames arguments.
 * @returns {void}
 */
export const moduleClassnames = ({
  classnamesInstance,
  attrs,
}: ModuleLibrary.Module.Classnames.Args<LessonActivitiesAttrs>): void => {
  // Add text option classnames.
  classnamesInstance.add(textOptionsClassnames(attrs?.module?.advanced?.text), true);

  // Add element classnames.
  classnamesInstance.add(
    elementClassnames({
      attrs: {
        ...attrs?.module?.decoration,
        link: attrs?.module?.advanced?.link,
      },
    })
  );
};
