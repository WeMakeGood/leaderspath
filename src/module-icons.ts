import { addFilter } from '@wordpress/hooks';

import * as lessonMetaIcon from './icons/lesson-meta';
import * as lessonObjectivesIcon from './icons/lesson-objectives';

addFilter('divi.iconLibrary.icon.map', 'leaderspath', (icons) => ({
    ...icons,
    [lessonMetaIcon.name]: lessonMetaIcon,
    [lessonObjectivesIcon.name]: lessonObjectivesIcon,
}));
