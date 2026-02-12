import { ModuleEditProps } from '@divi/module-library';
import {
    FormatBreakpointStateAttr,
    InternalAttrs,
    type Element,
} from '@divi/types';

interface ContextLibraryCssAttr {
    heading?: string;
    grid?: string;
    card?: string;
    cardIcon?: string;
    cardTitle?: string;
    cardDesc?: string;
    cardMeta?: string;
    viewButton?: string;
    downloadButton?: string;
    modal?: string;
}

interface ContextLibraryContentValues {
    showHeading?: string;
    headingText?: string;
    showIcon?: string;
    showDescription?: string;
    showMeta?: string;
    showView?: string;
    showDownload?: string;
}

export interface ContextLibraryAttrs extends InternalAttrs {
    css?: FormatBreakpointStateAttr<ContextLibraryCssAttr>;
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
        innerContent?: FormatBreakpointStateAttr<ContextLibraryContentValues>;
    };
    grid?: {
        decoration?: Element.Decoration.PickedAttributes<'layout'>;
    };
    heading?: {
        decoration?: Element.Decoration.PickedAttributes<'font'>;
    };
    card?: {
        decoration?: Element.Decoration.PickedAttributes<
            'background' | 'border' | 'spacing' | 'boxShadow'
        >;
    };
    cardIcon?: {
        advanced?: {
            color?: FormatBreakpointStateAttr<string>;
            size?: FormatBreakpointStateAttr<string>;
        };
    };
    cardTitle?: {
        decoration?: Element.Decoration.PickedAttributes<'font'>;
    };
    cardDesc?: {
        decoration?: Element.Decoration.PickedAttributes<'font'>;
    };
    cardMeta?: {
        decoration?: Element.Decoration.PickedAttributes<'font'>;
    };
    viewButton?: {
        decoration?: Element.Decoration.PickedAttributes<'button'>;
    };
    downloadButton?: {
        decoration?: Element.Decoration.PickedAttributes<'button'>;
    };
}

export type ContextLibraryEditProps = ModuleEditProps<ContextLibraryAttrs>;
