import { addFilter } from '@wordpress/hooks';

import * as lessonMetaIcon from './icons/lesson-meta';
import * as lessonObjectivesIcon from './icons/lesson-objectives';
import * as lessonActivitiesIcon from './icons/lesson-activities';
import * as activityMetaIcon from './icons/activity-meta';
import * as courseLessonsIcon from './icons/course-lessons';
import * as contextLibraryIcon from './icons/context-library';
import * as skillsListIcon from './icons/skills-list';

addFilter('divi.iconLibrary.icon.map', 'leaderspath', (icons) => ({
    ...icons,
    [lessonMetaIcon.name]: lessonMetaIcon,
    [lessonObjectivesIcon.name]: lessonObjectivesIcon,
    [lessonActivitiesIcon.name]: lessonActivitiesIcon,
    [activityMetaIcon.name]: activityMetaIcon,
    [courseLessonsIcon.name]: courseLessonsIcon,
    [contextLibraryIcon.name]: contextLibraryIcon,
    [skillsListIcon.name]: skillsListIcon,
}));
