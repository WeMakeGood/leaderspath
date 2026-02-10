/**
 * Lesson Objectives Module custom CSS fields.
 *
 * @package LeadersPath
 * @since 0.1.0
 */

import { type Module } from '@divi/types';

/**
 * Custom CSS fields for Lesson Objectives module.
 *
 * This const is equivalent of PHP function custom_css located in
 * modules/LessonObjectives/LessonObjectivesTrait/CustomCssTrait.php.
 *
 * @since 0.1.0
 */
export const cssFields: Module.Options.Css.FieldConfig = {
  content: {
    subName: 'content',
    selectorSuffix: ' .leaderspath-lesson-objectives__content',
    label: 'Content',
  },
  title: {
    subName: 'title',
    selectorSuffix: ' .leaderspath-lesson-objectives__title',
    label: 'Title',
  },
  list: {
    subName: 'list',
    selectorSuffix: ' .leaderspath-lesson-objectives__list',
    label: 'List',
  },
  item: {
    subName: 'item',
    selectorSuffix: ' .leaderspath-lesson-objectives__item',
    label: 'List Item',
  },
  empty: {
    subName: 'empty',
    selectorSuffix: ' .leaderspath-lesson-objectives__empty',
    label: 'Empty State',
  },
};
