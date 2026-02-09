/**
 * Course Activities Module custom CSS fields.
 *
 * @package LeadersPath
 * @since 0.1.0
 */

import { type Module } from '@divi/types';

/**
 * Custom CSS fields for Course Activities module.
 *
 * This const is equivalent of PHP function custom_css located in
 * modules/CourseActivities/CourseActivitiesTrait/CustomCssTrait.php.
 *
 * @since 0.1.0
 */
export const cssFields: Module.Options.Css.FieldConfig = {
  title: {
    subName: 'title',
    selectorSuffix: ' .leaderspath-course-activities__title',
    label: 'Title',
  },
  list: {
    subName: 'list',
    selectorSuffix: ' .leaderspath-course-activities__list',
    label: 'List',
  },
  item: {
    subName: 'item',
    selectorSuffix: ' .leaderspath-course-activities__item',
    label: 'List Item',
  },
  number: {
    subName: 'number',
    selectorSuffix: ' .leaderspath-course-activities__number',
    label: 'Item Number',
  },
  link: {
    subName: 'link',
    selectorSuffix: ' .leaderspath-course-activities__link',
    label: 'Activity Link',
  },
  empty: {
    subName: 'empty',
    selectorSuffix: ' .leaderspath-course-activities__empty',
    label: 'Empty State',
  },
};
