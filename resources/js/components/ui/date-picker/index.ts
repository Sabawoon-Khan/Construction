export { default as DatePicker } from './DatePicker.vue';

export function preventOutsideDismissForDatePicker(event: {
    preventDefault: () => void;
    detail?: { originalEvent?: Event };
    target?: EventTarget | null;
}): void {
    const target = (event.detail?.originalEvent?.target ?? event.target) as
        | Element
        | null;
    if (target instanceof Element && target.closest('[data-date-picker-panel]')) {
        event.preventDefault();
    }
}
