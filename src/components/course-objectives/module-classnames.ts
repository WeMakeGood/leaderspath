/**
 * Course Objectives Module classnames function.
 *
 * @package LeadersPath
 * @since 0.1.0
 */

import { type ModuleLibrary } from '@divi/types';
import {
  elementClassnames,
  textOptionsClassnames,
} from '@divi/module';

import { CourseObjectivesAttrs } from './types';

/**
 * Generate classnames for Course Objectives Module.
 *
 * This function is equivalent of PHP function module_classnames located in
 * modules/CourseObjectives/CourseObjectivesTrait/ModuleClassnamesTrait.php.
 *
 * @since 0.1.0
 *
 * @param {ModuleLibrary.Module.Classnames.Args<CourseObjectivesAttrs>} args Classnames arguments.
 * @returns {void}
 */
export const moduleClassnames = ({
  classnamesInstance,
  attrs,
}: ModuleLibrary.Module.Classnames.Args<CourseObjectivesAttrs>): void => {
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
