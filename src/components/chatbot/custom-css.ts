import { __ } from '@wordpress/i18n';

export const cssFields = {
    container: {
        subName: 'container',
        selectorSuffix: ' .leaderspath_chatbot__container',
        label: __('Container', 'leaderspath'),
    },
    heading: {
        subName: 'heading',
        selectorSuffix: ' .leaderspath_chatbot__heading',
        label: __('Heading', 'leaderspath'),
    },
    messages: {
        subName: 'messages',
        selectorSuffix: ' .leaderspath_chatbot__messages',
        label: __('Messages Area', 'leaderspath'),
    },
    userMessage: {
        subName: 'userMessage',
        selectorSuffix: ' .leaderspath_chatbot__message--user .leaderspath_chatbot__message__content',
        label: __('User Message', 'leaderspath'),
    },
    assistantMessage: {
        subName: 'assistantMessage',
        selectorSuffix: ' .leaderspath_chatbot__message--assistant .leaderspath_chatbot__message__content',
        label: __('Assistant Message', 'leaderspath'),
    },
    inputArea: {
        subName: 'inputArea',
        selectorSuffix: ' .leaderspath_chatbot__input_area',
        label: __('Input Area', 'leaderspath'),
    },
    inputField: {
        subName: 'inputField',
        selectorSuffix: ' .leaderspath_chatbot__input',
        label: __('Input Field', 'leaderspath'),
    },
    sendButton: {
        subName: 'sendButton',
        selectorSuffix: ' .leaderspath_chatbot__send',
        label: __('Send Button', 'leaderspath'),
    },
    modelSelect: {
        subName: 'modelSelect',
        selectorSuffix: ' .leaderspath_chatbot__model_select',
        label: __('Model Selector', 'leaderspath'),
    },
};
