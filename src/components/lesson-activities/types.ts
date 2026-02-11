import { ModuleEditProps } from '@divi/module-library';
import {
    FormatBreakpointStateAttr,
    InternalAttrs,
    type Element,
} from '@divi/types';

interface LessonActivitiesCssAttr {
    heading?: string;
    list?: string;
    item?: string;
    title?: string;
    number?: string;
    duration?: string;
    excerpt?: string;
}

interface LessonActivitiesContentValues {
    showHeading?: string;
    headingText?: string;
    showDuration?: string;
    showExcerpt?: string;
    showNumber?: string;
}

export interface LessonActivitiesAttrs extends InternalAttrs {
    css?: FormatBreakpointStateAttr<LessonActivitiesCssAttr>;
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
        innerContent?: FormatBreakpointStateAttr<LessonActivitiesContentValues>;
    };
    list?: {
        decoration?: Element.Decoration.PickedAttributes<'layout'>;
    };
    heading?: {
        decoration?: Element.Decoration.PickedAttributes<'font'>;
    };
    title?: {
        decoration?: Element.Decoration.PickedAttributes<'font'>;
    };
    number?: {
        decoration?: Element.Decoration.PickedAttributes<
            'font' | 'background' | 'border' | 'spacing' | 'boxShadow'
        >;
    };
    duration?: {
        decoration?: Element.Decoration.PickedAttributes<'font'>;
    };
    excerpt?: {
        decoration?: Element.Decoration.PickedAttributes<'font'>;
    };
}

export type LessonActivitiesEditProps = ModuleEditProps<LessonActivitiesAttrs>;
