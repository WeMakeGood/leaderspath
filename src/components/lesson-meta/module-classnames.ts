import { ModuleClassnamesParams, textOptionsClassnames } from '@divi/module';
import { LessonMetaAttrs } from './types';

export const moduleClassnames = ({
    classnamesInstance,
    attrs,
}: ModuleClassnamesParams<LessonMetaAttrs>): void => {
    classnamesInstance.add(
        textOptionsClassnames(attrs?.module?.advanced?.text)
    );
};
