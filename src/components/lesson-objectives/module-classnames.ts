import { ModuleClassnamesParams, textOptionsClassnames } from '@divi/module';
import { LessonObjectivesAttrs } from './types';

export const moduleClassnames = ({
    classnamesInstance,
    attrs,
}: ModuleClassnamesParams<LessonObjectivesAttrs>): void => {
    classnamesInstance.add(
        textOptionsClassnames(attrs?.module?.advanced?.text)
    );
};
