/**
 * Course Objectives Module custom CSS fields.
 *
 * @package LeadersPath
 * @since 0.1.0
 */

import { type Module } from '@divi/types';

/**
 * Custom CSS fields for Course Objectives module.
 *
 * This const is equivalent of PHP function custom_css located in
 * modules/CourseObjectives/CourseObjectivesTrait/CustomCssTrait.php.
 *
 * @since 0.1.0
 */
export const cssFields: Module.Options.Css.FieldConfig = {
  title: {
    subName: 'title',
    selectorSuffix: ' .leaderspath-course-objectives__title',
    label: 'Title',
  },
  list: {
    subName: 'list',
    selectorSuffix: ' .leaderspath-course-objectives__list',
    label: 'List',
  },
  item: {
    subName: 'item',
    selectorSuffix: ' .leaderspath-course-objectives__item',
    label: 'List Item',
  },
  empty: {
    subName: 'empty',
    selectorSuffix: ' .leaderspath-course-objectives__empty',
    label: 'Empty State',
  },
};
