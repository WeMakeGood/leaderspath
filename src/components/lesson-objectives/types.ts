import { ModuleEditProps } from '@divi/module-library';
import {
    FormatBreakpointStateAttr,
    InternalAttrs,
    type Element,
} from '@divi/types';

interface LessonObjectivesCssAttr {
    heading?: string;
    list?: string;
    item?: string;
}

interface LessonObjectivesContentValues {
    showHeading?: string;
    headingText?: string;
}

export interface LessonObjectivesAttrs extends InternalAttrs {
    css?: FormatBreakpointStateAttr<LessonObjectivesCssAttr>;
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
        innerContent?: FormatBreakpointStateAttr<LessonObjectivesContentValues>;
    };
    list?: {
        decoration?: Element.Decoration.PickedAttributes<'layout'>;
    };
    heading?: {
        decoration?: Element.Decoration.PickedAttributes<'font'>;
    };
    item?: {
        decoration?: Element.Decoration.PickedAttributes<'font'>;
    };
}

export type LessonObjectivesEditProps = ModuleEditProps<LessonObjectivesAttrs>;
