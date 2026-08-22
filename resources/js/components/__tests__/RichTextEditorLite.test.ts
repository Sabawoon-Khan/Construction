import { flushPromises, mount } from '@vue/test-utils';
import { describe, expect, it, vi } from 'vitest';
import RichTextEditorLite from '@/components/RichTextEditorLite.vue';

vi.mock('@/composables/useTranslations', () => ({
    useTranslations: () => ({
        t: (key: string) => key,
    }),
}));

describe('RichTextEditorLite', () => {
    it('loads initial html after mount', async () => {
        const wrapper = mount(RichTextEditorLite, {
            props: { modelValue: '<p>Hello</p>' },
            global: {
                stubs: { Button: true },
            },
        });

        await flushPromises();

        expect(wrapper.find('[contenteditable="true"]').element.innerHTML).toBe('<p>Hello</p>');
    });

    it('updates when modelValue changes', async () => {
        const wrapper = mount(RichTextEditorLite, {
            props: { modelValue: '<p>First</p>' },
            global: {
                stubs: { Button: true },
            },
        });

        await flushPromises();
        await wrapper.setProps({ modelValue: '<p>Second</p>' });
        await flushPromises();

        expect(wrapper.find('[contenteditable="true"]').element.innerHTML).toBe('<p>Second</p>');
    });

    it('clears the editor when modelValue becomes empty', async () => {
        const wrapper = mount(RichTextEditorLite, {
            props: { modelValue: '<p>Notes</p>' },
            global: {
                stubs: { Button: true },
            },
        });

        await flushPromises();
        await wrapper.setProps({ modelValue: '' });
        await flushPromises();

        expect(wrapper.find('[contenteditable="true"]').element.innerHTML).toBe('');
    });
});
