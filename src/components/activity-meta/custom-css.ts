/**
 * Activity Meta Module custom CSS fields.
 *
 * @package LeadersPath
 * @since 0.1.0
 */

import { type Module } from '@divi/types';

/**
 * Custom CSS fields for Activity Meta module.
 *
 * This const is equivalent of PHP function custom_css located in
 * modules/ActivityMeta/ActivityMetaTrait/CustomCssTrait.php.
 *
 * @since 0.1.0
 */
export const cssFields: Module.Options.Css.FieldConfig = {
  title: {
    subName: 'title',
    selectorSuffix: ' .leaderspath-activity-meta__title',
    label: 'Title',
  },
  section: {
    subName: 'section',
    selectorSuffix: ' .leaderspath-activity-meta__section',
    label: 'Section',
  },
  label: {
    subName: 'label',
    selectorSuffix: ' .leaderspath-activity-meta__label',
    label: 'Label',
  },
  value: {
    subName: 'value',
    selectorSuffix: ' .leaderspath-activity-meta__value',
    label: 'Value',
  },
  list: {
    subName: 'list',
    selectorSuffix: ' .leaderspath-activity-meta__list',
    label: 'List',
  },
  badge: {
    subName: 'badge',
    selectorSuffix: ' .leaderspath-activity-meta__badge',
    label: 'Badge',
  },
};
