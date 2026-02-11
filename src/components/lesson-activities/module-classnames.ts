import { ModuleClassnamesParams, textOptionsClassnames } from '@divi/module';
import { LessonActivitiesAttrs } from './types';

export const moduleClassnames = ({
    classnamesInstance,
    attrs,
}: ModuleClassnamesParams<LessonActivitiesAttrs>): void => {
    classnamesInstance.add(
        textOptionsClassnames(attrs?.module?.advanced?.text)
    );
};
