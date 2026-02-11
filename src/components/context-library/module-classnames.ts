import { ModuleClassnamesParams, textOptionsClassnames } from '@divi/module';
import { ContextLibraryAttrs } from './types';

export const moduleClassnames = ({
    classnamesInstance,
    attrs,
}: ModuleClassnamesParams<ContextLibraryAttrs>): void => {
    classnamesInstance.add(
        textOptionsClassnames(attrs?.module?.advanced?.text)
    );
};
