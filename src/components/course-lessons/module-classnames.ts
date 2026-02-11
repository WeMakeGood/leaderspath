import { ModuleClassnamesParams, textOptionsClassnames } from '@divi/module';
import { CourseLessonsAttrs } from './types';

export const moduleClassnames = ({
    classnamesInstance,
    attrs,
}: ModuleClassnamesParams<CourseLessonsAttrs>): void => {
    classnamesInstance.add(
        textOptionsClassnames(attrs?.module?.advanced?.text)
    );
};
