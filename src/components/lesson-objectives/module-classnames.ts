/**
 * Lesson Objectives Module classnames function.
 *
 * @package LeadersPath
 * @since 0.1.0
 */

import { type ModuleLibrary } from '@divi/types';
import {
  elementClassnames,
  textOptionsClassnames,
} from '@divi/module';

import { LessonObjectivesAttrs } from './types';

/**
 * Generate classnames for Lesson Objectives Module.
 *
 * This function is equivalent of PHP function module_classnames located in
 * modules/LessonObjectives/LessonObjectivesTrait/ModuleClassnamesTrait.php.
 *
 * @since 0.1.0
 *
 * @param {ModuleLibrary.Module.Classnames.Args<LessonObjectivesAttrs>} args Classnames arguments.
 * @returns {void}
 */
export const moduleClassnames = ({
  classnamesInstance,
  attrs,
}: ModuleLibrary.Module.Classnames.Args<LessonObjectivesAttrs>): void => {
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
