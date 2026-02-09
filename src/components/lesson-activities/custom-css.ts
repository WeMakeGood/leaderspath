/**
 * Lesson Activities Module custom CSS fields.
 *
 * @package LeadersPath
 * @since 0.1.0
 */

import { type Module } from '@divi/types';

/**
 * Custom CSS fields for Lesson Activities module.
 *
 * This const is equivalent of PHP function custom_css located in
 * modules/LessonActivities/LessonActivitiesTrait/CustomCssTrait.php.
 *
 * @since 0.1.0
 */
export const cssFields: Module.Options.Css.FieldConfig = {
  title: {
    subName: 'title',
    selectorSuffix: ' .leaderspath-lesson-activities__title',
    label: 'Title',
  },
  list: {
    subName: 'list',
    selectorSuffix: ' .leaderspath-lesson-activities__list',
    label: 'List',
  },
  item: {
    subName: 'item',
    selectorSuffix: ' .leaderspath-lesson-activities__item',
    label: 'List Item',
  },
  number: {
    subName: 'number',
    selectorSuffix: ' .leaderspath-lesson-activities__number',
    label: 'Item Number',
  },
  link: {
    subName: 'link',
    selectorSuffix: ' .leaderspath-lesson-activities__link',
    label: 'Activity Link',
  },
  empty: {
    subName: 'empty',
    selectorSuffix: ' .leaderspath-lesson-activities__empty',
    label: 'Empty State',
  },
};
