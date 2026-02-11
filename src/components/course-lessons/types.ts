import { ModuleEditProps } from '@divi/module-library';
import {
    FormatBreakpointStateAttr,
    InternalAttrs,
    type Element,
} from '@divi/types';

interface CourseLessonsCssAttr {
    heading?: string;
    list?: string;
    item?: string;
    title?: string;
    number?: string;
    difficulty?: string;
    duration?: string;
    activityCount?: string;
    excerpt?: string;
    meta?: string;
    prerequisites?: string;
}

interface CourseLessonsContentValues {
    showHeading?: string;
    headingText?: string;
    showNumber?: string;
    showDuration?: string;
    showDifficulty?: string;
    showActivityCount?: string;
    showExcerpt?: string;
    showPrerequisites?: string;
}

export interface CourseLessonsAttrs extends InternalAttrs {
    css?: FormatBreakpointStateAttr<CourseLessonsCssAttr>;
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
        innerContent?: FormatBreakpointStateAttr<CourseLessonsContentValues>;
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
    difficulty?: {
        decoration?: Element.Decoration.PickedAttributes<'font'>;
    };
    duration?: {
        decoration?: Element.Decoration.PickedAttributes<'font'>;
    };
    activityCount?: {
        decoration?: Element.Decoration.PickedAttributes<'font'>;
    };
    excerpt?: {
        decoration?: Element.Decoration.PickedAttributes<'font'>;
    };
}

export type CourseLessonsEditProps = ModuleEditProps<CourseLessonsAttrs>;
