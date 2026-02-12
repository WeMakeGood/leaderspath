import { ModuleEditProps } from '@divi/module-library';
import {
    FormatBreakpointStateAttr,
    InternalAttrs,
    type Element,
} from '@divi/types';

interface ChatbotCssAttr {
    container?: string;
    heading?: string;
    messages?: string;
    userMessage?: string;
    assistantMessage?: string;
    inputArea?: string;
    inputField?: string;
    sendButton?: string;
    modelSelect?: string;
}

interface ChatbotContentValues {
    showHeading?: string;
    headingText?: string;
    emptyText?: string;
}

export interface ChatbotAttrs extends InternalAttrs {
    css?: FormatBreakpointStateAttr<ChatbotCssAttr>;
    module?: {
        meta?: Element.Meta.Attributes;
        advanced?: {
            link?: Element.Advanced.Link.Attributes;
            htmlAttributes?: Element.Advanced.IdClasses.Attributes;
            text?: Element.Advanced.Text.Attributes;
        };
        decoration?: Element.Decoration.PickedAttributes<
            'animation' |
            'background' |
            'border' |
            'boxShadow' |
            'disabledOn' |
            'filters' |
            'overflow' |
            'position' |
            'scroll' |
            'sizing' |
            'spacing' |
            'sticky' |
            'transform' |
            'transition' |
            'zIndex'
        >;
    };
    content?: {
        innerContent?: FormatBreakpointStateAttr<ChatbotContentValues>;
    };
    heading?: {
        decoration?: Element.Decoration.PickedAttributes<'font'>;
    };
    container?: {
        decoration?: Element.Decoration.PickedAttributes<
            'background' | 'border' | 'spacing' | 'boxShadow'
        >;
    };
    userMessage?: {
        decoration?: Element.Decoration.PickedAttributes<'font' | 'background'>;
    };
    assistantMessage?: {
        decoration?: Element.Decoration.PickedAttributes<'font' | 'background'>;
    };
    inputField?: {
        decoration?: Element.Decoration.PickedAttributes<'font'>;
    };
    sendButton?: {
        decoration?: Element.Decoration.PickedAttributes<'button'>;
    };
}

export type ChatbotEditProps = ModuleEditProps<ChatbotAttrs>;
