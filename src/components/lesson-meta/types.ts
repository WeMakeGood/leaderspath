import { ModuleEditProps } from '@divi/module-library';
import {
    FormatBreakpointStateAttr,
    InternalAttrs,
    type Element,
} from '@divi/types';

interface LessonMetaCssAttr {
    list?: string;
    label?: string;
    duration?: string;
    activities?: string;
}

interface LessonMetaContentValues {
    showDuration?: string;
    durationLabel?: string;
    showActivities?: string;
    activitiesLabel?: string;
}

export interface LessonMetaAttrs extends InternalAttrs {
    css?: FormatBreakpointStateAttr<LessonMetaCssAttr>;
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
        innerContent?: FormatBreakpointStateAttr<LessonMetaContentValues>;
    };
    list?: {
        decoration?: Element.Decoration.PickedAttributes<'layout'>;
    };
    label?: {
        decoration?: Element.Decoration.PickedAttributes<'font'>;
    };
    duration?: {
        decoration?: Element.Decoration.PickedAttributes<'font'>;
    };
    activities?: {
        decoration?: Element.Decoration.PickedAttributes<'font'>;
    };
}

export type LessonMetaEditProps = ModuleEditProps<LessonMetaAttrs>;
