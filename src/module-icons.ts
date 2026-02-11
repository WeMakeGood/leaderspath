import { addFilter } from '@wordpress/hooks';

import * as lessonMetaIcon from './icons/lesson-meta';

addFilter('divi.iconLibrary.icon.map', 'leaderspath', (icons) => ({
    ...icons,
    [lessonMetaIcon.name]: lessonMetaIcon,
}));
