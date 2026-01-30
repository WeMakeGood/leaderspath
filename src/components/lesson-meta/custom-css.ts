/**
 * Lesson Meta Module custom CSS fields.
 *
 * @package LeadersPath
 * @since 0.1.0
 */

import { type Module } from '@divi/types';

/**
 * Custom CSS fields for Lesson Meta module.
 *
 * This const is equivalent of PHP function custom_css located in
 * modules/LessonMeta/LessonMetaTrait/CustomCssTrait.php.
 *
 * @since 0.1.0
 */
export const cssFields: Module.Options.Css.FieldConfig = {
  title: {
    subName: 'title',
    selectorSuffix: ' .leaderspath-lesson-meta__title',
    label: 'Title',
  },
  section: {
    subName: 'section',
    selectorSuffix: ' .leaderspath-lesson-meta__section',
    label: 'Section',
  },
  label: {
    subName: 'label',
    selectorSuffix: ' .leaderspath-lesson-meta__label',
    label: 'Label',
  },
  value: {
    subName: 'value',
    selectorSuffix: ' .leaderspath-lesson-meta__value',
    label: 'Value',
  },
  list: {
    subName: 'list',
    selectorSuffix: ' .leaderspath-lesson-meta__list',
    label: 'List',
  },
  badge: {
    subName: 'badge',
    selectorSuffix: ' .leaderspath-lesson-meta__badge',
    label: 'Badge',
  },
};
