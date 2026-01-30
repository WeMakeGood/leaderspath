/**
 * Chatbot Module classnames function.
 *
 * @package LeadersPath
 * @since 0.1.0
 */

import { type ModuleLibrary } from '@divi/types';
import {
  textOptionsClassnames,
  elementClassnames,
} from '@divi/module';

/**
 * Generate classnames for the Chatbot module.
 *
 * @since 0.1.0
 *
 * @param {ModuleLibrary.Module.Classnames.Args} args Classnames arguments.
 */
export const moduleClassnames = ({
  classnamesInstance,
  attrs,
}: ModuleLibrary.Module.Classnames.Args): void => {
  // Add text option classnames.
  classnamesInstance.add(
    textOptionsClassnames(attrs?.module?.advanced?.text),
    true
  );

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
