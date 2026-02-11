import { ModuleClassnamesParams, textOptionsClassnames } from '@divi/module';
import { ActivityMetaAttrs } from './types';

export const moduleClassnames = ({
    classnamesInstance,
    attrs,
}: ModuleClassnamesParams<ActivityMetaAttrs>): void => {
    classnamesInstance.add(
        textOptionsClassnames(attrs?.module?.advanced?.text)
    );
};
