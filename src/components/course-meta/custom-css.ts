/**
 * Course Meta Module custom CSS fields.
 *
 * @package LeadersPath
 * @since 0.1.0
 */

import { type Module } from '@divi/types';

/**
 * Custom CSS fields for Course Meta module.
 *
 * This const is equivalent of PHP function custom_css located in
 * modules/CourseMeta/CourseMetaTrait/CustomCssTrait.php.
 *
 * @since 0.1.0
 */
export const cssFields: Module.Options.Css.FieldConfig = {
  title: {
    subName: 'title',
    selectorSuffix: ' .leaderspath-course-meta__title',
    label: 'Title',
  },
  section: {
    subName: 'section',
    selectorSuffix: ' .leaderspath-course-meta__section',
    label: 'Section',
  },
  label: {
    subName: 'label',
    selectorSuffix: ' .leaderspath-course-meta__label',
    label: 'Label',
  },
  value: {
    subName: 'value',
    selectorSuffix: ' .leaderspath-course-meta__value',
    label: 'Value',
  },
  badge: {
    subName: 'badge',
    selectorSuffix: ' .leaderspath-course-meta__badge',
    label: 'Badge',
  },
};
