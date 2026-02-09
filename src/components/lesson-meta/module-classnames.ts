/**
 * Lesson Meta Module classnames function.
 *
 * @package LeadersPath
 * @since 0.1.0
 */

import { type ModuleLibrary } from '@divi/types';
import {
  elementClassnames,
  textOptionsClassnames,
} from '@divi/module';

import { LessonMetaAttrs } from './types';

/**
 * Generate classnames for Lesson Meta Module.
 *
 * This function is equivalent of PHP function module_classnames located in
 * modules/LessonMeta/LessonMetaTrait/ModuleClassnamesTrait.php.
 *
 * @since 0.1.0
 *
 * @param {ModuleLibrary.Module.Classnames.Args<LessonMetaAttrs>} args Classnames arguments.
 * @returns {void}
 */
export const moduleClassnames = ({
  classnamesInstance,
  attrs,
}: ModuleLibrary.Module.Classnames.Args<LessonMetaAttrs>): void => {
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
