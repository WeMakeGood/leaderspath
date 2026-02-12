import { ModuleClassnamesParams, textOptionsClassnames } from '@divi/module';
import { SkillsListAttrs } from './types';

export const moduleClassnames = ({
    classnamesInstance,
    attrs,
}: ModuleClassnamesParams<SkillsListAttrs>): void => {
    classnamesInstance.add(
        textOptionsClassnames(attrs?.module?.advanced?.text)
    );
};
