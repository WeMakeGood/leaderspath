/**
 * Skills List Module classnames function.
 *
 * @package LeadersPath
 * @since 0.1.0
 */

import { ModuleClassnamesParams, textOptionsClassnames } from '@divi/module';

import { SkillsListAttrs } from './types';

/**
 * Generate module classnames for the Skills List module.
 *
 * @since 0.1.0
 *
 * @param {ModuleClassnamesParams<SkillsListAttrs>} params Classnames parameters.
 * @returns {void}
 */
export const moduleClassnames = ({
  classnamesInstance,
  attrs,
}: ModuleClassnamesParams<SkillsListAttrs>): void => {
  // Add base module classname.
  classnamesInstance.add('leaderspath-skills-list');

  // Add text options classnames.
  textOptionsClassnames(classnamesInstance, attrs?.module?.advanced?.text);
};
