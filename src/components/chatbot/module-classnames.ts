import { ModuleClassnamesParams, textOptionsClassnames } from '@divi/module';
import { ChatbotAttrs } from './types';

export const moduleClassnames = ({
    classnamesInstance,
    attrs,
}: ModuleClassnamesParams<ChatbotAttrs>): void => {
    classnamesInstance.add(
        textOptionsClassnames(attrs?.module?.advanced?.text)
    );
};
