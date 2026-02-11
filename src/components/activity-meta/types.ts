import { ModuleEditProps } from '@divi/module-library';
import {
    FormatBreakpointStateAttr,
    InternalAttrs,
    type Element,
} from '@divi/types';

interface ActivityMetaCssAttr {
    list?: string;
    label?: string;
    duration?: string;
    model?: string;
    modelSwitch?: string;
}

interface ActivityMetaContentValues {
    showDuration?: string;
    durationLabel?: string;
    showModel?: string;
    modelLabel?: string;
    showModelSwitch?: string;
    modelSwitchLabel?: string;
}

export interface ActivityMetaAttrs extends InternalAttrs {
    css?: FormatBreakpointStateAttr<ActivityMetaCssAttr>;
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
        innerContent?: FormatBreakpointStateAttr<ActivityMetaContentValues>;
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
    model?: {
        decoration?: Element.Decoration.PickedAttributes<'font'>;
    };
    modelSwitch?: {
        decoration?: Element.Decoration.PickedAttributes<'font'>;
    };
}

export type ActivityMetaEditProps = ModuleEditProps<ActivityMetaAttrs>;
