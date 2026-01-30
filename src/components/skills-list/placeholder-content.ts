/**
 * Skills List Module placeholder content.
 *
 * @package LeadersPath
 * @since 0.1.0
 */

import { SkillsListAttrs } from './types';

/**
 * Default placeholder content for Skills List module.
 *
 * @since 0.1.0
 */
export const placeholderContent: SkillsListAttrs = {
  title: {
    innerContent: {
      desktop: {
        value: 'Available Skills',
      },
    },
  },
  emptyState: {
    innerContent: {
      desktop: {
        value: 'No skills are available for this lesson.',
      },
    },
  },
  skills: {
    advanced: {
      showDescription: {
        desktop: {
          value: 'on',
        },
      },
      showCompatibility: {
        desktop: {
          value: 'on',
        },
      },
      showVersion: {
        desktop: {
          value: 'on',
        },
      },
      showDownloadButton: {
        desktop: {
          value: 'on',
        },
      },
    },
  },
};
